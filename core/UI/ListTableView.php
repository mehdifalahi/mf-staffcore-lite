<?php

declare(strict_types=1);

namespace MF\StaffCore\UI;

/**
 * Helpers for module list table markup (column counts, actions visibility).
 */
final class ListTableView
{
    public static function showActionsColumn(bool $canEdit, bool $canDelete, bool $showRowViewActions = false): bool
    {
        return $canEdit || $canDelete || $showRowViewActions;
    }

    public static function colspan(int $dataColumns, bool $canDelete, bool $showActionsColumn): int
    {
        return $dataColumns + ($canDelete ? 1 : 0) + ($showActionsColumn ? 1 : 0);
    }
}
