<?php

declare(strict_types=1);

/**
 * Editable role permissions matrix (Settings → Permissions).
 *
 * @var list<array{id: string, label: string, description: string}> $roles
 * @var array<string, list<array{module: string, label: string, group: string, icon: string}>> $modulesByGroup
 * @var array<string, array<string, bool>> $matrix
 * @var array<string, string> $actionLabels
 * @var bool $canEdit
 * @var string $ajaxUrl
 * @var string $nonce
 */

use MF\StaffCore\Authorization\PermissionAction;
use MF\StaffCore\Authorization\RoleRegistry;
use MF\StaffCore\UI\TablerIcon;

if (! defined('ABSPATH')) {
    exit;
}
?>
<div
    class="staffcore-permissions-matrix"
    data-staffcore-module="permissions"
    data-ajax-url="<?php echo esc_url($ajaxUrl); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>"
    data-can-edit="<?php echo $canEdit ? '1' : '0'; ?>"
>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php esc_html_e('Role permissions', 'mf-staffcore-lite'); ?></h3>
            <?php if ($canEdit) : ?>
                <div class="card-actions">
                    <button type="button" class="btn btn-outline-primary" data-action="save-permissions" disabled>
                        <?php TablerIcon::print('device-floppy', 'me-1'); ?>
                        <?php esc_html_e('Save changes', 'mf-staffcore-lite'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body border-bottom">
            <p class="text-secondary mb-0">
                <?php if ($canEdit) : ?>
                    <?php esc_html_e(
                        'Configure view, create, edit, and delete access per screen for each role. Changes apply after save.',
                        'mf-staffcore-lite',
                    ); ?>
                <?php else : ?>
                    <?php esc_html_e('You can view permission settings but cannot modify them.', 'mf-staffcore-lite'); ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs staffcore-page-tabs mb-3" role="tablist">
                <?php foreach ($roles as $index => $role) :
                    $roleId = (string) $role['id'];
                    $tabId = 'permissions-role-' . $roleId;
                    ?>
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link<?php echo $index === 0 ? ' active' : ''; ?>"
                            id="<?php echo esc_attr($tabId); ?>-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#<?php echo esc_attr($tabId); ?>"
                            type="button"
                            role="tab"
                            aria-controls="<?php echo esc_attr($tabId); ?>"
                            <?php echo $index === 0 ? 'aria-selected="true"' : 'aria-selected="false"'; ?>
                        >
                            <?php echo esc_html(RoleRegistry::displayLabel($roleId)); ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content">
                <?php foreach ($roles as $index => $role) :
                    $roleId = (string) $role['id'];
                    $tabId = 'permissions-role-' . $roleId;
                    ?>
                    <div
                        class="tab-pane fade<?php echo $index === 0 ? ' show active' : ''; ?>"
                        id="<?php echo esc_attr($tabId); ?>"
                        role="tabpanel"
                        aria-labelledby="<?php echo esc_attr($tabId); ?>-tab"
                        data-role="<?php echo esc_attr($roleId); ?>"
                    >
                        <?php foreach ($modulesByGroup as $groupLabel => $groupModules) : ?>
                            <div class="mb-4">
                                <h4 class="text-secondary text-uppercase small mb-3">
                                    <?php echo esc_html($groupLabel); ?>
                                </h4>
                                <div class="list-group list-group-flush staffcore-permissions-modules">
                                    <?php foreach ($groupModules as $module) :
                                        $moduleId = (string) $module['module'];
                                        ?>
                                        <div class="list-group-item px-0 staffcore-permissions-module">
                                            <div class="d-flex flex-wrap align-items-center gap-3">
                                                <div class="staffcore-permissions-module__title flex-grow-1">
                                                    <span class="text-secondary me-2" aria-hidden="true">
                                                        <?php TablerIcon::print((string) ($module['icon'] ?? 'folder')); ?>
                                                    </span>
                                                    <span class="fw-medium">
                                                        <?php echo esc_html((string) $module['label']); ?>
                                                    </span>
                                                </div>
                                                <div class="staffcore-permissions-module__actions d-flex flex-wrap gap-3">
                                                    <?php foreach (PermissionAction::ALL as $action) :
                                                        $key = PermissionAction::key($moduleId, $action);
                                                        $checked = $matrix[$roleId][$key] ?? false;
                                                        $inputId = $roleId . '-' . $moduleId . '-' . $action;
                                                        ?>
                                                        <label
                                                            class="form-check form-check-inline mb-0 staffcore-permissions-check"
                                                            for="<?php echo esc_attr($inputId); ?>"
                                                        >
                                                            <input
                                                                class="form-check-input"
                                                                type="checkbox"
                                                                id="<?php echo esc_attr($inputId); ?>"
                                                                name="permissions[<?php echo esc_attr($roleId); ?>][]"
                                                                value="<?php echo esc_attr($key); ?>"
                                                                data-permission-key="<?php echo esc_attr($key); ?>"
                                                                <?php checked($checked); ?>
                                                                <?php disabled(! $canEdit); ?>
                                                            >
                                                            <span class="form-check-label">
                                                                <?php echo esc_html($actionLabels[$action] ?? $action); ?>
                                                            </span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" data-role="toast-container"></div>
</div>
