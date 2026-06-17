<?php

declare(strict_types=1);

namespace frontend\components;

use common\models\Customer;
use common\models\Product;
use Yii;

/**
 * Session-based shopping cart.
 *
 * Stored as a [productId => quantity] map in the session. Product details,
 * prices and line totals are resolved live from the database so the cart
 * always reflects current catalogue data (US-EC-03).
 */
final class Cart
{
    private const KEY = 'shop.cart';

    /** @return array<int,int> productId => quantity */
    public static function raw(): array
    {
        return (array) Yii::$app->session->get(self::KEY, []);
    }

    private static function store(array $cart): void
    {
        Yii::$app->session->set(self::KEY, $cart);
    }

    public static function add(int $productId, int $qty = 1): void
    {
        $cart = self::raw();
        $cart[$productId] = ($cart[$productId] ?? 0) + max(1, $qty);
        self::store($cart);
    }

    public static function setQuantity(int $productId, int $qty): void
    {
        $cart = self::raw();
        if ($qty <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $qty;
        }
        self::store($cart);
    }

    public static function remove(int $productId): void
    {
        $cart = self::raw();
        unset($cart[$productId]);
        self::store($cart);
    }

    public static function clear(): void
    {
        Yii::$app->session->remove(self::KEY);
    }

    /** Total number of items (sum of quantities) — used for the nav badge. */
    public static function count(): int
    {
        return array_sum(self::raw());
    }

    /**
     * The CRM customer record for the logged-in user, if any.
     * Used to apply wholesale pricing. Returns null for guests or users
     * without a customer record yet (treated as retail).
     */
    public static function currentCustomer(): ?Customer
    {
        if (Yii::$app->user->isGuest) {
            return null;
        }
        return Customer::findOne(['user_id' => Yii::$app->user->id]);
    }

    /**
     * Resolves the cart into line items against current published products.
     * Unpublished or deleted products are dropped.
     *
     * @return array<int, array{product: Product, qty: int, unitPrice: float, isWholesale: bool, lineTotal: float}>
     */
    public static function items(): array
    {
        $cart = self::raw();
        if (empty($cart)) {
            return [];
        }

        $products = Product::find()
            ->where(['id' => array_keys($cart), 'is_published' => 1])
            ->indexBy('id')
            ->all();

        $customer = self::currentCustomer();

        $items = [];
        foreach ($cart as $productId => $qty) {
            if (!isset($products[$productId])) {
                continue;
            }
            $product   = $products[$productId];
            $unitPrice = $product->effectivePrice($customer);
            $items[] = [
                'product'     => $product,
                'qty'         => (int) $qty,
                'unitPrice'   => $unitPrice,
                'isWholesale' => $product->isWholesalePriceFor($customer),
                'lineTotal'   => $unitPrice * (int) $qty,
            ];
        }
        return $items;
    }

    public static function total(): float
    {
        $total = 0.0;
        foreach (self::items() as $item) {
            $total += $item['lineTotal'];
        }
        return $total;
    }
}
