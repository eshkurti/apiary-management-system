<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Product;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Product management (US-EC-05).
 *
 * A product is derived from a released batch and inherits its provenance
 * fields (lot number, honey variety, harvest date, water content, origin
 * statement) live from the Batch record — these are never stored or edited
 * on the product. Only released batches may be published (enforced on the
 * Product model). Gated by the manageProducts permission.
 */
class ProductController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['manageProducts'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete'         => ['post'],
                    'toggle-publish' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Product::find()->with('batch')->orderBy(['created_at' => SORT_DESC]),
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
        $model = new Product(['stock_quantity' => 0, 'is_published' => 0]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Product “{$model->name}” saved.");
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Product “{$model->name}” updated.");
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', ['model' => $model]);
    }

    /**
     * Publishes or unpublishes a product (AC-EC-05.4, AC-EC-05.5).
     * Unpublishing only removes it from the catalogue; the record is kept.
     */
    public function actionTogglePublish(int $id): Response
    {
        $model = $this->findModel($id);
        $model->is_published = $model->is_published ? 0 : 1;

        if ($model->save()) {
            $state = $model->is_published ? 'published to the shop' : 'removed from the shop';
            Yii::$app->session->setFlash('success', "Product “{$model->name}” {$state}.");
        } else {
            Yii::$app->session->setFlash(
                'error',
                $model->getFirstError('batch_id') ?: 'Could not change the publish state.',
            );
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionDelete(int $id): Response
    {
        $model = $this->findModel($id);

        if ($model->getOrderItems()->exists()) {
            Yii::$app->session->setFlash('error', 'This product appears on existing orders and cannot be deleted. Unpublish it instead.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'Product deleted.');
        return $this->redirect(['index']);
    }

    private function findModel(int $id): Product
    {
        $model = Product::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('The requested product does not exist.');
        }
        return $model;
    }
}
