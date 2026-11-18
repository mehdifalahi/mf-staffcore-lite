<?php

declare(strict_types=1);

namespace MF\StaffCore\Plan;

/**
 * Default when no platform context is registered (local development = full features).
 */
final class DevelopmentPlanContext implements PlanContext
{
    public function isPro(): bool
    {
        return true;
    }
}
