<?php

if (! defined('ABSPATH')) {
    exit;
}

use MF\StaffCore\Platforms\WordPress\Admin\Presenters\DashboardPresenter;

(new DashboardPresenter())->render();
