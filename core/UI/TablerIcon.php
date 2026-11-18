<?php

declare(strict_types=1);

namespace MF\StaffCore\UI;

/**
 * Renders Tabler Icons (https://tabler.io/icons) via icons-webfont.
 *
 * Icon names match AdminMenuRegistry "icon" keys (e.g. home, users, file-text).
 */
final class TablerIcon
{
    /** @var array<string, array<string, bool>> */
    private const ALLOWED_HTML = [
        'i' => [
            'class' => true,
            'aria-hidden' => true,
        ],
        'span' => [
            'class' => true,
        ],
    ];

    public static function navIcon(string $name): string
    {
        return self::kses(self::buildNavIcon($name));
    }

    public static function icon(string $name, string $extraClass = ''): string
    {
        return self::kses(self::buildIcon($name, $extraClass));
    }

    /**
     * Echo a kses-escaped icon (use in views instead of echo icon()).
     */
    public static function print(string $name, string $extraClass = ''): void
    {
        echo wp_kses(self::buildIcon($name, $extraClass), self::ALLOWED_HTML);
    }

    /**
     * Echo a kses-escaped nav icon (use in views instead of echo navIcon()).
     */
    public static function printNav(string $name): void
    {
        echo wp_kses(self::buildNavIcon($name), self::ALLOWED_HTML);
    }

    private static function buildNavIcon(string $name): string
    {
        $safe = self::sanitizeName($name);

        return '<span class="nav-link-icon"><i class="ti ti-' . $safe . '" aria-hidden="true"></i></span>';
    }

    private static function buildIcon(string $name, string $extraClass = ''): string
    {
        $safe = self::sanitizeName($name);
        $class = 'ti ti-' . $safe;

        if ($extraClass !== '') {
            $class .= ' ' . preg_replace('/[^a-zA-Z0-9 _-]/', '', $extraClass);
        }

        return '<i class="' . $class . '" aria-hidden="true"></i>';
    }

    private static function sanitizeName(string $name): string
    {
        $normalized = strtolower(trim($name));
        $safe = preg_replace('/[^a-z0-9-]/', '', $normalized) ?? '';

        return $safe !== '' ? $safe : 'circle';
    }

    private static function kses(string $html): string
    {
        if (function_exists('wp_kses')) {
            return wp_kses($html, self::ALLOWED_HTML);
        }

        return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
