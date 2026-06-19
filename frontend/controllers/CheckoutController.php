<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Customer;
use common\models\Order;
use common\models\OrderItem;
use common\models\OrderStageLog;
use common\models\Product;
use frontend\components\Cart;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

/**
 * Checkout and order placement (US-EC-03). Requires a logged-in (verified)
 * customer account — guests are redirected to login by AccessControl.
 */
class CheckoutController extends Controller
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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['index' => ['get', 'post']],
            ],
        ];
    }

    public function actionIndex(): string|Response
    {
        $items = Cart::items();
        if (empty($items)) {
            Yii::$app->session->setFlash('warning', 'Your cart is empty.');
            return $this->redirect(['/cart/index']);
        }

        $user     = Yii::$app->user->identity;
        $customer = $this->findOrCreateCustomer($user);
        // Checkout requires a complete delivery address — street, postcode, city
        // (AC-EC-03.2). The scenario also drives the required-field markers in the form.
        $customer->scenario = Customer::SCENARIO_CHECKOUT;
        $total    = Cart::total();
        $totalQty = Cart::count();

        if (Yii::$app->request->isPost) {
            // The delivery address is captured on the customer record (AC-EC-03.2).
            $customer->load(Yii::$app->request->post());

            if (!$customer->save()) {
                Yii::$app->session->setFlash('error', 'Please complete the delivery address (street, postcode and city) before placing the order.');
                return $this->render('index', compact('items', 'total', 'customer'));
            }

            // Wholesale minimum order quantity enforcement (AC-EC-07.4).
            if ($customer->is_wholesale && $customer->min_order_quantity && $totalQty < $customer->min_order_quantity) {
                Yii::$app->session->setFlash('error', "As a wholesale account you must order at least {$customer->min_order_quantity} units. Your cart has {$totalQty}.");
                return $this->render('index', compact('items', 'total', 'customer'));
            }

            $order = $this->placeOrder($customer, $items);
            if ($order === null) {
                return $this->render('index', compact('items', 'total', 'customer'));
            }

            Cart::clear();
            Yii::$app->session->setFlash('success', "Thank you! Your order {$order->order_number} has been placed.");
            return $this->redirect(['/account/order', 'id' => $order->id]);
        }

        return $this->render('index', compact('items', 'total', 'customer'));
    }

    /**
     * Creates the order, line items and stage log inside a transaction,
     * re-checking stock at submission time and decrementing it (AC-EC-03.3, 03.4).
     *
     * @param array<int, array{product: Product, qty: int, lineTotal: float}> $items
     */
    private function placeOrder(Customer $customer, array $items): ?Order
    {
        $session = Yii::$app->session;
        $tx      = Yii::$app->db->beginTransaction();
        try {
            $total = 0.0;
            foreach ($items as $item) {
                /** @var Product $product */
                $product = Product::findOne($item['product']->id); // fresh row for the stock check
                if ($product === null || !$product->is_published) {
                    $tx->rollBack();
                    $session->setFlash('error', "“{$item['product']->name}” is no longer available.");
                    return null;
                }
                if ($item['qty'] > $product->stock_quantity) {
                    $tx->rollBack();
                    $session->setFlash('error', "Only {$product->stock_quantity} units of “{$product->name}” remain in stock.");
                    return null;
                }
                // Wholesale customers are charged the wholesale price when set.
                $total += $product->effectivePrice($customer) * $item['qty'];
            }

            $order = new Order([
                'customer_id'      => $customer->id,
                'order_number'     => Order::generateOrderNumber(),
                'order_date'       => date('Y-m-d'),
                'total_amount'     => $total,
                'status'           => Order::STATUS_RECEIVED,
                'shipping_address' => $this->formatAddress($customer),
            ]);
            if (!$order->save()) {
                $tx->rollBack();
                $session->setFlash('error', 'The order could not be created. Please try again.');
                return null;
            }

            foreach ($items as $item) {
                $product   = Product::findOne($item['product']->id);
                $unitPrice = $product->effectivePrice($customer);
                $lineTotal = $unitPrice * $item['qty'];

                $line = new OrderItem([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'batch_id'     => $product->batch_id,
                    'lot_number'   => $product->batch->lot_number ?? '',
                    'product_name' => $product->name,
                    'quantity'     => $item['qty'],
                    'unit_price'   => $unitPrice,
                    'line_total'   => $lineTotal,
                ]);
                $line->save(false);

                $product->stock_quantity -= $item['qty'];
                $product->save(false);
            }

            $log = new OrderStageLog([
                'order_id'    => $order->id,
                'from_status' => null,
                'to_status'   => Order::STATUS_RECEIVED,
                'notes'       => 'Order placed by customer.',
            ]);
            $log->save(false);

            $tx->commit();
            return $order;
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    private function findOrCreateCustomer(\common\models\User $user): Customer
    {
        $customer = Customer::findOne(['user_id' => $user->id]);
        if ($customer !== null) {
            return $customer;
        }

        // Link an existing customer that shares this email, otherwise create one.
        $customer = Customer::findOne(['email' => $user->email]);
        if ($customer !== null) {
            $customer->user_id = $user->id;
            return $customer;
        }

        return new Customer([
            'user_id'      => $user->id,
            'name'         => $user->username,
            'email'        => $user->email,
            'country'      => 'Germany',
            'is_active'    => 1,
            'is_wholesale' => 0,
        ]);
    }

    private function formatAddress(Customer $c): string
    {
        return implode(', ', array_filter([
            $c->name,
            $c->address,
            trim($c->postcode . ' ' . $c->city),
            $c->country,
        ]));
    }
}
