<?php
$selectedDoctorId = (string) ($selectedDoctorId ?? '');
$doctorOptions = is_array($doctorOptions ?? null) ? $doctorOptions : [];
$recentResumes = is_array($recentResumes ?? null) ? $recentResumes : [];
$dischargedWithoutResume = is_array($dischargedWithoutResume ?? null) ? $dischargedWithoutResume : [];
$dischargedWithoutResumeTotal = max(0, (int) ($dischargedWithoutResumeTotal ?? 0));
$loginAt = (string) ($loginAt ?? '');

$dashboardPath = (string) call_user_func($routePath, '/it/dashboard');
$rawatInapPath = (string) call_user_func($routePath, '/it/rawat-inap');
$resumePath = (string) call_user_func($routePath, '/it/rawat-inap/resume');

$getField = static function ($row, string $key): string {
    if ($row instanceof \flight\util\Collection) {
        $data = $row->getData();
        return isset($data[$key]) ? (string) $data[$key] : '';
    }

    if (is_array($row)) {
        return isset($row[$key]) ? (string) $row[$key] : '';
    }

    if (is_object($row) && isset($row->{$key})) {
        return (string) $row->{$key};
    }

    return '';
};
?>

<section class="page-card">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1"><?= \App\Helpers\App::e((string) ($pageTitle ?? 'IT Dashboard')) ?></h2>
                    <p class="text-secondary mb-0"><?= \App\Helpers\App::e((string) ($description ?? '')) ?></p>
                </div>
                <span class="badge text-bg-dark">IT</span>
            </div>

            <form method="get" action="<?= \App\Helpers\App::e($dashboardPath) ?>" class="row g-2 align-items-end">
                <div class="col-sm-8 col-md-6 col-lg-4">
                    <label for="doctorId" class="form-label small text-secondary mb-1">Filter Dokter</label>
                    <select class="form-select" id="doctorId" name="doctorId">
                        <option value="">Semua Dokter</option>
                        <?php foreach ($doctorOptions as $doctor): ?>
                            <?php
                            $doctorCode = trim((string) ($doctor['kd_dokter'] ?? ''));
                            $doctorName = trim((string) ($doctor['nm_dokter'] ?? ''));
                            ?>
                            <option value="<?= \App\Helpers\App::e($doctorCode) ?>" <?= $selectedDoctorId === $doctorCode ? 'selected' : '' ?>>
                                <?= \App\Helpers\App::e($doctorCode . ' - ' . ($doctorName !== '' ? $doctorName : '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                </div>
                <div class="col-auto">
                    <a href="<?= \App\Helpers\App::e($dashboardPath) ?>" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>

            <div class="row g-3 mt-1">
                <div class="col-12 col-md-6">
                    <div class="stat-box h-100">
                        <small class="text-uppercase text-secondary">Login Timestamp</small>
                        <h3 class="h6 mt-2 mb-0"><?= \App\Helpers\App::e($loginAt !== '' ? $loginAt : '-') ?></h3>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="stat-box h-100">
                        <small class="text-uppercase text-secondary">Sudah Pulang, Belum Resume</small>
                        <h3 class="h4 mt-2 mb-0"><?= number_format($dischargedWithoutResumeTotal, 0, ',', '.') ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3 class="h6 mb-0">Aktivitas Input Resume (Terbaru)</h3>
                        <a href="<?= \App\Helpers\App::e($rawatInapPath) ?>" class="btn btn-sm btn-outline-primary">Buka Rawat Inap</a>
                    </div>
                    <div class="table-responsive dashboard-table-wrap">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                            <tr>
                                <th>No. Rawat</th>
                                <th>Pasien</th>
                                <th>Dokter</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($recentResumes === []): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-secondary py-3">Belum ada data resume.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentResumes as $row): ?>
                                    <?php
                                    $noRawat = $getField($row, 'no_rawat');
                                    $pasien = $getField($row, 'nm_pasien');
                                    $doctorLabel = trim($getField($row, 'kd_dokter') . ' ' . $getField($row, 'nm_dokter'));
                                    $resumeUrl = $resumePath . '?' . http_build_query(['no_rawat' => $noRawat]);
                                    ?>
                                    <tr>
                                        <td><a href="<?= \App\Helpers\App::e($resumeUrl) ?>"><?= \App\Helpers\App::e($noRawat !== '' ? $noRawat : '-') ?></a></td>
                                        <td><?= \App\Helpers\App::e($pasien !== '' ? $pasien : '-') ?></td>
                                        <td><?= \App\Helpers\App::e($doctorLabel !== '' ? $doctorLabel : '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2">Pasien Sudah Pulang, Resume Belum Ada</h3>
                    <div class="table-responsive dashboard-table-wrap">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                            <tr>
                                <th>No. Rawat</th>
                                <th>Pasien</th>
                                <th>Tgl/Jam Pulang</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if ($dischargedWithoutResume === []): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-secondary py-3">Tidak ada pasien sudah pulang tanpa resume.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dischargedWithoutResume as $row): ?>
                                    <?php
                                    $noRawat = $getField($row, 'no_rawat');
                                    $pasien = $getField($row, 'nm_pasien');
                                    $tglKeluar = trim($getField($row, 'tgl_keluar') . ' ' . $getField($row, 'jam_keluar'));
                                    $resumeUrl = $resumePath . '?' . http_build_query(['no_rawat' => $noRawat]);
                                    ?>
                                    <tr>
                                        <td><a href="<?= \App\Helpers\App::e($resumeUrl) ?>"><?= \App\Helpers\App::e($noRawat !== '' ? $noRawat : '-') ?></a></td>
                                        <td><?= \App\Helpers\App::e($pasien !== '' ? $pasien : '-') ?></td>
                                        <td><?= \App\Helpers\App::e($tglKeluar !== '' ? $tglKeluar : '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
