<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\CompanyProfile;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

/**
 * Manages the single company-profile record (US-PM-00).
 * Restricted to the administrator via the manageCompanyProfile permission (AC-PM-00.2).
 */
class CompanyProfileController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['manageCompanyProfile'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['update' => ['get', 'post']],
            ],
        ];
    }

    public function actionIndex(): Response
    {
        return $this->redirect(['update']);
    }

    /**
     * Views and edits the company profile. The profile is a single row;
     * the model returns (or creates) instance id 1 (AC-PM-00.5).
     */
    public function actionUpdate(): string|Response
    {
        $model = CompanyProfile::getInstance();

        if ($model->load(Yii::$app->request->post())) {
            $model->updated_by = (int) Yii::$app->user->id;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Company profile saved.');
                return $this->refresh();
            }
        }

        return $this->render('update', ['model' => $model]);
    }
}
