<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Modules\Suppliers\Services\SupplierService;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\SuppliersPresenter;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

final class SupplierAjaxHandler
{
    use StaffCoreAjaxAuthorization;
    use StaffCoreBulkDeleteAjax;

    private const NONCE_ACTION = 'mf_staffcore_suppliers';
    private const MODULE = 'suppliers';

    public function save(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleSave(self::MODULE, self::NONCE_ACTION);

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $userId = get_current_user_id();

        try {
            $input = $this->service()->inputFromArray($this->readInputFromPost());

            if ($id > 0) {
                $supplier = $this->service()->update($id, $input, $userId);
            } else {
                $supplier = $this->service()->create($input, $userId);
            }

            wp_send_json_success([
                'supplier' => SuppliersPresenter::supplierPayload($supplier),
                'message' => $id > 0
                    ? __('Supplier updated successfully.', 'mf-staffcore-lite')
                    : __('Supplier created successfully.', 'mf-staffcore-lite'),
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
            wp_send_json_error(['message' => 'Invalid supplier ID.'], 400);
        }

        try {
            $this->service()->delete($id);

            wp_send_json_success([
                'message' => __('Supplier deleted successfully.', 'mf-staffcore-lite'),
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
                __('supplier', 'mf-staffcore-lite'),
                __('suppliers', 'mf-staffcore-lite'),
            );
        } catch (ValidationException | RuntimeException $exception) {
            $this->handleBulkDeleteFailure($exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readInputFromPost(): array
    {
        return [
            'supplier_name' => isset($_POST['supplier_name'])
                ? sanitize_text_field(wp_unslash((string) $_POST['supplier_name']))
                : '',
            'contact_person' => isset($_POST['contact_person'])
                ? sanitize_text_field(wp_unslash((string) $_POST['contact_person']))
                : '',
            'email' => isset($_POST['email'])
                ? sanitize_email(wp_unslash((string) $_POST['email']))
                : '',
            'phone' => isset($_POST['phone'])
                ? sanitize_text_field(wp_unslash((string) $_POST['phone']))
                : '',
            'address' => isset($_POST['address'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['address']))
                : '',
            'city' => isset($_POST['city'])
                ? sanitize_text_field(wp_unslash((string) $_POST['city']))
                : '',
            'state' => isset($_POST['state'])
                ? sanitize_text_field(wp_unslash((string) $_POST['state']))
                : '',
            'country' => isset($_POST['country'])
                ? sanitize_text_field(wp_unslash((string) $_POST['country']))
                : '',
            'postal_code' => isset($_POST['postal_code'])
                ? sanitize_text_field(wp_unslash((string) $_POST['postal_code']))
                : '',
            'website' => isset($_POST['website'])
                ? esc_url_raw(wp_unslash((string) $_POST['website']))
                : '',
            'payment_terms' => isset($_POST['payment_terms'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['payment_terms']))
                : '',
            'status' => isset($_POST['status'])
                ? sanitize_key(wp_unslash((string) $_POST['status']))
                : 'ACTIVE',
            'notes' => isset($_POST['notes'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['notes']))
                : '',
        ];
    }

    private function service(): SupplierService
    {
        return ServiceContainer::instance()->suppliers();
    }

    public static function nonce(): string
    {
        return wp_create_nonce(self::NONCE_ACTION);
    }
}
