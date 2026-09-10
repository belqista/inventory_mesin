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
   SIDEBAR ACTIVE MENU
========================================================= */

$active_menu = 'komponen';

/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function kondisiBadge($kondisi)
{
    $kondisi = trim((string)$kondisi);

    if ($kondisi === 'Baik') {
        return '<span class="status-badge status-baik">
                    <i class="bi bi-check-circle-fill"></i>
                    Baik
                </span>';
    }

    if ($kondisi === 'Perlu Pemeriksaan') {
        return '<span class="status-badge status-periksa">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    Perlu Pemeriksaan
                </span>';
    }

    if ($kondisi === 'Dalam Perbaikan') {
        return '<span class="status-badge status-perbaikan">
                    <i class="bi bi-tools"></i>
                    Dalam Perbaikan
                </span>';
    }

    return '<span class="status-badge status-default">
                <i class="bi bi-question-circle-fill"></i>
                ' . e($kondisi ?: 'Belum Ditentukan') . '
            </span>';
}

/* =========================================================
   PESAN
========================================================= */

$success = $_GET['success'] ?? '';
$error   = $_GET['error'] ?? '';

/* =========================================================
   FILTER
========================================================= */

$search       = trim($_GET['search'] ?? '');
$id_area      = intval($_GET['id_area'] ?? 0);
$id_jenis     = intval($_GET['id_jenis'] ?? 0);
$id_mesin     = intval($_GET['id_mesin'] ?? 0);
$id_sub_mesin = intval($_GET['id_sub_mesin'] ?? 0);
$kondisi      = trim($_GET['kondisi'] ?? '');

/* =========================================================
   DATA DROPDOWN AREA
========================================================= */

$areas = [];

$qArea = mysqli_query(
    $conn,
    "SELECT
        id,
        nama_area
     FROM area_bagian
     ORDER BY nama_area ASC"
);

if ($qArea) {

    while ($row = mysqli_fetch_assoc($qArea)) {
        $areas[] = $row;
    }

}

/* =========================================================
   DATA DROPDOWN JENIS MESIN
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
   DATA DROPDOWN MESIN
========================================================= */

$mesinList = [];

$qMesin = mysqli_query(
    $conn,
    "SELECT
        m.id,
        m.id_area,
        m.id_jenis_mesin,
        m.nama_mesin,
        m.serial_number,
        ab.nama_area,
        jm.nama_jenis_mesin
     FROM mesin m
     LEFT JOIN area_bagian ab
        ON ab.id = m.id_area
     LEFT JOIN jenis_mesin jm
        ON jm.id = m.id_jenis_mesin
     ORDER BY m.nama_mesin ASC"
);

if ($qMesin) {

    while ($row = mysqli_fetch_assoc($qMesin)) {
        $mesinList[] = $row;
    }

}

/* =========================================================
   DATA DROPDOWN SUB MESIN
========================================================= */

$subMesinList = [];

$qSubMesin = mysqli_query(
    $conn,
    "SELECT
        sm.id,
        sm.id_mesin,
        sm.nama_sub_mesin,
        sm.serial_number,
        m.nama_mesin
     FROM sub_mesin sm
     LEFT JOIN mesin m
        ON m.id = sm.id_mesin
     ORDER BY sm.nama_sub_mesin ASC"
);

if ($qSubMesin) {

    while ($row = mysqli_fetch_assoc($qSubMesin)) {
        $subMesinList[] = $row;
    }

}

/* =========================================================
   BUILD FILTER QUERY
========================================================= */

$where  = [];
$params = [];
$types  = '';

if ($search !== '') {

    $where[] = "(
        k.jenis_komponen LIKE ?
        OR k.serial_number LIKE ?
        OR k.nama_bagian LIKE ?
        OR k.spesifikasi LIKE ?
        OR k.kategori LIKE ?
        OR k.brand LIKE ?
        OR k.tipe LIKE ?
        OR k.part_number LIKE ?
        OR k.lokasi LIKE ?
        OR k.mesin LIKE ?
        OR k.sub_mesin LIKE ?
        OR m.nama_mesin LIKE ?
        OR sm.nama_sub_mesin LIKE ?
        OR ab.nama_area LIKE ?
        OR jm.nama_jenis_mesin LIKE ?
    )";

    $searchValue = '%' . $search . '%';

    for ($i = 0; $i < 15; $i++) {

        $params[] = $searchValue;
        $types .= 's';

    }

}

if ($id_area > 0) {

    $where[] = "k.id_area = ?";

    $params[] = $id_area;
    $types .= 'i';

}

if ($id_jenis > 0) {

    $where[] = "k.id_jenis_mesin = ?";

    $params[] = $id_jenis;
    $types .= 'i';

}

if ($id_mesin > 0) {

    $where[] = "k.id_mesin = ?";

    $params[] = $id_mesin;
    $types .= 'i';

}

if ($id_sub_mesin > 0) {

    $where[] = "k.id_sub_mesin = ?";

    $params[] = $id_sub_mesin;
    $types .= 'i';

}

if ($kondisi !== '') {

    $where[] = "k.kondisi = ?";

    $params[] = $kondisi;
    $types .= 's';

}

$whereSQL = '';

if (!empty($where)) {

    $whereSQL = 'WHERE ' . implode(' AND ', $where);

}

/* =========================================================
   QUERY KOMPONEN
========================================================= */

$sql = "
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
        k.daya,
        k.io_address,
        k.ip_address,
        k.input_voltage,
        k.frekuensi_input,
        k.arus_input,
        k.output,
        k.frekuensi_output,
        k.ip_rating,
        k.lokasi,
        k.kondisi,
        k.keterangan,
        k.gambar,

        ab.nama_area,
        jm.nama_jenis_mesin,
        m.nama_mesin,
        sm.nama_sub_mesin,

        (
            SELECT COUNT(*)
            FROM riwayat_maintenance rm
            WHERE rm.id_komponen = k.id
        ) AS jumlah_maintenance

    FROM komponen k

    LEFT JOIN area_bagian ab
        ON ab.id = k.id_area

    LEFT JOIN jenis_mesin jm
        ON jm.id = k.id_jenis_mesin

    LEFT JOIN mesin m
        ON m.id = k.id_mesin

    LEFT JOIN sub_mesin sm
        ON sm.id = k.id_sub_mesin

    $whereSQL

    ORDER BY k.id DESC
";

/* =========================================================
   EXECUTE QUERY
========================================================= */

$komponenList = [];
$queryError   = '';

$stmt = mysqli_prepare($conn, $sql);

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
            $komponenList[] = $row;
        }

    }

    mysqli_stmt_close($stmt);

} else {

    $queryError = mysqli_error($conn);

}

/* =========================================================
   FUNGSI COUNT
========================================================= */

function ambilCount($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);

    return (int)($row['total'] ?? 0);
}

/* =========================================================
   STATISTIK
========================================================= */

$totalKomponen = ambilCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM komponen"
);

$totalBaik = ambilCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM komponen
     WHERE kondisi = 'Baik'"
);

$totalPeriksa = ambilCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM komponen
     WHERE kondisi = 'Perlu Pemeriksaan'"
);

$totalPerbaikan = ambilCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM komponen
     WHERE kondisi = 'Dalam Perbaikan'"
);

$totalMesin = ambilCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM mesin"
);

$totalSubMesin = ambilCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM sub_mesin"
);

/* =========================================================
   FILTER AKTIF
========================================================= */

$jumlahFilter = 0;

if ($search !== '') {
    $jumlahFilter++;
}

if ($id_area > 0) {
    $jumlahFilter++;
}

if ($id_jenis > 0) {
    $jumlahFilter++;
}

if ($id_mesin > 0) {
    $jumlahFilter++;
}

if ($id_sub_mesin > 0) {
    $jumlahFilter++;
}

if ($kondisi !== '') {
    $jumlahFilter++;
}

/* =========================================================
   NAMA FILTER AKTIF
========================================================= */

$namaAreaFilter = '';

if ($id_area > 0) {

    foreach ($areas as $area) {

        if ((int)$area['id'] === $id_area) {

            $namaAreaFilter = $area['nama_area'];

            break;

        }

    }

}

$namaJenisFilter = '';

if ($id_jenis > 0) {

    foreach ($jenisMesin as $jenis) {

        if ((int)$jenis['id'] === $id_jenis) {

            $namaJenisFilter = $jenis['nama_jenis_mesin'];

            break;

        }

    }

}

$namaMesinFilter = '';

if ($id_mesin > 0) {

    foreach ($mesinList as $mesin) {

        if ((int)$mesin['id'] === $id_mesin) {

            $namaMesinFilter = $mesin['nama_mesin'];

            break;

        }

    }

}

$namaSubMesinFilter = '';

if ($id_sub_mesin > 0) {

    foreach ($subMesinList as $sub) {

        if ((int)$sub['id'] === $id_sub_mesin) {

            $namaSubMesinFilter = $sub['nama_sub_mesin'];

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

    <title>Data Komponen • Admin Inventory Mesin</title>

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
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
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
            transition: .25s ease;
        }

        .topbar {
            height: 76px;

            background: var(--white);

            border-bottom: 1px solid var(--border);

            padding: 0 28px;

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .page-title {
            margin: 0;

            font-size: 20px;
            font-weight: 700;

            color: var(--text);
        }

        .page-subtitle {
            margin: 3px 0 0;

            font-size: 11px;

            color: var(--muted);
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-avatar {
            width: 36px;
            height: 36px;

            border-radius: 50%;

            background: var(--primary-light);
            color: var(--primary);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 16px;
        }

        .admin-info strong {
            display: block;

            font-size: 12px;
            line-height: 1.3;
        }

        .admin-info span {
            font-size: 10px;
            color: var(--muted);
        }

        .content {
            padding: 25px 28px 35px;
        }

        /* =====================================================
           MOBILE BUTTON
        ====================================================== */

        .sidebar-toggle {
            display: none;

            width: 38px;
            height: 38px;

            border: 1px solid var(--border);

            background: #fff;

            border-radius: 8px;

            color: var(--text);

            align-items: center;
            justify-content: center;
        }

        /* =====================================================
           STAT CARDS
        ====================================================== */

        .stat-card {
            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 13px;

            padding: 17px;

            height: 100%;

            box-shadow: 0 3px 12px rgba(18, 63, 122, .035);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-icon {
            width: 40px;
            height: 40px;

            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 18px;
        }

        .icon-blue {
            background: var(--primary-light);
            color: var(--primary);
        }

        .icon-green {
            background: #eaf8f0;
            color: #198754;
        }

        .icon-yellow {
            background: #fff8e6;
            color: #d99100;
        }

        .icon-red {
            background: #fff0f0;
            color: #dc3545;
        }

        .stat-label {
            margin-top: 13px;

            font-size: 11px;
            color: var(--muted);
        }

        .stat-value {
            margin-top: 2px;

            font-size: 23px;
            font-weight: 700;
        }

        /* =====================================================
           CARD
        ====================================================== */

        .card-box {
            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 13px;

            box-shadow: 0 3px 12px rgba(18, 63, 122, .035);
        }

        .card-header-custom {
            padding: 17px 19px;

            border-bottom: 1px solid var(--border);

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;
        }

        .section-title {
            margin: 0;

            font-size: 14px;
            font-weight: 700;
        }

        .section-desc {
            margin: 3px 0 0;

            color: var(--muted);

            font-size: 10px;
        }

        /* =====================================================
           BUTTON
        ====================================================== */

        .btn-primary-custom {
            border: 0;

            background: var(--primary);
            color: #fff;

            border-radius: 8px;

            padding: 9px 13px;

            font-size: 11px;
            font-weight: 600;
        }

        .btn-primary-custom:hover {
            background: var(--primary-dark);
            color: #fff;
        }

        .btn-outline-custom {
            border: 1px solid var(--border);

            background: #fff;
            color: #596273;

            border-radius: 8px;

            padding: 8px 12px;

            font-size: 11px;
            font-weight: 500;
        }

        .btn-outline-custom:hover {
            border-color: var(--primary);

            color: var(--primary);
        }

        /* =====================================================
           FILTER
        ====================================================== */

        .filter-box {
            padding: 18px 19px;
        }

        .form-label-custom {
            margin-bottom: 6px;

            font-size: 10px;
            font-weight: 600;

            color: #626b7a;
        }

        .form-control,
        .form-select {
            border: 1px solid var(--border);

            border-radius: 8px;

            min-height: 38px;

            font-size: 11px;

            box-shadow: none !important;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
        }

        .filter-actions {
            display: flex;
            align-items: end;
            gap: 7px;
        }

        .filter-info {
            margin-top: 13px;

            display: flex;
            flex-wrap: wrap;

            gap: 6px;
        }

        .filter-tag {
            display: inline-flex;
            align-items: center;

            gap: 5px;

            padding: 5px 9px;

            border-radius: 20px;

            background: var(--primary-light);

            color: var(--primary);

            font-size: 10px;
            font-weight: 500;
        }

        /* =====================================================
           ALERT
        ====================================================== */

        .alert-custom {
            border: 0;

            border-radius: 10px;

            font-size: 11px;

            padding: 11px 14px;
        }

        /* =====================================================
           COMPONENT LIST
        ====================================================== */

        .component-list {
            padding: 0;
        }

        .component-item {
            padding: 17px 19px;

            border-bottom: 1px solid var(--border);

            transition: .2s ease;
        }

        .component-item:last-child {
            border-bottom: 0;
        }

        .component-item:hover {
            background: #fbfcfe;
        }

        .component-main {
            display: flex;
            align-items: flex-start;

            gap: 15px;
        }

        .component-image {
            width: 82px;
            height: 82px;

            border-radius: 10px;

            border: 1px solid var(--border);

            background: #f8fafc;

            flex-shrink: 0;

            overflow: hidden;

            display: flex;
            align-items: center;
            justify-content: center;
        }

        .component-image img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }

        .component-image-placeholder {
            color: #b2bac6;

            font-size: 26px;
        }

        .component-content {
            min-width: 0;
            flex: 1;
        }

        .component-title-row {
            display: flex;
            align-items: center;

            gap: 8px;

            flex-wrap: wrap;
        }

        .component-title {
            font-size: 14px;
            font-weight: 700;

            color: var(--text);

            margin: 0;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;

            gap: 5px;

            padding: 5px 8px;

            border-radius: 20px;

            font-size: 9px;
            font-weight: 600;
        }

        .status-baik {
            color: #198754;
            background: #eaf8f0;
        }

        .status-periksa {
            color: #b87900;
            background: #fff6df;
        }

        .status-perbaikan {
            color: #dc3545;
            background: #fff0f0;
        }

        .status-default {
            color: #687385;
            background: #f0f2f5;
        }

        .component-serial {
            margin-top: 4px;

            color: var(--muted);

            font-size: 10px;
        }

        .component-serial strong {
            color: #626b7a;
        }

        .component-meta {
            display: flex;
            flex-wrap: wrap;

            gap: 8px 17px;

            margin-top: 11px;
        }

        .meta-item {
            display: flex;
            align-items: center;

            gap: 6px;

            min-width: 120px;

            font-size: 10px;

            color: var(--muted);
        }

        .meta-item i {
            color: var(--primary);

            font-size: 13px;
        }

        .meta-item strong {
            color: #505a6b;

            font-weight: 600;
        }

        .component-actions {
            flex-shrink: 0;

            display: flex;
            align-items: center;

            gap: 6px;
        }

        .action-btn {
            width: 34px;
            height: 34px;

            border: 1px solid var(--border);

            background: #fff;

            color: #667083;

            border-radius: 8px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 14px;

            transition: .2s ease;
        }

        .action-btn:hover {
            border-color: var(--primary);

            color: var(--primary);

            background: var(--primary-light);
        }

        .action-btn.danger:hover {
            border-color: #dc3545;

            color: #dc3545;

            background: #fff0f0;
        }

        .maintenance-count {
            margin-top: 9px;

            display: inline-flex;
            align-items: center;

            gap: 5px;

            padding: 4px 8px;

            border-radius: 6px;

            background: #f4f6f9;

            color: #727b8b;

            font-size: 9px;
            font-weight: 500;
        }

        /* =====================================================
           EMPTY STATE
        ====================================================== */

        .empty-state {
            padding: 65px 25px;

            text-align: center;
        }

        .empty-icon {
            width: 62px;
            height: 62px;

            margin: 0 auto 13px;

            border-radius: 50%;

            background: var(--primary-light);

            color: var(--primary);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 26px;
        }

        .empty-state h5 {
            margin: 0;

            font-size: 14px;
            font-weight: 700;
        }

        .empty-state p {
            margin: 5px 0 0;

            color: var(--muted);

            font-size: 10px;
        }

        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1100px) {

            .main {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: flex;
            }

            .topbar {
                padding: 0 20px;
            }

            .content {
                padding: 20px;
            }

        }

        @media (max-width: 768px) {

            .page-title {
                font-size: 17px;
            }

            .admin-info {
                display: none;
            }

            .component-main {
                flex-wrap: wrap;
            }

            .component-actions {
                width: 100%;

                justify-content: flex-end;

                margin-top: 5px;
            }

            .component-image {
                width: 70px;
                height: 70px;
            }

            .meta-item {
                min-width: 45%;
            }

        }

        @media (max-width: 576px) {

            .topbar {
                height: 68px;
            }

            .content {
                padding: 15px;
            }

            .card-header-custom {
                align-items: flex-start;

                flex-direction: column;
            }

            .meta-item {
                min-width: 100%;
            }

            .component-main {
                gap: 10px;
            }

            .component-image {
                width: 62px;
                height: 62px;
            }

            .component-title {
                font-size: 12px;
            }

            .component-meta {
                gap: 7px;
            }

        }

    </style>

</head>

<body>

<!-- =========================================================
     SIDEBAR
     100% MENGGUNAKAN admin/sidebar.php
========================================================= -->

<?php include "../sidebar.php"; ?>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">

    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <header class="topbar">

        <div class="d-flex align-items-center gap-3">

            <button
                type="button"
                class="sidebar-toggle"
                id="mobileToggle">

                <i class="bi bi-list"></i>

            </button>

            <div>

                <h1 class="page-title">
                    Data Komponen
                </h1>

                <p class="page-subtitle">
                    Kelola data komponen mesin dan kondisi peralatan
                </p>

            </div>

        </div>


        <div class="admin-profile">

            <div class="admin-avatar">

                <i class="bi bi-person-fill"></i>

            </div>


            <div class="admin-info">

                <strong>
                    <?= e($_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Administrator') ?>
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

    <div class="content">

        <!-- =================================================
             ALERT
        ================================================== -->

        <?php if ($success !== ''): ?>

            <div class="alert alert-success alert-custom mb-3">

                <i class="bi bi-check-circle-fill me-2"></i>

                <?= e($success) ?>

            </div>

        <?php endif; ?>


        <?php if ($error !== ''): ?>

            <div class="alert alert-danger alert-custom mb-3">

                <i class="bi bi-exclamation-circle-fill me-2"></i>

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <?php if ($queryError !== ''): ?>

            <div class="alert alert-danger alert-custom mb-3">

                <i class="bi bi-database-x me-2"></i>

                Terjadi kesalahan saat mengambil data komponen.

            </div>

        <?php endif; ?>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="row g-3 mb-4">

            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon icon-blue">

                            <i class="bi bi-gear-wide-connected"></i>

                        </div>

                    </div>

                    <div class="stat-label">
                        Total Komponen
                    </div>

                    <div class="stat-value">
                        <?= number_format($totalKomponen) ?>
                    </div>

                </div>

            </div>


            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon icon-green">

                            <i class="bi bi-check-circle"></i>

                        </div>

                    </div>

                    <div class="stat-label">
                        Kondisi Baik
                    </div>

                    <div class="stat-value">
                        <?= number_format($totalBaik) ?>
                    </div>

                </div>

            </div>


            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon icon-yellow">

                            <i class="bi bi-exclamation-circle"></i>

                        </div>

                    </div>

                    <div class="stat-label">
                        Perlu Pemeriksaan
                    </div>

                    <div class="stat-value">
                        <?= number_format($totalPeriksa) ?>
                    </div>

                </div>

            </div>


            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-top">

                        <div class="stat-icon icon-red">

                            <i class="bi bi-tools"></i>

                        </div>

                    </div>

                    <div class="stat-label">
                        Dalam Perbaikan
                    </div>

                    <div class="stat-value">
                        <?= number_format($totalPerbaikan) ?>
                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             FILTER
        ================================================== -->

        <div class="card-box mb-4">

            <div class="card-header-custom">

                <div>

                    <h2 class="section-title">
                        Filter Komponen
                    </h2>

                    <p class="section-desc">
                        Cari dan saring komponen berdasarkan lokasi serta kondisi
                    </p>

                </div>


                <?php if ($jumlahFilter > 0): ?>

                    <span class="filter-tag">

                        <i class="bi bi-funnel-fill"></i>

                        <?= $jumlahFilter ?> filter aktif

                    </span>

                <?php endif; ?>

            </div>


            <div class="filter-box">

                <form
                    method="GET"
                    action="index.php">

                    <div class="row g-3">

                        <!-- SEARCH -->

                        <div class="col-12 col-lg-4">

                            <label class="form-label-custom">
                                Cari Komponen
                            </label>

                            <div class="input-group">

                                <span class="input-group-text bg-white border-end-0">

                                    <i class="bi bi-search text-muted"></i>

                                </span>


                                <input
                                    type="text"
                                    name="search"
                                    class="form-control border-start-0"
                                    value="<?= e($search) ?>"
                                    placeholder="Nama, serial, part number, brand...">

                            </div>

                        </div>


                        <!-- AREA -->

                        <div class="col-6 col-lg-2">

                            <label class="form-label-custom">
                                Area
                            </label>

                            <select
                                name="id_area"
                                class="form-select"
                                id="filterArea">

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


                        <!-- JENIS MESIN -->

                        <div class="col-6 col-lg-2">

                            <label class="form-label-custom">
                                Jenis Mesin
                            </label>

                            <select
                                name="id_jenis"
                                class="form-select"
                                id="filterJenis">

                                <option value="">
                                    Semua Jenis
                                </option>

                                <?php foreach ($jenisMesin as $jenis): ?>

                                    <option
                                        value="<?= (int)$jenis['id'] ?>"
                                        data-area="<?= (int)$jenis['id_area'] ?>"
                                        <?= $id_jenis === (int)$jenis['id'] ? 'selected' : '' ?>>

                                        <?= e($jenis['nama_jenis_mesin']) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- MESIN -->

                        <div class="col-6 col-lg-2">

                            <label class="form-label-custom">
                                Mesin
                            </label>

                            <select
                                name="id_mesin"
                                class="form-select"
                                id="filterMesin">

                                <option value="">
                                    Semua Mesin
                                </option>

                                <?php foreach ($mesinList as $mesin): ?>

                                    <option
                                        value="<?= (int)$mesin['id'] ?>"
                                        data-area="<?= (int)$mesin['id_area'] ?>"
                                        data-jenis="<?= (int)$mesin['id_jenis_mesin'] ?>"
                                        <?= $id_mesin === (int)$mesin['id'] ? 'selected' : '' ?>>

                                        <?= e($mesin['nama_mesin']) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- KONDISI -->

                        <div class="col-6 col-lg-2">

                            <label class="form-label-custom">
                                Kondisi
                            </label>

                            <select
                                name="kondisi"
                                class="form-select">

                                <option value="">
                                    Semua Kondisi
                                </option>

                                <option
                                    value="Baik"
                                    <?= $kondisi === 'Baik' ? 'selected' : '' ?>>

                                    Baik

                                </option>

                                <option
                                    value="Perlu Pemeriksaan"
                                    <?= $kondisi === 'Perlu Pemeriksaan' ? 'selected' : '' ?>>

                                    Perlu Pemeriksaan

                                </option>

                                <option
                                    value="Dalam Perbaikan"
                                    <?= $kondisi === 'Dalam Perbaikan' ? 'selected' : '' ?>>

                                    Dalam Perbaikan

                                </option>

                            </select>

                        </div>


                        <!-- SUB MESIN -->

                        <div class="col-12 col-lg-4">

                            <label class="form-label-custom">
                                Sub Mesin
                            </label>

                            <select
                                name="id_sub_mesin"
                                class="form-select"
                                id="filterSubMesin">

                                <option value="">
                                    Semua Sub Mesin
                                </option>

                                <?php foreach ($subMesinList as $sub): ?>

                                    <option
                                        value="<?= (int)$sub['id'] ?>"
                                        data-mesin="<?= (int)$sub['id_mesin'] ?>"
                                        <?= $id_sub_mesin === (int)$sub['id'] ? 'selected' : '' ?>>

                                        <?= e($sub['nama_sub_mesin']) ?>

                                        <?php if (!empty($sub['nama_mesin'])): ?>

                                            — <?= e($sub['nama_mesin']) ?>

                                        <?php endif; ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- BUTTON -->

                        <div class="col-12 col-lg-8">

                            <div class="filter-actions h-100">

                                <button
                                    type="submit"
                                    class="btn btn-primary-custom">

                                    <i class="bi bi-funnel-fill me-1"></i>

                                    Terapkan Filter

                                </button>


                                <a
                                    href="index.php"
                                    class="btn btn-outline-custom">

                                    <i class="bi bi-arrow-counterclockwise me-1"></i>

                                    Reset

                                </a>

                            </div>

                        </div>

                    </div>


                    <?php if ($jumlahFilter > 0): ?>

                        <div class="filter-info">

                            <?php if ($search !== ''): ?>

                                <span class="filter-tag">

                                    <i class="bi bi-search"></i>

                                    <?= e($search) ?>

                                </span>

                            <?php endif; ?>


                            <?php if ($namaAreaFilter !== ''): ?>

                                <span class="filter-tag">

                                    <i class="bi bi-buildings"></i>

                                    <?= e($namaAreaFilter) ?>

                                </span>

                            <?php endif; ?>


                            <?php if ($namaJenisFilter !== ''): ?>

                                <span class="filter-tag">

                                    <i class="bi bi-diagram-3"></i>

                                    <?= e($namaJenisFilter) ?>

                                </span>

                            <?php endif; ?>


                            <?php if ($namaMesinFilter !== ''): ?>

                                <span class="filter-tag">

                                    <i class="bi bi-cpu"></i>

                                    <?= e($namaMesinFilter) ?>

                                </span>

                            <?php endif; ?>


                            <?php if ($namaSubMesinFilter !== ''): ?>

                                <span class="filter-tag">

                                    <i class="bi bi-boxes"></i>

                                    <?= e($namaSubMesinFilter) ?>

                                </span>

                            <?php endif; ?>


                            <?php if ($kondisi !== ''): ?>

                                <span class="filter-tag">

                                    <i class="bi bi-heart-pulse"></i>

                                    <?= e($kondisi) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                    <?php endif; ?>

                </form>

            </div>

        </div>


        <!-- =================================================
             LIST KOMPONEN
        ================================================== -->

        <div class="card-box">

            <div class="card-header-custom">

                <div>

                    <h2 class="section-title">
                        Daftar Komponen
                    </h2>

                    <p class="section-desc">

                        <?= number_format(count($komponenList)) ?>

                        komponen ditampilkan

                    </p>

                </div>


                <a
                    href="tambah.php"
                    class="btn btn-primary-custom">

                    <i class="bi bi-plus-lg me-1"></i>

                    Tambah Komponen

                </a>

            </div>


            <div class="component-list">

                <?php if (empty($komponenList)): ?>

                    <div class="empty-state">

                        <div class="empty-icon">

                            <i class="bi bi-gear-wide-connected"></i>

                        </div>


                        <h5>
                            Komponen belum ditemukan
                        </h5>


                        <p>
                            Belum ada data komponen yang sesuai dengan filter.
                        </p>


                        <?php if ($jumlahFilter > 0): ?>

                            <a
                                href="index.php"
                                class="btn btn-outline-custom mt-3">

                                <i class="bi bi-arrow-counterclockwise me-1"></i>

                                Reset Filter

                            </a>

                        <?php else: ?>

                            <a
                                href="tambah.php"
                                class="btn btn-primary-custom mt-3">

                                <i class="bi bi-plus-lg me-1"></i>

                                Tambah Komponen

                            </a>

                        <?php endif; ?>

                    </div>

                <?php else: ?>


                    <?php foreach ($komponenList as $komponen): ?>

                        <?php

                        $namaKomponen = trim(
                            $komponen['jenis_komponen'] ?? ''
                        );

                        if ($namaKomponen === '') {

                            $namaKomponen = trim(
                                $komponen['nama_bagian'] ?? ''
                            );

                        }

                        if ($namaKomponen === '') {
                            $namaKomponen = 'Komponen';
                        }


                        $namaMesin = trim(
                            $komponen['nama_mesin'] ?? ''
                        );

                        if ($namaMesin === '') {

                            $namaMesin = trim(
                                $komponen['mesin'] ?? ''
                            );

                        }


                        $namaSubMesin = trim(
                            $komponen['nama_sub_mesin'] ?? ''
                        );

                        if ($namaSubMesin === '') {

                            $namaSubMesin = trim(
                                $komponen['sub_mesin'] ?? ''
                            );

                        }


                        $gambar = trim(
                            $komponen['gambar'] ?? ''
                        );

                        $gambarPath = '';

                        if ($gambar !== '') {

                            $gambarPath =
                                '../../uploads/komponen/' .
                                $gambar;

                        }

                        ?>


                        <div class="component-item">

                            <div class="component-main">

                                <!-- FOTO -->

                                <div class="component-image">

                                    <?php if ($gambar !== ''): ?>

                                        <img
                                            src="<?= e($gambarPath) ?>"
                                            alt="<?= e($namaKomponen) ?>"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">


                                        <div
                                            class="component-image-placeholder"
                                            style="display:none;">

                                            <i class="bi bi-gear-wide-connected"></i>

                                        </div>

                                    <?php else: ?>

                                        <div class="component-image-placeholder">

                                            <i class="bi bi-gear-wide-connected"></i>

                                        </div>

                                    <?php endif; ?>

                                </div>


                                <!-- CONTENT -->

                                <div class="component-content">

                                    <div class="component-title-row">

                                        <h3 class="component-title">

                                            <?= e($namaKomponen) ?>

                                        </h3>


                                        <?= kondisiBadge(
                                            $komponen['kondisi'] ?? ''
                                        ) ?>

                                    </div>


                                    <?php if (!empty($komponen['serial_number'])): ?>

                                        <div class="component-serial">

                                            Serial Number:

                                            <strong>
                                                <?= e($komponen['serial_number']) ?>
                                            </strong>

                                        </div>

                                    <?php endif; ?>


                                    <div class="component-meta">

                                        <!-- AREA -->

                                        <div class="meta-item">

                                            <i class="bi bi-buildings"></i>

                                            <span>

                                                Area:

                                                <strong>
                                                    <?= e(
                                                        $komponen['nama_area']
                                                        ?: '-'
                                                    ) ?>
                                                </strong>

                                            </span>

                                        </div>


                                        <!-- JENIS MESIN -->

                                        <div class="meta-item">

                                            <i class="bi bi-diagram-3"></i>

                                            <span>

                                                Jenis:

                                                <strong>
                                                    <?= e(
                                                        $komponen['nama_jenis_mesin']
                                                        ?: '-'
                                                    ) ?>
                                                </strong>

                                            </span>

                                        </div>


                                        <!-- MESIN -->

                                        <div class="meta-item">

                                            <i class="bi bi-cpu"></i>

                                            <span>

                                                Mesin:

                                                <strong>
                                                    <?= e(
                                                        $namaMesin ?: '-'
                                                    ) ?>
                                                </strong>

                                            </span>

                                        </div>


                                        <!-- SUB MESIN -->

                                        <div class="meta-item">

                                            <i class="bi bi-boxes"></i>

                                            <span>

                                                Sub Mesin:

                                                <strong>
                                                    <?= e(
                                                        $namaSubMesin ?: '-'
                                                    ) ?>
                                                </strong>

                                            </span>

                                        </div>


                                        <!-- PART NUMBER -->

                                        <?php if (!empty($komponen['part_number'])): ?>

                                            <div class="meta-item">

                                                <i class="bi bi-upc-scan"></i>

                                                <span>

                                                    Part Number:

                                                    <strong>
                                                        <?= e(
                                                            $komponen['part_number']
                                                        ) ?>
                                                    </strong>

                                                </span>

                                            </div>

                                        <?php endif; ?>


                                        <!-- BRAND -->

                                        <?php if (!empty($komponen['brand'])): ?>

                                            <div class="meta-item">

                                                <i class="bi bi-tag"></i>

                                                <span>

                                                    Brand:

                                                    <strong>
                                                        <?= e(
                                                            $komponen['brand']
                                                        ) ?>
                                                    </strong>

                                                </span>

                                            </div>

                                        <?php endif; ?>


                                        <!-- LOKASI -->

                                        <?php if (!empty($komponen['lokasi'])): ?>

                                            <div class="meta-item">

                                                <i class="bi bi-geo-alt"></i>

                                                <span>

                                                    Lokasi:

                                                    <strong>
                                                        <?= e(
                                                            $komponen['lokasi']
                                                        ) ?>
                                                    </strong>

                                                </span>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <!-- MAINTENANCE -->

                                    <div class="maintenance-count">

                                        <i class="bi bi-wrench-adjustable-circle"></i>

                                        <?= number_format(
                                            (int)$komponen['jumlah_maintenance']
                                        ) ?>

                                        riwayat maintenance

                                    </div>

                                </div>


                                <!-- ACTION -->

                                <div class="component-actions">

                                    <a
                                        href="detail.php?id=<?= (int)$komponen['id'] ?>"
                                        class="action-btn"
                                        title="Detail Komponen">

                                        <i class="bi bi-eye"></i>

                                    </a>


                                    <a
                                        href="edit.php?id=<?= (int)$komponen['id'] ?>"
                                        class="action-btn"
                                        title="Edit Komponen">

                                        <i class="bi bi-pencil"></i>

                                    </a>


                                    <a
                                        href="hapus.php?id=<?= (int)$komponen['id'] ?>"
                                        class="action-btn danger"
                                        title="Hapus Komponen"
                                        onclick="return confirm('Yakin ingin menghapus komponen ini?');">

                                        <i class="bi bi-trash3"></i>

                                    </a>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>

        </div>

    </div>

</main>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script>

document.addEventListener('DOMContentLoaded', function () {

    /* =====================================================
       FILTER DEPENDENT DROPDOWN
       AREA -> JENIS MESIN -> MESIN -> SUB MESIN
    ====================================================== */

    const areaSelect = document.getElementById('filterArea');
    const jenisSelect = document.getElementById('filterJenis');
    const mesinSelect = document.getElementById('filterMesin');
    const subMesinSelect = document.getElementById('filterSubMesin');


    function filterJenisMesin() {

        if (!areaSelect || !jenisSelect) {
            return;
        }

        const areaId = areaSelect.value;

        Array.from(jenisSelect.options).forEach(function (option) {

            if (option.value === '') {

                option.hidden = false;

                return;

            }

            const optionArea =
                option.getAttribute('data-area');

            option.hidden =
                areaId !== '' &&
                optionArea !== areaId;

        });


        if (
            jenisSelect.value !== '' &&
            jenisSelect.options[jenisSelect.selectedIndex].hidden
        ) {

            jenisSelect.value = '';

        }

    }


    function filterMesin() {

        if (!mesinSelect) {
            return;
        }

        const areaId =
            areaSelect ? areaSelect.value : '';

        const jenisId =
            jenisSelect ? jenisSelect.value : '';


        Array.from(mesinSelect.options).forEach(function (option) {

            if (option.value === '') {

                option.hidden = false;

                return;

            }


            const optionArea =
                option.getAttribute('data-area');

            const optionJenis =
                option.getAttribute('data-jenis');


            let tampil = true;


            if (
                areaId !== '' &&
                optionArea !== areaId
            ) {

                tampil = false;

            }


            if (
                jenisId !== '' &&
                optionJenis !== jenisId
            ) {

                tampil = false;

            }


            option.hidden = !tampil;

        });


        if (
            mesinSelect.value !== '' &&
            mesinSelect.options[mesinSelect.selectedIndex].hidden
        ) {

            mesinSelect.value = '';

        }

    }


    function filterSubMesin() {

        if (!subMesinSelect) {
            return;
        }

        const mesinId =
            mesinSelect ? mesinSelect.value : '';


        Array.from(subMesinSelect.options).forEach(function (option) {

            if (option.value === '') {

                option.hidden = false;

                return;

            }


            const optionMesin =
                option.getAttribute('data-mesin');


            option.hidden =
                mesinId !== '' &&
                optionMesin !== mesinId;

        });


        if (
            subMesinSelect.value !== '' &&
            subMesinSelect.options[subMesinSelect.selectedIndex].hidden
        ) {

            subMesinSelect.value = '';

        }

    }


    if (areaSelect) {

        areaSelect.addEventListener(
            'change',
            function () {

                filterJenisMesin();

                filterMesin();

                filterSubMesin();

            }
        );

    }


    if (jenisSelect) {

        jenisSelect.addEventListener(
            'change',
            function () {

                filterMesin();

                filterSubMesin();

            }
        );

    }


    if (mesinSelect) {

        mesinSelect.addEventListener(
            'change',
            function () {

                filterSubMesin();

            }
        );

    }


    /* =====================================================
       INITIAL FILTER
    ====================================================== */

    filterJenisMesin();

    filterMesin();

    filterSubMesin();

});

</script>

</body>
</html>