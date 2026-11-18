<?php

declare(strict_types=1);

namespace MF\StaffCore\Authorization;

/**
 * Per-user manager data scope mode stored on staff_user.manager_scope_mode.
 *
 * @see .cursor/rules/mf-staffcore-manager-scope-setting.mdc
 */
final class ManagerScopeModeRegistry
{
    public const AUTO = 'auto';

    public const TEAM = 'team';

    public const ALL = 'all';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [self::AUTO, self::TEAM, self::ALL];
    }

    public static function default(): string
    {
        return self::AUTO;
    }

    public static function isValid(string $mode): bool
    {
        return in_array($mode, self::values(), true);
    }

    /**
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function options(): array
    {
        return [
            [
                'value' => self::AUTO,
                'label' => 'Automatic (inactive)',
                'description' => 'Uses department manager link: team scope when they manage a department, otherwise company-wide.',
            ],
            [
                'value' => self::TEAM,
                'label' => 'Team only',
                'description' => 'Always limit operational lists to their managed team; lists stay empty until a department manager link exists.',
            ],
            [
                'value' => self::ALL,
                'label' => 'Company-wide',
                'description' => 'Full company HR data like central HR staff; My Team roster still follows department assignments only.',
            ],
        ];
    }
}
