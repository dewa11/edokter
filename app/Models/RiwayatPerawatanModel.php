<?php

declare(strict_types=1);

namespace App\Models;

use Flight;
use flight\database\PdoWrapper;

final class RiwayatPerawatanModel
{
    private ?PdoWrapper $db;

    public function __construct()
    {
        $db = Flight::has('db') ? Flight::get('db') : null;
        $this->db = $db instanceof PdoWrapper ? $db : null;
    }

    public function isConnected(): bool
    {
        return $this->db !== null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getPatient(string $noRm): ?array
    {
        if ($this->db === null || $noRm === '') {
            return null;
        }

        $sql = "
            SELECT
                p.no_rkm_medis,
                p.nm_pasien,
                p.jk,
                p.tmp_lahir,
                p.tgl_lahir,
                p.agama,
                p.nm_ibu,
                p.stts_nikah,
                p.pnd,
                p.gol_darah,
                p.pekerjaan,
                bp.nama_bahasa,
                cf.nama_cacat,
                CONCAT(p.alamat, ', ', kel.nm_kel, ', ', kec.nm_kec, ', ', kab.nm_kab) AS alamat
            FROM pasien p
            INNER JOIN bahasa_pasien bp ON bp.id = p.bahasa_pasien
            INNER JOIN cacat_fisik cf ON cf.id = p.cacat_fisik
            INNER JOIN kelurahan kel ON p.kd_kel = kel.kd_kel
            INNER JOIN kecamatan kec ON p.kd_kec = kec.kd_kec
            INNER JOIN kabupaten kab ON p.kd_kab = kab.kd_kab
            WHERE p.no_rkm_medis = ?
            LIMIT 1
        ";

        $row = $this->db->fetchRow($sql, [$noRm]);

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getVisits(string $noRm, string $mode, string $tgl1, string $tgl2, string $noRawat): array
    {
        if ($this->db === null || $noRm === '') {
            return [];
        }

        $sql = "
            SELECT
                rp.no_reg,
                rp.no_rawat,
                rp.tgl_registrasi,
                rp.jam_reg,
                rp.kd_dokter,
                d.nm_dokter,
                poli.nm_poli,
                rp.p_jawab,
                rp.almt_pj,
                rp.hubunganpj,
                rp.biaya_reg,
                rp.status_lanjut,
                pj.png_jawab,
                rp.umurdaftar,
                rp.sttsumur
            FROM reg_periksa rp
            INNER JOIN dokter d ON rp.kd_dokter = d.kd_dokter
            INNER JOIN poliklinik poli ON rp.kd_poli = poli.kd_poli
            INNER JOIN penjab pj ON rp.kd_pj = pj.kd_pj
            WHERE rp.stts <> 'Batal'
              AND rp.no_rkm_medis = ?
        ";

        $params = [$noRm];

        if ($mode === 'r1') {
            $sql .= ' ORDER BY rp.tgl_registrasi DESC LIMIT 5';
            return $this->db->fetchAll($sql, $params);
        }

        if ($mode === 'r3' && $tgl1 !== '' && $tgl2 !== '') {
            $sql .= ' AND rp.tgl_registrasi BETWEEN ? AND ? ORDER BY rp.tgl_registrasi ASC, rp.jam_reg ASC';
            $params[] = $tgl1;
            $params[] = $tgl2;
            return $this->db->fetchAll($sql, $params);
        }

        if ($mode === 'r4' && $noRawat !== '') {
            $sql .= ' AND rp.no_rawat = ? ORDER BY rp.tgl_registrasi ASC, rp.jam_reg ASC';
            $params[] = $noRawat;
            return $this->db->fetchAll($sql, $params);
        }

        $sql .= ' ORDER BY rp.tgl_registrasi ASC, rp.jam_reg ASC';
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getRujukanInternal(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT poli.nm_poli, d.nm_dokter
            FROM rujukan_internal_poli rip
            INNER JOIN poliklinik poli ON rip.kd_poli = poli.kd_poli
            INNER JOIN dokter d ON rip.kd_dokter = d.kd_dokter
            WHERE rip.no_rawat = ?
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getDpjpRanap(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT drp.kd_dokter, d.nm_dokter
            FROM dpjp_ranap drp
            INNER JOIN dokter d ON drp.kd_dokter = d.kd_dokter
            WHERE drp.no_rawat = ?
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getDiagnosa(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT dp.kd_penyakit, py.nm_penyakit, dp.status
            FROM diagnosa_pasien dp
            INNER JOIN penyakit py ON dp.kd_penyakit = py.kd_penyakit
            WHERE dp.no_rawat = ?
            ORDER BY dp.prioritas
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getProsedur(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT pp.kode, i9.deskripsi_panjang, pp.status
            FROM prosedur_pasien pp
            INNER JOIN icd9 i9 ON pp.kode = i9.kode
            WHERE pp.no_rawat = ?
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getCatatanDokter(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT cp.tanggal, cp.jam, cp.kd_dokter, d.nm_dokter, cp.catatan
            FROM catatan_perawatan cp
            INNER JOIN dokter d ON cp.kd_dokter = d.kd_dokter
            WHERE cp.no_rawat = ?
            ORDER BY cp.tanggal, cp.jam
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getTindakanRalanDokter(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT rjd.kd_jenis_prw, jns.nm_perawatan, d.nm_dokter, rjd.biaya_rawat, rjd.tgl_perawatan, rjd.jam_rawat
            FROM rawat_jl_dr rjd
            INNER JOIN jns_perawatan jns ON rjd.kd_jenis_prw = jns.kd_jenis_prw
            INNER JOIN dokter d ON rjd.kd_dokter = d.kd_dokter
            WHERE rjd.no_rawat = ?
            ORDER BY rjd.tgl_perawatan, rjd.jam_rawat
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getTindakanRalanParamedis(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT rjp.kd_jenis_prw, jns.nm_perawatan, p.nama, rjp.biaya_rawat, rjp.tgl_perawatan, rjp.jam_rawat
            FROM rawat_jl_pr rjp
            INNER JOIN jns_perawatan jns ON rjp.kd_jenis_prw = jns.kd_jenis_prw
            INNER JOIN petugas p ON rjp.nip = p.nip
            WHERE rjp.no_rawat = ?
            ORDER BY rjp.tgl_perawatan, rjp.jam_rawat
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getTindakanRalanDokterParamedis(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT rjdp.kd_jenis_prw, jns.nm_perawatan, d.nm_dokter, p.nama, rjdp.biaya_rawat, rjdp.tgl_perawatan, rjdp.jam_rawat
            FROM rawat_jl_drpr rjdp
            INNER JOIN jns_perawatan jns ON rjdp.kd_jenis_prw = jns.kd_jenis_prw
            INNER JOIN dokter d ON rjdp.kd_dokter = d.kd_dokter
            INNER JOIN petugas p ON rjdp.nip = p.nip
            WHERE rjdp.no_rawat = ?
            ORDER BY rjdp.tgl_perawatan, rjdp.jam_rawat
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getTindakanRanapDokter(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT rid.kd_jenis_prw, jns.nm_perawatan, d.nm_dokter, rid.biaya_rawat, rid.tgl_perawatan, rid.jam_rawat
            FROM rawat_inap_dr rid
            INNER JOIN jns_perawatan_inap jns ON rid.kd_jenis_prw = jns.kd_jenis_prw
            INNER JOIN dokter d ON rid.kd_dokter = d.kd_dokter
            WHERE rid.no_rawat = ?
            ORDER BY rid.tgl_perawatan, rid.jam_rawat
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getTindakanRanapParamedis(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT rip.kd_jenis_prw, jns.nm_perawatan, p.nama, rip.biaya_rawat, rip.tgl_perawatan, rip.jam_rawat
            FROM rawat_inap_pr rip
            INNER JOIN jns_perawatan_inap jns ON rip.kd_jenis_prw = jns.kd_jenis_prw
            INNER JOIN petugas p ON rip.nip = p.nip
            WHERE rip.no_rawat = ?
            ORDER BY rip.tgl_perawatan, rip.jam_rawat
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getTindakanRanapDokterParamedis(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT ridp.kd_jenis_prw, jns.nm_perawatan, d.nm_dokter, p.nama, ridp.biaya_rawat, ridp.tgl_perawatan, ridp.jam_rawat
            FROM rawat_inap_drpr ridp
            INNER JOIN jns_perawatan_inap jns ON ridp.kd_jenis_prw = jns.kd_jenis_prw
            INNER JOIN dokter d ON ridp.kd_dokter = d.kd_dokter
            INNER JOIN petugas p ON ridp.nip = p.nip
            WHERE ridp.no_rawat = ?
            ORDER BY ridp.tgl_perawatan, ridp.jam_rawat
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getPemeriksaanRalan(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT pr.tgl_perawatan, pr.jam_rawat, pr.suhu_tubuh, pr.tensi, pr.nadi, pr.respirasi,
                   pr.tinggi, pr.berat, pr.gcs, pr.spo2, pr.kesadaran, pr.keluhan, pr.pemeriksaan,
                   pr.alergi, pr.lingkar_perut, pr.rtl, pr.penilaian, pr.instruksi, pr.evaluasi,
                   pr.nip, pg.nama, pg.jbtn
            FROM pemeriksaan_ralan pr
            INNER JOIN pegawai pg ON pr.nip = pg.nik
            WHERE pr.no_rawat = ?
            ORDER BY pr.tgl_perawatan, pr.jam_rawat
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getPemeriksaanRanap(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT pr.tgl_perawatan, pr.jam_rawat, pr.suhu_tubuh, pr.tensi, pr.nadi, pr.respirasi,
                   pr.tinggi, pr.berat, pr.gcs, pr.spo2, pr.kesadaran, pr.keluhan, pr.pemeriksaan,
                   pr.alergi, pr.rtl, pr.penilaian, pr.instruksi, pr.evaluasi, pr.nip, pg.nama, pg.jbtn
            FROM pemeriksaan_ranap pr
            INNER JOIN pegawai pg ON pr.nip = pg.nik
            WHERE pr.no_rawat = ?
            ORDER BY pr.tgl_perawatan, pr.jam_rawat
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getPenggunaanKamar(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT ki.kd_kamar, b.nm_bangsal, ki.tgl_masuk, ki.tgl_keluar, ki.stts_pulang,
                   ki.lama, ki.jam_masuk, ki.jam_keluar, ki.ttl_biaya
            FROM kamar_inap ki
            INNER JOIN kamar k ON ki.kd_kamar = k.kd_kamar
            INNER JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
            WHERE ki.no_rawat = ?
            ORDER BY ki.tgl_masuk, ki.jam_masuk
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getPemeriksaanRadiologi(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT
                pr.tgl_periksa,
                pr.jam,
                pr.kd_jenis_prw,
                jpr.nm_perawatan,
                p.nama,
                d.nm_dokter,
                pr.biaya,
                CONCAT(
                    IF(pr.proyeksi <> '', CONCAT('Proyeksi: ', pr.proyeksi, ', '), ''),
                    IF(pr.kV <> '', CONCAT('kV: ', pr.kV, ', '), ''),
                    IF(pr.mAS <> '', CONCAT('mAS: ', pr.mAS, ', '), ''),
                    IF(pr.FFD <> '', CONCAT('FFD: ', pr.FFD, ', '), ''),
                    IF(pr.BSF <> '', CONCAT('BSF: ', pr.BSF, ', '), ''),
                    IF(pr.inak <> '', CONCAT('Inak: ', pr.inak, ', '), ''),
                    IF(pr.jml_penyinaran <> '', CONCAT('Jml Penyinaran: ', pr.jml_penyinaran, ', '), ''),
                    IF(pr.dosis <> '', CONCAT('Dosis Radiasi: ', pr.dosis), '')
                ) AS parameter_radiologi
            FROM periksa_radiologi pr
            INNER JOIN jns_perawatan_radiologi jpr ON pr.kd_jenis_prw = jpr.kd_jenis_prw
            INNER JOIN petugas p ON pr.nip = p.nip
            INNER JOIN dokter d ON pr.kd_dokter = d.kd_dokter
            WHERE pr.no_rawat = ?
            ORDER BY pr.tgl_periksa, pr.jam
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getHasilRadiologi(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT hr.tgl_periksa, hr.jam, hr.hasil
            FROM hasil_radiologi hr
            WHERE hr.no_rawat = ?
            ORDER BY hr.tgl_periksa, hr.jam
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getGambarRadiologi(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT gr.tgl_periksa, gr.jam, gr.lokasi_gambar
            FROM gambar_radiologi gr
            WHERE gr.no_rawat = ?
            ORDER BY gr.tgl_periksa, gr.jam
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getPemeriksaanLaboratPk(string $noRawat): array
    {
        return $this->getPemeriksaanLaboratByKategori($noRawat, 'PK');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getPemeriksaanLaboratMb(string $noRawat): array
    {
        return $this->getPemeriksaanLaboratByKategori($noRawat, 'MB');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function getPemeriksaanLaboratByKategori(string $noRawat, string $kategori): array
    {
        if ($this->db === null || $noRawat === '' || $kategori === '') {
            return [];
        }

        $sql = "
            SELECT
                dpl.tgl_periksa,
                dpl.jam,
                dpl.kd_jenis_prw,
                jpl.nm_perawatan,
                COALESCE(p.nama, '') AS nama,
                COALESCE(d.nm_dokter, '') AS nm_dokter,
                COALESCE(pl.biaya, 0) AS biaya,
                tpl.pemeriksaan,
                dpl.nilai,
                tpl.satuan,
                dpl.nilai_rujukan,
                0 AS biaya_item,
                dpl.keterangan,
                tpl.urut
            FROM detail_periksa_lab dpl
            INNER JOIN jns_perawatan_lab jpl ON dpl.kd_jenis_prw = jpl.kd_jenis_prw
            LEFT JOIN periksa_lab pl
                ON pl.no_rawat = dpl.no_rawat
                AND pl.kd_jenis_prw = dpl.kd_jenis_prw
                AND pl.tgl_periksa = dpl.tgl_periksa
                AND pl.jam = dpl.jam
            LEFT JOIN petugas p ON pl.nip = p.nip
            LEFT JOIN dokter d ON pl.kd_dokter = d.kd_dokter
            LEFT JOIN template_laboratorium tpl ON dpl.id_template = tpl.id_template
            WHERE dpl.no_rawat = ?
              AND UPPER(pl.kategori) = UPPER(?)
            ORDER BY dpl.tgl_periksa, dpl.jam, dpl.kd_jenis_prw, tpl.urut
        ";

        return $this->db->fetchAll($sql, [$noRawat, $kategori]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getPemeriksaanLaboratPa(string $noRawat): array
    {
        if ($this->db === null || $noRawat === '') {
            return [];
        }

        $sql = "
            SELECT
                pl.tgl_periksa,
                pl.jam,
                pl.kd_jenis_prw,
                jpl.nm_perawatan,
                p.nama,
                d.nm_dokter,
                pl.biaya,
                dpa.diagnosa_klinik,
                dpa.makroskopik,
                dpa.mikroskopik,
                dpa.kesimpulan,
                dpa.kesan
            FROM periksa_lab pl
            INNER JOIN jns_perawatan_lab jpl ON pl.kd_jenis_prw = jpl.kd_jenis_prw
            INNER JOIN petugas p ON pl.nip = p.nip
            INNER JOIN dokter d ON pl.kd_dokter = d.kd_dokter
            LEFT JOIN detail_periksa_labpa dpa
                ON dpa.no_rawat = pl.no_rawat
                AND dpa.kd_jenis_prw = pl.kd_jenis_prw
                AND dpa.tgl_periksa = pl.tgl_periksa
                AND dpa.jam = pl.jam
            WHERE pl.no_rawat = ?
              AND pl.kategori = 'PA'
            ORDER BY pl.tgl_periksa, pl.jam
        ";

        return $this->db->fetchAll($sql, [$noRawat]);
    }
}
