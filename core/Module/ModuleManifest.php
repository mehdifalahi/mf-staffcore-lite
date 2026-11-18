<?php

declare(strict_types=1);

namespace MF\StaffCore\Module;

/**
 * Platform-agnostic module metadata loaded from module.json.
 */
final class ModuleManifest
{
    /**
     * @param list<string> $permissions
     * @param list<string> $dependencies
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly string $version,
        public readonly string $namespace,
        public readonly bool $enabled,
        public readonly array $permissions,
        public readonly array $dependencies,
        public readonly string $path,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, string $path): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            description: (string) ($data['description'] ?? ''),
            version: (string) ($data['version'] ?? '0.0.0'),
            namespace: (string) ($data['namespace'] ?? ''),
            enabled: (bool) ($data['enabled'] ?? true),
            permissions: array_values(array_map('strval', $data['permissions'] ?? [])),
            dependencies: array_values(array_map('strval', $data['dependencies'] ?? [])),
            path: $path,
        );
    }

    public function isValid(): bool
    {
        return $this->id !== ''
            && $this->name !== ''
            && $this->namespace !== '';
    }
}
