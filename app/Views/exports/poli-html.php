<?php
$patients = is_array($patients ?? null) ? $patients : [];
$isItUser = (bool) ($isItUser ?? false);
$filters = is_array($filters ?? null) ? $filters : [];

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
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Export PDF Poli</title>
    <link rel="icon" type="image/png" href="<?= \App\Helpers\App::e(\App\Helpers\App::asset('setting/logo/logo.png')) ?>">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #1f2937; }
        h1 { margin: 0 0 8px; }
        .meta { color: #4b5563; margin-bottom: 16px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #d1d5db; padding: 8px; font-size: 13px; }
        th { background: #eef2ff; text-align: left; }
    </style>
</head>
<body>
<h1>Export Poli (PDF)</h1>
<p class="meta">
    Periode: <?= \App\Helpers\App::e((string) ($filters['dateFrom'] ?? '')) ?> s/d <?= \App\Helpers\App::e((string) ($filters['dateTo'] ?? '')) ?>
    | Pencarian: <?= \App\Helpers\App::e((string) ($filters['q'] ?? '-')) ?>
</p>

<table>
    <thead>
    <tr>
        <th>No</th>
        <th>No RM</th>
        <th>Pasien</th>
        <th>Tgl Daftar</th>
        <?php if ($isItUser): ?>
            <th>Dokter</th>
        <?php endif; ?>
        <th>JK</th>
        <th>Tgl Lahir</th>
        <th>Status Poli</th>
    </tr>
    </thead>
    <tbody>
    <?php if (count($patients) === 0): ?>
        <tr>
            <td colspan="<?= $isItUser ? '8' : '7' ?>">Tidak ada data.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($patients as $row): ?>
            <?php
            $jk = strtoupper($getField($row, 'jk'));
            $jkLabel = $jk === 'L' ? 'Laki-laki' : ($jk === 'P' ? 'Perempuan' : '-');
            ?>
            <tr>
                <td><?= \App\Helpers\App::e($getField($row, 'no_reg')) ?></td>
                <td><?= \App\Helpers\App::e($getField($row, 'no_rkm_medis')) ?></td>
                <td><?= \App\Helpers\App::e($getField($row, 'nm_pasien')) ?></td>
                <td><?= \App\Helpers\App::e($getField($row, 'tgl_registrasi')) ?></td>
                <?php if ($isItUser): ?>
                    <td><?= \App\Helpers\App::e($getField($row, 'nm_dokter')) ?></td>
                <?php endif; ?>
                <td><?= \App\Helpers\App::e($jkLabel) ?></td>
                <td><?= \App\Helpers\App::e($getField($row, 'tgl_lahir')) ?></td>
                <td><?= \App\Helpers\App::e($getField($row, 'stts')) ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</body>
</html>
