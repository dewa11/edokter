<section class="page-card">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-2"><?= \App\Helpers\App::e((string) ($pageTitle ?? 'Dashboard')) ?></h2>
            <p class="text-secondary mb-4"><?= \App\Helpers\App::e((string) ($description ?? '')) ?></p>

            <div class="row g-3">
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="stat-box">
                        <small>Total Pasien</small>
                        <h3><?= number_format((int) ($totalPatients ?? 0), 0, ',', '.') ?></h3>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="stat-box">
                        <small>Rawat Inap Aktif</small>
                        <h3><?= number_format((int) ($rawatInapActive ?? 0), 0, ',', '.') ?></h3>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="stat-box">
                        <small>Poli Hari Ini</small>
                        <h3><?= number_format((int) ($poliTodayTotal ?? 0), 0, ',', '.') ?></h3>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-12 col-xl-4">
                    <div class="stat-box h-100">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <small class="text-uppercase text-secondary">Data User Saat Ini</small>
                                <h3 class="h6 mb-1 mt-2"><?= \App\Helpers\App::e((string) (($doctorName ?? '') !== '' ? $doctorName : '-')) ?></h3>
                                <p class="mb-1"><strong>ID Dokter:</strong> <?= \App\Helpers\App::e((string) (($doctorId ?? '') !== '' ? $doctorId : '-')) ?></p>
                                <p class="mb-1"><strong>Tanggal:</strong> <?= \App\Helpers\App::e((string) ($todayDate ?? '-')) ?></p>
                                <p class="mb-0"><strong>Role:</strong> <?= \App\Helpers\App::e((string) (($isItUser ?? false) ? 'IT/Admin' : 'Dokter')) ?></p>
                            </div>
                            <span class="badge text-bg-primary">Aktif</span>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="stat-box h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="h6 mb-0">Poli Hari Ini (per Poliklinik)</h3>
                            <span class="badge text-bg-light border">Total: <?= number_format((int) ($poliTodayTotal ?? 0), 0, ',', '.') ?></span>
                        </div>
                        <div class="table-responsive dashboard-table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>KD Poli</th>
                                    <th>Nama Poliklinik</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $poliGroups = is_array($poliTodayGroups ?? null) ? $poliTodayGroups : []; ?>
                                <?php if ($poliGroups === []) : ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-secondary py-3">Belum ada data poli hari ini.</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($poliGroups as $group) : ?>
                                        <tr>
                                            <td><?= \App\Helpers\App::e((string) ($group['kd_poli'] ?? '-')) ?></td>
                                            <td><?= \App\Helpers\App::e((string) ($group['nm_poli'] ?? '-')) ?></td>
                                            <td class="text-end fw-semibold"><?= number_format((int) ($group['total_pasien'] ?? 0), 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-12 col-xl-6">
                    <div class="stat-box h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="h6 mb-0">Rawat Inap Baru Hari Ini</h3>
                            <span class="badge text-bg-light border">Total: <?= number_format((int) ($rawatInapTodayTotal ?? 0), 0, ',', '.') ?></span>
                        </div>
                        <div class="table-responsive dashboard-table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>No. Rawat</th>
                                    <th>No. RM</th>
                                    <th>Pasien</th>
                                    <th>Kamar</th>
                                    <th>Masuk</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $recentRanap = is_array($rawatInapRecent ?? null) ? $rawatInapRecent : []; ?>
                                <?php if ($recentRanap === []) : ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary py-3">Belum ada pasien rawat inap baru hari ini.</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($recentRanap as $row) : ?>
                                        <tr>
                                            <td><?= \App\Helpers\App::e((string) ($row['no_rawat'] ?? '-')) ?></td>
                                            <td><?= \App\Helpers\App::e((string) ($row['no_rkm_medis'] ?? '-')) ?></td>
                                            <td><?= \App\Helpers\App::e((string) ($row['nm_pasien'] ?? '-')) ?></td>
                                            <td><?= \App\Helpers\App::e((string) ($row['kamar'] ?? '-')) ?></td>
                                            <td><?= \App\Helpers\App::e(trim((string) ($row['tgl_masuk'] ?? '') . ' ' . (string) ($row['jam_masuk'] ?? ''))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="stat-box h-100">
                        <h3 class="h6 mb-2">Jadwal Poli Dokter (Mingguan)</h3>
                        <div class="table-responsive dashboard-table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>Hari</th>
                                    <th>Poli</th>
                                    <th>Jam</th>
                                    <th class="text-end">Kuota</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $jadwalRows = is_array($jadwalWeekly ?? null) ? $jadwalWeekly : []; ?>
                                <?php if ($jadwalRows === []) : ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-secondary py-3">Jadwal dokter belum tersedia.</td>
                                    </tr>
                                <?php else : ?>
                                    <?php foreach ($jadwalRows as $row) : ?>
                                        <tr>
                                            <td><?= \App\Helpers\App::e((string) ($row['hari_kerja'] ?? '-')) ?></td>
                                            <td><?= \App\Helpers\App::e((string) ($row['nm_poli'] ?? '-')) ?></td>
                                            <td>
                                                <?= \App\Helpers\App::e((string) ($row['jam_mulai'] ?? '-')) ?>
                                                -
                                                <?= \App\Helpers\App::e((string) (($row['jam_selesai'] ?? '') !== '' ? $row['jam_selesai'] : '-')) ?>
                                            </td>
                                            <td class="text-end"><?= number_format((int) ($row['kuota'] ?? 0), 0, ',', '.') ?></td>
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
    </div>
</section>
