<?php

declare(strict_types=1);

namespace backend\components;

use yii\helpers\Html;

/**
 * Renders a consistent status badge for batch and order statuses.
 *
 * Colour system (defined in css/admin.css):
 *   received → blue, packed → amber, shipped → purple,
 *   delivered / released → green, pending_release → amber,
 *   review_required → red, cancelled / unknown → grey.
 */
final class StatusBadge
{
    private const LABELS = [
        'received'        => 'Received',
        'packed'          => 'Packed',
        'shipped'         => 'Shipped',
        'delivered'       => 'Delivered',
        'cancelled'       => 'Cancelled',
        'pending_release' => 'Pending Release',
        'released'        => 'Released',
        'review_required' => 'Review Required',
    ];

    /**
     * Returns the badge HTML for the given status string.
     */
    public static function html(string $status): string
    {
        $modifier = array_key_exists($status, self::LABELS) ? $status : 'default';
        $label    = self::LABELS[$status] ?? ucwords(str_replace('_', ' ', $status));

        return Html::tag('span', Html::encode($label), [
            'class' => 'lin-badge lin-badge--' . $modifier,
        ]);
    }
}
