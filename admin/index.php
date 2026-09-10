<?php

session_start();

require_once "../koneksi.php";

date_default_timezone_set('Asia/Jakarta');


/* =========================================================
   PROTEKSI LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit;

}


/* =========================================================
   PROTEKSI ADMIN
========================================================= */

$role = strtolower(trim($_SESSION['role'] ?? ''));

if ($role !== 'admin') {

    header("Location: ../dashboard/index.php");
    exit;

}


/* =========================================================
   DATA USER LOGIN
========================================================= */

$nama_lengkap =
    $_SESSION['nama_lengkap']
    ?? $_SESSION['username']
    ?? 'Administrator';


/* =========================================================
   SIDEBAR
   Sidebar sekarang menggunakan file terpisah
========================================================= */

$base_admin = '';

$active_menu = 'dashboard';


/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function ambil_count($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);

    return (int)($row['total'] ?? 0);
}


/* =========================================================
   BADGE MAINTENANCE
========================================================= */

function badge_maintenance($status)
{
    $status_original = trim((string)$status);

    $status_lower = strtolower($status_original);


    if ($status_lower === 'selesai') {

        return '
            <span class="status-badge status-success">
                <i class="bi bi-check-circle-fill"></i>
                Selesai
            </span>
        ';
    }


    if ($status_lower === 'proses') {

        return '
            <span class="status-badge status-warning">
                <i class="bi bi-arrow-repeat"></i>
                Proses
            </span>
        ';
    }


    if ($status_lower === 'pending') {

        return '
            <span class="status-badge status-info">
                <i class="bi bi-clock-fill"></i>
                Pending
            </span>
        ';
    }


    return '
        <span class="status-badge status-info">
            <i class="bi bi-info-circle-fill"></i>
            ' . e($status_original ?: 'Tidak diketahui') . '
        </span>
    ';
}


/* =========================================================
   BADGE KONDISI
========================================================= */

function badge_kondisi($kondisi)
{
    $kondisi_original = trim((string)$kondisi);

    $kondisi_lower = strtolower($kondisi_original);


    if ($kondisi_lower === 'baik') {

        return '
            <span class="status-badge status-success">
                <i class="bi bi-check-circle-fill"></i>
                Baik
            </span>
        ';
    }


    if (strpos($kondisi_lower, 'perlu') !== false) {

        return '
            <span class="status-badge status-warning">
                <i class="bi bi-exclamation-circle-fill"></i>
                ' . e($kondisi_original) . '
            </span>
        ';
    }


    if (strpos($kondisi_lower, 'perbaikan') !== false) {

        return '
            <span class="status-badge status-danger">
                <i class="bi bi-tools"></i>
                ' . e($kondisi_original) . '
            </span>
        ';
    }


    return '
        <span class="status-badge status-info">
            <i class="bi bi-question-circle"></i>
            ' . e($kondisi_original ?: 'Tidak diketahui') . '
        </span>
    ';
}


/* =========================================================
   STATISTIK MASTER DATA
========================================================= */

$total_area = ambil_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM area_bagian"
);


$total_jenis_mesin = ambil_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM jenis_mesin"
);


$total_mesin = ambil_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM mesin"
);


$total_sub_mesin = ambil_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM sub_mesin"
);


$total_komponen = ambil_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM komponen"
);


$total_users = ambil_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users"
);


/* =========================================================
   STATISTIK MAINTENANCE
========================================================= */

$total_maintenance = ambil_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM riwayat_maintenance"
);


$total_maintenance_selesai = ambil_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM riwayat_maintenance
     WHERE LOWER(TRIM(status)) = 'selesai'"
);


$total_maintenance_proses = ambil_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM riwayat_maintenance
     WHERE LOWER(TRIM(status)) = 'proses'"
);


$total_maintenance_pending = ambil_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM riwayat_maintenance
     WHERE LOWER(TRIM(status)) = 'pending'"
);


/* =========================================================
   KOMPONEN BERMASALAH
========================================================= */

$total_komponen_bermasalah = ambil_count(
    $conn,
    "SELECT COUNT(*) AS total
     FROM komponen
     WHERE kondisi IS NOT NULL
     AND TRIM(kondisi) <> ''
     AND LOWER(TRIM(kondisi)) <> 'baik'"
);


/* =========================================================
   DATA KOMPONEN BERMASALAH
========================================================= */

$komponen_bermasalah = [];


$sql_komponen_bermasalah = "

    SELECT

        k.id,
        k.serial_number,

        k.id_area,
        k.id_jenis_mesin,
        k.id_mesin,
        k.id_sub_mesin,

        k.mesin,
        k.sub_mesin,

        k.nama_bagian,
        k.jenis_komponen,
        k.spesifikasi,
        k.kategori,

        k.brand,
        k.tipe,
        k.part_number,

        k.lokasi,
        k.kondisi,
        k.keterangan,
        k.gambar,

        ab.nama_area,

        jm.nama_jenis_mesin,

        m.nama_mesin,

        sm.nama_sub_mesin

    FROM komponen k

    LEFT JOIN area_bagian ab
        ON ab.id = k.id_area

    LEFT JOIN jenis_mesin jm
        ON jm.id = k.id_jenis_mesin

    LEFT JOIN mesin m
        ON m.id = k.id_mesin

    LEFT JOIN sub_mesin sm
        ON sm.id = k.id_sub_mesin

    WHERE k.kondisi IS NOT NULL

    AND TRIM(k.kondisi) <> ''

    AND LOWER(TRIM(k.kondisi)) <> 'baik'

    ORDER BY k.id DESC

    LIMIT 8

";


$result_komponen =
    mysqli_query(
        $conn,
        $sql_komponen_bermasalah
    );


if ($result_komponen) {

    while ($row = mysqli_fetch_assoc($result_komponen)) {

        $komponen_bermasalah[] = $row;

    }

}


/* =========================================================
   MAINTENANCE TERBARU
========================================================= */

$maintenance_terbaru = [];


$sql_maintenance = "

    SELECT

        rm.id,
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
        k.kondisi

    FROM riwayat_maintenance rm

    LEFT JOIN komponen k
        ON k.id = rm.id_komponen

    ORDER BY

        rm.tanggal DESC,
        rm.id DESC

    LIMIT 8

";


$result_maintenance =
    mysqli_query(
        $conn,
        $sql_maintenance
    );


if ($result_maintenance) {

    while ($row = mysqli_fetch_assoc($result_maintenance)) {

        $maintenance_terbaru[] = $row;

    }

}


/* =========================================================
   USER TERBARU
========================================================= */

$users_terbaru = [];


$sql_users = "

    SELECT

        id,
        username,
        nama_lengkap,
        role,
        status

    FROM users

    ORDER BY id DESC

    LIMIT 6

";


$result_users =
    mysqli_query(
        $conn,
        $sql_users
    );


if ($result_users) {

    while ($row = mysqli_fetch_assoc($result_users)) {

        $users_terbaru[] = $row;

    }

}


/* =========================================================
   CHART 6 BULAN TERAKHIR
========================================================= */

$chart_labels = [];

$chart_data = [];


$bulan_indonesia = [

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


$stmt_chart = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM riwayat_maintenance
     WHERE YEAR(tanggal) = ?
     AND MONTH(tanggal) = ?"
);


for ($i = 5; $i >= 0; $i--) {

    $timestamp = strtotime("-{$i} month");

    $tahun = (int)date('Y', $timestamp);

    $bulan = (int)date('n', $timestamp);


    $chart_labels[] =
        $bulan_indonesia[$bulan]
        . ' '
        . $tahun;


    $jumlah = 0;


    if ($stmt_chart) {

        mysqli_stmt_bind_param(
            $stmt_chart,
            'ii',
            $tahun,
            $bulan
        );


        mysqli_stmt_execute(
            $stmt_chart
        );


        $result_chart =
            mysqli_stmt_get_result(
                $stmt_chart
            );


        if ($result_chart) {

            $row_chart =
                mysqli_fetch_assoc(
                    $result_chart
                );


            $jumlah =
                (int)(
                    $row_chart['total']
                    ?? 0
                );

        }

    }


    $chart_data[] = $jumlah;

}


if ($stmt_chart) {

    mysqli_stmt_close(
        $stmt_chart
    );

}

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
        Dashboard Admin | Inventory Mesin
    </title>


    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
        crossorigin
    >

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


    <!-- =====================================================
         CHART JS
    ====================================================== -->

    <script
        src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js">
    </script>


    <style>

        /* =====================================================
           ROOT
        ====================================================== */

        :root {

            --primary: #075eaa;
            --primary-dark: #064d8c;
            --primary-light: #eaf4ff;

            --text: #172033;
            --muted: #7a8495;

            --bg: #f5f8fc;
            --white: #ffffff;

            --border: #e6ebf2;

            --success: #16a36a;
            --success-bg: #e9f8f1;

            --danger: #e5484d;
            --danger-bg: #fff0f0;

            --warning: #f59e0b;
            --warning-bg: #fff6df;

            --info: #0d6efd;
            --info-bg: #edf4ff;

            --sidebar-width: 240px;

        }


        /* =====================================================
           GLOBAL
        ====================================================== */

        * {
            box-sizing: border-box;
        }


        html {
            scroll-behavior: smooth;
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


        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }


        /* =====================================================
           MAIN WRAPPER
        ====================================================== */

        .main-wrapper {

            margin-left: var(--sidebar-width);

            min-height: 100vh;

        }


        /* =====================================================
           TOPBAR
        ====================================================== */

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


        .topbar-left {

            display: flex;

            align-items: center;

            gap: 13px;

            min-width: 0;

        }


        .mobile-toggle {

            display: none;

            border: 0;

            background: transparent;

            color: var(--text);

            font-size: 24px;

            padding: 4px;

            line-height: 1;

        }


        .mobile-toggle:hover {

            color: var(--primary);

        }


        .page-title {

            margin: 0;

            font-size: 19px;

            font-weight: 700;

            color: var(--text);

        }


        .page-subtitle {

            margin: 2px 0 0;

            font-size: 11px;

            color: var(--muted);

        }


        /* =====================================================
           ADMIN PROFILE
        ====================================================== */

        .admin-profile {

            display: flex;

            align-items: center;

            gap: 11px;

            flex-shrink: 0;

        }


        .admin-profile-info {

            text-align: right;

        }


        .admin-name {

            font-size: 12px;

            font-weight: 600;

            color: var(--text);

        }


        .admin-role {

            font-size: 10px;

            color: var(--muted);

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

            font-weight: 700;

        }


        /* =====================================================
           CONTENT
        ====================================================== */

        .content {

            padding: 28px;

        }


        /* =====================================================
           WELCOME
        ====================================================== */

        .welcome-box {

            background:
                linear-gradient(
                    135deg,
                    var(--primary),
                    var(--primary-dark)
                );

            color: #ffffff;

            border-radius: 16px;

            padding: 25px 28px;

            margin-bottom: 24px;

            position: relative;

            overflow: hidden;

        }


        .welcome-box::after {

            content: '';

            position: absolute;

            width: 190px;

            height: 190px;

            border-radius: 50%;

            right: -65px;

            top: -85px;

            background:
                rgba(255, 255, 255, .08);

        }


        .welcome-box::before {

            content: '';

            position: absolute;

            width: 100px;

            height: 100px;

            border-radius: 50%;

            right: 80px;

            bottom: -70px;

            background:
                rgba(255, 255, 255, .05);

        }


        .welcome-content {

            position: relative;

            z-index: 2;

        }


        .welcome-title {

            font-size: 20px;

            font-weight: 700;

            margin-bottom: 5px;

        }


        .welcome-text {

            margin: 0;

            font-size: 12px;

            opacity: .88;

        }


        /* =====================================================
           STAT CARD
        ====================================================== */

        .stat-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 14px;

            padding: 18px;

            height: 100%;

            transition:
                transform .2s ease,
                box-shadow .2s ease;

        }


        .stat-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 25px
                rgba(20, 40, 80, .07);

        }


        .stat-icon {

            width: 42px;

            height: 42px;

            border-radius: 11px;

            background: var(--primary-light);

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 19px;

            margin-bottom: 13px;

        }


        .stat-label {

            color: var(--muted);

            font-size: 11px;

            font-weight: 500;

            margin-bottom: 4px;

        }


        .stat-value {

            color: var(--text);

            font-size: 25px;

            font-weight: 700;

            line-height: 1.2;

        }


        /* =====================================================
           SECTION CARD
        ====================================================== */

        .section-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 14px;

            overflow: hidden;

        }


        .section-header {

            padding: 18px 20px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            border-bottom: 1px solid var(--border);

        }


        .section-title {

            margin: 0;

            font-size: 14px;

            font-weight: 700;

            color: var(--text);

        }


        .section-description {

            margin: 3px 0 0;

            font-size: 10px;

            color: var(--muted);

        }


        .section-body {

            padding: 20px;

        }


        .view-all {

            font-size: 11px;

            font-weight: 600;

            color: var(--primary);

            white-space: nowrap;

        }


        .view-all:hover {

            color: var(--primary-dark);

        }


        /* =====================================================
           QUICK ACTION
        ====================================================== */

        .quick-action {

            display: flex;

            align-items: center;

            gap: 11px;

            padding: 13px;

            border: 1px solid var(--border);

            border-radius: 11px;

            color: var(--text);

            height: 100%;

            transition: all .2s ease;

        }


        .quick-action:hover {

            border-color: #cbdcf0;

            background: var(--primary-light);

            color: var(--primary);

            transform: translateY(-1px);

        }


        .quick-action-icon {

            width: 38px;

            height: 38px;

            border-radius: 9px;

            background: var(--primary-light);

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            font-size: 16px;

        }


        .quick-action-title {

            font-size: 11px;

            font-weight: 600;

        }


        .quick-action-text {

            font-size: 9px;

            color: var(--muted);

            margin-top: 2px;

        }


        /* =====================================================
           MINI STAT
        ====================================================== */

        .mini-stat {

            border: 1px solid var(--border);

            border-radius: 12px;

            padding: 15px;

            height: 100%;

        }


        .mini-stat-icon {

            width: 35px;

            height: 35px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: var(--primary-light);

            color: var(--primary);

            margin-bottom: 10px;

        }


        .mini-stat-label {

            font-size: 10px;

            color: var(--muted);

            margin-bottom: 3px;

        }


        .mini-stat-value {

            font-size: 21px;

            font-weight: 700;

            color: var(--text);

        }


        /* =====================================================
           STATUS BADGE
        ====================================================== */

        .status-badge {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 5px;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 600;

            white-space: nowrap;

        }


        .status-success {

            color: #168653;

            background: var(--success-bg);

        }


        .status-warning {

            color: #a16207;

            background: var(--warning-bg);

        }


        .status-danger {

            color: #dc3545;

            background: var(--danger-bg);

        }


        .status-info {

            color: #0d6efd;

            background: var(--info-bg);

        }


        /* =====================================================
           PROBLEM LIST
        ====================================================== */

        .problem-list {

            max-height: 365px;

            overflow-y: auto;

            padding-right: 4px;

        }


        .problem-list::-webkit-scrollbar {

            width: 5px;

        }


        .problem-list::-webkit-scrollbar-track {

            background: #f3f5f8;

            border-radius: 10px;

        }


        .problem-list::-webkit-scrollbar-thumb {

            background: #cbd3df;

            border-radius: 10px;

        }


        .problem-item {

            padding: 12px 0;

            border-bottom: 1px solid var(--border);

        }


        .problem-item:first-child {

            padding-top: 0;

        }


        .problem-item:last-child {

            border-bottom: 0;

            padding-bottom: 0;

        }


        .problem-icon {

            width: 36px;

            height: 36px;

            border-radius: 9px;

            background: var(--danger-bg);

            color: var(--danger);

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            font-size: 15px;

        }


        .problem-title {

            font-size: 11px;

            font-weight: 600;

            color: var(--text);

            margin-bottom: 3px;

        }


        .problem-meta {

            font-size: 9px;

            color: var(--muted);

            line-height: 1.6;

        }


        /* =====================================================
           CHART
        ====================================================== */

        .chart-wrapper {

            height: 300px;

            position: relative;

        }


        /* =====================================================
           TABLE
        ====================================================== */

        .table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        .dashboard-table {

            width: 100%;

            min-width: 760px;

            border-collapse: collapse;

        }


        .dashboard-table th {

            padding: 11px 12px;

            background: #fafbfd;

            color: #8992a2;

            font-size: 10px;

            font-weight: 600;

            white-space: nowrap;

            border-bottom: 1px solid var(--border);

        }


        .dashboard-table td {

            padding: 12px;

            color: #4e586a;

            font-size: 11px;

            border-bottom: 1px solid #f0f2f5;

            vertical-align: middle;

        }


        .dashboard-table tbody tr:last-child td {

            border-bottom: 0;

        }


        .dashboard-table tbody tr:hover {

            background: #fbfcfe;

        }


        .table-primary-text {

            color: var(--text);

            font-weight: 600;

            font-size: 11px;

        }


        .table-secondary-text {

            color: var(--muted);

            font-size: 9px;

            margin-top: 2px;

        }


        /* =====================================================
           EMPTY STATE
        ====================================================== */

        .empty-state {

            text-align: center;

            padding: 30px 15px;

            color: var(--muted);

        }


        .empty-state i {

            font-size: 32px;

            display: block;

            margin-bottom: 8px;

            opacity: .5;

        }


        .empty-state p {

            margin: 0;

            font-size: 11px;

        }


        /* =====================================================
           FOOTER
        ====================================================== */

        .footer {

            padding:
                20px
                28px
                28px;

            color: var(--muted);

            font-size: 10px;

            text-align: center;

        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 991px) {

            .main-wrapper {

                margin-left: 0;

            }


            .mobile-toggle {

                display: flex;

                align-items: center;

                justify-content: center;

            }


            .topbar {

                padding: 0 18px;

            }


            .content {

                padding: 18px;

            }


            .footer {

                padding:
                    15px
                    18px
                    25px;

            }

        }


        /* =====================================================
           TABLET / SMALL
        ====================================================== */

        @media (max-width: 768px) {

            .admin-profile-info {

                display: none;

            }


            .admin-avatar {

                width: 36px;

                height: 36px;

            }


            .welcome-box {

                padding: 21px;

            }


            .welcome-title {

                font-size: 18px;

            }


            .welcome-text {

                font-size: 11px;

            }


            .chart-wrapper {

                height: 270px;

            }

        }


        /* =====================================================
           MOBILE
        ====================================================== */

        @media (max-width: 576px) {

            .page-title {

                font-size: 16px;

            }


            .page-subtitle {

                display: none;

            }


            .topbar {

                height: 70px;

            }


            .content {

                padding: 14px;

            }


            .welcome-box {

                margin-bottom: 18px;

                padding: 19px;

                border-radius: 14px;

            }


            .welcome-title {

                font-size: 16px;

            }


            .welcome-text {

                font-size: 10px;

                line-height: 1.7;

            }


            .stat-card {

                padding: 15px;

            }


            .stat-icon {

                width: 38px;

                height: 38px;

                font-size: 17px;

            }


            .stat-label {

                font-size: 10px;

            }


            .stat-value {

                font-size: 21px;

            }


            .section-header {

                padding: 15px;

            }


            .section-body {

                padding: 15px;

            }


            .section-title {

                font-size: 13px;

            }


            .section-description {

                font-size: 9px;

            }


            .quick-action {

                padding: 11px;

            }


            .quick-action-icon {

                width: 35px;

                height: 35px;

            }


            .chart-wrapper {

                height: 240px;

            }


            .footer {

                font-size: 9px;

            }

        }

    </style>

</head>


<body>


<?php

/* =========================================================
   SIDEBAR TERPUSAT
========================================================= */

include "sidebar.php";

?>


<!-- =========================================================
     MAIN WRAPPER
========================================================== -->

<div class="main-wrapper">


    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <header class="topbar">


        <div class="topbar-left">


            <button
                type="button"
                class="mobile-toggle"
                id="mobileToggle"
                aria-label="Buka menu">

                <i class="bi bi-list"></i>

            </button>


            <div>

                <h1 class="page-title">
                    Dashboard Admin
                </h1>

                <p class="page-subtitle">
                    Monitoring dan pengelolaan inventory mesin
                </p>

            </div>


        </div>


        <!-- =================================================
             ADMIN PROFILE
        ================================================== -->

        <div class="admin-profile">


            <div class="admin-profile-info">

                <div class="admin-name">
                    <?= e($nama_lengkap); ?>
                </div>

                <div class="admin-role">
                    Administrator
                </div>

            </div>


            <div class="admin-avatar">

                <i class="bi bi-person-fill"></i>

            </div>


        </div>


    </header>


    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <main class="content">


        <!-- =================================================
             WELCOME
        ================================================== -->

        <div class="welcome-box">

            <div class="welcome-content">

                <div class="welcome-title">

                    Selamat Datang,
                    <?= e($nama_lengkap); ?> 👋

                </div>


                <p class="welcome-text">

                    Kelola data area, mesin, komponen,
                    maintenance, dan pengguna melalui
                    dashboard administrator.

                </p>

            </div>

        </div>


        <!-- =================================================
             STATISTIK
        ================================================== -->

        <div class="row g-3 mb-4">


            <!-- AREA -->

            <div class="col-6 col-md-4 col-xl-2">

                <div class="stat-card">

                    <div class="stat-icon">

                        <i class="bi bi-geo-alt-fill"></i>

                    </div>


                    <div class="stat-label">
                        Total Area
                    </div>


                    <div class="stat-value">
                        <?= number_format($total_area); ?>
                    </div>

                </div>

            </div>


            <!-- JENIS MESIN -->

            <div class="col-6 col-md-4 col-xl-2">

                <div class="stat-card">

                    <div class="stat-icon">

                        <i class="bi bi-diagram-3-fill"></i>

                    </div>


                    <div class="stat-label">
                        Jenis Mesin
                    </div>


                    <div class="stat-value">
                        <?= number_format($total_jenis_mesin); ?>
                    </div>

                </div>

            </div>


            <!-- MESIN -->

            <div class="col-6 col-md-4 col-xl-2">

                <div class="stat-card">

                    <div class="stat-icon">

                        <i class="bi bi-cpu-fill"></i>

                    </div>


                    <div class="stat-label">
                        Total Mesin
                    </div>


                    <div class="stat-value">
                        <?= number_format($total_mesin); ?>
                    </div>

                </div>

            </div>


            <!-- SUB MESIN -->

            <div class="col-6 col-md-4 col-xl-2">

                <div class="stat-card">

                    <div class="stat-icon">

                        <i class="bi bi-boxes"></i>

                    </div>


                    <div class="stat-label">
                        Sub Mesin
                    </div>


                    <div class="stat-value">
                        <?= number_format($total_sub_mesin); ?>
                    </div>

                </div>

            </div>


            <!-- KOMPONEN -->

            <div class="col-6 col-md-4 col-xl-2">

                <div class="stat-card">

                    <div class="stat-icon">

                        <i class="bi bi-gear-wide-connected"></i>

                    </div>


                    <div class="stat-label">
                        Komponen
                    </div>


                    <div class="stat-value">
                        <?= number_format($total_komponen); ?>
                    </div>

                </div>

            </div>


            <!-- USERS -->

            <div class="col-6 col-md-4 col-xl-2">

                <div class="stat-card">

                    <div class="stat-icon">

                        <i class="bi bi-people-fill"></i>

                    </div>


                    <div class="stat-label">
                        Users
                    </div>


                    <div class="stat-value">
                        <?= number_format($total_users); ?>
                    </div>

                </div>

            </div>


        </div>


        <!-- =================================================
             AKSES CEPAT
        ================================================== -->

        <div class="section-card mb-4">


            <div class="section-header">

                <div>

                    <h2 class="section-title">
                        Akses Cepat
                    </h2>

                    <p class="section-description">
                        Menu utama yang sering digunakan administrator
                    </p>

                </div>

            </div>


            <div class="section-body">

                <div class="row g-3">


                    <!-- DATA AREA -->

                    <div class="col-12 col-sm-6 col-lg-3">

                        <a
                            href="/inventory_mesin/admin/area/index.php"
                            class="quick-action">

                            <div class="quick-action-icon">

                                <i class="bi bi-geo-alt-fill"></i>

                            </div>


                            <div>

                                <div class="quick-action-title">
                                    Data Area
                                </div>

                                <div class="quick-action-text">
                                    Kelola area dan lokasi
                                </div>

                            </div>

                        </a>

                    </div>


                    <!-- JENIS MESIN -->

                    <div class="col-12 col-sm-6 col-lg-3">

                        <a
                            href="/inventory_mesin/admin/jenis_mesin/index.php"
                            class="quick-action">

                            <div class="quick-action-icon">

                                <i class="bi bi-diagram-3-fill"></i>

                            </div>


                            <div>

                                <div class="quick-action-title">
                                    Jenis Mesin
                                </div>

                                <div class="quick-action-text">
                                    Kelola jenis mesin
                                </div>

                            </div>

                        </a>

                    </div>


                    <!-- DAFTAR MESIN -->

                    <div class="col-12 col-sm-6 col-lg-3">

                        <a
                            href="/inventory_mesin/admin/mesin/index.php"
                            class="quick-action">

                            <div class="quick-action-icon">

                                <i class="bi bi-cpu-fill"></i>

                            </div>


                            <div>

                                <div class="quick-action-title">
                                    Daftar Mesin
                                </div>

                                <div class="quick-action-text">
                                    Kelola daftar mesin
                                </div>

                            </div>

                        </a>

                    </div>


                    <!-- SUB MESIN -->

                    <div class="col-12 col-sm-6 col-lg-3">

                        <a
                            href="/inventory_mesin/admin/sub_mesin/index.php"
                            class="quick-action">

                            <div class="quick-action-icon">

                                <i class="bi bi-boxes"></i>

                            </div>


                            <div>

                                <div class="quick-action-title">
                                    Sub Mesin
                                </div>

                                <div class="quick-action-text">
                                    Kelola sub mesin
                                </div>

                            </div>

                        </a>

                    </div>


                    <!-- KOMPONEN -->

                    <div class="col-12 col-sm-6 col-lg-3">

                        <a
                            href="/inventory_mesin/admin/komponen/index.php"
                            class="quick-action">

                            <div class="quick-action-icon">

                                <i class="bi bi-gear-wide-connected"></i>

                            </div>


                            <div>

                                <div class="quick-action-title">
                                    Data Komponen
                                </div>

                                <div class="quick-action-text">
                                    Kelola komponen mesin
                                </div>

                            </div>

                        </a>

                    </div>


                    <!-- MAINTENANCE -->

                    <div class="col-12 col-sm-6 col-lg-3">

                        <a
                            href="/inventory_mesin/admin/maintenance/index.php"
                            class="quick-action">

                            <div class="quick-action-icon">

                                <i class="bi bi-tools"></i>

                            </div>


                            <div>

                                <div class="quick-action-title">
                                    Maintenance
                                </div>

                                <div class="quick-action-text">
                                    Kelola maintenance
                                </div>

                            </div>

                        </a>

                    </div>


                    <!-- USERS -->

                    <div class="col-12 col-sm-6 col-lg-3">

                        <a
                            href="/inventory_mesin/admin/users/index.php"
                            class="quick-action">

                            <div class="quick-action-icon">

                                <i class="bi bi-people-fill"></i>

                            </div>


                            <div>

                                <div class="quick-action-title">
                                    Manajemen User
                                </div>

                                <div class="quick-action-text">
                                    Kelola akun pengguna
                                </div>

                            </div>

                        </a>

                    </div>


                    <!-- RIWAYAT -->

                    <div class="col-12 col-sm-6 col-lg-3">

                        <a
                            href="/inventory_mesin/admin/maintenance/index.php"
                            class="quick-action">

                            <div class="quick-action-icon">

                                <i class="bi bi-clock-history"></i>

                            </div>


                            <div>

                                <div class="quick-action-title">
                                    Riwayat Maintenance
                                </div>

                                <div class="quick-action-text">
                                    Lihat seluruh riwayat
                                </div>

                            </div>

                        </a>

                    </div>


                </div>

            </div>

        </div>


        <!-- =================================================
             RINGKASAN MAINTENANCE
        ================================================== -->

        <div class="section-card mb-4">


            <div class="section-header">

                <div>

                    <h2 class="section-title">
                        Ringkasan Maintenance
                    </h2>

                    <p class="section-description">
                        Kondisi aktivitas maintenance mesin
                    </p>

                </div>


                <a
                    href="/inventory_mesin/admin/maintenance/index.php"
                    class="view-all">

                    Lihat Maintenance

                    <i class="bi bi-arrow-right"></i>

                </a>

            </div>


            <div class="section-body">

                <div class="row g-3">


                    <!-- TOTAL -->

                    <div class="col-12 col-md-4">

                        <div class="mini-stat">

                            <div class="mini-stat-icon">

                                <i class="bi bi-tools"></i>

                            </div>


                            <div class="mini-stat-label">
                                Total Maintenance
                            </div>


                            <div class="mini-stat-value">
                                <?= number_format($total_maintenance); ?>
                            </div>

                        </div>

                    </div>


                    <!-- SELESAI -->

                    <div class="col-12 col-md-4">

                        <div class="mini-stat">

                            <div class="mini-stat-icon">

                                <i class="bi bi-check-circle"></i>

                            </div>


                            <div class="mini-stat-label">
                                Maintenance Selesai
                            </div>


                            <div class="mini-stat-value">
                                <?= number_format($total_maintenance_selesai); ?>
                            </div>

                        </div>

                    </div>


                    <!-- PROSES -->

                    <div class="col-12 col-md-4">

                        <div class="mini-stat">

                            <div class="mini-stat-icon">

                                <i class="bi bi-arrow-repeat"></i>

                            </div>


                            <div class="mini-stat-label">
                                Maintenance Proses
                            </div>


                            <div class="mini-stat-value">
                                <?= number_format($total_maintenance_proses); ?>
                            </div>

                        </div>

                    </div>


                </div>

            </div>

        </div>


        <!-- =================================================
             AKTIVITAS + KOMPONEN BERMASALAH
        ================================================== -->

        <div class="row g-4 mb-4">


            <!-- AKTIVITAS MAINTENANCE -->

            <div class="col-12 col-xl-8">

                <div class="section-card h-100">


                    <div class="section-header">

                        <div>

                            <h2 class="section-title">
                                Aktivitas Maintenance
                            </h2>

                            <p class="section-description">
                                Jumlah maintenance 6 bulan terakhir
                            </p>

                        </div>

                    </div>


                    <div class="section-body">

                        <div class="chart-wrapper">

                            <canvas
                                id="maintenanceChart">
                            </canvas>

                        </div>

                    </div>


                </div>

            </div>


            <!-- KOMPONEN BERMASALAH -->

            <div class="col-12 col-xl-4">

                <div class="section-card h-100">


                    <div class="section-header">

                        <div>

                            <h2 class="section-title">
                                Komponen Bermasalah
                            </h2>

                            <p class="section-description">
                                Kondisi selain Baik
                            </p>

                        </div>


                        <span class="status-badge status-danger">

                            <?= number_format(
                                $total_komponen_bermasalah
                            ); ?>

                        </span>

                    </div>


                    <div class="section-body">


                        <?php if (
                            empty($komponen_bermasalah)
                        ): ?>


                            <div class="empty-state">

                                <i class="bi bi-check-circle"></i>

                                <p>
                                    Tidak ada komponen bermasalah.
                                </p>

                            </div>


                        <?php else: ?>


                            <div class="problem-list">


                                <?php foreach (
                                    $komponen_bermasalah
                                    as $komponen
                                ): ?>


                                    <?php

                                    $mesin_tampil =
                                        $komponen['nama_mesin']
                                        ?: $komponen['mesin']
                                        ?: '-';


                                    $sub_mesin_tampil =
                                        $komponen['nama_sub_mesin']
                                        ?: $komponen['sub_mesin']
                                        ?: '-';

                                    ?>


                                    <div class="problem-item">


                                        <div class="d-flex gap-3">


                                            <div class="problem-icon">

                                                <i class="bi bi-gear-wide-connected"></i>

                                            </div>


                                            <div class="flex-grow-1">


                                                <div class="problem-title">

                                                    <?= e(
                                                        $komponen[
                                                            'jenis_komponen'
                                                        ]
                                                        ?: 'Komponen'
                                                    ); ?>

                                                </div>


                                                <div class="problem-meta">

                                                    <strong>
                                                        Mesin:
                                                    </strong>

                                                    <?= e(
                                                        $mesin_tampil
                                                    ); ?>

                                                    <br>


                                                    <strong>
                                                        Sub:
                                                    </strong>

                                                    <?= e(
                                                        $sub_mesin_tampil
                                                    ); ?>

                                                </div>


                                                <div class="mt-2">

                                                    <?= badge_kondisi(
                                                        $komponen['kondisi']
                                                    ); ?>

                                                </div>


                                            </div>


                                        </div>


                                    </div>


                                <?php endforeach; ?>


                            </div>


                            <div class="text-center mt-3">

                                <a
                                    href="/inventory_mesin/admin/komponen/index.php"
                                    class="view-all">

                                    Lihat Semua Komponen

                                    <i class="bi bi-arrow-right"></i>

                                </a>

                            </div>


                        <?php endif; ?>


                    </div>

                </div>

            </div>


        </div>


        <!-- =================================================
             MAINTENANCE TERBARU
        ================================================== -->

        <div class="section-card mb-4">


            <div class="section-header">

                <div>

                    <h2 class="section-title">
                        Maintenance Terbaru
                    </h2>

                    <p class="section-description">
                        Riwayat maintenance terbaru
                    </p>

                </div>


                <a
                    href="/inventory_mesin/admin/maintenance/index.php"
                    class="view-all">

                    Lihat Semua

                    <i class="bi bi-arrow-right"></i>

                </a>

            </div>


            <div class="table-wrapper">


                <?php if (
                    empty($maintenance_terbaru)
                ): ?>


                    <div class="empty-state">

                        <i class="bi bi-tools"></i>

                        <p>
                            Belum ada riwayat maintenance.
                        </p>

                    </div>


                <?php else: ?>


                    <table class="dashboard-table">


                        <thead>

                            <tr>

                                <th>
                                    Tanggal
                                </th>

                                <th>
                                    Komponen
                                </th>

                                <th>
                                    Mesin
                                </th>

                                <th>
                                    Sub Mesin
                                </th>

                                <th>
                                    Tindakan
                                </th>

                                <th>
                                    Teknisi
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $maintenance_terbaru
                                as $maintenance
                            ): ?>


                                <tr>


                                    <td>

                                        <?php

                                        $tanggal =
                                            !empty(
                                                $maintenance['tanggal']
                                            )
                                            ? strtotime(
                                                $maintenance['tanggal']
                                            )
                                            : false;

                                        ?>


                                        <?= $tanggal
                                            ? e(
                                                date(
                                                    'd/m/Y',
                                                    $tanggal
                                                )
                                            )
                                            : '-'; ?>

                                    </td>


                                    <td>

                                        <div class="table-primary-text">

                                            <?= e(
                                                $maintenance[
                                                    'jenis_komponen'
                                                ]
                                                ?: 'Komponen'
                                            ); ?>

                                        </div>


                                        <?php if (
                                            !empty(
                                                $maintenance[
                                                    'part_number'
                                                ]
                                            )
                                        ): ?>


                                            <div
                                                class="table-secondary-text">

                                                Part:
                                                <?= e(
                                                    $maintenance[
                                                        'part_number'
                                                    ]
                                                ); ?>

                                            </div>


                                        <?php endif; ?>


                                    </td>


                                    <td>

                                        <?= e(
                                            $maintenance[
                                                'nama_mesin'
                                            ]
                                            ?: '-'
                                        ); ?>

                                    </td>


                                    <td>

                                        <?= e(
                                            $maintenance[
                                                'nama_sub_mesin'
                                            ]
                                            ?: '-'
                                        ); ?>

                                    </td>


                                    <td>

                                        <div
                                            style="
                                                max-width:220px;
                                                white-space:nowrap;
                                                overflow:hidden;
                                                text-overflow:ellipsis;
                                            "
                                            title="<?= e(
                                                $maintenance[
                                                    'tindakan'
                                                ]
                                            ); ?>">

                                            <?= e(
                                                $maintenance[
                                                    'tindakan'
                                                ]
                                                ?: '-'
                                            ); ?>

                                        </div>

                                    </td>


                                    <td>

                                        <?= e(
                                            $maintenance[
                                                'teknisi'
                                            ]
                                            ?: '-'
                                        ); ?>

                                    </td>


                                    <td>

                                        <?= badge_maintenance(
                                            $maintenance[
                                                'status'
                                            ]
                                        ); ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>


                <?php endif; ?>


            </div>


        </div>


        <!-- =================================================
             USER TERBARU
        ================================================== -->

        <div class="section-card mb-4">


            <div class="section-header">

                <div>

                    <h2 class="section-title">
                        User Terbaru
                    </h2>

                    <p class="section-description">
                        Pengguna yang terakhir ditambahkan
                    </p>

                </div>


                <a
                    href="/inventory_mesin/admin/users/index.php"
                    class="view-all">

                    Kelola

                    <i class="bi bi-arrow-right"></i>

                </a>

            </div>


            <div class="table-wrapper">


                <?php if (
                    empty($users_terbaru)
                ): ?>


                    <div class="empty-state">

                        <i class="bi bi-people"></i>

                        <p>
                            Belum ada data user.
                        </p>

                    </div>


                <?php else: ?>


                    <table class="dashboard-table">


                        <thead>

                            <tr>

                                <th>
                                    Nama Lengkap
                                </th>

                                <th>
                                    Username
                                </th>

                                <th>
                                    Role
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $users_terbaru
                                as $user
                            ): ?>


                                <?php

                                $role_user =
                                    strtolower(
                                        trim(
                                            $user['role']
                                            ?? ''
                                        )
                                    );


                                $status_user =
                                    strtolower(
                                        trim(
                                            $user['status']
                                            ?? ''
                                        )
                                    );

                                ?>


                                <tr>


                                    <td>

                                        <div class="table-primary-text">

                                            <?= e(
                                                $user[
                                                    'nama_lengkap'
                                                ]
                                                ?: '-'
                                            ); ?>

                                        </div>

                                    </td>


                                    <td>

                                        <?= e(
                                            $user[
                                                'username'
                                            ]
                                            ?: '-'
                                        ); ?>

                                    </td>


                                    <td>


                                        <?php if (
                                            $role_user === 'admin'
                                        ): ?>


                                            <span
                                                class="status-badge status-info">

                                                <i
                                                    class="bi bi-shield-check">
                                                </i>

                                                Admin

                                            </span>


                                        <?php else: ?>


                                            <span
                                                class="status-badge status-success">

                                                <i
                                                    class="bi bi-person">
                                                </i>

                                                User

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                    <td>


                                        <?php if (
                                            $status_user === 'aktif'
                                        ): ?>


                                            <span
                                                class="status-badge status-success">

                                                <i
                                                    class="bi bi-check-circle">
                                                </i>

                                                Aktif

                                            </span>


                                        <?php else: ?>


                                            <span
                                                class="status-badge status-danger">

                                                <i
                                                    class="bi bi-x-circle">
                                                </i>

                                                <?= e(
                                                    $user['status']
                                                    ?: 'Tidak aktif'
                                                ); ?>

                                            </span>


                                        <?php endif; ?>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>


                <?php endif; ?>


            </div>


        </div>


    </main>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <footer class="footer">

        Inventory Mesin &copy;
        <?= date('Y'); ?>

        &mdash;

        PT Garudafood Putra Putri Jaya Tbk

    </footer>


</div>


<!-- =========================================================
     BOOTSTRAP JS
========================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<!-- =========================================================
     CHART
========================================================== -->

<script>

const chartLabels =
    <?= json_encode(
        $chart_labels,
        JSON_UNESCAPED_UNICODE |
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    ); ?>;


const chartData =
    <?= json_encode(
        $chart_data,
        JSON_NUMERIC_CHECK
    ); ?>;


const chartElement =
    document.getElementById(
        'maintenanceChart'
    );


if (chartElement) {

    new Chart(
        chartElement,
        {

            type: 'line',

            data: {

                labels: chartLabels,

                datasets: [

                    {

                        label: 'Maintenance',

                        data: chartData,

                        tension: 0.35,

                        fill: true,

                        borderWidth: 2,

                        pointRadius: 4,

                        pointHoverRadius: 6

                    }

                ]

            },


            options: {

                responsive: true,

                maintainAspectRatio: false,


                interaction: {

                    intersect: false,

                    mode: 'index'

                },


                plugins: {

                    legend: {

                        display: false

                    },


                    tooltip: {

                        callbacks: {

                            label: function(context) {

                                return (
                                    ' Maintenance: '
                                    + context.parsed.y
                                );

                            }

                        }

                    }

                },


                scales: {

                    y: {

                        beginAtZero: true,

                        ticks: {

                            precision: 0

                        },

                        grid: {

                            color: '#eef1f5'

                        }

                    },


                    x: {

                        grid: {

                            display: false

                        }

                    }

                }

            }

        }
    );

}

</script>


</body>

</html>