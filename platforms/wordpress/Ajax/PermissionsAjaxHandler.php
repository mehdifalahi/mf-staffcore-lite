<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Authorization\PermissionModuleRegistry;
use MF\StaffCore\Authorization\RoleRegistry;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

final class PermissionsAjaxHandler
{
    use StaffCoreAjaxAuthorization;

    private const NONCE_ACTION = 'mf_staffcore_permissions';

    private const MODULE = 'permissions';

    public function save(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleEdit(self::MODULE, self::NONCE_ACTION);

        $roleId = isset($_POST['role'])
            ? sanitize_key(wp_unslash((string) $_POST['role']))
            : '';

        if (! RoleRegistry::isValidRole($roleId)) {
            wp_send_json_error(['message' => esc_html__('Invalid role.', 'mf-staffcore-lite')], 400);
        }

        $raw = isset($_POST['permissions'])
            ? wp_unslash((string) $_POST['permissions'])
            : '[]';

        if (! is_string($raw)) {
            $raw = '[]';
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($decoded)) {
                throw new ValidationException(esc_html__('Invalid permissions payload.', 'mf-staffcore-lite'));
            }

            $allowed = PermissionModuleRegistry::allPermissionKeys();
            $keys = [];

            foreach ($decoded as $key) {
                if (! is_string($key)) {
                    continue;
                }

                $candidate = trim($key);
                // Whitelist against known permission keys (e.g. employees.view) — do not use
                // sanitize_key() which strips dots.
                if ($candidate !== '' && in_array($candidate, $allowed, true)) {
                    $keys[] = $candidate;
                }
            }

            $keys = array_values(array_unique($keys));

            ServiceContainer::instance()
                ->permissionService()
                ->replaceRolePermissions($roleId, $keys);

            ServiceContainer::instance()->authorization()->resetCurrent();

            wp_send_json_success([
                'message' => __('Permissions saved successfully.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (\JsonException) {
            wp_send_json_error(['message' => esc_html__('Invalid permissions payload.', 'mf-staffcore-lite')], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    public static function nonce(): string
    {
        return wp_create_nonce(self::NONCE_ACTION);
    }
}

