<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Batch;
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
            'pagination' => ['pageSize' => 10],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * Creates a product — always from a released batch with units still
     * available. There is a single form and a single experience:
     *
     *  - With a batch_id (from the batch view, or chosen in the Products → New
     *    selector): the batch is locked, the name is generated, stock defaults
     *    to the remaining units, publishing is on, and only price / units /
     *    wholesale / publish are editable.
     *  - Without a batch_id (Products → New): a batch selector listing only
     *    eligible batches; choosing one loads that exact same form via AJAX.
     *
     * A product can never be created without an eligible batch — there is no
     * free-form batch field and the eligibility is enforced server-side too.
     */
    public function actionCreate(): string|Response
    {
        $batchId   = Yii::$app->request->get('batch_id');
        $fromBatch = $batchId !== null && $batchId !== '';
        $isAjax    = Yii::$app->request->isAjax;

        // Products → New with no batch chosen yet: show the batch selector.
        if (!$fromBatch) {
            return $this->render('create', ['batches' => $this->eligibleBatchesForProduct()]);
        }

        $batch = Batch::findOne((int) $batchId);

        // Resolve any eligibility problem with a clear message.
        $invalid = null;
        if ($batch === null || !$batch->isReleased()) {
            $invalid = 'A product can only be created from a released batch.';
        } elseif ($batch->isSoldThrough()) {
            $invalid = 'This batch has sold through; no new products can be created from it.';
        } elseif ($batch->remainingUnits() <= 0) {
            $invalid = 'This batch has no units remaining to allocate to a new product.';
        }
        if ($invalid !== null) {
            if ($isAjax) {
                return $this->renderAjax('_create-batch-unavailable', ['message' => $invalid]);
            }
            Yii::$app->session->setFlash('error', $invalid);
            return $this->redirect($batch === null ? ['/batch/index'] : ['/batch/view', 'id' => $batch->id]);
        }

        // Pre-filled, locked, publish-on-by-default product for this batch.
        $model = new Product(['is_published' => 1]);
        $model->batch_id       = $batch->id;
        $model->name           = $this->generatedProductName($batch);
        $model->stock_quantity = max(0, $batch->remainingUnits());

        if ($model->load(Yii::$app->request->post())) {
            // Lock the batch link and generated name server-side against tampering.
            $model->batch_id = $batch->id;
            $model->name     = $this->generatedProductName($batch);
            if ($model->save()) {
                Yii::$app->session->setFlash('success', "Product “{$model->name}” saved.");
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        // The selector loads the form fragment over AJAX; a direct visit (from
        // the batch view) renders the same fragment inside a full page.
        return $isAjax
            ? $this->renderAjax('_batch-product-form', ['model' => $model, 'batch' => $batch])
            : $this->render('create-from-batch', ['model' => $model, 'batch' => $batch]);
    }

    /**
     * Released batches that still have units available to allocate to a new
     * product — i.e. not fully allocated and not sold through.
     *
     * @return Batch[]
     */
    private function eligibleBatchesForProduct(): array
    {
        $batches = Batch::find()
            ->where(['status' => Batch::STATUS_RELEASED])
            ->orderBy(['lot_number' => SORT_ASC])
            ->all();

        return array_values(array_filter(
            $batches,
            static fn (Batch $b): bool => $b->isAvailableForNewProduct(),
        ));
    }

    /**
     * The auto-generated product name for a batch: "HoneyVariety — LotNumber".
     */
    private function generatedProductName(Batch $batch): string
    {
        return trim(($batch->honey_variety ?? '') . ' — ' . $batch->lot_number, " —");
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
