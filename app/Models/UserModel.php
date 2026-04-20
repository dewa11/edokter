<?php

declare(strict_types=1);

namespace App\Models;

use Flight;
use flight\database\PdoWrapper;

final class UserModel
{
    private ?PdoWrapper $db;

    public function __construct()
    {
        $db = Flight::has('db') ? Flight::get('db') : null;
        $this->db = $db instanceof PdoWrapper ? $db : null;
    }

    public function canLoginAsDoctor(string $username, string $password): bool
    {
        if ($this->db === null) {
            return false;
        }

        $userCount = (int) $this->db->fetchField(
            'SELECT COUNT(*) AS total FROM user WHERE user.id_user = AES_ENCRYPT(?, "nur") AND user.password = AES_ENCRYPT(?, "windi")',
            [$username, $password]
        );

        if ($userCount <= 0) {
            return false;
        }

        $doctorCount = (int) $this->db->fetchField(
            'SELECT COUNT(*) AS total FROM dokter WHERE dokter.kd_dokter = ?',
            [$username]
        );

        return $doctorCount > 0;
    }

    public function findDoctorNameByCode(string $doctorCode): string
    {
        if ($this->db === null || $doctorCode === '') {
            return '';
        }

        $name = $this->db->fetchField(
            'SELECT nm_dokter FROM dokter WHERE kd_dokter = ? LIMIT 1',
            [$doctorCode]
        );

        return is_string($name) ? trim($name) : '';
    }
}
