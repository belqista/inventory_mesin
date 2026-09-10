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
   SIDEBAR ADMIN
========================================================= */
$active_menu = 'maintenance';

/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function tampil($value, $fallback = '-')
{
    $value = trim((string)($value ?? ''));

    return $value !== ''
        ? e($value)
        : e($fallback);
}

function formatTanggal($tanggal)
{
    if (!$tanggal || $tanggal === '0000-00-00') {
        return '-';
    }

    $timestamp = strtotime($tanggal);

    if (!$timestamp) {
        return e($tanggal);
    }

    $bulan = [
        1  => 'Januari',
        2  => 'Februari',
        3  => 'Maret',
        4  => 'April',
        5  => 'Mei',
        6  => 'Juni',
        7  => 'Juli',
        8  => 'Agustus',
        9  => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];

    return date('d', $timestamp) . ' ' .
           $bulan[(int)date('m', $timestamp)] . ' ' .
           date('Y', $timestamp);
}

function badgeStatus($status)
{
    $statusLower = strtolower(trim((string)$status));

    if (
        strpos($statusLower, 'selesai') !== false ||
        strpos($statusLower, 'complete') !== false ||
        strpos($statusLower, 'completed') !== false
    ) {
        return '<span class="status-badge status-success">
                    <i class="bi bi-check-circle-fill"></i>
                    ' . e($status) . '
                </span>';
    }

    if (
        strpos($statusLower, 'proses') !== false ||
        strpos($statusLower, 'berlangsung') !== false ||
        strpos($statusLower, 'progress') !== false ||
        strpos($statusLower, 'pending') !== false
    ) {
        return '<span class="status-badge status-warning">
                    <i class="bi bi-arrow-repeat"></i>
                    ' . e($status) . '
                </span>';
    }

    if (
        strpos($statusLower, 'batal') !== false ||
        strpos($statusLower, 'cancel') !== false
    ) {
        return '<span class="status-badge status-danger">
                    <i class="bi bi-x-circle-fill"></i>
                    ' . e($status) . '
                </span>';
    }

    return '<span class="status-badge status-secondary">
                <i class="bi bi-info-circle-fill"></i>
                ' . tampil($status) . '
            </span>';
}

/* =========================================================
   AMBIL ID
========================================================= */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=data_tidak_ditemukan");
    exit;
}

/* =========================================================
   AMBIL DATA MAINTENANCE
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

        k.serial_number,
        k.jenis_komponen,
        k.nama_bagian AS komponen_nama_bagian,
        k.jenis_komponen AS komponen_jenis,
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
        k.lokasi AS komponen_lokasi,
        k.kondisi AS kondisi_komponen,
        k.keterangan AS keterangan_komponen,
        k.gambar,

        ab.nama_area,

        jm.nama_jenis_mesin,

        m.nama_mesin AS master_nama_mesin,

        sm.nama_sub_mesin AS master_nama_sub_mesin

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

    WHERE rm.id = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Query gagal dipersiapkan: " . e($conn->error));
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();

$data = $result->fetch_assoc();

$stmt->close();

/* =========================================================
   VALIDASI DATA
========================================================= */

if (!$data) {
    header("Location: index.php?error=data_tidak_ditemukan");
    exit;
}

/* =========================================================
   DATA TAMPILAN
========================================================= */

$namaKomponen = trim((string)($data['jenis_komponen'] ?? ''));

if ($namaKomponen === '') {
    $namaKomponen = trim((string)($data['komponen_nama_bagian'] ?? ''));
}

if ($namaKomponen === '') {
    $namaKomponen = 'Komponen';
}

/*
|--------------------------------------------------------------------------
| Nama mesin
|--------------------------------------------------------------------------
*/

$namaMesin = trim((string)($data['master_nama_mesin'] ?? ''));

if ($namaMesin === '') {
    $namaMesin = trim((string)($data['nama_mesin'] ?? ''));
}

$namaSubMesin = trim((string)($data['master_nama_sub_mesin'] ?? ''));

if ($namaSubMesin === '') {
    $namaSubMesin = trim((string)($data['nama_sub_mesin'] ?? ''));
}

/*
|--------------------------------------------------------------------------
| Lokasi
|--------------------------------------------------------------------------
*/

$lokasi = trim((string)($data['komponen_lokasi'] ?? ''));

/* =========================================================
   PATH GAMBAR
========================================================= */

$gambarUrl = '';

if (!empty($data['gambar'])) {

    $namaFile = basename($data['gambar']);

    $pathGambar = "../../uploads/komponen/" . $namaFile;

    if (file_exists($pathGambar)) {
        $gambarUrl = $pathGambar;
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

    <title>
        Detail Maintenance - <?= e($namaKomponen) ?>
    </title>

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

        /* =====================================================
           ROOT
        ====================================================== */

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
            gap: 14px;
        }

        .page-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            color: var(--text);
        }

        .page-subtitle {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
        }

        .mobile-menu {
            display: none;

            width: 38px;
            height: 38px;

            border: 1px solid var(--border);
            border-radius: 9px;

            background: var(--white);

            color: var(--text);

            align-items: center;
            justify-content: center;
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

            font-size: 16px;
        }

        .user-info {
            line-height: 1.25;
        }

        .user-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--text);
        }

        .user-role {
            font-size: 10px;
            color: var(--muted);
            text-transform: capitalize;
        }

        /* =====================================================
           CONTENT
        ====================================================== */

        .content {
            padding: 28px;
        }

        /* =====================================================
           BACK BUTTON
        ====================================================== */

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 7px;

            color: var(--muted);

            font-size: 12px;
            font-weight: 500;

            margin-bottom: 18px;

            transition: .2s;
        }

        .back-button:hover {
            color: var(--primary);
        }

        /* =====================================================
           HERO
        ====================================================== */

        .hero-card {
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

        .hero-left {
            display: flex;
            align-items: center;
            gap: 18px;

            min-width: 0;
        }

        .component-image {
            width: 92px;
            height: 92px;

            border-radius: 13px;

            border: 1px solid var(--border);

            background: #f8fafc;

            display: flex;
            align-items: center;
            justify-content: center;

            overflow: hidden;

            flex-shrink: 0;
        }

        .component-image img {
            width: 100%;
            height: 100%;

            object-fit: cover;
        }

        .component-image .no-image {
            color: #b4bbc6;
            font-size: 28px;
        }

        .hero-title {
            font-size: 20px;
            font-weight: 700;

            margin: 0 0 6px;

            color: var(--text);
        }

        .hero-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;

            gap: 7px 14px;

            color: var(--muted);

            font-size: 11px;
        }

        .hero-meta span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .hero-meta i {
            color: var(--primary);
        }

        .hero-actions {
            display: flex;
            gap: 8px;

            flex-shrink: 0;
        }

        .btn-main {
            border: 0;
            border-radius: 8px;

            padding: 9px 14px;

            font-size: 12px;
            font-weight: 600;

            display: inline-flex;
            align-items: center;
            gap: 7px;

            transition: .2s;
        }

        .btn-primary-custom {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary-custom:hover {
            background: var(--primary-dark);
            color: #fff;
        }

        .btn-danger-custom {
            background: #fff1f2;
            color: #dc3545;
        }

        .btn-danger-custom:hover {
            background: #dc3545;
            color: #fff;
        }

        .btn-light-custom {
            background: #f4f6f9;
            color: #596273;
        }

        .btn-light-custom:hover {
            background: #e9edf3;
            color: var(--text);
        }

        /* =====================================================
           STATUS BADGE
        ====================================================== */

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 10px;
            font-weight: 600;

            white-space: nowrap;
        }

        .status-success {
            background: #eaf8f0;
            color: var(--success);
        }

        .status-warning {
            background: #fff6df;
            color: #b77900;
        }

        .status-danger {
            background: #fff0f1;
            color: var(--danger);
        }

        .status-secondary {
            background: #f0f2f5;
            color: #687180;
        }

        /* =====================================================
           GRID
        ====================================================== */

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;

            gap: 20px;

            margin-bottom: 20px;
        }

        /* =====================================================
           CARD
        ====================================================== */

        .detail-card {
            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 14px;

            overflow: hidden;
        }

        .detail-card.full {
            grid-column: 1 / -1;
        }

        .card-header-custom {
            padding: 16px 20px;

            border-bottom: 1px solid var(--border);

            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header-title {
            display: flex;
            align-items: center;

            gap: 9px;

            font-size: 13px;
            font-weight: 700;

            color: var(--text);
        }

        .card-header-title i {
            width: 30px;
            height: 30px;

            border-radius: 8px;

            background: var(--primary-light);
            color: var(--primary);

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 15px;
        }

        .card-body-custom {
            padding: 20px;
        }

        /* =====================================================
           INFO LIST
        ====================================================== */

        .info-list {
            display: grid;
            grid-template-columns: 1fr 1fr;

            gap: 16px 22px;
        }

        .info-item {
            min-width: 0;
        }

        .info-label {
            color: var(--muted);

            font-size: 10px;
            font-weight: 500;

            margin-bottom: 5px;
        }

        .info-value {
            color: var(--text);

            font-size: 12px;
            font-weight: 600;

            word-break: break-word;
        }

        .info-value.normal {
            font-weight: 500;
        }

        /* =====================================================
           MAINTENANCE HIGHLIGHT
        ====================================================== */

        .maintenance-summary {
            display: grid;

            grid-template-columns: repeat(3, 1fr);

            gap: 12px;

            margin-bottom: 18px;
        }

        .summary-box {
            background: #f8fafc;

            border: 1px solid var(--border);

            border-radius: 10px;

            padding: 14px;
        }

        .summary-label {
            color: var(--muted);

            font-size: 10px;

            margin-bottom: 6px;
        }

        .summary-value {
            color: var(--text);

            font-size: 13px;
            font-weight: 700;
        }

        /* =====================================================
           ACTION BOX
        ====================================================== */

        .action-box {
            background: var(--primary-light);

            border: 1px solid #d8eaff;

            border-radius: 10px;

            padding: 15px;

            margin-top: 18px;
        }

        .action-label {
            font-size: 10px;
            font-weight: 600;

            color: var(--primary);

            margin-bottom: 7px;

            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .action-text {
            font-size: 13px;
            line-height: 1.7;

            color: var(--text);

            white-space: pre-line;
        }

        /* =====================================================
           NOTE
        ====================================================== */

        .note-box {
            background: #fafbfc;

            border: 1px solid var(--border);

            border-radius: 10px;

            padding: 15px;

            font-size: 12px;

            color: #596273;

            line-height: 1.7;

            white-space: pre-line;
        }

        /* =====================================================
           FOOTER ACTION
        ====================================================== */

        .bottom-actions {
            display: flex;
            justify-content: flex-end;

            gap: 8px;

            margin-top: 20px;
        }

        /* =====================================================
           MOBILE
        ====================================================== */

        @media (max-width: 991px) {

            .main {
                margin-left: 0;
            }

            .mobile-menu {
                display: flex;
            }

            .content {
                padding: 20px;
            }

            .topbar {
                padding: 0 20px;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .detail-card.full {
                grid-column: auto;
            }

        }

        @media (max-width: 767px) {

            .topbar {
                height: 68px;
            }

            .page-title {
                font-size: 15px;
            }

            .page-subtitle {
                display: none;
            }

            .user-info {
                display: none;
            }

            .content {
                padding: 15px;
            }

            .hero-card {
                flex-direction: column;
                align-items: stretch;
            }

            .hero-left {
                align-items: flex-start;
            }

            .hero-actions {
                width: 100%;
            }

            .hero-actions .btn-main {
                flex: 1;
                justify-content: center;
            }

            .info-list {
                grid-template-columns: 1fr;
            }

            .maintenance-summary {
                grid-template-columns: 1fr;
            }

            .component-image {
                width: 75px;
                height: 75px;
            }

            .hero-title {
                font-size: 17px;
            }

            .bottom-actions {
                flex-direction: column;
            }

            .bottom-actions .btn-main {
                width: 100%;
                justify-content: center;
            }
        }

    </style>

</head>

<body>

<!-- =========================================================
     MAIN
========================================================== -->

<div class="main">


    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <header class="topbar">

        <div class="topbar-left">

            <button
                type="button"
                class="mobile-menu"
                id="mobileMenu">

                <i class="bi bi-list"></i>

            </button>


            <div>

                <h1 class="page-title">
                    Detail Maintenance
                </h1>

                <div class="page-subtitle">
                    Informasi lengkap riwayat maintenance
                </div>

            </div>

        </div>


        <div class="user-box">

            <div class="user-avatar">

                <i class="bi bi-person-fill"></i>

            </div>


            <div class="user-info">

                <div class="user-name">
                    <?= tampil($_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Admin') ?>
                </div>

                <div class="user-role">
                    <?= tampil($_SESSION['role'] ?? 'Admin') ?>
                </div>

            </div>

        </div>

    </header>


    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <main class="content">


        <!-- =================================================
             BACK
        ================================================== -->

        <a
            href="index.php"
            class="back-button">

            <i class="bi bi-arrow-left"></i>

            Kembali ke Riwayat Maintenance

        </a>


        <!-- =================================================
             HERO
        ================================================== -->

        <section class="hero-card">


            <div class="hero-left">


                <div class="component-image">

                    <?php if ($gambarUrl !== ''): ?>

                        <img
                            src="<?= e($gambarUrl) ?>"
                            alt="<?= e($namaKomponen) ?>">

                    <?php else: ?>

                        <div class="no-image">

                            <i class="bi bi-pc-display"></i>

                        </div>

                    <?php endif; ?>

                </div>


                <div>

                    <h2 class="hero-title">
                        <?= e($namaKomponen) ?>
                    </h2>


                    <div class="hero-meta">

                        <span>

                            <i class="bi bi-calendar3"></i>

                            <?= formatTanggal($data['tanggal']) ?>

                        </span>


                        <span>

                            <i class="bi bi-person-gear"></i>

                            <?= tampil($data['teknisi']) ?>

                        </span>


                        <?php if (!empty($data['serial_number'])): ?>

                            <span>

                                <i class="bi bi-upc-scan"></i>

                                <?= e($data['serial_number']) ?>

                            </span>

                        <?php endif; ?>

                    </div>


                    <div class="mt-2">

                        <?= badgeStatus($data['status']) ?>

                    </div>

                </div>

            </div>


            <div class="hero-actions">

                <a
                    href="edit.php?id=<?= (int)$data['id'] ?>"
                    class="btn-main btn-primary-custom">

                    <i class="bi bi-pencil-square"></i>

                    Edit

                </a>


                <a
                    href="hapus.php?id=<?= (int)$data['id'] ?>"
                    class="btn-main btn-danger-custom"
                    onclick="return confirm('Yakin ingin menghapus riwayat maintenance ini? Data yang sudah dihapus tidak dapat dikembalikan.');">

                    <i class="bi bi-trash3"></i>

                    Hapus

                </a>

            </div>


        </section>


        <!-- =================================================
             DETAIL GRID
        ================================================== -->

        <div class="detail-grid">


            <!-- =============================================
                 DATA MAINTENANCE
            ============================================== -->

            <section class="detail-card">

                <div class="card-header-custom">

                    <div class="card-header-title">

                        <i class="bi bi-wrench-adjustable"></i>

                        Data Maintenance

                    </div>

                </div>


                <div class="card-body-custom">


                    <div class="maintenance-summary">


                        <div class="summary-box">

                            <div class="summary-label">
                                Tanggal
                            </div>

                            <div class="summary-value">
                                <?= formatTanggal($data['tanggal']) ?>
                            </div>

                        </div>


                        <div class="summary-box">

                            <div class="summary-label">
                                Status
                            </div>

                            <div class="summary-value">
                                <?= badgeStatus($data['status']) ?>
                            </div>

                        </div>


                        <div class="summary-box">

                            <div class="summary-label">
                                Teknisi
                            </div>

                            <div class="summary-value">
                                <?= tampil($data['teknisi']) ?>
                            </div>

                        </div>

                    </div>


                    <div class="info-list">


                        <div class="info-item">

                            <div class="info-label">
                                Nama Bagian
                            </div>

                            <div class="info-value">
                                <?= tampil($data['nama_bagian']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Teknisi / Pelaksana
                            </div>

                            <div class="info-value">
                                <?= tampil($data['teknisi']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Status Maintenance
                            </div>

                            <div class="info-value">
                                <?= badgeStatus($data['status']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Tanggal Maintenance
                            </div>

                            <div class="info-value">
                                <?= formatTanggal($data['tanggal']) ?>
                            </div>

                        </div>


                    </div>


                    <div class="action-box">

                        <div class="action-label">

                            <i class="bi bi-tools me-1"></i>

                            Tindakan Maintenance

                        </div>


                        <div class="action-text">

                            <?= tampil($data['tindakan'], 'Tidak ada tindakan yang dicatat.') ?>

                        </div>

                    </div>

                </div>

            </section>


            <!-- =============================================
                 DATA KOMPONEN
            ============================================== -->

            <section class="detail-card">

                <div class="card-header-custom">

                    <div class="card-header-title">

                        <i class="bi bi-pc-display"></i>

                        Data Komponen

                    </div>

                </div>


                <div class="card-body-custom">


                    <div class="info-list">


                        <div class="info-item">

                            <div class="info-label">
                                Jenis Komponen
                            </div>

                            <div class="info-value">
                                <?= tampil($data['jenis_komponen']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Nama Bagian
                            </div>

                            <div class="info-value">
                                <?= tampil($data['komponen_nama_bagian']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Serial Number
                            </div>

                            <div class="info-value">
                                <?= tampil($data['serial_number']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Kategori
                            </div>

                            <div class="info-value">
                                <?= tampil($data['kategori']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Brand
                            </div>

                            <div class="info-value">
                                <?= tampil($data['brand']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Tipe
                            </div>

                            <div class="info-value">
                                <?= tampil($data['tipe']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Part Number
                            </div>

                            <div class="info-value">
                                <?= tampil($data['part_number']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Kondisi Komponen
                            </div>

                            <div class="info-value">
                                <?= tampil($data['kondisi_komponen']) ?>
                            </div>

                        </div>


                    </div>


                    <?php if (!empty($data['spesifikasi'])): ?>

                        <div class="action-box">

                            <div class="action-label">

                                <i class="bi bi-card-text me-1"></i>

                                Spesifikasi

                            </div>


                            <div class="action-text">

                                <?= e($data['spesifikasi']) ?>

                            </div>

                        </div>

                    <?php endif; ?>

                </div>

            </section>


            <!-- =============================================
                 POSISI MESIN
            ============================================== -->

            <section class="detail-card">

                <div class="card-header-custom">

                    <div class="card-header-title">

                        <i class="bi bi-diagram-3-fill"></i>

                        Posisi Mesin

                    </div>

                </div>


                <div class="card-body-custom">


                    <div class="info-list">


                        <div class="info-item">

                            <div class="info-label">
                                Area
                            </div>

                            <div class="info-value">
                                <?= tampil($data['nama_area']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Jenis Mesin
                            </div>

                            <div class="info-value">
                                <?= tampil($data['nama_jenis_mesin']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Mesin
                            </div>

                            <div class="info-value">
                                <?= tampil($namaMesin) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Sub Mesin
                            </div>

                            <div class="info-value">
                                <?= tampil($namaSubMesin) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Lokasi
                            </div>

                            <div class="info-value">
                                <?= tampil($lokasi) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Nama Bagian Maintenance
                            </div>

                            <div class="info-value">
                                <?= tampil($data['nama_bagian']) ?>
                            </div>

                        </div>


                    </div>

                </div>

            </section>


            <!-- =============================================
                 SPESIFIKASI TEKNIS
            ============================================== -->

            <section class="detail-card">

                <div class="card-header-custom">

                    <div class="card-header-title">

                        <i class="bi bi-cpu-fill"></i>

                        Spesifikasi Teknis

                    </div>

                </div>


                <div class="card-body-custom">


                    <div class="info-list">


                        <div class="info-item">

                            <div class="info-label">
                                Daya
                            </div>

                            <div class="info-value">
                                <?= tampil($data['daya']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                IO Address
                            </div>

                            <div class="info-value">
                                <?= tampil($data['io_address']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                IP Address
                            </div>

                            <div class="info-value">
                                <?= tampil($data['ip_address']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Input Voltage
                            </div>

                            <div class="info-value">
                                <?= tampil($data['input_voltage']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Frekuensi Input
                            </div>

                            <div class="info-value">
                                <?= tampil($data['frekuensi_input']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Arus Input
                            </div>

                            <div class="info-value">
                                <?= tampil($data['arus_input']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Output
                            </div>

                            <div class="info-value">
                                <?= tampil($data['output']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                Frekuensi Output
                            </div>

                            <div class="info-value">
                                <?= tampil($data['frekuensi_output']) ?>
                            </div>

                        </div>


                        <div class="info-item">

                            <div class="info-label">
                                IP Rating
                            </div>

                            <div class="info-value">
                                <?= tampil($data['ip_rating']) ?>
                            </div>

                        </div>


                    </div>

                </div>

            </section>


            <!-- =============================================
                 KETERANGAN KOMPONEN
            ============================================== -->

            <?php if (!empty(trim((string)$data['keterangan_komponen']))): ?>

                <section class="detail-card full">

                    <div class="card-header-custom">

                        <div class="card-header-title">

                            <i class="bi bi-chat-left-text-fill"></i>

                            Keterangan Komponen

                        </div>

                    </div>


                    <div class="card-body-custom">

                        <div class="note-box">

                            <?= e($data['keterangan_komponen']) ?>

                        </div>

                    </div>

                </section>

            <?php endif; ?>


        </div>


        <!-- =================================================
             BOTTOM ACTIONS
        ================================================== -->

        <div class="bottom-actions">

            <a
                href="index.php"
                class="btn-main btn-light-custom">

                <i class="bi bi-arrow-left"></i>

                Kembali

            </a>


            <a
                href="edit.php?id=<?= (int)$data['id'] ?>"
                class="btn-main btn-primary-custom">

                <i class="bi bi-pencil-square"></i>

                Edit Maintenance

            </a>


            <a
                href="hapus.php?id=<?= (int)$data['id'] ?>"
                class="btn-main btn-danger-custom"
                onclick="return confirm('Yakin ingin menghapus riwayat maintenance ini? Data yang sudah dihapus tidak dapat dikembalikan.');">

                <i class="bi bi-trash3"></i>

                Hapus

            </a>

        </div>


    </main>

</div>


<!-- =========================================================
     SIDEBAR ADMIN
     WAJIB menggunakan admin/sidebar.php
========================================================== -->
<?php include "../sidebar.php"; ?>


<!-- =========================================================
     BOOTSTRAP JS
========================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script>

    /* =====================================================
       MOBILE SIDEBAR
    ====================================================== */

    const mobileMenu = document.getElementById('mobileMenu');
    const sidebar = document.getElementById('sidebar');

    if (mobileMenu && sidebar) {

        mobileMenu.addEventListener('click', function () {

            sidebar.classList.toggle('show');

        });

    }

    /* =====================================================
       TUTUP SIDEBAR SAAT KLIK DI LUAR
    ====================================================== */

    document.addEventListener('click', function (event) {

        if (
            window.innerWidth <= 991 &&
            sidebar.classList.contains('show') &&
            !sidebar.contains(event.target) &&
            !mobileMenu.contains(event.target)
        ) {

            sidebar.classList.remove('show');

        }

    });

</script>

</body>
</html>