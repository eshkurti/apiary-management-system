<?php

declare(strict_types=1);

namespace backend\components;

use common\models\Batch;

/**
 * Renders a batch's status as a clear, ordered badge hierarchy:
 *
 *   Pending Release (amber) → Released (green) → Fully Allocated (amber)
 *                                              → Sold Through (grey, primary)
 *
 * Review Required (red) is a side state that can occur after release.
 *
 * When a batch has sold through, "Sold Through" is the primary badge and
 * "Released" is shown only as secondary context — never as equal-weight badges.
 */
final class BatchStatusBadge
{
    public static function html(Batch $batch): string
    {
        // Side state: a post-release disease flag forced a review.
        if ($batch->status === Batch::STATUS_REVIEW_REQUIRED) {
            return StatusBadge::html($batch->status);
        }

        if ($batch->status === Batch::STATUS_RELEASED) {
            // Sold through takes precedence and becomes the primary badge.
            if ($batch->isSoldThrough()) {
                return '<span class="badge bg-secondary">Sold Through</span>'
                    . ' <span class="text-muted small ms-1">(Released)</span>';
            }

            $html = StatusBadge::html($batch->status); // green "Released"

            if ($batch->isFullyAllocated()) {
                $html .= ' <span class="badge bg-warning text-dark ms-1">Fully Allocated</span>';
            }
            if ($batch->remainingUnits() < 0) {
                $html .= ' <span class="badge bg-danger ms-1">Over-allocated</span>';
            }
            return $html;
        }

        // Pending release (amber) — or any unexpected status, rendered safely.
        return StatusBadge::html($batch->status);
    }
}
