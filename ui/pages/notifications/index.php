<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

(new \MF\StaffCore\Platforms\WordPress\Admin\Presenters\NotificationsPresenter())->render();
