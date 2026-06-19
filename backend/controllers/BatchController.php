<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Batch;
use common\models\Colony;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Batch / harvest management (US-PM-05, US-PM-06) and the release gate view (US-CO-01).
 *
 * Permission map:
 *   index / view → recordHarvest | completeBatchDetails | evaluateReleaseGate
 *   harvest      → recordHarvest
 *   update       → completeBatchDetails
 *   release      → releaseBatch
 *
 * The Batch model owns lot-number generation, water-content validation,
 * the five-check release gate, and the release transition.
 */
class BatchController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'view'],
                        'roles' => ['recordHarvest', 'completeBatchDetails', 'evaluateReleaseGate'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['harvest', 'colonies-for-stand'],
                        'roles' => ['recordHarvest'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['update'],
                        'roles' => ['completeBatchDetails'],
                    ],
                    // The release decision is a Compliance act — see
                    // ComplianceController::actionRelease. Production Management
                    // only records and tracks batches; it does not release them.
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Batch::find()->with('apiaryStand')->orderBy(['harvest_date' => SORT_DESC, 'id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * Records a harvest, creating a pending-release batch linked to its
     * source colonies (US-PM-05). Ineligible colonies (active Wartezeit or
     * disease flag) are rejected server-side (AC-PM-05.2).
     */
    public function actionHarvest(): string|Response
    {
        $model = new Batch([
            'status'       => Batch::STATUS_PENDING_RELEASE,
            'harvest_date' => date('Y-m-d'),
        ]);
        $selectedColonyIds = [];

        $request = Yii::$app->request;
        if ($request->isPost) {
            $model->load($request->post());
            $model->lot_number = Batch::generateLotNumber();
            $selectedColonyIds = array_map('intval', (array) $request->post('colony_ids', []));

            // Every selected colony must currently belong to the chosen stand
            // (same guard the treatment and inspection forms enforce).
            $standError = $this->validateColoniesBelongToStand($selectedColonyIds, (int) $model->apiary_stand_id);
            if ($standError !== null) {
                $model->addError('apiary_stand_id', $standError);
            }

            $error = $this->validateHarvestColonies($selectedColonyIds, (string) $model->harvest_date);
            if ($error !== null) {
                $model->addError('harvest_date', $error);
            }

            if (!$model->hasErrors() && $model->validate()) {
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $model->save(false);
                    foreach ($selectedColonyIds as $colonyId) {
                        Yii::$app->db->createCommand()
                            ->insert('{{%batch_colony}}', ['batch_id' => $model->id, 'colony_id' => $colonyId])
                            ->execute();
                    }
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', "Harvest recorded as batch {$model->lot_number}.");
                    return $this->redirect(['view', 'id' => $model->id]);
                } catch (\Throwable $e) {
                    $transaction->rollBack();
                    throw $e;
                }
            }
        }

        return $this->render('harvest', [
            'model' => $model,
            'selectedColonyIds' => $selectedColonyIds,
        ]);
    }

    /**
     * Completes batch production and label details (US-PM-06).
     * Editable only while the batch is pending release (AC-PM-06.5).
     */
    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);

        if (!$model->isPendingRelease()) {
            Yii::$app->session->setFlash('error', 'A released batch is locked and cannot be edited (AC-PM-06.5).');
            return $this->redirect(['view', 'id' => $id]);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            // Conductivity is informational — surface a warning without blocking.
            if ($warning = $model->conductivityWarning()) {
                Yii::$app->session->setFlash('warning', $warning);
            }
            Yii::$app->session->setFlash('success', 'Batch details saved.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        // Pre-fill the origin statement on first load so the beekeeper does
        // not have to type the standard text from scratch (Change 2).
        if (empty($model->origin_statement)) {
            $model->origin_statement = 'Honey from Germany (Bavaria, Landkreis Hof)';
        }

        return $this->render('update', ['model' => $model]);
    }

    /**
     * Returns the active colonies currently assigned to a stand as JSON, for the
     * dependent colony checkbox list on the harvest form. Mirrors
     * TreatmentController::actionColoniesForStand.
     */
    public function actionColoniesForStand(int $standId): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $colonies = Colony::find()
            ->where(['apiary_stand_id' => $standId, 'status' => 'active'])
            ->orderBy(['colony_code' => SORT_ASC])
            ->all();

        $data = [];
        foreach ($colonies as $colony) {
            $expiry = $colony->getLatestWartezeitExpiry();
            $data[] = [
                'id'            => $colony->id,
                'colony_code'   => $colony->colony_code,
                'status'        => $colony->status,
                'in_withdrawal' => !$colony->isWithdrawalCleared(),
                'withdrawal_until' => $colony->isWithdrawalCleared() ? null : $expiry,
                'disease_flag'  => (bool) $colony->disease_flag,
            ];
        }

        Yii::$app->response->data = $data;
        return Yii::$app->response;
    }

    /**
     * Ensures every selected colony currently belongs to the selected stand.
     * Returns an error message string naming the offending colony, or null.
     *
     * @param int[] $colonyIds
     */
    private function validateColoniesBelongToStand(array $colonyIds, int $standId): ?string
    {
        if (empty($colonyIds) || $standId <= 0) {
            return null; // emptiness / missing stand handled by other validators
        }

        $offending = [];
        foreach (Colony::findAll(['id' => $colonyIds]) as $colony) {
            if ((int) $colony->apiary_stand_id !== $standId) {
                $offending[] = $colony->colony_code;
            }
        }

        if (!empty($offending)) {
            return 'These colonies are not assigned to the selected apiary stand: '
                . implode(', ', $offending) . '. Select the stand the colonies were located at.';
        }

        return null;
    }

    /**
     * Validates the selected source colonies for a harvest.
     * Returns an error message string, or null when the selection is valid.
     *
     * @param int[] $colonyIds
     */
    private function validateHarvestColonies(array $colonyIds, string $harvestDate): ?string
    {
        if (empty($colonyIds)) {
            return 'Select at least one source colony (AC-PM-05.1).';
        }

        $blocked = [];
        foreach (Colony::findAll(['id' => $colonyIds]) as $colony) {
            if (!$colony->isEligibleForHarvest($harvestDate)) {
                $blocked[] = $colony->colony_code;
            }
        }

        if (!empty($blocked)) {
            return 'These colonies are blocked (active Wartezeit or disease flag) on this harvest date: '
                . implode(', ', $blocked);
        }

        return null;
    }

    private function findModel(int $id): Batch
    {
        $model = Batch::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('The requested batch does not exist.');
        }
        return $model;
    }
}
