<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Frontend;

use MF\StaffCore\Navigation\AdminRoute;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\AssetRegistrar;
use MF\StaffCore\Platforms\WordPress\Admin\PageRenderer;

final class FrontendPanel
{
    private const TOPBAR_NODE_ID = 'mf-staffcore-frontend-panel';

    public function __construct(
        private readonly PageRenderer $renderer,
        private readonly AdminUrlBuilder $frontendUrls,
    ) {
    }

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
        add_action('template_redirect', [$this, 'maybeRender']);
        add_action('admin_bar_menu', [$this, 'registerTopbarNode'], 90);
    }

    public function enqueue(): void
    {
        if (! $this->isFrontendPanelRequest() || ! is_user_logged_in()) {
            return;
        }

        (new AssetRegistrar())->enqueue('mf-staffcore-frontend');
    }

    public function maybeRender(): void
    {
        if (! $this->isFrontendPanelRequest()) {
            return;
        }

        if (! is_user_logged_in()) {
            wp_safe_redirect(wp_login_url($this->dashboardUrl()));
            exit;
        }

        $preflightRedirect = $this->renderer->resolvePreflightRedirect();

        if ($preflightRedirect !== null) {
            wp_safe_redirect($preflightRedirect);
            exit;
        }

        nocache_headers();
        status_header(200);

        echo '<!doctype html>';
        echo '<html lang="' . esc_attr(get_locale()) . '">';
        echo '<head>';
        echo '<meta charset="' . esc_attr(get_bloginfo('charset')) . '">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>' . esc_html__('MF StaffCore', 'mf-staffcore-lite') . '</title>';
        wp_head();
        echo '</head>';
        echo '<body class="mf-staffcore-admin mf-staffcore-frontend">';
        $this->renderer->render();
        wp_footer();
        echo '</body>';
        echo '</html>';
        exit;
    }

    public function registerTopbarNode(\WP_Admin_Bar $adminBar): void
    {
        if (! is_user_logged_in() || ! is_admin_bar_showing()) {
            return;
        }

        $adminBar->add_node([
            'id' => self::TOPBAR_NODE_ID,
            'title' => esc_html__('StaffCore Panel', 'mf-staffcore-lite'),
            'href' => $this->dashboardUrl(),
            'meta' => [
                'class' => self::TOPBAR_NODE_ID,
            ],
        ]);
    }

    private function dashboardUrl(): string
    {
        return $this->frontendUrls->build(new AdminRoute('dashboard', null));
    }

    /**
     * Bookmarkable frontend entry flag (mf_staffcore_front=1).
     *
     * Not a form submission: no CSRF nonce. After routing, PageRenderer still
     * requires WordPress login + StaffCore authorization.
     *
     * phpcs:disable WordPress.Security.NonceVerification.Recommended
     */
    private function isFrontendPanelRequest(): bool
    {
        $flag = isset($_GET[AdminUrlBuilder::FRONTEND_QUERY])
            ? sanitize_text_field(wp_unslash((string) $_GET[AdminUrlBuilder::FRONTEND_QUERY]))
            : '';

        return $flag !== '' && $flag !== '0';
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended
}
