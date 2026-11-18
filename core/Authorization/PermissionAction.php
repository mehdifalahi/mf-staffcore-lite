<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

/**
 * CRUD permission actions per module ({@see PermissionModuleRegistry}).
 */
final class PermissionAction
{
    public const VIEW = 'view';

    public const CREATE = 'create';

    public const EDIT = 'edit';

    public const DELETE = 'delete';

    /** @var list<string> */
    public const ALL = [
        self::VIEW,
        self::CREATE,
        self::EDIT,
        self::DELETE,
    ];

    public static function key(string $module, string $action): string
    {
        return $module . '.' . $action;
    }

    public static function isValid(string $action): bool
    {
        return in_array($action, self::ALL, true);
    }
}
