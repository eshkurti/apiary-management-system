<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Colony;
use common\models\Inspection;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Colony inspection log (US-PM-03).
 *
 * Permission map:
 *   index / view → viewColonies
 *   create       → logInspection
 */
class InspectionController extends Controller
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
                        'actions' => ['create', 'colonies-for-stand'],
                        'roles' => ['logInspection'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Inspection::find()->with(['colony', 'apiaryStand'])->orderBy(['inspection_date' => SORT_DESC, 'id' => SORT_DESC]),
            'pagination' => ['pageSize' => 10],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    public function actionCreate(): string|Response
    {
        $model = new Inspection([
            'inspection_date' => date('Y-m-d'),
            'queen_sighted'   => 1,
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Inspection logged.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', ['model' => $model]);
    }

    /**
     * Returns the colonies currently assigned to a stand as JSON, for the
     * dependent colony dropdown on the inspection form (Fix 2).
     * Each entry: id, colony_code, status.
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
                'id'          => $colony->id,
                'colony_code' => $colony->colony_code,
                'status'      => $colony->status,
            ];
        }

        Yii::$app->response->data = $data;
        return Yii::$app->response;
    }

    private function findModel(int $id): Inspection
    {
        $model = Inspection::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('The requested inspection does not exist.');
        }
        return $model;
    }
}
