<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\App;
use App\Helpers\Auth;
use App\Models\JadwalModel;
use App\Models\PoliModel;
use App\Models\RawatInapModel;
use App\Models\RiwayatPerawatanModel;
use App\Models\UserModel;
use DateTimeImmutable;
use Flight;

final class DashboardController
{
    public function dashboard(): void
    {
        $doctorId = trim(Auth::doctorId());
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');

        $poliModel = new PoliModel();
        $rawatInapModel = new RawatInapModel();
        $userModel = new UserModel();
        $jadwalModel = new JadwalModel();

        $isItUser = $poliModel->isItUser($doctorId);
        $doctorFilter = $isItUser ? null : $doctorId;
        $forceEmpty = !$isItUser && $doctorId === '';

        $doctorName = $userModel->findDoctorNameByCode($doctorId);
        $poliTodayTotal = $poliModel->countTodayPatients($doctorFilter, $forceEmpty);
        $poliTodayGroups = $poliModel->getTodayPoliGroups($doctorFilter, $forceEmpty);

        $rawatInapActive = $rawatInapModel->countActivePatients($doctorFilter, $forceEmpty);
        $rawatInapTodayTotal = $rawatInapModel->countTodayAdmissions($doctorFilter, $forceEmpty);
        $rawatInapRecent = $rawatInapModel->getTodayRecentAdmissions($doctorFilter, $forceEmpty, 10);
        $jadwalWeekly = $jadwalModel->getWeeklyScheduleByDoctor($doctorId);

        App::render('dashboard/dashboard', [
            'title' => 'Dashboard - Edokter',
            'activeMenu' => 'dashboard',
            'pageTitle' => 'Dashboard',
            'description' => 'Ringkasan data layanan pada sistem eDokter.',
            'doctorId' => $doctorId,
            'doctorName' => $doctorName,
            'isItUser' => $isItUser,
            'todayDate' => $today,
            'totalPatients' => $poliTodayTotal + $rawatInapActive,
            'poliTodayTotal' => $poliTodayTotal,
            'poliTodayGroups' => $poliTodayGroups,
            'rawatInapActive' => $rawatInapActive,
            'rawatInapTodayTotal' => $rawatInapTodayTotal,
            'rawatInapRecent' => $rawatInapRecent,
            'jadwalWeekly' => $jadwalWeekly,
        ]);
    }

    public function poli(): void
    {
        $state = $this->resolvePoliState();
        $poliModel = new PoliModel();

        $totalRows = $poliModel->countPatients($state['baseFilters']);
        $totalPages = max(1, (int) ceil($totalRows / $state['perPage']));
        $page = min($state['page'], $totalPages);
        $offset = ($page - 1) * $state['perPage'];

        $patients = $poliModel->getPatients($state['baseFilters'] + [
            'limit' => $state['perPage'],
            'offset' => $offset,
        ]);

        App::render('dashboard/poli', [
            'title' => 'Poli - Edokter',
            'activeMenu' => 'poli',
            'pageTitle' => 'Poli',
            'description' => 'Daftar pasien poli berdasarkan tanggal kunjungan.',
            'patients' => $patients,
            'totalRows' => $totalRows,
            'totalPages' => $totalPages,
            'page' => $page,
            'perPage' => $state['perPage'],
            'offset' => $offset,
            'isItUser' => $state['isItUser'],
            'allDoctors' => $state['allDoctors'],
            'filters' => [
                'q' => $state['search'],
                'datePreset' => $state['datePreset'],
                'dateFrom' => $state['dateFrom'],
                'dateTo' => $state['dateTo'],
                'perPage' => (string) $state['perPage'],
                'allDoctors' => $state['allDoctors'] ? '1' : '0',
            ],
        ]);
    }

    public function poliExportExcel(): void
    {
        $state = $this->resolvePoliState();
        $poliModel = new PoliModel();
        $patients = $poliModel->getPatientsForExport($state['baseFilters']);

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="poli-export-' . date('Ymd-His') . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo Flight::view()->fetch('exports/poli-excel', [
            'patients' => $patients,
            'isItUser' => $state['isItUser'],
            'filters' => [
                'q' => $state['search'],
                'datePreset' => $state['datePreset'],
                'dateFrom' => $state['dateFrom'],
                'dateTo' => $state['dateTo'],
            ],
        ]);
    }

    public function poliExportHtml(): void
    {
        $state = $this->resolvePoliState();
        $poliModel = new PoliModel();
        $patients = $poliModel->getPatientsForExport($state['baseFilters']);

        header('Content-Type: text/html; charset=UTF-8');

        echo Flight::view()->fetch('exports/poli-html', [
            'patients' => $patients,
            'isItUser' => $state['isItUser'],
            'filters' => [
                'q' => $state['search'],
                'datePreset' => $state['datePreset'],
                'dateFrom' => $state['dateFrom'],
                'dateTo' => $state['dateTo'],
            ],
        ]);
    }

    public function riwayatPerawatan(): void
    {
        $request = Flight::request();

        $sectionsAll = [
            'diagnosa' => 'Diagnosa/Penyakit (ICD-10)',
            'prosedur' => 'Prosedur/Tindakan (ICD-9)',
            'catatan_dokter' => 'Catatan Dokter',
            'tindakan_ralan_dokter' => 'Tindakan Rawat Jalan Dokter',
            'tindakan_ralan_paramedis' => 'Tindakan Rawat Jalan Paramedis',
            'tindakan_ralan_dokter_paramedis' => 'Tindakan Rawat Jalan Dokter & Paramedis',
            'tindakan_ranap_dokter' => 'Tindakan Rawat Inap Dokter',
            'tindakan_ranap_paramedis' => 'Tindakan Rawat Inap Paramedis',
            'tindakan_ranap_dokter_paramedis' => 'Tindakan Rawat Inap Dokter & Paramedis',
            'penggunaan_kamar' => 'Penggunaan Kamar',
            'soapi_ralan' => 'SOAPI Rawat Jalan',
            'soapi_ranap' => 'SOAPI Rawat Inap',
            'pemeriksaan_radiologi' => 'Pemeriksaan Radiologi',
            'hasil_radiologi' => 'Bacaan/Hasil Radiologi',
            'gambar_radiologi' => 'Gambar Radiologi',
            'laborat_pk' => 'Pemeriksaan Laboratorium PK',
            'laborat_mb' => 'Pemeriksaan Laboratorium MB',
            'laborat_pa' => 'Pemeriksaan Laboratorium PA',
        ];

        $noRm = trim((string) ($request->query->no_rkm_medis ?? ''));
        $mode = trim((string) ($request->query->mode ?? 'r1'));
        $allowedModes = ['r1', 'r2', 'r3'];
        if (!in_array($mode, $allowedModes, true)) {
            $mode = 'r1';
        }

        $tgl1 = trim((string) ($request->query->tgl1 ?? ''));
        $tgl2 = trim((string) ($request->query->tgl2 ?? ''));
        $noRawat = trim((string) ($request->query->no_rawat ?? ''));
        $selectAll = (string) ($request->query->select_all ?? '1');

        $selectedSections = [];
        foreach ($sectionsAll as $key => $_label) {
            if ($selectAll === '1' || isset($request->query->{'sec_' . $key})) {
                $selectedSections[$key] = true;
            }
        }

        $errors = [];
        $patient = null;
        $visits = [];
        $encounters = [];

        $model = new RiwayatPerawatanModel();

        if ($noRm !== '' && !$model->isConnected()) {
            $errors[] = 'Koneksi database tidak tersedia.';
        }

        if ($noRm !== '' && $model->isConnected()) {
            try {
                $patient = $model->getPatient($noRm);
                $visits = $model->getVisits($noRm, $mode, $tgl1, $tgl2, $noRawat);

                foreach ($visits as $visit) {
                    $noRawatVisit = (string) ($visit['no_rawat'] ?? '');
                    if ($noRawatVisit === '') {
                        continue;
                    }

                    $rujukan = $model->getRujukanInternal($noRawatVisit);
                    $dpjp = (string) ($visit['status_lanjut'] ?? '') === 'Ranap'
                        ? $model->getDpjpRanap($noRawatVisit)
                        : [];
                    if ((string) ($visit['status_lanjut'] ?? '') === 'Ranap' && count($dpjp) === 0) {
                        $kdDokter = (string) ($visit['kd_dokter'] ?? '');
                        $nmDokter = (string) ($visit['nm_dokter'] ?? '');
                        if ($kdDokter !== '' || $nmDokter !== '') {
                            $dpjp = [[
                                'kd_dokter' => $kdDokter,
                                'nm_dokter' => $nmDokter,
                            ]];
                        }
                    }

                    $diagnosa = isset($selectedSections['diagnosa']) ? $model->getDiagnosa($noRawatVisit) : [];
                    $prosedur = isset($selectedSections['prosedur']) ? $model->getProsedur($noRawatVisit) : [];
                    $catatan = isset($selectedSections['catatan_dokter']) ? $model->getCatatanDokter($noRawatVisit) : [];
                    $trd = isset($selectedSections['tindakan_ralan_dokter']) ? $model->getTindakanRalanDokter($noRawatVisit) : [];
                    $trp = isset($selectedSections['tindakan_ralan_paramedis']) ? $model->getTindakanRalanParamedis($noRawatVisit) : [];
                    $trdp = isset($selectedSections['tindakan_ralan_dokter_paramedis']) ? $model->getTindakanRalanDokterParamedis($noRawatVisit) : [];
                    $tid = isset($selectedSections['tindakan_ranap_dokter']) ? $model->getTindakanRanapDokter($noRawatVisit) : [];
                    $tip = isset($selectedSections['tindakan_ranap_paramedis']) ? $model->getTindakanRanapParamedis($noRawatVisit) : [];
                    $tidp = isset($selectedSections['tindakan_ranap_dokter_paramedis']) ? $model->getTindakanRanapDokterParamedis($noRawatVisit) : [];
                    $kamar = isset($selectedSections['penggunaan_kamar']) ? $model->getPenggunaanKamar($noRawatVisit) : [];
                    $soapiRalan = isset($selectedSections['soapi_ralan']) ? $model->getPemeriksaanRalan($noRawatVisit) : [];
                    $soapiRanap = isset($selectedSections['soapi_ranap']) ? $model->getPemeriksaanRanap($noRawatVisit) : [];
                    $radiologi = isset($selectedSections['pemeriksaan_radiologi']) ? $model->getPemeriksaanRadiologi($noRawatVisit) : [];
                    $hasilRadiologi = isset($selectedSections['hasil_radiologi']) ? $model->getHasilRadiologi($noRawatVisit) : [];
                    $gambarRadiologi = isset($selectedSections['gambar_radiologi']) ? $model->getGambarRadiologi($noRawatVisit) : [];
                    $laboratPk = isset($selectedSections['laborat_pk']) ? $model->getPemeriksaanLaboratPk($noRawatVisit) : [];
                    $laboratMb = isset($selectedSections['laborat_mb']) ? $model->getPemeriksaanLaboratMb($noRawatVisit) : [];
                    $laboratPa = isset($selectedSections['laborat_pa']) ? $model->getPemeriksaanLaboratPa($noRawatVisit) : [];

                    $biayaTotal = (float) ($visit['biaya_reg'] ?? 0);
                    foreach ($trd as $item) {
                        $biayaTotal += (float) ($item['biaya_rawat'] ?? 0);
                    }
                    foreach ($trp as $item) {
                        $biayaTotal += (float) ($item['biaya_rawat'] ?? 0);
                    }
                    foreach ($trdp as $item) {
                        $biayaTotal += (float) ($item['biaya_rawat'] ?? 0);
                    }
                    foreach ($tid as $item) {
                        $biayaTotal += (float) ($item['biaya_rawat'] ?? 0);
                    }
                    foreach ($tip as $item) {
                        $biayaTotal += (float) ($item['biaya_rawat'] ?? 0);
                    }
                    foreach ($tidp as $item) {
                        $biayaTotal += (float) ($item['biaya_rawat'] ?? 0);
                    }
                    foreach ($kamar as $item) {
                        $biayaTotal += (float) ($item['ttl_biaya'] ?? 0);
                    }
                    foreach ($radiologi as $item) {
                        $biayaTotal += (float) ($item['biaya'] ?? 0);
                    }
                    foreach ($laboratPk as $item) {
                        $biayaTotal += (float) ($item['biaya'] ?? 0);
                    }
                    foreach ($laboratMb as $item) {
                        $biayaTotal += (float) ($item['biaya'] ?? 0);
                    }
                    foreach ($laboratPa as $item) {
                        $biayaTotal += (float) ($item['biaya'] ?? 0);
                    }

                    $encounters[] = [
                        'visit' => $visit,
                        'rujukan' => $rujukan,
                        'dpjp' => $dpjp,
                        'diagnosa' => $diagnosa,
                        'prosedur' => $prosedur,
                        'catatan' => $catatan,
                        'trd' => $trd,
                        'trp' => $trp,
                        'trdp' => $trdp,
                        'tid' => $tid,
                        'tip' => $tip,
                        'tidp' => $tidp,
                        'kamar' => $kamar,
                        'soapiRalan' => $soapiRalan,
                        'soapiRanap' => $soapiRanap,
                        'radiologi' => $radiologi,
                        'hasilRadiologi' => $hasilRadiologi,
                        'gambarRadiologi' => $gambarRadiologi,
                        'laboratPk' => $laboratPk,
                        'laboratMb' => $laboratMb,
                        'laboratPa' => $laboratPa,
                        'biayaTotal' => $biayaTotal,
                    ];
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        App::render('dashboard/riwayat-perawatan', [
            'title' => 'Riwayat Perawatan - Edokter',
            'activeMenu' => 'poli',
            'pageTitle' => 'Riwayat Perawatan',
            'description' => 'Riwayat perawatan pasien berbasis No. RM.',
            'sectionsAll' => $sectionsAll,
            'selectedSections' => $selectedSections,
            'noRm' => $noRm,
            'mode' => $mode,
            'tgl1' => $tgl1,
            'tgl2' => $tgl2,
            'noRawat' => $noRawat,
            'selectAll' => $selectAll,
            'errors' => $errors,
            'patient' => $patient,
            'visits' => $visits,
            'encounters' => $encounters,
        ]);
    }

    public function rawatInap(): void
    {
        $state = $this->resolveRawatInapState();
        $rawatInapModel = new RawatInapModel();

        $totalRows = $rawatInapModel->countPatients($state['baseFilters']);
        $totalPages = max(1, (int) ceil($totalRows / $state['perPage']));
        $page = min($state['page'], $totalPages);
        $offset = ($page - 1) * $state['perPage'];

        $patients = $rawatInapModel->getPatients($state['baseFilters'] + [
            'limit' => $state['perPage'],
            'offset' => $offset,
        ]);

        App::render('dashboard/rawat-inap', [
            'title' => 'Rawat Inap - Edokter',
            'activeMenu' => 'rawat-inap',
            'pageTitle' => 'Rawat Inap',
            'description' => 'Daftar pasien rawat inap berdasarkan DPJP dokter login.',
            'patients' => $patients,
            'totalRows' => $totalRows,
            'totalPages' => $totalPages,
            'page' => $page,
            'perPage' => $state['perPage'],
            'offset' => $offset,
            'filters' => [
                'q' => $state['search'],
                'dateMode' => $state['dateMode'],
                'dateFrom' => $state['dateFrom'],
                'dateTo' => $state['dateTo'],
                'resumeStatus' => $state['resumeStatus'],
                'perPage' => (string) $state['perPage'],
            ],
            'flashError' => $_SESSION['flash_rawat_inap_error'] ?? '',
            'flashSuccess' => $_SESSION['flash_rawat_inap_success'] ?? '',
        ]);

        unset($_SESSION['flash_rawat_inap_error'], $_SESSION['flash_rawat_inap_success']);
    }

    public function rawatInapResume(): void
    {
        $request = Flight::request();
        $noRawat = trim((string) ($request->query->no_rawat ?? ''));
        if ($noRawat === '') {
            $_SESSION['flash_rawat_inap_error'] = 'No. Rawat tidak valid.';
            App::redirect('/rawat-inap');
            return;
        }

        $doctorId = trim(Auth::doctorId());
        if ($doctorId === '') {
            $_SESSION['flash_rawat_inap_error'] = 'Dokter login tidak ditemukan.';
            App::redirect('/rawat-inap');
            return;
        }

        $model = new RawatInapModel();
        $context = $model->getResumeContext($noRawat, $doctorId);
        if ($context === null) {
            $_SESSION['flash_rawat_inap_error'] = 'Data pasien rawat inap tidak ditemukan atau tidak dapat diakses.';
            App::redirect('/rawat-inap');
            return;
        }

        $existingResume = $model->getResumeByNoRawat($noRawat);
        $oldInput = $_SESSION['flash_rawat_inap_resume_old'] ?? [];
        $errors = $_SESSION['flash_rawat_inap_resume_errors'] ?? [];
        $error = $_SESSION['flash_rawat_inap_resume_error'] ?? '';
        $success = $_SESSION['flash_rawat_inap_resume_success'] ?? '';

        unset(
            $_SESSION['flash_rawat_inap_resume_old'],
            $_SESSION['flash_rawat_inap_resume_errors'],
            $_SESSION['flash_rawat_inap_resume_error'],
            $_SESSION['flash_rawat_inap_resume_success']
        );

        $resumeData = $existingResume !== []
            ? $existingResume
            : $model->getResumeAutoPrefill($noRawat);

        if (is_array($oldInput) && $oldInput !== []) {
            foreach ($oldInput as $key => $value) {
                $resumeData[(string) $key] = (string) $value;
            }
        }

        $userModel = new UserModel();
        $doctorName = $userModel->findDoctorNameByCode($doctorId);

        $caraKeluarOptions = ['Atas Izin Dokter', 'Pindah RS', 'Pulang Atas Permintaan Sendiri', 'Lainnya'];
        $keadaanOptions = ['Membaik', 'Sembuh', 'Keadaan Khusus', 'Meninggal'];
        $dilanjutkanOptions = ['Kembali Ke RS', 'RS Lain', 'Dokter Luar', 'Puskesmes', 'Lainnya'];

        App::render('dashboard/rawat-inap-resume', [
            'title' => 'Resume Rawat Inap - Edokter',
            'activeMenu' => 'rawat-inap',
            'pageTitle' => 'Resume Rawat Inap',
            'description' => 'Resume pasien rawat inap berbasis No. Rawat.',
            'context' => $context,
            'resumeData' => $resumeData,
            'doctorId' => $doctorId,
            'doctorName' => $doctorName,
            'errors' => is_array($errors) ? $errors : [],
            'error' => is_string($error) ? $error : '',
            'success' => is_string($success) ? $success : '',
            'caraKeluarOptions' => $caraKeluarOptions,
            'keadaanOptions' => $keadaanOptions,
            'dilanjutkanOptions' => $dilanjutkanOptions,
            'isNewResume' => $existingResume === [],
            'hasOldInput' => is_array($oldInput) && $oldInput !== [],
        ]);
    }

    public function rawatInapResumeSave(): void
    {
        $request = Flight::request();
        $noRawat = trim((string) ($request->data->no_rawat ?? ''));
        if ($noRawat === '') {
            $_SESSION['flash_rawat_inap_error'] = 'No. Rawat tidak valid.';
            App::redirect('/rawat-inap');
            return;
        }

        $doctorId = trim(Auth::doctorId());
        if ($doctorId === '') {
            $_SESSION['flash_rawat_inap_error'] = 'Dokter login tidak ditemukan.';
            App::redirect('/rawat-inap');
            return;
        }

        $model = new RawatInapModel();
        $context = $model->getResumeContext($noRawat, $doctorId);
        if ($context === null) {
            $_SESSION['flash_rawat_inap_error'] = 'Data pasien rawat inap tidak ditemukan atau tidak dapat diakses.';
            App::redirect('/rawat-inap');
            return;
        }

        $userModel = new UserModel();
        $doctorName = trim($userModel->findDoctorNameByCode($doctorId));

        $caraKeluarOptions = ['Atas Izin Dokter', 'Pindah RS', 'Pulang Atas Permintaan Sendiri', 'Lainnya'];
        $keadaanOptions = ['Membaik', 'Sembuh', 'Keadaan Khusus', 'Meninggal'];
        $dilanjutkanOptions = ['Kembali Ke RS', 'RS Lain', 'Dokter Luar', 'Puskesmes', 'Lainnya'];

        $input = [
            'no_rawat' => $noRawat,
            'diagnosa_awal' => trim((string) ($request->data->diagnosa_awal ?? '')),
            'alasan' => trim((string) ($request->data->alasan ?? '')),
            'keluhan_utama' => trim((string) ($request->data->keluhan_utama ?? '')),
            'pemeriksaan_fisik' => trim((string) ($request->data->pemeriksaan_fisik ?? '')),
            'jalannya_penyakit' => trim((string) ($request->data->jalannya_penyakit ?? '')),
            'pemeriksaan_penunjang' => trim((string) ($request->data->pemeriksaan_penunjang ?? '')),
            'hasil_laborat' => trim((string) ($request->data->hasil_laborat ?? '')),
            'tindakan_dan_operasi' => trim((string) ($request->data->tindakan_dan_operasi ?? '')),
            'obat_di_rs' => trim((string) ($request->data->obat_di_rs ?? '')),
            'diagnosa_utama' => trim((string) ($request->data->diagnosa_utama ?? '')),
            'kd_diagnosa_utama' => trim((string) ($request->data->kd_diagnosa_utama ?? '')),
            'diagnosa_sekunder' => trim((string) ($request->data->diagnosa_sekunder ?? '')),
            'kd_diagnosa_sekunder' => trim((string) ($request->data->kd_diagnosa_sekunder ?? '')),
            'diagnosa_sekunder2' => trim((string) ($request->data->diagnosa_sekunder2 ?? '')),
            'kd_diagnosa_sekunder2' => trim((string) ($request->data->kd_diagnosa_sekunder2 ?? '')),
            'diagnosa_sekunder3' => trim((string) ($request->data->diagnosa_sekunder3 ?? '')),
            'kd_diagnosa_sekunder3' => trim((string) ($request->data->kd_diagnosa_sekunder3 ?? '')),
            'diagnosa_sekunder4' => trim((string) ($request->data->diagnosa_sekunder4 ?? '')),
            'kd_diagnosa_sekunder4' => trim((string) ($request->data->kd_diagnosa_sekunder4 ?? '')),
            'prosedur_utama' => trim((string) ($request->data->prosedur_utama ?? '')),
            'kd_prosedur_utama' => trim((string) ($request->data->kd_prosedur_utama ?? '')),
            'prosedur_sekunder' => trim((string) ($request->data->prosedur_sekunder ?? '')),
            'kd_prosedur_sekunder' => trim((string) ($request->data->kd_prosedur_sekunder ?? '')),
            'prosedur_sekunder2' => trim((string) ($request->data->prosedur_sekunder2 ?? '')),
            'kd_prosedur_sekunder2' => trim((string) ($request->data->kd_prosedur_sekunder2 ?? '')),
            'prosedur_sekunder3' => trim((string) ($request->data->prosedur_sekunder3 ?? '')),
            'kd_prosedur_sekunder3' => trim((string) ($request->data->kd_prosedur_sekunder3 ?? '')),
            'alergi' => trim((string) ($request->data->alergi ?? '')),
            'diet' => trim((string) ($request->data->diet ?? '')),
            'lab_belum' => trim((string) ($request->data->lab_belum ?? '')),
            'edukasi' => trim((string) ($request->data->edukasi ?? '')),
            'cara_keluar' => trim((string) ($request->data->cara_keluar ?? '')),
            'ket_keluar' => trim((string) ($request->data->ket_keluar ?? '')),
            'keadaan' => trim((string) ($request->data->keadaan ?? '')),
            'ket_keadaan' => trim((string) ($request->data->ket_keadaan ?? '')),
            'dilanjutkan' => trim((string) ($request->data->dilanjutkan ?? '')),
            'ket_dilanjutkan' => trim((string) ($request->data->ket_dilanjutkan ?? '')),
            'kontrol_tanggal' => trim((string) ($request->data->kontrol_tanggal ?? '')),
            'kontrol_jam' => trim((string) ($request->data->kontrol_jam ?? '')),
            'kontrol' => trim((string) ($request->data->kontrol ?? '')),
            'obat_pulang' => trim((string) ($request->data->obat_pulang ?? '')),
        ];

        $errors = [];
        if (($context['no_rawat'] ?? '') === '' || ($context['no_rkm_medis'] ?? '') === '' || ($context['nm_pasien'] ?? '') === '') {
            $errors[] = 'Pasien wajib terisi.';
        }

        if ($doctorId === '' || $doctorName === '') {
            $errors[] = 'Dokter Penanggung Jawab wajib terisi.';
        }

        if ($input['keluhan_utama'] === '') {
            $errors[] = 'Keluhan utama riwayat penyakit yang positif wajib diisi.';
        }

        if ($input['jalannya_penyakit'] === '') {
            $errors[] = 'Jalannya penyakit selama perawatan wajib diisi.';
        }

        if ($input['diagnosa_utama'] === '') {
            $errors[] = 'Diagnosa utama wajib diisi.';
        }

        if (!in_array($input['cara_keluar'], $caraKeluarOptions, true)) {
            $errors[] = 'Cara keluar wajib dipilih sesuai data yang tersedia.';
        }

        if (!in_array($input['keadaan'], $keadaanOptions, true)) {
            $errors[] = 'Keadaan pulang wajib dipilih sesuai data yang tersedia.';
        }

        if (!in_array($input['dilanjutkan'], $dilanjutkanOptions, true)) {
            $errors[] = 'Dilanjutkan wajib dipilih sesuai data yang tersedia.';
        }

        $maxLengthMap = [
            'diagnosa_awal' => 70,
            'alasan' => 70,
            'keluhan_utama' => 2000,
            'pemeriksaan_fisik' => 2000,
            'jalannya_penyakit' => 2000,
            'pemeriksaan_penunjang' => 2000,
            'hasil_laborat' => 2000,
            'tindakan_dan_operasi' => 2000,
            'obat_di_rs' => 2000,
            'diagnosa_utama' => 80,
            'kd_diagnosa_utama' => 10,
            'diagnosa_sekunder' => 80,
            'kd_diagnosa_sekunder' => 10,
            'diagnosa_sekunder2' => 80,
            'kd_diagnosa_sekunder2' => 10,
            'diagnosa_sekunder3' => 80,
            'kd_diagnosa_sekunder3' => 10,
            'diagnosa_sekunder4' => 80,
            'kd_diagnosa_sekunder4' => 10,
            'prosedur_utama' => 80,
            'kd_prosedur_utama' => 8,
            'prosedur_sekunder' => 80,
            'kd_prosedur_sekunder' => 8,
            'prosedur_sekunder2' => 80,
            'kd_prosedur_sekunder2' => 8,
            'prosedur_sekunder3' => 80,
            'kd_prosedur_sekunder3' => 8,
            'alergi' => 100,
            'diet' => 2000,
            'lab_belum' => 2000,
            'edukasi' => 2000,
            'ket_keluar' => 50,
            'ket_keadaan' => 50,
            'ket_dilanjutkan' => 50,
            'obat_pulang' => 2000,
        ];

        foreach ($maxLengthMap as $field => $maxLength) {
            $value = (string) ($input[$field] ?? '');
            if ($value === '') {
                continue;
            }

            $length = function_exists('mb_strlen')
                ? mb_strlen($value)
                : strlen($value);

            if ($length > $maxLength) {
                $errors[] = sprintf('Panjang %s maksimal %d karakter.', str_replace('_', ' ', $field), $maxLength);
            }
        }

        if ($errors !== []) {
            $_SESSION['flash_rawat_inap_resume_old'] = $input;
            $_SESSION['flash_rawat_inap_resume_errors'] = $errors;
            App::redirect('/rawat-inap/resume?no_rawat=' . urlencode($noRawat));
            return;
        }

        $kontrol = $input['kontrol'];
        if ($kontrol === '' && $input['kontrol_tanggal'] !== '' && $input['kontrol_jam'] !== '') {
            $kontrol = $input['kontrol_tanggal'] . ' ' . $input['kontrol_jam'];
        }

        if ($kontrol !== '') {
            $kontrolDate = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $kontrol);
            if ($kontrolDate !== false) {
                $kontrol = $kontrolDate->format('Y-m-d H:i:s');
            } else {
                $kontrolDateAlt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $kontrol);
                if ($kontrolDateAlt !== false) {
                    $kontrol = $kontrolDateAlt->format('Y-m-d H:i:s');
                } else {
                    $kontrolDateLegacy = DateTimeImmutable::createFromFormat('d/m/Y H:i', $kontrol);
                    if ($kontrolDateLegacy !== false) {
                        $kontrol = $kontrolDateLegacy->format('Y-m-d H:i:s');
                    } else {
                        $kontrol = '';
                    }
                }
            }
        }

        $payload = [
            'no_rawat' => $noRawat,
            'kd_dokter' => $doctorId,
            'diagnosa_awal' => $input['diagnosa_awal'],
            'alasan' => $input['alasan'],
            'keluhan_utama' => $input['keluhan_utama'],
            'pemeriksaan_fisik' => $input['pemeriksaan_fisik'],
            'jalannya_penyakit' => $input['jalannya_penyakit'],
            'pemeriksaan_penunjang' => $input['pemeriksaan_penunjang'],
            'hasil_laborat' => $input['hasil_laborat'],
            'tindakan_dan_operasi' => $input['tindakan_dan_operasi'],
            'obat_di_rs' => $input['obat_di_rs'],
            'diagnosa_utama' => $input['diagnosa_utama'],
            'kd_diagnosa_utama' => $input['kd_diagnosa_utama'],
            'diagnosa_sekunder' => $input['diagnosa_sekunder'],
            'kd_diagnosa_sekunder' => $input['kd_diagnosa_sekunder'],
            'diagnosa_sekunder2' => $input['diagnosa_sekunder2'],
            'kd_diagnosa_sekunder2' => $input['kd_diagnosa_sekunder2'],
            'diagnosa_sekunder3' => $input['diagnosa_sekunder3'],
            'kd_diagnosa_sekunder3' => $input['kd_diagnosa_sekunder3'],
            'diagnosa_sekunder4' => $input['diagnosa_sekunder4'],
            'kd_diagnosa_sekunder4' => $input['kd_diagnosa_sekunder4'],
            'prosedur_utama' => $input['prosedur_utama'],
            'kd_prosedur_utama' => $input['kd_prosedur_utama'],
            'prosedur_sekunder' => $input['prosedur_sekunder'],
            'kd_prosedur_sekunder' => $input['kd_prosedur_sekunder'],
            'prosedur_sekunder2' => $input['prosedur_sekunder2'],
            'kd_prosedur_sekunder2' => $input['kd_prosedur_sekunder2'],
            'prosedur_sekunder3' => $input['prosedur_sekunder3'],
            'kd_prosedur_sekunder3' => $input['kd_prosedur_sekunder3'],
            'alergi' => $input['alergi'],
            'diet' => $input['diet'],
            'lab_belum' => $input['lab_belum'],
            'edukasi' => $input['edukasi'],
            'cara_keluar' => $input['cara_keluar'],
            'ket_keluar' => $input['ket_keluar'] !== '' ? $input['ket_keluar'] : null,
            'keadaan' => $input['keadaan'],
            'ket_keadaan' => $input['ket_keadaan'] !== '' ? $input['ket_keadaan'] : null,
            'dilanjutkan' => $input['dilanjutkan'],
            'ket_dilanjutkan' => $input['ket_dilanjutkan'] !== '' ? $input['ket_dilanjutkan'] : null,
            'kontrol' => $kontrol !== '' ? $kontrol : null,
            'obat_pulang' => $input['obat_pulang'],
        ];

        try {
            $saved = $model->saveResume($payload);
            if (!$saved) {
                throw new \RuntimeException('Data resume gagal disimpan.');
            }

            $_SESSION['flash_rawat_inap_resume_success'] = 'Resume pasien rawat inap berhasil disimpan.';
        } catch (\Throwable $e) {
            $_SESSION['flash_rawat_inap_resume_old'] = $input;
            $_SESSION['flash_rawat_inap_resume_error'] = 'Gagal menyimpan resume: ' . $e->getMessage();
        }

        App::redirect('/rawat-inap/resume?no_rawat=' . urlencode($noRawat));
    }

    public function rawatInapResumeAutofill(): void
    {
        $request = Flight::request();
        $noRawat = trim((string) ($request->query->no_rawat ?? ''));
        $field = trim((string) ($request->query->field ?? ''));

        if ($noRawat === '' || $field === '') {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'Parameter no_rawat atau field tidak valid.',
            ], 422);
            return;
        }

        $doctorId = trim(Auth::doctorId());
        if ($doctorId === '') {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'Dokter login tidak ditemukan.',
            ], 401);
            return;
        }

        $model = new RawatInapModel();
        $context = $model->getResumeContext($noRawat, $doctorId);
        if ($context === null) {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'Data pasien rawat inap tidak ditemukan atau tidak dapat diakses.',
            ], 404);
            return;
        }

        $text = '';
        if ($field === 'diagnosa_awal') {
            $text = $model->getDiagnosaAwalFromKamarInap($noRawat);
        } elseif ($field === 'keluhan_utama') {
            $text = $this->formatPlainItemsCompact($model->getKeluhanUtamaRanap($noRawat));
        } elseif ($field === 'pemeriksaan_penunjang') {
            $text = $this->formatPlainItemsCompact($model->getResumeHasilRadiologi($noRawat));
        } elseif ($field === 'hasil_laborat') {
            $text = $this->formatLabItemsCompact($model->getResumePemeriksaanLabPk($noRawat));
        } else {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'Field autofill tidak didukung.',
            ], 422);
            return;
        }

        $this->jsonResponse([
            'ok' => true,
            'text' => $text,
        ]);
    }

    public function rawatInapResumeMedicineOptions(): void
    {
        $request = Flight::request();
        $noRawat = trim((string) ($request->query->no_rawat ?? ''));
        $source = trim((string) ($request->query->source ?? ''));
        $keyword = trim((string) ($request->query->q ?? ''));

        if ($noRawat === '' || $source === '') {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'Parameter no_rawat atau source tidak valid.',
            ], 422);
            return;
        }

        $doctorId = trim(Auth::doctorId());
        if ($doctorId === '') {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'Dokter login tidak ditemukan.',
            ], 401);
            return;
        }

        $model = new RawatInapModel();
        $context = $model->getResumeContext($noRawat, $doctorId);
        if ($context === null) {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'Data pasien rawat inap tidak ditemukan atau tidak dapat diakses.',
            ], 404);
            return;
        }

        $items = [];
        if ($source === 'obat_di_rs') {
            $rows = $model->getObatSelamaRanap($noRawat, $keyword);
            foreach ($rows as $index => $row) {
                $labelParts = [];
                $qtyPart = trim(($row['jumlah'] ?? '') . ' ' . ($row['satuan'] ?? ''));
                if ($qtyPart !== '') {
                    $labelParts[] = $qtyPart;
                }
                if (($row['nama_brng'] ?? '') !== '') {
                    $labelParts[] = (string) $row['nama_brng'];
                }

                $items[] = [
                    'id' => (string) $index,
                    'label' => implode(' ', $labelParts),
                ];
            }
        } elseif ($source === 'obat_pulang') {
            $rows = $model->getObatPulang($noRawat, $keyword);
            foreach ($rows as $index => $row) {
                $labelParts = [];
                if (($row['nama_brng'] ?? '') !== '') {
                    $labelParts[] = (string) $row['nama_brng'];
                }
                if (($row['jumlah'] ?? '') !== '') {
                    $labelParts[] = (string) $row['jumlah'];
                }
                if (($row['dosis'] ?? '') !== '') {
                    $labelParts[] = (string) $row['dosis'];
                }

                $items[] = [
                    'id' => (string) $index,
                    'label' => implode(' ', $labelParts),
                ];
            }
        } else {
            $this->jsonResponse([
                'ok' => false,
                'message' => 'Source obat tidak didukung.',
            ], 422);
            return;
        }

        $this->jsonResponse([
            'ok' => true,
            'items' => $items,
        ]);
    }

    /**
     * @return array{
     *   search:string,
     *   dateMode:string,
     *   dateFrom:string,
     *   dateTo:string,
    *   resumeStatus:string,
     *   perPage:int,
     *   page:int,
    *   baseFilters:array{search:string,dateMode:string,dateFrom:string,dateTo:string,resumeStatus:string,doctorId:?string,forceEmpty:bool}
     * }
     */
    private function resolveRawatInapState(): array
    {
        $request = Flight::request();
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');

        $sessionKey = 'rawat_inap_state_doctor';
        $storedState = is_array($_SESSION[$sessionKey] ?? null) ? $_SESSION[$sessionKey] : [];
        $hasQueryString = count($_GET) > 0;

        $rawState = [
            'search' => (string) ($storedState['search'] ?? ''),
            'dateMode' => (string) ($storedState['dateMode'] ?? 'belum_pulang'),
            'dateFrom' => (string) ($storedState['dateFrom'] ?? $today),
            'dateTo' => (string) ($storedState['dateTo'] ?? $today),
            'resumeStatus' => (string) ($storedState['resumeStatus'] ?? 'all'),
            'perPage' => (int) ($storedState['perPage'] ?? 10),
            'page' => (int) ($storedState['page'] ?? 1),
        ];

        if ($hasQueryString) {
            if (array_key_exists('q', $_GET)) {
                $rawState['search'] = trim((string) ($request->query->q ?? ''));
            }
            if (array_key_exists('dateMode', $_GET)) {
                $rawState['dateMode'] = trim((string) ($request->query->dateMode ?? ''));
            }
            if (array_key_exists('dateFrom', $_GET)) {
                $rawState['dateFrom'] = trim((string) ($request->query->dateFrom ?? ''));
            }
            if (array_key_exists('dateTo', $_GET)) {
                $rawState['dateTo'] = trim((string) ($request->query->dateTo ?? ''));
            }
            if (array_key_exists('resumeStatus', $_GET)) {
                $rawState['resumeStatus'] = trim((string) ($request->query->resumeStatus ?? ''));
            }
            if (array_key_exists('perPage', $_GET)) {
                $rawState['perPage'] = (int) ($request->query->perPage ?? 10);
            }
            if (array_key_exists('page', $_GET)) {
                $rawState['page'] = (int) ($request->query->page ?? 1);
            }
        }

        $dateMode = trim((string) ($rawState['dateMode'] ?? 'belum_pulang'));
        $allowedDateModes = ['belum_pulang', 'tgl_masuk', 'tgl_pulang'];
        if (!in_array($dateMode, $allowedDateModes, true)) {
            $dateMode = 'belum_pulang';
        }

        $dateFrom = trim((string) ($rawState['dateFrom'] ?? $today));
        $dateTo = trim((string) ($rawState['dateTo'] ?? $today));

        $needsDateRange = $dateMode === 'tgl_masuk' || $dateMode === 'tgl_pulang';
        if ($needsDateRange) {
            $isValidDateFrom = DateTimeImmutable::createFromFormat('Y-m-d', $dateFrom) !== false;
            $isValidDateTo = DateTimeImmutable::createFromFormat('Y-m-d', $dateTo) !== false;

            if (!$isValidDateFrom || !$isValidDateTo) {
                $dateFrom = $today;
                $dateTo = $today;
            }

            if ($dateFrom > $dateTo) {
                [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            }
        }

        $search = trim((string) ($rawState['search'] ?? ''));
        $resumeStatus = trim((string) ($rawState['resumeStatus'] ?? 'all'));
        $allowedResumeStatus = ['all', 'sudah', 'belum'];
        if (!in_array($resumeStatus, $allowedResumeStatus, true)) {
            $resumeStatus = 'all';
        }

        $perPage = (int) ($rawState['perPage'] ?? 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $page = max(1, (int) ($rawState['page'] ?? 1));

        $doctorId = trim(Auth::doctorId());
        $forceEmpty = $doctorId === '';

        $_SESSION[$sessionKey] = [
            'search' => $search,
            'dateMode' => $dateMode,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'resumeStatus' => $resumeStatus,
            'perPage' => $perPage,
            'page' => $page,
        ];

        return [
            'search' => $search,
            'dateMode' => $dateMode,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'resumeStatus' => $resumeStatus,
            'perPage' => $perPage,
            'page' => $page,
            'baseFilters' => [
                'search' => $search,
                'dateMode' => $dateMode,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'resumeStatus' => $resumeStatus,
                'doctorId' => $doctorId,
                'forceEmpty' => $forceEmpty,
            ],
        ];
    }

    /**
     * @param array<int,array<string,string>> $rows
     */
    private function formatDatedItemsCompact(array $rows): string
    {
        $parts = [];
        foreach ($rows as $row) {
            $text = trim((string) ($row['isi'] ?? ''));
            if ($text === '') {
                continue;
            }

            $datePart = trim((string) ($row['tanggal'] ?? '') . ' ' . (string) ($row['jam'] ?? ''));
            $parts[] = $datePart !== '' ? ($datePart . ': ' . $text) : $text;
        }

        return implode(', ', $parts);
    }

    /**
     * @param array<int,array<string,string>> $rows
     */
    private function formatLabItemsCompact(array $rows): string
    {
        $parts = [];
        foreach ($rows as $row) {
            $pemeriksaan = trim((string) ($row['pemeriksaan'] ?? ''));
            $nilai = trim((string) ($row['nilai'] ?? ''));
            $satuan = trim((string) ($row['satuan'] ?? ''));

            $valuePart = $pemeriksaan;
            if ($nilai !== '') {
                $valuePart .= ($valuePart !== '' ? '=' : '') . $nilai;
            }
            if ($satuan !== '') {
                $valuePart .= ($valuePart !== '' ? ' ' : '') . $satuan;
            }

            $valuePart = trim($valuePart);
            if ($valuePart === '') {
                continue;
            }

            $parts[] = $valuePart;
        }

        return implode(', ', $parts);
    }

    /**
     * @param array<int,array<string,string>> $rows
     */
    private function formatPlainItemsCompact(array $rows): string
    {
        $parts = [];
        foreach ($rows as $row) {
            $text = trim((string) ($row['isi'] ?? ''));
            if ($text === '') {
                continue;
            }

            $parts[] = $text;
        }

        return implode(', ', $parts);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function jsonResponse(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array{
     *   search:string,
     *   datePreset:string,
     *   dateFrom:string,
     *   dateTo:string,
     *   perPage:int,
     *   page:int,
     *   isItUser:bool,
     *   allDoctors:bool,
     *   baseFilters:array{search:string,dateFrom:string,dateTo:string,doctorId:?string,forceEmpty:bool}
     * }
     */
    private function resolvePoliState(): array
    {
        $request = Flight::request();
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');

        $datePreset = trim((string) ($request->query->datePreset ?? 'today'));
        $allowedPresets = ['today', 'this_month', 'this_year', 'custom'];
        if (!in_array($datePreset, $allowedPresets, true)) {
            $datePreset = 'today';
        }

        $dateFrom = trim((string) ($request->query->dateFrom ?? ''));
        $dateTo = trim((string) ($request->query->dateTo ?? ''));

        if ($datePreset === 'today') {
            $dateFrom = $today;
            $dateTo = $today;
        } elseif ($datePreset === 'this_month') {
            $monthStart = new DateTimeImmutable('first day of this month');
            $monthEnd = new DateTimeImmutable('last day of this month');
            $dateFrom = $monthStart->format('Y-m-d');
            $dateTo = $monthEnd->format('Y-m-d');
        } elseif ($datePreset === 'this_year') {
            $yearStart = new DateTimeImmutable('first day of january this year');
            $yearEnd = new DateTimeImmutable('last day of december this year');
            $dateFrom = $yearStart->format('Y-m-d');
            $dateTo = $yearEnd->format('Y-m-d');
        }

        $isValidDateFrom = DateTimeImmutable::createFromFormat('Y-m-d', $dateFrom) !== false;
        $isValidDateTo = DateTimeImmutable::createFromFormat('Y-m-d', $dateTo) !== false;
        if (!$isValidDateFrom || !$isValidDateTo) {
            $datePreset = 'today';
            $dateFrom = $today;
            $dateTo = $today;
        }

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $search = trim((string) ($request->query->q ?? ''));
        $perPage = (int) ($request->query->perPage ?? 10);
        $allowedPerPage = [10, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $page = max(1, (int) ($request->query->page ?? 1));

        $doctorId = Auth::doctorId();
        $poliModel = new PoliModel();
        $isItUser = $poliModel->isItUser($doctorId);

        $allDoctors = $isItUser && (string) ($request->query->allDoctors ?? '0') === '1';
        $forceEmpty = $isItUser && !$allDoctors;

        if (!$isItUser && $doctorId === '') {
            $forceEmpty = true;
        }

        $doctorFilter = $allDoctors ? null : $doctorId;

        return [
            'search' => $search,
            'datePreset' => $datePreset,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'perPage' => $perPage,
            'page' => $page,
            'isItUser' => $isItUser,
            'allDoctors' => $allDoctors,
            'baseFilters' => [
                'search' => $search,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'doctorId' => $doctorFilter,
                'forceEmpty' => $forceEmpty,
            ],
        ];
    }
}
