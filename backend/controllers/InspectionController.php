<?php

declare(strict_types=1);

namespace backend\controllers;

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
                        'actions' => ['create'],
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
            'pagination' => ['pageSize' => 20],
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

    private function findModel(int $id): Inspection
    {
        $model = Inspection::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('The requested inspection does not exist.');
        }
        return $model;
    }
}
