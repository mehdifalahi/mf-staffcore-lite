<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Plan;

use MF\StaffCore\Plan\PlanContext;

/**
 * Lite-safe plan context: never Pro (WordPress.org Guideline 5).
 * Pro modules are absent from the Lite ZIP; no unlock filters or license checks.
 */
final class WordPressPlanContext implements PlanContext
{
    public function isPro(): bool
    {
        return false;
    }
}
