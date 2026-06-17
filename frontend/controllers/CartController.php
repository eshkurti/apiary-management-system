<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Product;
use frontend\components\Cart;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

/**
 * Session-based shopping cart (US-EC-03).
 *
 * Viewing the cart is open to everyone; modifying it requires a logged-in
 * account, so a guest who tries to add a product is redirected to login
 * (AC-EC-01.5).
 */
class CartController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['?', '@'],
                    ],
                    [
                        'actions' => ['add', 'update', 'remove'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'add'    => ['post'],
                    'update' => ['post'],
                    'remove' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'items' => Cart::items(),
            'total' => Cart::total(),
        ]);
    }

    public function actionAdd(int $id): Response
    {
        $product = Product::find()->where(['id' => $id, 'is_published' => 1])->one();
        if ($product === null) {
            Yii::$app->session->setFlash('error', 'That product is no longer available.');
            return $this->redirect(['/shop/index']);
        }

        $qty = max(1, (int) Yii::$app->request->post('quantity', 1));
        if ($qty > $product->stock_quantity) {
            $qty = $product->stock_quantity;
        }

        Cart::add($product->id, $qty);
        Yii::$app->session->setFlash('success', "“{$product->name}” added to your cart.");
        return $this->redirect(['index']);
    }

    public function actionUpdate(int $id): Response
    {
        $qty = (int) Yii::$app->request->post('quantity', 1);

        // Cap the quantity at available stock.
        $product = Product::findOne($id);
        if ($product !== null && $qty > $product->stock_quantity) {
            $qty = $product->stock_quantity;
            Yii::$app->session->setFlash('warning', "Only {$product->stock_quantity} units of “{$product->name}” are in stock.");
        }

        Cart::setQuantity($id, $qty);
        return $this->redirect(['index']);
    }

    public function actionRemove(int $id): Response
    {
        Cart::remove($id);
        return $this->redirect(['index']);
    }
}
