<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress;

use MF\StaffCore\Navigation\AdminPageResolver;
use MF\StaffCore\Platforms\WordPress\Admin\AdminMenu;
use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;
use MF\StaffCore\Platforms\WordPress\Admin\AssetRegistrar;
use MF\StaffCore\Platforms\WordPress\Admin\PageRenderer;
use MF\StaffCore\Platforms\WordPress\Ajax\AjaxRegistrar;
use MF\StaffCore\Platforms\WordPress\Auth\WordPressPlatformUserGateway;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Platforms\WordPress\Frontend\FrontendPanel;

final class Plugin
{
    public static function boot(): void
    {
        \MF\StaffCore\Plan\PlanRegistry::setContext(
            new \MF\StaffCore\Platforms\WordPress\Plan\WordPressPlanContext(),
        );

        ServiceContainer::instance()->registerPlatformUserGateway(new WordPressPlatformUserGateway());

        \add_action('plugins_loaded', static function (): void {
            \load_plugin_textdomain(
                'mf-staffcore-lite',
                false,
                \dirname(\plugin_basename(MF_STAFFCORE_FILE)) . '/languages/'
            );
        }, 0);

        $resolver = new AdminPageResolver();
        $urls = new AdminUrlBuilder();
        $renderer = new PageRenderer($resolver, $urls);
        $frontendUrls = new AdminUrlBuilder(true);
        $frontendRenderer = new PageRenderer($resolver, $frontendUrls);

        (new AdminMenu($renderer))->register();
        (new AssetRegistrar())->register();
        (new AjaxRegistrar())->register();
        (new FrontendPanel($frontendRenderer, $frontendUrls))->register();
    }
}
