<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

/* =========================================================
   DOWNLOAD PDF DETAIL SUB MESIN
   GARUDAFOOD INVENTORY & MAINTENANCE SYSTEM

   FILE:
   /inventory_mesin/sub_mesin/download_pdf.php

   VERSI HOSTING
   - Tidak menggunakan embedded PHP Dompdf
   - Tidak menggunakan isPhpEnabled
   - Foto menggunakan Base64
   - Logo menggunakan Base64
   - Aman untuk hosting
========================================================= */


/* =========================================================
   ERROR HANDLING
========================================================= */

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);


/* =========================================================
   LOAD DATABASE & DOMPDF
========================================================= */

require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;


/* =========================================================
   CEK KONEKSI
========================================================= */

if (!isset($conn) || !$conn) {
    die('Koneksi database gagal.');
}


/* =========================================================
   AMBIL ID SUB MESIN
========================================================= */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die('ID sub mesin tidak valid.');
}


/* =========================================================
   HELPER ESCAPE HTML
========================================================= */

function e($text)
{
    return htmlspecialchars(
        (string)$text,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   HELPER FORMAT TANGGAL
========================================================= */

function formatTanggal($tanggal)
{
    if (empty($tanggal)) {
        return '-';
    }

    $time = strtotime($tanggal);

    if (!$time) {
        return '-';
    }

    return date('d-m-Y H:i', $time);
}


/* =========================================================
   HELPER KONDISI
========================================================= */

function kondisiClass($kondisi)
{
    switch ($kondisi) {

        case 'Baik':
            return 'baik';

        case 'Perlu Pemeriksaan':
            return 'periksa';

        case 'Dalam Perbaikan':
            return 'perbaikan';

        case 'Rusak':
            return 'rusak';

        default:
            return 'normal';
    }
}


/* =========================================================
   HELPER STATUS MAINTENANCE
========================================================= */

function statusClass($status)
{
    switch ($status) {

        case 'Selesai':
            return 'selesai';

        case 'Proses':
            return 'proses';

        case 'Pending':
            return 'pending';

        default:
            return 'normal';
    }
}


/* =========================================================
   HELPER GAMBAR → BASE64
========================================================= */

function imageBase64($path)
{
    if (empty($path)) {
        return '';
    }

    if (!file_exists($path)) {
        return '';
    }

    if (!is_readable($path)) {
        return '';
    }

    $data = @file_get_contents($path);

    if ($data === false || empty($data)) {
        return '';
    }

    $mime = '';

    if (function_exists('mime_content_type')) {
        $mime = @mime_content_type($path);
    }

    if (empty($mime)) {

        $extension = strtolower(
            pathinfo($path, PATHINFO_EXTENSION)
        );

        switch ($extension) {

            case 'jpg':
            case 'jpeg':
                $mime = 'image/jpeg';
                break;

            case 'png':
                $mime = 'image/png';
                break;

            case 'gif':
                $mime = 'image/gif';
                break;

            case 'webp':
                $mime = 'image/webp';
                break;

            default:
                return '';
        }
    }

    return 'data:' . $mime . ';base64,' .
           base64_encode($data);
}


/* =========================================================
   DATA DETAIL SUB MESIN
========================================================= */

$sql = "
    SELECT
        sm.*,
        m.nama_mesin,
        m.serial_number AS sn_mesin,
        m.gambar AS gambar_mesin,
        m.keterangan AS keterangan_mesin,
        jm.nama_jenis_mesin,
        ab.nama_area,
        ab.lokasi
    FROM sub_mesin sm

    LEFT JOIN mesin m
        ON sm.id_mesin = m.id

    LEFT JOIN jenis_mesin jm
        ON m.id_jenis_mesin = jm.id

    LEFT JOIN area_bagian ab
        ON m.id_area = ab.id

    WHERE sm.id = ?
    LIMIT 1
";


$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die('Query sub mesin gagal.');
}

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$d = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$d) {
    die('Data sub mesin tidak ditemukan.');
}


/* =========================================================
   DATA KOMPONEN
========================================================= */

$sqlKomponen = "
    SELECT
        k.id,
        k.nama_bagian,
        k.serial_number,
        k.kategori,
        k.brand,
        k.tipe,
        k.kondisi,
        k.gambar
    FROM komponen k

    WHERE k.id_sub_mesin = ?

    ORDER BY k.id ASC
";


$stmtKomponen = mysqli_prepare(
    $conn,
    $sqlKomponen
);

$data_komponen = [];


if ($stmtKomponen) {

    mysqli_stmt_bind_param(
        $stmtKomponen,
        'i',
        $id
    );

    mysqli_stmt_execute(
        $stmtKomponen
    );

    $resultKomponen =
        mysqli_stmt_get_result(
            $stmtKomponen
        );

    while (
        $row = mysqli_fetch_assoc(
            $resultKomponen
        )
    ) {

        $data_komponen[] = $row;
    }

    mysqli_stmt_close(
        $stmtKomponen
    );
}


/* =========================================================
   DATA MAINTENANCE
========================================================= */

$sqlMaintenance = "
    SELECT
        rm.id,
        rm.id_komponen,
        rm.tanggal,
        rm.jenis,
        rm.tindakan,
        rm.teknisi,
        rm.status,

        k.nama_bagian,
        k.serial_number

    FROM riwayat_maintenance rm

    LEFT JOIN komponen k
        ON rm.id_komponen = k.id

    WHERE k.id_sub_mesin = ?

    ORDER BY
        rm.tanggal DESC,
        rm.id DESC
";


$stmtMaintenance = mysqli_prepare(
    $conn,
    $sqlMaintenance
);

$data_maintenance = [];


if ($stmtMaintenance) {

    mysqli_stmt_bind_param(
        $stmtMaintenance,
        'i',
        $id
    );

    mysqli_stmt_execute(
        $stmtMaintenance
    );

    $resultMaintenance =
        mysqli_stmt_get_result(
            $stmtMaintenance
        );

    while (
        $row = mysqli_fetch_assoc(
            $resultMaintenance
        )
    ) {

        $data_maintenance[] = $row;
    }

    mysqli_stmt_close(
        $stmtMaintenance
    );
}


/* =========================================================
   FOTO SUB MESIN
========================================================= */

$fotoSub = '';

if (!empty($d['gambar'])) {

    $pathFotoSub =
        __DIR__ .
        '/../uploads/sub_mesin/' .
        basename($d['gambar']);

    $fotoSub = imageBase64(
        $pathFotoSub
    );
}


/* =========================================================
   LOGO GARUDAFOOD
========================================================= */

$logoGarudafood = '';

$kemungkinanLogo = [

    __DIR__ .
    '/../assets/img/logo-garudafood.png',

    __DIR__ .
    '/../assets/img/garudafood.png',

    __DIR__ .
    '/../assets/logo-garudafood.png',

    __DIR__ .
    '/../assets/logo.png'

];


foreach (
    $kemungkinanLogo
    as $logoPath
) {

    if (
        file_exists($logoPath) &&
        is_readable($logoPath)
    ) {

        $logoGarudafood =
            imageBase64($logoPath);

        if (!empty($logoGarudafood)) {
            break;
        }
    }
}


/* =========================================================
   TANGGAL CETAK
========================================================= */

$tanggalCetak =
    date('d-m-Y H:i');


/* =========================================================
   NAMA SUB MESIN
========================================================= */

$namaSubMesin =
    !empty($d['nama_sub_mesin'])
        ? $d['nama_sub_mesin']
        : 'Sub Mesin';


/* =========================================================
   HTML PDF
========================================================= */

$html = '

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<style>

/* =========================================================
   PAGE
========================================================= */

@page {
    margin: 28px 32px 45px 32px;
}


/* =========================================================
   BODY
========================================================= */

body {

    font-family:
        DejaVu Sans,
        sans-serif;

    font-size: 8.5px;

    color: #172033;

    line-height: 1.35;

    margin: 0;

    padding: 0;
}


/* =========================================================
   HEADER
========================================================= */

.company-header {

    width: 100%;

    border-bottom:
        2.5px solid #005baa;

    padding-bottom: 8px;

    margin-bottom: 12px;
}


.company-table {

    width: 100%;

    border-collapse:
        collapse;
}


.logo-box {

    width: 100px;

    vertical-align:
        middle;
}


.logo {

    max-width: 90px;

    max-height: 38px;
}


.company-name {

    font-size: 12px;

    font-weight: bold;

    color: #005baa;
}


.company-system {

    font-size: 7.5px;

    color: #64748b;

    margin-top: 2px;
}


.print-info {

    text-align: right;

    font-size: 7.5px;

    color: #64748b;
}


.print-date {

    font-weight: bold;

    color: #334155;
}


/* =========================================================
   TITLE
========================================================= */

.report-title {

    margin-bottom: 10px;
}


.report-title-main {

    font-size: 16px;

    font-weight: bold;

    color: #172033;
}


.report-title-sub {

    font-size: 7.5px;

    color: #64748b;

    margin-top: 2px;
}


/* =========================================================
   HERO
========================================================= */

.hero {

    width: 100%;

    border:
        1px solid #dbe3ea;

    border-radius: 7px;

    background: #ffffff;

    margin-bottom: 9px;
}


.hero-table {

    width: 100%;

    border-collapse:
        collapse;
}


.hero-photo-cell {

    width: 145px;

    padding: 9px;

    vertical-align:
        middle;
}


.hero-photo {

    width: 125px;

    height: 105px;

    object-fit: contain;

    border:
        1px solid #dbe3ea;

    border-radius: 5px;

    padding: 4px;

    background: #f8fafc;
}


.no-photo {

    width: 125px;

    height: 105px;

    border:
        1px solid #dbe3ea;

    border-radius: 5px;

    background: #f8fafc;

    text-align: center;

    vertical-align: middle;

    color: #94a3b8;

    font-size: 7px;
}


.no-photo-icon {

    font-size: 22px;

    font-weight: bold;
}


.hero-content {

    padding:
        9px 10px 9px 2px;

    vertical-align:
        top;
}


.sub-label {

    font-size: 6.5px;

    font-weight: bold;

    color: #64748b;

    text-transform:
        uppercase;

    letter-spacing: .6px;
}


.sub-name {

    font-size: 15px;

    font-weight: bold;

    color: #172033;

    margin-top: 2px;

    margin-bottom: 7px;
}


/* =========================================================
   INFO
========================================================= */

.info-grid {

    width: 100%;

    border-collapse:
        separate;

    border-spacing: 4px;

    margin-left: -4px;
}


.info-box {

    background: #f8fafc;

    border:
        1px solid #e8edf3;

    border-radius: 5px;

    padding: 5px;
}


.info-label {

    font-size: 5.8px;

    color: #94a3b8;

    font-weight: bold;

    text-transform:
        uppercase;

    margin-bottom: 1px;
}


.info-value {

    font-size: 7.5px;

    color: #1e293b;

    font-weight: bold;
}


/* =========================================================
   STATISTICS
========================================================= */

.stat-table {

    width: 100%;

    border-collapse:
        separate;

    border-spacing: 5px;

    margin-left: -5px;

    margin-top: 0;

    margin-bottom: 5px;
}


.stat {

    width: 33.33%;

    border:
        1px solid #e5e7eb;

    border-radius: 5px;

    background: #ffffff;

    padding: 6px;
}


.stat-number {

    font-size: 12px;

    font-weight: bold;

    color: #005baa;
}


.stat-label {

    font-size: 6.5px;

    color: #64748b;
}


/* =========================================================
   SECTION
========================================================= */

.section {

    margin-top: 8px;

    margin-bottom: 7px;
}


.section-title {

    background: #005baa;

    color: #ffffff;

    padding: 5px 7px;

    font-size: 9px;

    font-weight: bold;

    border-radius:
        4px 4px 0 0;
}


.section-subtitle {

    background: #eef5fb;

    border:
        1px solid #dbe3ea;

    border-top: 0;

    padding: 4px 7px;

    font-size: 6.8px;

    color: #64748b;
}


/* =========================================================
   DESCRIPTION
========================================================= */

.description {

    background: #f8fafc;

    border:
        1px solid #e5e7eb;

    padding: 7px;

    border-radius:
        0 0 5px 5px;

    font-size: 7.5px;
}


/* =========================================================
   TABLE
========================================================= */

.data-table {

    width: 100%;

    border-collapse:
        collapse;

    margin-top: 5px;

    page-break-inside:
        auto;
}


.data-table thead {

    display:
        table-header-group;
}


.data-table tr {

    page-break-inside:
        avoid;

    page-break-after:
        auto;
}


.data-table th {

    background: #eef4f9;

    color: #334155;

    font-weight: bold;

    padding: 5px 4px;

    border:
        1px solid #dbe3ea;

    font-size: 6.8px;

    text-align: left;
}


.data-table td {

    padding: 5px 4px;

    border:
        1px solid #dbe3ea;

    vertical-align:
        middle;

    font-size: 6.8px;
}


/* =========================================================
   TEXT
========================================================= */

.center {

    text-align:
        center;
}


.bold {

    font-weight:
        bold;
}


.muted {

    color: #64748b;
}


.small {

    font-size: 6px;

    color: #64748b;
}


/* =========================================================
   BADGES
========================================================= */

.badge {

    display:
        inline-block;

    padding:
        2.5px 5px;

    border-radius:
        7px;

    font-size:
        5.8px;

    font-weight:
        bold;
}


.baik {

    background:
        #dcfce7;

    color:
        #166534;
}


.periksa {

    background:
        #fef3c7;

    color:
        #92400e;
}


.perbaikan {

    background:
        #fee2e2;

    color:
        #991b1b;
}


.rusak {

    background:
        #dc2626;

    color:
        #ffffff;
}


.selesai {

    background:
        #dcfce7;

    color:
        #166534;
}


.proses {

    background:
        #fef3c7;

    color:
        #92400e;
}


.pending {

    background:
        #fee2e2;

    color:
        #991b1b;
}


.normal {

    background:
        #f1f5f9;

    color:
        #475569;
}


/* =========================================================
   NOTE
========================================================= */

.note {

    background:
        #f8fafc;

    border:
        1px solid #e5e7eb;

    padding: 7px;

    color:
        #64748b;

    border-radius:
        4px;

    font-size:
        7px;
}


/* =========================================================
   FOOTER
========================================================= */

.footer {

    position: fixed;

    bottom: -28px;

    left: 0;

    right: 0;

    height: 18px;

    border-top:
        1px solid #e5e7eb;

    padding-top: 4px;

    font-size: 6.5px;

    color: #94a3b8;
}


.footer-left {

    float: left;
}


.footer-right {

    float: right;
}


/* =========================================================
   SECTION SPACING
========================================================= */

.component-section {

    margin-top: 8px;
}


.maintenance-section {

    margin-top: 12px;
}


</style>

</head>

<body>


<!-- =========================================================
     HEADER PERUSAHAAN
========================================================= -->

<div class="company-header">

<table class="company-table">

<tr>

<td class="logo-box">
';


/* =========================================================
   LOGO
========================================================= */

if (!empty($logoGarudafood)) {

    $html .= '

    <img
        src="' . $logoGarudafood . '"
        class="logo"
        alt="Garudafood"
    >

    ';

} else {

    $html .= '

    <div class="company-name">
        GARUDAFOOD
    </div>

    ';
}


$html .= '

</td>

<td>

<div class="company-name">
    Inventory & Maintenance System
</div>

<div class="company-system">
    Sistem Informasi Inventory dan Maintenance Mesin
</div>

</td>

<td class="print-info">

Dokumen Detail Sub Mesin<br>

<span class="print-date">
' . e($tanggalCetak) . '
</span>

</td>

</tr>

</table>

</div>


<!-- =========================================================
     JUDUL
========================================================= -->

<div class="report-title">

<div class="report-title-main">
    Detail Sub Mesin
</div>

<div class="report-title-sub">
    Informasi lengkap sub mesin, komponen dan riwayat maintenance
</div>

</div>


<!-- =========================================================
     HERO
========================================================= -->

<div class="hero">

<table class="hero-table">

<tr>

<td class="hero-photo-cell">
';


if (!empty($fotoSub)) {

    $html .= '

    <img
        src="' . $fotoSub . '"
        class="hero-photo"
        alt="Foto Sub Mesin"
    >

    ';

} else {

    $html .= '

    <div class="no-photo">

        <div class="no-photo-icon">
            ⚙
        </div>

        Tidak ada foto sub mesin

    </div>

    ';
}


$html .= '

</td>

<td class="hero-content">

<div class="sub-label">
    SUB MESIN
</div>

<div class="sub-name">
    ' . e($namaSubMesin) . '
</div>


<table class="info-grid">

<tr>

<td width="50%">

<div class="info-box">

<div class="info-label">
    Mesin Induk
</div>

<div class="info-value">
    ' . e(
        $d['nama_mesin'] ?? '-'
    ) . '
</div>

</div>

</td>

<td width="50%">

<div class="info-box">

<div class="info-label">
    Serial Number Mesin
</div>

<div class="info-value">
    ' . e(
        $d['sn_mesin'] ?? '-'
    ) . '
</div>

</div>

</td>

</tr>


<tr>

<td>

<div class="info-box">

<div class="info-label">
    Jenis Mesin
</div>

<div class="info-value">
    ' . e(
        $d['nama_jenis_mesin'] ?? '-'
    ) . '
</div>

</div>

</td>

<td>

<div class="info-box">

<div class="info-label">
    Area
</div>

<div class="info-value">
    ' . e(
        $d['nama_area'] ?? '-'
    ) . '
</div>

</div>

</td>

</tr>


<tr>

<td colspan="2">

<div class="info-box">

<div class="info-label">
    Lokasi
</div>

<div class="info-value">
    ' . e(
        $d['lokasi'] ?? '-'
    ) . '
</div>

</div>

</td>

</tr>

</table>

</td>

</tr>

</table>

</div>


<!-- =========================================================
     STATISTIK
========================================================= -->

<table class="stat-table">

<tr>

<td class="stat">

<div class="stat-number">
    ' . count($data_komponen) . '
</div>

<div class="stat-label">
    Komponen Terhubung
</div>

</td>


<td class="stat">

<div class="stat-number">
    ' . count($data_maintenance) . '
</div>

<div class="stat-label">
    Riwayat Maintenance
</div>

</td>


<td class="stat">

<div class="stat-number">
';


if (!empty($d['keterangan'])) {

    $html .= 'Ada';

} else {

    $html .= '-';
}


$html .= '

</div>

<div class="stat-label">
    Keterangan Sub Mesin
</div>

</td>

</tr>

</table>


<!-- =========================================================
     KETERANGAN
========================================================= -->

<div class="section">

<div class="section-title">
    Keterangan Sub Mesin
</div>

<div class="description">
';


if (!empty($d['keterangan'])) {

    $html .= nl2br(
        e($d['keterangan'])
    );

} else {

    $html .= '

    <span class="muted">
        Tidak ada keterangan untuk sub mesin ini.
    </span>

    ';
}


$html .= '

</div>

</div>


<!-- =========================================================
     DATA KOMPONEN
========================================================= -->

<div class="component-section section">

<div class="section-title">
    Data Komponen (' .
    count($data_komponen) .
    ')
</div>

<div class="section-subtitle">
    Daftar seluruh komponen yang terhubung dengan sub mesin
</div>
';


if (!empty($data_komponen)) {

    $html .= '

    <table class="data-table">

    <thead>

    <tr>

        <th
            width="5%"
            class="center"
        >
            No
        </th>

        <th width="23%">
            Nama Komponen
        </th>

        <th width="17%">
            Serial Number
        </th>

        <th width="14%">
            Kategori
        </th>

        <th width="14%">
            Brand
        </th>

        <th width="13%">
            Tipe
        </th>

        <th
            width="14%"
            class="center"
        >
            Kondisi
        </th>

    </tr>

    </thead>

    <tbody>
    ';


    $no = 1;


    foreach (
        $data_komponen
        as $k
    ) {

        $kondisi =
            !empty($k['kondisi'])
                ? $k['kondisi']
                : '-';


        $html .= '

        <tr>

            <td class="center">
                ' . $no++ . '
            </td>

            <td>

                <span class="bold">
                    ' . e(
                        $k['nama_bagian']
                        ?? '-'
                    ) . '
                </span>

            </td>

            <td>
                ' . e(
                    $k['serial_number']
                    ?? '-'
                ) . '
            </td>

            <td>
                ' . e(
                    $k['kategori']
                    ?? '-'
                ) . '
            </td>

            <td>
                ' . e(
                    $k['brand']
                    ?? '-'
                ) . '
            </td>

            <td>
                ' . e(
                    $k['tipe']
                    ?? '-'
                ) . '
            </td>

            <td class="center">

                <span class="badge ' .
                    kondisiClass($kondisi) .
                '">

                    ' .
                    e($kondisi) .
                    '

                </span>

            </td>

        </tr>

        ';
    }


    $html .= '

    </tbody>

    </table>

    ';


} else {

    $html .= '

    <div class="note">
        Belum ada komponen yang terhubung
        dengan sub mesin ini.
    </div>

    ';
}


$html .= '

</div>


<!-- =========================================================
     RIWAYAT MAINTENANCE
========================================================= -->

<div class="maintenance-section section">

<div class="section-title">
    Riwayat Maintenance (' .
    count($data_maintenance) .
    ')
</div>

<div class="section-subtitle">
    Riwayat maintenance seluruh komponen
    pada sub mesin
</div>
';


if (!empty($data_maintenance)) {

    $html .= '

    <table class="data-table">

    <thead>

    <tr>

        <th
            width="5%"
            class="center"
        >
            No
        </th>

        <th width="15%">
            Tanggal
        </th>

        <th width="21%">
            Komponen
        </th>

        <th width="13%">
            Jenis
        </th>

        <th width="20%">
            Tindakan
        </th>

        <th width="13%">
            Teknisi
        </th>

        <th
            width="13%"
            class="center"
        >
            Status
        </th>

    </tr>

    </thead>

    <tbody>
    ';


    $no = 1;


    foreach (
        $data_maintenance
        as $rm
    ) {

        $status =
            !empty($rm['status'])
                ? $rm['status']
                : '-';


        $html .= '

        <tr>

            <td class="center">
                ' . $no++ . '
            </td>

            <td>
                ' . formatTanggal(
                    $rm['tanggal'] ?? ''
                ) . '
            </td>

            <td>

                <span class="bold">
                    ' . e(
                        $rm['nama_bagian']
                        ?? 'Komponen'
                    ) . '
                </span>

                <br>

                <span class="small">
                    SN:
                    ' . e(
                        $rm['serial_number']
                        ?? '-'
                    ) . '
                </span>

            </td>

            <td>
                ' . e(
                    $rm['jenis']
                    ?? '-'
                ) . '
            </td>

            <td>
                ' . nl2br(
                    e(
                        $rm['tindakan']
                        ?? '-'
                    )
                ) . '
            </td>

            <td>
                ' . e(
                    $rm['teknisi']
                    ?? '-'
                ) . '
            </td>

            <td class="center">

                <span class="badge ' .
                    statusClass($status) .
                '">

                    ' .
                    e($status) .
                    '

                </span>

            </td>

        </tr>

        ';
    }


    $html .= '

    </tbody>

    </table>

    ';


} else {

    $html .= '

    <div class="note">
        Belum ada riwayat maintenance
        untuk sub mesin ini.
    </div>

    ';
}


$html .= '


<!-- =========================================================
     FOOTER
========================================================= -->

<div class="footer">

<div class="footer-left">
    GARUDAFOOD • Inventory & Maintenance System
</div>

<div class="footer-right">
    Detail Sub Mesin
</div>

</div>


</body>

</html>
';


/* =========================================================
   DOMPDF OPTIONS
========================================================= */

$options = new Options();


/*
 * HTML5 parser
 */

$options->set(
    'isHtml5ParserEnabled',
    true
);


/*
 * Tidak menggunakan remote resource.
 * Semua gambar sudah dibuat Base64.
 */

$options->set(
    'isRemoteEnabled',
    false
);


/*
 * Font default
 */

$options->set(
    'defaultFont',
    'DejaVu Sans'
);


/* =========================================================
   BUAT DOMPDF
========================================================= */

$dompdf = new Dompdf(
    $options
);


/* =========================================================
   LOAD HTML
========================================================= */

$dompdf->loadHtml(
    $html,
    'UTF-8'
);


/* =========================================================
   PAPER
========================================================= */

$dompdf->setPaper(
    'A4',
    'portrait'
);


/* =========================================================
   RENDER
========================================================= */

$dompdf->render();


/* =========================================================
   NAMA FILE
========================================================= */

$namaFile = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '_',
    $namaSubMesin
);


/* =========================================================
   DOWNLOAD
========================================================= */

$dompdf->stream(
    'Detail_Sub_Mesin_' .
    $namaFile .
    '.pdf',
    [
        'Attachment' => true
    ]
);


exit;

?>