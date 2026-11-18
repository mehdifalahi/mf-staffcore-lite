<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Support\BulkDeleteResult;

final class BulkDeletePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function resultPayload(BulkDeleteResult $result): array
    {
        return $result->toArray();
    }

    public static function summaryMessage(
        BulkDeleteResult $result,
        string $singular,
        string $plural,
    ): string {
        $deleted = count($result->deleted);
        $skipped = count($result->skipped);
        $failed = count($result->failed);

        if ($failed === 0 && $skipped === 0) {
            $entityLabel = $deleted === 1 ? $singular : $plural;

            return sprintf(
                /* translators: 1: deleted count, 2: entity label (singular or plural) */
                _n('%1$d %2$s deleted successfully.', '%1$d %2$s deleted successfully.', $deleted, 'mf-staffcore-lite'),
                $deleted,
                $entityLabel,
            );
        }

        $parts = [];

        if ($deleted > 0) {
            $parts[] = sprintf(
                /* translators: %d: deleted count */
                _n('%d deleted', '%d deleted', $deleted, 'mf-staffcore-lite'),
                $deleted,
            );
        }

        if ($skipped > 0) {
            $parts[] = sprintf(
                /* translators: %d: skipped count */
                _n('%d skipped', '%d skipped', $skipped, 'mf-staffcore-lite'),
                $skipped,
            );
        }

        if ($failed > 0) {
            $parts[] = sprintf(
                /* translators: %d: failed count */
                _n('%d failed', '%d failed', $failed, 'mf-staffcore-lite'),
                $failed,
            );
        }

        return implode(', ', $parts) . '.';
    }
}
