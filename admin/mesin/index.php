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
if (strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') {
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
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* =========================================================
   DATA ADMIN
========================================================= */
$namaAdmin = $_SESSION['nama_lengkap']
    ?? $_SESSION['username']
    ?? 'Administrator';

/* =========================================================
   FILTER
========================================================= */
$search = trim($_GET['search'] ?? '');
$id_area = intval($_GET['id_area'] ?? 0);
$id_jenis = intval($_GET['id_jenis'] ?? 0);

/* =========================================================
   DATA AREA
========================================================= */
$areas = [];

$qArea = mysqli_query(
    $conn,
    "SELECT id, nama_area
     FROM area_bagian
     ORDER BY nama_area ASC"
);

if ($qArea) {
    while ($row = mysqli_fetch_assoc($qArea)) {
        $areas[] = $row;
    }
}

/* =========================================================
   DATA JENIS MESIN
========================================================= */
$jenisMesin = [];

$qJenis = mysqli_query(
    $conn,
    "SELECT
        jm.id,
        jm.id_area,
        jm.nama_jenis_mesin,
        ab.nama_area
     FROM jenis_mesin jm
     LEFT JOIN area_bagian ab
        ON ab.id = jm.id_area
     ORDER BY jm.nama_jenis_mesin ASC"
);

if ($qJenis) {
    while ($row = mysqli_fetch_assoc($qJenis)) {
        $jenisMesin[] = $row;
    }
}

/* =========================================================
   WHERE FILTER
========================================================= */
$where = [];

if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $search);

    $where[] = "(
        m.nama_mesin LIKE '%$safeSearch%'
        OR m.serial_number LIKE '%$safeSearch%'
        OR m.lokasi LIKE '%$safeSearch%'
        OR m.keterangan LIKE '%$safeSearch%'
    )";
}

if ($id_area > 0) {
    $where[] = "m.id_area = $id_area";
}

if ($id_jenis > 0) {
    $where[] = "m.id_jenis_mesin = $id_jenis";
}

$whereSQL = '';

if (!empty($where)) {
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

/* =========================================================
   STATISTIK
========================================================= */

/* Total Mesin */
$totalMesin = 0;

$qTotalMesin = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS jumlah
     FROM mesin"
);

if ($qTotalMesin) {
    $row = mysqli_fetch_assoc($qTotalMesin);
    $totalMesin = (int)($row['jumlah'] ?? 0);
}

/* Total Area */
$totalArea = 0;

$qTotalArea = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS jumlah
     FROM area_bagian"
);

if ($qTotalArea) {
    $row = mysqli_fetch_assoc($qTotalArea);
    $totalArea = (int)($row['jumlah'] ?? 0);
}

/* Total Jenis Mesin */
$totalJenis = 0;

$qTotalJenis = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS jumlah
     FROM jenis_mesin"
);

if ($qTotalJenis) {
    $row = mysqli_fetch_assoc($qTotalJenis);
    $totalJenis = (int)($row['jumlah'] ?? 0);
}

/* Total Sub Mesin */
$totalSubMesin = 0;

$qTotalSub = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS jumlah
     FROM sub_mesin"
);

if ($qTotalSub) {
    $row = mysqli_fetch_assoc($qTotalSub);
    $totalSubMesin = (int)($row['jumlah'] ?? 0);
}

/* =========================================================
   DATA MESIN
========================================================= */
$mesin = [];

$sqlMesin = "
    SELECT
        m.id,
        m.id_jenis_mesin,
        m.id_area,
        m.nama_mesin,
        m.serial_number,
        m.lokasi,
        m.keterangan,
        m.gambar,

        ab.nama_area,

        jm.nama_jenis_mesin,

        COUNT(sm.id) AS jumlah_sub_mesin

    FROM mesin m

    LEFT JOIN area_bagian ab
        ON ab.id = m.id_area

    LEFT JOIN jenis_mesin jm
        ON jm.id = m.id_jenis_mesin

    LEFT JOIN sub_mesin sm
        ON sm.id_mesin = m.id

    $whereSQL

    GROUP BY
        m.id,
        m.id_jenis_mesin,
        m.id_area,
        m.nama_mesin,
        m.serial_number,
        m.lokasi,
        m.keterangan,
        m.gambar,
        ab.nama_area,
        jm.nama_jenis_mesin

    ORDER BY
        m.nama_mesin ASC
";

$qMesin = mysqli_query($conn, $sqlMesin);

if ($qMesin) {
    while ($row = mysqli_fetch_assoc($qMesin)) {
        $mesin[] = $row;
    }
}

$jumlahDitampilkan = count($mesin);

/* =========================================================
   FILTER AKTIF
========================================================= */
$filterAktif = 0;

if ($search !== '') {
    $filterAktif++;
}

if ($id_area > 0) {
    $filterAktif++;
}

if ($id_jenis > 0) {
    $filterAktif++;
}

/* =========================================================
   NAMA AREA TERPILIH
========================================================= */
$namaAreaTerpilih = '';

if ($id_area > 0) {
    foreach ($areas as $area) {
        if ((int)$area['id'] === $id_area) {
            $namaAreaTerpilih = $area['nama_area'];
            break;
        }
    }
}

/* =========================================================
   NAMA JENIS TERPILIH
========================================================= */
$namaJenisTerpilih = '';

if ($id_jenis > 0) {
    foreach ($jenisMesin as $jenis) {
        if ((int)$jenis['id'] === $id_jenis) {
            $namaJenisTerpilih = $jenis['nama_jenis_mesin'];
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Daftar Mesin | Admin</title>

    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet">

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
            --warning: #f59f00;
            --danger: #dc3545;
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
           SIDEBAR
        ====================================================== */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;

            width: 240px;

            background: var(--white);
            border-right: 1px solid var(--border);

            z-index: 1050;

            display: flex;
            flex-direction: column;
        }

        .sidebar-logo {
            height: 76px;

            display: flex;
            align-items: center;

            padding: 0 22px;

            border-bottom: 1px solid var(--border);
        }

        .sidebar-logo img {
            max-width: 165px;
            max-height: 45px;
            object-fit: contain;
        }

        .sidebar-menu {
            padding: 18px 12px;
            overflow-y: auto;
            flex: 1;
        }

        .menu-label {
            padding: 8px 12px 10px;

            color: #a1a8b5;

            font-size: 10px;
            font-weight: 700;

            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;

            width: 100%;

            padding: 11px 13px;
            margin-bottom: 4px;

            color: #596273;

            border-radius: 9px;

            font-size: 13px;
            font-weight: 500;

            transition: .2s ease;
        }

        .menu-item i {
            width: 20px;
            text-align: center;
            font-size: 17px;
        }

        .menu-item:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .menu-item.active {
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
        }

        .sidebar-bottom {
            padding: 12px;
            border-top: 1px solid var(--border);
        }

        .logout-item {
            color: #dc3545;
        }

        .logout-item:hover {
            background: #fff1f2;
            color: #dc3545;
        }

        /* =====================================================
           MAIN
        ====================================================== */

        .main-wrapper {
            margin-left: 240px;
            min-height: 100vh;
        }

        /* =====================================================
           TOPBAR
        ====================================================== */

        .topbar {
            position: sticky;
            top: 0;

            height: 76px;

            background: rgba(255,255,255,.95);
            backdrop-filter: blur(10px);

            border-bottom: 1px solid var(--border);

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 30px;

            z-index: 1000;
        }

        .page-title {
            margin: 0;

            font-size: 20px;
            font-weight: 700;
        }

        .page-subtitle {
            margin: 3px 0 0;

            color: var(--muted);
            font-size: 12px;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-avatar {
            width: 38px;
            height: 38px;

            border-radius: 50%;

            background: var(--primary-light);
            color: var(--primary);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 17px;
        }

        .admin-name {
            font-size: 13px;
            font-weight: 600;
        }

        .admin-role {
            color: var(--muted);
            font-size: 10px;
        }

        /* =====================================================
           CONTENT
        ====================================================== */

        .content {
            padding: 28px 30px 40px;
        }

        /* =====================================================
           BREADCRUMB
        ====================================================== */

        .breadcrumb-wrap {
            margin-bottom: 20px;
        }

        .breadcrumb {
            margin: 0;
            font-size: 12px;
        }

        .breadcrumb-item a {
            color: var(--primary);
        }

        .breadcrumb-item.active {
            color: var(--muted);
        }

        /* =====================================================
           PAGE HEADER
        ====================================================== */

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 22px;
        }

        .page-header-left h2 {
            margin: 0;

            font-size: 23px;
            font-weight: 700;
        }

        .page-header-left p {
            margin: 5px 0 0;

            color: var(--muted);
            font-size: 12px;
        }

        .btn-primary-custom {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            border: 0;

            padding: 10px 16px;

            border-radius: 9px;

            background: var(--primary);
            color: #fff;

            font-size: 12px;
            font-weight: 600;

            transition: .2s ease;
        }

        .btn-primary-custom:hover {
            background: var(--primary-dark);
            color: #fff;
            transform: translateY(-1px);
        }

        /* =====================================================
           STAT CARDS
        ====================================================== */

        .stat-card {
            height: 100%;

            background: var(--white);

            border: 1px solid var(--border);
            border-radius: 14px;

            padding: 18px;

            display: flex;
            align-items: center;
            gap: 14px;

            box-shadow: 0 3px 12px rgba(20, 40, 80, .035);
        }

        .stat-icon {
            flex: 0 0 45px;

            width: 45px;
            height: 45px;

            border-radius: 11px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: var(--primary-light);
            color: var(--primary);

            font-size: 20px;
        }

        .stat-label {
            color: var(--muted);

            font-size: 11px;
            font-weight: 500;

            margin-bottom: 2px;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 700;
            line-height: 1.2;
        }

        /* =====================================================
           FILTER CARD
        ====================================================== */

        .filter-card {
            background: var(--white);

            border: 1px solid var(--border);
            border-radius: 14px;

            padding: 20px;

            margin-top: 24px;

            box-shadow: 0 3px 12px rgba(20, 40, 80, .035);
        }

        .filter-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-bottom: 16px;
        }

        .filter-title {
            display: flex;
            align-items: center;
            gap: 8px;

            font-size: 14px;
            font-weight: 600;
        }

        .filter-title i {
            color: var(--primary);
        }

        .filter-count {
            padding: 4px 9px;

            border-radius: 20px;

            background: var(--primary-light);
            color: var(--primary);

            font-size: 10px;
            font-weight: 600;
        }

        .form-label-custom {
            margin-bottom: 6px;

            color: #596273;

            font-size: 11px;
            font-weight: 600;
        }

        .form-control,
        .form-select {
            min-height: 42px;

            border: 1px solid var(--border);
            border-radius: 8px;

            font-size: 12px;

            box-shadow: none !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
        }

        .filter-buttons {
            display: flex;
            gap: 8px;
            align-items: end;
        }

        .btn-filter {
            min-height: 42px;

            border: 0;
            border-radius: 8px;

            padding: 0 15px;

            background: var(--primary);
            color: #fff;

            font-size: 11px;
            font-weight: 600;
        }

        .btn-filter:hover {
            background: var(--primary-dark);
            color: #fff;
        }

        .btn-reset {
            min-height: 42px;

            border: 1px solid var(--border);
            border-radius: 8px;

            padding: 0 14px;

            background: #fff;
            color: #596273;

            font-size: 11px;
            font-weight: 600;
        }

        .btn-reset:hover {
            background: #f8f9fb;
        }

        /* =====================================================
           ACTIVE FILTER
        ====================================================== */

        .active-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;

            margin-top: 15px;
        }

        .filter-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;

            padding: 5px 9px;

            background: #f3f7fc;
            border: 1px solid #dce9f7;

            color: var(--primary);

            border-radius: 20px;

            font-size: 10px;
            font-weight: 500;
        }

        /* =====================================================
           MACHINE SECTION
        ====================================================== */

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            margin-top: 28px;
            margin-bottom: 15px;
        }

        .section-title {
            margin: 0;

            font-size: 15px;
            font-weight: 700;
        }

        .section-result {
            color: var(--muted);
            font-size: 11px;
        }

        /* =====================================================
           MACHINE CARD
        ====================================================== */

        .machine-card {
            height: 100%;

            background: var(--white);

            border: 1px solid var(--border);
            border-radius: 14px;

            overflow: hidden;

            box-shadow: 0 3px 12px rgba(20, 40, 80, .035);

            transition: .2s ease;
        }

        .machine-card:hover {
            transform: translateY(-2px);

            box-shadow: 0 8px 22px rgba(20, 40, 80, .08);

            border-color: #dce5f0;
        }

        .machine-image {
            height: 155px;

            background: #f2f5f9;

            position: relative;

            overflow: hidden;
        }

        .machine-image img {
            width: 100%;
            height: 100%;

            object-fit: cover;

            transition: .3s ease;
        }

        .machine-card:hover .machine-image img {
            transform: scale(1.025);
        }

        .machine-image-empty {
            width: 100%;
            height: 100%;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #b4bbc6;

            font-size: 42px;
        }

        .machine-content {
            padding: 15px;
        }

        .machine-name {
            margin: 0;

            color: var(--text);

            font-size: 14px;
            font-weight: 700;

            line-height: 1.35;
        }

        .machine-serial {
            margin-top: 4px;

            color: var(--muted);

            font-size: 10px;
        }

        .machine-info {
            display: grid;
            grid-template-columns: 1fr 1fr;

            gap: 9px;

            margin-top: 14px;
        }

        .info-box {
            min-width: 0;
        }

        .info-label {
            margin-bottom: 2px;

            color: #9aa2af;

            font-size: 9px;
            font-weight: 500;
        }

        .info-value {
            color: #4b5565;

            font-size: 10px;
            font-weight: 600;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .machine-meta {
            margin-top: 12px;

            padding-top: 11px;

            border-top: 1px solid #f0f2f5;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 10px;
        }

        .machine-type {
            display: inline-flex;
            align-items: center;
            gap: 5px;

            max-width: 72%;

            color: var(--primary);

            font-size: 10px;
            font-weight: 600;
        }

        .machine-type span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .sub-count {
            display: inline-flex;
            align-items: center;
            gap: 4px;

            color: var(--muted);

            font-size: 9px;
            white-space: nowrap;
        }

        .machine-actions {
            display: flex;
            gap: 6px;

            margin-top: 12px;
        }

        .action-btn {
            flex: 1;

            min-height: 34px;

            border-radius: 7px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;

            font-size: 10px;
            font-weight: 600;

            transition: .2s ease;
        }

        .action-detail {
            border: 1px solid #dce9f7;
            background: #f4f8fd;
            color: var(--primary);
        }

        .action-detail:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .action-edit {
            border: 1px solid #e8e9ec;
            background: #fff;
            color: #596273;
        }

        .action-edit:hover {
            background: #f5f6f8;
            color: #3f4754;
        }

        .action-delete {
            flex: 0 0 34px;

            border: 1px solid #f3d5d9;
            background: #fff6f7;
            color: var(--danger);
        }

        .action-delete:hover {
            background: #ffecef;
            color: var(--danger);
        }

        /* =====================================================
           EMPTY STATE
        ====================================================== */

        .empty-card {
            background: var(--white);

            border: 1px dashed #d8dee8;
            border-radius: 14px;

            padding: 55px 20px;

            text-align: center;
        }

        .empty-icon {
            width: 60px;
            height: 60px;

            margin: 0 auto 14px;

            border-radius: 50%;

            background: #f2f5f9;
            color: #a5adba;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 25px;
        }

        .empty-title {
            margin-bottom: 5px;

            font-size: 14px;
            font-weight: 700;
        }

        .empty-text {
            margin: 0;

            color: var(--muted);

            font-size: 11px;
        }

        /* =====================================================
           FOOTER
        ====================================================== */

        .footer {
            padding: 20px 30px;

            border-top: 1px solid var(--border);

            color: var(--muted);

            font-size: 10px;

            text-align: center;
        }

        /* =====================================================
           MOBILE
        ====================================================== */

        .mobile-toggle {
            display: none;

            width: 38px;
            height: 38px;

            border: 1px solid var(--border);
            border-radius: 8px;

            background: #fff;
            color: var(--text);

            align-items: center;
            justify-content: center;
        }

        .sidebar-overlay {
            display: none;

            position: fixed;
            inset: 0;

            background: rgba(0,0,0,.35);

            z-index: 1040;
        }

        @media (max-width: 991.98px) {

            .sidebar {
                transform: translateX(-100%);
                transition: .25s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-overlay.show {
                display: block;
            }

            .main-wrapper {
                margin-left: 0;
            }

            .mobile-toggle {
                display: inline-flex;
            }

            .topbar {
                padding: 0 20px;
            }

            .content {
                padding: 24px 20px 35px;
            }

            .footer {
                padding: 18px 20px;
            }
        }

        @media (max-width: 767.98px) {

            .topbar {
                height: 68px;
            }

            .page-title {
                font-size: 17px;
            }

            .page-subtitle {
                display: none;
            }

            .admin-profile > div:last-child {
                display: none;
            }

            .content {
                padding: 20px 15px 30px;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .page-header .btn-primary-custom {
                width: 100%;
                justify-content: center;
            }

            .filter-card {
                padding: 15px;
            }

            .filter-buttons {
                width: 100%;
            }

            .btn-filter,
            .btn-reset {
                flex: 1;
            }

            .machine-image {
                height: 175px;
            }

            .section-header {
                align-items: flex-start;
                flex-direction: column;
                gap: 3px;
            }
        }

    </style>

</head>

<body>

<!-- =========================================================
     SIDEBAR OVERLAY
========================================================= -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay">
</div>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar" id="sidebar">

    <!-- LOGO -->
    <div class="sidebar-logo">

        <img
            src="../../assets/img/logo-garudafood.png"
            alt="Garudafood">

    </div>


    <!-- MENU -->
    <div class="sidebar-menu">

        <div class="menu-label">
            Menu Utama
        </div>

        <a
            href="../index.php"
            class="menu-item">

            <i class="bi bi-grid-1x2"></i>

            <span>
                Dashboard Admin
            </span>

        </a>


        <div class="menu-label mt-2">
            Master Data
        </div>

        <a
            href="../area/index.php"
            class="menu-item">

            <i class="bi bi-geo-alt"></i>

            <span>
                Data Area
            </span>

        </a>


        <a
            href="../jenis_mesin/index.php"
            class="menu-item">

            <i class="bi bi-cpu"></i>

            <span>
                Jenis Mesin
            </span>

        </a>


        <a
            href="index.php"
            class="menu-item active">

            <i class="bi bi-gear-wide-connected"></i>

            <span>
                Daftar Mesin
            </span>

        </a>


        <a
            href="../sub_mesin/index.php"
            class="menu-item">

            <i class="bi bi-diagram-3"></i>

            <span>
                Sub Mesin
            </span>

        </a>


        <a
            href="../komponen/index.php"
            class="menu-item">

            <i class="bi bi-box-seam"></i>

            <span>
                Data Komponen
            </span>

        </a>


        <div class="menu-label mt-2">
            Maintenance
        </div>


        <a
            href="../maintenance/index.php"
            class="menu-item">

            <i class="bi bi-wrench-adjustable-circle"></i>

            <span>
                Riwayat Maintenance
            </span>

        </a>


        <div class="menu-label mt-2">
            Pengaturan
        </div>


        <a
            href="../users/index.php"
            class="menu-item">

            <i class="bi bi-people"></i>

            <span>
                Manajemen User
            </span>

        </a>

    </div>


    <!-- LOGOUT -->
    <div class="sidebar-bottom">

        <a
            href="../../logout.php"
            class="menu-item logout-item">

            <i class="bi bi-box-arrow-right"></i>

            <span>
                Logout
            </span>

        </a>

    </div>

</aside>


<!-- =========================================================
     MAIN WRAPPER
========================================================= -->

<div class="main-wrapper">


    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <header class="topbar">

        <div class="d-flex align-items-center gap-3">

            <button
                type="button"
                class="mobile-toggle"
                id="mobileToggle">

                <i class="bi bi-list"></i>

            </button>


            <div>

                <h1 class="page-title">
                    Daftar Mesin
                </h1>

                <p class="page-subtitle">
                    Kelola seluruh data mesin yang terdaftar
                </p>

            </div>

        </div>


        <div class="admin-profile">

            <div class="admin-avatar">

                <i class="bi bi-person"></i>

            </div>

            <div>

                <div class="admin-name">
                    <?= e($namaAdmin) ?>
                </div>

                <div class="admin-role">
                    Administrator
                </div>

            </div>

        </div>

    </header>


    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <main class="content">


        <!-- BREADCRUMB -->
        <div class="breadcrumb-wrap">

            <nav aria-label="breadcrumb">

                <ol class="breadcrumb">

                    <li class="breadcrumb-item">
                        <a href="../index.php">
                            Dashboard Admin
                        </a>
                    </li>

                    <li class="breadcrumb-item active">
                        Daftar Mesin
                    </li>

                </ol>

            </nav>

        </div>


        <!-- PAGE HEADER -->
        <div class="page-header">

            <div class="page-header-left">

                <h2>
                    Daftar Mesin
                </h2>

                <p>
                    Lihat, cari, dan kelola data mesin.
                </p>

            </div>


            <a
                href="tambah.php"
                class="btn-primary-custom">

                <i class="bi bi-plus-lg"></i>

                Tambah Mesin

            </a>

        </div>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="row g-3">

            <!-- TOTAL MESIN -->
            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-gear-wide-connected"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Total Mesin
                        </div>

                        <div class="stat-value">
                            <?= number_format($totalMesin) ?>
                        </div>

                    </div>

                </div>

            </div>


            <!-- TOTAL AREA -->
            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-geo-alt"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Total Area
                        </div>

                        <div class="stat-value">
                            <?= number_format($totalArea) ?>
                        </div>

                    </div>

                </div>

            </div>


            <!-- JENIS MESIN -->
            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-cpu"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Jenis Mesin
                        </div>

                        <div class="stat-value">
                            <?= number_format($totalJenis) ?>
                        </div>

                    </div>

                </div>

            </div>


            <!-- SUB MESIN -->
            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="bi bi-diagram-3"></i>
                    </div>

                    <div>

                        <div class="stat-label">
                            Total Sub Mesin
                        </div>

                        <div class="stat-value">
                            <?= number_format($totalSubMesin) ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             FILTER
        ================================================== -->

        <div class="filter-card">

            <div class="filter-header">

                <div class="filter-title">

                    <i class="bi bi-funnel"></i>

                    Filter Daftar Mesin

                </div>


                <?php if ($filterAktif > 0): ?>

                    <div class="filter-count">

                        <?= $filterAktif ?> filter aktif

                    </div>

                <?php endif; ?>

            </div>


            <form
                method="GET"
                action="index.php">

                <div class="row g-3">


                    <!-- SEARCH -->
                    <div class="col-lg-5">

                        <label class="form-label-custom">
                            Cari Mesin
                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="<?= e($search) ?>"
                            placeholder="Nama mesin, serial number, lokasi...">

                    </div>


                    <!-- AREA -->
                    <div class="col-lg-3">

                        <label class="form-label-custom">
                            Area
                        </label>

                        <select
                            name="id_area"
                            class="form-select">

                            <option value="">
                                Semua Area
                            </option>

                            <?php foreach ($areas as $area): ?>

                                <option
                                    value="<?= (int)$area['id'] ?>"
                                    <?= $id_area === (int)$area['id'] ? 'selected' : '' ?>>

                                    <?= e($area['nama_area']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- JENIS -->
                    <div class="col-lg-3">

                        <label class="form-label-custom">
                            Jenis Mesin
                        </label>

                        <select
                            name="id_jenis"
                            class="form-select">

                            <option value="">
                                Semua Jenis Mesin
                            </option>

                            <?php foreach ($jenisMesin as $jenis): ?>

                                <option
                                    value="<?= (int)$jenis['id'] ?>"
                                    <?= $id_jenis === (int)$jenis['id'] ? 'selected' : '' ?>>

                                    <?= e($jenis['nama_jenis_mesin']) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- BUTTON -->
                    <div class="col-lg-1">

                        <label class="form-label-custom d-none d-lg-block">
                            &nbsp;
                        </label>

                        <div class="filter-buttons">

                            <button
                                type="submit"
                                class="btn-filter">

                                <i class="bi bi-search"></i>

                            </button>

                        </div>

                    </div>

                </div>


                <!-- ACTIVE FILTER -->
                <?php if ($filterAktif > 0): ?>

                    <div class="active-filter">

                        <?php if ($search !== ''): ?>

                            <span class="filter-badge">

                                <i class="bi bi-search"></i>

                                Pencarian:
                                <?= e($search) ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($namaAreaTerpilih !== ''): ?>

                            <span class="filter-badge">

                                <i class="bi bi-geo-alt"></i>

                                Area:
                                <?= e($namaAreaTerpilih) ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($namaJenisTerpilih !== ''): ?>

                            <span class="filter-badge">

                                <i class="bi bi-cpu"></i>

                                Jenis:
                                <?= e($namaJenisTerpilih) ?>

                            </span>

                        <?php endif; ?>


                        <a
                            href="index.php"
                            class="filter-badge">

                            <i class="bi bi-x-circle"></i>

                            Reset Filter

                        </a>

                    </div>

                <?php endif; ?>

            </form>

        </div>


        <!-- =================================================
             MACHINE LIST
        ================================================== -->

        <div class="section-header">

            <h3 class="section-title">
                Mesin Terdaftar
            </h3>

            <div class="section-result">

                Menampilkan
                <strong><?= number_format($jumlahDitampilkan) ?></strong>
                mesin

                <?php if ($filterAktif > 0): ?>

                    dari
                    <strong><?= number_format($totalMesin) ?></strong>
                    total mesin

                <?php endif; ?>

            </div>

        </div>


        <?php if (!empty($mesin)): ?>

            <div class="row g-3">

                <?php foreach ($mesin as $item): ?>

                    <?php

                    $gambar = trim($item['gambar'] ?? '');

                    $namaMesin = trim($item['nama_mesin'] ?? '');

                    $serial = trim($item['serial_number'] ?? '');

                    $lokasi = trim($item['lokasi'] ?? '');

                    $area = trim($item['nama_area'] ?? '');

                    $jenis = trim($item['nama_jenis_mesin'] ?? '');

                    $jumlahSub = (int)($item['jumlah_sub_mesin'] ?? 0);

                    ?>

                    <div class="col-md-6 col-xl-4 col-xxl-3">

                        <div class="machine-card">


                            <!-- FOTO -->
                            <div class="machine-image">

                                <?php if ($gambar !== ''): ?>

                                    <img
                                        src="../../uploads/mesin/<?= e($gambar) ?>"
                                        alt="<?= e($namaMesin) ?>"
                                        loading="lazy"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                                    <div
                                        class="machine-image-empty"
                                        style="display:none;">

                                        <i class="bi bi-gear-wide-connected"></i>

                                    </div>

                                <?php else: ?>

                                    <div class="machine-image-empty">

                                        <i class="bi bi-gear-wide-connected"></i>

                                    </div>

                                <?php endif; ?>

                            </div>


                            <!-- CONTENT -->
                            <div class="machine-content">


                                <!-- NAMA -->
                                <h4 class="machine-name">

                                    <?= e($namaMesin ?: 'Mesin Tanpa Nama') ?>

                                </h4>


                                <!-- SERIAL -->
                                <div class="machine-serial">

                                    <?php if ($serial !== ''): ?>

                                        <i class="bi bi-upc-scan"></i>

                                        <?= e($serial) ?>

                                    <?php else: ?>

                                        <i class="bi bi-upc-scan"></i>

                                        Serial number belum diisi

                                    <?php endif; ?>

                                </div>


                                <!-- INFO -->
                                <div class="machine-info">


                                    <!-- AREA -->
                                    <div class="info-box">

                                        <div class="info-label">
                                            AREA
                                        </div>

                                        <div
                                            class="info-value"
                                            title="<?= e($area ?: '-') ?>">

                                            <?= e($area ?: '-') ?>

                                        </div>

                                    </div>


                                    <!-- LOKASI -->
                                    <div class="info-box">

                                        <div class="info-label">
                                            LOKASI
                                        </div>

                                        <div
                                            class="info-value"
                                            title="<?= e($lokasi ?: '-') ?>">

                                            <?= e($lokasi ?: '-') ?>

                                        </div>

                                    </div>

                                </div>


                                <!-- META -->
                                <div class="machine-meta">


                                    <div
                                        class="machine-type"
                                        title="<?= e($jenis ?: '-') ?>">

                                        <i class="bi bi-cpu"></i>

                                        <span>
                                            <?= e($jenis ?: 'Jenis belum diatur') ?>
                                        </span>

                                    </div>


                                    <div class="sub-count">

                                        <i class="bi bi-diagram-3"></i>

                                        <?= number_format($jumlahSub) ?>
                                        Sub

                                    </div>

                                </div>


                                <!-- ACTION -->
                                <div class="machine-actions">


                                    <a
                                        href="detail.php?id=<?= (int)$item['id'] ?>"
                                        class="action-btn action-detail"
                                        title="Lihat Detail">

                                        <i class="bi bi-eye"></i>

                                        Detail

                                    </a>


                                    <a
                                        href="edit.php?id=<?= (int)$item['id'] ?>"
                                        class="action-btn action-edit"
                                        title="Edit Mesin">

                                        <i class="bi bi-pencil"></i>

                                        Edit

                                    </a>


                                    <a
                                        href="hapus.php?id=<?= (int)$item['id'] ?>"
                                        class="action-btn action-delete"
                                        title="Hapus Mesin"
                                        onclick="return confirm('Yakin ingin menghapus mesin ini? Data yang berkaitan dengan mesin ini dapat ikut terdampak.');">

                                        <i class="bi bi-trash3"></i>

                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>


        <?php else: ?>


            <!-- EMPTY -->
            <div class="empty-card">

                <div class="empty-icon">

                    <i class="bi bi-gear-wide-connected"></i>

                </div>

                <div class="empty-title">

                    Data mesin tidak ditemukan

                </div>

                <p class="empty-text">

                    <?php if ($filterAktif > 0): ?>

                        Tidak ada mesin yang sesuai dengan filter yang dipilih.

                    <?php else: ?>

                        Belum ada data mesin yang tersedia.

                    <?php endif; ?>

                </p>


                <?php if ($filterAktif > 0): ?>

                    <a
                        href="index.php"
                        class="btn-primary-custom mt-3">

                        <i class="bi bi-arrow-counterclockwise"></i>

                        Reset Filter

                    </a>

                <?php else: ?>

                    <a
                        href="tambah.php"
                        class="btn-primary-custom mt-3">

                        <i class="bi bi-plus-lg"></i>

                        Tambah Mesin

                    </a>

                <?php endif; ?>

            </div>


        <?php endif; ?>


    </main>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <footer class="footer">

        Inventory Mesin • PT Garudafood Putra Putri Jaya Tbk

    </footer>


</div>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<!-- =========================================================
     SIDEBAR MOBILE
========================================================= -->

<script>

    const mobileToggle =
        document.getElementById('mobileToggle');

    const sidebar =
        document.getElementById('sidebar');

    const sidebarOverlay =
        document.getElementById('sidebarOverlay');


    function bukaSidebar() {

        sidebar.classList.add('show');

        sidebarOverlay.classList.add('show');

    }


    function tutupSidebar() {

        sidebar.classList.remove('show');

        sidebarOverlay.classList.remove('show');

    }


    if (mobileToggle) {

        mobileToggle.addEventListener(
            'click',
            bukaSidebar
        );

    }


    if (sidebarOverlay) {

        sidebarOverlay.addEventListener(
            'click',
            tutupSidebar
        );

    }


    document
        .querySelectorAll('.sidebar .menu-item')
        .forEach(function(item) {

            item.addEventListener(
                'click',
                function() {

                    if (window.innerWidth <= 991) {
                        tutupSidebar();
                    }

                }
            );

        });

</script>

</body>

</html>