<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\CompanyInformation\DTO;

final class CompanyInformationInput
{
    public function __construct(
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
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabaseRow(): array
    {
        return [
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
        ];
    }
}
