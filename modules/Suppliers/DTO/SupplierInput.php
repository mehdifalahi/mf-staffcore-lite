<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\Suppliers\DTO;

final class SupplierInput
{
    public function __construct(
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
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabaseRow(): array
    {
        return [
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
        ];
    }
}
