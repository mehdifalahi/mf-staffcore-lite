<?php

declare(strict_types=1);

use MF\StaffCore\Support\PaginationWindow;

/**
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalItems
 * @var int $perPage
 * @var callable(int): string $pageUrl
 */

if (! defined('ABSPATH')) {
    exit;
}

if ($totalItems <= 0) {
    return;
}

$from = (($currentPage - 1) * $perPage) + 1;
$to = min($currentPage * $perPage, $totalItems);
$firstPage = 1;
$prevPage = max(1, $currentPage - 1);
$nextPage = min($totalPages, $currentPage + 1);
$lastPage = $totalPages;
$pageItems = PaginationWindow::pageItems($currentPage, $totalPages);
?>
<div class="card-footer d-flex flex-wrap align-items-center gap-2">
    <p class="m-0 text-secondary">
        <?php
        echo esc_html(
            sprintf(
                /* translators: 1: first row, 2: last row, 3: total rows */
                __('Showing %1$d–%2$d of %3$d', 'mf-staffcore-lite'),
                $from,
                $to,
                $totalItems
            )
        );
        ?>
    </p>
    <?php if ($totalPages > 1) : ?>
        <ul class="pagination m-0 ms-auto">
            <li class="page-item<?php echo $currentPage <= 1 ? ' disabled' : ''; ?>">
                <a class="page-link" href="<?php echo esc_url($pageUrl($firstPage)); ?>" aria-label="<?php esc_attr_e('First page', 'mf-staffcore-lite'); ?>">
                    <i class="ti ti-chevrons-left" aria-hidden="true"></i>
                </a>
            </li>
            <li class="page-item<?php echo $currentPage <= 1 ? ' disabled' : ''; ?>">
                <a class="page-link" href="<?php echo esc_url($pageUrl($prevPage)); ?>" tabindex="-1" aria-label="<?php esc_attr_e('Previous', 'mf-staffcore-lite'); ?>">
                    <i class="ti ti-chevron-left" aria-hidden="true"></i>
                </a>
            </li>
            <?php foreach ($pageItems as $pageItem) : ?>
                <?php if ($pageItem === 'ellipsis') : ?>
                    <li class="page-item disabled">
                        <span class="page-link" aria-hidden="true">&hellip;</span>
                    </li>
                <?php else : ?>
                    <li class="page-item<?php echo $pageItem === $currentPage ? ' active' : ''; ?>">
                        <a class="page-link" href="<?php echo esc_url($pageUrl($pageItem)); ?>"><?php echo esc_html((string) $pageItem); ?></a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
            <li class="page-item<?php echo $currentPage >= $totalPages ? ' disabled' : ''; ?>">
                <a class="page-link" href="<?php echo esc_url($pageUrl($nextPage)); ?>" aria-label="<?php esc_attr_e('Next', 'mf-staffcore-lite'); ?>">
                    <i class="ti ti-chevron-right" aria-hidden="true"></i>
                </a>
            </li>
            <li class="page-item<?php echo $currentPage >= $totalPages ? ' disabled' : ''; ?>">
                <a class="page-link" href="<?php echo esc_url($pageUrl($lastPage)); ?>" aria-label="<?php esc_attr_e('Last page', 'mf-staffcore-lite'); ?>">
                    <i class="ti ti-chevrons-right" aria-hidden="true"></i>
                </a>
            </li>
        </ul>
    <?php endif; ?>
</div>
