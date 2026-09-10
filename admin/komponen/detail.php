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
   ACTIVE SIDEBAR
========================================================= */

$active_menu = 'komponen';


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


function tampil($value)
{
    $value = trim((string)$value);

    return $value !== ''
        ? e($value)
        : '-';
}


function badgeKondisi($kondisi)
{
    $kondisi = trim((string)$kondisi);

    if (strcasecmp($kondisi, 'Baik') === 0) {

        return '
            <span class="badge kondisi-baik">
                <i class="bi bi-check-circle me-1"></i>
                Baik
            </span>
        ';
    }


    if (strcasecmp($kondisi, 'Perlu Pemeriksaan') === 0) {

        return '
            <span class="badge kondisi-periksa">
                <i class="bi bi-exclamation-circle me-1"></i>
                Perlu Pemeriksaan
            </span>
        ';
    }


    if (stripos($kondisi, 'Dalam Perbaikan') !== false) {

        return '
            <span class="badge kondisi-perbaikan">
                <i class="bi bi-tools me-1"></i>
                ' . e($kondisi) . '
            </span>
        ';
    }


    return '
        <span class="badge kondisi-default">
            ' . e($kondisi ?: '-') . '
        </span>
    ';
}


/* =========================================================
   AMBIL ID
========================================================= */

$id = intval($_GET['id'] ?? 0);


if ($id <= 0) {

    header(
        "Location: index.php?error=" .
        urlencode('ID komponen tidak valid')
    );

    exit;
}


/* =========================================================
   AMBIL DATA KOMPONEN
========================================================= */

$sql = "
    SELECT
        k.*,

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

    WHERE k.id = ?

    LIMIT 1
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


if (!$stmt) {

    die(
        "Query gagal disiapkan: " .
        mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);


mysqli_stmt_execute($stmt);


$result =
    mysqli_stmt_get_result($stmt);


$komponen =
    mysqli_fetch_assoc($result);


mysqli_stmt_close($stmt);


/* =========================================================
   CEK DATA
========================================================= */

if (!$komponen) {

    header(
        "Location: index.php?error=" .
        urlencode('Data komponen tidak ditemukan')
    );

    exit;
}


/* =========================================================
   JUMLAH MAINTENANCE
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "
        SELECT COUNT(*) AS total

        FROM riwayat_maintenance

        WHERE id_komponen = ?
    "
);


if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    mysqli_stmt_execute($stmt);

    $result =
        mysqli_stmt_get_result($stmt);

    $rowCount =
        mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

} else {

    $rowCount = [
        'total' => 0
    ];

}


$totalMaintenance =
    intval($rowCount['total'] ?? 0);


/* =========================================================
   RIWAYAT MAINTENANCE
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "
        SELECT
            id,
            tanggal,
            tindakan,
            status,
            teknisi,
            nama_bagian,
            nama_mesin,
            nama_sub_mesin

        FROM riwayat_maintenance

        WHERE id_komponen = ?

        ORDER BY
            tanggal DESC,
            id DESC

        LIMIT 30
    "
);


$maintenanceResult = false;


if ($stmt) {

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    mysqli_stmt_execute($stmt);

    $maintenanceResult =
        mysqli_stmt_get_result($stmt);

}


/* =========================================================
   JUDUL KOMPONEN
========================================================= */

$judul =
    trim($komponen['jenis_komponen'] ?? '');


if ($judul === '') {

    $judul =
        trim($komponen['nama_bagian'] ?? '');

}


if ($judul === '') {

    $judul = 'Komponen';

}


/* =========================================================
   FOTO
========================================================= */

$gambar =
    trim($komponen['gambar'] ?? '');


$gambarFile =
    __DIR__ .
    "/../../uploads/komponen/" .
    basename($gambar);


$gambarUrl =
    "../../uploads/komponen/" .
    rawurlencode(
        basename($gambar)
    );


$gambarAda =
    $gambar !== '' &&
    is_file($gambarFile);

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
        Detail Komponen | Admin
    </title>


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
         POPPINS
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <style>

        /* =====================================================
           VARIABLE
        ===================================================== */

        :root {

            --primary:#075eaa;
            --primary-dark:#064b89;
            --primary-light:#eaf4ff;

            --text:#172033;
            --muted:#7b8494;

            --bg:#f5f7fb;
            --white:#ffffff;

            --border:#e7ebf1;

        }


        /* =====================================================
           GLOBAL
        ===================================================== */

        * {
            box-sizing:border-box;
        }


        body {

            margin:0;

            background:var(--bg);

            color:var(--text);

            font-family:'Poppins',sans-serif;

            font-size:13px;

        }


        /* =====================================================
           MAIN

           Sidebar TIDAK dibuat di halaman ini.
           Sidebar berasal dari:
           admin/sidebar.php
        ===================================================== */

        .main {

            margin-left:240px;

            min-height:100vh;

        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {

            height:72px;

            background:var(--white);

            border-bottom:1px solid var(--border);

            display:flex;

            align-items:center;

            justify-content:space-between;

            padding:0 28px;

            position:sticky;

            top:0;

            z-index:900;

        }


        .topbar-left {

            display:flex;

            align-items:center;

            gap:12px;

            min-width:0;

        }


        .topbar-title {

            font-size:16px;

            font-weight:700;

            margin:0;

        }


        .topbar-subtitle {

            color:var(--muted);

            font-size:10px;

            margin-top:2px;

        }


        .topbar-user {

            display:flex;

            align-items:center;

            gap:10px;

        }


        .user-icon {

            width:36px;

            height:36px;

            border-radius:50%;

            background:var(--primary-light);

            color:var(--primary);

            display:flex;

            align-items:center;

            justify-content:center;

            font-size:17px;

            flex-shrink:0;

        }


        .user-name {

            font-size:12px;

            font-weight:600;

        }


        .user-role {

            font-size:10px;

            color:var(--muted);

        }


        /* =====================================================
           MOBILE TOGGLE

           ID HARUS mobileToggle
           karena dikendalikan oleh admin/sidebar.php
        ===================================================== */

        .mobile-toggle {

            display:none;

            width:38px;

            height:38px;

            align-items:center;

            justify-content:center;

            border:1px solid var(--border);

            border-radius:8px;

            background:#fff;

            color:var(--primary);

            font-size:20px;

            padding:0;

        }


        .mobile-toggle:hover {

            background:var(--primary-light);

            color:var(--primary);

        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .content {

            padding:25px 28px 35px;

        }


        /* =====================================================
           PAGE HEADER
        ===================================================== */

        .page-head {

            display:flex;

            justify-content:space-between;

            align-items:flex-start;

            gap:20px;

            margin-bottom:20px;

        }


        .page-title {

            font-size:22px;

            font-weight:700;

            margin:0 0 4px;

        }


        .page-subtitle {

            margin:0;

            color:var(--muted);

            font-size:11px;

        }


        /* =====================================================
           CARD
        ===================================================== */

        .card-box {

            background:var(--white);

            border:1px solid var(--border);

            border-radius:14px;

            box-shadow:
                0 3px 12px rgba(20,30,50,.035);

        }


        /* =====================================================
           HERO
        ===================================================== */

        .hero {

            padding:22px;

            display:flex;

            gap:22px;

            margin-bottom:20px;

        }


        .component-image {

            width:175px;

            height:175px;

            flex-shrink:0;

            border-radius:12px;

            overflow:hidden;

            background:#f8fafc;

            border:1px solid var(--border);

            display:flex;

            align-items:center;

            justify-content:center;

        }


        .component-image img {

            width:100%;

            height:100%;

            object-fit:cover;

        }


        .no-image {

            text-align:center;

            color:#a0a8b5;

            font-size:10px;

        }


        .no-image i {

            display:block;

            font-size:46px;

            margin-bottom:5px;

        }


        .hero-info {

            flex:1;

            min-width:0;

        }


        .hero-info h2 {

            margin:3px 0 7px;

            font-size:22px;

            font-weight:700;

            word-break:break-word;

        }


        .serial {

            color:var(--muted);

            font-size:11px;

            margin-bottom:13px;

        }


        /* =====================================================
           BADGE
        ===================================================== */

        .badge {

            padding:6px 10px;

            border-radius:20px;

            font-size:10px;

            font-weight:600;

        }


        .kondisi-baik {

            background:#e8f8ef;

            color:#16834d;

        }


        .kondisi-periksa {

            background:#fff5dc;

            color:#a66b00;

        }


        .kondisi-perbaikan {

            background:#ffe8e8;

            color:#c0392b;

        }


        .kondisi-default {

            background:#eef1f5;

            color:#667085;

        }


        /* =====================================================
           ACTION
        ===================================================== */

        .action-buttons {

            display:flex;

            flex-wrap:wrap;

            gap:8px;

            margin-top:18px;

        }


        .btn-primary-custom {

            background:var(--primary);

            border:1px solid var(--primary);

            color:#fff;

            border-radius:8px;

            padding:8px 13px;

            font-size:11px;

            font-weight:600;

            text-decoration:none;

            display:inline-flex;

            align-items:center;

        }


        .btn-primary-custom:hover {

            background:var(--primary-dark);

            border-color:var(--primary-dark);

            color:#fff;

        }


        .btn-danger-custom {

            background:#fff;

            border:1px solid #dc3545;

            color:#dc3545;

            border-radius:8px;

            padding:8px 13px;

            font-size:11px;

            font-weight:600;

            text-decoration:none;

            display:inline-flex;

            align-items:center;

        }


        .btn-danger-custom:hover {

            background:#fff1f2;

            color:#c82333;

        }


        .btn-back {

            border:1px solid var(--border);

            background:#fff;

            color:#5f6878;

            border-radius:8px;

            padding:8px 13px;

            font-size:11px;

            font-weight:500;

            text-decoration:none;

            display:inline-flex;

            align-items:center;

        }


        .btn-back:hover {

            border-color:var(--primary);

            color:var(--primary);

            background:var(--primary-light);

        }


        /* =====================================================
           SECTION
        ===================================================== */

        .section-card {

            margin-bottom:20px;

            overflow:hidden;

        }


        .section-header {

            padding:15px 18px;

            border-bottom:1px solid var(--border);

            font-size:12px;

            font-weight:700;

            display:flex;

            align-items:center;

            gap:8px;

        }


        .section-header > i {

            color:var(--primary);

            font-size:15px;

        }


        .section-body {

            padding:18px;

        }


        /* =====================================================
           INFO GRID
        ===================================================== */

        .info-grid {

            display:grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap:17px 20px;

        }


        .info-item {

            min-width:0;

        }


        .info-label {

            color:var(--muted);

            font-size:10px;

            margin-bottom:4px;

        }


        .info-value {

            font-size:12px;

            font-weight:600;

            word-break:break-word;

        }


        /* =====================================================
           DESCRIPTION
        ===================================================== */

        .description {

            color:#4b5565;

            line-height:1.7;

            white-space:pre-wrap;

            font-size:12px;

        }


        /* =====================================================
           TABLE
        ===================================================== */

        .maintenance-table {

            margin:0;

        }


        .maintenance-table th {

            background:#f8fafc;

            color:#667085;

            font-size:10px;

            font-weight:600;

            white-space:nowrap;

            padding:11px 12px;

        }


        .maintenance-table td {

            font-size:11px;

            vertical-align:middle;

            padding:11px 12px;

        }


        .maintenance-table tbody tr:hover {

            background:#fafcff;

        }


        .status-maintenance {

            display:inline-flex;

            align-items:center;

            gap:4px;

            padding:5px 8px;

            border-radius:15px;

            background:#f1f3f5;

            color:#596273;

            font-size:9px;

            font-weight:600;

        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .empty {

            padding:35px 20px;

            text-align:center;

            color:var(--muted);

            font-size:11px;

        }


        .empty i {

            font-size:30px;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width:1100px) {

            .info-grid {

                grid-template-columns:
                    repeat(3, minmax(0,1fr));

            }

        }


        @media (max-width:850px) {

            .main {

                margin-left:0;

            }


            .mobile-toggle {

                display:inline-flex;

            }


            .topbar {

                padding:0 18px;

            }


            .content {

                padding:20px 16px 30px;

            }


            .hero {

                flex-direction:column;

            }


            .component-image {

                width:150px;

                height:150px;

            }


            .info-grid {

                grid-template-columns:
                    repeat(2, minmax(0,1fr));

            }


            .page-head {

                flex-direction:column;

            }

        }


        @media (max-width:600px) {

            .topbar {

                height:65px;

                padding:0 14px;

            }


            .topbar-subtitle {

                display:none;

            }


            .topbar-title {

                font-size:14px;

            }


            .user-name,

            .user-role {

                display:none;

            }


            .content {

                padding:16px 14px 25px;

            }


            .page-title {

                font-size:19px;

            }


            .hero {

                padding:16px;

            }


            .component-image {

                width:130px;

                height:130px;

            }


            .hero-info h2 {

                font-size:19px;

            }


            .info-grid {

                grid-template-columns:1fr;

            }


            .section-body {

                padding:15px;

            }


            .action-buttons {

                width:100%;

            }


            .action-buttons a {

                flex:1;

                justify-content:center;

            }

        }

    </style>

</head>


<body>


<?php
/*
|--------------------------------------------------------------------------
| SIDEBAR ADMIN
|--------------------------------------------------------------------------
| Sidebar WAJIB berasal dari:
| inventory_mesin/admin/sidebar.php
|
| Tidak ada HTML/CSS/JS sidebar lain di halaman ini.
|--------------------------------------------------------------------------
*/

include "../sidebar.php";
?>


<!-- =========================================================
     MAIN
========================================================= -->

<div class="main">


    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <header class="topbar">

        <div class="topbar-left">


            <!--
                ID mobileToggle sengaja mengikuti
                admin/sidebar.php
            -->

            <button
                type="button"
                class="mobile-toggle"
                id="mobileToggle"
                aria-label="Buka menu"
            >

                <i class="bi bi-list"></i>

            </button>


            <div>

                <h1 class="topbar-title">
                    Detail Komponen
                </h1>

                <div class="topbar-subtitle">
                    Inventory Mesin • Master Data Komponen
                </div>

            </div>

        </div>


        <!-- =================================================
             USER
        ================================================== -->

        <div class="topbar-user">

            <div class="user-icon">

                <i class="bi bi-person"></i>

            </div>


            <div>

                <div class="user-name">

                    <?= e(
                        $_SESSION['nama_lengkap']
                        ?? $_SESSION['username']
                        ?? 'Administrator'
                    ) ?>

                </div>

                <div class="user-role">
                    Administrator
                </div>

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

        <div class="page-head">

            <div>

                <h2 class="page-title">
                    Detail Komponen
                </h2>

                <p class="page-subtitle">
                    Informasi lengkap komponen mesin
                </p>

            </div>


            <a
                href="index.php"
                class="btn-back"
            >

                <i class="bi bi-arrow-left me-1"></i>

                Kembali

            </a>

        </div>


        <!-- =================================================
             HERO
        ================================================== -->

        <div class="card-box hero">


            <!-- FOTO -->

            <div class="component-image">

                <?php if ($gambarAda): ?>

                    <img
                        src="<?= e($gambarUrl) ?>"
                        alt="<?= e($judul) ?>"
                    >

                <?php else: ?>

                    <div class="no-image">

                        <i class="bi bi-image"></i>

                        <small>
                            Tidak ada foto
                        </small>

                    </div>

                <?php endif; ?>

            </div>


            <!-- INFO -->

            <div class="hero-info">

                <h2>
                    <?= e($judul) ?>
                </h2>


                <div class="serial">

                    <i class="bi bi-upc-scan me-1"></i>

                    Serial Number:

                    <strong>
                        <?= tampil(
                            $komponen['serial_number']
                        ) ?>
                    </strong>

                </div>


                <?= badgeKondisi(
                    $komponen['kondisi'] ?? ''
                ) ?>


                <!-- ACTION -->

                <div class="action-buttons">


                    <a
                        href="edit.php?id=<?= $id ?>"
                        class="btn-primary-custom"
                    >

                        <i class="bi bi-pencil me-1"></i>

                        Edit Data

                    </a>


                    <a
                        href="hapus.php?id=<?= $id ?>"
                        class="btn-danger-custom"
                        onclick="
                            return confirm(
                                'Yakin ingin menghapus komponen ini?'
                            );
                        "
                    >

                        <i class="bi bi-trash me-1"></i>

                        Hapus

                    </a>

                </div>

            </div>

        </div>


        <!-- =================================================
             IDENTITAS KOMPONEN
        ================================================== -->

        <div class="card-box section-card">


            <div class="section-header">

                <i class="bi bi-info-circle"></i>

                <span>
                    Identitas Komponen
                </span>

            </div>


            <div class="section-body">

                <div class="info-grid">


                    <div class="info-item">

                        <div class="info-label">
                            Jenis Komponen
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['jenis_komponen']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Nama Bagian
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['nama_bagian']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Serial Number
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['serial_number']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Kategori
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['kategori']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Brand
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['brand']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Tipe
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['tipe']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Part Number
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['part_number']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Spesifikasi
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['spesifikasi']
                            ) ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             POSISI KOMPONEN
        ================================================== -->

        <div class="card-box section-card">


            <div class="section-header">

                <i class="bi bi-diagram-3"></i>

                <span>
                    Posisi Komponen
                </span>

            </div>


            <div class="section-body">

                <div class="info-grid">


                    <div class="info-item">

                        <div class="info-label">
                            Area
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['nama_area']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Jenis Mesin
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['nama_jenis_mesin']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Mesin
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['nama_mesin']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Sub Mesin
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['nama_sub_mesin']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Lokasi
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['lokasi']
                            ) ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             SPESIFIKASI TEKNIS
        ================================================== -->

        <div class="card-box section-card">


            <div class="section-header">

                <i class="bi bi-lightning-charge"></i>

                <span>
                    Spesifikasi Teknis
                </span>

            </div>


            <div class="section-body">

                <div class="info-grid">


                    <div class="info-item">

                        <div class="info-label">
                            Daya
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['daya']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            IO Address
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['io_address']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            IP Address
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['ip_address']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Input Voltage
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['input_voltage']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Frekuensi Input
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['frekuensi_input']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Arus Input
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['arus_input']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Output
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['output']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Frekuensi Output
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['frekuensi_output']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            IP Rating
                        </div>

                        <div class="info-value">
                            <?= tampil(
                                $komponen['ip_rating']
                            ) ?>
                        </div>

                    </div>


                    <div class="info-item">

                        <div class="info-label">
                            Kondisi
                        </div>

                        <div class="info-value">

                            <?= badgeKondisi(
                                $komponen['kondisi'] ?? ''
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             KETERANGAN
        ================================================== -->

        <?php if (
            trim(
                $komponen['keterangan'] ?? ''
            ) !== ''
        ): ?>


            <div class="card-box section-card">


                <div class="section-header">

                    <i class="bi bi-chat-left-text"></i>

                    <span>
                        Keterangan
                    </span>

                </div>


                <div class="section-body description">

                    <?= e(
                        $komponen['keterangan']
                    ) ?>

                </div>

            </div>


        <?php endif; ?>


        <!-- =================================================
             RIWAYAT MAINTENANCE
        ================================================== -->

        <div class="card-box section-card">


            <div class="section-header">

                <i class="bi bi-wrench-adjustable"></i>

                <span>
                    Riwayat Maintenance
                </span>


                <span
                    class="badge bg-light text-dark ms-auto"
                >

                    <?= $totalMaintenance ?>

                    riwayat

                </span>

            </div>


            <div class="table-responsive">


                <?php if (
                    $maintenanceResult &&
                    mysqli_num_rows(
                        $maintenanceResult
                    ) > 0
                ): ?>


                    <table
                        class="table table-hover align-middle maintenance-table"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Tanggal
                                </th>

                                <th>
                                    Tindakan
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Teknisi
                                </th>

                                <th>
                                    Bagian
                                </th>

                                <th>
                                    Mesin
                                </th>

                                <th>
                                    Sub Mesin
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php while (
                            $maintenance =
                                mysqli_fetch_assoc(
                                    $maintenanceResult
                                )
                        ): ?>


                            <tr>


                                <!-- TANGGAL -->

                                <td>

                                    <?php

                                    if (
                                        !empty(
                                            $maintenance['tanggal']
                                        )
                                    ) {

                                        echo e(
                                            date(
                                                'd-m-Y',
                                                strtotime(
                                                    $maintenance['tanggal']
                                                )
                                            )
                                        );

                                    } else {

                                        echo '-';

                                    }

                                    ?>

                                </td>


                                <!-- TINDAKAN -->

                                <td>

                                    <?= tampil(
                                        $maintenance['tindakan']
                                    ) ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span
                                        class="status-maintenance"
                                    >

                                        <i class="bi bi-info-circle"></i>

                                        <?= tampil(
                                            $maintenance['status']
                                        ) ?>

                                    </span>

                                </td>


                                <!-- TEKNISI -->

                                <td>

                                    <?= tampil(
                                        $maintenance['teknisi']
                                    ) ?>

                                </td>


                                <!-- BAGIAN -->

                                <td>

                                    <?= tampil(
                                        $maintenance['nama_bagian']
                                    ) ?>

                                </td>


                                <!-- MESIN -->

                                <td>

                                    <?= tampil(
                                        $maintenance['nama_mesin']
                                    ) ?>

                                </td>


                                <!-- SUB MESIN -->

                                <td>

                                    <?= tampil(
                                        $maintenance['nama_sub_mesin']
                                    ) ?>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                        </tbody>

                    </table>


                <?php else: ?>


                    <div class="empty">

                        <i class="bi bi-wrench"></i>


                        <div class="mt-2">

                            Belum ada riwayat maintenance
                            untuk komponen ini.

                        </div>

                    </div>


                <?php endif; ?>


            </div>

        </div>


    </main>

</div>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>