<?php

declare(strict_types=1);

namespace MF\StaffCore\Auth;

use MF\StaffCore\Support\PaginatedList;

/**
 * Bridge to the host platform user store (WordPress, Joomla, etc.).
 */
interface PlatformUserGateway
{
    public function findById(int $platformUserId): ?PlatformUser;

    /**
     * @param list<int> $platformUserIds
     * @return array<int, PlatformUser> keyed by platform user ID
     */
    public function findByIds(array $platformUserIds): array;

    public function create(PlatformUserInput $input): PlatformUser;

    public function update(int $platformUserId, PlatformUserInput $input): PlatformUser;

    public function setPassword(int $platformUserId, string $password): void;

    public function verifyPassword(int $platformUserId, string $password): bool;

    public function delete(int $platformUserId): void;

    public function loginExists(string $login, ?int $excludePlatformUserId = null): bool;

    public function emailExists(string $email, ?int $excludePlatformUserId = null): bool;

    /**
     * Search platform accounts available to link (adapter may exclude IDs already in StaffCore).
     *
     * @param list<int> $excludePlatformUserIds
     * @return list<PlatformUser>
     */
    public function search(string $query, int $limit = 25, array $excludePlatformUserIds = []): array;

    /**
     * Platform user IDs whose display name, login, or email match the query (adapter-specific).
     *
     * @return list<int>
     */
    public function findIdsBySearch(string $query): array;

    /**
     * Paginated platform accounts excluding given IDs (bulk link — unlinked_all source).
     *
     * @param list<int> $excludePlatformUserIds
     * @return PaginatedList<PlatformUser>
     */
    public function listUnlinkedCandidates(
        string $search,
        array $excludePlatformUserIds,
        int $page,
        int $perPage,
    ): PaginatedList;

    /** WordPress: subscriber, administrator, etc. — display/filter only. */
    public function platformRoleLabel(int $platformUserId): ?string;
}
