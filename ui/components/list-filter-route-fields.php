<?php

declare(strict_types=1);

/**
 * Adapter-aware hidden fields for StaffCore list filter GET forms.
 *
 * @var \MF\StaffCore\Platforms\WordPress\Admin\AdminUrlBuilder $urlBuilder
 * @var \MF\StaffCore\Navigation\AdminRoute $filterRoute
 */

if (! defined('ABSPATH')) {
    exit;
}

foreach ($urlBuilder->routeQueryArgs($filterRoute) as $name => $value) {
    printf(
        '<input type="hidden" name="%s" value="%s">',
        esc_attr($name),
        esc_attr((string) $value),
    );
}
