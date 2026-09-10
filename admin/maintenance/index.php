<?php

session_start();

require_once "../../koneksi.php";


/* =========================================================
   PROTEKSI LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}


/* =========================================================
   PROTEKSI ADMIN
========================================================= */

$role = strtolower(trim($_SESSION['role'] ?? ''));

if ($role !== 'admin') {
    header("Location: ../../dashboard/index.php");
    exit;
}


/* =========================================================
   TIMEZONE
========================================================= */

date_default_timezone_set('Asia/Jakarta');


/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}


function tampil($value, $default = '-')
{
    $value = trim((string)($value ?? ''));

    return $value !== ''
        ? e($value)
        : e($default);
}


function badgeStatus($status)
{
    $status = trim((string)$status);
    $lower  = strtolower($status);

    if ($status === '') {
        return '<span class="status-badge status-default">-</span>';
    }

    if (
        strpos($lower, 'selesai') !== false ||
        strpos($lower, 'complete') !== false
    ) {
        return '<span class="status-badge status-success">'
             . e($status)
             . '</span>';
    }

    if (
        strpos($lower, 'proses') !== false ||
        strpos($lower, 'progress') !== false ||
        strpos($lower, 'berlangsung') !== false
    ) {
        return '<span class="status-badge status-warning">'
             . e($status)
             . '</span>';
    }

    if (
        strpos($lower, 'tunda') !== false ||
        strpos($lower, 'pending') !== false ||
        strpos($lower, 'menunggu') !== false
    ) {
        return '<span class="status-badge status-info">'
             . e($status)
             . '</span>';
    }

    if (
        strpos($lower, 'batal') !== false ||
        strpos($lower, 'cancel') !== false
    ) {
        return '<span class="status-badge status-danger">'
             . e($status)
             . '</span>';
    }

    return '<span class="status-badge status-default">'
         . e($status)
         . '</span>';
}


function formatTanggal($tanggal)
{
    if (empty($tanggal)) {
        return '-';
    }

    $timestamp = strtotime($tanggal);

    if (!$timestamp) {
        return e($tanggal);
    }

    $bulan = [
        1  => 'Jan',
        2  => 'Feb',
        3  => 'Mar',
        4  => 'Apr',
        5  => 'Mei',
        6  => 'Jun',
        7  => 'Jul',
        8  => 'Agu',
        9  => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des'
    ];

    return date('d', $timestamp)
        . ' '
        . $bulan[(int)date('n', $timestamp)]
        . ' '
        . date('Y', $timestamp);
}


/* =========================================================
   FILTER
========================================================= */

$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');

$id_area  = (int)($_GET['id_area'] ?? 0);
$id_mesin = (int)($_GET['id_mesin'] ?? 0);

$tanggal_dari   = trim($_GET['tanggal_dari'] ?? '');
$tanggal_sampai = trim($_GET['tanggal_sampai'] ?? '');


/* =========================================================
   DATA AREA
========================================================= */

$areas = [];

$qArea = mysqli_query(
    $conn,
    "
    SELECT
        id,
        nama_area
    FROM area_bagian
    ORDER BY nama_area ASC
    "
);

if ($qArea) {

    while ($row = mysqli_fetch_assoc($qArea)) {
        $areas[] = $row;
    }

}


/* =========================================================
   DATA MESIN
========================================================= */

$mesins = [];

$qMesin = mysqli_query(
    $conn,
    "
    SELECT
        m.id,
        m.nama_mesin,
        m.id_area,
        ab.nama_area

    FROM mesin m

    LEFT JOIN area_bagian ab
        ON ab.id = m.id_area

    ORDER BY m.nama_mesin ASC
    "
);

if ($qMesin) {

    while ($row = mysqli_fetch_assoc($qMesin)) {
        $mesins[] = $row;
    }

}


/* =========================================================
   KONDISI WHERE
========================================================= */

$where  = [];
$params = [];
$types  = '';


/* =========================================================
   SEARCH
========================================================= */

if ($search !== '') {

    $where[] = "
        (
            rm.tindakan LIKE ?
            OR rm.status LIKE ?
            OR rm.teknisi LIKE ?
            OR rm.nama_bagian LIKE ?
            OR rm.nama_mesin LIKE ?
            OR rm.nama_sub_mesin LIKE ?

            OR k.jenis_komponen LIKE ?
            OR k.serial_number LIKE ?
            OR k.part_number LIKE ?
            OR k.brand LIKE ?

            OR ab.nama_area LIKE ?
            OR jm.nama_jenis_mesin LIKE ?
            OR m.nama_mesin LIKE ?
            OR sm.nama_sub_mesin LIKE ?
        )
    ";

    $keyword = '%' . $search . '%';

    for ($i = 0; $i < 14; $i++) {

        $params[] = $keyword;
        $types .= 's';

    }

}


/* =========================================================
   FILTER STATUS
========================================================= */

if ($status !== '') {

    $where[] = "rm.status = ?";

    $params[] = $status;

    $types .= 's';

}


/* =========================================================
   FILTER AREA
========================================================= */

if ($id_area > 0) {

    $where[] = "k.id_area = ?";

    $params[] = $id_area;

    $types .= 'i';

}


/* =========================================================
   FILTER MESIN
========================================================= */

if ($id_mesin > 0) {

    $where[] = "k.id_mesin = ?";

    $params[] = $id_mesin;

    $types .= 'i';

}


/* =========================================================
   FILTER TANGGAL DARI
========================================================= */

if ($tanggal_dari !== '') {

    $where[] = "DATE(rm.tanggal) >= ?";

    $params[] = $tanggal_dari;

    $types .= 's';

}


/* =========================================================
   FILTER TANGGAL SAMPAI
========================================================= */

if ($tanggal_sampai !== '') {

    $where[] = "DATE(rm.tanggal) <= ?";

    $params[] = $tanggal_sampai;

    $types .= 's';

}


/* =========================================================
   WHERE SQL
========================================================= */

$whereSQL = '';

if (!empty($where)) {

    $whereSQL = 'WHERE ' . implode(' AND ', $where);

}


/* =========================================================
   QUERY DATA MAINTENANCE
========================================================= */

$sql = "
    SELECT
        rm.id,
        rm.id_komponen,
        rm.tanggal,
        rm.tindakan,
        rm.status,
        rm.teknisi,
        rm.nama_bagian,
        rm.nama_mesin,
        rm.nama_sub_mesin,

        k.jenis_komponen,
        k.serial_number,
        k.part_number,
        k.brand,
        k.lokasi,
        k.gambar,

        ab.nama_area,
        jm.nama_jenis_mesin,
        m.nama_mesin AS mesin_master,
        sm.nama_sub_mesin AS sub_mesin_master

    FROM riwayat_maintenance rm

    LEFT JOIN komponen k
        ON k.id = rm.id_komponen

    LEFT JOIN area_bagian ab
        ON ab.id = k.id_area

    LEFT JOIN jenis_mesin jm
        ON jm.id = k.id_jenis_mesin

    LEFT JOIN mesin m
        ON m.id = k.id_mesin

    LEFT JOIN sub_mesin sm
        ON sm.id = k.id_sub_mesin

    $whereSQL

    ORDER BY
        rm.tanggal DESC,
        rm.id DESC
";


$stmt = mysqli_prepare($conn, $sql);

$maintenance = [];

if ($stmt) {

    if (!empty($params)) {

        mysqli_stmt_bind_param(
            $stmt,
            $types,
            ...$params
        );

    }

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result) {

        while ($row = mysqli_fetch_assoc($result)) {
            $maintenance[] = $row;
        }

    }

    mysqli_stmt_close($stmt);

}


/* =========================================================
   STATISTIK
========================================================= */

$totalMaintenance = 0;
$totalSelesai     = 0;
$totalProses      = 0;
$totalLainnya     = 0;


/* TOTAL SELURUH MAINTENANCE */

$qTotal = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM riwayat_maintenance
    "
);

if ($qTotal) {

    $row = mysqli_fetch_assoc($qTotal);

    $totalMaintenance = (int)($row['total'] ?? 0);

}


/* TOTAL SELESAI */

$qSelesai = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM riwayat_maintenance
    WHERE LOWER(TRIM(status)) LIKE '%selesai%'
    "
);

if ($qSelesai) {

    $row = mysqli_fetch_assoc($qSelesai);

    $totalSelesai = (int)($row['total'] ?? 0);

}


/* TOTAL PROSES */

$qProses = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM riwayat_maintenance
    WHERE
        LOWER(TRIM(status)) LIKE '%proses%'
        OR LOWER(TRIM(status)) LIKE '%berlangsung%'
        OR LOWER(TRIM(status)) LIKE '%progress%'
    "
);

if ($qProses) {

    $row = mysqli_fetch_assoc($qProses);

    $totalProses = (int)($row['total'] ?? 0);

}


/* STATUS LAINNYA */

$totalLainnya = max(
    0,
    $totalMaintenance - $totalSelesai - $totalProses
);


/* =========================================================
   URL
========================================================= */

$resetUrl = 'index.php';

$tambahUrl = 'tambah.php';

$active_menu = 'maintenance';

?>

<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Riwayat Maintenance • Admin</title>


<!-- =====================================================
     GOOGLE FONT
====================================================== -->

<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
    rel="stylesheet"
>


<!-- =====================================================
     BOOTSTRAP
====================================================== -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- =====================================================
     BOOTSTRAP ICONS
====================================================== -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
    rel="stylesheet"
>


<style>

    :root {

        --primary: #075eaa;
        --primary-dark: #064b89;
        --primary-light: #eaf4ff;

        --text: #172033;
        --muted: #7b8494;

        --bg: #f5f7fb;
        --white: #ffffff;

        --border: #e7ebf1;

        --success: #198754;
        --warning: #f59e0b;
        --danger: #dc3545;
        --info: #0d6efd;

    }


    * {
        box-sizing: border-box;
    }


    body {

        margin: 0;

        background: var(--bg);

        color: var(--text);

        font-family: 'Poppins', sans-serif;

        font-size: 14px;

    }


    a {
        text-decoration: none;
    }


    /* =====================================================
       MAIN
    ====================================================== */

    .main {

        margin-left: 240px;

        min-height: 100vh;

    }


    /* =====================================================
       TOPBAR
    ====================================================== */

    .topbar {

        height: 76px;

        background: var(--white);

        border-bottom: 1px solid var(--border);

        padding: 0 28px;

        display: flex;

        align-items: center;

        justify-content: space-between;

    }


    .topbar-left {

        display: flex;

        align-items: center;

        gap: 12px;

    }


    .mobile-menu {

        display: none;

        border: 0;

        background: var(--primary-light);

        color: var(--primary);

        width: 40px;

        height: 40px;

        border-radius: 9px;

        font-size: 20px;

    }


    .page-title {

        margin: 0;

        font-size: 20px;

        font-weight: 700;

    }


    .page-subtitle {

        color: var(--muted);

        font-size: 12px;

        margin-top: 2px;

    }


    .user-box {

        display: flex;

        align-items: center;

        gap: 10px;

    }


    .user-avatar {

        width: 38px;

        height: 38px;

        border-radius: 50%;

        background: var(--primary-light);

        color: var(--primary);

        display: flex;

        align-items: center;

        justify-content: center;

        font-size: 18px;

    }


    .user-info strong {

        display: block;

        font-size: 12px;

    }


    .user-info span {

        color: var(--muted);

        font-size: 10px;

    }


    /* =====================================================
       CONTENT
    ====================================================== */

    .content {

        padding: 28px;

    }


    /* =====================================================
       PAGE HEADER
    ====================================================== */

    .page-header {

        display: flex;

        align-items: center;

        justify-content: space-between;

        gap: 15px;

        margin-bottom: 22px;

    }


    .page-header h2 {

        margin: 0;

        font-size: 22px;

        font-weight: 700;

    }


    .page-header p {

        margin: 5px 0 0;

        color: var(--muted);

        font-size: 12px;

    }


    .btn-primary-custom {

        display: inline-flex;

        align-items: center;

        gap: 8px;

        background: var(--primary);

        border: 1px solid var(--primary);

        color: white;

        padding: 10px 15px;

        border-radius: 9px;

        font-size: 12px;

        font-weight: 600;

        transition: .2s;

    }


    .btn-primary-custom:hover {

        background: var(--primary-dark);

        color: white;

    }


    /* =====================================================
       STATISTICS
    ====================================================== */

    .stats-grid {

        display: grid;

        grid-template-columns:
            repeat(4, minmax(0, 1fr));

        gap: 14px;

        margin-bottom: 22px;

    }


    .stat-card {

        background: var(--white);

        border: 1px solid var(--border);

        border-radius: 12px;

        padding: 17px;

        display: flex;

        align-items: center;

        gap: 13px;

    }


    .stat-icon {

        width: 44px;

        height: 44px;

        border-radius: 10px;

        display: flex;

        align-items: center;

        justify-content: center;

        font-size: 20px;

        flex-shrink: 0;

    }


    .stat-blue {

        background: #eaf4ff;

        color: var(--primary);

    }


    .stat-green {

        background: #eaf8f0;

        color: var(--success);

    }


    .stat-orange {

        background: #fff5df;

        color: var(--warning);

    }


    .stat-gray {

        background: #f0f2f5;

        color: #697386;

    }


    .stat-text span {

        display: block;

        color: var(--muted);

        font-size: 11px;

        margin-bottom: 3px;

    }


    .stat-text strong {

        font-size: 20px;

        font-weight: 700;

    }


    /* =====================================================
       FILTER
    ====================================================== */

    .filter-card {

        background: var(--white);

        border: 1px solid var(--border);

        border-radius: 12px;

        padding: 18px;

        margin-bottom: 18px;

    }


    .filter-title {

        display: flex;

        align-items: center;

        gap: 8px;

        font-size: 13px;

        font-weight: 700;

        margin-bottom: 15px;

    }


    .filter-title i {

        color: var(--primary);

        font-size: 16px;

    }


    .filter-grid {

        display: grid;

        grid-template-columns:
            1.5fr
            1fr
            1fr
            1fr
            1fr;

        gap: 11px;

    }


    .form-label-custom {

        display: block;

        font-size: 11px;

        font-weight: 600;

        color: #5e6879;

        margin-bottom: 6px;

    }


    .form-control-custom,
    .form-select-custom {

        width: 100%;

        height: 40px;

        border: 1px solid var(--border);

        border-radius: 8px;

        background: white;

        padding: 0 11px;

        color: var(--text);

        font-family: inherit;

        font-size: 11px;

        outline: none;

    }


    .form-control-custom:focus,
    .form-select-custom:focus {

        border-color: var(--primary);

        box-shadow:
            0 0 0 3px
            rgba(7, 94, 170, .08);

    }


    .date-grid {

        display: grid;

        grid-template-columns: 1fr 1fr;

        gap: 10px;

        margin-top: 12px;

    }


    .filter-actions {

        display: flex;

        justify-content: flex-end;

        gap: 8px;

        margin-top: 14px;

    }


    .btn-filter {

        height: 38px;

        padding: 0 15px;

        border-radius: 8px;

        border: 1px solid var(--primary);

        background: var(--primary);

        color: white;

        font-size: 11px;

        font-weight: 600;

    }


    .btn-reset {

        height: 38px;

        padding: 0 15px;

        border-radius: 8px;

        border: 1px solid var(--border);

        background: white;

        color: #606a7b;

        font-size: 11px;

        font-weight: 600;

    }


    .btn-reset:hover {

        background: #f8f9fb;

    }


    /* =====================================================
       RESULT
    ====================================================== */

    .result-bar {

        display: flex;

        align-items: center;

        justify-content: space-between;

        margin-bottom: 12px;

        gap: 10px;

    }


    .result-info {

        color: var(--muted);

        font-size: 11px;

    }


    .result-info strong {

        color: var(--text);

    }


    /* =====================================================
       MAINTENANCE LIST
    ====================================================== */

    .maintenance-list {

        display: flex;

        flex-direction: column;

        gap: 10px;

    }


    /* =====================================================
       CARD

       URUTAN:
       1. ICON
       2. KOMPONEN
       3. MESIN
       4. TEKNISI / TINDAKAN
       5. TANGGAL
       6. AKSI
    ====================================================== */

    .maintenance-card {

        background: var(--white);

        border: 1px solid var(--border);

        border-radius: 12px;

        padding: 14px;

        display: grid;

        grid-template-columns:
            54px
            minmax(200px, 1.5fr)
            minmax(180px, 1.2fr)
            minmax(140px, .9fr)
            90px
            108px;

        align-items: center;

        gap: 14px;

        transition: .2s;

    }


    .maintenance-card:hover {

        border-color: #cfd8e5;

        box-shadow:
            0 4px 14px
            rgba(23, 32, 51, .05);

    }


    /* =====================================================
       ICON
    ====================================================== */

    .maintenance-icon {

        width: 48px;

        height: 48px;

        border-radius: 10px;

        background: var(--primary-light);

        color: var(--primary);

        display: flex;

        align-items: center;

        justify-content: center;

        font-size: 21px;

    }


    /* =====================================================
       MAIN
    ====================================================== */

    .maintenance-main {

        min-width: 0;

    }


    .maintenance-title {

        display: flex;

        align-items: center;

        gap: 7px;

        flex-wrap: wrap;

        margin-bottom: 5px;

    }


    .maintenance-title strong {

        font-size: 13px;

        font-weight: 700;

        color: var(--text);

    }


    .maintenance-subtitle {

        color: var(--muted);

        font-size: 10px;

        line-height: 1.5;

        white-space: nowrap;

        overflow: hidden;

        text-overflow: ellipsis;

    }


    .maintenance-subtitle i {

        margin-right: 3px;

    }


    /* =====================================================
       DETAIL
    ====================================================== */

    .maintenance-detail {

        min-width: 0;

    }


    .detail-label {

        color: #9aa2af;

        font-size: 9px;

        text-transform: uppercase;

        letter-spacing: .3px;

        margin-bottom: 3px;

    }


    .detail-value {

        color: #495366;

        font-size: 10px;

        line-height: 1.5;

    }


    .detail-value.truncate {

        white-space: nowrap;

        overflow: hidden;

        text-overflow: ellipsis;

    }


    /* =====================================================
       TANGGAL
    ====================================================== */

    .maintenance-date {

        text-align: center;

        min-width: 0;

    }


    .maintenance-date i {

        display: block;

        color: var(--primary);

        font-size: 15px;

        margin-bottom: 4px;

    }


    .maintenance-date strong {

        display: block;

        font-size: 10px;

        font-weight: 600;

        white-space: nowrap;

    }


    /* =====================================================
       ACTION

       SELALU PALING KANAN
    ====================================================== */

    .maintenance-actions {

        display: flex;

        align-items: center;

        justify-content: flex-end;

        gap: 5px;

        width: 100%;

    }


    .action-btn {

        width: 32px;

        height: 32px;

        flex: 0 0 32px;

        border-radius: 7px;

        border: 1px solid var(--border);

        background: white;

        display: flex;

        align-items: center;

        justify-content: center;

        font-size: 14px;

        transition: .2s;

    }


    .action-detail {

        color: var(--primary);

    }


    .action-detail:hover {

        background: var(--primary-light);

        border-color: #c9def5;

    }


    .action-edit {

        color: #f59e0b;

    }


    .action-edit:hover {

        background: #fff7e7;

        border-color: #f8dfad;

    }


    .action-delete {

        color: #dc3545;

    }


    .action-delete:hover {

        background: #fff0f1;

        border-color: #f2c8cc;

    }


    /* =====================================================
       STATUS
    ====================================================== */

    .status-badge {

        display: inline-flex;

        align-items: center;

        justify-content: center;

        padding: 4px 8px;

        border-radius: 999px;

        font-size: 9px;

        font-weight: 600;

        white-space: nowrap;

    }


    .status-success {

        background: #e9f8ef;

        color: #198754;

    }


    .status-warning {

        background: #fff4df;

        color: #b77900;

    }


    .status-info {

        background: #eaf4ff;

        color: #0d6efd;

    }


    .status-danger {

        background: #fff0f1;

        color: #dc3545;

    }


    .status-default {

        background: #f0f2f5;

        color: #697386;

    }


    /* =====================================================
       EMPTY
    ====================================================== */

    .empty-state {

        background: var(--white);

        border: 1px solid var(--border);

        border-radius: 12px;

        padding: 55px 20px;

        text-align: center;

    }


    .empty-icon {

        width: 58px;

        height: 58px;

        border-radius: 50%;

        background: var(--primary-light);

        color: var(--primary);

        display: flex;

        align-items: center;

        justify-content: center;

        margin: 0 auto 14px;

        font-size: 25px;

    }


    .empty-state h4 {

        margin: 0 0 5px;

        font-size: 14px;

        font-weight: 700;

    }


    .empty-state p {

        margin: 0;

        color: var(--muted);

        font-size: 11px;

    }


    /* =====================================================
       RESPONSIVE
    ====================================================== */

    @media (max-width: 1350px) {

        .maintenance-card {

            grid-template-columns:
                54px
                minmax(180px, 1.4fr)
                minmax(160px, 1.1fr)
                minmax(130px, 1fr)
                85px
                108px;

            gap: 11px;

        }

    }


    @media (max-width: 1200px) {

        .filter-grid {

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

        }


        .maintenance-card {

            grid-template-columns:
                54px
                minmax(170px, 1.4fr)
                minmax(150px, 1.1fr)
                minmax(120px, 1fr)
                82px
                105px;

            gap: 9px;

        }


        .maintenance-actions {

            justify-content: flex-end;

        }

    }


    @media (max-width: 1050px) {

        .maintenance-card {

            grid-template-columns:
                50px
                1.4fr
                1fr
                85px
                105px;

        }


        .maintenance-detail:nth-of-type(3) {

            display: none;

        }

    }


    @media (max-width: 992px) {

        .main {

            margin-left: 0;

        }


        .stats-grid {

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

        }


        .maintenance-card {

            grid-template-columns:
                54px
                minmax(180px, 1.4fr)
                minmax(150px, 1fr)
                90px
                108px;

        }


        /*
         * Pada ukuran ini informasi teknisi/tindakan
         * tetap ditampilkan sebagai kolom tersendiri.
         */

        .maintenance-actions {

            grid-column: auto;

            justify-content: flex-end;

        }

    }


    @media (max-width: 820px) {

        .maintenance-card {

            grid-template-columns:
                54px
                1fr
                95px
                108px;

        }


        .maintenance-card .maintenance-detail {

            grid-column: 2;

        }


        .maintenance-card .maintenance-date {

            grid-column: 3;

            grid-row: 1 / span 2;

        }


        .maintenance-card .maintenance-actions {

            grid-column: 4;

            grid-row: 1 / span 2;

        }

    }


    @media (max-width: 768px) {

        .content {

            padding: 18px;

        }


        .topbar {

            padding: 0 18px;

        }


        .user-info {

            display: none;

        }


        .page-header {

            align-items: flex-start;

            flex-direction: column;

        }


        .filter-grid {

            grid-template-columns: 1fr;

        }


        .date-grid {

            grid-template-columns: 1fr;

        }


        /*
         * Mobile:
         * card menjadi satu kolom visual,
         * aksi tetap berada paling bawah.
         */

        .maintenance-card {

            display: grid;

            grid-template-columns:
                48px
                1fr;

            gap: 10px;

        }


        .maintenance-icon {

            width: 44px;

            height: 44px;

        }


        .maintenance-main {

            grid-column: 2;

            grid-row: 1;

        }


        .maintenance-card .maintenance-detail {

            grid-column: 2;

        }


        .maintenance-card .maintenance-detail:nth-of-type(1) {

            grid-row: auto;

        }


        .maintenance-date {

            grid-column: 2 !important;

            grid-row: auto !important;

            text-align: left;

            padding-top: 3px;

        }


        .maintenance-date i {

            display: inline-block;

            margin-right: 5px;

            margin-bottom: 0;

        }


        .maintenance-date strong {

            display: inline-block;

        }


        .maintenance-actions {

            grid-column: 2 !important;

            grid-row: auto !important;

            justify-content: flex-start;

            padding-top: 2px;

        }

    }


    @media (max-width: 480px) {

        .stats-grid {

            grid-template-columns: 1fr;

        }


        .page-title {

            font-size: 17px;

        }


        .page-header h2 {

            font-size: 19px;

        }


        .btn-primary-custom {

            width: 100%;

            justify-content: center;

        }


        .filter-actions {

            flex-direction: column;

        }


        .btn-filter,
        .btn-reset {

            width: 100%;

        }

    }

</style>

</head>

<body>

<!-- =========================================================
     MAIN
========================================================= -->

<div class="main">

<!-- =====================================================
     TOPBAR
====================================================== -->

<header class="topbar">

    <div class="topbar-left">

        <button
            type="button"
            class="mobile-menu"
            id="mobileToggle"
        >
            <i class="bi bi-list"></i>
        </button>


        <div>

            <h1 class="page-title">
                Riwayat Maintenance
            </h1>

            <div class="page-subtitle">
                Kelola dan pantau seluruh aktivitas maintenance mesin
            </div>

        </div>

    </div>


    <div class="user-box">

        <div class="user-avatar">
            <i class="bi bi-person"></i>
        </div>


        <div class="user-info">

            <strong>
                <?= tampil(
                    $_SESSION['nama_lengkap']
                    ?? $_SESSION['username']
                    ?? 'Administrator'
                ); ?>
            </strong>

            <span>
                Administrator
            </span>

        </div>

    </div>

</header>


<!-- =====================================================
     CONTENT
====================================================== -->

<main class="content">


    <!-- =================================================
         PAGE HEADER
    ================================================== -->

    <div class="page-header">

        <div>

            <h2>
                Riwayat Maintenance
            </h2>

            <p>
                Catat, lihat, dan kelola histori maintenance komponen.
            </p>

        </div>


        <a
            href="<?= e($tambahUrl); ?>"
            class="btn-primary-custom"
        >

            <i class="bi bi-plus-lg"></i>

            Tambah Maintenance

        </a>

    </div>


    <!-- =================================================
         STATISTICS
    ================================================== -->

    <div class="stats-grid">


        <div class="stat-card">

            <div class="stat-icon stat-blue">

                <i class="bi bi-tools"></i>

            </div>


            <div class="stat-text">

                <span>
                    Total Maintenance
                </span>

                <strong>
                    <?= number_format($totalMaintenance); ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon stat-green">

                <i class="bi bi-check-circle"></i>

            </div>


            <div class="stat-text">

                <span>
                    Maintenance Selesai
                </span>

                <strong>
                    <?= number_format($totalSelesai); ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon stat-orange">

                <i class="bi bi-hourglass-split"></i>

            </div>


            <div class="stat-text">

                <span>
                    Dalam Proses
                </span>

                <strong>
                    <?= number_format($totalProses); ?>
                </strong>

            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon stat-gray">

                <i class="bi bi-three-dots"></i>

            </div>


            <div class="stat-text">

                <span>
                    Status Lainnya
                </span>

                <strong>
                    <?= number_format($totalLainnya); ?>
                </strong>

            </div>

        </div>


    </div>


    <!-- =================================================
         FILTER
    ================================================== -->

    <form
        method="GET"
        action="index.php"
        class="filter-card"
    >


        <div class="filter-title">

            <i class="bi bi-funnel"></i>

            Filter Riwayat Maintenance

        </div>


        <div class="filter-grid">


            <!-- SEARCH -->

            <div>

                <label class="form-label-custom">
                    Cari Maintenance
                </label>

                <input
                    type="text"
                    name="search"
                    class="form-control-custom"
                    value="<?= e($search); ?>"
                    placeholder="Komponen, mesin, teknisi, tindakan..."
                >

            </div>


            <!-- STATUS -->

            <div>

                <label class="form-label-custom">
                    Status
                </label>

                <select
                    name="status"
                    class="form-select-custom"
                >

                    <option value="">
                        Semua Status
                    </option>

                    <option
                        value="Selesai"
                        <?= $status === 'Selesai'
                            ? 'selected'
                            : ''; ?>
                    >
                        Selesai
                    </option>

                    <option
                        value="Proses"
                        <?= $status === 'Proses'
                            ? 'selected'
                            : ''; ?>
                    >
                        Proses
                    </option>

                    <option
                        value="Menunggu"
                        <?= $status === 'Menunggu'
                            ? 'selected'
                            : ''; ?>
                    >
                        Menunggu
                    </option>

                    <option
                        value="Batal"
                        <?= $status === 'Batal'
                            ? 'selected'
                            : ''; ?>
                    >
                        Batal
                    </option>

                </select>

            </div>


            <!-- AREA -->

            <div>

                <label class="form-label-custom">
                    Area
                </label>

                <select
                    name="id_area"
                    class="form-select-custom"
                >

                    <option value="">
                        Semua Area
                    </option>

                    <?php foreach ($areas as $area): ?>

                        <option
                            value="<?= (int)$area['id']; ?>"
                            <?= $id_area === (int)$area['id']
                                ? 'selected'
                                : ''; ?>
                        >
                            <?= e($area['nama_area']); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- MESIN -->

            <div>

                <label class="form-label-custom">
                    Mesin
                </label>

                <select
                    name="id_mesin"
                    class="form-select-custom"
                >

                    <option value="">
                        Semua Mesin
                    </option>

                    <?php foreach ($mesins as $mesin): ?>

                        <option
                            value="<?= (int)$mesin['id']; ?>"
                            data-area="<?= (int)$mesin['id_area']; ?>"
                            <?= $id_mesin === (int)$mesin['id']
                                ? 'selected'
                                : ''; ?>
                        >
                            <?= e($mesin['nama_mesin']); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- TANGGAL -->

            <div>

                <label class="form-label-custom">
                    Periode
                </label>

                <div class="date-grid">

                    <input
                        type="date"
                        name="tanggal_dari"
                        class="form-control-custom"
                        value="<?= e($tanggal_dari); ?>"
                        title="Tanggal mulai"
                    >

                    <input
                        type="date"
                        name="tanggal_sampai"
                        class="form-control-custom"
                        value="<?= e($tanggal_sampai); ?>"
                        title="Tanggal sampai"
                    >

                </div>

            </div>


        </div>


        <div class="filter-actions">

            <a
                href="<?= e($resetUrl); ?>"
                class="btn-reset"
            >

                <i class="bi bi-arrow-counterclockwise"></i>

                Reset

            </a>


            <button
                type="submit"
                class="btn-filter"
            >

                <i class="bi bi-funnel"></i>

                Terapkan Filter

            </button>

        </div>


    </form>


    <!-- =================================================
         RESULT BAR
    ================================================== -->

    <div class="result-bar">

        <div class="result-info">

            Menampilkan

            <strong>
                <?= number_format(count($maintenance)); ?>
            </strong>

            riwayat maintenance

            <?php if (
                $search !== ''
                || $status !== ''
                || $id_area > 0
                || $id_mesin > 0
                || $tanggal_dari !== ''
                || $tanggal_sampai !== ''
            ): ?>

                <span>
                    dengan filter aktif
                </span>

            <?php endif; ?>

        </div>

    </div>


    <!-- =================================================
         MAINTENANCE LIST
    ================================================== -->

    <?php if (!empty($maintenance)): ?>


        <div class="maintenance-list">


            <?php foreach ($maintenance as $item): ?>


                <?php

                /* =========================================
                   NAMA KOMPONEN
                ========================================== */

                $namaKomponen = trim(
                    $item['jenis_komponen'] ?? ''
                );

                if ($namaKomponen === '') {

                    $namaKomponen = trim(
                        $item['nama_bagian'] ?? ''
                    );

                }

                if ($namaKomponen === '') {

                    $namaKomponen = 'Komponen';

                }


                /* =========================================
                   NAMA MESIN
                ========================================== */

                $namaMesin = trim(
                    $item['mesin_master'] ?? ''
                );

                if ($namaMesin === '') {

                    $namaMesin = trim(
                        $item['nama_mesin'] ?? ''
                    );

                }


                /* =========================================
                   NAMA SUB MESIN
                ========================================== */

                $namaSubMesin = trim(
                    $item['sub_mesin_master'] ?? ''
                );

                if ($namaSubMesin === '') {

                    $namaSubMesin = trim(
                        $item['nama_sub_mesin'] ?? ''
                    );

                }


                ?>


                <div class="maintenance-card">


                    <!-- =================================================
                         1. ICON
                    ================================================== -->

                    <div class="maintenance-icon">

                        <i class="bi bi-tools"></i>

                    </div>


                    <!-- =================================================
                         2. KOMPONEN
                    ================================================== -->

                    <div class="maintenance-main">


                        <div class="maintenance-title">

                            <strong>
                                <?= e($namaKomponen); ?>
                            </strong>

                            <?= badgeStatus(
                                $item['status'] ?? ''
                            ); ?>

                        </div>


                        <div class="maintenance-subtitle">


                            <?php if (
                                !empty($item['serial_number'])
                            ): ?>

                                <span>

                                    <i class="bi bi-upc-scan"></i>

                                    <?= e(
                                        $item['serial_number']
                                    ); ?>

                                </span>

                            <?php endif; ?>


                            <?php if (
                                !empty($item['part_number'])
                            ): ?>

                                <span class="ms-2">

                                    <i class="bi bi-tag"></i>

                                    <?= e(
                                        $item['part_number']
                                    ); ?>

                                </span>

                            <?php endif; ?>


                        </div>


                    </div>


                    <!-- =================================================
                         3. MESIN
                    ================================================== -->

                    <div class="maintenance-detail">


                        <div class="detail-label">
                            Mesin
                        </div>


                        <div
                            class="detail-value truncate"
                            title="<?= e($namaMesin); ?>"
                        >

                            <i class="bi bi-cpu"></i>

                            <?= tampil($namaMesin); ?>

                        </div>


                        <div
                            class="detail-value truncate mt-1"
                            title="<?= e($namaSubMesin); ?>"
                        >

                            <i class="bi bi-boxes"></i>

                            <?= tampil($namaSubMesin); ?>

                        </div>


                    </div>


                    <!-- =================================================
                         4. TEKNISI / TINDAKAN
                    ================================================== -->

                    <div class="maintenance-detail">


                        <div class="detail-label">
                            Teknisi
                        </div>


                        <div class="detail-value truncate">

                            <i class="bi bi-person-gear"></i>

                            <?= tampil(
                                $item['teknisi'] ?? ''
                            ); ?>

                        </div>


                        <div
                            class="detail-value truncate mt-1"
                            title="<?= e(
                                $item['tindakan'] ?? ''
                            ); ?>"
                        >

                            <i class="bi bi-wrench-adjustable"></i>

                            <?= tampil(
                                $item['tindakan'] ?? ''
                            ); ?>

                        </div>


                    </div>


                    <!-- =================================================
                         5. TANGGAL
                    ================================================== -->

                    <div class="maintenance-date">

                        <i class="bi bi-calendar3"></i>

                        <strong>
                            <?= formatTanggal(
                                $item['tanggal'] ?? ''
                            ); ?>
                        </strong>

                    </div>


                    <!-- =================================================
                         6. ACTION — PALING KANAN
                    ================================================== -->

                    <div class="maintenance-actions">


                        <!-- DETAIL -->

                        <a
                            href="detail.php?id=<?= (int)$item['id']; ?>"
                            class="action-btn action-detail"
                            title="Detail"
                        >

                            <i class="bi bi-eye"></i>

                        </a>


                        <!-- EDIT -->

                        <a
                            href="edit.php?id=<?= (int)$item['id']; ?>"
                            class="action-btn action-edit"
                            title="Edit"
                        >

                            <i class="bi bi-pencil"></i>

                        </a>


                        <!-- HAPUS -->

                        <a
                            href="hapus.php?id=<?= (int)$item['id']; ?>"
                            class="action-btn action-delete"
                            title="Hapus"
                            onclick="return confirm('Yakin ingin menghapus riwayat maintenance ini?');"
                        >

                            <i class="bi bi-trash"></i>

                        </a>


                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <!-- =================================================
             EMPTY STATE
        ================================================== -->

        <div class="empty-state">


            <div class="empty-icon">

                <i class="bi bi-tools"></i>

            </div>


            <h4>
                Belum Ada Riwayat Maintenance
            </h4>


            <p>
                Belum ada data maintenance yang sesuai dengan filter.
            </p>


            <div class="mt-3">

                <a
                    href="<?= e($tambahUrl); ?>"
                    class="btn-primary-custom"
                >

                    <i class="bi bi-plus-lg"></i>

                    Tambah Maintenance

                </a>

            </div>


        </div>


    <?php endif; ?>


</main>

</div>

<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

/* =========================================================
   FILTER MESIN BERDASARKAN AREA
========================================================= */

const areaSelect =
    document.querySelector('select[name="id_area"]');

const mesinSelect =
    document.querySelector('select[name="id_mesin"]');


function filterMesin()
{
    if (!areaSelect || !mesinSelect) {
        return;
    }


    const areaId =
        areaSelect.value;


    const selectedValue =
        mesinSelect.value;


    let selectedStillValid = false;


    Array.from(
        mesinSelect.options
    ).forEach(function(option, index) {


        if (index === 0) {

            option.hidden = false;

            return;

        }


        const optionArea =
            option.getAttribute('data-area');


        if (
            areaId === '' ||
            optionArea === areaId
        ) {

            option.hidden = false;


            if (
                option.value === selectedValue
            ) {

                selectedStillValid = true;

            }

        } else {

            option.hidden = true;

        }

    });


    if (!selectedStillValid) {

        mesinSelect.value = '';

    }

}


if (areaSelect) {

    areaSelect.addEventListener(
        'change',
        filterMesin
    );

}


document.addEventListener(
    'DOMContentLoaded',
    filterMesin
);

</script>

<!-- =========================================================
     SIDEBAR ADMIN
     
     WAJIB menggunakan admin/sidebar.php
========================================================= -->

<?php include "../sidebar.php"; ?>

<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>
