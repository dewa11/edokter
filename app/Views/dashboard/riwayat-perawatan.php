<?php
$sectionsAll = is_array($sectionsAll ?? null) ? $sectionsAll : [];
$selectedSections = is_array($selectedSections ?? null) ? $selectedSections : [];
$errors = is_array($errors ?? null) ? $errors : [];
$patient = is_array($patient ?? null) ? $patient : null;
$visits = is_array($visits ?? null) ? $visits : [];
$encounters = is_array($encounters ?? null) ? $encounters : [];

$noRm = (string) ($noRm ?? '');
$mode = (string) ($mode ?? 'r1');
$tgl1 = (string) ($tgl1 ?? '');
$tgl2 = (string) ($tgl2 ?? '');
$noRawat = (string) ($noRawat ?? '');
$selectAll = (string) ($selectAll ?? '1');

$path = (string) call_user_func($routePath, '/riwayat-perawatan');

$money = static function (float $value): string {
    return number_format($value, 2, ',', '.');
};
?>

<section class="page-card riwayat-page">
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1"><?= \App\Helpers\App::e((string) ($pageTitle ?? 'Riwayat Perawatan')) ?></h2>
                    <p class="text-secondary mb-0"><?= \App\Helpers\App::e((string) ($description ?? 'Riwayat perawatan pasien berbasis No. RM.')) ?></p>
                </div>
            </div>

            <form method="get" action="<?= \App\Helpers\App::e($path) ?>" class="riwayat-filters">
                <div>
                    <label for="no_rkm_medis" class="form-label small text-secondary mb-1">No. RM</label>
                    <input id="no_rkm_medis" class="form-control" name="no_rkm_medis" value="<?= \App\Helpers\App::e($noRm) ?>" required>
                </div>

                <div>
                    <label for="mode" class="form-label small text-secondary mb-1">Mode</label>
                    <select id="mode" class="form-select" name="mode">
                        <option value="r1" <?= $mode === 'r1' ? 'selected' : '' ?>>5 Riwayat Terakhir</option>
                        <option value="r2" <?= $mode === 'r2' ? 'selected' : '' ?>>Semua Riwayat</option>
                        <option value="r3" <?= $mode === 'r3' ? 'selected' : '' ?>>Rentang Tanggal</option>
                    </select>
                </div>

                <div>
                    <label for="tgl1" class="form-label small text-secondary mb-1">Tgl 1</label>
                    <input id="tgl1" class="form-control" type="date" name="tgl1" value="<?= \App\Helpers\App::e($tgl1) ?>">
                </div>

                <div>
                    <label for="tgl2" class="form-label small text-secondary mb-1">Tgl 2</label>
                    <input id="tgl2" class="form-control" type="date" name="tgl2" value="<?= \App\Helpers\App::e($tgl2) ?>">
                </div>

                <div>
                    <button
                        class="btn btn-primary w-100 riwayat-icon-btn"
                        type="submit"
                        aria-label="Tampilkan"
                        title="Tampilkan"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                    >
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="riwayat-body" id="riwayat_body">
                <aside class="riwayat-sidebar" id="riwayat_sidebar">
                    <div class="riwayat-menu-header">
                        <h3 class="h6 mb-0">Menu Section</h3>
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm riwayat-menu-toggle"
                            id="menu_section_toggle"
                            aria-expanded="true"
                            aria-controls="menu_section_content"
                            title="Sembunyikan ke kiri"
                        >
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div id="menu_section_content" class="riwayat-menu-content">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <label class="form-check mb-0">
                                <input type="checkbox" class="form-check-input" id="select_all" <?= $selectAll === '1' ? 'checked' : '' ?>>
                                <span class="form-check-label">Pilih Semua</span>
                            </label>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="uncheck_all">Batal Pilih Semua</button>
                        </div>

                        <form id="section-form" method="get" action="<?= \App\Helpers\App::e($path) ?>">
                        <input type="hidden" name="no_rkm_medis" value="<?= \App\Helpers\App::e($noRm) ?>">
                        <input type="hidden" name="mode" value="<?= \App\Helpers\App::e($mode) ?>">
                        <input type="hidden" name="tgl1" value="<?= \App\Helpers\App::e($tgl1) ?>">
                        <input type="hidden" name="tgl2" value="<?= \App\Helpers\App::e($tgl2) ?>">
                        <input type="hidden" name="select_all" id="select_all_hidden" value="<?= \App\Helpers\App::e($selectAll) ?>">

                        <?php foreach ($sectionsAll as $key => $label): ?>
                            <label class="form-check mb-2">
                                <input
                                    type="checkbox"
                                    class="form-check-input section-check"
                                    name="sec_<?= \App\Helpers\App::e((string) $key) ?>"
                                    value="1"
                                    <?= isset($selectedSections[$key]) ? 'checked' : '' ?>
                                >
                                <span class="form-check-label"><?= \App\Helpers\App::e((string) $label) ?></span>
                            </label>
                        <?php endforeach; ?>

                            <button class="btn btn-outline-primary btn-sm mt-2" id="apply_menu_button" type="submit">Apply Menu</button>
                        </form>
                    </div>
                </aside>

                <div class="riwayat-content">
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-danger py-2">
                            Error DB: <?= \App\Helpers\App::e((string) $error) ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($noRm === ''): ?>
                        <div class="text-secondary">Masukkan No. RM lalu klik Tampilkan.</div>
                    <?php else: ?>
                        <div class="card border mb-3">
                            <div class="card-header bg-light fw-semibold">Data Pasien</div>
                            <div class="card-body p-0">
                                <?php if ($patient === null): ?>
                                    <div class="p-3 text-secondary">Pasien tidak ditemukan.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <tr><th width="22%">No RM</th><td><?= \App\Helpers\App::e((string) ($patient['no_rkm_medis'] ?? '')) ?></td></tr>
                                            <tr><th>Nama Pasien</th><td><?= \App\Helpers\App::e((string) ($patient['nm_pasien'] ?? '')) ?></td></tr>
                                            <tr><th>JK</th><td><?= \App\Helpers\App::e((string) ($patient['jk'] ?? '')) ?></td></tr>
                                            <tr><th>TTL</th><td><?= \App\Helpers\App::e((string) ($patient['tmp_lahir'] ?? '')) ?>, <?= \App\Helpers\App::e((string) ($patient['tgl_lahir'] ?? '')) ?></td></tr>
                                            <tr><th>Alamat</th><td><?= \App\Helpers\App::e((string) ($patient['alamat'] ?? '')) ?></td></tr>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge text-bg-light border">Encounter: <?= \App\Helpers\App::e((string) count($visits)) ?></span>
                            <span class="badge text-bg-light border">Mode: <?= \App\Helpers\App::e(strtoupper($mode)) ?></span>
                        </div>

                        <?php foreach ($encounters as $idx => $encounter): ?>
                            <?php
                            $visit = is_array($encounter['visit'] ?? null) ? $encounter['visit'] : [];
                            $rujukan = is_array($encounter['rujukan'] ?? null) ? $encounter['rujukan'] : [];
                            $dpjp = is_array($encounter['dpjp'] ?? null) ? $encounter['dpjp'] : [];
                            $diagnosa = is_array($encounter['diagnosa'] ?? null) ? $encounter['diagnosa'] : [];
                            $prosedur = is_array($encounter['prosedur'] ?? null) ? $encounter['prosedur'] : [];
                            $catatan = is_array($encounter['catatan'] ?? null) ? $encounter['catatan'] : [];
                            $trd = is_array($encounter['trd'] ?? null) ? $encounter['trd'] : [];
                            $trp = is_array($encounter['trp'] ?? null) ? $encounter['trp'] : [];
                            $trdp = is_array($encounter['trdp'] ?? null) ? $encounter['trdp'] : [];
                            $tid = is_array($encounter['tid'] ?? null) ? $encounter['tid'] : [];
                            $tip = is_array($encounter['tip'] ?? null) ? $encounter['tip'] : [];
                            $tidp = is_array($encounter['tidp'] ?? null) ? $encounter['tidp'] : [];
                            $kamar = is_array($encounter['kamar'] ?? null) ? $encounter['kamar'] : [];
                            $soapiRalan = is_array($encounter['soapiRalan'] ?? null) ? $encounter['soapiRalan'] : [];
                            $soapiRanap = is_array($encounter['soapiRanap'] ?? null) ? $encounter['soapiRanap'] : [];
                            $radiologi = is_array($encounter['radiologi'] ?? null) ? $encounter['radiologi'] : [];
                            $hasilRadiologi = is_array($encounter['hasilRadiologi'] ?? null) ? $encounter['hasilRadiologi'] : [];
                            $gambarRadiologi = is_array($encounter['gambarRadiologi'] ?? null) ? $encounter['gambarRadiologi'] : [];
                            $laboratPk = is_array($encounter['laboratPk'] ?? null) ? $encounter['laboratPk'] : [];
                            $laboratMb = is_array($encounter['laboratMb'] ?? null) ? $encounter['laboratMb'] : [];
                            $laboratPa = is_array($encounter['laboratPa'] ?? null) ? $encounter['laboratPa'] : [];
                            $biayaTotal = (float) ($encounter['biayaTotal'] ?? 0);
                            ?>
                            <div class="card border mb-3">
                                <div class="card-header bg-light fw-semibold">
                                    Encounter #<?= \App\Helpers\App::e((string) ($idx + 1)) ?> - <?= \App\Helpers\App::e((string) ($visit['no_rawat'] ?? '')) ?>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <tr><th width="22%">No. Rawat</th><td><?= \App\Helpers\App::e((string) ($visit['no_rawat'] ?? '')) ?></td></tr>
                                            <tr><th>No. Registrasi</th><td><?= \App\Helpers\App::e((string) ($visit['no_reg'] ?? '')) ?></td></tr>
                                            <tr><th>Tanggal Registrasi</th><td><?= \App\Helpers\App::e((string) ($visit['tgl_registrasi'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($visit['jam_reg'] ?? '')) ?></td></tr>
                                            <tr><th>Umur Saat Daftar</th><td><?= \App\Helpers\App::e((string) ($visit['umurdaftar'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($visit['sttsumur'] ?? '')) ?></td></tr>
                                            <tr><th>Unit/Poliklinik</th><td><?= \App\Helpers\App::e((string) ($visit['nm_poli'] ?? '')) ?></td></tr>
                                            <tr><th>Dokter Poli</th><td><?= \App\Helpers\App::e((string) ($visit['nm_dokter'] ?? '')) ?></td></tr>
                                            <tr><th>Cara Bayar</th><td><?= \App\Helpers\App::e((string) ($visit['png_jawab'] ?? '')) ?></td></tr>
                                            <tr><th>Penanggung Jawab</th><td><?= \App\Helpers\App::e((string) ($visit['p_jawab'] ?? '')) ?></td></tr>
                                            <tr><th>Alamat P.J.</th><td><?= \App\Helpers\App::e((string) ($visit['almt_pj'] ?? '')) ?></td></tr>
                                            <tr><th>Hubungan P.J.</th><td><?= \App\Helpers\App::e((string) ($visit['hubunganpj'] ?? '')) ?></td></tr>
                                            <tr><th>Status</th><td><?= \App\Helpers\App::e((string) ($visit['status_lanjut'] ?? '')) ?></td></tr>
                                        </table>
                                    </div>

                                    <?php if (count($rujukan) > 0): ?>
                                        <h3 class="riwayat-section-title">Rujukan Internal Poli</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th>Poli</th><th>Dokter</th></tr>
                                                <?php foreach ($rujukan as $rIdx => $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($rIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_poli'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_dokter'] ?? '')) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (count($dpjp) > 0): ?>
                                        <h3 class="riwayat-section-title">DPJP Ranap</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th>Kode</th><th>Nama Dokter</th></tr>
                                                <?php foreach ($dpjp as $dIdx => $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($dIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kd_dokter'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_dokter'] ?? '')) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['diagnosa']) && count($diagnosa) > 0): ?>
                                        <h3 class="riwayat-section-title">Diagnosa / ICD-10</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="20%">Kode</th><th>Nama Penyakit</th><th width="20%">Status</th></tr>
                                                <?php foreach ($diagnosa as $dIdx => $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($dIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kd_penyakit'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_penyakit'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['status'] ?? '')) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['prosedur']) && count($prosedur) > 0): ?>
                                        <h3 class="riwayat-section-title">Prosedur / ICD-9</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="20%">Kode</th><th>Nama Prosedur</th><th width="20%">Status</th></tr>
                                                <?php foreach ($prosedur as $pIdx => $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($pIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kode'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['deskripsi_panjang'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['status'] ?? '')) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['catatan_dokter']) && count($catatan) > 0): ?>
                                        <h3 class="riwayat-section-title">Catatan Dokter</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="18%">Tanggal</th><th width="12%">Kode</th><th width="20%">Dokter</th><th>Catatan</th></tr>
                                                <?php foreach ($catatan as $cIdx => $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($cIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tanggal'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kd_dokter'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_dokter'] ?? '')) ?></td>
                                                        <td><?= nl2br(\App\Helpers\App::e((string) ($row['catatan'] ?? ''))) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['tindakan_ralan_dokter']) && count($trd) > 0): ?>
                                        <h3 class="riwayat-section-title">Tindakan Rawat Jalan Dokter</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="18%">Tanggal</th><th width="12%">Kode</th><th>Nama Tindakan</th><th width="20%">Dokter</th><th width="12%">Biaya</th></tr>
                                                <?php foreach ($trd as $tIdx => $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($tIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_perawatan'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam_rawat'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kd_jenis_prw'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_perawatan'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_dokter'] ?? '')) ?></td>
                                                        <td class="text-end"><?= \App\Helpers\App::e($money((float) ($row['biaya_rawat'] ?? 0))) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['tindakan_ralan_paramedis']) && count($trp) > 0): ?>
                                        <h3 class="riwayat-section-title">Tindakan Rawat Jalan Paramedis</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="18%">Tanggal</th><th width="12%">Kode</th><th>Nama Tindakan</th><th width="20%">Paramedis</th><th width="12%">Biaya</th></tr>
                                                <?php foreach ($trp as $tIdx => $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($tIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_perawatan'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam_rawat'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kd_jenis_prw'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_perawatan'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nama'] ?? '')) ?></td>
                                                        <td class="text-end"><?= \App\Helpers\App::e($money((float) ($row['biaya_rawat'] ?? 0))) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['tindakan_ralan_dokter_paramedis']) && count($trdp) > 0): ?>
                                        <h3 class="riwayat-section-title">Tindakan Rawat Jalan Dokter & Paramedis</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="18%">Tanggal</th><th width="12%">Kode</th><th>Nama Tindakan</th><th width="20%">Dokter</th><th width="20%">Paramedis</th><th width="12%">Biaya</th></tr>
                                                <?php foreach ($trdp as $tIdx => $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($tIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_perawatan'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam_rawat'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kd_jenis_prw'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_perawatan'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_dokter'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nama'] ?? '')) ?></td>
                                                        <td class="text-end"><?= \App\Helpers\App::e($money((float) ($row['biaya_rawat'] ?? 0))) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['tindakan_ranap_dokter']) && count($tid) > 0): ?>
                                        <h3 class="riwayat-section-title">Tindakan Rawat Inap Dokter</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="18%">Tanggal</th><th width="12%">Kode</th><th>Nama Tindakan</th><th width="20%">Dokter</th><th width="12%">Biaya</th></tr>
                                                <?php foreach ($tid as $tIdx => $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($tIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_perawatan'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam_rawat'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kd_jenis_prw'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_perawatan'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_dokter'] ?? '')) ?></td>
                                                        <td class="text-end"><?= \App\Helpers\App::e($money((float) ($row['biaya_rawat'] ?? 0))) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['tindakan_ranap_paramedis']) && count($tip) > 0): ?>
                                        <h3 class="riwayat-section-title">Tindakan Rawat Inap Paramedis</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="18%">Tanggal</th><th width="12%">Kode</th><th>Nama Tindakan</th><th width="20%">Paramedis</th><th width="12%">Biaya</th></tr>
                                                <?php foreach ($tip as $tIdx => $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($tIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_perawatan'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam_rawat'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kd_jenis_prw'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_perawatan'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nama'] ?? '')) ?></td>
                                                        <td class="text-end"><?= \App\Helpers\App::e($money((float) ($row['biaya_rawat'] ?? 0))) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['tindakan_ranap_dokter_paramedis']) && count($tidp) > 0): ?>
                                        <h3 class="riwayat-section-title">Tindakan Rawat Inap Dokter & Paramedis</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="18%">Tanggal</th><th width="12%">Kode</th><th>Nama Tindakan</th><th width="20%">Dokter</th><th width="20%">Paramedis</th><th width="12%">Biaya</th></tr>
                                                <?php foreach ($tidp as $tIdx => $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($tIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_perawatan'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam_rawat'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kd_jenis_prw'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_perawatan'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_dokter'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nama'] ?? '')) ?></td>
                                                        <td class="text-end"><?= \App\Helpers\App::e($money((float) ($row['biaya_rawat'] ?? 0))) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['penggunaan_kamar']) && count($kamar) > 0): ?>
                                        <h3 class="riwayat-section-title">Penggunaan Kamar</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="17%">Tgl Masuk</th><th width="17%">Tgl Keluar</th><th width="8%">Lama</th><th>Kamar</th><th width="12%">Status</th><th width="12%">Biaya</th></tr>
                                                <?php foreach ($kamar as $kIdx => $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($kIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_masuk'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam_masuk'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_keluar'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam_keluar'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['lama'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kd_kamar'] ?? '')) ?>, <?= \App\Helpers\App::e((string) ($row['nm_bangsal'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['stts_pulang'] ?? '')) ?></td>
                                                        <td class="text-end"><?= \App\Helpers\App::e($money((float) ($row['ttl_biaya'] ?? 0))) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['soapi_ralan']) && count($soapiRalan) > 0): ?>
                                        <h3 class="riwayat-section-title">SOAPI Rawat Jalan</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="14%">Tanggal</th><th width="18%">Dokter/Paramedis</th><th>Subjek</th><th>Objek</th><th>Asesmen</th><th>Plan</th><th>Inst/Impl</th><th>Evaluasi</th></tr>
                                                <?php foreach ($soapiRalan as $row): ?>
                                                    <?php
                                                    $objek = (string) ($row['pemeriksaan'] ?? '');
                                                    $objek .= (string) ($row['alergi'] ?? '') !== '' ? "\nAlergi: " . (string) $row['alergi'] : '';
                                                    $objek .= (string) ($row['suhu_tubuh'] ?? '') !== '' ? "\nSuhu(C): " . (string) $row['suhu_tubuh'] : '';
                                                    $objek .= (string) ($row['tensi'] ?? '') !== '' ? "\nTensi: " . (string) $row['tensi'] : '';
                                                    $objek .= (string) ($row['nadi'] ?? '') !== '' ? "\nNadi(/menit): " . (string) $row['nadi'] : '';
                                                    $objek .= (string) ($row['respirasi'] ?? '') !== '' ? "\nRespirasi(/menit): " . (string) $row['respirasi'] : '';
                                                    $objek .= (string) ($row['tinggi'] ?? '') !== '' ? "\nTinggi(Cm): " . (string) $row['tinggi'] : '';
                                                    $objek .= (string) ($row['berat'] ?? '') !== '' ? "\nBerat(Kg): " . (string) $row['berat'] : '';
                                                    $objek .= (string) ($row['lingkar_perut'] ?? '') !== '' ? "\nLingkar Perut(Cm): " . (string) $row['lingkar_perut'] : '';
                                                    $objek .= (string) ($row['spo2'] ?? '') !== '' ? "\nSpO2(%): " . (string) $row['spo2'] : '';
                                                    $objek .= (string) ($row['gcs'] ?? '') !== '' ? "\nGCS(E,V,M): " . (string) $row['gcs'] : '';
                                                    $objek .= (string) ($row['kesadaran'] ?? '') !== '' ? "\nKesadaran: " . (string) $row['kesadaran'] : '';
                                                    ?>
                                                    <tr>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_perawatan'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam_rawat'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nip'] ?? '')) ?><br><?= \App\Helpers\App::e((string) ($row['nama'] ?? '')) ?></td>
                                                        <td><?= nl2br(\App\Helpers\App::e((string) ($row['keluhan'] ?? ''))) ?></td>
                                                        <td><?= nl2br(\App\Helpers\App::e($objek)) ?></td>
                                                        <td><?= nl2br(\App\Helpers\App::e((string) ($row['penilaian'] ?? ''))) ?></td>
                                                        <td><?= nl2br(\App\Helpers\App::e((string) ($row['rtl'] ?? ''))) ?></td>
                                                        <td><?= nl2br(\App\Helpers\App::e((string) ($row['instruksi'] ?? ''))) ?></td>
                                                        <td><?= nl2br(\App\Helpers\App::e((string) ($row['evaluasi'] ?? ''))) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['soapi_ranap']) && count($soapiRanap) > 0): ?>
                                        <h3 class="riwayat-section-title">SOAPI Rawat Inap</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="14%">Tanggal</th><th width="18%">Dokter/Paramedis</th><th>Subjek</th><th>Objek</th><th>Asesmen</th><th>Plan</th><th>Inst/Impl</th><th>Evaluasi</th></tr>
                                                <?php foreach ($soapiRanap as $row): ?>
                                                    <?php
                                                    $objek = (string) ($row['pemeriksaan'] ?? '');
                                                    $objek .= (string) ($row['alergi'] ?? '') !== '' ? "\nAlergi: " . (string) $row['alergi'] : '';
                                                    $objek .= (string) ($row['suhu_tubuh'] ?? '') !== '' ? "\nSuhu(C): " . (string) $row['suhu_tubuh'] : '';
                                                    $objek .= (string) ($row['tensi'] ?? '') !== '' ? "\nTensi: " . (string) $row['tensi'] : '';
                                                    $objek .= (string) ($row['nadi'] ?? '') !== '' ? "\nNadi(/menit): " . (string) $row['nadi'] : '';
                                                    $objek .= (string) ($row['respirasi'] ?? '') !== '' ? "\nRespirasi(/menit): " . (string) $row['respirasi'] : '';
                                                    $objek .= (string) ($row['tinggi'] ?? '') !== '' ? "\nTinggi(Cm): " . (string) $row['tinggi'] : '';
                                                    $objek .= (string) ($row['berat'] ?? '') !== '' ? "\nBerat(Kg): " . (string) $row['berat'] : '';
                                                    $objek .= (string) ($row['spo2'] ?? '') !== '' ? "\nSpO2(%): " . (string) $row['spo2'] : '';
                                                    $objek .= (string) ($row['gcs'] ?? '') !== '' ? "\nGCS(E,V,M): " . (string) $row['gcs'] : '';
                                                    $objek .= (string) ($row['kesadaran'] ?? '') !== '' ? "\nKesadaran: " . (string) $row['kesadaran'] : '';
                                                    ?>
                                                    <tr>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_perawatan'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam_rawat'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nip'] ?? '')) ?><br><?= \App\Helpers\App::e((string) ($row['nama'] ?? '')) ?></td>
                                                        <td><?= nl2br(\App\Helpers\App::e((string) ($row['keluhan'] ?? ''))) ?></td>
                                                        <td><?= nl2br(\App\Helpers\App::e($objek)) ?></td>
                                                        <td><?= nl2br(\App\Helpers\App::e((string) ($row['penilaian'] ?? ''))) ?></td>
                                                        <td><?= nl2br(\App\Helpers\App::e((string) ($row['rtl'] ?? ''))) ?></td>
                                                        <td><?= nl2br(\App\Helpers\App::e((string) ($row['instruksi'] ?? ''))) ?></td>
                                                        <td><?= nl2br(\App\Helpers\App::e((string) ($row['evaluasi'] ?? ''))) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['pemeriksaan_radiologi']) && count($radiologi) > 0): ?>
                                        <h3 class="riwayat-section-title">Pemeriksaan Radiologi</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="16%">Tanggal</th><th width="12%">Kode</th><th>Nama Pemeriksaan</th><th width="15%">Dokter PJ</th><th width="15%">Petugas</th><th width="12%">Biaya</th></tr>
                                                <?php foreach ($radiologi as $rIdx => $row): ?>
                                                    <?php $parameter = trim((string) ($row['parameter_radiologi'] ?? ''), ', '); ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($rIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_periksa'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kd_jenis_prw'] ?? '')) ?></td>
                                                        <td>
                                                            <?= \App\Helpers\App::e((string) ($row['nm_perawatan'] ?? '')) ?>
                                                            <?php if ($parameter !== ''): ?>
                                                                <div class="small text-secondary mt-1"><?= \App\Helpers\App::e($parameter) ?></div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_dokter'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nama'] ?? '')) ?></td>
                                                        <td class="text-end"><?= \App\Helpers\App::e($money((float) ($row['biaya'] ?? 0))) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['hasil_radiologi']) && count($hasilRadiologi) > 0): ?>
                                        <h3 class="riwayat-section-title">Bacaan/Hasil Radiologi</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="16%">Tanggal</th><th>Hasil Pemeriksaan</th></tr>
                                                <?php foreach ($hasilRadiologi as $hIdx => $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($hIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_periksa'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam'] ?? '')) ?></td>
                                                        <td><?= nl2br(\App\Helpers\App::e((string) ($row['hasil'] ?? ''))) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['gambar_radiologi']) && count($gambarRadiologi) > 0): ?>
                                        <h3 class="riwayat-section-title">Gambar Radiologi</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="16%">Tanggal</th><th>Lokasi Gambar</th></tr>
                                                <?php foreach ($gambarRadiologi as $gIdx => $row): ?>
                                                    <?php
                                                    $lokasiGambar = trim((string) ($row['lokasi_gambar'] ?? ''));
                                                    $url = $lokasiGambar;
                                                    $altUrl = '';
                                                    if ($lokasiGambar !== '' && !preg_match('/^https?:\/\//i', $lokasiGambar)) {
                                                        $requestHost = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                                                        if ($requestHost === '') {
                                                            $requestHost = 'localhost';
                                                        }
                                                        $radiologiBaseUrls = [
                                                            'http://' . $requestHost . '/webapps/radiologi/pages/upload/',
                                                            'http://localhost/webapps/radiologi/pages/upload/',
                                                        ];
                                                        $radiologiBaseUrls = array_values(array_unique($radiologiBaseUrls));
                                                        $normalizedLokasi = ltrim(str_replace('\\', '/', $lokasiGambar), '/');

                                                        // If DB already stores a path containing /upload/, keep only the tail after upload/.
                                                        if (stripos($normalizedLokasi, 'upload/') !== false) {
                                                            $uploadPos = stripos($normalizedLokasi, 'upload/');
                                                            $normalizedLokasi = substr($normalizedLokasi, $uploadPos + 7);
                                                        }

                                                        $url = $radiologiBaseUrls[0] . ltrim((string) $normalizedLokasi, '/');
                                                        if (isset($radiologiBaseUrls[1])) {
                                                            $altUrl = $radiologiBaseUrls[1] . ltrim((string) $normalizedLokasi, '/');
                                                        }
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($gIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_periksa'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam'] ?? '')) ?></td>
                                                        <td>
                                                            <?php if ($lokasiGambar === ''): ?>
                                                                <span class="text-secondary">Tidak ada lokasi gambar</span>
                                                            <?php else: ?>
                                                                <div>
                                                                    <a href="<?= \App\Helpers\App::e((string) $url) ?>" target="_blank" rel="noopener noreferrer">
                                                                        <?= \App\Helpers\App::e($lokasiGambar) ?>
                                                                    </a>
                                                                </div>
                                                                <?php if ($altUrl !== ''): ?>
                                                                    <div class="small mt-1">
                                                                        <a class="text-secondary" href="<?= \App\Helpers\App::e((string) $altUrl) ?>" target="_blank" rel="noopener noreferrer">
                                                                            Link alternatif
                                                                        </a>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['laborat_pk']) && count($laboratPk) > 0): ?>
                                        <h3 class="riwayat-section-title">Pemeriksaan Laboratorium PK</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="16%">Tanggal</th><th width="12%">Kode</th><th>Nama Pemeriksaan</th><th width="14%">Dokter PJ</th><th width="14%">Petugas</th><th width="11%">Biaya</th></tr>
                                                <?php
                                                $lastPk = '';
                                                $rowNoPk = 0;
                                                foreach ($laboratPk as $row):
                                                    $groupKey = (string) (($row['tgl_periksa'] ?? '') . ' ' . ($row['jam'] ?? '') . '|' . ($row['kd_jenis_prw'] ?? ''));
                                                    if ($groupKey !== $lastPk):
                                                        $lastPk = $groupKey;
                                                        $rowNoPk++;
                                                ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) $rowNoPk) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_periksa'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kd_jenis_prw'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_perawatan'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_dokter'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nama'] ?? '')) ?></td>
                                                        <td class="text-end"><?= \App\Helpers\App::e($money((float) ($row['biaya'] ?? 0))) ?></td>
                                                    </tr>
                                                <?php endif; ?>

                                                <?php if ((string) ($row['pemeriksaan'] ?? '') !== ''): ?>
                                                    <?php
                                                    $ketClass = 'lab-ket-normal';
                                                    $keterangan = strtolower(trim((string) ($row['keterangan'] ?? '')));
                                                    if ($keterangan === 'l') {
                                                        $ketClass = 'lab-ket-low';
                                                    } elseif ($keterangan === 'h') {
                                                        $ketClass = 'lab-ket-high';
                                                    } elseif ($keterangan === 't') {
                                                        $ketClass = 'lab-ket-text';
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td></td>
                                                        <td colspan="3"><span class="text-secondary small">Detail:</span> <?= \App\Helpers\App::e((string) ($row['pemeriksaan'] ?? '')) ?></td>
                                                        <td class="<?= \App\Helpers\App::e($ketClass) ?>">
                                                            <?= \App\Helpers\App::e((string) ($row['nilai'] ?? '')) ?>
                                                            <?= (string) ($row['satuan'] ?? '') !== '' ? ' ' . \App\Helpers\App::e((string) ($row['satuan'] ?? '')) : '' ?>
                                                        </td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nilai_rujukan'] ?? '')) ?></td>
                                                        <td class="text-end"><?= \App\Helpers\App::e($money((float) ($row['biaya_item'] ?? 0))) ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['laborat_mb']) && count($laboratMb) > 0): ?>
                                        <h3 class="riwayat-section-title">Pemeriksaan Laboratorium MB</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="16%">Tanggal</th><th width="12%">Kode</th><th>Nama Pemeriksaan</th><th width="14%">Dokter PJ</th><th width="14%">Petugas</th><th width="11%">Biaya</th></tr>
                                                <?php
                                                $lastMb = '';
                                                $rowNoMb = 0;
                                                foreach ($laboratMb as $row):
                                                    $groupKey = (string) (($row['tgl_periksa'] ?? '') . ' ' . ($row['jam'] ?? '') . '|' . ($row['kd_jenis_prw'] ?? ''));
                                                    if ($groupKey !== $lastMb):
                                                        $lastMb = $groupKey;
                                                        $rowNoMb++;
                                                ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) $rowNoMb) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_periksa'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kd_jenis_prw'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_perawatan'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_dokter'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nama'] ?? '')) ?></td>
                                                        <td class="text-end"><?= \App\Helpers\App::e($money((float) ($row['biaya'] ?? 0))) ?></td>
                                                    </tr>
                                                <?php endif; ?>

                                                <?php if ((string) ($row['pemeriksaan'] ?? '') !== ''): ?>
                                                    <?php
                                                    $ketClass = 'lab-ket-normal';
                                                    $keterangan = strtolower(trim((string) ($row['keterangan'] ?? '')));
                                                    if ($keterangan === 'l') {
                                                        $ketClass = 'lab-ket-low';
                                                    } elseif ($keterangan === 'h') {
                                                        $ketClass = 'lab-ket-high';
                                                    } elseif ($keterangan === 't') {
                                                        $ketClass = 'lab-ket-text';
                                                    }
                                                    ?>
                                                    <tr>
                                                        <td></td>
                                                        <td colspan="3"><span class="text-secondary small">Detail:</span> <?= \App\Helpers\App::e((string) ($row['pemeriksaan'] ?? '')) ?></td>
                                                        <td class="<?= \App\Helpers\App::e($ketClass) ?>">
                                                            <?= \App\Helpers\App::e((string) ($row['nilai'] ?? '')) ?>
                                                            <?= (string) ($row['satuan'] ?? '') !== '' ? ' ' . \App\Helpers\App::e((string) ($row['satuan'] ?? '')) : '' ?>
                                                        </td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nilai_rujukan'] ?? '')) ?></td>
                                                        <td class="text-end"><?= \App\Helpers\App::e($money((float) ($row['biaya_item'] ?? 0))) ?></td>
                                                    </tr>
                                                <?php endif; ?>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (isset($selectedSections['laborat_pa']) && count($laboratPa) > 0): ?>
                                        <h3 class="riwayat-section-title">Pemeriksaan Laboratorium PA</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <tr><th width="5%">No</th><th width="16%">Tanggal</th><th width="12%">Kode</th><th>Nama Pemeriksaan</th><th width="14%">Dokter PJ</th><th width="14%">Petugas</th><th width="11%">Biaya</th></tr>
                                                <?php foreach ($laboratPa as $lIdx => $row): ?>
                                                    <tr>
                                                        <td class="text-center"><?= \App\Helpers\App::e((string) ($lIdx + 1)) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['tgl_periksa'] ?? '')) ?> <?= \App\Helpers\App::e((string) ($row['jam'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['kd_jenis_prw'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_perawatan'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nm_dokter'] ?? '')) ?></td>
                                                        <td><?= \App\Helpers\App::e((string) ($row['nama'] ?? '')) ?></td>
                                                        <td class="text-end"><?= \App\Helpers\App::e($money((float) ($row['biaya'] ?? 0))) ?></td>
                                                    </tr>
                                                    <?php if ((string) ($row['diagnosa_klinik'] ?? '') !== '' || (string) ($row['makroskopik'] ?? '') !== '' || (string) ($row['mikroskopik'] ?? '') !== '' || (string) ($row['kesimpulan'] ?? '') !== '' || (string) ($row['kesan'] ?? '') !== ''): ?>
                                                        <tr>
                                                            <td></td>
                                                            <td colspan="6">
                                                                <?php if ((string) ($row['diagnosa_klinik'] ?? '') !== ''): ?>
                                                                    <div><strong>Diagnosa Klinik:</strong> <?= nl2br(\App\Helpers\App::e((string) ($row['diagnosa_klinik'] ?? ''))) ?></div>
                                                                <?php endif; ?>
                                                                <?php if ((string) ($row['makroskopik'] ?? '') !== ''): ?>
                                                                    <div><strong>Makroskopik:</strong> <?= nl2br(\App\Helpers\App::e((string) ($row['makroskopik'] ?? ''))) ?></div>
                                                                <?php endif; ?>
                                                                <?php if ((string) ($row['mikroskopik'] ?? '') !== ''): ?>
                                                                    <div><strong>Mikroskopik:</strong> <?= nl2br(\App\Helpers\App::e((string) ($row['mikroskopik'] ?? ''))) ?></div>
                                                                <?php endif; ?>
                                                                <?php if ((string) ($row['kesimpulan'] ?? '') !== ''): ?>
                                                                    <div><strong>Kesimpulan:</strong> <?= nl2br(\App\Helpers\App::e((string) ($row['kesimpulan'] ?? ''))) ?></div>
                                                                <?php endif; ?>
                                                                <?php if ((string) ($row['kesan'] ?? '') !== ''): ?>
                                                                    <div><strong>Kesan:</strong> <?= nl2br(\App\Helpers\App::e((string) ($row['kesan'] ?? ''))) ?></div>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count($visits) === 0): ?>
                            <div class="text-secondary">Tidak ada kunjungan sesuai filter.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var selectAll = document.getElementById('select_all');
    var uncheckAll = document.getElementById('uncheck_all');
    var sectionChecks = document.querySelectorAll('.section-check');
    var selectAllHidden = document.getElementById('select_all_hidden');
    var sectionForm = document.getElementById('section-form');
    var menuToggle = document.getElementById('menu_section_toggle');
    var menuContent = document.getElementById('menu_section_content');
    var riwayatBody = document.getElementById('riwayat_body');
    var riwayatSidebar = document.getElementById('riwayat_sidebar');

    if (!selectAll || !selectAllHidden || !sectionForm) {
        return;
    }

    function syncSelectAllState() {
        if (sectionChecks.length === 0) {
            return;
        }

        var checkedCount = 0;
        sectionChecks.forEach(function (checkbox) {
            if (checkbox.checked) {
                checkedCount += 1;
            }
        });

        selectAll.checked = checkedCount === sectionChecks.length;
        selectAllHidden.value = selectAll.checked ? '1' : '0';
    }

    selectAll.addEventListener('change', function () {
        var checked = selectAll.checked;
        selectAllHidden.value = checked ? '1' : '0';

        sectionChecks.forEach(function (checkbox) {
            checkbox.checked = checked;
        });
    });

    if (uncheckAll) {
        uncheckAll.addEventListener('click', function () {
            sectionChecks.forEach(function (checkbox) {
                checkbox.checked = false;
            });
            selectAll.checked = false;
            selectAllHidden.value = '0';
        });
    }

    sectionChecks.forEach(function (checkbox) {
        checkbox.addEventListener('change', syncSelectAllState);
    });

    sectionForm.addEventListener('submit', function () {
        syncSelectAllState();
    });

    syncSelectAllState();

    if (menuToggle && menuContent && riwayatBody && riwayatSidebar) {
        menuToggle.addEventListener('click', function () {
            var expanded = menuToggle.getAttribute('aria-expanded') === 'true';
            var nextExpanded = !expanded;
            menuToggle.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
            riwayatBody.classList.toggle('riwayat-sidebar-collapsed', !nextExpanded);
            riwayatSidebar.classList.toggle('is-collapsed', !nextExpanded);
            menuToggle.setAttribute('title', nextExpanded ? 'Sembunyikan ke kiri' : 'Tampilkan ke kanan');
            var icon = menuToggle.querySelector('i');
            if (icon) {
                icon.className = nextExpanded ? 'bi bi-chevron-left' : 'bi bi-chevron-right';
            }
        });
    }

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        if (window.bootstrap && window.bootstrap.Tooltip) {
            new window.bootstrap.Tooltip(tooltipTriggerEl);
        }
    });
})();
</script>
