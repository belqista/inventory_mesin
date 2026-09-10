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
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* =========================================================
   AMBIL ID MESIN
========================================================= */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   AMBIL DATA MESIN
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        m.id,
        m.id_jenis_mesin,
        m.id_area,
        m.nama_mesin,
        m.serial_number,
        m.lokasi,
        m.keterangan,
        m.gambar,
        ab.nama_area,
        ab.lokasi AS lokasi_area,
        jm.nama_jenis_mesin,

        (
            SELECT COUNT(*)
            FROM sub_mesin sm
            WHERE sm.id_mesin = m.id
        ) AS jumlah_sub_mesin,

        (
            SELECT COUNT(*)
            FROM komponen k
            WHERE k.id_mesin = m.id
        ) AS jumlah_komponen

    FROM mesin m

    LEFT JOIN area_bagian ab
        ON ab.id = m.id_area

    LEFT JOIN jenis_mesin jm
        ON jm.id = m.id_jenis_mesin

    WHERE m.id = ?

    LIMIT 1"
);

if (!$stmt) {
    die("Query mesin gagal diproses.");
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$mesin = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

/* =========================================================
   JIKA DATA TIDAK DITEMUKAN
========================================================= */

if (!$mesin) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   DATA SUB MESIN
========================================================= */

$stmtSub = mysqli_prepare(
    $conn,
    "SELECT
        sm.id,
        sm.nama_sub_mesin,
        sm.serial_number,
        sm.keterangan,

        (
            SELECT COUNT(*)
            FROM komponen k
            WHERE k.id_sub_mesin = sm.id
        ) AS jumlah_komponen

    FROM sub_mesin sm

    WHERE sm.id_mesin = ?

    ORDER BY sm.nama_sub_mesin ASC"
);

$sub_mesin_list = [];

if ($stmtSub) {

    mysqli_stmt_bind_param($stmtSub, "i", $id);
    mysqli_stmt_execute($stmtSub);

    $resultSub = mysqli_stmt_get_result($stmtSub);

    while ($row = mysqli_fetch_assoc($resultSub)) {
        $sub_mesin_list[] = $row;
    }

    mysqli_stmt_close($stmtSub);
}

/* =========================================================
   DATA KOMPONEN MESIN
========================================================= */

$stmtKomponen = mysqli_prepare(
    $conn,
    "SELECT
        k.id,
        k.jenis_komponen,
        k.serial_number,
        k.brand,
        k.tipe,
        k.part_number,
        k.kondisi,
        k.lokasi,
        k.id_sub_mesin,

        sm.nama_sub_mesin

    FROM komponen k

    LEFT JOIN sub_mesin sm
        ON sm.id = k.id_sub_mesin

    WHERE k.id_mesin = ?

    ORDER BY k.id DESC

    LIMIT 20"
);

$komponen_list = [];

if ($stmtKomponen) {

    mysqli_stmt_bind_param($stmtKomponen, "i", $id);
    mysqli_stmt_execute($stmtKomponen);

    $resultKomponen = mysqli_stmt_get_result($stmtKomponen);

    while ($row = mysqli_fetch_assoc($resultKomponen)) {
        $komponen_list[] = $row;
    }

    mysqli_stmt_close($stmtKomponen);
}

/* =========================================================
   FORMAT KONDISI
========================================================= */

function badgeKondisi($kondisi)
{
    $kondisi = trim((string)$kondisi);

    if ($kondisi === 'Baik') {
        return '<span class="badge-status status-baik">
                    <i class="bi bi-check-circle-fill"></i>
                    Baik
                </span>';
    }

    if ($kondisi === 'Perlu Pemeriksaan') {
        return '<span class="badge-status status-periksa">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    Perlu Pemeriksaan
                </span>';
    }

    if ($kondisi === 'Dalam Perbaikan') {
        return '<span class="badge-status status-perbaikan">
                    <i class="bi bi-tools"></i>
                    Dalam Perbaikan
                </span>';
    }

    return '<span class="badge-status status-default">'
        . e($kondisi ?: '-')
        . '</span>';
}

/* =========================================================
   FOTO MESIN
========================================================= */

$gambarMesin = trim((string)($mesin['gambar'] ?? ''));

$pathGambar = "../../uploads/mesin/" . $gambarMesin;

$adaGambar = (
    $gambarMesin !== ''
    && file_exists($pathGambar)
);

/* =========================================================
   DATA TAMPILAN
========================================================= */

$namaMesin = trim((string)($mesin['nama_mesin'] ?? ''));

if ($namaMesin === '') {
    $namaMesin = 'Mesin';
}

$serialNumber = trim((string)($mesin['serial_number'] ?? ''));

$namaArea = trim((string)($mesin['nama_area'] ?? ''));

$namaJenis = trim((string)($mesin['nama_jenis_mesin'] ?? ''));

$lokasi = trim((string)($mesin['lokasi'] ?? ''));

$keterangan = trim((string)($mesin['keterangan'] ?? ''));

$jumlahSubMesin = intval($mesin['jumlah_sub_mesin'] ?? 0);

$jumlahKomponen = intval($mesin['jumlah_komponen'] ?? 0);

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Detail Mesin - <?= e($namaMesin) ?>
    </title>

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
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
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
            --warning: #d99b00;
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

        /* =====================================================
           SIDEBAR
        ====================================================== */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;

            width: 240px;
            height: 100vh;

            background: var(--white);
            border-right: 1px solid var(--border);

            z-index: 1000;

            display: flex;
            flex-direction: column;
        }

        .sidebar-logo {
            height: 76px;

            padding: 15px 20px;

            display: flex;
            align-items: center;

            border-bottom: 1px solid var(--border);
        }

        .sidebar-logo img {
            max-width: 155px;
            max-height: 48px;
            object-fit: contain;
        }

        .sidebar-menu {
            padding: 18px 12px;
            overflow-y: auto;
            flex: 1;
        }

        .menu-label {
            font-size: 10px;
            font-weight: 700;

            color: #a0a7b4;

            text-transform: uppercase;

            letter-spacing: .8px;

            margin: 10px 10px 8px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 11px;

            padding: 11px 12px;

            margin-bottom: 4px;

            border-radius: 10px;

            color: #566174;

            text-decoration: none;

            font-size: 13px;
            font-weight: 500;

            transition: .2s;
        }

        .menu-item i {
            width: 20px;

            font-size: 17px;

            text-align: center;
        }

        .menu-item:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .menu-item.active {
            background: var(--primary);
            color: #fff;
            font-weight: 600;
        }

        .sidebar-footer {
            padding: 14px 12px;

            border-top: 1px solid var(--border);
        }

        .logout-link {
            color: #dc3545;
        }

        .logout-link:hover {
            background: #fff0f1;
            color: #dc3545;
        }

        /* =====================================================
           MAIN
        ====================================================== */

        .main-content {
            margin-left: 240px;
            min-height: 100vh;
        }

        .topbar {
            height: 76px;

            background: var(--white);
            border-bottom: 1px solid var(--border);

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 28px;

            position: sticky;
            top: 0;

            z-index: 900;
        }

        .topbar-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .topbar-subtitle {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
        }

        .admin-info {
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

            font-size: 18px;
        }

        .admin-name {
            font-size: 12px;
            font-weight: 600;
        }

        .admin-role {
            font-size: 10px;
            color: var(--muted);
        }

        /* =====================================================
           CONTENT
        ====================================================== */

        .page-content {
            padding: 28px;
        }

        .breadcrumb-area {
            margin-bottom: 20px;
        }

        .breadcrumb {
            margin: 0;
            font-size: 12px;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: var(--muted);
        }

        /* =====================================================
           DETAIL HEADER
        ====================================================== */

        .detail-header {
            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 16px;

            padding: 22px;

            margin-bottom: 20px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;
        }

        .machine-title-area {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .machine-icon {
            width: 58px;
            height: 58px;

            border-radius: 14px;

            background: var(--primary-light);
            color: var(--primary);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 27px;
        }

        .machine-title {
            font-size: 21px;
            font-weight: 700;

            margin: 0 0 5px;
        }

        .machine-subtitle {
            color: var(--muted);
            font-size: 12px;
        }

        .header-actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            border-radius: 9px;
            font-size: 12px;
            font-weight: 600;
            padding: 9px 14px;
        }

        .btn-primary-custom {
            background: var(--primary);
            color: #fff;
            border: none;
        }

        .btn-primary-custom:hover {
            background: var(--primary-dark);
            color: #fff;
        }

        .btn-light-custom {
            background: #fff;
            color: #566174;
            border: 1px solid var(--border);
        }

        .btn-light-custom:hover {
            background: #f8fafc;
        }

        /* =====================================================
           CARDS
        ====================================================== */

        .card-box {
            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 16px;

            overflow: hidden;

            height: 100%;
        }

        .card-header-custom {
            padding: 17px 20px;

            border-bottom: 1px solid var(--border);

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header-title {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
        }

        .card-body-custom {
            padding: 20px;
        }

        /* =====================================================
           IMAGE
        ====================================================== */

        .machine-image-box {
            background: #f8fafc;

            border: 1px solid var(--border);

            border-radius: 13px;

            min-height: 260px;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;
        }

        .machine-image-box img {
            width: 100%;
            height: 100%;

            max-height: 360px;

            object-fit: contain;
        }

        .no-image {
            text-align: center;
            color: #a0a7b4;
            padding: 30px;
        }

        .no-image i {
            font-size: 50px;
            display: block;
            margin-bottom: 10px;
        }

        .no-image span {
            font-size: 12px;
        }

        /* =====================================================
           INFO
        ====================================================== */

        .info-grid {
            display: grid;

            grid-template-columns: repeat(2, minmax(0, 1fr));

            gap: 0;

            border-top: 1px solid var(--border);
            border-left: 1px solid var(--border);

            border-radius: 10px;

            overflow: hidden;
        }

        .info-item {
            padding: 14px 15px;

            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);

            min-height: 76px;
        }

        .info-label {
            font-size: 10px;

            color: var(--muted);

            margin-bottom: 5px;

            text-transform: uppercase;

            font-weight: 600;

            letter-spacing: .3px;
        }

        .info-value {
            font-size: 13px;

            font-weight: 600;

            color: var(--text);

            word-break: break-word;
        }

        .info-value.muted {
            color: var(--muted);
            font-weight: 500;
        }

        /* =====================================================
           STAT MINI
        ====================================================== */

        .mini-stat-wrapper {
            display: grid;

            grid-template-columns: repeat(2, 1fr);

            gap: 12px;

            margin-top: 15px;
        }

        .mini-stat {
            padding: 14px;

            border-radius: 12px;

            background: #f8fafc;

            border: 1px solid var(--border);
        }

        .mini-stat-icon {
            width: 32px;
            height: 32px;

            border-radius: 9px;

            background: var(--primary-light);
            color: var(--primary);

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 8px;
        }

        .mini-stat-number {
            font-size: 19px;
            font-weight: 700;
            line-height: 1;
        }

        .mini-stat-label {
            font-size: 10px;
            color: var(--muted);
            margin-top: 5px;
        }

        /* =====================================================
           KETERANGAN
        ====================================================== */

        .description-box {
            margin-top: 18px;

            padding: 15px;

            background: #f8fafc;

            border: 1px solid var(--border);

            border-radius: 10px;
        }

        .description-title {
            font-size: 11px;
            font-weight: 700;

            margin-bottom: 7px;
        }

        .description-text {
            color: #566174;

            font-size: 12px;

            line-height: 1.7;

            white-space: pre-line;
        }

        /* =====================================================
           TABLE
        ====================================================== */

        .table-wrapper {
            overflow-x: auto;
        }

        .table {
            margin: 0;
            font-size: 12px;
        }

        .table thead th {
            background: #f8fafc;

            color: #667085;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: .3px;

            font-weight: 700;

            padding: 12px;

            border-bottom: 1px solid var(--border);

            white-space: nowrap;
        }

        .table tbody td {
            padding: 13px 12px;

            vertical-align: middle;

            border-color: var(--border);
        }

        .table tbody tr:hover {
            background: #fbfcfe;
        }

        .sub-name {
            font-weight: 600;
            color: var(--text);
        }

        .sub-serial {
            color: var(--muted);
            font-size: 10px;
            margin-top: 3px;
        }

        /* =====================================================
           BADGES
        ====================================================== */

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;

            padding: 5px 8px;

            border-radius: 7px;

            font-size: 9px;

            font-weight: 600;

            white-space: nowrap;
        }

        .status-baik {
            background: #eaf8f0;
            color: #198754;
        }

        .status-periksa {
            background: #fff6df;
            color: #b07800;
        }

        .status-perbaikan {
            background: #fff0f1;
            color: #dc3545;
        }

        .status-default {
            background: #eef1f5;
            color: #687386;
        }

        /* =====================================================
           EMPTY
        ====================================================== */

        .empty-state {
            text-align: center;

            padding: 40px 20px;

            color: var(--muted);
        }

        .empty-state i {
            font-size: 35px;

            display: block;

            margin-bottom: 10px;
        }

        .empty-state div {
            font-size: 12px;
        }

        /* =====================================================
           MOBILE
        ====================================================== */

        .mobile-toggle {
            display: none;

            border: none;

            background: transparent;

            font-size: 23px;

            color: var(--text);
        }

        .sidebar-overlay {
            display: none;

            position: fixed;

            inset: 0;

            background: rgba(0, 0, 0, .35);

            z-index: 999;
        }

        @media (max-width: 991px) {

            .sidebar {
                transform: translateX(-100%);

                transition: .25s;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-overlay.show {
                display: block;
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
            }

            .topbar {
                padding: 0 18px;
            }

            .page-content {
                padding: 18px;
            }

        }

        @media (max-width: 767px) {

            .detail-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .header-actions {
                width: 100%;
            }

            .header-actions .btn {
                flex: 1;
            }

            .machine-title {
                font-size: 18px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .mini-stat-wrapper {
                grid-template-columns: 1fr 1fr;
            }

            .admin-info > div {
                display: none;
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
    id="sidebarOverlay"
    onclick="closeSidebar()"
></div>

<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar" id="sidebar">

    <div class="sidebar-logo">

        <img
            src="../../assets/img/logo-garudafood.png"
            alt="Garudafood"
        >

    </div>

    <div class="sidebar-menu">

        <div class="menu-label">
            Utama
        </div>

        <a
            href="../index.php"
            class="menu-item"
        >
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard Admin</span>
        </a>

        <div class="menu-label">
            Master Data
        </div>

        <a
            href="../area/index.php"
            class="menu-item"
        >
            <i class="bi bi-geo-alt"></i>
            <span>Data Area</span>
        </a>

        <a
            href="../jenis_mesin/index.php"
            class="menu-item"
        >
            <i class="bi bi-diagram-3"></i>
            <span>Jenis Mesin</span>
        </a>

        <a
            href="index.php"
            class="menu-item active"
        >
            <i class="bi bi-cpu"></i>
            <span>Daftar Mesin</span>
        </a>

        <a
            href="../sub_mesin/index.php"
            class="menu-item"
        >
            <i class="bi bi-hdd-stack"></i>
            <span>Sub Mesin</span>
        </a>

        <a
            href="../komponen/index.php"
            class="menu-item"
        >
            <i class="bi bi-box-seam"></i>
            <span>Data Komponen</span>
        </a>

        <div class="menu-label">
            Maintenance
        </div>

        <a
            href="../maintenance/index.php"
            class="menu-item"
        >
            <i class="bi bi-wrench-adjustable"></i>
            <span>Riwayat Maintenance</span>
        </a>

        <div class="menu-label">
            Sistem
        </div>

        <a
            href="../users/index.php"
            class="menu-item"
        >
            <i class="bi bi-people"></i>
            <span>Manajemen User</span>
        </a>

    </div>

    <div class="sidebar-footer">

        <a
            href="../../logout.php"
            class="menu-item logout-link"
        >
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>

    </div>

</aside>

<!-- =========================================================
     MAIN
========================================================= -->

<div class="main-content">

    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <header class="topbar">

        <div class="d-flex align-items-center gap-3">

            <button
                type="button"
                class="mobile-toggle"
                onclick="openSidebar()"
            >
                <i class="bi bi-list"></i>
            </button>

            <div>

                <h1 class="topbar-title">
                    Detail Mesin
                </h1>

                <div class="topbar-subtitle">
                    Informasi lengkap mesin
                </div>

            </div>

        </div>

        <div class="admin-info">

            <div class="admin-avatar">
                <i class="bi bi-person-fill"></i>
            </div>

            <div>

                <div class="admin-name">
                    <?= e($_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Admin') ?>
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

    <main class="page-content">

        <!-- =================================================
             BREADCRUMB
        ================================================== -->

        <div class="breadcrumb-area">

            <nav aria-label="breadcrumb">

                <ol class="breadcrumb">

                    <li class="breadcrumb-item">
                        <a href="../index.php">
                            Admin
                        </a>
                    </li>

                    <li class="breadcrumb-item">
                        <a href="index.php">
                            Daftar Mesin
                        </a>
                    </li>

                    <li class="breadcrumb-item active">
                        Detail
                    </li>

                </ol>

            </nav>

        </div>

        <!-- =================================================
             HEADER MESIN
        ================================================== -->

        <div class="detail-header">

            <div class="machine-title-area">

                <div class="machine-icon">
                    <i class="bi bi-cpu-fill"></i>
                </div>

                <div>

                    <h2 class="machine-title">
                        <?= e($namaMesin) ?>
                    </h2>

                    <div class="machine-subtitle">

                        <?= $namaJenis !== ''
                            ? e($namaJenis)
                            : 'Jenis mesin belum ditentukan'
                        ?>

                        <?php if ($serialNumber !== ''): ?>

                            <span class="mx-1">•</span>

                            SN:
                            <?= e($serialNumber) ?>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

            <div class="header-actions">

                <a
                    href="index.php"
                    class="btn btn-light-custom"
                >
                    <i class="bi bi-arrow-left me-1"></i>
                    Kembali
                </a>

            </div>

        </div>

        <!-- =================================================
             DETAIL UTAMA
        ================================================== -->

        <div class="row g-4">

            <!-- =============================================
                 FOTO
            ============================================== -->

            <div class="col-lg-4">

                <div class="card-box">

                    <div class="card-header-custom">

                        <h3 class="card-header-title">
                            <i class="bi bi-image me-2"></i>
                            Foto Mesin
                        </h3>

                    </div>

                    <div class="card-body-custom">

                        <div class="machine-image-box">

                            <?php if ($adaGambar): ?>

                                <img
                                    src="<?= e($pathGambar) ?>"
                                    alt="<?= e($namaMesin) ?>"
                                >

                            <?php else: ?>

                                <div class="no-image">

                                    <i class="bi bi-image"></i>

                                    <span>
                                        Foto mesin belum tersedia
                                    </span>

                                </div>

                            <?php endif; ?>

                        </div>

                        <!-- MINI STAT -->

                        <div class="mini-stat-wrapper">

                            <div class="mini-stat">

                                <div class="mini-stat-icon">
                                    <i class="bi bi-hdd-stack"></i>
                                </div>

                                <div class="mini-stat-number">
                                    <?= number_format($jumlahSubMesin) ?>
                                </div>

                                <div class="mini-stat-label">
                                    Sub Mesin
                                </div>

                            </div>

                            <div class="mini-stat">

                                <div class="mini-stat-icon">
                                    <i class="bi bi-box-seam"></i>
                                </div>

                                <div class="mini-stat-number">
                                    <?= number_format($jumlahKomponen) ?>
                                </div>

                                <div class="mini-stat-label">
                                    Komponen
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- =============================================
                 INFORMASI MESIN
            ============================================== -->

            <div class="col-lg-8">

                <div class="card-box">

                    <div class="card-header-custom">

                        <h3 class="card-header-title">
                            <i class="bi bi-info-circle me-2"></i>
                            Informasi Mesin
                        </h3>

                    </div>

                    <div class="card-body-custom">

                        <div class="info-grid">

                            <div class="info-item">

                                <div class="info-label">
                                    Nama Mesin
                                </div>

                                <div class="info-value">
                                    <?= e($namaMesin) ?>
                                </div>

                            </div>

                            <div class="info-item">

                                <div class="info-label">
                                    Serial Number
                                </div>

                                <div class="info-value <?= $serialNumber === '' ? 'muted' : '' ?>">

                                    <?= $serialNumber !== ''
                                        ? e($serialNumber)
                                        : '-'
                                    ?>

                                </div>

                            </div>

                            <div class="info-item">

                                <div class="info-label">
                                    Area
                                </div>

                                <div class="info-value <?= $namaArea === '' ? 'muted' : '' ?>">

                                    <?= $namaArea !== ''
                                        ? e($namaArea)
                                        : '-'
                                    ?>

                                </div>

                            </div>

                            <div class="info-item">

                                <div class="info-label">
                                    Jenis Mesin
                                </div>

                                <div class="info-value <?= $namaJenis === '' ? 'muted' : '' ?>">

                                    <?= $namaJenis !== ''
                                        ? e($namaJenis)
                                        : '-'
                                    ?>

                                </div>

                            </div>

                            <div class="info-item">

                                <div class="info-label">
                                    Lokasi Mesin
                                </div>

                                <div class="info-value <?= $lokasi === '' ? 'muted' : '' ?>">

                                    <?= $lokasi !== ''
                                        ? e($lokasi)
                                        : '-'
                                    ?>

                                </div>

                            </div>

                            <div class="info-item">

                                <div class="info-label">
                                    Jumlah Sub Mesin
                                </div>

                                <div class="info-value">

                                    <?= number_format($jumlahSubMesin) ?>

                                    <span
                                        style="
                                            color:#7b8494;
                                            font-weight:400;
                                        "
                                    >
                                        sub mesin
                                    </span>

                                </div>

                            </div>

                            <div class="info-item">

                                <div class="info-label">
                                    Jumlah Komponen
                                </div>

                                <div class="info-value">

                                    <?= number_format($jumlahKomponen) ?>

                                    <span
                                        style="
                                            color:#7b8494;
                                            font-weight:400;
                                        "
                                    >
                                        komponen
                                    </span>

                                </div>

                            </div>

                            <div class="info-item">

                                <div class="info-label">
                                    ID Internal
                                </div>

                                <div class="info-value muted">
                                    Data internal
                                </div>

                            </div>

                        </div>

                        <!-- KETERANGAN -->

                        <div class="description-box">

                            <div class="description-title">
                                <i class="bi bi-card-text me-1"></i>
                                Keterangan
                            </div>

                            <div class="description-text">

                                <?= $keterangan !== ''
                                    ? e($keterangan)
                                    : 'Tidak ada keterangan untuk mesin ini.'
                                ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- =================================================
             SUB MESIN
        ================================================== -->

        <div class="card-box mt-4">

            <div class="card-header-custom">

                <h3 class="card-header-title">
                    <i class="bi bi-hdd-stack me-2"></i>
                    Sub Mesin
                </h3>

                <span
                    class="badge rounded-pill text-bg-light"
                    style="font-size:10px;"
                >
                    <?= number_format($jumlahSubMesin) ?> data
                </span>

            </div>

            <?php if (count($sub_mesin_list) > 0): ?>

                <div class="table-wrapper">

                    <table class="table">

                        <thead>

                            <tr>

                                <th>
                                    Nama Sub Mesin
                                </th>

                                <th>
                                    Serial Number
                                </th>

                                <th>
                                    Komponen
                                </th>

                                <th>
                                    Keterangan
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($sub_mesin_list as $sub): ?>

                                <tr>

                                    <td>

                                        <div class="sub-name">

                                            <?= e($sub['nama_sub_mesin']) ?>

                                        </div>

                                    </td>

                                    <td>

                                        <?= trim((string)$sub['serial_number']) !== ''
                                            ? e($sub['serial_number'])
                                            : '<span class="text-muted">-</span>'
                                        ?>

                                    </td>

                                    <td>

                                        <span
                                            style="
                                                font-weight:600;
                                                color:#075eaa;
                                            "
                                        >
                                            <?= number_format((int)$sub['jumlah_komponen']) ?>
                                        </span>

                                    </td>

                                    <td>

                                        <?php
                                        $ketSub = trim((string)$sub['keterangan']);
                                        ?>

                                        <?= $ketSub !== ''
                                            ? e($ketSub)
                                            : '<span class="text-muted">-</span>'
                                        ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <i class="bi bi-hdd-stack"></i>

                    <div>
                        Belum ada sub mesin yang terdaftar pada mesin ini.
                    </div>

                </div>

            <?php endif; ?>

        </div>

        <!-- =================================================
             KOMPONEN
        ================================================== -->

        <div class="card-box mt-4">

            <div class="card-header-custom">

                <h3 class="card-header-title">
                    <i class="bi bi-box-seam me-2"></i>
                    Komponen Mesin
                </h3>

                <span
                    class="badge rounded-pill text-bg-light"
                    style="font-size:10px;"
                >
                    <?=
                        number_format($jumlahKomponen)
                    ?>
                    total
                </span>

            </div>

            <?php if (count($komponen_list) > 0): ?>

                <div class="table-wrapper">

                    <table class="table">

                        <thead>

                            <tr>

                                <th>
                                    Komponen
                                </th>

                                <th>
                                    Sub Mesin
                                </th>

                                <th>
                                    Serial Number
                                </th>

                                <th>
                                    Brand / Tipe
                                </th>

                                <th>
                                    Part Number
                                </th>

                                <th>
                                    Kondisi
                                </th>

                                <th>
                                    Lokasi
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($komponen_list as $komponen): ?>

                                <tr>

                                    <td>

                                        <div class="sub-name">

                                            <?= trim((string)$komponen['jenis_komponen']) !== ''
                                                ? e($komponen['jenis_komponen'])
                                                : 'Komponen'
                                            ?>

                                        </div>

                                    </td>

                                    <td>

                                        <?= trim((string)$komponen['nama_sub_mesin']) !== ''
                                            ? e($komponen['nama_sub_mesin'])
                                            : '<span class="text-muted">-</span>'
                                        ?>

                                    </td>

                                    <td>

                                        <?= trim((string)$komponen['serial_number']) !== ''
                                            ? e($komponen['serial_number'])
                                            : '<span class="text-muted">-</span>'
                                        ?>

                                    </td>

                                    <td>

                                        <?php

                                        $brand = trim(
                                            (string)($komponen['brand'] ?? '')
                                        );

                                        $tipe = trim(
                                            (string)($komponen['tipe'] ?? '')
                                        );

                                        ?>

                                        <?php if ($brand !== ''): ?>

                                            <div style="font-weight:600;">
                                                <?= e($brand) ?>
                                            </div>

                                        <?php endif; ?>

                                        <?php if ($tipe !== ''): ?>

                                            <div
                                                style="
                                                    font-size:10px;
                                                    color:#7b8494;
                                                "
                                            >
                                                <?= e($tipe) ?>
                                            </div>

                                        <?php endif; ?>

                                        <?php if ($brand === '' && $tipe === ''): ?>

                                            <span class="text-muted">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?= trim((string)$komponen['part_number']) !== ''
                                            ? e($komponen['part_number'])
                                            : '<span class="text-muted">-</span>'
                                        ?>

                                    </td>

                                    <td>

                                        <?= badgeKondisi($komponen['kondisi']) ?>

                                    </td>

                                    <td>

                                        <?= trim((string)$komponen['lokasi']) !== ''
                                            ? e($komponen['lokasi'])
                                            : '<span class="text-muted">-</span>'
                                        ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="empty-state">

                    <i class="bi bi-box-seam"></i>

                    <div>
                        Belum ada komponen yang terdaftar pada mesin ini.
                    </div>

                </div>

            <?php endif; ?>

        </div>

        <!-- =================================================
             FOOTER ACTION
        ================================================== -->

        <div
            class="d-flex justify-content-between align-items-center mt-4"
        >

            <div
                style="
                    color:#7b8494;
                    font-size:11px;
                "
            >
                <i class="bi bi-info-circle me-1"></i>
                Detail mesin dan data turunannya.
            </div>

            <a
                href="index.php"
                class="btn btn-light-custom"
            >
                <i class="bi bi-arrow-left me-1"></i>
                Kembali ke Daftar Mesin
            </a>

        </div>

    </main>

</div>

<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

<script>

    function openSidebar() {

        document
            .getElementById('sidebar')
            .classList
            .add('show');

        document
            .getElementById('sidebarOverlay')
            .classList
            .add('show');

    }

    function closeSidebar() {

        document
            .getElementById('sidebar')
            .classList
            .remove('show');

        document
            .getElementById('sidebarOverlay')
            .classList
            .remove('show');

    }

</script>

</body>
</html>
