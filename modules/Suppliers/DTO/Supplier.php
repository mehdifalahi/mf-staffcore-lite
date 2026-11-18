<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Suppliers\DTO;

final class Supplier
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    public function __construct(
        public readonly int $id,
        public readonly string $supplierName,
        public readonly ?string $contactPerson,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly ?string $city,
        public readonly ?string $state,
        public readonly ?string $country,
        public readonly ?string $postalCode,
        public readonly ?string $website,
        public readonly ?string $paymentTerms,
        public readonly string $status,
        public readonly ?string $notes,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?int $createdBy,
        public readonly ?int $updatedBy,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id: (int) ($row['id'] ?? 0),
            supplierName: (string) ($row['supplier_name'] ?? ''),
            contactPerson: self::nullableString($row['contact_person'] ?? null),
            email: self::nullableString($row['email'] ?? null),
            phone: self::nullableString($row['phone'] ?? null),
            address: self::nullableString($row['address'] ?? null),
            city: self::nullableString($row['city'] ?? null),
            state: self::nullableString($row['state'] ?? null),
            country: self::nullableString($row['country'] ?? null),
            postalCode: self::nullableString($row['postal_code'] ?? null),
            website: self::nullableString($row['website'] ?? null),
            paymentTerms: self::nullableString($row['payment_terms'] ?? null),
            status: (string) ($row['status'] ?? self::STATUS_ACTIVE),
            notes: self::nullableString($row['notes'] ?? null),
            createdAt: (string) ($row['created_at'] ?? ''),
            updatedAt: (string) ($row['updated_at'] ?? ''),
            createdBy: isset($row['created_by']) ? (int) $row['created_by'] : null,
            updatedBy: isset($row['updated_by']) ? (int) $row['updated_by'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'supplier_name' => $this->supplierName,
            'contact_person' => $this->contactPerson,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postalCode,
            'website' => $this->website,
            'payment_terms' => $this->paymentTerms,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
