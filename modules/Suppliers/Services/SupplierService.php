<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Suppliers\Services;

use MF\StaffCore\Modules\Suppliers\DTO\Supplier;
use MF\StaffCore\Modules\Suppliers\DTO\SupplierInput;
use MF\StaffCore\Modules\Suppliers\Repositories\SupplierRepository;
use MF\StaffCore\Support\BulkDeleteExecutor;
use MF\StaffCore\Support\BulkDeleteResult;
use MF\StaffCore\Support\PaginatedList;
use MF\StaffCore\Support\ValidationException;

final class SupplierService
{
    private const SUPPLIER_NAME_MAX = 255;
    private const CONTACT_PERSON_MAX = 100;
    private const EMAIL_MAX = 255;
    private const PHONE_MAX = 20;
    private const CITY_MAX = 100;
    private const STATE_MAX = 100;
    private const COUNTRY_MAX = 100;
    private const POSTAL_CODE_MAX = 20;
    private const WEBSITE_MAX = 255;

    public function __construct(
        private readonly SupplierRepository $repository,
    ) {
    }

    /**
     * @return PaginatedList<Supplier>
     */
    public function listPage(int $page, int $perPage = PaginatedList::DEFAULT_PER_PAGE): PaginatedList
    {
        return $this->repository->findPage($page, $perPage);
    }

    public function get(int $id): ?Supplier
    {
        return $this->repository->findById($id);
    }

    /**
     * @return list<Supplier>
     */
    public function listAll(): array
    {
        return $this->repository->listAll();
    }

    public function create(SupplierInput $input, int $userId): Supplier
    {
        $input = $this->normalize($input);
        $this->assert($input);

        if ($this->repository->nameExists($input->supplierName)) {
            throw new ValidationException(esc_html('A supplier with this name already exists.'));
        }

        return $this->repository->create($input, $userId);
    }

    public function update(int $id, SupplierInput $input, int $userId): Supplier
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('Supplier not found.'));
        }

        $input = $this->normalize($input);
        $this->assert($input);

        if ($this->repository->nameExists($input->supplierName, $id)) {
            throw new ValidationException(esc_html('A supplier with this name already exists.'));
        }

        return $this->repository->update($id, $input, $userId);
    }

    public function delete(int $id): void
    {
        $existing = $this->repository->findById($id);

        if ($existing === null) {
            throw new ValidationException(esc_html('Supplier not found.'));
        }

        $this->repository->delete($id);
    }

    /**
     * @param list<int> $ids
     */
    public function bulkDelete(array $ids, int $actorUserId): BulkDeleteResult
    {
        unset($actorUserId);

        return BulkDeleteExecutor::execute($ids, fn (int $id) => $this->delete($id));
    }

    /**
     * @param array<string, mixed> $raw
     */
    public function inputFromArray(array $raw): SupplierInput
    {
        return new SupplierInput(
            supplierName: (string) ($raw['supplier_name'] ?? ''),
            contactPerson: isset($raw['contact_person']) ? (string) $raw['contact_person'] : null,
            email: isset($raw['email']) ? (string) $raw['email'] : null,
            phone: isset($raw['phone']) ? (string) $raw['phone'] : null,
            address: isset($raw['address']) ? (string) $raw['address'] : null,
            city: isset($raw['city']) ? (string) $raw['city'] : null,
            state: isset($raw['state']) ? (string) $raw['state'] : null,
            country: isset($raw['country']) ? (string) $raw['country'] : null,
            postalCode: isset($raw['postal_code']) ? (string) $raw['postal_code'] : null,
            website: isset($raw['website']) ? (string) $raw['website'] : null,
            paymentTerms: isset($raw['payment_terms']) ? (string) $raw['payment_terms'] : null,
            status: (string) ($raw['status'] ?? Supplier::STATUS_ACTIVE),
            notes: isset($raw['notes']) ? (string) $raw['notes'] : null,
        );
    }

    private function normalize(SupplierInput $input): SupplierInput
    {
        return new SupplierInput(
            supplierName: trim($input->supplierName),
            contactPerson: $this->trimNullable($input->contactPerson),
            email: $this->trimNullable($input->email),
            phone: $this->trimNullable($input->phone),
            address: $this->trimNullable($input->address),
            city: $this->trimNullable($input->city),
            state: $this->trimNullable($input->state),
            country: $this->trimNullable($input->country),
            postalCode: $this->trimNullable($input->postalCode),
            website: $this->trimNullable($input->website),
            paymentTerms: $this->trimNullable($input->paymentTerms),
            status: strtoupper(trim($input->status)),
            notes: $this->trimNullable($input->notes),
        );
    }

    private function trimNullable(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function assert(SupplierInput $input): void
    {
        if ($input->supplierName === '') {
            throw new ValidationException(esc_html('Supplier name is required.'));
        }

        $this->assertMaxLength('Supplier name', $input->supplierName, self::SUPPLIER_NAME_MAX);
        $this->assertMaxLength('Contact person', $input->contactPerson, self::CONTACT_PERSON_MAX);
        $this->assertMaxLength('Email', $input->email, self::EMAIL_MAX);
        $this->assertMaxLength('Phone', $input->phone, self::PHONE_MAX);
        $this->assertMaxLength('City', $input->city, self::CITY_MAX);
        $this->assertMaxLength('State', $input->state, self::STATE_MAX);
        $this->assertMaxLength('Country', $input->country, self::COUNTRY_MAX);
        $this->assertMaxLength('Postal code', $input->postalCode, self::POSTAL_CODE_MAX);
        $this->assertMaxLength('Website', $input->website, self::WEBSITE_MAX);

        if ($input->email !== null && ! filter_var($input->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(esc_html('Email address is not valid.'));
        }

        if (! in_array($input->status, [Supplier::STATUS_ACTIVE, Supplier::STATUS_INACTIVE], true)) {
            throw new ValidationException(esc_html('Invalid supplier status.'));
        }
    }

    private function assertMaxLength(string $label, ?string $value, int $max): void
    {
        if ($value !== null && strlen($value) > $max) {
            throw new ValidationException( esc_html( $label . ' must be at most ' . $max . ' characters.' ) );
        }
    }
}

