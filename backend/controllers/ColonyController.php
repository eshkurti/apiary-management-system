<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Colony;
use common\models\ColonyMovement;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Colony register, movement and Stockkarte (US-PM-02, US-PM-07).
 *
 * Permission map:
 *   index / view / stockkarte  → viewColonies
 *   create / update / delete   → manageColonies
 *   move                       → moveColony
 */
class ColonyController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'stockkarte'],
                        'roles' => ['viewColonies'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['create', 'update', 'delete'],
                        'roles' => ['manageColonies'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['move'],
                        'roles' => ['moveColony'],
                    ],
                    [
                        'allow' => true,
                        'actions' => ['set-disease-flag', 'clear-disease-flag'],
                        'roles' => ['manageDiseaseFlag'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete'             => ['post'],
                    'set-disease-flag'   => ['post'],
                    'clear-disease-flag' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Colony::find()->with('apiaryStand')->orderBy(['colony_code' => SORT_ASC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * Read-only chronological Stockkarte for a colony (US-PM-07).
     */
    public function actionStockkarte(int $id): string
    {
        return $this->render('stockkarte', ['model' => $this->findModel($id)]);
    }

    public function actionCreate(): string|Response
    {
        $model = new Colony(['status' => 'active', 'queen_year' => (int) date('Y')]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Colony {$model->colony_code} registered.");
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Colony {$model->colony_code} updated.");
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', ['model' => $model]);
    }

    /**
     * Records a colony movement between stands (AC-PM-02.4).
     * The origin stand is taken from the colony's current assignment;
     * the ColonyMovement model updates the colony's stand on save.
     */
    public function actionMove(int $id): string|Response
    {
        $colony = $this->findModel($id);

        $movement = new ColonyMovement([
            'colony_id'     => $colony->id,
            'from_stand_id' => $colony->apiary_stand_id,
            'movement_date' => date('Y-m-d'),
        ]);

        if ($movement->load(Yii::$app->request->post()) && $movement->save()) {
            Yii::$app->session->setFlash('success', "Colony {$colony->colony_code} moved.");
            return $this->redirect(['view', 'id' => $colony->id]);
        }

        return $this->render('move', ['movement' => $movement, 'colony' => $colony]);
    }

    public function actionDelete(int $id): Response
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Colony deleted.');
        return $this->redirect(['index']);
    }

    /**
     * Sets a disease concern flag on a colony (US-CO-05, AC-CO-05.1, 05.2).
     * Blocks release of any batch sourcing this colony and, for batches already
     * released, forces them back to review_required (AC-CO-02.5).
     */
    public function actionSetDiseaseFlag(int $id): Response
    {
        $colony = $this->findModel($id);
        $note   = trim((string) Yii::$app->request->post('note', ''));

        if ($note === '') {
            Yii::$app->session->setFlash('error', 'Describe the disease concern before setting the flag.');
            return $this->redirect(['view', 'id' => $id]);
        }

        if ($colony->setDiseaseFlag($note)) {
            Yii::$app->session->setFlash('success', "Disease flag set on colony {$colony->colony_code}.");
        } else {
            Yii::$app->session->setFlash('error', 'Could not set the disease flag.');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Clears a disease concern flag (AC-CO-05.3, 05.4). The optional resolution
     * note is recorded. Released batches that were moved to review_required do
     * NOT auto-revert — the head beekeeper re-evaluates them manually.
     */
    public function actionClearDiseaseFlag(int $id): Response
    {
        $colony     = $this->findModel($id);
        $resolution = trim((string) Yii::$app->request->post('resolution', ''));

        if ($colony->clearDiseaseFlag($resolution)) {
            Yii::$app->session->setFlash('success', "Disease flag cleared on colony {$colony->colony_code}.");
        } else {
            Yii::$app->session->setFlash('error', 'Could not clear the disease flag.');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    private function findModel(int $id): Colony
    {
        $model = Colony::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('The requested colony does not exist.');
        }
        return $model;
    }
}
