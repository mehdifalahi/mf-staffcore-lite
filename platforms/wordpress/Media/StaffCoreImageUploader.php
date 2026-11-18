<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Media;

use MF\StaffCore\Support\ValidationException;

/**
 * WordPress adapter: stores StaffCore images under wp-content/uploads.
 */
final class StaffCoreImageUploader
{
    private const MAX_BYTES = 5 * 1024 * 1024;

    /** @var list<string> */
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * @param array<string, mixed> $file $_FILES entry
     */
    public static function uploadEmployeePhoto(array $file): string
    {
        return self::upload($file, 'mf-staffcore/employees');
    }

    /**
     * @param array<string, mixed> $file
     */
    private static function upload(array $file, string $subdir): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new ValidationException(esc_html(__('No image file was uploaded.', 'mf-staffcore-lite')));
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new ValidationException(esc_html(__('Image upload failed.', 'mf-staffcore-lite')));
        }

        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new ValidationException(esc_html(sprintf(
                    /* translators: %d: maximum file size in megabytes */
                    __('Image must be smaller than %d MB.', 'mf-staffcore-lite'),
                    (int) (self::MAX_BYTES / 1024 / 1024),
                )));
        }

        $mime = (string) ($file['type'] ?? '');

        if ($mime !== '' && ! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new ValidationException(esc_html(__('Only JPEG, PNG, and WebP images are allowed.', 'mf-staffcore-lite')));
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $filter = static function (array $dirs) use ($subdir): array {
            $dirs['subdir'] = '/' . trim($subdir, '/');
            $dirs['path'] = $dirs['basedir'] . $dirs['subdir'];
            $dirs['url'] = $dirs['baseurl'] . $dirs['subdir'];

            if (! is_dir($dirs['path'])) {
                wp_mkdir_p($dirs['path']);
            }

            return $dirs;
        };

        add_filter('upload_dir', $filter);

        try {
            $upload = wp_handle_upload($file, [
                'test_form' => false,
                'mimes' => [
                    'jpg|jpeg|jpe' => 'image/jpeg',
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                ],
            ]);
        } finally {
            remove_filter('upload_dir', $filter);
        }

        if (isset($upload['error'])) {
            throw new ValidationException( esc_html( (string) $upload['error'] ) );
        }

        $url = (string) ($upload['url'] ?? '');

        if ($url === '') {
            throw new ValidationException(esc_html(__('Image upload failed.', 'mf-staffcore-lite')));
        }

        return $url;
    }
}

