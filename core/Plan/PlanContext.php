<?php

declare(strict_types=1);

namespace MF\StaffCore\Plan;

/**
 * Platform-independent plan detection (Free vs Pro).
 */
interface PlanContext
{
    public function isPro(): bool;
}
