<?php
declare(strict_types=1);

/*
 * Plain PHP version of Riwayat Perawatan tab.
 *
 * How to run quickly:
 * 1) Set DB credentials in environment:
 *    SIMRS_DB_HOST, SIMRS_DB_PORT, SIMRS_DB_NAME, SIMRS_DB_USER, SIMRS_DB_PASS
 * 2) Serve with PHP built-in server:
 *    php -S 0.0.0.0:8080 -t /home/rvl
 * 3) Open:
 *    http://localhost:8080/riwayat_perawatan_tab.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

function h(?string $v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('SIMRS_DB_HOST') ?: '127.0.0.1';
    $port = getenv('SIMRS_DB_PORT') ?: '3306';
    $name = getenv('SIMRS_DB_NAME') ?: 'sik';
    $user = getenv('SIMRS_DB_USER') ?: 'root';
    $pass = getenv('SIMRS_DB_PASS') ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

/**
 * Filter mode:
 * r1 = 5 terakhir
 * r2 = semua
 * r3 = rentang tanggal
 * r4 = no_rawat spesifik
 */
function fetchVisits(PDO $pdo, string $noRm, string $mode, ?string $tgl1, ?string $tgl2, ?string $noRawat): array {
    $baseSql =
        "select reg_periksa.no_reg, reg_periksa.no_rawat, reg_periksa.tgl_registrasi, reg_periksa.jam_reg,
                reg_periksa.kd_dokter, dokter.nm_dokter, poliklinik.nm_poli,
                reg_periksa.p_jawab, reg_periksa.almt_pj, reg_periksa.hubunganpj,
                reg_periksa.biaya_reg, reg_periksa.status_lanjut, penjab.png_jawab,
                reg_periksa.umurdaftar, reg_periksa.sttsumur
         from reg_periksa
         inner join dokter on reg_periksa.kd_dokter = dokter.kd_dokter
         inner join poliklinik on reg_periksa.kd_poli = poliklinik.kd_poli
         inner join penjab on reg_periksa.kd_pj = penjab.kd_pj
         where reg_periksa.stts <> 'Batal' and reg_periksa.no_rkm_medis = :no_rm";

    if ($mode === 'r1') {
        $sql = $baseSql . ' order by reg_periksa.tgl_registrasi desc limit 5';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':no_rm' => $noRm]);
        return $stmt->fetchAll();
    }

    if ($mode === 'r3' && $tgl1 && $tgl2) {
        $sql = $baseSql . ' and reg_periksa.tgl_registrasi between :tgl1 and :tgl2 order by reg_periksa.tgl_registrasi';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':no_rm' => $noRm,
            ':tgl1' => $tgl1,
            ':tgl2' => $tgl2,
        ]);
        return $stmt->fetchAll();
    }

    if ($mode === 'r4' && $noRawat) {
        $sql = $baseSql . ' and reg_periksa.no_rawat = :no_rawat order by reg_periksa.tgl_registrasi';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':no_rm' => $noRm,
            ':no_rawat' => $noRawat,
        ]);
        return $stmt->fetchAll();
    }

    $sql = $baseSql . ' order by reg_periksa.tgl_registrasi';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':no_rm' => $noRm]);
    return $stmt->fetchAll();
}

function fetchPatient(PDO $pdo, string $noRm): ?array {
    $sql =
        "select pasien.no_rkm_medis, pasien.nm_pasien, pasien.jk, pasien.tmp_lahir, pasien.tgl_lahir,
                pasien.agama, pasien.nm_ibu, pasien.stts_nikah, pasien.pnd, pasien.gol_darah,
                pasien.pekerjaan,
                bahasa_pasien.nama_bahasa,
                cacat_fisik.nama_cacat,
                concat(pasien.alamat, ', ', kelurahan.nm_kel, ', ', kecamatan.nm_kec, ', ', kabupaten.nm_kab) as alamat
         from pasien
         inner join bahasa_pasien on bahasa_pasien.id = pasien.bahasa_pasien
         inner join cacat_fisik on cacat_fisik.id = pasien.cacat_fisik
         inner join kelurahan on pasien.kd_kel = kelurahan.kd_kel
         inner join kecamatan on pasien.kd_kec = kecamatan.kd_kec
         inner join kabupaten on pasien.kd_kab = kabupaten.kd_kab
         where pasien.no_rkm_medis = :no_rm
         limit 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':no_rm' => $noRm]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function fetchRujukanInternal(PDO $pdo, string $noRawat): array {
    $sql =
        "select poliklinik.nm_poli, dokter.nm_dokter
         from rujukan_internal_poli
         inner join poliklinik on rujukan_internal_poli.kd_poli = poliklinik.kd_poli
         inner join dokter on rujukan_internal_poli.kd_dokter = dokter.kd_dokter
         where rujukan_internal_poli.no_rawat = :no_rawat";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':no_rawat' => $noRawat]);
    return $stmt->fetchAll();
}

function fetchDpjpRanap(PDO $pdo, string $noRawat): array {
    $sql =
        "select dpjp_ranap.kd_dokter, dokter.nm_dokter
         from dpjp_ranap
         inner join dokter on dpjp_ranap.kd_dokter = dokter.kd_dokter
         where dpjp_ranap.no_rawat = :no_rawat";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':no_rawat' => $noRawat]);
    return $stmt->fetchAll();
}

function fetchDiagnosa(PDO $pdo, string $noRawat): array {
    $sql =
        "select diagnosa_pasien.kd_penyakit, penyakit.nm_penyakit, diagnosa_pasien.status
         from diagnosa_pasien
         inner join penyakit on diagnosa_pasien.kd_penyakit = penyakit.kd_penyakit
         where diagnosa_pasien.no_rawat = :no_rawat
         order by diagnosa_pasien.prioritas";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':no_rawat' => $noRawat]);
    return $stmt->fetchAll();
}

function fetchProsedur(PDO $pdo, string $noRawat): array {
    $sql =
        "select prosedur_pasien.kode, icd9.deskripsi_panjang, prosedur_pasien.status
         from prosedur_pasien
         inner join icd9 on prosedur_pasien.kode = icd9.kode
         where prosedur_pasien.no_rawat = :no_rawat";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':no_rawat' => $noRawat]);
    return $stmt->fetchAll();
}

function fetchCatatanDokter(PDO $pdo, string $noRawat): array {
    $sql =
        "select catatan_perawatan.tanggal, catatan_perawatan.jam, catatan_perawatan.kd_dokter,
                dokter.nm_dokter, catatan_perawatan.catatan
         from catatan_perawatan
         inner join dokter on catatan_perawatan.kd_dokter = dokter.kd_dokter
         where catatan_perawatan.no_rawat = :no_rawat
         order by catatan_perawatan.tanggal, catatan_perawatan.jam";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':no_rawat' => $noRawat]);
    return $stmt->fetchAll();
}

function fetchTindakanRalanDokter(PDO $pdo, string $noRawat): array {
    $sql =
        "select rawat_jl_dr.kd_jenis_prw, jns_perawatan.nm_perawatan, dokter.nm_dokter,
                rawat_jl_dr.biaya_rawat, rawat_jl_dr.tgl_perawatan, rawat_jl_dr.jam_rawat
         from rawat_jl_dr
         inner join jns_perawatan on rawat_jl_dr.kd_jenis_prw = jns_perawatan.kd_jenis_prw
         inner join dokter on rawat_jl_dr.kd_dokter = dokter.kd_dokter
         where rawat_jl_dr.no_rawat = :no_rawat
         order by rawat_jl_dr.tgl_perawatan, rawat_jl_dr.jam_rawat";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':no_rawat' => $noRawat]);
    return $stmt->fetchAll();
}

function fetchTindakanRalanParamedis(PDO $pdo, string $noRawat): array {
    $sql =
        "select rawat_jl_pr.kd_jenis_prw, jns_perawatan.nm_perawatan, petugas.nama,
                rawat_jl_pr.biaya_rawat, rawat_jl_pr.tgl_perawatan, rawat_jl_pr.jam_rawat
         from rawat_jl_pr
         inner join jns_perawatan on rawat_jl_pr.kd_jenis_prw = jns_perawatan.kd_jenis_prw
         inner join petugas on rawat_jl_pr.nip = petugas.nip
         where rawat_jl_pr.no_rawat = :no_rawat
         order by rawat_jl_pr.tgl_perawatan, rawat_jl_pr.jam_rawat";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':no_rawat' => $noRawat]);
    return $stmt->fetchAll();
}

function fetchPenggunaanKamar(PDO $pdo, string $noRawat): array {
    $sql =
        "select kamar_inap.kd_kamar, bangsal.nm_bangsal, kamar_inap.tgl_masuk, kamar_inap.tgl_keluar,
                kamar_inap.stts_pulang, kamar_inap.lama, kamar_inap.jam_masuk, kamar_inap.jam_keluar,
                kamar_inap.ttl_biaya
         from kamar_inap
         inner join kamar on kamar_inap.kd_kamar = kamar.kd_kamar
         inner join bangsal on kamar.kd_bangsal = bangsal.kd_bangsal
         where kamar_inap.no_rawat = :no_rawat
         order by kamar_inap.tgl_masuk, kamar_inap.jam_masuk";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':no_rawat' => $noRawat]);
    return $stmt->fetchAll();
}

function money(float $n): string {
    return number_format($n, 2, ',', '.');
}

$sectionsAll = [
    'diagnosa' => 'Diagnosa/Penyakit (ICD-10)',
    'prosedur' => 'Prosedur/Tindakan (ICD-9)',
    'catatan_dokter' => 'Catatan Dokter',
    'tindakan_ralan_dokter' => 'Tindakan Rawat Jalan Dokter',
    'tindakan_ralan_paramedis' => 'Tindakan Rawat Jalan Paramedis',
    'penggunaan_kamar' => 'Penggunaan Kamar',
    'biaya_ringkas' => 'Biaya Ringkas',
];

$noRm = trim((string) ($_GET['no_rkm_medis'] ?? ''));
$mode = (string) ($_GET['mode'] ?? 'r1');
$tgl1 = trim((string) ($_GET['tgl1'] ?? ''));
$tgl2 = trim((string) ($_GET['tgl2'] ?? ''));
$noRawat = trim((string) ($_GET['no_rawat'] ?? ''));
$selectAll = (string) ($_GET['select_all'] ?? '1');

$selectedSections = [];
foreach ($sectionsAll as $key => $_label) {
    if ($selectAll === '1' || isset($_GET['sec_' . $key])) {
        $selectedSections[$key] = true;
    }
}

$errors = [];
$patient = null;
$visits = [];

if ($noRm !== '') {
    try {
        $pdo = db();
        $patient = fetchPatient($pdo, $noRm);
        $visits = fetchVisits($pdo, $noRm, $mode, $tgl1 !== '' ? $tgl1 : null, $tgl2 !== '' ? $tgl2 : null, $noRawat !== '' ? $noRawat : null);
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Riwayat Perawatan (Plain PHP)</title>
<link rel="icon" type="image/png" href="public/setting/logo/logo.png">
<style>
:root {
    --bg: #f2f5f7;
    --card: #ffffff;
    --line: #d9e1e7;
    --text: #1e2b37;
    --muted: #667787;
    --accent: #0f6d84;
    --accent-2: #0a4f60;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: Tahoma, Verdana, sans-serif;
    color: var(--text);
    background:
        radial-gradient(circle at 85% -5%, #cde9ef 0, #cde9ef 18%, transparent 45%),
        radial-gradient(circle at 5% 105%, #dbeee0 0, #dbeee0 22%, transparent 48%),
        var(--bg);
}
.container {
    max-width: 1400px;
    margin: 18px auto;
    padding: 0 14px;
}
.card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(22, 39, 54, .06);
}
.header {
    padding: 14px 16px;
    border-bottom: 1px solid var(--line);
}
.header h1 {
    margin: 0;
    font-size: 20px;
}
.header p {
    margin: 6px 0 0;
    color: var(--muted);
}
.search {
    padding: 14px;
    border-bottom: 1px solid var(--line);
    display: grid;
    gap: 10px;
}
.row {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(12, 1fr);
}
.input, .select, .btn {
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 9px 10px;
    font-size: 13px;
    width: 100%;
}
.btn {
    background: var(--accent);
    color: #fff;
    border: 1px solid var(--accent-2);
    cursor: pointer;
    font-weight: 700;
}
.btn:hover { filter: brightness(0.96); }
.chips {
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
}
.chip {
    border: 1px solid var(--line);
    background: #f8fbfd;
    border-radius: 999px;
    padding: 5px 9px;
    font-size: 12px;
}
.body {
    display: grid;
    gap: 0;
    grid-template-columns: 290px 1fr;
}
.sidebar {
    border-right: 1px solid var(--line);
    padding: 12px;
    max-height: 74vh;
    overflow: auto;
}
.sidebar h3 {
    margin: 0 0 8px;
    font-size: 14px;
}
.check { display: block; margin-bottom: 8px; font-size: 13px; }
.content {
    padding: 14px;
    max-height: 74vh;
    overflow: auto;
}
.table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
}
.table td, .table th {
    border: 1px solid #e2e7dd;
    padding: 6px;
    font-size: 12px;
    vertical-align: top;
}
.table th {
    background: #fffaf8;
}
.section-title {
    background: #eef7f9;
    color: #1f5060;
    padding: 7px 9px;
    border: 1px solid #d8e9ee;
    border-radius: 7px;
    margin: 10px 0 6px;
    font-weight: 700;
    font-size: 12px;
}
.block {
    margin-bottom: 14px;
    border: 1px solid var(--line);
    border-radius: 10px;
    overflow: hidden;
}
.block-head {
    background: linear-gradient(90deg, #edf6f9, #f6fafb);
    border-bottom: 1px solid var(--line);
    padding: 9px 11px;
    font-size: 13px;
    font-weight: 700;
}
.block-body {
    padding: 10px;
}
.muted { color: var(--muted); }
.error {
    background: #fff3f3;
    border: 1px solid #ffc7c7;
    color: #9d1f1f;
    padding: 10px;
    border-radius: 8px;
    margin: 10px 0;
    font-size: 13px;
}
@media (max-width: 980px) {
    .body { grid-template-columns: 1fr; }
    .sidebar { border-right: 0; border-bottom: 1px solid var(--line); max-height: unset; }
    .content { max-height: unset; }
}
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <h1>Riwayat Perawatan</h1>
            <p>Versi plain PHP dari tab Riwayat Perawatan (fokus encounter + section detail).</p>
        </div>

        <form method="get" class="search">
            <div class="row">
                <div style="grid-column: span 3;">
                    <label>No. RM</label>
                    <input class="input" name="no_rkm_medis" value="<?= h($noRm) ?>" required>
                </div>
                <div style="grid-column: span 2;">
                    <label>Mode</label>
                    <select class="select" name="mode" id="mode">
                        <option value="r1" <?= $mode === 'r1' ? 'selected' : '' ?>>5 Riwayat Terakhir</option>
                        <option value="r2" <?= $mode === 'r2' ? 'selected' : '' ?>>Semua Riwayat</option>
                        <option value="r3" <?= $mode === 'r3' ? 'selected' : '' ?>>Rentang Tanggal</option>
                        <option value="r4" <?= $mode === 'r4' ? 'selected' : '' ?>>No. Rawat Spesifik</option>
                    </select>
                </div>
                <div style="grid-column: span 2;">
                    <label>Tgl 1</label>
                    <input class="input" type="date" name="tgl1" value="<?= h($tgl1) ?>">
                </div>
                <div style="grid-column: span 2;">
                    <label>Tgl 2</label>
                    <input class="input" type="date" name="tgl2" value="<?= h($tgl2) ?>">
                </div>
                <div style="grid-column: span 2;">
                    <label>No Rawat</label>
                    <input class="input" name="no_rawat" value="<?= h($noRawat) ?>">
                </div>
                <div style="grid-column: span 1; align-self: end;">
                    <button class="btn" type="submit">Tampilkan</button>
                </div>
            </div>
        </form>

        <div class="body">
            <div class="sidebar">
                <h3>Menu Section</h3>
                <label class="check">
                    <input type="checkbox" id="select_all" name="select_all_checkbox" <?= $selectAll === '1' ? 'checked' : '' ?>>
                    Pilih Semua
                </label>
                <input type="hidden" name="select_all" id="select_all_hidden" form="hidden-form" value="<?= h($selectAll) ?>">

                <?php foreach ($sectionsAll as $k => $label): ?>
                    <label class="check">
                        <input type="checkbox"
                               class="sec"
                               name="sec_<?= h($k) ?>"
                               form="hidden-form"
                               value="1"
                               <?= isset($selectedSections[$k]) ? 'checked' : '' ?>>
                        <?= h($label) ?>
                    </label>
                <?php endforeach; ?>

                <form id="hidden-form" method="get">
                    <input type="hidden" name="no_rkm_medis" value="<?= h($noRm) ?>">
                    <input type="hidden" name="mode" value="<?= h($mode) ?>">
                    <input type="hidden" name="tgl1" value="<?= h($tgl1) ?>">
                    <input type="hidden" name="tgl2" value="<?= h($tgl2) ?>">
                    <input type="hidden" name="no_rawat" value="<?= h($noRawat) ?>">
                    <div style="margin-top:10px;">
                        <button class="btn" type="submit">Apply Menu</button>
                    </div>
                </form>
            </div>

            <div class="content">
                <?php if ($errors): ?>
                    <?php foreach ($errors as $er): ?>
                        <div class="error">Error DB: <?= h($er) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($noRm === ''): ?>
                    <div class="muted">Masukkan No. RM lalu klik Tampilkan.</div>
                <?php else: ?>
                    <div class="block">
                        <div class="block-head">Data Pasien</div>
                        <div class="block-body">
                            <?php if (!$patient): ?>
                                <div class="muted">Pasien tidak ditemukan.</div>
                            <?php else: ?>
                                <table class="table">
                                    <tr><th width="22%">No RM</th><td><?= h($patient['no_rkm_medis']) ?></td></tr>
                                    <tr><th>Nama Pasien</th><td><?= h($patient['nm_pasien']) ?></td></tr>
                                    <tr><th>JK</th><td><?= h($patient['jk']) ?></td></tr>
                                    <tr><th>TTL</th><td><?= h($patient['tmp_lahir']) ?>, <?= h($patient['tgl_lahir']) ?></td></tr>
                                    <tr><th>Alamat</th><td><?= h($patient['alamat']) ?></td></tr>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="chips">
                        <span class="chip">Encounter: <?= h((string) count($visits)) ?></span>
                        <span class="chip">Mode: <?= h(strtoupper($mode)) ?></span>
                    </div>

                    <?php foreach ($visits as $idx => $v): ?>
                        <?php
                        $noRawatVisit = (string) $v['no_rawat'];
                        $rujukan = fetchRujukanInternal($pdo, $noRawatVisit);
                        $dpjp = $v['status_lanjut'] === 'Ranap' ? fetchDpjpRanap($pdo, $noRawatVisit) : [];
                        $biayaTotal = (float) $v['biaya_reg'];
                        ?>
                        <div class="block">
                            <div class="block-head">Encounter #<?= h((string) ($idx + 1)) ?> - <?= h($noRawatVisit) ?></div>
                            <div class="block-body">
                                <table class="table">
                                    <tr><th width="22%">No. Rawat</th><td><?= h($v['no_rawat']) ?></td></tr>
                                    <tr><th>No. Registrasi</th><td><?= h($v['no_reg']) ?></td></tr>
                                    <tr><th>Tanggal Registrasi</th><td><?= h($v['tgl_registrasi']) ?> <?= h($v['jam_reg']) ?></td></tr>
                                    <tr><th>Umur Saat Daftar</th><td><?= h($v['umurdaftar']) ?> <?= h($v['sttsumur']) ?></td></tr>
                                    <tr><th>Unit/Poliklinik</th><td><?= h($v['nm_poli']) ?></td></tr>
                                    <tr><th>Dokter Poli</th><td><?= h($v['nm_dokter']) ?></td></tr>
                                    <tr><th>Cara Bayar</th><td><?= h($v['png_jawab']) ?></td></tr>
                                    <tr><th>Penanggung Jawab</th><td><?= h($v['p_jawab']) ?></td></tr>
                                    <tr><th>Alamat P.J.</th><td><?= h($v['almt_pj']) ?></td></tr>
                                    <tr><th>Hubungan P.J.</th><td><?= h($v['hubunganpj']) ?></td></tr>
                                    <tr><th>Status</th><td><?= h($v['status_lanjut']) ?></td></tr>
                                </table>

                                <?php if ($rujukan): ?>
                                    <div class="section-title">Rujukan Internal Poli</div>
                                    <table class="table">
                                        <tr><th width="5%">No</th><th>Poli</th><th>Dokter</th></tr>
                                        <?php foreach ($rujukan as $rIdx => $r): ?>
                                            <tr>
                                                <td align="center"><?= h((string) ($rIdx + 1)) ?></td>
                                                <td><?= h($r['nm_poli']) ?></td>
                                                <td><?= h($r['nm_dokter']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                <?php endif; ?>

                                <?php if ($dpjp): ?>
                                    <div class="section-title">DPJP Ranap</div>
                                    <table class="table">
                                        <tr><th width="5%">No</th><th>Kode</th><th>Nama Dokter</th></tr>
                                        <?php foreach ($dpjp as $dIdx => $d): ?>
                                            <tr>
                                                <td align="center"><?= h((string) ($dIdx + 1)) ?></td>
                                                <td><?= h($d['kd_dokter']) ?></td>
                                                <td><?= h($d['nm_dokter']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                <?php endif; ?>

                                <?php if (isset($selectedSections['diagnosa'])): ?>
                                    <?php $diagnosa = fetchDiagnosa($pdo, $noRawatVisit); ?>
                                    <?php if ($diagnosa): ?>
                                        <div class="section-title">Diagnosa / ICD-10</div>
                                        <table class="table">
                                            <tr><th width="5%">No</th><th width="20%">Kode</th><th>Nama Penyakit</th><th width="20%">Status</th></tr>
                                            <?php foreach ($diagnosa as $dIdx => $d): ?>
                                                <tr>
                                                    <td align="center"><?= h((string) ($dIdx + 1)) ?></td>
                                                    <td><?= h($d['kd_penyakit']) ?></td>
                                                    <td><?= h($d['nm_penyakit']) ?></td>
                                                    <td><?= h($d['status']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (isset($selectedSections['prosedur'])): ?>
                                    <?php $prosedur = fetchProsedur($pdo, $noRawatVisit); ?>
                                    <?php if ($prosedur): ?>
                                        <div class="section-title">Prosedur / ICD-9</div>
                                        <table class="table">
                                            <tr><th width="5%">No</th><th width="20%">Kode</th><th>Nama Prosedur</th><th width="20%">Status</th></tr>
                                            <?php foreach ($prosedur as $pIdx => $p): ?>
                                                <tr>
                                                    <td align="center"><?= h((string) ($pIdx + 1)) ?></td>
                                                    <td><?= h($p['kode']) ?></td>
                                                    <td><?= h($p['deskripsi_panjang']) ?></td>
                                                    <td><?= h($p['status']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (isset($selectedSections['catatan_dokter'])): ?>
                                    <?php $catatan = fetchCatatanDokter($pdo, $noRawatVisit); ?>
                                    <?php if ($catatan): ?>
                                        <div class="section-title">Catatan Dokter</div>
                                        <table class="table">
                                            <tr><th width="5%">No</th><th width="18%">Tanggal</th><th width="12%">Kode</th><th width="20%">Dokter</th><th>Catatan</th></tr>
                                            <?php foreach ($catatan as $cIdx => $c): ?>
                                                <tr>
                                                    <td align="center"><?= h((string) ($cIdx + 1)) ?></td>
                                                    <td><?= h($c['tanggal']) ?> <?= h($c['jam']) ?></td>
                                                    <td><?= h($c['kd_dokter']) ?></td>
                                                    <td><?= h($c['nm_dokter']) ?></td>
                                                    <td><?= nl2br(h($c['catatan'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (isset($selectedSections['tindakan_ralan_dokter'])): ?>
                                    <?php $trd = fetchTindakanRalanDokter($pdo, $noRawatVisit); ?>
                                    <?php if ($trd): ?>
                                        <div class="section-title">Tindakan Rawat Jalan Dokter</div>
                                        <table class="table">
                                            <tr><th width="5%">No</th><th width="18%">Tanggal</th><th width="12%">Kode</th><th>Nama Tindakan</th><th width="20%">Dokter</th><th width="12%">Biaya</th></tr>
                                            <?php foreach ($trd as $tIdx => $t): ?>
                                                <?php $biayaTotal += (float) $t['biaya_rawat']; ?>
                                                <tr>
                                                    <td align="center"><?= h((string) ($tIdx + 1)) ?></td>
                                                    <td><?= h($t['tgl_perawatan']) ?> <?= h($t['jam_rawat']) ?></td>
                                                    <td><?= h($t['kd_jenis_prw']) ?></td>
                                                    <td><?= h($t['nm_perawatan']) ?></td>
                                                    <td><?= h($t['nm_dokter']) ?></td>
                                                    <td align="right"><?= h(money((float) $t['biaya_rawat'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (isset($selectedSections['tindakan_ralan_paramedis'])): ?>
                                    <?php $trp = fetchTindakanRalanParamedis($pdo, $noRawatVisit); ?>
                                    <?php if ($trp): ?>
                                        <div class="section-title">Tindakan Rawat Jalan Paramedis</div>
                                        <table class="table">
                                            <tr><th width="5%">No</th><th width="18%">Tanggal</th><th width="12%">Kode</th><th>Nama Tindakan</th><th width="20%">Paramedis</th><th width="12%">Biaya</th></tr>
                                            <?php foreach ($trp as $tIdx => $t): ?>
                                                <?php $biayaTotal += (float) $t['biaya_rawat']; ?>
                                                <tr>
                                                    <td align="center"><?= h((string) ($tIdx + 1)) ?></td>
                                                    <td><?= h($t['tgl_perawatan']) ?> <?= h($t['jam_rawat']) ?></td>
                                                    <td><?= h($t['kd_jenis_prw']) ?></td>
                                                    <td><?= h($t['nm_perawatan']) ?></td>
                                                    <td><?= h($t['nama']) ?></td>
                                                    <td align="right"><?= h(money((float) $t['biaya_rawat'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (isset($selectedSections['penggunaan_kamar'])): ?>
                                    <?php $kamar = fetchPenggunaanKamar($pdo, $noRawatVisit); ?>
                                    <?php if ($kamar): ?>
                                        <div class="section-title">Penggunaan Kamar</div>
                                        <table class="table">
                                            <tr><th width="5%">No</th><th width="17%">Tgl Masuk</th><th width="17%">Tgl Keluar</th><th width="8%">Lama</th><th>Kamar</th><th width="12%">Status</th><th width="12%">Biaya</th></tr>
                                            <?php foreach ($kamar as $kIdx => $k): ?>
                                                <?php $biayaTotal += (float) $k['ttl_biaya']; ?>
                                                <tr>
                                                    <td align="center"><?= h((string) ($kIdx + 1)) ?></td>
                                                    <td><?= h($k['tgl_masuk']) ?> <?= h($k['jam_masuk']) ?></td>
                                                    <td><?= h($k['tgl_keluar']) ?> <?= h($k['jam_keluar']) ?></td>
                                                    <td><?= h($k['lama']) ?></td>
                                                    <td><?= h($k['kd_kamar']) ?>, <?= h($k['nm_bangsal']) ?></td>
                                                    <td><?= h($k['stts_pulang']) ?></td>
                                                    <td align="right"><?= h(money((float) $k['ttl_biaya'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (isset($selectedSections['biaya_ringkas'])): ?>
                                    <div class="section-title">Biaya Ringkas</div>
                                    <table class="table">
                                        <tr><th width="22%">Administrasi</th><td align="right"><?= h(money((float) $v['biaya_reg'])) ?></td></tr>
                                        <tr><th>Total Sementara</th><td align="right"><strong><?= h(money($biayaTotal)) ?></strong></td></tr>
                                    </table>
                                <?php endif; ?>

                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (!$visits): ?>
                        <div class="muted">Tidak ada kunjungan sesuai filter.</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var selectAll = document.getElementById('select_all');
    var sec = document.querySelectorAll('.sec');
    var hidden = document.getElementById('select_all_hidden');

    function applySelectAllState() {
        var checked = selectAll.checked;
        hidden.value = checked ? '1' : '0';
        sec.forEach(function (s) {
            s.checked = checked;
        });
    }

    if (selectAll) {
        selectAll.addEventListener('change', applySelectAllState);
    }
})();
</script>
</body>
</html>
