<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Ajax;

use MF\StaffCore\Plan\PlanException;
use MF\StaffCore\Plan\PlanRegistry;
use MF\StaffCore\Support\ValidationException;
use Throwable;

trait StaffCorePlanAjaxResponse
{
    protected function sendJsonErrorWithPlanUpgrade(Throwable $exception, int $status = 422): void
    {
        $payload = ['message' => $exception->getMessage()];

        if (PlanRegistry::shouldShowUpgradeUi() && self::exceptionQualifiesForUpgradeLink($exception)) {
            $payload['upgrade_url'] = PlanRegistry::upgradeUrl('plan_exception');
            $payload['upgrade_label'] = __('Upgrade to Pro', 'mf-staffcore-lite');
        }

        wp_send_json_error($payload, $status);
    }

    private static function exceptionQualifiesForUpgradeLink(Throwable $exception): bool
    {
        if ($exception instanceof PlanException) {
            return true;
        }

        if ($exception instanceof ValidationException) {
            return str_contains($exception->getMessage(), 'Upgrade to Pro');
        }

        return false;
    }
}
