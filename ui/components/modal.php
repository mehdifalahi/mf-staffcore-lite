<?php

declare(strict_types=1);

/**
 * Reusable Tabler/Bootstrap modal shell.
 *
 * Trust boundary: $bodyHtml / $footerHtml are ALWAYS built server-side via
 * ob_start()/ob_get_clean() from other view partials in this same plugin
 * (see modules/*\/Views/page.php) — every leaf value inside those partials is
 * already escaped with esc_html()/esc_attr()/wp_kses_post() at the point of
 * output. Nothing here ever assigns raw $_GET/$_POST/$_REQUEST input directly
 * to $bodyHtml or $footerHtml. Do NOT start doing so — if a caller ever needs
 * to inject user-supplied HTML here, it must be escaped/kses'd at that call
 * site BEFORE reaching this template.
 *
 * @var string $modalId
 * @var string $title
 * @var string $size modal-dialog class suffix (e.g. modal-lg)
 * @var string $bodyHtml inner HTML for modal-body (pre-escaped by caller, see above)
 * @var string $footerHtml inner HTML for modal-footer (pre-escaped by caller, see above)
 * @var string $modalExtraClass optional extra classes on the modal root
 * @var string $dialogExtraClass optional extra classes on modal-dialog
 * @var bool $dialogCentered whether to vertically center the dialog (default true)
 */

if (! defined('ABSPATH')) {
    exit;
}

$modalId = $modalId ?? 'staffcore-modal';
$title = $title ?? '';
$size = $size ?? 'modal-lg';
$bodyHtml = $bodyHtml ?? '';
$footerHtml = $footerHtml ?? '';
$modalExtraClass = $modalExtraClass ?? '';
$dialogExtraClass = $dialogExtraClass ?? '';
$dialogCentered = $dialogCentered ?? true;
$modalRootClass = trim('modal modal-blur fade ' . $modalExtraClass);
$dialogClass = trim($size . ' ' . $dialogExtraClass . ($dialogCentered ? ' modal-dialog-centered' : ''));
?>
<div
    class="<?php echo esc_attr($modalRootClass); ?>"
    id="<?php echo esc_attr($modalId); ?>"
    tabindex="-1"
    role="dialog"
    aria-hidden="true"
    data-staffcore-modal
    data-bs-scroll="true"
>
    <div class="modal-dialog <?php echo esc_attr($dialogClass); ?>" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo esc_html($title); ?></h5>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="<?php esc_attr_e('Close', 'mf-staffcore-lite'); ?>"
                ></button>
            </div>
            <div class="modal-body">
                <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped server-composed HTML, see file docblock ?>
                <?php echo $bodyHtml; ?>
            </div>
            <?php if ($footerHtml !== '') : ?>
                <div class="modal-footer">
                    <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pre-escaped server-composed HTML, see file docblock ?>
                    <?php echo $footerHtml; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
