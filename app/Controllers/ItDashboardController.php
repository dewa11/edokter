<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\App;
use App\Helpers\Auth;
use App\Models\RawatInapModel;
use App\Models\UserModel;
use DateTimeImmutable;
use Flight;

final class ItDashboardController
{
    public function dashboard(): void
    {
        $state = $this->resolveItDashboardState();
        $model = new RawatInapModel();

        $doctorFilter = $state['doctorId'];
        $doctorOptions = $model->getDoctorOptions();

        $recentResumes = $model->getRecentResumeEntries($doctorFilter, false, 20);
        $dischargedWithoutResume = $model->getDischargedWithoutResume($doctorFilter, false, 20);
        $dischargedWithoutResumeTotal = $model->countDischargedWithoutResume($doctorFilter, false);

        App::render('dashboard/it-dashboard', [
            'title' => 'IT Dashboard - Edokter',
            'activeMenu' => 'dashboard',
            'pageTitle' => 'IT Dashboard',
            'description' => 'Monitoring aktivitas dokter dan kepatuhan pengisian resume rawat inap.',
            'doctorId' => Auth::doctorId(),
            'doctorName' => 'IT Admin',
            'loginAt' => Auth::loginTimestamp(),
            'selectedDoctorId' => $doctorFilter ?? '',
            'doctorOptions' => $doctorOptions,
            'recentResumes' => $recentResumes,
            'dischargedWithoutResume' => $dischargedWithoutResume,
            'dischargedWithoutResumeTotal' => $dischargedWithoutResumeTotal,
        ]);
    }

    public function rawatInap(): void
    {
        $state = $this->resolveRawatInapStateForIt();
        $rawatInapModel = new RawatInapModel();

        $totalRows = $rawatInapModel->countPatients($state['baseFilters']);
        $totalPages = max(1, (int) ceil($totalRows / $state['perPage']));
        $page = min($state['page'], $totalPages);
        $offset = ($page - 1) * $state['perPage'];

        $patients = $rawatInapModel->getPatients($state['baseFilters'] + [
            'limit' => $state['perPage'],
            'offset' => $offset,
        ]);

        App::render('dashboard/it-rawat-inap', [
            'title' => 'Rawat Inap IT - Edokter',
            'activeMenu' => 'rawat-inap',
            'pageTitle' => 'Rawat Inap (IT)',
            'description' => 'Daftar pasien rawat inap dengan filter dokter DPJP.',
            'patients' => $patients,
            'totalRows' => $totalRows,
            'totalPages' => $totalPages,
            'page' => $page,
            'perPage' => $state['perPage'],
            'offset' => $offset,
            'doctorOptions' => $state['doctorOptions'],
            'filters' => [
                'q' => $state['search'],
                'dateMode' => $state['dateMode'],
                'dateFrom' => $state['dateFrom'],
                'dateTo' => $state['dateTo'],
                'resumeStatus' => $state['resumeStatus'],
                'perPage' => (string) $state['perPage'],
                'doctorId' => $state['selectedDoctorId'],
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
            App::redirect('/it/rawat-inap');
            return;
        }

        $model = new RawatInapModel();
        $context = $model->getResumeContext($noRawat, null);
        if ($context === null) {
            $_SESSION['flash_rawat_inap_error'] = 'Data pasien rawat inap tidak ditemukan atau tidak dapat diakses.';
            App::redirect('/it/rawat-inap');
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

        $doctorId = trim((string) ($resumeData['kd_dokter'] ?? ($context['kd_dpjp'] ?? '')));
        if ($doctorId === '') {
            $doctorId = trim((string) ($request->query->doctorId ?? ''));
        }

        $userModel = new UserModel();
        $doctorName = $userModel->findDoctorNameByCode($doctorId);

        $caraKeluarOptions = ['Atas Izin Dokter', 'Pindah RS', 'Pulang Atas Permintaan Sendiri', 'Lainnya'];
        $keadaanOptions = ['Membaik', 'Sembuh', 'Keadaan Khusus', 'Meninggal'];
        $dilanjutkanOptions = ['Kembali Ke RS', 'RS Lain', 'Dokter Luar', 'Puskesmes', 'Lainnya'];

        App::render('dashboard/rawat-inap-resume', [
            'title' => 'Resume Rawat Inap - Edokter',
            'activeMenu' => 'rawat-inap',
            'pageTitle' => 'Resume Rawat Inap (IT)',
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
            'isItMode' => true,
            'backPath' => '/it/rawat-inap',
            'savePath' => '/it/rawat-inap/resume',
            'autofillPath' => '/it/rawat-inap/resume/autofill',
            'medicineOptionsPath' => '/it/rawat-inap/resume/medicine-options',
        ]);
    }

    public function rawatInapResumeSave(): void
    {
        $request = Flight::request();
        $noRawat = trim((string) ($request->data->no_rawat ?? ''));
        if ($noRawat === '') {
            $_SESSION['flash_rawat_inap_error'] = 'No. Rawat tidak valid.';
            App::redirect('/it/rawat-inap');
            return;
        }

        $model = new RawatInapModel();
        $context = $model->getResumeContext($noRawat, null);
        if ($context === null) {
            $_SESSION['flash_rawat_inap_error'] = 'Data pasien rawat inap tidak ditemukan atau tidak dapat diakses.';
            App::redirect('/it/rawat-inap');
            return;
        }

        $input = $this->collectResumeInput($request, $noRawat);

        $doctorId = trim((string) ($request->data->kd_dokter ?? ''));
        if ($doctorId === '') {
            $doctorId = trim((string) ($context['kd_dpjp'] ?? ''));
        }

        $userModel = new UserModel();
        $doctorName = trim($userModel->findDoctorNameByCode($doctorId));

        $caraKeluarOptions = ['Atas Izin Dokter', 'Pindah RS', 'Pulang Atas Permintaan Sendiri', 'Lainnya'];
        $keadaanOptions = ['Membaik', 'Sembuh', 'Keadaan Khusus', 'Meninggal'];
        $dilanjutkanOptions = ['Kembali Ke RS', 'RS Lain', 'Dokter Luar', 'Puskesmes', 'Lainnya'];

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
            $_SESSION['flash_rawat_inap_resume_old'] = $input + ['kd_dokter' => $doctorId];
            $_SESSION['flash_rawat_inap_resume_errors'] = $errors;
            App::redirect('/it/rawat-inap/resume?no_rawat=' . urlencode($noRawat));
            return;
        }

        $kontrol = $this->normalizeKontrolValue($input);

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
            $_SESSION['flash_rawat_inap_resume_old'] = $input + ['kd_dokter' => $doctorId];
            $_SESSION['flash_rawat_inap_resume_error'] = 'Gagal menyimpan resume: ' . $e->getMessage();
        }

        App::redirect('/it/rawat-inap/resume?no_rawat=' . urlencode($noRawat));
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

        $model = new RawatInapModel();
        $context = $model->getResumeContext($noRawat, null);
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

        $model = new RawatInapModel();
        $context = $model->getResumeContext($noRawat, null);
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
     * @return array{doctorId:?string}
     */
    private function resolveItDashboardState(): array
    {
        $request = Flight::request();
        $doctorId = trim((string) ($request->query->doctorId ?? ''));

        return [
            'doctorId' => $doctorId !== '' ? $doctorId : null,
        ];
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
     *   selectedDoctorId:string,
     *   doctorOptions:array<int,array{kd_dokter:string,nm_dokter:string}>,
     *   baseFilters:array{search:string,dateMode:string,dateFrom:string,dateTo:string,resumeStatus:string,doctorId:?string,forceEmpty:bool}
     * }
     */
    private function resolveRawatInapStateForIt(): array
    {
        $request = Flight::request();
        $today = (new DateTimeImmutable('today'))->format('Y-m-d');

        $sessionKey = 'rawat_inap_state_it';
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
            'selectedDoctorId' => (string) ($storedState['selectedDoctorId'] ?? ''),
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
            if (array_key_exists('doctorId', $_GET)) {
                $rawState['selectedDoctorId'] = trim((string) ($request->query->doctorId ?? ''));
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

        $model = new RawatInapModel();
        $doctorOptions = $model->getDoctorOptions();
        $selectedDoctorId = trim((string) ($rawState['selectedDoctorId'] ?? ''));
        $knownDoctorCodes = array_map(static fn (array $row): string => (string) ($row['kd_dokter'] ?? ''), $doctorOptions);
        if ($selectedDoctorId !== '' && !in_array($selectedDoctorId, $knownDoctorCodes, true)) {
            $selectedDoctorId = '';
        }

        $_SESSION[$sessionKey] = [
            'search' => $search,
            'dateMode' => $dateMode,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'resumeStatus' => $resumeStatus,
            'perPage' => $perPage,
            'page' => $page,
            'selectedDoctorId' => $selectedDoctorId,
        ];

        return [
            'search' => $search,
            'dateMode' => $dateMode,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'resumeStatus' => $resumeStatus,
            'perPage' => $perPage,
            'page' => $page,
            'selectedDoctorId' => $selectedDoctorId,
            'doctorOptions' => $doctorOptions,
            'baseFilters' => [
                'search' => $search,
                'dateMode' => $dateMode,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'resumeStatus' => $resumeStatus,
                'doctorId' => $selectedDoctorId !== '' ? $selectedDoctorId : null,
                'forceEmpty' => false,
            ],
        ];
    }

    /**
     * @return array<string,string>
     */
    private function collectResumeInput($request, string $noRawat): array
    {
        return [
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
    }

    private function normalizeKontrolValue(array $input): string
    {
        $kontrol = (string) ($input['kontrol'] ?? '');
        if ($kontrol === '' && ($input['kontrol_tanggal'] ?? '') !== '' && ($input['kontrol_jam'] ?? '') !== '') {
            $kontrol = (string) $input['kontrol_tanggal'] . ' ' . (string) $input['kontrol_jam'];
        }

        if ($kontrol !== '') {
            $kontrolDate = DateTimeImmutable::createFromFormat('Y-m-d\\TH:i', $kontrol);
            if ($kontrolDate !== false) {
                return $kontrolDate->format('Y-m-d H:i:s');
            }

            $kontrolDateAlt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $kontrol);
            if ($kontrolDateAlt !== false) {
                return $kontrolDateAlt->format('Y-m-d H:i:s');
            }

            $kontrolDateLegacy = DateTimeImmutable::createFromFormat('d/m/Y H:i', $kontrol);
            if ($kontrolDateLegacy !== false) {
                return $kontrolDateLegacy->format('Y-m-d H:i:s');
            }
        }

        return '';
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
}
