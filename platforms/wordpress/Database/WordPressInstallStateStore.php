<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Database;

use MF\StaffCore\Database\InstallStateStore;

final class WordPressInstallStateStore implements InstallStateStore
{
    public function __construct(
        private readonly string $optionName,
    ) {
    }

    public function getInstalledVersion(): ?string
    {
        $version = get_option($this->optionName, null);

        return is_string($version) && $version !== '' ? $version : null;
    }

    public function setInstalledVersion(string $version): void
    {
        update_option($this->optionName, $version, false);
    }
}
