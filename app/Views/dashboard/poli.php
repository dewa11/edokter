<?php
$patients = is_array($patients ?? null) ? $patients : [];
$filters = is_array($filters ?? null) ? $filters : [];
$isItUser = (bool) ($isItUser ?? false);
$allDoctors = (bool) ($allDoctors ?? false);
$page = max(1, (int) ($page ?? 1));
$perPage = max(1, (int) ($perPage ?? 10));
$totalRows = max(0, (int) ($totalRows ?? 0));
$totalPages = max(1, (int) ($totalPages ?? 1));
$offset = max(0, (int) ($offset ?? 0));

$q = (string) ($filters['q'] ?? '');
$datePreset = (string) ($filters['datePreset'] ?? 'today');
$dateFrom = (string) ($filters['dateFrom'] ?? '');
$dateTo = (string) ($filters['dateTo'] ?? '');
$perPageFilter = (string) ($filters['perPage'] ?? '10');

$path = (string) call_user_func($routePath, '/poli');
$excelExportPath = (string) call_user_func($routePath, '/poli/export/excel');
$htmlExportPath = (string) call_user_func($routePath, '/poli/export/html');
$riwayatPath = (string) call_user_func($routePath, '/riwayat-perawatan');

$buildUrl = static function (array $overrides) use ($path, $q, $datePreset, $dateFrom, $dateTo, $perPageFilter, $isItUser, $allDoctors): string {
    $params = [
        'q' => $q,
        'datePreset' => $datePreset,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'perPage' => $perPageFilter,
    ];

    if ($isItUser) {
        $params['allDoctors'] = $allDoctors ? '1' : '0';
    }

    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
            continue;
        }

        $params[$key] = (string) $value;
    }

    return $path . '?' . http_build_query($params);
};

$buildExportUrl = static function (string $targetPath) use ($q, $datePreset, $dateFrom, $dateTo, $isItUser, $allDoctors): string {
    $params = [
        'q' => $q,
        'datePreset' => $datePreset,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
    ];

    if ($isItUser) {
        $params['allDoctors'] = $allDoctors ? '1' : '0';
    }

    return $targetPath . '?' . http_build_query($params);
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

$firstRow = $totalRows > 0 ? $offset + 1 : 0;
$lastRow = min($offset + count($patients), $totalRows);
?>

<section class="page-card poli-page">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1"><?= \App\Helpers\App::e((string) ($pageTitle ?? 'Poli')) ?></h2>
                    <p class="text-secondary mb-0"><?= \App\Helpers\App::e((string) ($description ?? 'Daftar pasien poli.')) ?></p>
                </div>
                <div class="text-secondary small">
                    Menampilkan <?= \App\Helpers\App::e((string) $firstRow) ?> - <?= \App\Helpers\App::e((string) $lastRow) ?> dari <?= \App\Helpers\App::e((string) $totalRows) ?> pasien
                </div>
            </div>

            <form method="get" action="<?= \App\Helpers\App::e($path) ?>" class="poli-filters">
                <input type="hidden" name="page" value="1">

                <div class="search-box input-group">
                    <input
                        type="text"
                        class="form-control"
                        name="q"
                        value="<?= \App\Helpers\App::e($q) ?>"
                        placeholder="Cari No, No RM, nama pasien, atau status poli"
                    >
                    <button class="btn btn-primary" type="submit" aria-label="Cari pasien">
                        <i class="bi bi-search"></i>
                    </button>
                </div>

                <div>
                    <label for="datePreset" class="form-label small text-secondary mb-1">Periode</label>
                    <select id="datePreset" name="datePreset" class="form-select">
                        <option value="today" <?= $datePreset === 'today' ? 'selected' : '' ?>>Hari Ini</option>
                        <option value="this_month" <?= $datePreset === 'this_month' ? 'selected' : '' ?>>Bulan Ini</option>
                        <option value="this_year" <?= $datePreset === 'this_year' ? 'selected' : '' ?>>Tahun Ini</option>
                        <option value="custom" <?= $datePreset === 'custom' ? 'selected' : '' ?>>Rentang Kustom</option>
                    </select>
                </div>

                <div>
                    <label for="dateFrom" class="form-label small text-secondary mb-1">Dari</label>
                    <input type="date" id="dateFrom" name="dateFrom" class="form-control" value="<?= \App\Helpers\App::e($dateFrom) ?>">
                </div>

                <div>
                    <label for="dateTo" class="form-label small text-secondary mb-1">Sampai</label>
                    <input type="date" id="dateTo" name="dateTo" class="form-control" value="<?= \App\Helpers\App::e($dateTo) ?>">
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

                <?php if ($isItUser): ?>
                    <div class="d-flex align-items-center gap-2 pt-4">
                        <input
                            class="form-check-input mt-0"
                            id="allDoctors"
                            type="checkbox"
                            name="allDoctors"
                            value="1"
                            <?= $allDoctors ? 'checked' : '' ?>
                        >
                        <label class="form-check-label" for="allDoctors">All Doctors</label>
                    </div>
                <?php endif; ?>

                <div class="pt-4">
                    <a class="btn btn-outline-secondary" href="<?= \App\Helpers\App::e($buildUrl(['q' => '', 'page' => '1'])) ?>">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive poli-table-wrap">
                <table class="table align-middle mb-0 poli-table">
                    <thead>
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">No RM</th>
                        <th scope="col">Pasien</th>
                        <th scope="col">Tgl Daftar</th>
                        <?php if ($isItUser): ?>
                            <th scope="col">Dokter</th>
                        <?php endif; ?>
                        <th scope="col">JK</th>
                        <th scope="col">Tgl Lahir</th>
                        <th scope="col">Status Poli</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($patients) === 0): ?>
                        <tr>
                            <td colspan="<?= $isItUser ? '8' : '7' ?>" class="text-center py-4 text-secondary">
                                Data pasien tidak ditemukan untuk filter yang dipilih.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($patients as $row): ?>
                            <?php
                            $noReg = $getField($row, 'no_reg');
                            $noRm = $getField($row, 'no_rkm_medis');
                            $nmPasien = $getField($row, 'nm_pasien');
                            $tglDaftar = $getField($row, 'tgl_registrasi');
                            $tglDaftarDisplay = $tglDaftar;
                            if ($tglDaftar !== '') {
                                $tglDaftarDate = \DateTimeImmutable::createFromFormat('Y-m-d', $tglDaftar);
                                if ($tglDaftarDate !== false) {
                                    $tglDaftarDisplay = $tglDaftarDate->format('d-m-Y');
                                }
                            }
                            $nmDokter = $getField($row, 'nm_dokter');
                            $jk = strtoupper($getField($row, 'jk'));
                            $tglLahir = $getField($row, 'tgl_lahir');
                            $tglLahirDisplay = $tglLahir;
                            if ($tglLahir !== '') {
                                $tglLahirDate = \DateTimeImmutable::createFromFormat('Y-m-d', $tglLahir);
                                if ($tglLahirDate !== false) {
                                    $tglLahirDisplay = $tglLahirDate->format('d-m-Y');
                                }
                            }
                            $statusPoli = $getField($row, 'stts');
                            $riwayatUrl = $riwayatPath . '?' . http_build_query([
                                'no_rkm_medis' => $noRm,
                                'mode' => 'r1',
                            ]);

                            $jkIcon = 'bi-gender-ambiguous';
                            $jkLabel = '-';
                            $jkClass = 'text-secondary';
                            if ($jk === 'L') {
                                $jkIcon = 'bi-gender-male';
                                $jkLabel = 'Laki-laki';
                                $jkClass = 'text-primary';
                            } elseif ($jk === 'P') {
                                $jkIcon = 'bi-gender-female';
                                $jkLabel = 'Perempuan';
                                $jkClass = 'text-danger';
                            }
                            ?>
                            <tr class="poli-clickable-row" data-riwayat-url="<?= \App\Helpers\App::e($riwayatUrl) ?>" tabindex="0" role="link">
                                <td><?= \App\Helpers\App::e($noReg) ?></td>
                                <td>
                                    <a class="poli-row-link" href="<?= \App\Helpers\App::e($riwayatUrl) ?>" target="_blank" rel="noopener">
                                        <?= \App\Helpers\App::e($noRm) ?>
                                    </a>
                                </td>
                                <td>
                                    <a class="poli-row-link" href="<?= \App\Helpers\App::e($riwayatUrl) ?>" target="_blank" rel="noopener">
                                        <?= \App\Helpers\App::e($nmPasien) ?>
                                    </a>
                                </td>
                                <td><?= \App\Helpers\App::e($tglDaftarDisplay) ?></td>
                                <?php if ($isItUser): ?>
                                    <td><?= \App\Helpers\App::e($nmDokter) ?></td>
                                <?php endif; ?>
                                <td>
                                    <span class="jk-badge <?= \App\Helpers\App::e($jkClass) ?>">
                                        <i class="bi <?= \App\Helpers\App::e($jkIcon) ?>"></i>
                                        <?= \App\Helpers\App::e($jkLabel) ?>
                                    </span>
                                </td>
                                <td><?= \App\Helpers\App::e($tglLahirDisplay) ?></td>
                                <td>
                                    <span class="badge text-bg-light border"><?= \App\Helpers\App::e($statusPoli) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
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
                <nav aria-label="Poli pagination">
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

        <div class="d-flex align-items-center gap-2">
            <a
                class="btn btn-outline-success btn-sm export-icon-btn"
                href="<?= \App\Helpers\App::e($buildExportUrl($excelExportPath)) ?>"
                title="Export Excel"
                aria-label="Export Excel"
            >
                <i class="bi bi-file-earmark-excel"></i>
            </a>
            <a
                class="btn btn-outline-dark btn-sm export-icon-btn"
                href="<?= \App\Helpers\App::e($buildExportUrl($htmlExportPath)) ?>"
                title="Export HTML"
                aria-label="Export HTML"
                target="_blank"
                rel="noopener"
            >
                <i class="bi bi-filetype-html"></i>
            </a>
        </div>
    </div>
</section>

<script>
(function () {
    var rows = document.querySelectorAll('.poli-clickable-row');

    function openRowUrl(row) {
        var url = row.getAttribute('data-riwayat-url');
        if (!url) {
            return;
        }

        window.open(url, '_blank', 'noopener');
    }

    rows.forEach(function (row) {
        row.addEventListener('click', function (event) {
            var target = event.target;

            if (target instanceof HTMLElement && target.closest('a, button, input, select, textarea, label')) {
                return;
            }

            openRowUrl(row);
        });

        row.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            openRowUrl(row);
        });
    });
})();
</script>
