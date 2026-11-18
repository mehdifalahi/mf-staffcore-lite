<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

use MF\StaffCore\Platforms\WordPress\Admin\Presenters\PositionsPresenter;

(new PositionsPresenter())->render();
