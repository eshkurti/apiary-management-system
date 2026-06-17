<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\ApiaryStand;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Apiary Stand register (US-PM-01).
 * Gated by the manageApiaryStands permission (administrator).
 */
class ApiaryStandController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['manageApiaryStands'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['delete' => ['post']],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => ApiaryStand::find()->orderBy(['stand_code' => SORT_ASC]),
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
        $model = new ApiaryStand(['is_active' => 1, 'landkreis' => 'Hof']);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Apiary stand {$model->stand_code} registered.");
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Apiary stand {$model->stand_code} updated.");
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', ['model' => $model]);
    }

    public function actionDelete(int $id): Response
    {
        $model = $this->findModel($id);
        if ($model->getActiveColonyCount() > 0) {
            Yii::$app->session->setFlash('error', 'Cannot delete a stand with active colonies assigned.');
            return $this->redirect(['view', 'id' => $id]);
        }
        $model->delete();
        Yii::$app->session->setFlash('success', 'Apiary stand deleted.');
        return $this->redirect(['index']);
    }

    private function findModel(int $id): ApiaryStand
    {
        $model = ApiaryStand::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('The requested apiary stand does not exist.');
        }
        return $model;
    }
}
