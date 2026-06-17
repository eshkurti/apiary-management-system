<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Customer;
use common\models\Product;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Public product catalogue (US-EC-01). Open to guests.
 */
class ShopController extends Controller
{
    /**
     * Catalogue of published products with stock available (AC-EC-01.1).
     */
    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Product::find()
                ->with('batch')
                ->where(['is_published' => 1])
                ->andWhere(['>', 'stock_quantity', 0])
                ->orderBy(['name' => SORT_ASC]),
            'pagination' => ['pageSize' => 12],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    /**
     * Product detail with traceability panel (AC-EC-01.3, 01.4). Open to guests.
     */
    public function actionProduct(int $id): string
    {
        $product = Product::find()->where(['id' => $id, 'is_published' => 1])->one();
        if ($product === null) {
            throw new NotFoundHttpException('This product is not available.');
        }

        // Wholesale customers see their wholesale price when the product has one.
        $customer = Yii::$app->user->isGuest
            ? null
            : Customer::findOne(['user_id' => Yii::$app->user->id]);

        return $this->render('product', [
            'product'         => $product,
            'displayPrice'    => $product->effectivePrice($customer),
            'isWholesalePrice' => $product->isWholesalePriceFor($customer),
        ]);
    }
}
