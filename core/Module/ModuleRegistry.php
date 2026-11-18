<?php

declare(strict_types=1);

namespace MF\StaffCore\Module;

/**
 * Discovers and loads module.json manifests from /modules.
 */
final class ModuleRegistry
{
    private const MANIFEST_FILENAME = 'module.json';

    /** @var list<ModuleManifest>|null */
    private ?array $modules = null;

    public function __construct(
        private readonly string $modulesPath,
    ) {
    }

    /**
     * @return list<ModuleManifest>
     */
    public function all(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        $this->modules = [];

        if (! is_dir($this->modulesPath)) {
            return $this->modules;
        }

        $directories = glob($this->modulesPath . '/*', GLOB_ONLYDIR) ?: [];

        foreach ($directories as $directory) {
            $manifestPath = $directory . DIRECTORY_SEPARATOR . self::MANIFEST_FILENAME;

            if (! is_readable($manifestPath)) {
                continue;
            }

            $raw = file_get_contents($manifestPath);
            if ($raw === false) {
                continue;
            }

            /** @var array<string, mixed>|null $data */
            $data = json_decode($raw, true);
            if (! is_array($data)) {
                continue;
            }

            $manifest = ModuleManifest::fromArray($data, $directory);

            if ($manifest->isValid() && $manifest->enabled) {
                $this->modules[] = $manifest;
            }
        }

        usort(
            $this->modules,
            static fn (ModuleManifest $a, ModuleManifest $b): int => strcmp($a->id, $b->id),
        );

        return $this->modules;
    }

    public function find(string $id): ?ModuleManifest
    {
        foreach ($this->all() as $module) {
            if ($module->id === $id) {
                return $module;
            }
        }

        return null;
    }
}
