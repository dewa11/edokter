<?php

declare(strict_types=1);

namespace App\Models;

use Flight;
use flight\database\PdoWrapper;

final class JadwalModel
{
    private ?PdoWrapper $db;

    public function __construct()
    {
        $db = Flight::has('db') ? Flight::get('db') : null;
        $this->db = $db instanceof PdoWrapper ? $db : null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getWeeklyScheduleByDoctor(string $doctorId): array
    {
        if ($this->db === null || trim($doctorId) === '') {
            return [];
        }

        $sql = "
            SELECT
                j.hari_kerja,
                j.jam_mulai,
                j.jam_selesai,
                j.kd_poli,
                COALESCE(pl.nm_poli, '-') AS nm_poli,
                COALESCE(j.kuota, 0) AS kuota
            FROM jadwal j
            LEFT JOIN poliklinik pl ON pl.kd_poli = j.kd_poli
            WHERE j.kd_dokter = ?
            ORDER BY
                FIELD(j.hari_kerja, 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU', 'AKHAD'),
                j.jam_mulai ASC
        ";

        return $this->db->fetchAll($sql, [trim($doctorId)]);
    }
}
