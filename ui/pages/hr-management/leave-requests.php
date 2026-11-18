<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

use MF\StaffCore\Platforms\WordPress\Admin\Presenters\LeaveRequestsPresenter;

(new LeaveRequestsPresenter())->render();
