<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\Order;
use common\models\OrderStageLog;
use common\models\Product;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Order fulfilment management (US-EC-06).
 *
 * Presents a kanban board grouped by fulfilment stage and an order detail
 * view. Stage transitions and the staff-note timeline are recorded through
 * the Order / OrderStageLog models. Gated by the manageOrders permission.
 */
class OrderController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['manageOrders'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'advance'  => ['post'],
                    'add-note' => ['post'],
                    'cancel'   => ['post'],
                ],
            ],
        ];
    }

    /**
     * Kanban board: orders grouped by fulfilment stage (AC-EC-06.1).
     */
    public function actionIndex(): string
    {
        $stages  = [
            Order::STATUS_RECEIVED,
            Order::STATUS_PACKED,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
        ];
        $columns = [];
        foreach ($stages as $stage) {
            $columns[$stage] = Order::find()
                ->where(['status' => $stage])
                ->with('customer')
                ->orderBy(['order_date' => SORT_DESC, 'id' => SORT_DESC])
                ->all();
        }

        return $this->render('index', ['columns' => $columns]);
    }

    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * Advances the order to the next fulfilment stage (AC-EC-06.1, 06.2, 06.5).
     * Sequencing and the transition log are handled by Order::advanceStatus().
     */
    public function actionAdvance(int $id): Response
    {
        $model = $this->findModel($id);
        $note  = trim((string) Yii::$app->request->post('note', ''));

        if ($model->advanceStatus((int) Yii::$app->user->id, $note)) {
            Yii::$app->session->setFlash('success', "Order {$model->order_number} advanced to “" . Order::statusLabels()[$model->status] . "”.");
        } else {
            Yii::$app->session->setFlash('error', 'This order is already at the final stage and cannot be advanced further.');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Adds a staff note to the order at the current stage (AC-EC-06.3).
     * Stored as a same-stage entry in the order's timeline with author and
     * timestamp.
     */
    public function actionAddNote(int $id): Response
    {
        $model = $this->findModel($id);
        $note  = trim((string) Yii::$app->request->post('note', ''));

        if ($note === '') {
            Yii::$app->session->setFlash('error', 'The note cannot be empty.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $log = new OrderStageLog([
            'order_id'    => $model->id,
            'from_status' => $model->status,
            'to_status'   => $model->status,
            'notes'       => $note,
        ]);
        $log->save();

        Yii::$app->session->setFlash('success', 'Note added to the order.');
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Cancels an order and restores the stock it reserved (AC-EC-04.5).
     * Only orders still in the warehouse (received or packed) may be cancelled;
     * shipped or delivered orders cannot. Runs in a transaction so the status
     * change, stock restoration and audit-log entry are all-or-nothing.
     */
    public function actionCancel(int $id): Response
    {
        $model = $this->findModel($id);

        if (!in_array($model->status, [Order::STATUS_RECEIVED, Order::STATUS_PACKED], true)) {
            Yii::$app->session->setFlash('error', 'Only orders that are still received or packed can be cancelled.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $note = trim((string) Yii::$app->request->post('note', ''));
        $from = $model->status;

        $tx = Yii::$app->db->beginTransaction();
        try {
            $model->status = Order::STATUS_CANCELLED;
            $model->save(false);

            // Return each line's quantity to the product's stock.
            foreach ($model->items as $item) {
                $product = Product::findOne($item->product_id);
                if ($product !== null) {
                    $product->stock_quantity += (int) $item->quantity;
                    $product->save(false);
                }
            }

            $log = new OrderStageLog([
                'order_id'    => $model->id,
                'from_status' => $from,
                'to_status'   => Order::STATUS_CANCELLED,
                'notes'       => $note !== '' ? $note : 'Order cancelled; stock restored.',
            ]);
            $log->save(false);

            $tx->commit();
            Yii::$app->session->setFlash('success', "Order {$model->order_number} cancelled and stock restored.");
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    private function findModel(int $id): Order
    {
        $model = Order::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('The requested order does not exist.');
        }
        return $model;
    }
}
