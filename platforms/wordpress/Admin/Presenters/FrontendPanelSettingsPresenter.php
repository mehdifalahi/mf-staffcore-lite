<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Admin\Presenters;

use MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder;

final class FrontendPanelSettingsPresenter
{
    public function render(): void
    {
        $frontendPanelUrl = AdminUrlBuilder::defaultFrontendPanelUrl();

        include MF_STAFFCORE_PATH . 'modules/FrontendPanel/Views/page.php';
    }
}
