<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\CompanyInformation\Services;

use MF\StaffCore\Modules\CompanyInformation\DTO\CompanyInformation;
use MF\StaffCore\Modules\CompanyInformation\DTO\CompanyInformationInput;
use MF\StaffCore\Modules\CompanyInformation\Repositories\CompanyInformationRepository;
use MF\StaffCore\Support\ValidationException;

final class CompanyInformationService
{
    private const NAME_MAX = 255;
    private const EMAIL_MAX = 255;
    private const PHONE_MAX = 20;
    private const CITY_MAX = 100;
    private const PROVINCE_MAX = 100;
    private const COUNTRY_MAX = 100;
    private const WEBSITE_MAX = 255;
    private const CURRENCY_MAX = 10;
    private const TIMEZONE_MAX = 100;
    private const LOGO_MAX = 500;
    private const ADDRESS_MAX = 65535;

    /** @var list<string> */
    private const CURRENCIES = [
        'USD',
        'EUR',
        'GBP',
        'IRR',
        'AED',
        'SAR',
        'TRY',
        'INR',
        'PKR',
    ];

    /** @var list<string> */
    private const TIMEZONES = [
        'UTC',
        'Asia/Tehran',
        'Asia/Dubai',
        'Asia/Kolkata',
        'Asia/Karachi',
        'Asia/Riyadh',
        'Asia/Istanbul',
        'Europe/London',
        'Europe/Berlin',
        'Europe/Paris',
        'America/New_York',
        'America/Chicago',
        'America/Los_Angeles',
    ];

    public function __construct(
        private readonly CompanyInformationRepository $repository,
    ) {
    }

    public function getProfile(): ?CompanyInformation
    {
        return $this->repository->findProfile();
    }

    public function save(CompanyInformationInput $input, int $userId): CompanyInformation
    {
        $input = $this->normalize($input);
        $this->assert($input);

        return $this->repository->save($input, $userId);
    }

    public function removeLogo(int $userId): CompanyInformation
    {
        $existing = $this->repository->findProfile();

        if ($existing === null) {
            throw new ValidationException(esc_html('Company information has not been saved yet.'));
        }

        return $this->repository->clearLogo($existing->id, $userId);
    }

    /**
     * @return list<string>
     */
    public function currencyOptions(): array
    {
        return self::CURRENCIES;
    }

    /**
     * @return list<string>
     */
    public function timezoneOptions(): array
    {
        return self::TIMEZONES;
    }

    /**
     * @param array<string, mixed> $raw
     */
    public function inputFromArray(array $raw): CompanyInformationInput
    {
        $email = isset($raw['email']) ? trim((string) $raw['email']) : '';

        return new CompanyInformationInput(
            name: (string) ($raw['name'] ?? ''),
            email: $email !== '' ? $email : null,
            phone: isset($raw['phone']) ? (string) $raw['phone'] : null,
            address: isset($raw['address']) ? (string) $raw['address'] : null,
            city: isset($raw['city']) ? (string) $raw['city'] : null,
            province: isset($raw['province']) ? (string) $raw['province'] : null,
            country: isset($raw['country']) ? (string) $raw['country'] : null,
            website: isset($raw['website']) ? (string) $raw['website'] : null,
            currency: isset($raw['currency']) ? (string) $raw['currency'] : null,
            timezone: isset($raw['timezone']) ? (string) $raw['timezone'] : null,
            logo: isset($raw['logo']) ? (string) $raw['logo'] : null,
        );
    }

    private function normalize(CompanyInformationInput $input): CompanyInformationInput
    {
        return new CompanyInformationInput(
            name: trim($input->name),
            email: $this->trimNullable($input->email),
            phone: $this->trimNullable($input->phone),
            address: $this->trimNullable($input->address),
            city: $this->trimNullable($input->city),
            province: $this->trimNullable($input->province),
            country: $this->trimNullable($input->country),
            website: $this->trimNullable($input->website),
            currency: $this->trimNullable($input->currency),
            timezone: $this->trimNullable($input->timezone),
            logo: $this->trimNullable($input->logo),
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

    private function assert(CompanyInformationInput $input): void
    {
        if ($input->name === '') {
            throw new ValidationException(esc_html('Company name is required.'));
        }

        $this->assertMaxLength('Company name', $input->name, self::NAME_MAX);

        if ($input->email !== null) {
            if (! filter_var($input->email, FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException(esc_html('Company email is invalid.'));
            }

            $this->assertMaxLength('Company email', $input->email, self::EMAIL_MAX);
        }

        $this->assertMaxLength('Phone', $input->phone, self::PHONE_MAX);
        $this->assertMaxLength('City', $input->city, self::CITY_MAX);
        $this->assertMaxLength('Province', $input->province, self::PROVINCE_MAX);
        $this->assertMaxLength('Country', $input->country, self::COUNTRY_MAX);
        $this->assertMaxLength('Website', $input->website, self::WEBSITE_MAX);
        $this->assertMaxLength('Currency', $input->currency, self::CURRENCY_MAX);
        $this->assertMaxLength('Timezone', $input->timezone, self::TIMEZONE_MAX);
        $this->assertMaxLength('Logo', $input->logo, self::LOGO_MAX);

        if ($input->address !== null && strlen($input->address) > self::ADDRESS_MAX) {
            throw new ValidationException(esc_html('Address is too long.'));
        }

        if ($input->website !== null && $input->website !== '') {
            $url = $input->website;
            if (! preg_match('#^https?://#i', $url)) {
                $url = 'https://' . $url;
            }

            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                throw new ValidationException(esc_html('Website URL is invalid.'));
            }
        }

        if ($input->currency !== null && ! in_array($input->currency, self::CURRENCIES, true)) {
            throw new ValidationException(esc_html('Invalid currency code.'));
        }

        if ($input->timezone !== null && $input->timezone !== '') {
            try {
                new \DateTimeZone($input->timezone);
            } catch (\Exception) {
                throw new ValidationException(esc_html('Invalid timezone.'));
            }
        }
    }

    private function assertMaxLength(string $label, ?string $value, int $max): void
    {
        if ($value !== null && strlen($value) > $max) {
            throw new ValidationException( esc_html( $label . ' must be at most ' . $max . ' characters.' ) );
        }
    }
}

