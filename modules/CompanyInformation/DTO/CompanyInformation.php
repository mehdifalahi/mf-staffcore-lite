<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\CompanyInformation\DTO;

final class CompanyInformation
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly ?string $city,
        public readonly ?string $province,
        public readonly ?string $country,
        public readonly ?string $website,
        public readonly ?string $currency,
        public readonly ?string $timezone,
        public readonly ?string $logo,
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
            name: (string) ($row['name'] ?? ''),
            email: self::nullableString($row['email'] ?? null),
            phone: self::nullableString($row['phone'] ?? null),
            address: self::nullableString($row['address'] ?? null),
            city: self::nullableString($row['city'] ?? null),
            province: self::nullableString($row['province'] ?? null),
            country: self::nullableString($row['country'] ?? null),
            website: self::nullableString($row['website'] ?? null),
            currency: self::nullableString($row['currency'] ?? null),
            timezone: self::nullableString($row['timezone'] ?? null),
            logo: self::nullableString($row['logo'] ?? null),
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'country' => $this->country,
            'website' => $this->website,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'logo' => $this->logo,
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
