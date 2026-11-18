<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Modules\CompanyInformation\Services\CompanyInformationService;
use MF\StaffCore\Platforms\WordPress\Admin\Presenters\CompanyInformationPresenter;
use MF\StaffCore\Platforms\WordPress\Bootstrap\ServiceContainer;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

final class CompanyInformationAjaxHandler
{
    use StaffCoreAjaxAuthorization;

    private const NONCE_ACTION = 'mf_staffcore_company_information';
    private const MODULE = 'company_information';

    public function save(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleEdit(self::MODULE, self::NONCE_ACTION);

        $userId = get_current_user_id();

        try {
            $raw = $this->readInputFromPost();
            $raw['logo'] = $this->resolveLogoPath($raw['logo'] ?? null);

            $input = $this->service()->inputFromArray($raw);
            $profile = $this->service()->save($input, $userId);

            wp_send_json_success([
                'profile' => CompanyInformationPresenter::profilePayload($profile),
                'message' => __('Company information saved successfully.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    public function removeLogo(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $this->authorizeModuleEdit(self::MODULE, self::NONCE_ACTION);

        try {
            $profile = $this->service()->removeLogo(get_current_user_id());

            wp_send_json_success([
                'profile' => CompanyInformationPresenter::profilePayload($profile),
                'message' => __('Company logo removed.', 'mf-staffcore-lite'),
            ]);
        } catch (ValidationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);
        } catch (RuntimeException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 500);
        }
    }

    private function resolveLogoPath(?string $existing): ?string
    {
        if (! isset($_FILES['logo']) || ! is_array($_FILES['logo'])) {
            return $existing !== null && $existing !== '' ? $existing : null;
        }

        $file = $_FILES['logo'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $existing !== null && $existing !== '' ? $existing : null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new ValidationException(esc_html('Logo upload failed.'));
        }

        $mime = (string) ($file['type'] ?? '');
        if ($mime !== '' && ! str_starts_with($mime, 'image/')) {
            throw new ValidationException(esc_html('Logo must be an image file.'));
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
            'name' => isset($_POST['name'])
                ? sanitize_text_field(wp_unslash((string) $_POST['name']))
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
            'province' => isset($_POST['province'])
                ? sanitize_text_field(wp_unslash((string) $_POST['province']))
                : '',
            'country' => isset($_POST['country'])
                ? sanitize_text_field(wp_unslash((string) $_POST['country']))
                : '',
            'website' => isset($_POST['website'])
                ? esc_url_raw(wp_unslash((string) $_POST['website']))
                : '',
            'currency' => isset($_POST['currency'])
                ? sanitize_text_field(wp_unslash((string) $_POST['currency']))
                : '',
            'timezone' => isset($_POST['timezone'])
                ? sanitize_text_field(wp_unslash((string) $_POST['timezone']))
                : '',
            'logo' => isset($_POST['logo_current'])
                ? esc_url_raw(wp_unslash((string) $_POST['logo_current']))
                : '',
        ];
    }

    private function service(): CompanyInformationService
    {
        return ServiceContainer::instance()->companyInformation();
    }

    public static function nonce(): string
    {
        return wp_create_nonce(self::NONCE_ACTION);
    }
}

