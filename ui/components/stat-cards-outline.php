<?php

declare(strict_types=1);

/**
 * Performance summary cards — Tabler "card with icon bg" style (outline + corner decor).
 *
 * @var list<array{
 *     label: string,
 *     value: int|string,
 *     icon: string,
 *     tone: string,
 *     hint?: string,
 *     hintTone?: string,
 *     url?: string
 * }> $statCards
 * @var string $statCardsRowClass Optional extra class on the row wrapper.
 * @var string $statCardsColumnClass Optional Bootstrap column class (default col-sm-6 col-lg-3).
 */

use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}

$statCards = $statCards ?? [];
$statCardsRowClass = trim((string) ($statCardsRowClass ?? ''));
$statCardsColumnClass = trim((string) ($statCardsColumnClass ?? 'col-sm-6 col-lg-3'));

if ($statCards === []) {
    return;
}
?>
<div class="staffcore-stat-cards row row-cards<?php echo $statCardsRowClass !== '' ? ' ' . esc_attr($statCardsRowClass) : ''; ?>">
    <?php foreach ($statCards as $card) :
        $tone = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($card['tone'] ?? 'primary'))) ?: 'primary';
        $hintTone = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($card['hintTone'] ?? 'secondary'))) ?: 'secondary';
        $label = (string) ($card['label'] ?? '');
        $value = $card['value'] ?? 0;
        $icon = (string) ($card['icon'] ?? 'circle');
        $hint = isset($card['hint']) ? (string) $card['hint'] : '';
        $url = isset($card['url']) && $card['url'] !== '' ? (string) $card['url'] : '';
        ?>
        <div class="<?php echo esc_attr($statCardsColumnClass); ?>">
            <?php if ($url !== '') : ?>
                <a href="<?php echo esc_url($url); ?>" class="staffcore-stat-card-link d-block h-100 text-reset text-decoration-none">
            <?php endif; ?>
            <div class="card card-sm staffcore-stat-card staffcore-stat-card--<?php echo esc_attr($tone); ?><?php echo $url !== '' ? ' staffcore-stat-card--interactive' : ''; ?>">
                <div class="staffcore-stat-card__iconbg" aria-hidden="true">
                    <?php TablerIcon::print($icon, 'staffcore-stat-card__icon'); ?>
                </div>
                <div class="card-body">
                    <div class="staffcore-stat-card__label text-secondary">
                        <?php echo esc_html($label); ?>
                    </div>
                    <div class="staffcore-stat-card__value h1 mb-0">
                        <?php echo esc_html((string) $value); ?>
                    </div>
                    <?php if ($hint !== '') : ?>
                        <div class="staffcore-stat-card__hint text-<?php echo esc_attr($hintTone); ?> small mt-1">
                            <?php echo esc_html($hint); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($url !== '') : ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
