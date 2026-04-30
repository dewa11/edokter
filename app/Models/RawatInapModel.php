<?php

declare(strict_types=1);

namespace App\Models;

use Flight;
use flight\database\PdoWrapper;
use flight\util\Collection;

final class RawatInapModel
{
    /** @var array<int,string> */
    private const RESUME_COLUMNS = [
        'no_rawat',
        'kd_dokter',
        'diagnosa_awal',
        'alasan',
        'keluhan_utama',
        'pemeriksaan_fisik',
        'jalannya_penyakit',
        'pemeriksaan_penunjang',
        'hasil_laborat',
        'tindakan_dan_operasi',
        'obat_di_rs',
        'diagnosa_utama',
        'kd_diagnosa_utama',
        'diagnosa_sekunder',
        'kd_diagnosa_sekunder',
        'diagnosa_sekunder2',
        'kd_diagnosa_sekunder2',
        'diagnosa_sekunder3',
        'kd_diagnosa_sekunder3',
        'diagnosa_sekunder4',
        'kd_diagnosa_sekunder4',
        'prosedur_utama',
        'kd_prosedur_utama',
        'prosedur_sekunder',
        'kd_prosedur_sekunder',
        'prosedur_sekunder2',
        'kd_prosedur_sekunder2',
        'prosedur_sekunder3',
        'kd_prosedur_sekunder3',
        'alergi',
        'diet',
        'lab_belum',
        'edukasi',
        'cara_keluar',
        'ket_keluar',
        'keadaan',
        'ket_keadaan',
        'dilanjutkan',
        'ket_dilanjutkan',
        'kontrol',
        'obat_pulang',
    ];

    /** @var array<int,string> */
    private const RESUME_NULLABLE_COLUMNS = [
        'ket_keluar',
        'ket_keadaan',
        'ket_dilanjutkan',
        'kontrol',
    ];

    private ?PdoWrapper $db;

    public function __construct()
    {
        $db = Flight::has('db') ? Flight::get('db') : null;
        $this->db = $db instanceof PdoWrapper ? $db : null;
    }

    /**
     * @param array{
     *   search:string,
     *   dateMode:string,
     *   dateFrom:string,
     *   dateTo:string,
    *   resumeStatus:string,
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
            SELECT DISTINCT
                ki.no_rawat,
                rp.no_rkm_medis,
                p.nm_pasien,
                p.tgl_lahir,
                CONCAT(ki.kd_kamar, ' ', b.nm_bangsal) AS kamar,
                ki.tgl_masuk,
                IF(ki.tgl_keluar = '0000-00-00', '', ki.tgl_keluar) AS tgl_keluar,
                ki.stts_pulang,
                pj.png_jawab AS penjamin,
                COALESCE(dp.dpjp, '') AS dpjp,
                CASE WHEN rr.no_rawat IS NULL THEN '0' ELSE '1' END AS has_resume_pasien_ranap
            FROM kamar_inap ki
            LEFT JOIN kamar_inap ki_newer
                ON ki_newer.no_rawat = ki.no_rawat
                AND (
                    ki_newer.tgl_masuk > ki.tgl_masuk
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk > ki.jam_masuk)
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk = ki.jam_masuk AND ki_newer.kd_kamar > ki.kd_kamar)
                )
            INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
            INNER JOIN dpjp_ranap drp_filter ON drp_filter.no_rawat = rp.no_rawat
            INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar
            INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
            INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
            LEFT JOIN (
                SELECT
                    dr.no_rawat,
                    GROUP_CONCAT(DISTINCT d.nm_dokter ORDER BY d.nm_dokter SEPARATOR ', ') AS dpjp
                FROM dpjp_ranap dr
                INNER JOIN dokter d ON d.kd_dokter = dr.kd_dokter
                GROUP BY dr.no_rawat
            ) dp ON dp.no_rawat = rp.no_rawat
            LEFT JOIN (
                SELECT DISTINCT rpr.no_rawat
                FROM resume_pasien_ranap rpr
            ) rr ON rr.no_rawat = rp.no_rawat
            {$where['sql']}
            ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC, ki.no_rawat DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        return $this->db->fetchAll($sql, $where['params']);
    }

    /**
     * @param array{
     *   search:string,
     *   dateMode:string,
     *   dateFrom:string,
     *   dateTo:string,
    *   resumeStatus:string,
     *   doctorId:?string,
     *   forceEmpty:bool
     * } $filters
     */
    public function countPatients(array $filters): int
    {
        if ($this->db === null) {
            return 0;
        }

        $where = $this->buildWhereClause($filters);

        $sql = "
            SELECT COUNT(DISTINCT ki.no_rawat) AS total
            FROM kamar_inap ki
            LEFT JOIN kamar_inap ki_newer
                ON ki_newer.no_rawat = ki.no_rawat
                AND (
                    ki_newer.tgl_masuk > ki.tgl_masuk
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk > ki.jam_masuk)
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk = ki.jam_masuk AND ki_newer.kd_kamar > ki.kd_kamar)
                )
            INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
            INNER JOIN dpjp_ranap drp_filter ON drp_filter.no_rawat = rp.no_rawat
            INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar
            INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
            INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
            LEFT JOIN (
                SELECT DISTINCT rpr.no_rawat
                FROM resume_pasien_ranap rpr
            ) rr ON rr.no_rawat = rp.no_rawat
            {$where['sql']}
        ";

        return (int) $this->db->fetchField($sql, $where['params']);
    }

    public function countActivePatients(?string $doctorId, bool $forceEmpty = false): int
    {
        if ($this->db === null) {
            return 0;
        }

        $where = $this->buildDashboardWhereClause($doctorId, $forceEmpty, null);

        $sql = "
            SELECT COUNT(DISTINCT ki.no_rawat) AS total
            FROM kamar_inap ki
            LEFT JOIN kamar_inap ki_newer
                ON ki_newer.no_rawat = ki.no_rawat
                AND (
                    ki_newer.tgl_masuk > ki.tgl_masuk
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk > ki.jam_masuk)
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk = ki.jam_masuk AND ki_newer.kd_kamar > ki.kd_kamar)
                )
            INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
            INNER JOIN dpjp_ranap drp_filter ON drp_filter.no_rawat = rp.no_rawat
            {$where['sql']}
        ";

        return (int) $this->db->fetchField($sql, $where['params']);
    }

    public function countTodayAdmissions(?string $doctorId, bool $forceEmpty = false): int
    {
        if ($this->db === null) {
            return 0;
        }

        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $where = $this->buildDashboardWhereClause($doctorId, $forceEmpty, $today);

        $sql = "
            SELECT COUNT(DISTINCT ki.no_rawat) AS total
            FROM kamar_inap ki
            LEFT JOIN kamar_inap ki_newer
                ON ki_newer.no_rawat = ki.no_rawat
                AND (
                    ki_newer.tgl_masuk > ki.tgl_masuk
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk > ki.jam_masuk)
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk = ki.jam_masuk AND ki_newer.kd_kamar > ki.kd_kamar)
                )
            INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
            INNER JOIN dpjp_ranap drp_filter ON drp_filter.no_rawat = rp.no_rawat
            {$where['sql']}
        ";

        return (int) $this->db->fetchField($sql, $where['params']);
    }

    /**
     * @return array<int,array{kd_dokter:string,nm_dokter:string}>
     */
    public function getDoctorOptions(): array
    {
        if ($this->db === null) {
            return [];
        }

        $sql = "
            SELECT DISTINCT
                d.kd_dokter,
                d.nm_dokter
            FROM dpjp_ranap dr
            INNER JOIN dokter d ON d.kd_dokter = dr.kd_dokter
            ORDER BY d.nm_dokter ASC
        ";

        $rows = $this->db->fetchAll($sql);
        $result = [];
        foreach ($rows as $row) {
            $item = $this->rowToArray($row);
            $kdDokter = trim((string) ($item['kd_dokter'] ?? ''));
            if ($kdDokter === '') {
                continue;
            }

            $result[] = [
                'kd_dokter' => $kdDokter,
                'nm_dokter' => trim((string) ($item['nm_dokter'] ?? '')),
            ];
        }

        return $result;
    }

    /**
     * @return array<int,array<string,string>>
     */
    public function getDischargedWithoutResume(?string $doctorId, bool $forceEmpty = false, int $limit = 20): array
    {
        if ($this->db === null) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $doctorId = trim((string) $doctorId);
        $clauses = [
            'ki_newer.no_rawat IS NULL',
            "ki.tgl_keluar <> '0000-00-00'",
            "ki.jam_keluar <> '00:00:00'",
            'rr.no_rawat IS NULL',
        ];
        $params = [];

        if ($forceEmpty) {
            $clauses[] = '1 = 0';
        } elseif ($doctorId !== '') {
            $clauses[] = 'TRIM(drp_filter.kd_dokter) = ?';
            $params[] = $doctorId;
        }

        $sql = "
            SELECT DISTINCT
                ki.no_rawat,
                rp.no_rkm_medis,
                p.nm_pasien,
                COALESCE(dp.dpjp, '') AS dpjp,
                COALESCE(ki.tgl_keluar, '') AS tgl_keluar,
                COALESCE(ki.jam_keluar, '') AS jam_keluar
            FROM kamar_inap ki
            LEFT JOIN kamar_inap ki_newer
                ON ki_newer.no_rawat = ki.no_rawat
                AND (
                    ki_newer.tgl_masuk > ki.tgl_masuk
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk > ki.jam_masuk)
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk = ki.jam_masuk AND ki_newer.kd_kamar > ki.kd_kamar)
                )
            INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
            INNER JOIN dpjp_ranap drp_filter ON drp_filter.no_rawat = rp.no_rawat
            INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            LEFT JOIN (
                SELECT
                    dr.no_rawat,
                    GROUP_CONCAT(DISTINCT d.nm_dokter ORDER BY d.nm_dokter SEPARATOR ', ') AS dpjp
                FROM dpjp_ranap dr
                INNER JOIN dokter d ON d.kd_dokter = dr.kd_dokter
                GROUP BY dr.no_rawat
            ) dp ON dp.no_rawat = rp.no_rawat
            LEFT JOIN (
                SELECT DISTINCT rpr.no_rawat
                FROM resume_pasien_ranap rpr
            ) rr ON rr.no_rawat = rp.no_rawat
            WHERE " . implode(' AND ', $clauses) . "
            ORDER BY ki.tgl_keluar DESC, ki.jam_keluar DESC, ki.no_rawat DESC
            LIMIT {$limit}
        ";

        return $this->db->fetchAll($sql, $params);
    }

    public function countDischargedWithoutResume(?string $doctorId, bool $forceEmpty = false): int
    {
        if ($this->db === null) {
            return 0;
        }

        $doctorId = trim((string) $doctorId);
        $clauses = [
            'ki_newer.no_rawat IS NULL',
            "ki.tgl_keluar <> '0000-00-00'",
            "ki.jam_keluar <> '00:00:00'",
            'rr.no_rawat IS NULL',
        ];
        $params = [];

        if ($forceEmpty) {
            $clauses[] = '1 = 0';
        } elseif ($doctorId !== '') {
            $clauses[] = 'TRIM(drp_filter.kd_dokter) = ?';
            $params[] = $doctorId;
        }

        $sql = "
            SELECT COUNT(DISTINCT ki.no_rawat) AS total
            FROM kamar_inap ki
            LEFT JOIN kamar_inap ki_newer
                ON ki_newer.no_rawat = ki.no_rawat
                AND (
                    ki_newer.tgl_masuk > ki.tgl_masuk
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk > ki.jam_masuk)
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk = ki.jam_masuk AND ki_newer.kd_kamar > ki.kd_kamar)
                )
            INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
            INNER JOIN dpjp_ranap drp_filter ON drp_filter.no_rawat = rp.no_rawat
            LEFT JOIN (
                SELECT DISTINCT rpr.no_rawat
                FROM resume_pasien_ranap rpr
            ) rr ON rr.no_rawat = rp.no_rawat
            WHERE " . implode(' AND ', $clauses) . "
        ";

        return (int) $this->db->fetchField($sql, $params);
    }

    /**
     * @return array<int,array<string,string>>
     */
    public function getRecentResumeEntries(?string $doctorId, bool $forceEmpty = false, int $limit = 20): array
    {
        if ($this->db === null) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $doctorId = trim((string) $doctorId);
        $clauses = ['1 = 1'];
        $params = [];

        if ($forceEmpty) {
            $clauses[] = '1 = 0';
        } elseif ($doctorId !== '') {
            $clauses[] = 'TRIM(rr.kd_dokter) = ?';
            $params[] = $doctorId;
        }

        $sql = "
            SELECT
                rr.no_rawat,
                COALESCE(rp.no_rkm_medis, '') AS no_rkm_medis,
                COALESCE(p.nm_pasien, '') AS nm_pasien,
                COALESCE(rr.kd_dokter, '') AS kd_dokter,
                COALESCE(d.nm_dokter, '') AS nm_dokter
            FROM resume_pasien_ranap rr
            LEFT JOIN reg_periksa rp ON rp.no_rawat = rr.no_rawat
            LEFT JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
            LEFT JOIN dokter d ON d.kd_dokter = rr.kd_dokter
            WHERE " . implode(' AND ', $clauses) . "
            ORDER BY rr.no_rawat DESC
            LIMIT {$limit}
        ";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * @return array<int,array<string,string>>
     */
    public function getTodayRecentAdmissions(?string $doctorId, bool $forceEmpty = false, int $limit = 10): array
    {
        if ($this->db === null) {
            return [];
        }

        $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $where = $this->buildDashboardWhereClause($doctorId, $forceEmpty, $today);
        $limit = max(1, min(50, $limit));

        $sql = "
            SELECT DISTINCT
                ki.no_rawat,
                rp.no_rkm_medis,
                p.nm_pasien,
                COALESCE(dp.dpjp, '') AS dpjp,
                CONCAT(ki.kd_kamar, ' ', b.nm_bangsal) AS kamar,
                ki.tgl_masuk,
                ki.jam_masuk
            FROM kamar_inap ki
            LEFT JOIN kamar_inap ki_newer
                ON ki_newer.no_rawat = ki.no_rawat
                AND (
                    ki_newer.tgl_masuk > ki.tgl_masuk
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk > ki.jam_masuk)
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk = ki.jam_masuk AND ki_newer.kd_kamar > ki.kd_kamar)
                )
            INNER JOIN reg_periksa rp ON ki.no_rawat = rp.no_rawat
            INNER JOIN dpjp_ranap drp_filter ON drp_filter.no_rawat = rp.no_rawat
            INNER JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar
            INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
            LEFT JOIN (
                SELECT
                    dr.no_rawat,
                    GROUP_CONCAT(DISTINCT d.nm_dokter ORDER BY d.nm_dokter SEPARATOR ', ') AS dpjp
                FROM dpjp_ranap dr
                INNER JOIN dokter d ON d.kd_dokter = dr.kd_dokter
                GROUP BY dr.no_rawat
            ) dp ON dp.no_rawat = rp.no_rawat
            {$where['sql']}
            ORDER BY ki.tgl_masuk DESC, ki.jam_masuk DESC, ki.no_rawat DESC
            LIMIT {$limit}
        ";

        return $this->db->fetchAll($sql, $where['params']);
    }

    /**
     * @return array<string,string>|null
     */
    public function getResumeContext(string $noRawat, ?string $doctorId = null): ?array
    {
        if ($this->db === null || trim($noRawat) === '') {
            return null;
        }

        $params = [trim($noRawat)];
        $doctorClause = '';
        $doctorId = trim((string) $doctorId);
        if ($doctorId !== '') {
            $doctorClause = ' AND EXISTS (SELECT 1 FROM dpjp_ranap drf WHERE drf.no_rawat = rp.no_rawat AND TRIM(drf.kd_dokter) = ?)';
            $params[] = $doctorId;
        }

        $sql = "
            SELECT
                rp.no_rawat,
                rp.no_rkm_medis,
                p.nm_pasien,
                COALESCE(dp.dpjp, '') AS dpjp,
                COALESCE(dp.kd_dpjp, '') AS kd_dpjp,
                COALESCE(ki.tgl_masuk, '') AS tgl_masuk,
                IF(COALESCE(ki.tgl_keluar, '') = '0000-00-00', '', COALESCE(ki.tgl_keluar, '')) AS tgl_keluar,
                COALESCE(ki.stts_pulang, '-') AS stts_pulang,
                COALESCE(pj.png_jawab, '') AS penjamin
            FROM reg_periksa rp
            INNER JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
            LEFT JOIN penjab pj ON pj.kd_pj = rp.kd_pj
            LEFT JOIN kamar_inap ki ON ki.no_rawat = rp.no_rawat
            LEFT JOIN kamar_inap ki_newer
                ON ki_newer.no_rawat = ki.no_rawat
                AND (
                    ki_newer.tgl_masuk > ki.tgl_masuk
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk > ki.jam_masuk)
                    OR (ki_newer.tgl_masuk = ki.tgl_masuk AND ki_newer.jam_masuk = ki.jam_masuk AND ki_newer.kd_kamar > ki.kd_kamar)
                )
            LEFT JOIN (
                SELECT
                    dr.no_rawat,
                    GROUP_CONCAT(DISTINCT d.nm_dokter ORDER BY d.nm_dokter SEPARATOR ', ') AS dpjp,
                    MIN(dr.kd_dokter) AS kd_dpjp
                FROM dpjp_ranap dr
                INNER JOIN dokter d ON d.kd_dokter = dr.kd_dokter
                GROUP BY dr.no_rawat
            ) dp ON dp.no_rawat = rp.no_rawat
            WHERE rp.no_rawat = ?
              AND (ki.no_rawat IS NULL OR ki_newer.no_rawat IS NULL)
              {$doctorClause}
            LIMIT 1
        ";

        $row = $this->db->fetchRow($sql, $params);
        $data = $this->rowToArray($row);
        if ($data === []) {
            return null;
        }

        return $data;
    }

    /**
     * @return array<string,string>
     */
    public function getResumeByNoRawat(string $noRawat): array
    {
        if ($this->db === null || trim($noRawat) === '') {
            return [];
        }

        $sql = 'SELECT ' . implode(', ', self::RESUME_COLUMNS) . ' FROM resume_pasien_ranap WHERE no_rawat = ? LIMIT 1';
        $row = $this->db->fetchRow($sql, [trim($noRawat)]);

        $data = $this->rowToArray($row);
        if ($data === []) {
            return [];
        }

        $result = [];
        foreach (self::RESUME_COLUMNS as $column) {
            $result[$column] = isset($data[$column]) ? (string) $data[$column] : '';
        }

        return $result;
    }

    /**
     * Build Java-like default resume values from diagnosa/prosedur priority tables.
     *
     * @return array<string,string>
     */
    public function getResumeAutoPrefill(string $noRawat): array
    {
        if ($this->db === null || trim($noRawat) === '') {
            return [];
        }

        $prefill = [];
        foreach (self::RESUME_COLUMNS as $column) {
            if ($column !== 'no_rawat' && $column !== 'kd_dokter') {
                $prefill[$column] = '';
            }
        }

        $diagnosaRows = $this->db->fetchAll(
            'SELECT dp.kd_penyakit, p.nm_penyakit, dp.prioritas
             FROM diagnosa_pasien dp
             INNER JOIN penyakit p ON p.kd_penyakit = dp.kd_penyakit
             WHERE dp.no_rawat = ? AND dp.status = ?
             ORDER BY dp.prioritas ASC',
            [trim($noRawat), 'Ranap']
        );

        foreach ($diagnosaRows as $row) {
            $item = $this->rowToArray($row);
            $prioritas = (int) ($item['prioritas'] ?? 0);
            $kode = trim((string) ($item['kd_penyakit'] ?? ''));
            $nama = trim((string) ($item['nm_penyakit'] ?? ''));

            if ($prioritas === 1) {
                $prefill['kd_diagnosa_utama'] = $kode;
                $prefill['diagnosa_utama'] = $nama;
            } elseif ($prioritas === 2) {
                $prefill['kd_diagnosa_sekunder'] = $kode;
                $prefill['diagnosa_sekunder'] = $nama;
            } elseif ($prioritas === 3) {
                $prefill['kd_diagnosa_sekunder2'] = $kode;
                $prefill['diagnosa_sekunder2'] = $nama;
            } elseif ($prioritas === 4) {
                $prefill['kd_diagnosa_sekunder3'] = $kode;
                $prefill['diagnosa_sekunder3'] = $nama;
            } elseif ($prioritas === 5) {
                $prefill['kd_diagnosa_sekunder4'] = $kode;
                $prefill['diagnosa_sekunder4'] = $nama;
            }
        }

        $prosedurRows = $this->db->fetchAll(
            'SELECT pp.kode, i.deskripsi_panjang, pp.prioritas
             FROM prosedur_pasien pp
             INNER JOIN icd9 i ON i.kode = pp.kode
             WHERE pp.no_rawat = ?
             ORDER BY pp.prioritas ASC',
            [trim($noRawat)]
        );

        foreach ($prosedurRows as $row) {
            $item = $this->rowToArray($row);
            $prioritas = (int) ($item['prioritas'] ?? 0);
            $kode = trim((string) ($item['kode'] ?? ''));
            $nama = trim((string) ($item['deskripsi_panjang'] ?? ''));

            if ($prioritas === 1) {
                $prefill['kd_prosedur_utama'] = $kode;
                $prefill['prosedur_utama'] = $nama;
            } elseif ($prioritas === 2) {
                $prefill['kd_prosedur_sekunder'] = $kode;
                $prefill['prosedur_sekunder'] = $nama;
            } elseif ($prioritas === 3) {
                $prefill['kd_prosedur_sekunder2'] = $kode;
                $prefill['prosedur_sekunder2'] = $nama;
            } elseif ($prioritas === 4) {
                $prefill['kd_prosedur_sekunder3'] = $kode;
                $prefill['prosedur_sekunder3'] = $nama;
            }
        }

        return $prefill;
    }

    public function getDiagnosaAwalFromKamarInap(string $noRawat): string
    {
        if ($this->db === null || trim($noRawat) === '') {
            return '';
        }

        $sql = "
            SELECT COALESCE(ki.diagnosa_awal, '') AS diagnosa_awal
            FROM kamar_inap ki
            WHERE ki.no_rawat = ?
            ORDER BY ki.tgl_keluar DESC, ki.jam_keluar DESC
            LIMIT 1
        ";

        $value = $this->db->fetchField($sql, [trim($noRawat)]);

        return trim((string) $value);
    }

    /**
     * @return array<int,array<string,string>>
     */
    public function getKeluhanUtamaRanap(string $noRawat): array
    {
        if ($this->db === null || trim($noRawat) === '') {
            return [];
        }

        $sql = "
            SELECT pr.tgl_perawatan, pr.jam_rawat, pr.keluhan
            FROM pemeriksaan_ranap pr
            WHERE pr.no_rawat = ?
              AND COALESCE(pr.keluhan, '') <> ''
            ORDER BY pr.tgl_perawatan, pr.jam_rawat
        ";

        $rows = $this->db->fetchAll($sql, [trim($noRawat)]);
        $result = [];
        foreach ($rows as $row) {
            $item = $this->rowToArray($row);
            $result[] = [
                'tanggal' => trim((string) ($item['tgl_perawatan'] ?? '')),
                'jam' => trim((string) ($item['jam_rawat'] ?? '')),
                'isi' => trim((string) ($item['keluhan'] ?? '')),
            ];
        }

        return $result;
    }

    /**
     * @return array<int,array<string,string>>
     */
    public function getResumeHasilRadiologi(string $noRawat): array
    {
        if ($this->db === null || trim($noRawat) === '') {
            return [];
        }

        $sql = "
            SELECT hr.tgl_periksa, hr.jam, hr.hasil
            FROM hasil_radiologi hr
            WHERE hr.no_rawat = ?
              AND COALESCE(hr.hasil, '') <> ''
            ORDER BY hr.tgl_periksa, hr.jam
        ";

        $rows = $this->db->fetchAll($sql, [trim($noRawat)]);
        $result = [];
        foreach ($rows as $row) {
            $item = $this->rowToArray($row);
            $result[] = [
                'tanggal' => trim((string) ($item['tgl_periksa'] ?? '')),
                'jam' => trim((string) ($item['jam'] ?? '')),
                'isi' => trim((string) ($item['hasil'] ?? '')),
            ];
        }

        return $result;
    }

    /**
     * @return array<int,array<string,string>>
     */
    public function getResumePemeriksaanLabPk(string $noRawat): array
    {
        if ($this->db === null || trim($noRawat) === '') {
            return [];
        }

        $sql = "
            SELECT dpl.tgl_periksa, dpl.jam, COALESCE(tpl.pemeriksaan, '') AS pemeriksaan,
                   COALESCE(dpl.nilai, '') AS nilai, COALESCE(tpl.satuan, '') AS satuan
            FROM detail_periksa_lab dpl
            LEFT JOIN template_laboratorium tpl ON dpl.id_template = tpl.id_template
            LEFT JOIN periksa_lab pl
                ON pl.no_rawat = dpl.no_rawat
               AND pl.kd_jenis_prw = dpl.kd_jenis_prw
               AND pl.tgl_periksa = dpl.tgl_periksa
               AND pl.jam = dpl.jam
            WHERE dpl.no_rawat = ?
              AND UPPER(COALESCE(pl.kategori, '')) = 'PK'
            ORDER BY dpl.tgl_periksa, dpl.jam, tpl.urut
        ";

        $rows = $this->db->fetchAll($sql, [trim($noRawat)]);
        $result = [];
        foreach ($rows as $row) {
            $item = $this->rowToArray($row);
            $result[] = [
                'tanggal' => trim((string) ($item['tgl_periksa'] ?? '')),
                'jam' => trim((string) ($item['jam'] ?? '')),
                'pemeriksaan' => trim((string) ($item['pemeriksaan'] ?? '')),
                'nilai' => trim((string) ($item['nilai'] ?? '')),
                'satuan' => trim((string) ($item['satuan'] ?? '')),
            ];
        }

        return $result;
    }

    /**
     * @return array<int,array<string,string>>
     */
    public function getObatSelamaRanap(string $noRawat, string $keyword = ''): array
    {
        if ($this->db === null || trim($noRawat) === '') {
            return [];
        }

        $searchLike = '%' . trim($keyword) . '%';
        $sql = "
            SELECT dpo.tgl_perawatan, dpo.jam, db.nama_brng, dpo.jml, db.kode_sat,
                   dpo.no_rawat, dpo.kode_brng
            FROM detail_pemberian_obat dpo
            INNER JOIN databarang db ON dpo.kode_brng = db.kode_brng
            WHERE dpo.no_rawat = ?
              AND (dpo.tgl_perawatan LIKE ? OR db.nama_brng LIKE ?)
            ORDER BY dpo.tgl_perawatan, dpo.jam
        ";

        $rows = $this->db->fetchAll($sql, [trim($noRawat), $searchLike, $searchLike]);
        $result = [];
        foreach ($rows as $row) {
            $item = $this->rowToArray($row);
            $result[] = [
                'tanggal' => trim((string) ($item['tgl_perawatan'] ?? '')),
                'jam' => trim((string) ($item['jam'] ?? '')),
                'nama_brng' => trim((string) ($item['nama_brng'] ?? '')),
                'jumlah' => trim((string) ($item['jml'] ?? '')),
                'satuan' => trim((string) ($item['kode_sat'] ?? '')),
            ];
        }

        return $result;
    }

    /**
     * @return array<int,array<string,string>>
     */
    public function getObatPulang(string $noRawat, string $keyword = ''): array
    {
        if ($this->db === null || trim($noRawat) === '') {
            return [];
        }

        $searchLike = '%' . trim($keyword) . '%';
        $sql = "
            SELECT rp.tanggal, rp.jam, db.nama_brng, rp.jml_barang, rp.dosis
            FROM resep_pulang rp
            INNER JOIN databarang db ON db.kode_brng = rp.kode_brng
            WHERE rp.no_rawat = ?
              AND (rp.tanggal LIKE ? OR db.nama_brng LIKE ?)
            ORDER BY rp.tanggal, rp.jam
        ";

        $rows = $this->db->fetchAll($sql, [trim($noRawat), $searchLike, $searchLike]);
        $result = [];
        foreach ($rows as $row) {
            $item = $this->rowToArray($row);
            $result[] = [
                'tanggal' => trim((string) ($item['tanggal'] ?? '')),
                'jam' => trim((string) ($item['jam'] ?? '')),
                'nama_brng' => trim((string) ($item['nama_brng'] ?? '')),
                'jumlah' => trim((string) ($item['jml_barang'] ?? '')),
                'dosis' => trim((string) ($item['dosis'] ?? '')),
            ];
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function saveResume(array $payload): bool
    {
        if ($this->db === null) {
            return false;
        }

        $data = [];
        foreach (self::RESUME_COLUMNS as $column) {
            $rawValue = $payload[$column] ?? '';
            if (in_array($column, self::RESUME_NULLABLE_COLUMNS, true)) {
                if ($rawValue === null || trim((string) $rawValue) === '') {
                    $data[$column] = null;
                } else {
                    $data[$column] = trim((string) $rawValue);
                }
                continue;
            }

            $data[$column] = trim((string) $rawValue);
        }

        if ($data['no_rawat'] === '') {
            return false;
        }

        $exists = (int) $this->db->fetchField(
            'SELECT COUNT(*) AS total FROM resume_pasien_ranap WHERE no_rawat = ?',
            [$data['no_rawat']]
        ) > 0;

        if ($exists) {
            $setColumns = array_filter(self::RESUME_COLUMNS, static fn (string $column): bool => $column !== 'no_rawat');
            $setSql = implode(', ', array_map(static fn (string $column): string => $column . ' = ?', $setColumns));

            $params = [];
            foreach ($setColumns as $column) {
                $params[] = $data[$column];
            }
            $params[] = $data['no_rawat'];

            $this->db->runQuery(
                'UPDATE resume_pasien_ranap SET ' . $setSql . ' WHERE no_rawat = ?',
                $params
            );

            return true;
        }

        $columnsSql = implode(', ', self::RESUME_COLUMNS);
        $placeholders = implode(', ', array_fill(0, count(self::RESUME_COLUMNS), '?'));
        $params = [];
        foreach (self::RESUME_COLUMNS as $column) {
            $params[] = $data[$column];
        }

        $this->db->runQuery(
            'INSERT INTO resume_pasien_ranap (' . $columnsSql . ') VALUES (' . $placeholders . ')',
            $params
        );

        return true;
    }

    /**
     * @param array{
     *   search:string,
     *   dateMode:string,
     *   dateFrom:string,
     *   dateTo:string,
    *   resumeStatus:string,
     *   doctorId:?string,
     *   forceEmpty:bool
     * } $filters
     * @return array{sql:string,params:array<int,mixed>}
     */
    private function buildWhereClause(array $filters): array
    {
        $clauses = ['ki_newer.no_rawat IS NULL'];
        $params = [];

        if ((bool) $filters['forceEmpty'] === true) {
            $clauses[] = '1 = 0';
        } elseif ($filters['doctorId'] !== null && trim((string) $filters['doctorId']) !== '') {
            $clauses[] = 'TRIM(drp_filter.kd_dokter) = ?';
            $params[] = trim((string) $filters['doctorId']);
        }

        $search = trim((string) $filters['search']);
        if ($search !== '') {
            $searchLike = '%' . $search . '%';
            $clauses[] = '(p.nm_pasien LIKE ? OR rp.no_rkm_medis LIKE ?)';
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        $dateMode = (string) ($filters['dateMode'] ?? 'belum_pulang');
        if ($dateMode === 'tgl_masuk') {
            $clauses[] = 'ki.tgl_masuk BETWEEN ? AND ?';
            $params[] = (string) $filters['dateFrom'];
            $params[] = (string) $filters['dateTo'];
        } elseif ($dateMode === 'tgl_pulang') {
            $clauses[] = "ki.tgl_keluar <> '0000-00-00'";
            $clauses[] = 'ki.tgl_keluar BETWEEN ? AND ?';
            $params[] = (string) $filters['dateFrom'];
            $params[] = (string) $filters['dateTo'];
        } else {
            $clauses[] = "(ki.stts_pulang = '-' OR ki.tgl_keluar = '0000-00-00' OR ki.jam_keluar = '00:00:00')";
        }

        $resumeStatus = (string) ($filters['resumeStatus'] ?? 'all');
        if ($resumeStatus === 'sudah') {
            $clauses[] = 'rr.no_rawat IS NOT NULL';
        } elseif ($resumeStatus === 'belum') {
            $clauses[] = 'rr.no_rawat IS NULL';
        }

        return [
            'sql' => 'WHERE ' . implode(' AND ', $clauses),
            'params' => $params,
        ];
    }

    /**
     * @return array{sql:string,params:array<int,mixed>}
     */
    private function buildDashboardWhereClause(?string $doctorId, bool $forceEmpty, ?string $admissionDate): array
    {
        $clauses = [
            'ki_newer.no_rawat IS NULL',
            "(ki.stts_pulang = '-' OR ki.tgl_keluar = '0000-00-00' OR ki.jam_keluar = '00:00:00')",
        ];
        $params = [];

        if ($admissionDate !== null) {
            $clauses[] = 'ki.tgl_masuk = ?';
            $params[] = $admissionDate;
        }

        $doctorId = trim((string) $doctorId);
        if ($forceEmpty) {
            $clauses[] = '1 = 0';
        } elseif ($doctorId !== '') {
            $clauses[] = 'drp_filter.kd_dokter = ?';
            $params[] = $doctorId;
        }

        return [
            'sql' => 'WHERE ' . implode(' AND ', $clauses),
            'params' => $params,
        ];
    }

    /**
     * @param mixed $row
     * @return array<string,mixed>
     */
    private function rowToArray($row): array
    {
        if ($row instanceof Collection) {
            return $row->getData();
        }

        if (is_array($row)) {
            return $row;
        }

        return [];
    }
}
