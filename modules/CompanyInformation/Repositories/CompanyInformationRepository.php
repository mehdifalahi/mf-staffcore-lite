<?php

declare(strict_types=1);

namespace MF\StaffCore\Modules\CompanyInformation\Repositories;

use MF\StaffCore\Database\IdSequence;
use MF\StaffCore\Database\TableGateway;
use MF\StaffCore\Modules\CompanyInformation\DTO\CompanyInformation;
use MF\StaffCore\Modules\CompanyInformation\DTO\CompanyInformationInput;
use RuntimeException;

final class CompanyInformationRepository
{
    private const TABLE = 'company_information';

    private const SELECT_COLUMNS = 'id, name, email, phone, address, city, province, country,
        website, currency, timezone, logo, created_at, updated_at, created_by, updated_by';

    public function __construct(
        private readonly TableGateway $db,
        private readonly IdSequence $idSequence,
    ) {
    }

    public function findProfile(): ?CompanyInformation
    {
        $table = $this->db->resolveTableName(self::TABLE);

        $row = $this->db->getRow(
            'SELECT ' . self::SELECT_COLUMNS . ' FROM `' . $table . '` ORDER BY id ASC LIMIT 1',
            [],
        );

        return $row !== null ? CompanyInformation::fromRow($row) : null;
    }

    public function save(CompanyInformationInput $input, int $userId): CompanyInformation
    {
        $existing = $this->findProfile();

        if ($existing !== null) {
            return $this->update($existing->id, $input, $userId);
        }

        return $this->create($input, $userId);
    }

    public function clearLogo(int $id, int $userId): CompanyInformation
    {
        $ok = $this->db->update(
            self::TABLE,
            [
                'logo' => null,
                'updated_at' => gmdate('Y-m-d H:i:s'),
                'updated_by' => $userId,
            ],
            ['id' => $id],
        );

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to remove company logo: ' . $this->db->getLastError()));
        }

        $profile = $this->findProfile();

        if ($profile === null) {
            throw new RuntimeException(esc_html('Company profile could not be loaded after logo removal.'));
        }

        return $profile;
    }

    private function create(CompanyInformationInput $input, int $userId): CompanyInformation
    {
        $id = $this->idSequence->nextId(self::TABLE);
        $now = gmdate('Y-m-d H:i:s');

        $data = array_merge($input->toDatabaseRow(), [
            'id' => $id,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $ok = $this->db->insert(self::TABLE, $data);

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to save company information: ' . $this->db->getLastError()));
        }

        $profile = $this->findProfile();

        if ($profile === null) {
            throw new RuntimeException(esc_html('Company information was saved but could not be loaded.'));
        }

        return $profile;
    }

    private function update(int $id, CompanyInformationInput $input, int $userId): CompanyInformation
    {
        $data = array_merge($input->toDatabaseRow(), [
            'updated_at' => gmdate('Y-m-d H:i:s'),
            'updated_by' => $userId,
        ]);

        $ok = $this->db->update(
            self::TABLE,
            $data,
            ['id' => $id],
        );

        if (! $ok) {
            throw new RuntimeException(esc_html('Failed to update company information: ' . $this->db->getLastError()));
        }

        $profile = $this->findProfile();

        if ($profile === null) {
            throw new RuntimeException(esc_html('Company information was updated but could not be loaded.'));
        }

        return $profile;
    }
}

