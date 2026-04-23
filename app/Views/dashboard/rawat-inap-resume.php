<?php
$context = is_array($context ?? null) ? $context : [];
$resumeData = is_array($resumeData ?? null) ? $resumeData : [];
$errors = is_array($errors ?? null) ? $errors : [];
$error = (string) ($error ?? '');
$success = (string) ($success ?? '');
$doctorId = (string) ($doctorId ?? '');
$doctorName = (string) ($doctorName ?? '');
$caraKeluarOptions = is_array($caraKeluarOptions ?? null) ? $caraKeluarOptions : ['Atas Izin Dokter', 'Pindah RS', 'Pulang Atas Permintaan Sendiri', 'Lainnya'];
$keadaanOptions = is_array($keadaanOptions ?? null) ? $keadaanOptions : ['Membaik', 'Sembuh', 'Keadaan Khusus', 'Meninggal'];
$dilanjutkanOptions = is_array($dilanjutkanOptions ?? null) ? $dilanjutkanOptions : ['Kembali Ke RS', 'RS Lain', 'Dokter Luar', 'Puskesmes', 'Lainnya'];
$isNewResume = (bool) ($isNewResume ?? false);
$hasOldInput = (bool) ($hasOldInput ?? false);
$isItMode = (bool) ($isItMode ?? false);

$noRawat = (string) ($context['no_rawat'] ?? '');
$noRm = (string) ($context['no_rkm_medis'] ?? '');
$nmPasien = (string) ($context['nm_pasien'] ?? '');
$dpjp = (string) ($context['dpjp'] ?? '');
$tglMasuk = (string) ($context['tgl_masuk'] ?? '');
$tglKeluar = (string) ($context['tgl_keluar'] ?? '');

$formatDate = static function (string $dateValue): string {
    if ($dateValue === '' || $dateValue === '0000-00-00') {
        return '-';
    }

    $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateValue);
    if ($date === false) {
        return $dateValue;
    }

    return $date->format('d-m-Y');
};

$getValue = static function (string $field) use ($resumeData): string {
    return isset($resumeData[$field]) ? (string) $resumeData[$field] : '';
};

$kontrolValue = $getValue('kontrol');
$kontrolInputValue = '';
$kontrolDateValue = '';
$kontrolTimeValue = '';
if ($kontrolValue !== '') {
    $kontrolDateTime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $kontrolValue);
    if ($kontrolDateTime !== false) {
        $kontrolInputValue = $kontrolDateTime->format('Y-m-d\TH:i');
        $kontrolDateValue = $kontrolDateTime->format('d/m/Y');
        $kontrolTimeValue = $kontrolDateTime->format('H:i');
    } else {
        $kontrolDateTimeAlt = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $kontrolValue);
        if ($kontrolDateTimeAlt !== false) {
            $kontrolInputValue = $kontrolDateTimeAlt->format('Y-m-d\TH:i');
            $kontrolDateValue = $kontrolDateTimeAlt->format('d/m/Y');
            $kontrolTimeValue = $kontrolDateTimeAlt->format('H:i');
        } else {
            $kontrolInputValue = $kontrolValue;
        }
    }
}

if ($kontrolInputValue === '' && $isNewResume && !$hasOldInput) {
    $kontrolInputValue = (new \DateTimeImmutable('now'))->format('Y-m-d\TH:i');
    $now = new \DateTimeImmutable('now');
    $kontrolDateValue = $now->format('d/m/Y');
    $kontrolTimeValue = $now->format('H:i');
}

if ($kontrolInputValue !== '' && ($kontrolDateValue === '' || $kontrolTimeValue === '')) {
    $kontrolDateTimeInput = \DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $kontrolInputValue);
    if ($kontrolDateTimeInput !== false) {
        $kontrolDateValue = $kontrolDateTimeInput->format('d/m/Y');
        $kontrolTimeValue = $kontrolDateTimeInput->format('H:i');
    }
}

$backPath = (string) ($backPath ?? '/rawat-inap');
$savePath = (string) ($savePath ?? '/rawat-inap/resume');
$autofillPath = (string) ($autofillPath ?? '/rawat-inap/resume/autofill');
$medicineOptionsPath = (string) ($medicineOptionsPath ?? '/rawat-inap/resume/medicine-options');

$backUrl = (string) call_user_func($routePath, $backPath);
$saveUrl = (string) call_user_func($routePath, $savePath);
$autofillUrl = (string) call_user_func($routePath, $autofillPath);
$medicineOptionsUrl = (string) call_user_func($routePath, $medicineOptionsPath);
$riwayatUrl = (string) call_user_func($routePath, '/riwayat-perawatan');
$riwayatModalUrl = $riwayatUrl;
if ($noRm !== '') {
    $riwayatModalUrl .= '?' . http_build_query([
        'no_rkm_medis' => $noRm,
        'mode' => 'r1',
    ]);
}
?>

<section class="page-card rawat-inap-page">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">Resume Rawat Inap</h2>
                    <p class="text-secondary mb-0">Isi resume pasien berdasarkan No. Rawat.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-primary btn-sm js-open-riwayat-modal" title="Lihat riwayat pasien tanpa meninggalkan halaman resume">
                        <i class="bi bi-clock-history"></i>
                        Riwayat
                    </button>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= \App\Helpers\App::e($backUrl) ?>">
                        <i class="bi bi-arrow-left"></i>
                        Kembali ke Rawat Inap
                    </a>
                </div>
            </div>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success mb-3"><?= \App\Helpers\App::e($success) ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger mb-3"><?= \App\Helpers\App::e($error) ?></div>
            <?php endif; ?>

            <?php if ($errors !== []): ?>
                <div class="alert alert-danger mb-3">
                    <p class="mb-1 fw-semibold">Periksa kembali data resume:</p>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $item): ?>
                            <li><?= \App\Helpers\App::e((string) $item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded p-3 bg-light-subtle h-100">
                        <small class="text-secondary">No. Rawat</small>
                        <p class="mb-0 fw-semibold"><?= \App\Helpers\App::e($noRawat) ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded p-3 bg-light-subtle h-100">
                        <small class="text-secondary">No. RM / Pasien</small>
                        <p class="mb-0 fw-semibold"><?= \App\Helpers\App::e($noRm) ?> - <?= \App\Helpers\App::e($nmPasien) ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded p-3 bg-light-subtle h-100">
                        <small class="text-secondary">DPJP</small>
                        <p class="mb-0 fw-semibold"><?= \App\Helpers\App::e($dpjp !== '' ? $dpjp : '-') ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded p-3 bg-light-subtle h-100">
                        <small class="text-secondary">Tanggal Masuk</small>
                        <p class="mb-0 fw-semibold"><?= \App\Helpers\App::e($formatDate($tglMasuk)) ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded p-3 bg-light-subtle h-100">
                        <small class="text-secondary">Tanggal Keluar</small>
                        <p class="mb-0 fw-semibold"><?= \App\Helpers\App::e($formatDate($tglKeluar)) ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded p-3 bg-light-subtle h-100">
                        <small class="text-secondary">Dokter Penanggung Jawab</small>
                        <p class="mb-0 fw-semibold"><?= \App\Helpers\App::e($doctorId) ?> - <?= \App\Helpers\App::e($doctorName !== '' ? $doctorName : '-') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="post" action="<?= \App\Helpers\App::e($saveUrl) ?>" class="card border-0 shadow-sm rawat-inap-resume-form" id="rawatInapResumeForm">
        <div class="card-body">
            <input type="hidden" name="no_rawat" value="<?= \App\Helpers\App::e($noRawat) ?>">
            <?php if ($isItMode): ?>
                <input type="hidden" name="kd_dokter" value="<?= \App\Helpers\App::e($doctorId) ?>">
            <?php endif; ?>

            <h3 class="h6 mb-3">Anamnesis dan Perjalanan Penyakit</h3>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <label class="form-label mb-0" for="diagnosa_awal">Diagnosa Awal</label>
                        <button type="button" class="btn btn-sm btn-edokter-autofill js-autofill-field" data-field="diagnosa_awal" data-target="diagnosa_awal" title="Ambil data otomatis">
                            <i class="bi bi-magic"></i>
                            Autofill
                        </button>
                    </div>
                    <input class="form-control" id="diagnosa_awal" name="diagnosa_awal" value="<?= \App\Helpers\App::e($getValue('diagnosa_awal')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="alasan">Alasan Dirawat</label>
                    <input class="form-control" id="alasan" name="alasan" value="<?= \App\Helpers\App::e($getValue('alasan')) ?>">
                </div>
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <label class="form-label mb-0" for="keluhan_utama">Keluhan Utama</label>
                        <button type="button" class="btn btn-sm btn-edokter-autofill js-autofill-field" data-field="keluhan_utama" data-target="keluhan_utama" title="Ambil data otomatis">
                            <i class="bi bi-magic"></i>
                            Autofill
                        </button>
                    </div>
                    <textarea class="form-control" id="keluhan_utama" name="keluhan_utama" rows="2" required><?= \App\Helpers\App::e($getValue('keluhan_utama')) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="pemeriksaan_fisik">Pemeriksaan Fisik</label>
                    <textarea class="form-control" id="pemeriksaan_fisik" name="pemeriksaan_fisik" rows="2"><?= \App\Helpers\App::e($getValue('pemeriksaan_fisik')) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="jalannya_penyakit">Jalannya Penyakit Selama Perawatan</label>
                    <textarea class="form-control" id="jalannya_penyakit" name="jalannya_penyakit" rows="3" required><?= \App\Helpers\App::e($getValue('jalannya_penyakit')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <label class="form-label mb-0" for="pemeriksaan_penunjang">Pemeriksaan Radiologi</label>
                        <button type="button" class="btn btn-sm btn-edokter-autofill js-autofill-field" data-field="pemeriksaan_penunjang" data-target="pemeriksaan_penunjang" title="Ambil data otomatis">
                            <i class="bi bi-magic"></i>
                            Autofill
                        </button>
                    </div>
                    <textarea class="form-control" id="pemeriksaan_penunjang" name="pemeriksaan_penunjang" rows="2"><?= \App\Helpers\App::e($getValue('pemeriksaan_penunjang')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <label class="form-label mb-0" for="hasil_laborat">Pemeriksaan Laboratorium</label>
                        <button type="button" class="btn btn-sm btn-edokter-autofill js-autofill-field" data-field="hasil_laborat" data-target="hasil_laborat" title="Ambil data otomatis">
                            <i class="bi bi-magic"></i>
                            Autofill
                        </button>
                    </div>
                    <textarea class="form-control" id="hasil_laborat" name="hasil_laborat" rows="2"><?= \App\Helpers\App::e($getValue('hasil_laborat')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="tindakan_dan_operasi">Tindakan Selama di RS</label>
                    <textarea class="form-control" id="tindakan_dan_operasi" name="tindakan_dan_operasi" rows="2"><?= \App\Helpers\App::e($getValue('tindakan_dan_operasi')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <label class="form-label mb-0" for="obat_di_rs">Obat Selama di RS</label>
                        <button type="button" class="btn btn-outline-secondary btn-sm js-open-medicine-modal" data-source="obat_di_rs" data-target="obat_di_rs" data-title="Pilih Obat Selama di RS" title="Pilih dari daftar obat">
                            <i class="bi bi-list-check"></i>
                        </button>
                    </div>
                    <textarea class="form-control" id="obat_di_rs" name="obat_di_rs" rows="2"><?= \App\Helpers\App::e($getValue('obat_di_rs')) ?></textarea>
                </div>
            </div>

            <h3 class="h6 mb-3">Diagnosa dan Prosedur</h3>
            <div class="row g-3 mb-4">
                <div class="col-md-9">
                    <label class="form-label" for="diagnosa_utama">Diagnosa Utama</label>
                    <input class="form-control" id="diagnosa_utama" name="diagnosa_utama" value="<?= \App\Helpers\App::e($getValue('diagnosa_utama')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="kd_diagnosa_utama">Kode Diagnosa Utama</label>
                    <input class="form-control" id="kd_diagnosa_utama" name="kd_diagnosa_utama" value="<?= \App\Helpers\App::e($getValue('kd_diagnosa_utama')) ?>">
                </div>

                <div class="col-md-9">
                    <label class="form-label" for="diagnosa_sekunder">Diagnosa Sekunder 1</label>
                    <input class="form-control" id="diagnosa_sekunder" name="diagnosa_sekunder" value="<?= \App\Helpers\App::e($getValue('diagnosa_sekunder')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="kd_diagnosa_sekunder">Kode</label>
                    <input class="form-control" id="kd_diagnosa_sekunder" name="kd_diagnosa_sekunder" value="<?= \App\Helpers\App::e($getValue('kd_diagnosa_sekunder')) ?>">
                </div>

                <div class="col-md-9">
                    <label class="form-label" for="diagnosa_sekunder2">Diagnosa Sekunder 2</label>
                    <input class="form-control" id="diagnosa_sekunder2" name="diagnosa_sekunder2" value="<?= \App\Helpers\App::e($getValue('diagnosa_sekunder2')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="kd_diagnosa_sekunder2">Kode</label>
                    <input class="form-control" id="kd_diagnosa_sekunder2" name="kd_diagnosa_sekunder2" value="<?= \App\Helpers\App::e($getValue('kd_diagnosa_sekunder2')) ?>">
                </div>

                <div class="col-md-9">
                    <label class="form-label" for="diagnosa_sekunder3">Diagnosa Sekunder 3</label>
                    <input class="form-control" id="diagnosa_sekunder3" name="diagnosa_sekunder3" value="<?= \App\Helpers\App::e($getValue('diagnosa_sekunder3')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="kd_diagnosa_sekunder3">Kode</label>
                    <input class="form-control" id="kd_diagnosa_sekunder3" name="kd_diagnosa_sekunder3" value="<?= \App\Helpers\App::e($getValue('kd_diagnosa_sekunder3')) ?>">
                </div>

                <div class="col-md-9">
                    <label class="form-label" for="diagnosa_sekunder4">Diagnosa Sekunder 4</label>
                    <input class="form-control" id="diagnosa_sekunder4" name="diagnosa_sekunder4" value="<?= \App\Helpers\App::e($getValue('diagnosa_sekunder4')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="kd_diagnosa_sekunder4">Kode</label>
                    <input class="form-control" id="kd_diagnosa_sekunder4" name="kd_diagnosa_sekunder4" value="<?= \App\Helpers\App::e($getValue('kd_diagnosa_sekunder4')) ?>">
                </div>

                <div class="col-md-9">
                    <label class="form-label" for="prosedur_utama">Prosedur Utama</label>
                    <input class="form-control" id="prosedur_utama" name="prosedur_utama" value="<?= \App\Helpers\App::e($getValue('prosedur_utama')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="kd_prosedur_utama">Kode Prosedur Utama</label>
                    <input class="form-control" id="kd_prosedur_utama" name="kd_prosedur_utama" value="<?= \App\Helpers\App::e($getValue('kd_prosedur_utama')) ?>">
                </div>

                <div class="col-md-9">
                    <label class="form-label" for="prosedur_sekunder">Prosedur Sekunder 1</label>
                    <input class="form-control" id="prosedur_sekunder" name="prosedur_sekunder" value="<?= \App\Helpers\App::e($getValue('prosedur_sekunder')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="kd_prosedur_sekunder">Kode</label>
                    <input class="form-control" id="kd_prosedur_sekunder" name="kd_prosedur_sekunder" value="<?= \App\Helpers\App::e($getValue('kd_prosedur_sekunder')) ?>">
                </div>

                <div class="col-md-9">
                    <label class="form-label" for="prosedur_sekunder2">Prosedur Sekunder 2</label>
                    <input class="form-control" id="prosedur_sekunder2" name="prosedur_sekunder2" value="<?= \App\Helpers\App::e($getValue('prosedur_sekunder2')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="kd_prosedur_sekunder2">Kode</label>
                    <input class="form-control" id="kd_prosedur_sekunder2" name="kd_prosedur_sekunder2" value="<?= \App\Helpers\App::e($getValue('kd_prosedur_sekunder2')) ?>">
                </div>

                <div class="col-md-9">
                    <label class="form-label" for="prosedur_sekunder3">Prosedur Sekunder 3</label>
                    <input class="form-control" id="prosedur_sekunder3" name="prosedur_sekunder3" value="<?= \App\Helpers\App::e($getValue('prosedur_sekunder3')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="kd_prosedur_sekunder3">Kode</label>
                    <input class="form-control" id="kd_prosedur_sekunder3" name="kd_prosedur_sekunder3" value="<?= \App\Helpers\App::e($getValue('kd_prosedur_sekunder3')) ?>">
                </div>
            </div>

            <h3 class="h6 mb-3">Rencana Pulang dan Tindak Lanjut</h3>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label" for="alergi">Alergi</label>
                    <input class="form-control" id="alergi" name="alergi" value="<?= \App\Helpers\App::e($getValue('alergi')) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="diet">Diet</label>
                    <textarea class="form-control" id="diet" name="diet" rows="2"><?= \App\Helpers\App::e($getValue('diet')) ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="lab_belum">Lab Belum Selesai</label>
                    <textarea class="form-control" id="lab_belum" name="lab_belum" rows="2"><?= \App\Helpers\App::e($getValue('lab_belum')) ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="edukasi">Edukasi</label>
                    <textarea class="form-control" id="edukasi" name="edukasi" rows="2"><?= \App\Helpers\App::e($getValue('edukasi')) ?></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="cara_keluar">Cara Keluar</label>
                    <?php $caraKeluarValue = $getValue('cara_keluar') !== '' ? $getValue('cara_keluar') : (string) ($caraKeluarOptions[0] ?? ''); ?>
                    <select class="form-select" id="cara_keluar" name="cara_keluar" required>
                        <?php foreach ($caraKeluarOptions as $option): ?>
                            <option value="<?= \App\Helpers\App::e((string) $option) ?>" <?= $caraKeluarValue === (string) $option ? 'selected' : '' ?>>
                                <?= \App\Helpers\App::e((string) $option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="ket_keluar">Keterangan Cara Keluar</label>
                    <input class="form-control" id="ket_keluar" name="ket_keluar" value="<?= \App\Helpers\App::e($getValue('ket_keluar')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="keadaan">Keadaan Pulang</label>
                    <?php $keadaanValue = $getValue('keadaan') !== '' ? $getValue('keadaan') : (string) ($keadaanOptions[0] ?? ''); ?>
                    <select class="form-select" id="keadaan" name="keadaan" required>
                        <?php foreach ($keadaanOptions as $option): ?>
                            <option value="<?= \App\Helpers\App::e((string) $option) ?>" <?= $keadaanValue === (string) $option ? 'selected' : '' ?>>
                                <?= \App\Helpers\App::e((string) $option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="ket_keadaan">Keterangan Keadaan</label>
                    <input class="form-control" id="ket_keadaan" name="ket_keadaan" value="<?= \App\Helpers\App::e($getValue('ket_keadaan')) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="dilanjutkan">Dilanjutkan</label>
                    <?php $dilanjutkanValue = $getValue('dilanjutkan') !== '' ? $getValue('dilanjutkan') : (string) ($dilanjutkanOptions[0] ?? ''); ?>
                    <select class="form-select" id="dilanjutkan" name="dilanjutkan" required>
                        <?php foreach ($dilanjutkanOptions as $option): ?>
                            <option value="<?= \App\Helpers\App::e((string) $option) ?>" <?= $dilanjutkanValue === (string) $option ? 'selected' : '' ?>>
                                <?= \App\Helpers\App::e((string) $option) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="ket_dilanjutkan">Keterangan Dilanjutkan</label>
                    <input class="form-control" id="ket_dilanjutkan" name="ket_dilanjutkan" value="<?= \App\Helpers\App::e($getValue('ket_dilanjutkan')) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="kontrol_tanggal">Kontrol</label>
                    <div class="row g-2">
                        <div class="col-7">
                            <input type="text" class="form-control" id="kontrol_tanggal" name="kontrol_tanggal" value="<?= \App\Helpers\App::e($kontrolDateValue) ?>" placeholder="DD/MM/YYYY" inputmode="numeric" pattern="^([0-2][0-9]|3[0-1])/(0[1-9]|1[0-2])/[0-9]{4}$" title="Gunakan format DD/MM/YYYY">
                        </div>
                        <div class="col-5">
                            <input type="text" class="form-control" id="kontrol_jam" name="kontrol_jam" value="<?= \App\Helpers\App::e($kontrolTimeValue) ?>" placeholder="HH:mm" inputmode="numeric" pattern="^([01][0-9]|2[0-3]):[0-5][0-9]$" title="Gunakan format 24 jam HH:mm">
                        </div>
                    </div>
                    <input type="hidden" id="kontrol" name="kontrol" value="<?= \App\Helpers\App::e($kontrolInputValue) ?>">
                    <small class="text-secondary">Format tanggal DD/MM/YYYY dan jam 24 jam (HH:mm).</small>
                </div>
                <div class="col-md-8">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <label class="form-label mb-0" for="obat_pulang">Obat Pulang</label>
                        <button type="button" class="btn btn-outline-secondary btn-sm js-open-medicine-modal" data-source="obat_pulang" data-target="obat_pulang" data-title="Pilih Obat Pulang" title="Pilih dari daftar obat">
                            <i class="bi bi-list-check"></i>
                        </button>
                    </div>
                    <textarea class="form-control" id="obat_pulang" name="obat_pulang" rows="2"><?= \App\Helpers\App::e($getValue('obat_pulang')) ?></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 flex-wrap">
                <a class="btn btn-outline-secondary" href="<?= \App\Helpers\App::e($backUrl) ?>">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Resume</button>
            </div>
        </div>
    </form>

    <div class="modal fade" id="medicinePickerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title fs-6" id="medicinePickerTitle">Pilih Obat</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 align-items-center mb-3">
                        <div class="col-md-8">
                            <input type="search" class="form-control" id="medicinePickerSearch" placeholder="Cari tanggal atau nama obat">
                        </div>
                        <div class="col-md-4 d-grid">
                            <button type="button" class="btn btn-outline-primary" id="medicinePickerSearchBtn">Cari</button>
                        </div>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="medicinePickerSelectAll">
                        <label class="form-check-label" for="medicinePickerSelectAll">Pilih Semua</label>
                    </div>
                    <div id="medicinePickerFeedback" class="small text-secondary mb-2"></div>
                    <div class="border rounded p-2" style="max-height: 360px; overflow: auto;">
                        <div id="medicinePickerList" class="d-flex flex-column gap-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="medicinePickerApply">Pakai Pilihan</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="riwayatModal" tabindex="-1" aria-labelledby="riwayatModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable riwayat-modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title fs-6" id="riwayatModalTitle">Riwayat Perawatan Pasien</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-0 riwayat-modal-body">
                    <div id="riwayatModalLoading" class="riwayat-modal-loading">
                        <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
                        <span>Memuat riwayat pasien...</span>
                    </div>
                    <div id="riwayatModalFallback" class="alert alert-warning m-3 d-none" role="alert">
                        <div class="fw-semibold mb-1">Riwayat belum berhasil dimuat di modal.</div>
                        <div class="small">Silakan buka di tab baru jika loading terlalu lama.</div>
                        <a id="riwayatModalOpenTab" class="btn btn-sm btn-outline-primary mt-2" target="_blank" rel="noopener">Buka Riwayat di Tab Baru</a>
                    </div>
                    <div id="riwayatModalContent" class="riwayat-modal-content-area d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(() => {
    const noRawat = <?= json_encode($noRawat, JSON_UNESCAPED_UNICODE) ?>;
    const autofillUrl = <?= json_encode($autofillUrl, JSON_UNESCAPED_UNICODE) ?>;
    const medicineOptionsUrl = <?= json_encode($medicineOptionsUrl, JSON_UNESCAPED_UNICODE) ?>;
    const riwayatModalUrl = <?= json_encode($riwayatModalUrl, JSON_UNESCAPED_UNICODE) ?>;

    if (!noRawat) {
        return;
    }

    const resumeForm = document.getElementById('rawatInapResumeForm');
    const kontrolInput = document.getElementById('kontrol');
    const kontrolTanggalInput = document.getElementById('kontrol_tanggal');
    const kontrolJamInput = document.getElementById('kontrol_jam');

    const fieldButtons = document.querySelectorAll('.js-autofill-field');
    const medicineButtons = document.querySelectorAll('.js-open-medicine-modal');
    const riwayatButton = document.querySelector('.js-open-riwayat-modal');

    const medicineModalElement = document.getElementById('medicinePickerModal');
    const medicineTitle = document.getElementById('medicinePickerTitle');
    const medicineSearch = document.getElementById('medicinePickerSearch');
    const medicineSearchBtn = document.getElementById('medicinePickerSearchBtn');
    const medicineSelectAll = document.getElementById('medicinePickerSelectAll');
    const medicineList = document.getElementById('medicinePickerList');
    const medicineFeedback = document.getElementById('medicinePickerFeedback');
    const medicineApplyBtn = document.getElementById('medicinePickerApply');
    const riwayatModalElement = document.getElementById('riwayatModal');
    const riwayatModalContent = document.getElementById('riwayatModalContent');
    const riwayatLoading = document.getElementById('riwayatModalLoading');
    const riwayatFallback = document.getElementById('riwayatModalFallback');
    const riwayatOpenTab = document.getElementById('riwayatModalOpenTab');

    let medicineModal = null;
    let riwayatModal = null;
    let riwayatLoadingTimer = null;
    let riwayatAbortController = null;
    let activeMedicineSource = '';
    let activeMedicineTarget = '';

    const getModalInstance = () => {
        if (!medicineModalElement || typeof window.bootstrap === 'undefined') {
            return null;
        }
        if (!medicineModal) {
            medicineModal = new window.bootstrap.Modal(medicineModalElement);
        }
        return medicineModal;
    };

    const getRiwayatModalInstance = () => {
        if (!riwayatModalElement || typeof window.bootstrap === 'undefined') {
            return null;
        }
        if (!riwayatModal) {
            riwayatModal = new window.bootstrap.Modal(riwayatModalElement);
        }
        return riwayatModal;
    };

    const setRiwayatLoading = (loading) => {
        if (riwayatLoading) {
            riwayatLoading.classList.toggle('d-none', !loading);
        }
        if (riwayatFallback && loading) {
            riwayatFallback.classList.add('d-none');
        }
        if (riwayatModalContent) {
            riwayatModalContent.classList.toggle('d-none', loading);
        }
    };

    const clearRiwayatLoadingTimer = () => {
        if (!riwayatLoadingTimer) {
            return;
        }
        window.clearTimeout(riwayatLoadingTimer);
        riwayatLoadingTimer = null;
    };

    const showRiwayatFallback = () => {
        if (riwayatFallback) {
            riwayatFallback.classList.remove('d-none');
        }
        setRiwayatLoading(false);
    };

    const stopRiwayatRequest = () => {
        if (!riwayatAbortController) {
            return;
        }
        riwayatAbortController.abort();
        riwayatAbortController = null;
    };

    const executeScripts = (container) => {
        const scripts = container.querySelectorAll('script');
        scripts.forEach((oldScript) => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach((attribute) => {
                newScript.setAttribute(attribute.name, attribute.value);
            });
            newScript.text = oldScript.text;
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    };

    const initRiwayatSectionControls = (container) => {
        if (!container) {
            return;
        }

        const selectAll = container.querySelector('#select_all');
        const uncheckAll = container.querySelector('#uncheck_all');
        const sectionChecks = Array.from(container.querySelectorAll('.section-check'));
        const selectAllHidden = container.querySelector('#select_all_hidden');
        const sectionForm = container.querySelector('#section-form');
        const menuToggle = container.querySelector('#menu_section_toggle');
        const menuContent = container.querySelector('#menu_section_content');
        const riwayatBody = container.querySelector('#riwayat_body');
        const riwayatSidebar = container.querySelector('#riwayat_sidebar');

        if (!selectAll || !selectAllHidden || !sectionForm) {
            return;
        }

        const syncSelectAllState = () => {
            if (sectionChecks.length === 0) {
                return;
            }

            const checkedCount = sectionChecks.reduce((count, checkbox) => {
                return count + (checkbox.checked ? 1 : 0);
            }, 0);

            selectAll.checked = checkedCount === sectionChecks.length;
            selectAllHidden.value = selectAll.checked ? '1' : '0';
        };

        selectAll.addEventListener('change', () => {
            const checked = selectAll.checked;
            selectAllHidden.value = checked ? '1' : '0';
            sectionChecks.forEach((checkbox) => {
                checkbox.checked = checked;
            });
        });

        if (uncheckAll) {
            uncheckAll.addEventListener('click', () => {
                sectionChecks.forEach((checkbox) => {
                    checkbox.checked = false;
                });
                selectAll.checked = false;
                selectAllHidden.value = '0';
            });
        }

        sectionChecks.forEach((checkbox) => {
            checkbox.addEventListener('change', syncSelectAllState);
        });

        sectionForm.addEventListener('submit', () => {
            syncSelectAllState();
        });

        syncSelectAllState();

        if (menuToggle && menuContent && riwayatBody && riwayatSidebar) {
            menuToggle.addEventListener('click', () => {
                const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
                const nextExpanded = !expanded;
                menuToggle.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
                riwayatBody.classList.toggle('riwayat-sidebar-collapsed', !nextExpanded);
                riwayatSidebar.classList.toggle('is-collapsed', !nextExpanded);
                menuToggle.setAttribute('title', nextExpanded ? 'Sembunyikan ke kiri' : 'Tampilkan ke kanan');
                const icon = menuToggle.querySelector('i');
                if (icon) {
                    icon.className = nextExpanded ? 'bi bi-chevron-left' : 'bi bi-chevron-right';
                }
            });
        }
    };

    const addRiwayatModalFormHandlers = () => {
        if (!riwayatModalContent) {
            return;
        }

        const forms = riwayatModalContent.querySelectorAll('form');
        forms.forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const formData = new FormData(form);
                const params = new URLSearchParams();
                formData.forEach((value, key) => {
                    params.append(String(key), String(value));
                });
                loadRiwayatModal(form.action + '?' + params.toString());
            });
        });
    };

    const loadRiwayatModal = async (url) => {
        if (!riwayatModalContent) {
            return;
        }

        stopRiwayatRequest();
        setRiwayatLoading(true);
        clearRiwayatLoadingTimer();
        riwayatLoadingTimer = window.setTimeout(() => {
            showRiwayatFallback();
        }, 12000);

        riwayatAbortController = new AbortController();

        try {
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'text/html' },
                signal: riwayatAbortController.signal,
            });

            if (!response.ok) {
                throw new Error('Riwayat gagal dimuat.');
            }

            const html = await response.text();
            const parser = new DOMParser();
            const documentParsed = parser.parseFromString(html, 'text/html');
            const section = documentParsed.querySelector('.riwayat-page');
            if (!section) {
                throw new Error('Konten riwayat tidak ditemukan.');
            }

            riwayatModalContent.innerHTML = section.outerHTML;
            executeScripts(riwayatModalContent);
            initRiwayatSectionControls(riwayatModalContent);
            addRiwayatModalFormHandlers();

            clearRiwayatLoadingTimer();
            if (riwayatFallback) {
                riwayatFallback.classList.add('d-none');
            }
            setRiwayatLoading(false);
        } catch (error) {
            if (error && error.name === 'AbortError') {
                return;
            }
            clearRiwayatLoadingTimer();
            showRiwayatFallback();
        } finally {
            riwayatAbortController = null;
        }
    };

    const pad2 = (value) => String(value).padStart(2, '0');

    const buildKontrolValue = () => {
        if (!kontrolInput || !kontrolTanggalInput || !kontrolJamInput) {
            return;
        }

        const dateText = (kontrolTanggalInput.value || '').trim();
        const timeText = (kontrolJamInput.value || '').trim();
        if (dateText === '' && timeText === '') {
            kontrolInput.value = '';
            kontrolTanggalInput.setCustomValidity('');
            return;
        }

        const dateMatch = dateText.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        const timeMatch = timeText.match(/^([01][0-9]|2[0-3]):[0-5][0-9]$/);
        if (!dateMatch || !timeMatch) {
            kontrolInput.value = '';
            kontrolTanggalInput.setCustomValidity('Gunakan format tanggal DD/MM/YYYY dan isi jam kontrol.');
            kontrolJamInput.setCustomValidity('Gunakan format jam 24 jam HH:mm.');
            return;
        }

        const day = Number(dateMatch[1]);
        const month = Number(dateMatch[2]);
        const year = Number(dateMatch[3]);
        const testDate = new Date(year, month - 1, day);
        if (
            Number.isNaN(testDate.getTime())
            || testDate.getFullYear() !== year
            || (testDate.getMonth() + 1) !== month
            || testDate.getDate() !== day
        ) {
            kontrolInput.value = '';
            kontrolTanggalInput.setCustomValidity('Tanggal kontrol tidak valid.');
            kontrolJamInput.setCustomValidity('');
            return;
        }

        kontrolTanggalInput.setCustomValidity('');
        kontrolJamInput.setCustomValidity('');
        kontrolInput.value = year + '-' + pad2(month) + '-' + pad2(day) + 'T' + timeText;
    };

    if (kontrolTanggalInput) {
        kontrolTanggalInput.addEventListener('input', buildKontrolValue);
        kontrolTanggalInput.addEventListener('blur', buildKontrolValue);
    }

    if (kontrolJamInput) {
        kontrolJamInput.addEventListener('input', buildKontrolValue);
    }

    if (resumeForm) {
        resumeForm.addEventListener('submit', () => {
            buildKontrolValue();
        });
    }

    buildKontrolValue();

    const setFieldButtonLoading = (button, loading) => {
        if (!button) {
            return;
        }
        button.disabled = loading;
        button.classList.toggle('disabled', loading);
    };

    const fetchAutofill = async (field, button) => {
        const params = new URLSearchParams({
            no_rawat: noRawat,
            field,
        });

        setFieldButtonLoading(button, true);
        try {
            const response = await fetch(autofillUrl + '?' + params.toString(), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                throw new Error(payload.message || 'Gagal mengambil data autofill.');
            }
            return String(payload.text || '');
        } finally {
            setFieldButtonLoading(button, false);
        }
    };

    fieldButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const field = button.getAttribute('data-field') || '';
            const targetId = button.getAttribute('data-target') || '';
            const target = document.getElementById(targetId);
            if (!field || !target) {
                return;
            }

            try {
                const text = await fetchAutofill(field, button);
                target.value = text;
            } catch (error) {
                alert((error && error.message) ? error.message : 'Gagal mengambil data autofill.');
            }
        });
    });

    const renderMedicineOptions = (items) => {
        medicineList.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            medicineFeedback.textContent = 'Data obat tidak ditemukan.';
            medicineSelectAll.checked = false;
            return;
        }

        medicineFeedback.textContent = 'Total data: ' + String(items.length);
        items.forEach((item, index) => {
            const rowId = 'medicinePickerItem' + String(index);
            const wrapper = document.createElement('div');
            wrapper.className = 'form-check';

            const input = document.createElement('input');
            input.className = 'form-check-input js-medicine-item';
            input.type = 'checkbox';
            input.id = rowId;
            input.value = String(item.label || '');

            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = rowId;
            label.textContent = String(item.label || '');

            wrapper.appendChild(input);
            wrapper.appendChild(label);
            medicineList.appendChild(wrapper);
        });
        medicineSelectAll.checked = false;
    };

    const loadMedicineOptions = async () => {
        if (!activeMedicineSource) {
            return;
        }

        medicineFeedback.textContent = 'Memuat data...';
        medicineList.innerHTML = '';

        const params = new URLSearchParams({
            no_rawat: noRawat,
            source: activeMedicineSource,
            q: medicineSearch.value || '',
        });

        try {
            const response = await fetch(medicineOptionsUrl + '?' + params.toString(), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json();
            if (!response.ok || !payload.ok) {
                throw new Error(payload.message || 'Gagal mengambil daftar obat.');
            }
            renderMedicineOptions(payload.items || []);
        } catch (error) {
            medicineFeedback.textContent = '';
            alert((error && error.message) ? error.message : 'Gagal mengambil daftar obat.');
        }
    };

    medicineButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            activeMedicineSource = button.getAttribute('data-source') || '';
            activeMedicineTarget = button.getAttribute('data-target') || '';
            const title = button.getAttribute('data-title') || 'Pilih Obat';
            medicineTitle.textContent = title;
            medicineSearch.value = '';
            await loadMedicineOptions();

            const modal = getModalInstance();
            if (modal) {
                modal.show();
            }
        });
    });

    medicineSearchBtn.addEventListener('click', loadMedicineOptions);
    medicineSearch.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            loadMedicineOptions();
        }
    });

    medicineSelectAll.addEventListener('change', () => {
        const checkboxes = medicineList.querySelectorAll('.js-medicine-item');
        checkboxes.forEach((checkbox) => {
            checkbox.checked = medicineSelectAll.checked;
        });
    });

    medicineList.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement) || !target.classList.contains('js-medicine-item')) {
            return;
        }

        const checkboxes = Array.from(medicineList.querySelectorAll('.js-medicine-item'));
        if (checkboxes.length === 0) {
            medicineSelectAll.checked = false;
            return;
        }

        medicineSelectAll.checked = checkboxes.every((checkbox) => checkbox.checked);
    });

    medicineApplyBtn.addEventListener('click', () => {
        const target = document.getElementById(activeMedicineTarget);
        if (!target) {
            return;
        }

        const selected = Array.from(medicineList.querySelectorAll('.js-medicine-item:checked'))
            .map((checkbox) => checkbox.value)
            .filter((value) => value !== '');

        target.value = selected.join(', ');

        const modal = getModalInstance();
        if (modal) {
            modal.hide();
        }
    });

    if (riwayatModalElement) {
        riwayatModalElement.addEventListener('hidden.bs.modal', () => {
            clearRiwayatLoadingTimer();
            stopRiwayatRequest();
            if (riwayatFallback) {
                riwayatFallback.classList.add('d-none');
            }
        });
    }

    if (riwayatButton) {
        riwayatButton.addEventListener('click', () => {
            if (!riwayatModalUrl) {
                alert('No. RM pasien tidak ditemukan.');
                return;
            }

            if (riwayatOpenTab) {
                riwayatOpenTab.href = riwayatModalUrl;
            }

            const modal = getRiwayatModalInstance();
            if (modal) {
                modal.show();
            }

            loadRiwayatModal(riwayatModalUrl);
        });
    }
})();
</script>
