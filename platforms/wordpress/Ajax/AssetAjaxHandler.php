<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Modules\Assets\Services\AssetService;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\AssetsPresenter;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

final class AssetAjaxHandler
{
    use StaffCoreAjaxAuthorization;
    use StaffCoreBulkDeleteAjax;

    private const NONCE_ACTION = 'mf_staffcore_assets';
    private const MODULE = 'assets';

    public function save(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleSave(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $userId = get_current_user_id();

        try {
            $raw = $this->readInputFromPost();
            $raw['image'] = $this->resolveImagePath($raw['image'] ?? null, $id);

            $input = $this->service()->inputFromArray($raw);

            if ($id > 0) {
                $asset = $this->service()->update($id, $input, $userId);
            } else {
                $asset = $this->service()->create($input, $userId);
            }

            wp_send_json_success([
                'asset' => AssetsPresenter::assetPayload($asset),
                'message' => $id > 0
                    ? __('Asset updated successfully.', 'mf-staffcore-lite')
                    : __('Asset created successfully.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    public function delete(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleDelete(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid asset ID.'], 400);
        }

        try {
            $this->service()->delete($id);

            wp_send_json_success([
                'message' => __('Asset deleted successfully.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    public function bulkDelete(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleDelete(self::MODULE, self::NONCE_ACTION);

        try {
            $ids = $this->readBulkDeleteIdsFromPost();
            $result = $this->service()->bulkDelete($ids, get_current_user_id());
            $this->sendBulkDeleteResponse(
                $result,
                __('asset', 'mf-staffcore-lite'),
                __('assets', 'mf-staffcore-lite'),
            );
        } catch (ValidationException | RuntimeException $exception) {
            $this->handleBulkDeleteFailure($exception);
        }
    }

    public function removeImage(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleEdit(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id <= 0) {
            wp_send_json_error(['message' => 'Invalid asset ID.'], 400);
        }

        try {
            $asset = $this->service()->removeImage($id, get_current_user_id());

            wp_send_json_success([
                'asset' => AssetsPresenter::assetPayload($asset),
                'message' => __('Asset image removed.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    private function resolveImagePath(?string $existing, int $assetId): ?string
    {
        if (! isset($_FILES['image']) || ! is_array($_FILES['image'])) {
            return $existing !== null && $existing !== '' ? $existing : null;
        }

        $file = $_FILES['image'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $existing !== null && $existing !== '' ? $existing : null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new ValidationException(esc_html('Image upload failed.'));
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $upload = wp_handle_upload($file, ['test_form' => false]);

        if (isset($upload['error'])) {
            throw new ValidationException( esc_html( (string) $upload['error'] ) );
        }

        return (string) ($upload['url'] ?? $existing);
    }

    /**
     * @return array<string, mixed>
     */
    private function readInputFromPost(): array
    {
        return [
            'code' => isset($_POST['code'])
                ? sanitize_text_field(wp_unslash((string) $_POST['code']))
                : '',
            'name' => isset($_POST['name'])
                ? sanitize_text_field(wp_unslash((string) $_POST['name']))
                : '',
            'category_id' => isset($_POST['category_id'])
                ? (int) $_POST['category_id']
                : 0,
            'purchase_date' => isset($_POST['purchase_date'])
                ? sanitize_text_field(wp_unslash((string) $_POST['purchase_date']))
                : '',
            'purchase_cost' => isset($_POST['purchase_cost'])
                ? sanitize_text_field(wp_unslash((string) $_POST['purchase_cost']))
                : '',
            'current_value' => isset($_POST['current_value'])
                ? sanitize_text_field(wp_unslash((string) $_POST['current_value']))
                : '',
            'serial_number' => isset($_POST['serial_number'])
                ? sanitize_text_field(wp_unslash((string) $_POST['serial_number']))
                : '',
            'warranty_expiry' => isset($_POST['warranty_expiry'])
                ? sanitize_text_field(wp_unslash((string) $_POST['warranty_expiry']))
                : '',
            'status' => isset($_POST['status'])
                ? sanitize_key(wp_unslash((string) $_POST['status']))
                : 'ACTIVE',
            'condition' => isset($_POST['condition'])
                ? sanitize_key(wp_unslash((string) $_POST['condition']))
                : 'GOOD',
            'assigned_to' => isset($_POST['assigned_to'])
                ? sanitize_text_field(wp_unslash((string) $_POST['assigned_to']))
                : '',
            'location' => isset($_POST['location'])
                ? sanitize_text_field(wp_unslash((string) $_POST['location']))
                : '',
            'supplier_id' => isset($_POST['supplier_id'])
                ? sanitize_text_field(wp_unslash((string) $_POST['supplier_id']))
                : '',
            'image' => isset($_POST['image_current'])
                ? esc_url_raw(wp_unslash((string) $_POST['image_current']))
                : '',
            'notes' => isset($_POST['notes'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['notes']))
                : '',
        ];
    }

    private function service(): AssetService
    {
        return ServiceContainer::instance()->assets();
    }

    public static function nonce(): string
    {
        return wp_create_nonce(self::NONCE_ACTION);
    }
}

