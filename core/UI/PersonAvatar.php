<?php

declare(strict_types=1);

namespace MF\StaffCore\UI;

/**
 * Renders person avatars: employee photo when available, otherwise initials.
 */
final class PersonAvatar
{
    public static function initialsFromName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $letters = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $letters .= strtoupper($part[0]);

            if (strlen($letters) >= 2) {
                break;
            }
        }

        return $letters !== '' ? $letters : '?';
    }

    public static function spanHtml(
        string $name,
        ?string $photoUrl,
        string $class = 'avatar avatar-sm rounded',
        ?string $ariaLabel = null,
    ): string {
        $label = $ariaLabel ?? ($name !== '' ? $name : 'User');

        if ($photoUrl !== null && $photoUrl !== '') {
            return sprintf(
                '<span class="%s" style="background-image: url(%s)" role="img" aria-label="%s"></span>',
                esc_attr($class),
                esc_url($photoUrl),
                esc_attr($label),
            );
        }

        return sprintf(
            '<span class="%s">%s</span>',
            esc_attr($class),
            esc_html(self::initialsFromName($name)),
        );
    }

    public static function cellHtml(
        string $name,
        ?string $photoUrl,
        ?string $secondaryLine = null,
        string $avatarClass = 'avatar avatar-sm rounded',
    ): string {
        $displayName = $name !== '' ? $name : '—';
        $html = '<div class="d-flex align-items-center gap-2">';
        $html .= self::spanHtml($displayName, $photoUrl, $avatarClass, $displayName);
        $html .= '<div><div class="fw-medium">' . esc_html($displayName) . '</div>';

        if ($secondaryLine !== null && $secondaryLine !== '') {
            $html .= '<div class="text-secondary small">' . esc_html($secondaryLine) . '</div>';
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function photoFromRow(array $row, string $key = 'employee_photo'): ?string
    {
        if (! isset($row[$key])) {
            return null;
        }

        $value = trim((string) $row[$key]);

        return $value !== '' ? $value : null;
    }
}
