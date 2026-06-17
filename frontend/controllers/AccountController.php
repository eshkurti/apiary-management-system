<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Customer;
use common\models\Order;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Customer account area: profile overview and order history / tracking
 * (US-EC-04). Requires a logged-in account; a customer can only see their
 * own orders (AC-EC-04.4).
 */
class AccountController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'user'     => Yii::$app->user->identity,
            'customer' => $this->currentCustomer(),
        ]);
    }

    /**
     * Order history list (US-EC-04) — at /account/orders.
     */
    public function actionOrders(): string
    {
        $customer = $this->currentCustomer();
        $orders   = $customer === null
            ? []
            : Order::find()->where(['customer_id' => $customer->id])->with('items')->orderBy(['order_date' => SORT_DESC, 'id' => SORT_DESC])->all();

        return $this->render('orders', ['orders' => $orders]);
    }

    /**
     * Single order detail / tracking. Restricted to the owner (AC-EC-04.4).
     */
    public function actionOrder(int $id): string
    {
        $customer = $this->currentCustomer();
        $order    = Order::findOne($id);

        if ($order === null || $customer === null || $order->customer_id !== $customer->id) {
            throw new NotFoundHttpException('Order not found.');
        }

        return $this->render('order', ['order' => $order]);
    }

    private function currentCustomer(): ?Customer
    {
        $user = Yii::$app->user->identity;
        return Customer::findOne(['user_id' => $user->id])
            ?? Customer::findOne(['email' => $user->email]);
    }
}
