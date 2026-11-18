<?php

declare(strict_types=1);

namespace MF\StaffCore\Database;

/**
 * Persists schema install metadata outside business tables.
 */
interface InstallStateStore
{
    public function getInstalledVersion(): ?string;

    public function setInstalledVersion(string $version): void;
}
