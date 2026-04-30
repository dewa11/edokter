<?php
$patients = is_array($patients ?? null) ? $patients : [];
$filters = is_array($filters ?? null) ? $filters : [];
$page = max(1, (int) ($page ?? 1));
$perPage = max(1, (int) ($perPage ?? 10));
$totalRows = max(0, (int) ($totalRows ?? 0));
$totalPages = max(1, (int) ($totalPages ?? 1));
$offset = max(0, (int) ($offset ?? 0));

$q = (string) ($filters['q'] ?? '');
$dateMode = (string) ($filters['dateMode'] ?? 'belum_pulang');
$dateFrom = (string) ($filters['dateFrom'] ?? '');
$dateTo = (string) ($filters['dateTo'] ?? '');
$resumeStatus = (string) ($filters['resumeStatus'] ?? 'all');
$perPageFilter = (string) ($filters['perPage'] ?? '10');
$doctorIdFilter = (string) ($filters['doctorId'] ?? '');
$doctorOptions = is_array($doctorOptions ?? null) ? $doctorOptions : [];
$flashError = (string) ($flashError ?? '');
$flashSuccess = (string) ($flashSuccess ?? '');

$path = (string) call_user_func($routePath, '/it/rawat-inap');
$riwayatPath = (string) call_user_func($routePath, '/riwayat-perawatan');
$resumePath = (string) call_user_func($routePath, '/it/rawat-inap/resume');

$buildUrl = static function (array $overrides) use ($path, $q, $dateMode, $dateFrom, $dateTo, $resumeStatus, $perPageFilter, $doctorIdFilter): string {
    $params = [
        'q' => $q,
        'dateMode' => $dateMode,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'resumeStatus' => $resumeStatus,
        'perPage' => $perPageFilter,
        'doctorId' => $doctorIdFilter,
    ];

    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
            continue;
        }

        $params[$key] = (string) $value;
    }

    return $path . '?' . http_build_query($params);
};

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

$formatDate = static function (string $dateValue): string {
    if ($dateValue === '' || $dateValue === '0000-00-00') {
        return '';
    }

    $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateValue);
    if ($date === false) {
        return $dateValue;
    }

    return $date->format('d-m-Y');
};

$formatDobWithAge = static function (string $dateValue): string {
    if ($dateValue === '' || $dateValue === '0000-00-00') {
        return '-';
    }

    $birthDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue);
    $errors = \DateTimeImmutable::getLastErrors();
    if ($birthDate === false || $errors['warning_count'] > 0 || $errors['error_count'] > 0) {
        return '-';
    }

    $today = new \DateTimeImmutable('today');
    if ($birthDate > $today) {
        return '-';
    }

    $age = $birthDate->diff($today)->y;

    return $birthDate->format('d-m-Y') . ' (' . $age . ' th)';
};

$firstRow = $totalRows > 0 ? $offset + 1 : 0;
$lastRow = min($offset + count($patients), $totalRows);
$dateInputsDisabled = $dateMode === 'belum_pulang';
?>

<section class="page-card rawat-inap-page">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1"><?= \App\Helpers\App::e((string) ($pageTitle ?? 'Rawat Inap')) ?></h2>
                    <p class="text-secondary mb-0"><?= \App\Helpers\App::e((string) ($description ?? 'Daftar pasien rawat inap.')) ?></p>
                </div>
                <div class="text-secondary small">
                    Menampilkan <?= \App\Helpers\App::e((string) $firstRow) ?> - <?= \App\Helpers\App::e((string) $lastRow) ?> dari <?= \App\Helpers\App::e((string) $totalRows) ?> pasien
                </div>
            </div>

            <?php if ($flashSuccess !== ''): ?>
                <div class="alert alert-success mb-3"><?= \App\Helpers\App::e($flashSuccess) ?></div>
            <?php endif; ?>

            <?php if ($flashError !== ''): ?>
                <div class="alert alert-danger mb-3"><?= \App\Helpers\App::e($flashError) ?></div>
            <?php endif; ?>

            <form method="get" action="<?= \App\Helpers\App::e($path) ?>" class="rawat-inap-filters" id="rawatInapFilters">
                <input type="hidden" name="page" value="1">

                <div class="search-box input-group">
                    <input
                        type="text"
                        class="form-control"
                        name="q"
                        value="<?= \App\Helpers\App::e($q) ?>"
                        placeholder="Cari Nama Pasien atau No RM"
                    >
                    <button class="btn btn-primary" type="submit" title="Search" aria-label="Search">
                        <i class="bi bi-search"></i>
                    </button>
                </div>

                <div>
                    <label for="dateMode" class="form-label small text-secondary mb-1">Filter Tanggal</label>
                    <select id="dateMode" name="dateMode" class="form-select">
                        <option value="belum_pulang" <?= $dateMode === 'belum_pulang' ? 'selected' : '' ?>>Belum Pulang</option>
                        <option value="tgl_masuk" <?= $dateMode === 'tgl_masuk' ? 'selected' : '' ?>>Tanggal Masuk</option>
                        <option value="tgl_pulang" <?= $dateMode === 'tgl_pulang' ? 'selected' : '' ?>>Tanggal Pulang</option>
                    </select>
                </div>

                <div>
                    <label for="dateFrom" class="form-label small text-secondary mb-1">Dari</label>
                    <input
                        type="date"
                        id="dateFrom"
                        name="dateFrom"
                        class="form-control"
                        value="<?= \App\Helpers\App::e($dateFrom) ?>"
                        <?= $dateInputsDisabled ? 'disabled' : '' ?>
                    >
                </div>

                <div>
                    <label for="dateTo" class="form-label small text-secondary mb-1">Sampai</label>
                    <input
                        type="date"
                        id="dateTo"
                        name="dateTo"
                        class="form-control"
                        value="<?= \App\Helpers\App::e($dateTo) ?>"
                        <?= $dateInputsDisabled ? 'disabled' : '' ?>
                    >
                </div>

                <div>
                    <label for="resumeStatus" class="form-label small text-secondary mb-1">Status Resume</label>
                    <select id="resumeStatus" name="resumeStatus" class="form-select">
                        <option value="all" <?= $resumeStatus === 'all' ? 'selected' : '' ?>>Semua</option>
                        <option value="sudah" <?= $resumeStatus === 'sudah' ? 'selected' : '' ?>>Sudah Ada</option>
                        <option value="belum" <?= $resumeStatus === 'belum' ? 'selected' : '' ?>>Belum Ada</option>
                    </select>
                </div>

                <div>
                    <label for="doctorId" class="form-label small text-secondary mb-1">Dokter DPJP</label>
                    <select id="doctorId" name="doctorId" class="form-select">
                        <option value="">Semua Dokter</option>
                        <?php foreach ($doctorOptions as $doctor): ?>
                            <?php
                            $doctorCode = trim((string) ($doctor['kd_dokter'] ?? ''));
                            $doctorName = trim((string) ($doctor['nm_dokter'] ?? ''));
                            ?>
                            <option value="<?= \App\Helpers\App::e($doctorCode) ?>" <?= $doctorIdFilter === $doctorCode ? 'selected' : '' ?>>
                                <?= \App\Helpers\App::e($doctorCode . ' - ' . ($doctorName !== '' ? $doctorName : '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="perPage" class="form-label small text-secondary mb-1">Per Halaman</label>
                    <select id="perPage" name="perPage" class="form-select">
                        <option value="10" <?= $perPageFilter === '10' ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $perPageFilter === '25' ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $perPageFilter === '50' ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $perPageFilter === '100' ? 'selected' : '' ?>>100</option>
                    </select>
                </div>

                <div class="pt-4">
                    <a
                        class="btn btn-outline-secondary"
                        href="<?= \App\Helpers\App::e($buildUrl(['q' => '', 'dateMode' => 'belum_pulang', 'dateFrom' => null, 'dateTo' => null, 'resumeStatus' => 'all', 'doctorId' => '', 'page' => '1'])) ?>"
                    >
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 rawat-inap-table">
                    <thead>
                    <tr>
                        <th scope="col">No Rawat</th>
                        <th scope="col">Resume</th>
                        <th scope="col">No RM</th>
                        <th scope="col">Nama Pasien</th>
                        <th scope="col">Tanggal Lahir (Age)</th>
                        <th scope="col">Kamar</th>
                        <th scope="col">Tanggal Masuk</th>
                        <th scope="col">Tanggal Pulang</th>
                        <th scope="col">Status Pulang</th>
                        <th scope="col">Penjamin</th>
                        <th scope="col">DPJP</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($patients) === 0): ?>
                        <tr>
                            <td colspan="11" class="text-center py-4 text-secondary">
                                Data pasien rawat inap tidak ditemukan untuk filter yang dipilih.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($patients as $row): ?>
                            <?php
                            $noRawat = $getField($row, 'no_rawat');
                            $noRm = $getField($row, 'no_rkm_medis');
                            $nmPasien = $getField($row, 'nm_pasien');
                            $tglLahirAge = $formatDobWithAge($getField($row, 'tgl_lahir'));
                            $kamar = $getField($row, 'kamar');
                            $tglMasuk = $formatDate($getField($row, 'tgl_masuk'));
                            $tglKeluar = $formatDate($getField($row, 'tgl_keluar'));
                            $statusPulang = $getField($row, 'stts_pulang');
                            $penjamin = $getField($row, 'penjamin');
                            $dpjp = $getField($row, 'dpjp');
                            $hasResume = $getField($row, 'has_resume_pasien_ranap') === '1';
                            $riwayatUrl = $riwayatPath . '?' . http_build_query([
                                'no_rkm_medis' => $noRm,
                                'mode' => 'r1',
                            ]);
                            $resumeUrl = $resumePath . '?' . http_build_query([
                                'no_rawat' => $noRawat,
                                'doctorId' => $doctorIdFilter,
                            ]);
                            ?>
                            <tr
                                class="rawat-inap-clickable-row"
                                data-no-rawat="<?= \App\Helpers\App::e($noRawat) ?>"
                                data-no-rm="<?= \App\Helpers\App::e($noRm) ?>"
                                data-nm-pasien="<?= \App\Helpers\App::e($nmPasien) ?>"
                                data-riwayat-url="<?= \App\Helpers\App::e($riwayatUrl) ?>"
                                data-resume-url="<?= \App\Helpers\App::e($resumeUrl) ?>"
                                tabindex="0"
                                role="button"
                            >
                                <td><?= \App\Helpers\App::e($noRawat) ?></td>
                                <td>
                                    <?php if ($hasResume): ?>
                                        <span class="badge text-bg-success">Sudah ada</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Belum ada</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= \App\Helpers\App::e($noRm) ?></td>
                                <td><?= \App\Helpers\App::e($nmPasien) ?></td>
                                <td><?= \App\Helpers\App::e($tglLahirAge) ?></td>
                                <td><?= \App\Helpers\App::e($kamar) ?></td>
                                <td><?= \App\Helpers\App::e($tglMasuk) ?></td>
                                <td><?= \App\Helpers\App::e($tglKeluar) ?></td>
                                <td>
                                    <span class="badge text-bg-light border"><?= \App\Helpers\App::e($statusPulang) ?></span>
                                </td>
                                <td><?= \App\Helpers\App::e($penjamin) ?></td>
                                <td><?= \App\Helpers\App::e($dpjp) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rawatInapActionModal" tabindex="-1" aria-labelledby="rawatInapActionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title fs-6" id="rawatInapActionModalLabel">Pilih Aksi Pasien</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1"><strong>No Rawat:</strong> <span id="rawatInapModalNoRawat">-</span></p>
                    <p class="mb-1"><strong>No RM:</strong> <span id="rawatInapModalNoRm">-</span></p>
                    <p class="mb-0"><strong>Pasien:</strong> <span id="rawatInapModalNama">-</span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="rawatInapModalResumeBtn">Resume</button>
                    <button type="button" class="btn btn-outline-primary" id="rawatInapModalRiwayatBtn">Riwayat Pasien</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
        <div>
            <?php if ($totalPages > 1): ?>
                <?php
                $windowStart = max(1, $page - 2);
                $windowEnd = min($totalPages, $page + 2);
                ?>
                <nav aria-label="Rawat inap pagination">
                    <ul class="pagination mb-0 flex-wrap gap-1">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= \App\Helpers\App::e($buildUrl(['page' => (string) max(1, $page - 1)])) ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>

                        <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= \App\Helpers\App::e($buildUrl(['page' => (string) $i])) ?>"><?= \App\Helpers\App::e((string) $i) ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= \App\Helpers\App::e($buildUrl(['page' => (string) min($totalPages, $page + 1)])) ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
(function () {
    var dateMode = document.getElementById('dateMode');
    var dateFrom = document.getElementById('dateFrom');
    var dateTo = document.getElementById('dateTo');
    var rows = document.querySelectorAll('.rawat-inap-clickable-row');
    var actionModalElement = document.getElementById('rawatInapActionModal');
    var resumeButton = document.getElementById('rawatInapModalResumeBtn');
    var riwayatButton = document.getElementById('rawatInapModalRiwayatBtn');
    var noRawatElement = document.getElementById('rawatInapModalNoRawat');
    var noRmElement = document.getElementById('rawatInapModalNoRm');
    var namaElement = document.getElementById('rawatInapModalNama');

    var actionModal = null;
    var selectedResumeUrl = '';
    var selectedRiwayatUrl = '';

    function getActionModal() {
        if (actionModal) {
            return actionModal;
        }

        if (!actionModalElement) {
            return null;
        }

        if (typeof window.bootstrap === 'undefined' || !window.bootstrap.Modal) {
            return null;
        }

        actionModal = new window.bootstrap.Modal(actionModalElement);
        return actionModal;
    }

    function updateDateInputState() {
        if (!dateMode || !dateFrom || !dateTo) {
            return;
        }

        var disabled = dateMode.value === 'belum_pulang';
        dateFrom.disabled = disabled;
        dateTo.disabled = disabled;
    }

    function openRowActions(row) {
        var modal = getActionModal();
        if (!modal) {
            return;
        }

        selectedResumeUrl = row.getAttribute('data-resume-url') || '';
        selectedRiwayatUrl = row.getAttribute('data-riwayat-url') || '';

        if (noRawatElement) {
            noRawatElement.textContent = row.getAttribute('data-no-rawat') || '-';
        }

        if (noRmElement) {
            noRmElement.textContent = row.getAttribute('data-no-rm') || '-';
        }

        if (namaElement) {
            namaElement.textContent = row.getAttribute('data-nm-pasien') || '-';
        }

        modal.show();
    }

    rows.forEach(function (row) {
        row.addEventListener('click', function (event) {
            var target = event.target;
            if (target instanceof HTMLElement && target.closest('a, button, input, select, textarea, label')) {
                return;
            }

            openRowActions(row);
        });

        row.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            openRowActions(row);
        });
    });

    if (resumeButton) {
        resumeButton.addEventListener('click', function () {
            if (selectedResumeUrl !== '') {
                window.location.href = selectedResumeUrl;
            }
        });
    }

    if (riwayatButton) {
        riwayatButton.addEventListener('click', function () {
            if (selectedRiwayatUrl !== '') {
                window.open(selectedRiwayatUrl, '_blank', 'noopener');
            }
        });
    }

    if (dateMode) {
        dateMode.addEventListener('change', updateDateInputState);
    }

    updateDateInputState();
})();
</script>
