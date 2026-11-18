<?php

declare(strict_types=1);

namespace MF\StaffCore\Platforms\WordPress\Auth;

use MF\StaffCore\Auth\PlatformUser;
use MF\StaffCore\Auth\PlatformUserGateway;
use MF\StaffCore\Auth\PlatformUserInput;
use MF\StaffCore\Support\PaginatedList;
use RuntimeException;
use WP_User;
use WP_User_Query;

use function function_exists;
use function get_user_by;
use function get_users;
use function is_wp_error;
use function wp_check_password;
use function wp_delete_user;
use function wp_generate_password;
use function wp_insert_user;
use function wp_update_user;

final class WordPressPlatformUserGateway implements PlatformUserGateway
{
    public function findById(int $platformUserId): ?PlatformUser
    {
        if (! function_exists('get_user_by')) {
            return null;
        }

        $user = get_user_by('id', $platformUserId);

        return $user instanceof WP_User ? $this->mapUser($user) : null;
    }

    public function findByIds(array $platformUserIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $platformUserIds),
            static fn (int $id): bool => $id > 0,
        )));

        if ($ids === []) {
            return [];
        }

        global $wpdb;

        if (! isset($wpdb)) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '%d'));
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- %d placeholders built dynamically; values bound via prepare
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, user_login, user_email, display_name
                 FROM {$wpdb->users}
                 WHERE ID IN ({$placeholders})",
                ...$ids,
            ),
            ARRAY_A,
        );

        if (! is_array($rows)) {
            return [];
        }

        $map = [];

        foreach ($rows as $row) {
            $id = (int) ($row['ID'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $map[$id] = new PlatformUser(
                id: $id,
                login: (string) ($row['user_login'] ?? ''),
                email: (string) ($row['user_email'] ?? ''),
                displayName: (string) ($row['display_name'] ?? ''),
            );
        }

        return $map;
    }

    public function create(PlatformUserInput $input): PlatformUser
    {
        [$firstName, $lastName] = $this->splitDisplayName($input->displayName);
        $userId = wp_insert_user([
            'user_login' => $input->login,
            'user_email' => $input->email,
            'display_name' => $input->displayName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'nickname' => $input->displayName,
            'user_pass' => $input->password ?? wp_generate_password(16, true),
            'role' => 'subscriber',
        ]);

        if (is_wp_error($userId)) {
            throw new RuntimeException( esc_html( $userId->get_error_message() ) );
        }

        $user = get_user_by('id', (int) $userId);

        if (! $user instanceof WP_User) {
            throw new RuntimeException(esc_html('User was created but could not be loaded.'));
        }

        return $this->mapUser($user);
    }

    public function update(int $platformUserId, PlatformUserInput $input): PlatformUser
    {
        // WordPress does not allow changing user_login after registration.
        [$firstName, $lastName] = $this->splitDisplayName($input->displayName);
        $userId = wp_update_user([
            'ID' => $platformUserId,
            'user_email' => $input->email,
            'display_name' => $input->displayName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'nickname' => $input->displayName,
        ]);

        if (is_wp_error($userId)) {
            throw new RuntimeException( esc_html( $userId->get_error_message() ) );
        }

        $user = get_user_by('id', $platformUserId);

        if (! $user instanceof WP_User) {
            throw new RuntimeException(esc_html('User was updated but could not be loaded.'));
        }

        return $this->mapUser($user);
    }

    public function setPassword(int $platformUserId, string $password): void
    {
        $userId = wp_update_user([
            'ID' => $platformUserId,
            'user_pass' => $password,
        ]);

        if (is_wp_error($userId)) {
            throw new RuntimeException( esc_html( $userId->get_error_message() ) );
        }
    }

    public function verifyPassword(int $platformUserId, string $password): bool
    {
        if ($platformUserId <= 0 || ! function_exists('get_user_by')) {
            return false;
        }

        $user = get_user_by('id', $platformUserId);

        if (! $user instanceof WP_User) {
            return false;
        }

        return wp_check_password($password, $user->user_pass, $platformUserId);
    }

    public function delete(int $platformUserId): void
    {
        if ($platformUserId === 1) {
            throw new RuntimeException(esc_html('Cannot delete the primary WordPress administrator.'));
        }

        if (! function_exists('wp_delete_user')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        if (! wp_delete_user($platformUserId)) {
            throw new RuntimeException(esc_html('Failed to delete WordPress user.'));
        }
    }

    public function loginExists(string $login, ?int $excludePlatformUserId = null): bool
    {
        $existing = get_user_by('login', $login);

        if (! $existing instanceof WP_User) {
            return false;
        }

        if ($excludePlatformUserId !== null && (int) $existing->ID === $excludePlatformUserId) {
            return false;
        }

        return true;
    }

    public function emailExists(string $email, ?int $excludePlatformUserId = null): bool
    {
        $existing = get_user_by('email', $email);

        if (! $existing instanceof WP_User) {
            return false;
        }

        if ($excludePlatformUserId !== null && (int) $existing->ID === $excludePlatformUserId) {
            return false;
        }

        return true;
    }

    public function search(string $query, int $limit = 25, array $excludePlatformUserIds = []): array
    {
        if (! function_exists('get_users')) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $query = trim($query);

        $args = [
            'number' => $limit,
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => 'all',
        ];

        if ($query !== '') {
            $args['search'] = '*' . $query . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }

        $exclude = array_values(array_filter(
            array_map('intval', $excludePlatformUserIds),
            static fn (int $id): bool => $id > 0,
        ));

        if ($exclude !== []) {
            $args['exclude'] = $exclude;
        }

        $users = get_users($args);
        $results = [];

        foreach ($users as $user) {
            if ($user instanceof WP_User) {
                $results[] = $this->mapUser($user);
            }
        }

        return $results;
    }

    public function findIdsBySearch(string $query): array
    {
        $query = trim($query);

        if ($query === '' || ! class_exists(WP_User_Query::class)) {
            return [];
        }

        $userQuery = new WP_User_Query([
            'search' => '*' . $query . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'fields' => 'ID',
            'number' => -1,
        ]);

        $ids = $userQuery->get_results();

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_filter(
            array_map('intval', $ids),
            static fn (int $id): bool => $id > 0,
        ));
    }

    public function listUnlinkedCandidates(
        string $search,
        array $excludePlatformUserIds,
        int $page,
        int $perPage,
    ): PaginatedList {
        if (! class_exists(WP_User_Query::class)) {
            return new PaginatedList([], 0, max(1, $page), max(1, $perPage));
        }

        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $search = trim($search);

        $exclude = array_values(array_filter(
            array_map('intval', $excludePlatformUserIds),
            static fn (int $id): bool => $id > 0,
        ));

        $args = [
            'number' => $perPage,
            'offset' => ($page - 1) * $perPage,
            'orderby' => 'ID',
            'order' => 'DESC',
            'fields' => 'all',
            'count_total' => true,
        ];

        if ($exclude !== []) {
            $args['exclude'] = $exclude;
        }

        if ($search !== '') {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }

        $query = new WP_User_Query($args);
        $users = $query->get_results();
        $items = [];

        if (is_array($users)) {
            foreach ($users as $user) {
                if ($user instanceof WP_User) {
                    $items[] = $this->mapUser($user);
                }
            }
        }

        return new PaginatedList($items, (int) $query->get_total(), $page, $perPage);
    }

    public function platformRoleLabel(int $platformUserId): ?string
    {
        if ($platformUserId <= 0 || ! function_exists('get_user_by')) {
            return null;
        }

        $user = get_user_by('id', $platformUserId);

        if (! $user instanceof WP_User) {
            return null;
        }

        $roles = $user->roles;

        if ($roles === []) {
            return null;
        }

        return (string) $roles[0];
    }

    private function mapUser(WP_User $user): PlatformUser
    {
        return new PlatformUser(
            id: (int) $user->ID,
            login: (string) $user->user_login,
            email: (string) $user->user_email,
            displayName: (string) $user->display_name,
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitDisplayName(string $displayName): array
    {
        $displayName = trim($displayName);

        if ($displayName === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $displayName, 2) ?: [];

        return [
            (string) ($parts[0] ?? ''),
            (string) ($parts[1] ?? ''),
        ];
    }
}

