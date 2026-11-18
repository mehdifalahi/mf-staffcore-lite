<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Navigation;

use MF\StaffCore\Navigation\PanelActionUrl;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;

/**
 * Resolves stored notification {@see PanelActionUrl} values to adapter-aware URLs.
 */
final class NotificationActionUrlResolver
{
    public function resolve(?string $storedUrl, ?bool $frontendPanel = null): ?string
    {
        if ($storedUrl === null || trim($storedUrl) === '') {
            return null;
        }

        $parsed = PanelActionUrl::parse($storedUrl);

        if ($parsed === null) {
            return admin_url($storedUrl);
        }

        $builder = $frontendPanel === null
            ? new AdminUrlBuilder()
            : new AdminUrlBuilder($frontendPanel);

        return $builder->build($parsed['route'], $parsed['extra']);
    }

    public function resolveForCurrentRequest(?string $storedUrl): ?string
    {
        return $this->resolve($storedUrl, null);
    }

    public static function isFrontendPanelAjaxRequest(): bool
    {
        $ctx = isset($_POST['panel_context'])
            ? sanitize_key((string) wp_unslash($_POST['panel_context']))
            : '';

        return $ctx === 'frontend';
    }
}
