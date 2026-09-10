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
   KONFIGURASI SIDEBAR
========================================================= */

$base_admin  = '/inventory_mesin/admin';
$active_menu = 'area';


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


/* =========================================================
   ALERT
========================================================= */

$alert      = '';
$alert_type = 'success';


/* =========================================================
   TAMBAH / EDIT / HAPUS
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    /* =====================================================
       TAMBAH AREA
    ===================================================== */

    if ($action === 'tambah') {

        $nama_area  = trim($_POST['nama_area'] ?? '');
        $lokasi     = trim($_POST['lokasi'] ?? '');
        $keterangan = trim($_POST['keterangan'] ?? '');


        if ($nama_area === '') {

            $alert      = 'Nama area wajib diisi.';
            $alert_type = 'danger';

        } else {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO area_bagian
                (nama_area, lokasi, keterangan)
                VALUES (?, ?, ?)"
            );


            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "sss",
                    $nama_area,
                    $lokasi,
                    $keterangan
                );


                if (mysqli_stmt_execute($stmt)) {

                    $alert      = 'Data area berhasil ditambahkan.';
                    $alert_type = 'success';

                } else {

                    $alert      = 'Gagal menambahkan data area.';
                    $alert_type = 'danger';

                }


                mysqli_stmt_close($stmt);

            } else {

                $alert      = 'Query tambah area gagal.';
                $alert_type = 'danger';

            }

        }

    }


    /* =====================================================
       EDIT AREA
    ===================================================== */

    elseif ($action === 'edit') {

        $id         = intval($_POST['id'] ?? 0);
        $nama_area  = trim($_POST['nama_area'] ?? '');
        $lokasi     = trim($_POST['lokasi'] ?? '');
        $keterangan = trim($_POST['keterangan'] ?? '');


        if ($id <= 0 || $nama_area === '') {

            $alert      = 'Data area tidak valid.';
            $alert_type = 'danger';

        } else {

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE area_bagian
                 SET nama_area = ?,
                     lokasi = ?,
                     keterangan = ?
                 WHERE id = ?"
            );


            if ($stmt) {

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssi",
                    $nama_area,
                    $lokasi,
                    $keterangan,
                    $id
                );


                if (mysqli_stmt_execute($stmt)) {

                    $alert      = 'Data area berhasil diperbarui.';
                    $alert_type = 'success';

                } else {

                    $alert      = 'Gagal memperbarui data area.';
                    $alert_type = 'danger';

                }


                mysqli_stmt_close($stmt);

            } else {

                $alert      = 'Query edit area gagal.';
                $alert_type = 'danger';

            }

        }

    }


    /* =====================================================
       HAPUS AREA
    ===================================================== */

    elseif ($action === 'hapus') {

        $id = intval($_POST['id'] ?? 0);


        if ($id <= 0) {

            $alert      = 'ID area tidak valid.';
            $alert_type = 'danger';

        } else {


            /* -------------------------------------------------
               CEK RELASI JENIS MESIN
            ------------------------------------------------- */

            $cek = mysqli_prepare(
                $conn,
                "SELECT COUNT(*) AS jumlah
                 FROM jenis_mesin
                 WHERE id_area = ?"
            );


            if ($cek) {

                mysqli_stmt_bind_param(
                    $cek,
                    "i",
                    $id
                );

                mysqli_stmt_execute($cek);

                $hasil_cek = mysqli_stmt_get_result($cek);

                $data_cek = mysqli_fetch_assoc($hasil_cek);

                mysqli_stmt_close($cek);


                if ((int)($data_cek['jumlah'] ?? 0) > 0) {

                    $alert =
                        'Area tidak dapat dihapus karena masih digunakan oleh data jenis mesin.';

                    $alert_type = 'warning';

                } else {


                    /* -------------------------------------------------
                       CEK RELASI MESIN
                    ------------------------------------------------- */

                    $cek = mysqli_prepare(
                        $conn,
                        "SELECT COUNT(*) AS jumlah
                         FROM mesin
                         WHERE id_area = ?"
                    );


                    if ($cek) {

                        mysqli_stmt_bind_param(
                            $cek,
                            "i",
                            $id
                        );

                        mysqli_stmt_execute($cek);

                        $hasil_cek = mysqli_stmt_get_result($cek);

                        $data_cek = mysqli_fetch_assoc($hasil_cek);

                        mysqli_stmt_close($cek);


                        if ((int)($data_cek['jumlah'] ?? 0) > 0) {

                            $alert =
                                'Area tidak dapat dihapus karena masih digunakan oleh data mesin.';

                            $alert_type = 'warning';

                        } else {


                            /* -------------------------------------------------
                               HAPUS AREA
                            ------------------------------------------------- */

                            $stmt = mysqli_prepare(
                                $conn,
                                "DELETE FROM area_bagian
                                 WHERE id = ?"
                            );


                            if ($stmt) {

                                mysqli_stmt_bind_param(
                                    $stmt,
                                    "i",
                                    $id
                                );


                                if (mysqli_stmt_execute($stmt)) {

                                    $alert =
                                        'Data area berhasil dihapus.';

                                    $alert_type = 'success';

                                } else {

                                    $alert =
                                        'Gagal menghapus data area.';

                                    $alert_type = 'danger';

                                }


                                mysqli_stmt_close($stmt);

                            } else {

                                $alert =
                                    'Query hapus area gagal.';

                                $alert_type = 'danger';

                            }

                        }

                    } else {

                        $alert =
                            'Gagal memeriksa relasi mesin.';

                        $alert_type = 'danger';

                    }

                }

            } else {

                $alert =
                    'Gagal memeriksa relasi jenis mesin.';

                $alert_type = 'danger';

            }

        }

    }

}


/* =========================================================
   DATA AREA
========================================================= */

$query_area = mysqli_query(
    $conn,
    "SELECT
        ab.id,
        ab.nama_area,
        ab.lokasi,
        ab.keterangan,

        (
            SELECT COUNT(*)
            FROM jenis_mesin jm
            WHERE jm.id_area = ab.id
        ) AS jumlah_jenis_mesin,

        (
            SELECT COUNT(*)
            FROM mesin m
            WHERE m.id_area = ab.id
        ) AS jumlah_mesin

     FROM area_bagian ab

     ORDER BY ab.nama_area ASC"
);


if (!$query_area) {

    die(
        "Query data area gagal: "
        . e(mysqli_error($conn))
    );

}


/* =========================================================
   STATISTIK TOTAL AREA
========================================================= */

$total_area = 0;

$result_total = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM area_bagian"
);


if ($result_total) {

    $row_total = mysqli_fetch_assoc($result_total);

    $total_area = (int)(
        $row_total['total'] ?? 0
    );

}


/* =========================================================
   STATISTIK TOTAL MESIN
========================================================= */

$total_mesin = 0;

$result_mesin = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM mesin"
);


if ($result_mesin) {

    $row_mesin = mysqli_fetch_assoc($result_mesin);

    $total_mesin = (int)(
        $row_mesin['total'] ?? 0
    );

}


/* =========================================================
   STATISTIK TOTAL JENIS MESIN
========================================================= */

$total_jenis = 0;

$result_jenis = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM jenis_mesin"
);


if ($result_jenis) {

    $row_jenis = mysqli_fetch_assoc($result_jenis);

    $total_jenis = (int)(
        $row_jenis['total'] ?? 0
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
        Data Area • Admin Inventory Mesin
    </title>


    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
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

        }


        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family: 'Poppins', sans-serif;

            background: var(--bg);

            color: var(--text);

        }


        /* =====================================================
           MAIN
        ====================================================== */

        .main-wrapper {

            margin-left: 240px;

            min-height: 100vh;

            padding: 28px 30px;

        }


        /* =====================================================
           TOPBAR
        ====================================================== */

        .topbar {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 25px;

        }


        .page-title {

            margin: 0;

            font-size: 25px;

            font-weight: 700;

        }


        .page-subtitle {

            margin: 5px 0 0;

            color: var(--muted);

            font-size: 13px;

        }


        .admin-profile {

            display: flex;

            align-items: center;

            gap: 11px;

        }


        .admin-avatar {

            width: 42px;

            height: 42px;

            border-radius: 50%;

            background: var(--primary-light);

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;

        }


        .admin-name {

            font-size: 13px;

            font-weight: 600;

        }


        .admin-role {

            color: var(--muted);

            font-size: 11px;

        }


        /* =====================================================
           MOBILE TOPBAR
        ====================================================== */

        .mobile-topbar {

            display: none;

        }


        .mobile-menu-btn {

            width: 40px;

            height: 40px;

            border: 1px solid var(--border);

            background: var(--white);

            border-radius: 9px;

            color: var(--text);

            display: flex;

            align-items: center;

            justify-content: center;

        }


        /* =====================================================
           ALERT
        ====================================================== */

        .alert-custom {

            border: 0;

            border-radius: 12px;

            font-size: 13px;

            margin-bottom: 20px;

            box-shadow:
                0 5px 18px rgba(20, 40, 80, .05);

        }


        /* =====================================================
           STAT CARD
        ====================================================== */

        .stat-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 15px;

            padding: 19px;

            height: 100%;

            box-shadow:
                0 5px 18px rgba(20, 40, 80, .04);

        }


        .stat-icon {

            width: 43px;

            height: 43px;

            border-radius: 11px;

            background: var(--primary-light);

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;

            margin-bottom: 13px;

        }


        .stat-label {

            font-size: 11px;

            color: var(--muted);

            margin-bottom: 3px;

        }


        .stat-number {

            font-size: 23px;

            font-weight: 700;

        }


        /* =====================================================
           CONTENT CARD
        ====================================================== */

        .content-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 15px;

            box-shadow:
                0 5px 18px rgba(20, 40, 80, .04);

            overflow: hidden;

        }


        .content-header {

            padding: 20px 22px;

            border-bottom: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

        }


        .content-title {

            margin: 0;

            font-size: 16px;

            font-weight: 700;

        }


        .content-description {

            margin: 4px 0 0;

            font-size: 11px;

            color: var(--muted);

        }


        /* =====================================================
           BUTTON
        ====================================================== */

        .btn-primary-custom {

            background: var(--primary);

            border-color: var(--primary);

            color: white;

            border-radius: 9px;

            font-size: 12px;

            font-weight: 600;

            padding: 9px 15px;

        }


        .btn-primary-custom:hover {

            background: var(--primary-dark);

            border-color: var(--primary-dark);

            color: white;

        }


        .btn-soft {

            background: var(--primary-light);

            color: var(--primary);

            border: 0;

            border-radius: 8px;

            width: 34px;

            height: 34px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            transition: .2s;

        }


        .btn-soft:hover {

            background: #dcecff;

            color: var(--primary-dark);

        }


        .btn-soft-danger {

            background: #fff1f2;

            color: var(--danger);

        }


        .btn-soft-danger:hover {

            background: #ffe0e4;

            color: var(--danger);

        }


        /* =====================================================
           TABLE
        ====================================================== */

        .table-wrapper {

            overflow-x: auto;

        }


        .table {

            margin: 0;

            min-width: 850px;

        }


        .table thead th {

            background: #fafbfd;

            border-bottom: 1px solid var(--border);

            color: #667085;

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: .3px;

            padding: 13px 18px;

            white-space: nowrap;

        }


        .table tbody td {

            padding: 14px 18px;

            border-bottom: 1px solid #f0f2f5;

            font-size: 12px;

            vertical-align: middle;

        }


        .table tbody tr:last-child td {

            border-bottom: 0;

        }


        .table tbody tr:hover {

            background: #fafcff;

        }


        .area-name {

            font-weight: 600;

            color: var(--text);

        }


        .count-badge {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-width: 28px;

            height: 26px;

            padding: 0 8px;

            border-radius: 7px;

            background: #f1f5f9;

            color: #475467;

            font-size: 11px;

            font-weight: 600;

        }


        .action-group {

            display: flex;

            align-items: center;

            gap: 6px;

        }


        /* =====================================================
           EMPTY STATE
        ====================================================== */

        .empty-state {

            padding: 65px 20px;

            text-align: center;

            color: var(--muted);

        }


        .empty-icon {

            width: 60px;

            height: 60px;

            border-radius: 50%;

            background: var(--primary-light);

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 25px;

            margin: 0 auto 15px;

        }


        .empty-state h5 {

            font-size: 15px;

            color: var(--text);

            margin-bottom: 5px;

        }


        .empty-state p {

            font-size: 12px;

            margin: 0;

        }


        /* =====================================================
           MODAL
        ====================================================== */

        .modal-content {

            border: 0;

            border-radius: 16px;

            overflow: hidden;

        }


        .modal-header {

            padding: 18px 22px;

            border-bottom: 1px solid var(--border);

        }


        .modal-title {

            font-size: 16px;

            font-weight: 700;

        }


        .modal-body {

            padding: 22px;

        }


        .modal-footer {

            padding: 15px 22px;

            border-top: 1px solid var(--border);

        }


        .form-label {

            font-size: 12px;

            font-weight: 600;

            margin-bottom: 7px;

        }


        .form-control {

            border-color: #dfe4ea;

            border-radius: 9px;

            padding: 10px 12px;

            font-size: 12px;

        }


        .form-control:focus {

            border-color: var(--primary);

            box-shadow:
                0 0 0 .2rem rgba(7, 94, 170, .10);

        }


        .detail-box {

            background: #f8fafc;

            border: 1px solid var(--border);

            border-radius: 10px;

            padding: 13px 15px;

            margin-bottom: 12px;

        }


        .detail-label {

            color: var(--muted);

            font-size: 10px;

            margin-bottom: 3px;

        }


        .detail-value {

            color: var(--text);

            font-size: 12px;

            font-weight: 600;

        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 991px) {

            .main-wrapper {

                margin-left: 0;

                padding: 20px;

            }


            .mobile-topbar {

                display: flex;

                align-items: center;

                justify-content: space-between;

                margin-bottom: 20px;

            }


            .topbar {

                margin-bottom: 20px;

            }


            .admin-profile {

                display: none;

            }

        }


        @media (max-width: 575px) {

            .main-wrapper {

                padding: 15px;

            }


            .page-title {

                font-size: 21px;

            }


            .content-header {

                align-items: flex-start;

                flex-direction: column;

            }


            .content-header .btn {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR ADMIN BERSAMA
========================================================== -->

<?php

include "../sidebar.php";

?>


<!-- =========================================================
     MAIN CONTENT
========================================================== -->

<main class="main-wrapper">


    <!-- =====================================================
         MOBILE TOPBAR
    ====================================================== -->

    <div class="mobile-topbar">

        <button
            type="button"
            class="mobile-menu-btn"
            id="mobileToggle"
        >

            <i class="bi bi-list fs-5"></i>

        </button>


        <div class="admin-avatar">

            <i class="bi bi-person-fill"></i>

        </div>

    </div>


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="topbar">

        <div>

            <h1 class="page-title">
                Data Area
            </h1>

            <p class="page-subtitle">
                Kelola area atau bagian tempat mesin berada.
            </p>

        </div>


        <div class="admin-profile">

            <div class="admin-avatar">

                <i class="bi bi-person-fill"></i>

            </div>


            <div>

                <div class="admin-name">

                    <?= e(
                        $_SESSION['nama_lengkap']
                        ?? $_SESSION['username']
                        ?? 'Administrator'
                    ) ?>

                </div>


                <div class="admin-role">
                    Administrator
                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         ALERT
    ====================================================== -->

    <?php if ($alert !== ''): ?>

        <div
            class="alert alert-<?= e($alert_type) ?> alert-custom alert-dismissible fade show"
            role="alert"
        >

            <?php if ($alert_type === 'success'): ?>

                <i class="bi bi-check-circle me-2"></i>

            <?php elseif ($alert_type === 'warning'): ?>

                <i class="bi bi-exclamation-triangle me-2"></i>

            <?php else: ?>

                <i class="bi bi-x-circle me-2"></i>

            <?php endif; ?>


            <?= e($alert) ?>


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         STATISTICS
    ====================================================== -->

    <div class="row g-3 mb-4">


        <!-- TOTAL AREA -->

        <div class="col-12 col-md-4">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="bi bi-geo-alt-fill"></i>

                </div>


                <div class="stat-label">
                    TOTAL AREA
                </div>


                <div class="stat-number">

                    <?= number_format($total_area) ?>

                </div>

            </div>

        </div>


        <!-- TOTAL JENIS MESIN -->

        <div class="col-12 col-md-4">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="bi bi-diagram-3-fill"></i>

                </div>


                <div class="stat-label">
                    TOTAL JENIS MESIN
                </div>


                <div class="stat-number">

                    <?= number_format($total_jenis) ?>

                </div>

            </div>

        </div>


        <!-- TOTAL MESIN -->

        <div class="col-12 col-md-4">

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="bi bi-cpu-fill"></i>

                </div>


                <div class="stat-label">
                    TOTAL MESIN
                </div>


                <div class="stat-number">

                    <?= number_format($total_mesin) ?>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         DATA AREA
    ====================================================== -->

    <div class="content-card">


        <!-- HEADER -->

        <div class="content-header">

            <div>

                <h2 class="content-title">
                    Daftar Area
                </h2>


                <p class="content-description">

                    Data area yang terdaftar pada
                    sistem inventory mesin.

                </p>

            </div>


            <button
                type="button"
                class="btn btn-primary-custom"
                data-bs-toggle="modal"
                data-bs-target="#modalTambah"
            >

                <i class="bi bi-plus-lg me-1"></i>

                Tambah Area

            </button>

        </div>


        <!-- =================================================
             TABLE
        ================================================== -->

        <div class="table-wrapper">

            <table class="table align-middle">


                <thead>

                    <tr>

                        <th width="60">
                            No
                        </th>

                        <th>
                            Area
                        </th>

                        <th>
                            Lokasi
                        </th>

                        <th class="text-center">
                            Jenis Mesin
                        </th>

                        <th class="text-center">
                            Mesin
                        </th>

                        <th>
                            Keterangan
                        </th>

                        <th width="135">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (mysqli_num_rows($query_area) > 0): ?>


                    <?php

                    $no = 1;

                    while (
                        $area = mysqli_fetch_assoc($query_area)
                    ):

                    ?>


                        <tr>


                            <!-- NO -->

                            <td>

                                <?= $no++ ?>

                            </td>


                            <!-- AREA -->

                            <td>

                                <div class="area-name">

                                    <?= e(
                                        $area['nama_area']
                                    ) ?>

                                </div>

                            </td>


                            <!-- LOKASI -->

                            <td>

                                <?php if (
                                    !empty($area['lokasi'])
                                ): ?>

                                    <?= e(
                                        $area['lokasi']
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">
                                        Belum diisi
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- JENIS MESIN -->

                            <td class="text-center">

                                <span class="count-badge">

                                    <?= (int)(
                                        $area['jumlah_jenis_mesin']
                                    ) ?>

                                </span>

                            </td>


                            <!-- MESIN -->

                            <td class="text-center">

                                <span class="count-badge">

                                    <?= (int)(
                                        $area['jumlah_mesin']
                                    ) ?>

                                </span>

                            </td>


                            <!-- KETERANGAN -->

                            <td>

                                <?php if (
                                    !empty($area['keterangan'])
                                ): ?>

                                    <span
                                        title="<?= e(
                                            $area['keterangan']
                                        ) ?>"
                                    >

                                        <?= e(
                                            mb_strimwidth(
                                                $area['keterangan'],
                                                0,
                                                45,
                                                '...'
                                            )
                                        ) ?>

                                    </span>

                                <?php else: ?>

                                    <span class="text-muted">
                                        -
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- AKSI -->

                            <td>

                                <div class="action-group">


                                    <!-- DETAIL -->

                                    <button
                                        type="button"
                                        class="btn-soft"
                                        title="Detail"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalDetail<?= (int)$area['id'] ?>"
                                    >

                                        <i class="bi bi-eye"></i>

                                    </button>


                                    <!-- EDIT -->

                                    <button
                                        type="button"
                                        class="btn-soft"
                                        title="Edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEdit<?= (int)$area['id'] ?>"
                                    >

                                        <i class="bi bi-pencil"></i>

                                    </button>


                                    <!-- HAPUS -->

                                    <button
                                        type="button"
                                        class="btn-soft btn-soft-danger"
                                        title="Hapus"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalHapus<?= (int)$area['id'] ?>"
                                    >

                                        <i class="bi bi-trash"></i>

                                    </button>

                                </div>

                            </td>

                        </tr>


                        <!-- =================================================
                             MODAL DETAIL
                        ================================================== -->

                        <div
                            class="modal fade"
                            id="modalDetail<?= (int)$area['id'] ?>"
                            tabindex="-1"
                            aria-hidden="true"
                        >

                            <div class="modal-dialog modal-dialog-centered">

                                <div class="modal-content">


                                    <div class="modal-header">

                                        <h5 class="modal-title">

                                            <i
                                                class="bi bi-geo-alt-fill text-primary me-2"
                                            ></i>

                                            Detail Area

                                        </h5>


                                        <button
                                            type="button"
                                            class="btn-close"
                                            data-bs-dismiss="modal"
                                        ></button>

                                    </div>


                                    <div class="modal-body">


                                        <div class="detail-box">

                                            <div class="detail-label">
                                                Nama Area
                                            </div>


                                            <div class="detail-value">

                                                <?= e(
                                                    $area['nama_area']
                                                ) ?>

                                            </div>

                                        </div>


                                        <div class="row g-3">


                                            <!-- LOKASI -->

                                            <div class="col-12 col-md-6">

                                                <div class="detail-box mb-0">

                                                    <div class="detail-label">
                                                        Lokasi
                                                    </div>


                                                    <div class="detail-value">

                                                        <?= !empty(
                                                            $area['lokasi']
                                                        )
                                                            ? e(
                                                                $area['lokasi']
                                                            )
                                                            : '-'
                                                        ?>

                                                    </div>

                                                </div>

                                            </div>


                                            <!-- JENIS -->

                                            <div class="col-12 col-md-3">

                                                <div class="detail-box mb-0">

                                                    <div class="detail-label">
                                                        Jenis Mesin
                                                    </div>


                                                    <div class="detail-value">

                                                        <?= (int)(
                                                            $area['jumlah_jenis_mesin']
                                                        ) ?>

                                                    </div>

                                                </div>

                                            </div>


                                            <!-- MESIN -->

                                            <div class="col-12 col-md-3">

                                                <div class="detail-box mb-0">

                                                    <div class="detail-label">
                                                        Mesin
                                                    </div>


                                                    <div class="detail-value">

                                                        <?= (int)(
                                                            $area['jumlah_mesin']
                                                        ) ?>

                                                    </div>

                                                </div>

                                            </div>

                                        </div>


                                        <!-- KETERANGAN -->

                                        <div class="detail-box mt-3 mb-0">

                                            <div class="detail-label">
                                                Keterangan
                                            </div>


                                            <div class="detail-value">

                                                <?= !empty(
                                                    $area['keterangan']
                                                )
                                                    ? nl2br(
                                                        e(
                                                            $area['keterangan']
                                                        )
                                                    )
                                                    : '-'
                                                ?>

                                            </div>

                                        </div>

                                    </div>


                                    <div class="modal-footer">

                                        <button
                                            type="button"
                                            class="btn btn-light"
                                            data-bs-dismiss="modal"
                                        >
                                            Tutup
                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             MODAL EDIT
                        ================================================== -->

                        <div
                            class="modal fade"
                            id="modalEdit<?= (int)$area['id'] ?>"
                            tabindex="-1"
                            aria-hidden="true"
                        >

                            <div class="modal-dialog modal-dialog-centered">

                                <div class="modal-content">


                                    <form method="POST">


                                        <input
                                            type="hidden"
                                            name="action"
                                            value="edit"
                                        >


                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$area['id'] ?>"
                                        >


                                        <div class="modal-header">

                                            <h5 class="modal-title">

                                                <i
                                                    class="bi bi-pencil-square text-primary me-2"
                                                ></i>

                                                Edit Area

                                            </h5>


                                            <button
                                                type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"
                                            ></button>

                                        </div>


                                        <div class="modal-body">


                                            <!-- NAMA -->

                                            <div class="mb-3">

                                                <label class="form-label">

                                                    Nama Area

                                                    <span class="text-danger">
                                                        *
                                                    </span>

                                                </label>


                                                <input
                                                    type="text"
                                                    name="nama_area"
                                                    class="form-control"
                                                    value="<?= e(
                                                        $area['nama_area']
                                                    ) ?>"
                                                    maxlength="100"
                                                    required
                                                >

                                            </div>


                                            <!-- LOKASI -->

                                            <div class="mb-3">

                                                <label class="form-label">
                                                    Lokasi
                                                </label>


                                                <input
                                                    type="text"
                                                    name="lokasi"
                                                    class="form-control"
                                                    value="<?= e(
                                                        $area['lokasi']
                                                    ) ?>"
                                                    maxlength="255"
                                                    placeholder="Contoh: Gedung Produksi 1"
                                                >

                                            </div>


                                            <!-- KETERANGAN -->

                                            <div>

                                                <label class="form-label">
                                                    Keterangan
                                                </label>


                                                <textarea
                                                    name="keterangan"
                                                    class="form-control"
                                                    rows="4"
                                                    placeholder="Tambahkan keterangan area..."
                                                ><?= e(
                                                    $area['keterangan']
                                                ) ?></textarea>

                                            </div>

                                        </div>


                                        <div class="modal-footer">

                                            <button
                                                type="button"
                                                class="btn btn-light"
                                                data-bs-dismiss="modal"
                                            >

                                                Batal

                                            </button>


                                            <button
                                                type="submit"
                                                class="btn btn-primary-custom"
                                            >

                                                <i class="bi bi-check-lg me-1"></i>

                                                Simpan Perubahan

                                            </button>

                                        </div>

                                    </form>

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             MODAL HAPUS
                        ================================================== -->

                        <div
                            class="modal fade"
                            id="modalHapus<?= (int)$area['id'] ?>"
                            tabindex="-1"
                            aria-hidden="true"
                        >

                            <div class="modal-dialog modal-dialog-centered">

                                <div class="modal-content">


                                    <form method="POST">


                                        <input
                                            type="hidden"
                                            name="action"
                                            value="hapus"
                                        >


                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$area['id'] ?>"
                                        >


                                        <div class="modal-header">

                                            <h5 class="modal-title">

                                                <i
                                                    class="bi bi-trash text-danger me-2"
                                                ></i>

                                                Hapus Area

                                            </h5>


                                            <button
                                                type="button"
                                                class="btn-close"
                                                data-bs-dismiss="modal"
                                            ></button>

                                        </div>


                                        <div class="modal-body text-center py-4">


                                            <div
                                                class="mx-auto mb-3"
                                                style="
                                                    width:60px;
                                                    height:60px;
                                                    border-radius:50%;
                                                    background:#fff1f2;
                                                    color:#dc3545;
                                                    display:flex;
                                                    align-items:center;
                                                    justify-content:center;
                                                    font-size:25px;
                                                "
                                            >

                                                <i class="bi bi-trash3"></i>

                                            </div>


                                            <h6 class="fw-bold mb-2">

                                                Hapus area ini?

                                            </h6>


                                            <p class="text-muted small mb-0">

                                                Data area

                                                <strong>

                                                    <?= e(
                                                        $area['nama_area']
                                                    ) ?>

                                                </strong>

                                                akan dihapus dari sistem.

                                            </p>


                                            <?php if (

                                                (int)$area['jumlah_jenis_mesin'] > 0

                                                ||

                                                (int)$area['jumlah_mesin'] > 0

                                            ): ?>


                                                <div
                                                    class="alert alert-warning mt-3 mb-0 small"
                                                >

                                                    <i
                                                        class="bi bi-exclamation-triangle me-1"
                                                    ></i>


                                                    Area ini masih digunakan oleh

                                                    <?= (int)(
                                                        $area['jumlah_jenis_mesin']
                                                    ) ?>

                                                    jenis mesin dan

                                                    <?= (int)(
                                                        $area['jumlah_mesin']
                                                    ) ?>

                                                    mesin.


                                                    Data tidak akan dihapus
                                                    sampai relasi tersebut dilepas.

                                                </div>


                                            <?php endif; ?>


                                        </div>


                                        <div
                                            class="modal-footer justify-content-center"
                                        >

                                            <button
                                                type="button"
                                                class="btn btn-light"
                                                data-bs-dismiss="modal"
                                            >

                                                Batal

                                            </button>


                                            <button
                                                type="submit"
                                                class="btn btn-danger"
                                            >

                                                <i class="bi bi-trash me-1"></i>

                                                Ya, Hapus

                                            </button>

                                        </div>

                                    </form>

                                </div>

                            </div>

                        </div>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td colspan="7">


                            <div class="empty-state">


                                <div class="empty-icon">

                                    <i class="bi bi-geo-alt"></i>

                                </div>


                                <h5>
                                    Belum Ada Data Area
                                </h5>


                                <p>

                                    Silakan tambahkan area pertama
                                    menggunakan tombol

                                    <strong>
                                        Tambah Area
                                    </strong>.

                                </p>

                            </div>


                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>

</main>


<!-- =========================================================
     MODAL TAMBAH AREA
========================================================== -->

<div
    class="modal fade"
    id="modalTambah"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <form method="POST">


                <input
                    type="hidden"
                    name="action"
                    value="tambah"
                >


                <div class="modal-header">

                    <h5 class="modal-title">

                        <i
                            class="bi bi-plus-circle-fill text-primary me-2"
                        ></i>

                        Tambah Area

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body">


                    <!-- NAMA AREA -->

                    <div class="mb-3">

                        <label class="form-label">

                            Nama Area

                            <span class="text-danger">
                                *
                            </span>

                        </label>


                        <input
                            type="text"
                            name="nama_area"
                            class="form-control"
                            maxlength="100"
                            placeholder="Contoh: Produksi"
                            required
                        >

                    </div>


                    <!-- LOKASI -->

                    <div class="mb-3">

                        <label class="form-label">
                            Lokasi
                        </label>


                        <input
                            type="text"
                            name="lokasi"
                            class="form-control"
                            maxlength="255"
                            placeholder="Contoh: Gedung Produksi 1"
                        >

                    </div>


                    <!-- KETERANGAN -->

                    <div>

                        <label class="form-label">
                            Keterangan
                        </label>


                        <textarea
                            name="keterangan"
                            class="form-control"
                            rows="4"
                            placeholder="Tambahkan keterangan area..."
                        ></textarea>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >

                        Batal

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary-custom"
                    >

                        <i class="bi bi-plus-lg me-1"></i>

                        Tambah Area

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =========================================================
     BOOTSTRAP JS
========================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>