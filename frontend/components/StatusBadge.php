<?php

declare(strict_types=1);

namespace frontend\components;

use common\models\Order;
use yii\helpers\Html;

/**
 * Renders an order fulfilment status badge using the shop colour system
 * (defined in css/shop.css).
 */
final class StatusBadge
{
    public static function html(string $status): string
    {
        $labels = Order::statusLabels();
        $label  = $labels[$status] ?? ucfirst($status);

        return Html::tag('span', Html::encode($label), [
            'class' => 'status-badge status-' . $status,
        ]);
    }
}
