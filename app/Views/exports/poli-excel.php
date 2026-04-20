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
    <title>Export Excel Poli</title>
    <link rel="icon" type="image/png" href="<?= \App\Helpers\App::e(\App\Helpers\App::asset('setting/logo/logo.png')) ?>">
</head>
<body>
<table border="1">
    <tr>
        <td colspan="<?= $isItUser ? '8' : '7' ?>"><strong>Export Poli (Excel)</strong></td>
    </tr>
    <tr>
        <td colspan="<?= $isItUser ? '8' : '7' ?>">
            Periode: <?= \App\Helpers\App::e((string) ($filters['dateFrom'] ?? '')) ?> s/d <?= \App\Helpers\App::e((string) ($filters['dateTo'] ?? '')) ?>
            | Pencarian: <?= \App\Helpers\App::e((string) ($filters['q'] ?? '-')) ?>
        </td>
    </tr>
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
</table>
</body>
</html>
