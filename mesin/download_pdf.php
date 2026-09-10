<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

/* =========================================================
   DOWNLOAD PDF DETAIL MESIN
   INVENTORY & MAINTENANCE SYSTEM
   PT GARUDAFOOD PUTRA PUTRI JAYA TBK
========================================================= */

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../koneksi.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/* =========================================================
   ERROR HANDLER
========================================================= */

function pdfError($message)
{
    error_log('[PDF MESIN] ' . $message);

    http_response_code(500);

    echo '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>PDF Error</title>
        <style>
            body{
                font-family:Arial,sans-serif;
                background:#f5f7fb;
                padding:50px;
                color:#172033;
            }
            .box{
                max-width:650px;
                margin:auto;
                background:#fff;
                padding:30px;
                border-radius:12px;
                box-shadow:0 5px 25px rgba(0,0,0,.08);
            }
            h2{
                color:#dc3545;
                margin-top:0;
            }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>Gagal Membuat PDF</h2>
            <p>
                Terjadi kesalahan saat membuat laporan PDF.
                Silakan coba kembali.
            </p>
        </div>
    </body>
    </html>
    ';

    exit;
}

/* =========================================================
   CEK DOMPDF
========================================================= */

$autoloadPath = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoloadPath)) {
    pdfError('File vendor/autoload.php tidak ditemukan.');
}

if (!isset($conn) || !$conn) {
    pdfError('Koneksi database tidak tersedia.');
}

/* =========================================================
   PARAMETER
========================================================= */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    pdfError('ID mesin tidak valid.');
}

/* =========================================================
   HELPER
========================================================= */

function e($text)
{
    return htmlspecialchars(
        (string)$text,
        ENT_QUOTES,
        'UTF-8'
    );
}

/*
 * Mengubah gambar menjadi Data URI.
 * Dibuat kompatibel dengan hosting yang tidak memiliki
 * mime_content_type().
 */
function imageToDataUri($path)
{
    if (empty($path) || !file_exists($path)) {
        return '';
    }

    $type = '';

    if (function_exists('mime_content_type')) {
        $type = @mime_content_type($path);
    }

    if (empty($type) && function_exists('finfo_open')) {

        $finfo = @finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo) {
            $type = @finfo_file($finfo, $path);
            @finfo_close($finfo);
        }
    }

    if (empty($type)) {

        $ext = strtolower(
            pathinfo($path, PATHINFO_EXTENSION)
        );

        $mimeMap = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif'
        ];

        $type = $mimeMap[$ext] ?? '';
    }

    if (empty($type)) {
        return '';
    }

    $data = @file_get_contents($path);

    if ($data === false) {
        return '';
    }

    return 'data:' .
        $type .
        ';base64,' .
        base64_encode($data);
}

/* =========================================================
   DATA MESIN
========================================================= */

$sql = "
    SELECT
        m.*,
        jm.nama_jenis_mesin,
        ab.nama_area,
        ab.lokasi
    FROM mesin m
    LEFT JOIN jenis_mesin jm
        ON m.id_jenis_mesin = jm.id
    LEFT JOIN area_bagian ab
        ON m.id_area = ab.id
    WHERE m.id = {$id}
    LIMIT 1
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    pdfError(
        'Query data mesin gagal: ' .
        mysqli_error($conn)
    );
}

$mesin = mysqli_fetch_assoc($result);

mysqli_free_result($result);

if (!$mesin) {
    pdfError('Data mesin tidak ditemukan.');
}

/* =========================================================
   FOTO MESIN
========================================================= */

$fotoMesin = '';

if (!empty($mesin['gambar'])) {

    $pathFoto = dirname(__DIR__) .
        '/uploads/mesin/' .
        $mesin['gambar'];

    $fotoMesin = imageToDataUri($pathFoto);
}

/* =========================================================
   DATA SUB MESIN
========================================================= */

$dataSub = [];

$sql = "
    SELECT
        sm.*,

        (
            SELECT COUNT(*)
            FROM komponen k
            WHERE k.id_sub_mesin = sm.id
        ) AS total_komponen,

        (
            SELECT COUNT(*)
            FROM riwayat_maintenance rm
            INNER JOIN komponen k2
                ON rm.id_komponen = k2.id
            WHERE k2.id_sub_mesin = sm.id
        ) AS total_maintenance

    FROM sub_mesin sm

    WHERE sm.id_mesin = {$id}

    ORDER BY sm.id ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    pdfError(
        'Query sub mesin gagal: ' .
        mysqli_error($conn)
    );
}

while ($row = mysqli_fetch_assoc($result)) {
    $dataSub[] = $row;
}

mysqli_free_result($result);

/* =========================================================
   DATA KOMPONEN
========================================================= */

$dataKomponen = [];

$sql = "
    SELECT
        k.*,
        sm.nama_sub_mesin

    FROM komponen k

    LEFT JOIN sub_mesin sm
        ON k.id_sub_mesin = sm.id

    WHERE sm.id_mesin = {$id}

    ORDER BY k.id ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    pdfError(
        'Query komponen gagal: ' .
        mysqli_error($conn)
    );
}

while ($row = mysqli_fetch_assoc($result)) {
    $dataKomponen[] = $row;
}

mysqli_free_result($result);

/* =========================================================
   DATA MAINTENANCE
========================================================= */

$dataMaintenance = [];

$sql = "
    SELECT
        rm.*,
        k.nama_bagian,
        k.serial_number,
        sm.nama_sub_mesin

    FROM riwayat_maintenance rm

    LEFT JOIN komponen k
        ON rm.id_komponen = k.id

    LEFT JOIN sub_mesin sm
        ON k.id_sub_mesin = sm.id

    WHERE sm.id_mesin = {$id}

    ORDER BY rm.tanggal DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    pdfError(
        'Query maintenance gagal: ' .
        mysqli_error($conn)
    );
}

while ($row = mysqli_fetch_assoc($result)) {
    $dataMaintenance[] = $row;
}

mysqli_free_result($result);

/* =========================================================
   HTML PDF
========================================================= */

$html = '
<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">

<style>

@page {
    margin: 35px 35px 45px 35px;
}

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 10px;
    color: #263238;
}

.header {
    border-bottom: 3px solid #005baa;
    padding-bottom: 12px;
    margin-bottom: 18px;
}

.header-table {
    width: 100%;
    border-collapse: collapse;
}

.header-title {
    font-size: 20px;
    font-weight: bold;
    color: #005baa;
}

.header-subtitle {
    font-size: 10px;
    color: #6b7280;
    margin-top: 4px;
}

.date {
    text-align: right;
    color: #6b7280;
    font-size: 9px;
}

.machine-box {
    border: 1px solid #dce3ea;
    border-radius: 8px;
    padding: 14px;
    margin-bottom: 18px;
}

.machine-table {
    width: 100%;
    border-collapse: collapse;
}

.machine-photo {
    width: 145px;
    height: 115px;
    object-fit: contain;
}

.no-photo {
    width: 145px;
    height: 115px;
    background: #f1f5f9;
    text-align: center;
    vertical-align: middle;
    color: #94a3b8;
    line-height: 115px;
}

.machine-name {
    font-size: 18px;
    font-weight: bold;
    color: #111827;
    margin-bottom: 10px;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table td {
    padding: 5px 7px;
    border-bottom: 1px solid #edf0f2;
}

.info-label {
    width: 125px;
    color: #64748b;
}

.section-title {
    background: #005baa;
    color: white;
    padding: 8px 10px;
    font-size: 12px;
    font-weight: bold;
    margin-top: 15px;
    margin-bottom: 8px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
}

.data-table th {
    background: #eef5fb;
    color: #174a73;
    border: 1px solid #d7e1ea;
    padding: 7px 6px;
    font-size: 9px;
}

.data-table td {
    border: 1px solid #d7e1ea;
    padding: 7px 6px;
    vertical-align: top;
}

.center {
    text-align: center;
}

.badge {
    display: inline-block;
    padding: 3px 7px;
    border-radius: 10px;
    background: #e8f1ff;
    color: #005baa;
}

.good {
    background: #dcfce7;
    color: #166534;
}

.warning {
    background: #fef3c7;
    color: #92400e;
}

.danger {
    background: #fee2e2;
    color: #991b1b;
}

.footer {
    position: fixed;
    bottom: -25px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 8px;
    color: #94a3b8;
}

.small {
    font-size: 8px;
    color: #64748b;
}

</style>

</head>

<body>

<div class="header">

<table class="header-table">
<tr>

<td>

<div class="header-title">
INVENTORY & MAINTENANCE SYSTEM
</div>

<div class="header-subtitle">
PT Garudafood Putra Putri Jaya Tbk
</div>

</td>

<td class="date">
Dicetak: ' .
date('d/m/Y H:i') .
' WIB
</td>

</tr>
</table>

</div>


<div class="machine-box">

<table class="machine-table">

<tr>

<td width="160" valign="top">
';

/* =========================================================
   FOTO
========================================================= */

if (!empty($fotoMesin)) {

    $html .= '
        <img
            src="' . $fotoMesin . '"
            class="machine-photo"
        >
    ';

} else {

    $html .= '
        <div class="no-photo">
            Tidak ada foto
        </div>
    ';
}

$html .= '

</td>

<td valign="top">

<div class="machine-name">
' . e($mesin['nama_mesin'] ?? '-') . '
</div>

<table class="info-table">

<tr>
<td class="info-label">
Serial Number
</td>

<td>
<b>' .
e($mesin['serial_number'] ?? '-') .
'</b>
</td>
</tr>

<tr>
<td class="info-label">
Area
</td>

<td>' .
e($mesin['nama_area'] ?? '-') .
'</td>
</tr>

<tr>
<td class="info-label">
Lokasi
</td>

<td>' .
e($mesin['lokasi'] ?? '-') .
'</td>
</tr>

<tr>
<td class="info-label">
Jenis Mesin
</td>

<td>' .
e($mesin['nama_jenis_mesin'] ?? '-') .
'</td>
</tr>

<tr>
<td class="info-label">
Keterangan
</td>

<td>' .
nl2br(
    e($mesin['keterangan'] ?? '-')
) .
'</td>
</tr>

</table>

</td>

</tr>

</table>

</div>


<div class="section-title">
SUB MESIN
</div>

<table class="data-table">

<thead>

<tr>

<th width="30">
No
</th>

<th>
Nama Sub Mesin
</th>

<th width="130">
Serial Number
</th>

<th width="80">
Komponen
</th>

<th width="90">
Maintenance
</th>

</tr>

</thead>

<tbody>
';

/* =========================================================
   SUB MESIN
========================================================= */

if (!empty($dataSub)) {

    $no = 1;

    foreach ($dataSub as $sub) {

        $html .= '
        <tr>

        <td class="center">
        ' . $no++ . '
        </td>

        <td>
            <b>' .
            e(
                $sub['nama_sub_mesin'] ?? '-'
            ) .
            '</b>

            <br>

            <span class="small">' .
            e(
                $sub['keterangan'] ?? ''
            ) .
            '</span>

        </td>

        <td>' .
        e(
            $sub['serial_number'] ?? '-'
        ) .
        '</td>

        <td class="center">' .
        intval(
            $sub['total_komponen']
        ) .
        '</td>

        <td class="center">' .
        intval(
            $sub['total_maintenance']
        ) .
        '</td>

        </tr>
        ';
    }

} else {

    $html .= '
    <tr>

        <td colspan="5" class="center">
            Belum ada sub mesin.
        </td>

    </tr>
    ';
}

$html .= '
</tbody>
</table>


<div class="section-title">
KOMPONEN
</div>

<table class="data-table">

<thead>

<tr>

<th width="30">
No
</th>

<th>
Nama Bagian
</th>

<th>
Sub Mesin
</th>

<th>
Brand
</th>

<th>
Tipe
</th>

<th>
Part Number
</th>

<th>
Kondisi
</th>

</tr>

</thead>

<tbody>
';

/* =========================================================
   KOMPONEN
========================================================= */

if (!empty($dataKomponen)) {

    $no = 1;

    foreach ($dataKomponen as $komp) {

        $kondisi =
            $komp['kondisi'] ??
            'Baik';

        $class = 'good';

        if ($kondisi === 'Perlu Pemeriksaan') {

            $class = 'warning';

        } elseif (
            $kondisi === 'Dalam Perbaikan' ||
            $kondisi === 'Rusak'
        ) {

            $class = 'danger';
        }

        $html .= '
        <tr>

        <td class="center">
        ' . $no++ . '
        </td>

        <td>

            <b>' .
            e(
                $komp['nama_bagian'] ?? '-'
            ) .
            '</b>

            <br>

            <span class="small">
            SN: ' .
            e(
                $komp['serial_number'] ?? '-'
            ) .
            '
            </span>

        </td>

        <td>' .
        e(
            $komp['nama_sub_mesin'] ?? '-'
        ) .
        '</td>

        <td>' .
        e(
            $komp['brand'] ?? '-'
        ) .
        '</td>

        <td>' .
        e(
            $komp['tipe'] ?? '-'
        ) .
        '</td>

        <td>' .
        e(
            $komp['part_number'] ?? '-'
        ) .
        '</td>

        <td class="center">

            <span class="badge ' .
            $class .
            '">

            ' .
            e($kondisi) .
            '

            </span>

        </td>

        </tr>
        ';
    }

} else {

    $html .= '
    <tr>

        <td colspan="7" class="center">
            Belum ada komponen.
        </td>

    </tr>
    ';
}

$html .= '
</tbody>
</table>


<div class="section-title">
RIWAYAT MAINTENANCE
</div>

<table class="data-table">

<thead>

<tr>

<th width="30">
No
</th>

<th width="75">
Tanggal
</th>

<th>
Komponen
</th>

<th>
Sub Mesin
</th>

<th>
Tindakan
</th>

<th width="70">
Status
</th>

</tr>

</thead>

<tbody>
';

/* =========================================================
   MAINTENANCE
========================================================= */

if (!empty($dataMaintenance)) {

    $no = 1;

    foreach ($dataMaintenance as $maint) {

        $status =
            $maint['status'] ??
            'Pending';

        $class = 'danger';

        if ($status === 'Selesai') {

            $class = 'good';

        } elseif ($status === 'Proses') {

            $class = 'warning';
        }

        $tanggal = '-';

        if (!empty($maint['tanggal'])) {

            $timestamp =
                strtotime(
                    $maint['tanggal']
                );

            if ($timestamp !== false) {

                $tanggal =
                    date(
                        'd/m/Y H:i',
                        $timestamp
                    );
            }
        }

        $html .= '
        <tr>

        <td class="center">
        ' . $no++ . '
        </td>

        <td>
        ' . $tanggal . '
        </td>

        <td>

            <b>' .
            e(
                $maint['nama_bagian'] ?? '-'
            ) .
            '</b>

            <br>

            <span class="small">
            SN: ' .
            e(
                $maint['serial_number'] ?? '-'
            ) .
            '
            </span>

        </td>

        <td>' .
        e(
            $maint['nama_sub_mesin'] ?? '-'
        ) .
        '</td>

        <td>' .
        nl2br(
            e(
                $maint['tindakan'] ?? '-'
            )
        ) .
        '</td>

        <td class="center">

            <span class="badge ' .
            $class .
            '">

            ' .
            e($status) .
            '

            </span>

        </td>

        </tr>
        ';
    }

} else {

    $html .= '
    <tr>

        <td colspan="6" class="center">
            Belum ada riwayat maintenance.
        </td>

    </tr>
    ';
}

$html .= '

</tbody>
</table>


<div class="footer">

Inventory & Maintenance System
—
PT Garudafood Putra Putri Jaya Tbk

</div>

</body>
</html>
';

/* =========================================================
   DOMPDF
========================================================= */

try {

    $options = new Options();

    $options->set(
        'isHtml5ParserEnabled',
        true
    );

    $options->set(
        'isRemoteEnabled',
        true
    );

    $options->set(
        'defaultFont',
        'DejaVu Sans'
    );

    $dompdf =
        new Dompdf($options);

    $dompdf->loadHtml($html);

    $dompdf->setPaper(
        'A4',
        'portrait'
    );

    $dompdf->render();

    $namaMesin =
        $mesin['nama_mesin'] ??
        'mesin';

    $namaFile =
        'Detail_Mesin_' .
        preg_replace(
            '/[^A-Za-z0-9_-]/',
            '_',
            $namaMesin
        ) .
        '.pdf';

    $dompdf->stream(
        $namaFile,
        [
            'Attachment' => true
        ]
    );

    exit;

} catch (Throwable $e) {

    pdfError(
        'Dompdf error: ' .
        $e->getMessage()
    );
}