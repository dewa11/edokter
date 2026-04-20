<?php

declare(strict_types=1);

namespace App\Models;

use Flight;
use flight\database\PdoWrapper;

final class PoliModel
{
    private ?PdoWrapper $db;

    public function __construct()
    {
        $db = Flight::has('db') ? Flight::get('db') : null;
        $this->db = $db instanceof PdoWrapper ? $db : null;
    }

    public function isItUser(string $username): bool
    {
        if ($this->db === null || $username === '') {
            return false;
        }

        $total = (int) $this->db->fetchField(
            'SELECT COUNT(*) AS total FROM admin WHERE usere = ?',
            [$username]
        );

        return $total > 0;
    }

    /**
     * @param array{
     *   search:string,
     *   dateFrom:string,
     *   dateTo:string,
     *   doctorId:?string,
     *   forceEmpty:bool,
     *   limit?:int,
     *   offset?:int
     * } $filters
     * @return array<int,mixed>
     */
    public function getPatients(array $filters): array
    {
        if ($this->db === null) {
            return [];
        }

        $where = $this->buildWhereClause($filters);
        $limit = max(1, (int) ($filters['limit'] ?? 10));
        $offset = max(0, (int) ($filters['offset'] ?? 0));

        $sql = "
            SELECT
                rp.no_reg,
                rp.no_rkm_medis,
                p.nm_pasien,
                rp.tgl_registrasi,
                p.jk,
                p.tgl_lahir,
                rp.stts,
                d.nm_dokter
            FROM reg_periksa rp
            INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
            LEFT JOIN dokter d ON d.kd_dokter = rp.kd_dokter
            {$where['sql']}
            ORDER BY rp.jam_reg ASC, rp.no_reg ASC
            LIMIT {$limit} OFFSET {$offset}
        ";

        return $this->db->fetchAll($sql, $where['params']);
    }

    /**
     * @param array{search:string,dateFrom:string,dateTo:string,doctorId:?string,forceEmpty:bool} $filters
     * @return array<int,mixed>
     */
    public function getPatientsForExport(array $filters): array
    {
        if ($this->db === null) {
            return [];
        }

        $where = $this->buildWhereClause($filters);

        $sql = "
            SELECT
                rp.no_reg,
                rp.no_rkm_medis,
                p.nm_pasien,
                rp.tgl_registrasi,
                p.jk,
                p.tgl_lahir,
                rp.stts,
                d.nm_dokter
            FROM reg_periksa rp
            INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
            LEFT JOIN dokter d ON d.kd_dokter = rp.kd_dokter
            {$where['sql']}
            ORDER BY rp.tgl_registrasi ASC, rp.jam_reg ASC, rp.no_reg ASC
        ";

        return $this->db->fetchAll($sql, $where['params']);
    }

    /**
     * @param array{search:string,dateFrom:string,dateTo:string,doctorId:?string,forceEmpty:bool} $filters
     */
    public function countPatients(array $filters): int
    {
        if ($this->db === null) {
            return 0;
        }

        $where = $this->buildWhereClause($filters);

        $sql = "
            SELECT COUNT(*) AS total
            FROM reg_periksa rp
            INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
            {$where['sql']}
        ";

        return (int) $this->db->fetchField($sql, $where['params']);
    }

    public function countTodayPatients(?string $doctorId, bool $forceEmpty = false): int
    {
        if ($this->db === null) {
            return 0;
        }

        $clauses = ['rp.tgl_registrasi = ?'];
        $params = [(new \DateTimeImmutable('today'))->format('Y-m-d')];

        $doctorId = trim((string) $doctorId);
        if ($forceEmpty) {
            $clauses[] = '1 = 0';
        } elseif ($doctorId !== '') {
            $clauses[] = 'rp.kd_dokter = ?';
            $params[] = $doctorId;
        }

        $sql = "
            SELECT COUNT(*) AS total
            FROM reg_periksa rp
            WHERE " . implode(' AND ', $clauses);

        return (int) $this->db->fetchField($sql, $params);
    }

    /**
     * @return array<int,array{kd_poli:string,nm_poli:string,total_pasien:int}>
     */
    public function getTodayPoliGroups(?string $doctorId, bool $forceEmpty = false): array
    {
        if ($this->db === null) {
            return [];
        }

        $clauses = ['rp.tgl_registrasi = ?'];
        $params = [(new \DateTimeImmutable('today'))->format('Y-m-d')];

        $doctorId = trim((string) $doctorId);
        if ($forceEmpty) {
            $clauses[] = '1 = 0';
        } elseif ($doctorId !== '') {
            $clauses[] = 'rp.kd_dokter = ?';
            $params[] = $doctorId;
        }

        $sql = "
            SELECT
                COALESCE(rp.kd_poli, '') AS kd_poli,
                COALESCE(pl.nm_poli, '-') AS nm_poli,
                COUNT(*) AS total_pasien
            FROM reg_periksa rp
            LEFT JOIN poliklinik pl ON pl.kd_poli = rp.kd_poli
            WHERE " . implode(' AND ', $clauses) . "
            GROUP BY rp.kd_poli, pl.nm_poli
            ORDER BY total_pasien DESC, nm_poli ASC
        ";

        $rows = $this->db->fetchAll($sql, $params);
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'kd_poli' => (string) ($row['kd_poli'] ?? ''),
                'nm_poli' => (string) ($row['nm_poli'] ?? '-'),
                'total_pasien' => (int) ($row['total_pasien'] ?? 0),
            ];
        }

        return $result;
    }

    /**
     * @param array{search:string,dateFrom:string,dateTo:string,doctorId:?string,forceEmpty:bool} $filters
     * @return array{sql:string,params:array<int,mixed>}
     */
    private function buildWhereClause(array $filters): array
    {
        $clauses = ['rp.tgl_registrasi BETWEEN ? AND ?'];
        $params = [(string) $filters['dateFrom'], (string) $filters['dateTo']];

        $search = trim((string) $filters['search']);
        if ($search !== '') {
            $searchLike = '%' . $search . '%';
            $clauses[] = '(rp.no_reg LIKE ? OR rp.no_rkm_medis LIKE ? OR p.nm_pasien LIKE ? OR rp.stts LIKE ?)';
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        if ((bool) $filters['forceEmpty'] === true) {
            $clauses[] = '1 = 0';
        } elseif ($filters['doctorId'] !== null) {
            $clauses[] = 'rp.kd_dokter = ?';
            $params[] = (string) $filters['doctorId'];
        }

        return [
            'sql' => 'WHERE ' . implode(' AND ', $clauses),
            'params' => $params,
        ];
    }
}
