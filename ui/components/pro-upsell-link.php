<?php

declare(strict_types=1);

/**
 * Canonical outbound Pro purchase link (Lite builds only — pass URL from PlanRegistry).
 *
 * @var string $upgradeUrl
 * @var string|null $label
 * @var string|null $linkClass
 * @var bool|null $asButton
 */

if (! defined('ABSPATH')) {
    exit;
}

$label = $label ?? __('Upgrade to Pro', 'mf-staffcore-lite');
$linkClass = $linkClass ?? '';
$asButton = (bool) ($asButton ?? false);

if ($asButton) :
    ?>
<a
    href="<?php echo esc_url($upgradeUrl); ?>"
    class="btn btn-primary shadow-sm <?php echo esc_attr(trim($linkClass)); ?>"
    target="_blank"
    rel="noopener noreferrer"
><?php echo esc_html($label); ?></a>
    <?php
else :
    ?>
<a
    href="<?php echo esc_url($upgradeUrl); ?>"
    class="<?php echo esc_attr(trim('staffcore-pro-upsell-link ' . $linkClass)); ?>"
    target="_blank"
    rel="noopener noreferrer"
><?php echo esc_html($label); ?></a>
    <?php
endif;
