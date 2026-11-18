<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Auth;

use MF\StaffCore\Authorization\AuthorizationException;
use MF\StaffCore\Authorization\AuthorizationService;
use MF\StaffCore\Authorization\StaffUserContext;
use MF\StaffCore\Authorization\StaffUserResolverInterface;

use function current_user_can;
use function get_current_user_id;
use function is_user_logged_in;
use function wp_send_json_error;

/**
 * WordPress adapter: session → StaffCore authorization (including migration fallback).
 */
final class WordPressAuthorizationBridge
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly StaffUserResolverInterface $staffUserResolver,
    ) {
    }

    public function getCurrentPlatformUserId(): int
    {
        return (int) get_current_user_id();
    }

    /**
     * Resolves staff_user for the logged-in platform account and primes AuthorizationService.
     *
     * When no staff_user row exists, users with {@code manage_options} receive a synthetic
     * administrator context (migration fallback; prefer install seed for platform user 1).
     */
    public function resolveCurrentStaffUser(): ?StaffUserContext
    {
        $platformUserId = $this->getCurrentPlatformUserId();

        if ($platformUserId <= 0) {
            $this->authorization->setCurrentContext(null);

            return null;
        }

        $context = $this->staffUserResolver->resolveByPlatformUserId($platformUserId);

        if ($context !== null) {
            $this->authorization->setCurrentContext($context);

            return $context;
        }

        if ($this->shouldUsePlatformAdminFallback()) {
            $fallback = StaffUserContext::platformAdminFallback($platformUserId);
            $this->authorization->setCurrentContext($fallback);

            return $fallback;
        }

        $this->authorization->setCurrentContext(null);

        return null;
    }

    public function requireActiveStaffUser(): StaffUserContext
    {
        $context = $this->resolveCurrentStaffUser();

        if ($context === null || ! $context->isActive()) {
            throw new AuthorizationException(esc_html('You do not have access to StaffCore. Contact an administrator to enable your account.'));
        }

        return $context;
    }

    public function can(string $permission): bool
    {
        $this->resolveCurrentStaffUser();

        return $this->authorization->can($permission);
    }

    public function authorizePermission(string $permission): void
    {
        if (! is_user_logged_in()) {
            wp_send_json_error(['message' => 'Forbidden.'], 403);
        }

        try {
            $this->resolveCurrentStaffUser();
            $this->authorization->require($permission);
        } catch (AuthorizationException $exception) {
            wp_send_json_error(['message' => $exception->getMessage()], 403);
        }
    }

    private function shouldUsePlatformAdminFallback(): bool
    {
        return current_user_can('manage_options');
    }
}

