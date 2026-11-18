<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Http;

final class SafeRedirect
{
    public static function to(string $url, int $status = 302): void
    {
        if (! headers_sent()) {
            wp_safe_redirect($url, $status);
            exit;
        }

        $escapedUrl = esc_url($url);

        echo '<!doctype html><html><head>';
        echo '<meta http-equiv="refresh" content="0;url=' . esc_attr($escapedUrl) . '">';
        echo '</head><body><a href="' . esc_attr($escapedUrl) . '">' . esc_html($escapedUrl) . '</a></body></html>';
        exit;
    }
}
