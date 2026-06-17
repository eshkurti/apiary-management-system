<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Customer;
use common\models\CustomerNote;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Customer / CRM management (US-EC-07).
 *
 * Supports wholesale designation with a minimum order quantity, a full
 * order-history view, and timestamped communication notes. Customers with
 * existing orders are deactivated rather than deleted (AC-EC-07.5).
 * Gated by the manageCustomers permission.
 */
class CustomerController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['manageCustomers'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete'   => ['post'],
                    'add-note' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Customer::find()->orderBy(['name' => SORT_ASC]),
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
        $model = new Customer(['country' => 'Germany', 'is_active' => 1, 'is_wholesale' => 0]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Customer “{$model->name}” saved.");
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Customer “{$model->name}” updated.");
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', ['model' => $model]);
    }

    /**
     * Records a communication note against the customer (AC-EC-07.3).
     */
    public function actionAddNote(int $id): Response
    {
        $customer = $this->findModel($id);
        $text     = trim((string) Yii::$app->request->post('note', ''));

        if ($text === '') {
            Yii::$app->session->setFlash('error', 'The note cannot be empty.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $note = new CustomerNote(['customer_id' => $customer->id, 'note' => $text]);
        $note->save();

        Yii::$app->session->setFlash('success', 'Communication note recorded.');
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Deactivates a customer that has orders, otherwise deletes it (AC-EC-07.5).
     */
    public function actionDelete(int $id): Response
    {
        $model = $this->findModel($id);

        if ($model->getOrderCount() > 0) {
            $model->is_active = 0;
            $model->save(false);
            Yii::$app->session->setFlash('warning', 'Customer has existing orders and was deactivated, preserving order history.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'Customer deleted.');
        return $this->redirect(['index']);
    }

    private function findModel(int $id): Customer
    {
        $model = Customer::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('The requested customer does not exist.');
        }
        return $model;
    }
}
