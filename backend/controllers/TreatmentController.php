<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Colony;
use common\models\Treatment;
use common\models\TreatmentProduct;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Veterinary treatment log — the Bestandsbuch source records (US-PM-04).
 *
 * Permission map:
 *   index / view → viewColonies
 *   create       → recordTreatment
 *
 * The Treatment model auto-calculates the Wartezeit expiry (AC-PM-04.3)
 * and refreshes the colony's annual compliance flags (AC-PM-02.6) on save.
 */
class TreatmentController extends Controller
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
                        'roles' => ['viewColonies'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create', 'product-data', 'colonies-for-stand'],
                        'roles' => ['recordTreatment'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(?int $colony_id = null): string
    {
        $query = Treatment::find()
            ->with(['colony', 'apiaryStand'])
            ->orderBy(['application_date' => SORT_DESC, 'id' => SORT_DESC]);

        // Optional filter: treatments for a single colony (used by the
        // release gate's "view treatments" navigation link).
        $colony = null;
        if ($colony_id !== null) {
            $query->andWhere(['colony_id' => $colony_id]);
            $colony = \common\models\Colony::findOne($colony_id);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'colony'       => $colony,
        ]);
    }

    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    public function actionCreate(): string|Response
    {
        $model = new Treatment([
            'treatment_type'          => Treatment::TYPE_VARROA,
            'application_date'        => date('Y-m-d'),
            'colonies_treated_at_stand' => 1,
            'treatment_duration_days' => 1,
            'operator_name'           => Yii::$app->user->identity?->username ?? '',
        ]);

        if ($model->load(Yii::$app->request->post())) {
            // colonies_treated_at_stand is not a form field — calculate it
            // automatically as the count of active colonies at the selected
            // stand on the application date (AC-PM-04.1).
            $model->colonies_treated_at_stand = $this->countActiveColoniesAtStand(
                (int) $model->apiary_stand_id,
            );

            if ($model->save()) {
                Yii::$app->session->setFlash(
                    'success',
                    "Treatment recorded. Wartezeit expires {$model->wartezeit_expiry}.",
                );
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', ['model' => $model]);
    }

    /**
     * Returns the typical statutory values for a treatment product as JSON,
     * used by the create form to autofill withdrawal/duration/quantity/supplier.
     */
    public function actionProductData(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $product = TreatmentProduct::findOne($id);
        if ($product === null) {
            Yii::$app->response->statusCode = 404;
            return Yii::$app->response;
        }

        Yii::$app->response->data = $product->toAutofillData();
        return Yii::$app->response;
    }

    /**
     * Returns the colonies currently assigned to a stand as JSON, for the
     * dependent colony dropdown on the treatment form (Change 1).
     * Each entry: id, colony_code, status, in_withdrawal.
     */
    public function actionColoniesForStand(int $standId): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $colonies = Colony::find()
            ->where(['apiary_stand_id' => $standId])
            ->orderBy(['colony_code' => SORT_ASC])
            ->all();

        $data = [];
        foreach ($colonies as $colony) {
            $data[] = [
                'id'            => $colony->id,
                'colony_code'   => $colony->colony_code,
                'status'        => $colony->status,
                'in_withdrawal' => !$colony->isWithdrawalCleared(),
            ];
        }

        Yii::$app->response->data = $data;
        return Yii::$app->response;
    }

    /**
     * Counts active colonies currently assigned to the given apiary stand.
     * Returns at least 1 so the treated colony is always represented.
     */
    private function countActiveColoniesAtStand(int $standId): int
    {
        if ($standId <= 0) {
            return 1;
        }
        $count = (int) Colony::find()
            ->where(['apiary_stand_id' => $standId, 'status' => 'active'])
            ->count();
        return max(1, $count);
    }

    private function findModel(int $id): Treatment
    {
        $model = Treatment::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('The requested treatment does not exist.');
        }
        return $model;
    }
}
