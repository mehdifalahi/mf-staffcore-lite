<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Platforms\WordPress\Admin\Presenters\BulkDeletePresenter;
use MF\StaffCore\Support\BulkDeleteResult;
use MF\StaffCore\Support\ValidationException;
use RuntimeException;

trait StaffCoreBulkDeleteAjax
{
    /**
     * @return list<int>
     *
     * Caller must run authorizeModuleDelete / check_ajax_referer before this helper.
     */
    protected function readBulkDeleteIdsFromPost(): array
    {
        if (! isset($_POST['ids'])) {
            return [];
        }

        $raw = wp_unslash($_POST['ids']);

        if (is_string($raw)) {
            $trimmed = trim($raw);

            if ($trimmed === '') {
                return [];
            }

            if (str_starts_with($trimmed, '[')) {
                try {
                    $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    return [];
                }

                $raw = is_array($decoded) ? $decoded : [];
            } else {
                $raw = array_map(
                    static fn (string $part): string => sanitize_text_field(trim($part)),
                    explode(',', $trimmed),
                );
            }
        }

        if (! is_array($raw)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => absint($id), $raw),
            static fn (int $id): bool => $id > 0,
        )));
    }

    protected function sendBulkDeleteResponse(
        BulkDeleteResult $result,
        string $singular,
        string $plural,
    ): void {
        wp_send_json_success([
            'result' => BulkDeletePresenter::resultPayload($result),
            'message' => BulkDeletePresenter::summaryMessage($result, $singular, $plural),
        ]);
    }

    protected function handleBulkDeleteFailure(ValidationException|RuntimeException $exception): void
    {
        if ($exception instanceof ValidationException) {
            wp_send_json_error(['message' => $exception->getMessage()], 422);

            return;
        }

        wp_send_json_error(['message' => $exception->getMessage()], 500);
    }
}
