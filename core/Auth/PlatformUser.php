<?php

declare(strict_types=1);

namespace MF\StaffCore\Auth;

/**
 * Platform-agnostic view of an external CMS user account.
 */
final class PlatformUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $login,
        public readonly string $email,
        public readonly string $displayName,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            login: (string) ($row['login'] ?? ''),
            email: (string) ($row['email'] ?? ''),
            displayName: (string) ($row['display_name'] ?? $row['displayName'] ?? ''),
        );
    }

    /** Display name or login with email for selectors (e.g. "Jane Doe (jane@example.com)"). */
    public function accountLabel(): string
    {
        $name = trim($this->displayName);

        if ($name === '') {
            $name = trim($this->login);
        }

        $email = trim($this->email);

        if ($name !== '' && $email !== '') {
            return $name . ' (' . $email . ')';
        }

        if ($name !== '') {
            return $name;
        }

        if ($email !== '') {
            return $email;
        }

        return 'User #' . $this->id;
    }
}
