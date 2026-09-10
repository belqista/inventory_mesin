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

if (
    strtolower(
        trim(
            $_SESSION['role'] ?? ''
        )
    ) !== 'admin'
) {

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
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   ACTIVE SIDEBAR
   WAJIB UNTUK admin/sidebar.php
========================================================= */

$active_menu = 'sub_mesin';


/* =========================================================
   DATA ADMIN
========================================================= */

$namaAdmin =
    $_SESSION['nama_lengkap']
    ?? $_SESSION['username']
    ?? 'Administrator';


/* =========================================================
   FLASH MESSAGE
========================================================= */

$success =
    $_SESSION['success']
    ?? '';

$error =
    $_SESSION['error']
    ?? '';

unset(
    $_SESSION['success'],
    $_SESSION['error']
);


/* =========================================================
   PROSES CRUD
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action =
        $_POST['action']
        ?? '';


    /* =====================================================
       TAMBAH SUB MESIN
    ====================================================== */

    if ($action === 'tambah') {

        $idMesin =
            intval(
                $_POST['id_mesin']
                ?? 0
            );

        $namaSubMesin =
            trim(
                $_POST['nama_sub_mesin']
                ?? ''
            );

        $serialNumber =
            trim(
                $_POST['serial_number']
                ?? ''
            );

        $keterangan =
            trim(
                $_POST['keterangan']
                ?? ''
            );


        if ($idMesin <= 0) {

            $_SESSION['error'] =
                'Mesin induk wajib dipilih.';

            header("Location: index.php");

            exit;
        }


        if ($namaSubMesin === '') {

            $_SESSION['error'] =
                'Nama sub mesin wajib diisi.';

            header("Location: index.php");

            exit;
        }


        /* =================================================
           CEK MESIN
        ================================================== */

        $stmtMesin =
            mysqli_prepare(
                $conn,
                "SELECT
                    m.id,
                    m.nama_mesin
                 FROM mesin m
                 WHERE m.id = ?
                 LIMIT 1"
            );


        mysqli_stmt_bind_param(
            $stmtMesin,
            "i",
            $idMesin
        );


        mysqli_stmt_execute(
            $stmtMesin
        );


        $resultMesin =
            mysqli_stmt_get_result(
                $stmtMesin
            );


        if (
            mysqli_num_rows(
                $resultMesin
            ) === 0
        ) {

            mysqli_stmt_close(
                $stmtMesin
            );

            $_SESSION['error'] =
                'Mesin yang dipilih tidak ditemukan.';

            header("Location: index.php");

            exit;
        }


        mysqli_stmt_close(
            $stmtMesin
        );


        /* =================================================
           CEK DUPLIKAT NAMA
        ================================================== */

        $stmtCek =
            mysqli_prepare(
                $conn,
                "SELECT id
                 FROM sub_mesin
                 WHERE id_mesin = ?
                 AND LOWER(
                     TRIM(nama_sub_mesin)
                 )
                 =
                 LOWER(
                     TRIM(?)
                 )
                 LIMIT 1"
            );


        mysqli_stmt_bind_param(
            $stmtCek,
            "is",
            $idMesin,
            $namaSubMesin
        );


        mysqli_stmt_execute(
            $stmtCek
        );


        $resultCek =
            mysqli_stmt_get_result(
                $stmtCek
            );


        if (
            mysqli_num_rows(
                $resultCek
            ) > 0
        ) {

            mysqli_stmt_close(
                $stmtCek
            );

            $_SESSION['error'] =
                'Nama sub mesin tersebut sudah ada pada mesin yang dipilih.';

            header("Location: index.php");

            exit;
        }


        mysqli_stmt_close(
            $stmtCek
        );


        /* =================================================
           INSERT
        ================================================== */

        $stmt =
            mysqli_prepare(
                $conn,
                "INSERT INTO sub_mesin
                (
                    id_mesin,
                    nama_sub_mesin,
                    serial_number,
                    keterangan
                )
                VALUES
                (?, ?, ?, ?)"
            );


        mysqli_stmt_bind_param(
            $stmt,
            "isss",
            $idMesin,
            $namaSubMesin,
            $serialNumber,
            $keterangan
        );


        if (
            mysqli_stmt_execute(
                $stmt
            )
        ) {

            $_SESSION['success'] =
                'Sub mesin berhasil ditambahkan.';

        } else {

            $_SESSION['error'] =
                'Sub mesin gagal ditambahkan.';
        }


        mysqli_stmt_close(
            $stmt
        );


        header("Location: index.php");

        exit;
    }


    /* =====================================================
       EDIT SUB MESIN
    ====================================================== */

    if ($action === 'edit') {

        $id =
            intval(
                $_POST['id']
                ?? 0
            );

        $idMesin =
            intval(
                $_POST['id_mesin']
                ?? 0
            );

        $namaSubMesin =
            trim(
                $_POST['nama_sub_mesin']
                ?? ''
            );

        $serialNumber =
            trim(
                $_POST['serial_number']
                ?? ''
            );

        $keterangan =
            trim(
                $_POST['keterangan']
                ?? ''
            );


        if ($id <= 0) {

            $_SESSION['error'] =
                'Data sub mesin tidak valid.';

            header("Location: index.php");

            exit;
        }


        if ($idMesin <= 0) {

            $_SESSION['error'] =
                'Mesin induk wajib dipilih.';

            header("Location: index.php");

            exit;
        }


        if ($namaSubMesin === '') {

            $_SESSION['error'] =
                'Nama sub mesin wajib diisi.';

            header("Location: index.php");

            exit;
        }


        /* =================================================
           CEK MESIN
        ================================================== */

        $stmtMesin =
            mysqli_prepare(
                $conn,
                "SELECT id
                 FROM mesin
                 WHERE id = ?
                 LIMIT 1"
            );


        mysqli_stmt_bind_param(
            $stmtMesin,
            "i",
            $idMesin
        );


        mysqli_stmt_execute(
            $stmtMesin
        );


        $resultMesin =
            mysqli_stmt_get_result(
                $stmtMesin
            );


        if (
            mysqli_num_rows(
                $resultMesin
            ) === 0
        ) {

            mysqli_stmt_close(
                $stmtMesin
            );

            $_SESSION['error'] =
                'Mesin yang dipilih tidak ditemukan.';

            header("Location: index.php");

            exit;
        }


        mysqli_stmt_close(
            $stmtMesin
        );


        /* =================================================
           CEK DUPLIKAT
        ================================================== */

        $stmtCek =
            mysqli_prepare(
                $conn,
                "SELECT id
                 FROM sub_mesin
                 WHERE id_mesin = ?
                 AND LOWER(
                     TRIM(nama_sub_mesin)
                 )
                 =
                 LOWER(
                     TRIM(?)
                 )
                 AND id <> ?
                 LIMIT 1"
            );


        mysqli_stmt_bind_param(
            $stmtCek,
            "isi",
            $idMesin,
            $namaSubMesin,
            $id
        );


        mysqli_stmt_execute(
            $stmtCek
        );


        $resultCek =
            mysqli_stmt_get_result(
                $stmtCek
            );


        if (
            mysqli_num_rows(
                $resultCek
            ) > 0
        ) {

            mysqli_stmt_close(
                $stmtCek
            );

            $_SESSION['error'] =
                'Nama sub mesin tersebut sudah ada pada mesin yang dipilih.';

            header("Location: index.php");

            exit;
        }


        mysqli_stmt_close(
            $stmtCek
        );


        /* =================================================
           UPDATE
        ================================================== */

        $stmt =
            mysqli_prepare(
                $conn,
                "UPDATE sub_mesin
                 SET
                    id_mesin = ?,
                    nama_sub_mesin = ?,
                    serial_number = ?,
                    keterangan = ?
                 WHERE id = ?"
            );


        mysqli_stmt_bind_param(
            $stmt,
            "isssi",
            $idMesin,
            $namaSubMesin,
            $serialNumber,
            $keterangan,
            $id
        );


        if (
            mysqli_stmt_execute(
                $stmt
            )
        ) {

            $_SESSION['success'] =
                'Sub mesin berhasil diperbarui.';

        } else {

            $_SESSION['error'] =
                'Sub mesin gagal diperbarui.';
        }


        mysqli_stmt_close(
            $stmt
        );


        header("Location: index.php");

        exit;
    }


    /* =====================================================
       HAPUS SUB MESIN
    ====================================================== */

    if ($action === 'hapus') {

        $id =
            intval(
                $_POST['id']
                ?? 0
            );


        if ($id <= 0) {

            $_SESSION['error'] =
                'Data sub mesin tidak valid.';

            header("Location: index.php");

            exit;
        }


        /* =================================================
           CEK KOMPONEN
        ================================================== */

        $stmtCek =
            mysqli_prepare(
                $conn,
                "SELECT
                    COUNT(*) AS jumlah
                 FROM komponen
                 WHERE id_sub_mesin = ?"
            );


        mysqli_stmt_bind_param(
            $stmtCek,
            "i",
            $id
        );


        mysqli_stmt_execute(
            $stmtCek
        );


        $resultCek =
            mysqli_stmt_get_result(
                $stmtCek
            );


        $dataCek =
            mysqli_fetch_assoc(
                $resultCek
            );


        mysqli_stmt_close(
            $stmtCek
        );


        $jumlahKomponen =
            (int)(
                $dataCek['jumlah']
                ?? 0
            );


        if ($jumlahKomponen > 0) {

            $_SESSION['error'] =
                'Sub mesin tidak dapat dihapus karena masih digunakan oleh '
                . $jumlahKomponen
                . ' komponen.';

            header("Location: index.php");

            exit;
        }


        /* =================================================
           DELETE
        ================================================== */

        $stmt =
            mysqli_prepare(
                $conn,
                "DELETE FROM sub_mesin
                 WHERE id = ?"
            );


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $id
        );


        if (
            mysqli_stmt_execute(
                $stmt
            )
        ) {

            $_SESSION['success'] =
                'Sub mesin berhasil dihapus.';

        } else {

            $_SESSION['error'] =
                'Sub mesin gagal dihapus.';
        }


        mysqli_stmt_close(
            $stmt
        );


        header("Location: index.php");

        exit;
    }
}


/* =========================================================
   FILTER
========================================================= */

$search =
    trim(
        $_GET['search']
        ?? ''
    );


$idMesinFilter =
    intval(
        $_GET['id_mesin']
        ?? 0
    );


/* =========================================================
   DATA MESIN DROPDOWN
========================================================= */

$mesinList = [];


$qMesin =
    mysqli_query(
        $conn,
        "SELECT
            m.id,
            m.nama_mesin,
            m.serial_number,
            ab.nama_area,
            jm.nama_jenis_mesin
         FROM mesin m
         LEFT JOIN area_bagian ab
            ON ab.id = m.id_area
         LEFT JOIN jenis_mesin jm
            ON jm.id = m.id_jenis_mesin
         ORDER BY
            m.nama_mesin ASC"
    );


if ($qMesin) {

    while (
        $row =
            mysqli_fetch_assoc(
                $qMesin
            )
    ) {

        $mesinList[] =
            $row;
    }
}


/* =========================================================
   FILTER WHERE
========================================================= */

$where = [];


if ($search !== '') {

    $safeSearch =
        mysqli_real_escape_string(
            $conn,
            $search
        );


    $where[] = "
        (
            sm.nama_sub_mesin
                LIKE '%$safeSearch%'

            OR sm.serial_number
                LIKE '%$safeSearch%'

            OR sm.keterangan
                LIKE '%$safeSearch%'

            OR m.nama_mesin
                LIKE '%$safeSearch%'

            OR ab.nama_area
                LIKE '%$safeSearch%'

            OR jm.nama_jenis_mesin
                LIKE '%$safeSearch%'
        )
    ";
}


if ($idMesinFilter > 0) {

    $where[] =
        "sm.id_mesin = "
        . $idMesinFilter;
}


$whereSQL = '';


if (!empty($where)) {

    $whereSQL =
        "WHERE "
        . implode(
            " AND ",
            $where
        );
}


/* =========================================================
   STATISTIK
========================================================= */


/* TOTAL SUB MESIN */

$totalSubMesin = 0;


$qTotalSub =
    mysqli_query(
        $conn,
        "SELECT
            COUNT(*) AS jumlah
         FROM sub_mesin"
    );


if ($qTotalSub) {

    $row =
        mysqli_fetch_assoc(
            $qTotalSub
        );


    $totalSubMesin =
        (int)(
            $row['jumlah']
            ?? 0
        );
}


/* TOTAL MESIN */

$totalMesin = 0;


$qTotalMesin =
    mysqli_query(
        $conn,
        "SELECT
            COUNT(*) AS jumlah
         FROM mesin"
    );


if ($qTotalMesin) {

    $row =
        mysqli_fetch_assoc(
            $qTotalMesin
        );


    $totalMesin =
        (int)(
            $row['jumlah']
            ?? 0
        );
}


/* TOTAL AREA */

$totalArea = 0;


$qTotalArea =
    mysqli_query(
        $conn,
        "SELECT
            COUNT(*) AS jumlah
         FROM area_bagian"
    );


if ($qTotalArea) {

    $row =
        mysqli_fetch_assoc(
            $qTotalArea
        );


    $totalArea =
        (int)(
            $row['jumlah']
            ?? 0
        );
}


/* TOTAL KOMPONEN */

$totalKomponen = 0;


$qTotalKomponen =
    mysqli_query(
        $conn,
        "SELECT
            COUNT(*) AS jumlah
         FROM komponen"
    );


if ($qTotalKomponen) {

    $row =
        mysqli_fetch_assoc(
            $qTotalKomponen
        );


    $totalKomponen =
        (int)(
            $row['jumlah']
            ?? 0
        );
}


/* =========================================================
   DATA SUB MESIN
========================================================= */

$subMesinList = [];


$sqlSubMesin = "
    SELECT
        sm.id,
        sm.id_mesin,
        sm.nama_sub_mesin,
        sm.serial_number,
        sm.keterangan,

        m.nama_mesin,
        m.serial_number AS serial_mesin,
        m.lokasi AS lokasi_mesin,

        ab.nama_area,

        jm.nama_jenis_mesin,

        COUNT(k.id) AS jumlah_komponen

    FROM sub_mesin sm

    LEFT JOIN mesin m
        ON m.id = sm.id_mesin

    LEFT JOIN area_bagian ab
        ON ab.id = m.id_area

    LEFT JOIN jenis_mesin jm
        ON jm.id = m.id_jenis_mesin

    LEFT JOIN komponen k
        ON k.id_sub_mesin = sm.id

    $whereSQL

    GROUP BY
        sm.id,
        sm.id_mesin,
        sm.nama_sub_mesin,
        sm.serial_number,
        sm.keterangan,
        m.nama_mesin,
        m.serial_number,
        m.lokasi,
        ab.nama_area,
        jm.nama_jenis_mesin

    ORDER BY
        sm.nama_sub_mesin ASC
";


$qSubMesin =
    mysqli_query(
        $conn,
        $sqlSubMesin
    );


if ($qSubMesin) {

    while (
        $row =
            mysqli_fetch_assoc(
                $qSubMesin
            )
    ) {

        $subMesinList[] =
            $row;
    }
}


$jumlahDitampilkan =
    count(
        $subMesinList
    );


/* =========================================================
   FILTER AKTIF
========================================================= */

$filterAktif = 0;


if ($search !== '') {

    $filterAktif++;
}


if ($idMesinFilter > 0) {

    $filterAktif++;
}


/* =========================================================
   MESIN TERPILIH
========================================================= */

$namaMesinTerpilih = '';


foreach (
    $mesinList
    as $mesin
) {

    if (
        (int)$mesin['id']
        === $idMesinFilter
    ) {

        $namaMesinTerpilih =
            $mesin['nama_mesin'];

        break;
    }
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
        Sub Mesin | Admin
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
           SIDEBAR DITANGANI OLEH admin/sidebar.php
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

            background: rgba(
                255,
                255,
                255,
                .95
            );

            backdrop-filter: blur(10px);

            border-bottom:
                1px solid
                var(--border);

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

            background:
                var(--primary-light);

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

            padding:
                28px 30px 40px;
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

            justify-content: center;

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

            background:
                var(--primary-dark);

            color: #fff;

            transform:
                translateY(-1px);
        }


        /* =====================================================
           STAT CARD
        ====================================================== */

        .stat-card {

            height: 100%;

            background: var(--white);

            border:
                1px solid
                var(--border);

            border-radius: 14px;

            padding: 18px;

            display: flex;

            align-items: center;

            gap: 14px;

            box-shadow:
                0 3px 12px
                rgba(
                    20,
                    40,
                    80,
                    .035
                );
        }


        .stat-icon {

            flex: 0 0 45px;

            width: 45px;

            height: 45px;

            border-radius: 11px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                var(--primary-light);

            color:
                var(--primary);

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
           ALERT
        ====================================================== */

        .alert-custom {

            border: 0;

            border-radius: 11px;

            font-size: 11px;

            margin-top: 20px;

            margin-bottom: 0;
        }


        /* =====================================================
           FILTER
        ====================================================== */

        .filter-card {

            background: var(--white);

            border:
                1px solid
                var(--border);

            border-radius: 14px;

            padding: 20px;

            margin-top: 24px;

            box-shadow:
                0 3px 12px
                rgba(
                    20,
                    40,
                    80,
                    .035
                );
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

            background:
                var(--primary-light);

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

            border:
                1px solid
                var(--border);

            border-radius: 8px;

            font-size: 12px;

            box-shadow: none !important;
        }


        .form-control:focus,
        .form-select:focus {

            border-color:
                var(--primary);
        }


        .btn-filter {

            min-height: 42px;

            width: 100%;

            border: 0;

            border-radius: 8px;

            background:
                var(--primary);

            color: #fff;

            font-size: 11px;

            font-weight: 600;
        }


        .btn-filter:hover {

            background:
                var(--primary-dark);

            color: #fff;
        }


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

            border:
                1px solid
                #dce9f7;

            color: var(--primary);

            border-radius: 20px;

            font-size: 10px;

            font-weight: 500;
        }


        /* =====================================================
           SECTION HEADER
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
           SUB MESIN CARD
        ====================================================== */

        .sub-card {

            height: 100%;

            background: var(--white);

            border:
                1px solid
                var(--border);

            border-radius: 14px;

            padding: 18px;

            box-shadow:
                0 3px 12px
                rgba(
                    20,
                    40,
                    80,
                    .035
                );

            transition: .2s ease;
        }


        .sub-card:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 8px 22px
                rgba(
                    20,
                    40,
                    80,
                    .08
                );

            border-color:
                #dce5f0;
        }


        .sub-top {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 12px;
        }


        .sub-icon {

            width: 45px;

            height: 45px;

            flex: 0 0 45px;

            border-radius: 11px;

            background:
                var(--primary-light);

            color:
                var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 20px;
        }


        .component-count {

            flex-shrink: 0;

            display: inline-flex;

            align-items: center;

            gap: 4px;

            padding: 5px 8px;

            border-radius: 20px;

            background: #f4f7fa;

            color: #687282;

            font-size: 9px;

            font-weight: 600;
        }


        .sub-name {

            margin: 0;

            color: var(--text);

            font-size: 14px;

            font-weight: 700;

            line-height: 1.4;
        }


        .sub-machine {

            margin-top: 4px;

            color:
                var(--primary);

            font-size: 10px;

            font-weight: 600;
        }


        .sub-area {

            margin-top: 3px;

            color:
                var(--muted);

            font-size: 10px;
        }


        .sub-info {

            margin-top: 17px;

            padding: 13px;

            border-radius: 10px;

            background: #f8fafc;

            border:
                1px solid
                #edf0f4;
        }


        .info-row {

            display: flex;

            align-items: flex-start;

            gap: 9px;

            margin-bottom: 10px;
        }


        .info-row:last-child {

            margin-bottom: 0;
        }


        .info-icon {

            color: #8c96a5;

            width: 15px;

            flex-shrink: 0;

            text-align: center;

            font-size: 12px;
        }


        .info-content {

            min-width: 0;

            flex: 1;
        }


        .info-label {

            color: #9aa2af;

            font-size: 9px;

            margin-bottom: 2px;
        }


        .info-value {

            color: #4b5565;

            font-size: 10px;

            font-weight: 600;

            word-break: break-word;
        }


        .sub-actions {

            display: flex;

            gap: 7px;

            margin-top: 14px;
        }


        .action-btn {

            min-height: 35px;

            border-radius: 8px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            gap: 5px;

            font-size: 10px;

            font-weight: 600;

            transition: .2s ease;
        }


        .action-detail {

            flex: 1;

            border:
                1px solid
                #dce9f7;

            background: #f4f8fd;

            color:
                var(--primary);
        }


        .action-detail:hover {

            background:
                var(--primary-light);

            color:
                var(--primary);
        }


        .action-edit {

            flex: 1;

            border:
                1px solid
                #e8e9ec;

            background: #fff;

            color: #596273;
        }


        .action-edit:hover {

            background: #f5f6f8;

            color: #3f4754;
        }


        .action-delete {

            flex: 0 0 35px;

            border:
                1px solid
                #f3d5d9;

            background: #fff6f7;

            color:
                var(--danger);
        }


        .action-delete:hover {

            background: #ffecef;

            color:
                var(--danger);
        }


        /* =====================================================
           EMPTY
        ====================================================== */

        .empty-card {

            background: var(--white);

            border:
                1px dashed
                #d8dee8;

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
           MODAL
        ====================================================== */

        .modal-content {

            border: 0;

            border-radius: 14px;

            overflow: hidden;
        }


        .modal-header {

            padding: 18px 20px;

            border-bottom:
                1px solid
                var(--border);
        }


        .modal-title {

            font-size: 15px;

            font-weight: 700;
        }


        .modal-body {

            padding: 20px;
        }


        .modal-footer {

            padding: 15px 20px;

            border-top:
                1px solid
                var(--border);
        }


        .detail-item {

            padding: 12px 0;

            border-bottom:
                1px solid
                #f0f2f5;
        }


        .detail-item:last-child {

            border-bottom: 0;
        }


        .detail-label {

            color: #9aa2af;

            font-size: 10px;

            margin-bottom: 3px;
        }


        .detail-value {

            color: var(--text);

            font-size: 12px;

            font-weight: 600;

            word-break: break-word;
        }


        /* =====================================================
           FOOTER
        ====================================================== */

        .footer {

            padding: 20px 30px;

            border-top:
                1px solid
                var(--border);

            color: var(--muted);

            font-size: 10px;

            text-align: center;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 991.98px) {

            .main-wrapper {

                margin-left: 0;
            }


            .topbar {

                padding: 0 20px;
            }


            .content {

                padding:
                    24px 20px 35px;
            }


            .footer {

                padding:
                    18px 20px;
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

                padding:
                    20px 15px 30px;
            }


            .page-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;
            }


            .page-header
            .btn-primary-custom {

                width: 100%;
            }


            .filter-card {

                padding: 15px;
            }


            .section-header {

                align-items:
                    flex-start;

                flex-direction:
                    column;

                gap: 4px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
     
     SATU-SATUNYA SIDEBAR:
     inventory_mesin/admin/sidebar.php
========================================================= -->

<?php

include "../sidebar.php";

?>


<!-- =========================================================
     MAIN WRAPPER
========================================================= -->

<div class="main-wrapper">


    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <header class="topbar">


        <div
            class="d-flex align-items-center gap-3"
        >


            <!--

                Tombol sidebar mobile
                mengikuti admin/sidebar.php

            -->

            <button
                type="button"
                class="sidebar-toggle"
                id="mobileToggle"
                aria-label="Buka menu"
            >

                <i class="bi bi-list"></i>

            </button>


            <div>

                <h1 class="page-title">

                    Sub Mesin

                </h1>


                <p class="page-subtitle">

                    Kelola bagian atau unit mesin
                    yang lebih spesifik

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

            <nav
                aria-label="breadcrumb"
            >

                <ol class="breadcrumb">


                    <li class="breadcrumb-item">

                        <a href="../index.php">

                            Dashboard Admin

                        </a>

                    </li>


                    <li class="breadcrumb-item active">

                        Sub Mesin

                    </li>


                </ol>

            </nav>

        </div>


        <!-- PAGE HEADER -->

        <div class="page-header">


            <div class="page-header-left">

                <h2>

                    Sub Mesin

                </h2>


                <p>

                    Kelola sub mesin yang berada
                    di dalam setiap mesin.

                </p>

            </div>


            <button
                type="button"
                class="btn-primary-custom"
                data-bs-toggle="modal"
                data-bs-target="#modalTambah"
            >

                <i class="bi bi-plus-lg"></i>

                Tambah Sub Mesin

            </button>

        </div>


        <!-- =================================================
             FLASH MESSAGE
        ================================================== -->

        <?php if ($success !== ''): ?>

            <div
                class="alert alert-success alert-custom alert-dismissible fade show"
                role="alert"
            >

                <i class="bi bi-check-circle me-1"></i>

                <?= e($success) ?>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                >
                </button>

            </div>

        <?php endif; ?>


        <?php if ($error !== ''): ?>

            <div
                class="alert alert-danger alert-custom alert-dismissible fade show"
                role="alert"
            >

                <i class="bi bi-exclamation-circle me-1"></i>

                <?= e($error) ?>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                >
                </button>

            </div>

        <?php endif; ?>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="row g-3 mt-1">


            <!-- SUB MESIN -->

            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">

                        <i class="bi bi-diagram-3"></i>

                    </div>


                    <div>

                        <div class="stat-label">

                            Sub Mesin

                        </div>


                        <div class="stat-value">

                            <?= number_format($totalSubMesin) ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- MESIN -->

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


            <!-- AREA -->

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


            <!-- KOMPONEN -->

            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon">

                        <i class="bi bi-box-seam"></i>

                    </div>


                    <div>

                        <div class="stat-label">

                            Total Komponen

                        </div>


                        <div class="stat-value">

                            <?= number_format($totalKomponen) ?>

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

                    Filter Sub Mesin

                </div>


                <?php if ($filterAktif > 0): ?>

                    <div class="filter-count">

                        <?= $filterAktif ?>

                        filter aktif

                    </div>

                <?php endif; ?>


            </div>


            <form
                method="GET"
                action="index.php"
            >


                <div class="row g-3">


                    <!-- SEARCH -->

                    <div class="col-lg-6">

                        <label
                            class="form-label-custom"
                        >

                            Cari Sub Mesin

                        </label>


                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="<?= e($search) ?>"
                            placeholder="Nama sub mesin, serial number, mesin, area..."
                        >

                    </div>


                    <!-- MESIN -->

                    <div class="col-lg-4">

                        <label
                            class="form-label-custom"
                        >

                            Mesin

                        </label>


                        <select
                            name="id_mesin"
                            class="form-select"
                        >


                            <option value="">

                                Semua Mesin

                            </option>


                            <?php foreach (
                                $mesinList
                                as $mesin
                            ): ?>

                                <option
                                    value="<?= (int)$mesin['id'] ?>"
                                    <?= (
                                        $idMesinFilter
                                        ===
                                        (int)$mesin['id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= e(
                                        $mesin['nama_mesin']
                                    ) ?>


                                    <?php if (
                                        !empty(
                                            $mesin['nama_area']
                                        )
                                    ): ?>

                                        —

                                        <?= e(
                                            $mesin['nama_area']
                                        ) ?>

                                    <?php endif; ?>

                                </option>

                            <?php endforeach; ?>


                        </select>

                    </div>


                    <!-- BUTTON -->

                    <div class="col-lg-2">

                        <label
                            class="form-label-custom d-none d-lg-block"
                        >

                            &nbsp;

                        </label>


                        <button
                            type="submit"
                            class="btn-filter"
                        >

                            <i class="bi bi-search me-1"></i>

                            Terapkan

                        </button>

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


                        <?php if (
                            $namaMesinTerpilih
                            !== ''
                        ): ?>

                            <span class="filter-badge">

                                <i class="bi bi-gear"></i>

                                Mesin:

                                <?= e(
                                    $namaMesinTerpilih
                                ) ?>

                            </span>

                        <?php endif; ?>


                        <a
                            href="index.php"
                            class="filter-badge"
                        >

                            <i class="bi bi-x-circle"></i>

                            Reset Filter

                        </a>


                    </div>

                <?php endif; ?>


            </form>

        </div>


        <!-- =================================================
             SECTION HEADER
        ================================================== -->

        <div class="section-header">


            <h3 class="section-title">

                Sub Mesin Terdaftar

            </h3>


            <div class="section-result">

                Menampilkan

                <strong>

                    <?= number_format(
                        $jumlahDitampilkan
                    ) ?>

                </strong>

                sub mesin


                <?php if ($filterAktif > 0): ?>

                    dari

                    <strong>

                        <?= number_format(
                            $totalSubMesin
                        ) ?>

                    </strong>

                    total

                <?php endif; ?>


            </div>

        </div>


        <!-- =================================================
             LIST SUB MESIN
        ================================================== -->

        <?php if (
            !empty(
                $subMesinList
            )
        ): ?>


            <div class="row g-3">


                <?php foreach (
                    $subMesinList
                    as $item
                ): ?>


                    <?php

                    $namaSubMesin =
                        trim(
                            $item[
                                'nama_sub_mesin'
                            ]
                            ?? ''
                        );


                    $namaMesin =
                        trim(
                            $item[
                                'nama_mesin'
                            ]
                            ?? ''
                        );


                    $namaArea =
                        trim(
                            $item[
                                'nama_area'
                            ]
                            ?? ''
                        );


                    $namaJenis =
                        trim(
                            $item[
                                'nama_jenis_mesin'
                            ]
                            ?? ''
                        );


                    $serialSub =
                        trim(
                            $item[
                                'serial_number'
                            ]
                            ?? ''
                        );


                    $serialMesin =
                        trim(
                            $item[
                                'serial_mesin'
                            ]
                            ?? ''
                        );


                    $lokasiMesin =
                        trim(
                            $item[
                                'lokasi_mesin'
                            ]
                            ?? ''
                        );


                    $keterangan =
                        trim(
                            $item[
                                'keterangan'
                            ]
                            ?? ''
                        );


                    $jumlahKomponen =
                        (int)(
                            $item[
                                'jumlah_komponen'
                            ]
                            ?? 0
                        );

                    ?>


                    <div
                        class="col-md-6 col-xl-4 col-xxl-3"
                    >


                        <div class="sub-card">


                            <!-- TOP -->

                            <div class="sub-top">


                                <div class="sub-icon">

                                    <i class="bi bi-diagram-3"></i>

                                </div>


                                <div class="component-count">

                                    <i class="bi bi-box-seam"></i>

                                    <?= number_format(
                                        $jumlahKomponen
                                    ) ?>

                                    Komponen

                                </div>

                            </div>


                            <!-- NAME -->

                            <div class="mt-3">


                                <h4 class="sub-name">

                                    <?= e(
                                        $namaSubMesin
                                        ?: 'Sub Mesin'
                                    ) ?>

                                </h4>


                                <div class="sub-machine">

                                    <i
                                        class="bi bi-gear me-1"
                                    ></i>

                                    <?= e(
                                        $namaMesin
                                        ?: 'Mesin belum diatur'
                                    ) ?>

                                </div>


                                <div class="sub-area">

                                    <i
                                        class="bi bi-geo-alt me-1"
                                    ></i>

                                    <?= e(
                                        $namaArea
                                        ?: 'Area belum diatur'
                                    ) ?>

                                </div>

                            </div>


                            <!-- INFO -->

                            <div class="sub-info">


                                <!-- JENIS MESIN -->

                                <div class="info-row">

                                    <div class="info-icon">

                                        <i
                                            class="bi bi-cpu"
                                        ></i>

                                    </div>


                                    <div class="info-content">

                                        <div class="info-label">

                                            JENIS MESIN

                                        </div>


                                        <div class="info-value">

                                            <?= e(
                                                $namaJenis
                                                ?: '-'
                                            ) ?>

                                        </div>

                                    </div>

                                </div>


                                <!-- SERIAL -->

                                <div class="info-row">

                                    <div class="info-icon">

                                        <i
                                            class="bi bi-upc-scan"
                                        ></i>

                                    </div>


                                    <div class="info-content">

                                        <div class="info-label">

                                            SERIAL NUMBER

                                        </div>


                                        <div class="info-value">

                                            <?= e(
                                                $serialSub
                                                ?: '-'
                                            ) ?>

                                        </div>

                                    </div>

                                </div>


                                <!-- LOKASI -->

                                <div class="info-row">

                                    <div class="info-icon">

                                        <i
                                            class="bi bi-pin-map"
                                        ></i>

                                    </div>


                                    <div class="info-content">

                                        <div class="info-label">

                                            LOKASI MESIN

                                        </div>


                                        <div class="info-value">

                                            <?= e(
                                                $lokasiMesin
                                                ?: '-'
                                            ) ?>

                                        </div>

                                    </div>

                                </div>


                            </div>


                            <!-- ACTION -->

                            <div class="sub-actions">


                                <!-- DETAIL -->

                                <button
                                    type="button"
                                    class="action-btn action-detail"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalDetail"

                                    data-nama="<?= e(
                                        $namaSubMesin
                                    ) ?>"

                                    data-mesin="<?= e(
                                        $namaMesin
                                        ?: '-'
                                    ) ?>"

                                    data-area="<?= e(
                                        $namaArea
                                        ?: '-'
                                    ) ?>"

                                    data-jenis="<?= e(
                                        $namaJenis
                                        ?: '-'
                                    ) ?>"

                                    data-serial="<?= e(
                                        $serialSub
                                        ?: '-'
                                    ) ?>"

                                    data-serial-mesin="<?= e(
                                        $serialMesin
                                        ?: '-'
                                    ) ?>"

                                    data-lokasi="<?= e(
                                        $lokasiMesin
                                        ?: '-'
                                    ) ?>"

                                    data-komponen="<?= $jumlahKomponen ?>"

                                    data-keterangan="<?= e(
                                        $keterangan
                                        ?: '-'
                                    ) ?>"
                                >

                                    <i class="bi bi-eye"></i>

                                    Detail

                                </button>


                                <!-- EDIT -->

                                <button
                                    type="button"
                                    class="action-btn action-edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEdit"

                                    data-id="<?= (int)$item['id'] ?>"

                                    data-mesin="<?= (int)$item['id_mesin'] ?>"

                                    data-nama="<?= e(
                                        $namaSubMesin
                                    ) ?>"

                                    data-serial="<?= e(
                                        $serialSub
                                    ) ?>"

                                    data-keterangan="<?= e(
                                        $keterangan
                                    ) ?>"
                                >

                                    <i class="bi bi-pencil"></i>

                                    Edit

                                </button>


                                <!-- HAPUS -->

                                <button
                                    type="button"
                                    class="action-btn action-delete"
                                    title="Hapus"

                                    data-bs-toggle="modal"
                                    data-bs-target="#modalHapus"

                                    data-id="<?= (int)$item['id'] ?>"

                                    data-nama="<?= e(
                                        $namaSubMesin
                                    ) ?>"

                                    data-komponen="<?= $jumlahKomponen ?>"
                                >

                                    <i class="bi bi-trash3"></i>

                                </button>


                            </div>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php else: ?>


            <!-- EMPTY STATE -->

            <div class="empty-card">


                <div class="empty-icon">

                    <i class="bi bi-diagram-3"></i>

                </div>


                <div class="empty-title">

                    Sub mesin tidak ditemukan

                </div>


                <p class="empty-text">


                    <?php if (
                        $filterAktif > 0
                    ): ?>

                        Tidak ada sub mesin yang sesuai
                        dengan filter yang dipilih.

                    <?php else: ?>

                        Belum ada data sub mesin.

                    <?php endif; ?>


                </p>


                <?php if (
                    $filterAktif > 0
                ): ?>

                    <a
                        href="index.php"
                        class="btn-primary-custom mt-3"
                    >

                        <i
                            class="bi bi-arrow-counterclockwise"
                        ></i>

                        Reset Filter

                    </a>

                <?php else: ?>

                    <button
                        type="button"
                        class="btn-primary-custom mt-3"
                        data-bs-toggle="modal"
                        data-bs-target="#modalTambah"
                    >

                        <i class="bi bi-plus-lg"></i>

                        Tambah Sub Mesin

                    </button>

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
     MODAL TAMBAH
========================================================= -->

<div
    class="modal fade"
    id="modalTambah"
    tabindex="-1"
    aria-hidden="true"
>


    <div
        class="modal-dialog modal-dialog-centered modal-lg"
    >


        <div class="modal-content">


            <form
                method="POST"
                action="index.php"
            >


                <div class="modal-header">

                    <h5 class="modal-title">

                        <i
                            class="bi bi-plus-circle text-primary me-2"
                        ></i>

                        Tambah Sub Mesin

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    >
                    </button>

                </div>


                <div class="modal-body">


                    <input
                        type="hidden"
                        name="action"
                        value="tambah"
                    >


                    <div class="row g-3">


                        <!-- MESIN -->

                        <div class="col-md-6">


                            <label
                                class="form-label-custom"
                            >

                                Mesin Induk
                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <select
                                name="id_mesin"
                                class="form-select"
                                required
                            >


                                <option value="">

                                    Pilih Mesin

                                </option>


                                <?php foreach (
                                    $mesinList
                                    as $mesin
                                ): ?>

                                    <option
                                        value="<?= (int)$mesin['id'] ?>"
                                    >

                                        <?= e(
                                            $mesin['nama_mesin']
                                        ) ?>


                                        <?php if (
                                            !empty(
                                                $mesin['nama_area']
                                            )
                                        ): ?>

                                            —

                                            <?= e(
                                                $mesin['nama_area']
                                            ) ?>

                                        <?php endif; ?>

                                    </option>

                                <?php endforeach; ?>


                            </select>


                        </div>


                        <!-- NAMA -->

                        <div class="col-md-6">


                            <label
                                class="form-label-custom"
                            >

                                Nama Sub Mesin
                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                name="nama_sub_mesin"
                                class="form-control"
                                placeholder="Contoh: Conveyor"
                                required
                            >


                        </div>


                        <!-- SERIAL -->

                        <div class="col-md-6">


                            <label
                                class="form-label-custom"
                            >

                                Serial Number

                            </label>


                            <input
                                type="text"
                                name="serial_number"
                                class="form-control"
                                placeholder="Masukkan serial number"
                            >


                        </div>


                        <!-- KETERANGAN -->

                        <div class="col-12">


                            <label
                                class="form-label-custom"
                            >

                                Keterangan

                            </label>


                            <textarea
                                name="keterangan"
                                class="form-control"
                                rows="3"
                                placeholder="Keterangan sub mesin jika diperlukan"
                            ></textarea>


                        </div>


                    </div>


                </div>


                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-light btn-sm"
                        data-bs-dismiss="modal"
                    >

                        Batal

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary btn-sm px-3"
                    >

                        <i class="bi bi-check-lg me-1"></i>

                        Simpan

                    </button>


                </div>


            </form>


        </div>

    </div>

</div>


<!-- =========================================================
     MODAL DETAIL
========================================================= -->

<div
    class="modal fade"
    id="modalDetail"
    tabindex="-1"
    aria-hidden="true"
>


    <div
        class="modal-dialog modal-dialog-centered modal-lg"
    >


        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">

                    <i
                        class="bi bi-info-circle text-primary me-2"
                    ></i>

                    Detail Sub Mesin

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                >
                </button>

            </div>


            <div class="modal-body">


                <div class="row">


                    <div class="col-md-6">


                        <div class="detail-item">

                            <div class="detail-label">

                                NAMA SUB MESIN

                            </div>


                            <div
                                class="detail-value"
                                id="detailNama"
                            >

                                -

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">

                                MESIN INDUK

                            </div>


                            <div
                                class="detail-value"
                                id="detailMesin"
                            >

                                -

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">

                                JENIS MESIN

                            </div>


                            <div
                                class="detail-value"
                                id="detailJenis"
                            >

                                -

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">

                                AREA

                            </div>


                            <div
                                class="detail-value"
                                id="detailArea"
                            >

                                -

                            </div>

                        </div>


                    </div>


                    <div class="col-md-6">


                        <div class="detail-item">

                            <div class="detail-label">

                                SERIAL NUMBER SUB MESIN

                            </div>


                            <div
                                class="detail-value"
                                id="detailSerial"
                            >

                                -

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">

                                SERIAL NUMBER MESIN

                            </div>


                            <div
                                class="detail-value"
                                id="detailSerialMesin"
                            >

                                -

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">

                                LOKASI MESIN

                            </div>


                            <div
                                class="detail-value"
                                id="detailLokasi"
                            >

                                -

                            </div>

                        </div>


                        <div class="detail-item">

                            <div class="detail-label">

                                KOMPONEN TERDAFTAR

                            </div>


                            <div
                                class="detail-value"
                                id="detailKomponen"
                            >

                                -

                            </div>

                        </div>


                    </div>


                    <div class="col-12">


                        <div class="detail-item">

                            <div class="detail-label">

                                KETERANGAN

                            </div>


                            <div
                                class="detail-value"
                                id="detailKeterangan"
                            >

                                -

                            </div>

                        </div>


                    </div>


                </div>


            </div>


            <div class="modal-footer">


                <button
                    type="button"
                    class="btn btn-primary btn-sm px-3"
                    data-bs-dismiss="modal"
                >

                    Tutup

                </button>


            </div>


        </div>

    </div>

</div>


<!-- =========================================================
     MODAL EDIT
========================================================= -->

<div
    class="modal fade"
    id="modalEdit"
    tabindex="-1"
    aria-hidden="true"
>


    <div
        class="modal-dialog modal-dialog-centered modal-lg"
    >


        <div class="modal-content">


            <form
                method="POST"
                action="index.php"
            >


                <div class="modal-header">

                    <h5 class="modal-title">

                        <i
                            class="bi bi-pencil-square text-primary me-2"
                        ></i>

                        Edit Sub Mesin

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    >
                    </button>

                </div>


                <div class="modal-body">


                    <input
                        type="hidden"
                        name="action"
                        value="edit"
                    >


                    <input
                        type="hidden"
                        name="id"
                        id="editId"
                    >


                    <div class="row g-3">


                        <!-- MESIN -->

                        <div class="col-md-6">


                            <label
                                class="form-label-custom"
                            >

                                Mesin Induk
                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <select
                                name="id_mesin"
                                id="editMesin"
                                class="form-select"
                                required
                            >


                                <option value="">

                                    Pilih Mesin

                                </option>


                                <?php foreach (
                                    $mesinList
                                    as $mesin
                                ): ?>

                                    <option
                                        value="<?= (int)$mesin['id'] ?>"
                                    >

                                        <?= e(
                                            $mesin['nama_mesin']
                                        ) ?>


                                        <?php if (
                                            !empty(
                                                $mesin['nama_area']
                                            )
                                        ): ?>

                                            —

                                            <?= e(
                                                $mesin['nama_area']
                                            ) ?>

                                        <?php endif; ?>

                                    </option>

                                <?php endforeach; ?>


                            </select>


                        </div>


                        <!-- NAMA -->

                        <div class="col-md-6">


                            <label
                                class="form-label-custom"
                            >

                                Nama Sub Mesin
                                <span class="text-danger">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                name="nama_sub_mesin"
                                id="editNama"
                                class="form-control"
                                required
                            >


                        </div>


                        <!-- SERIAL -->

                        <div class="col-md-6">


                            <label
                                class="form-label-custom"
                            >

                                Serial Number

                            </label>


                            <input
                                type="text"
                                name="serial_number"
                                id="editSerial"
                                class="form-control"
                            >


                        </div>


                        <!-- KETERANGAN -->

                        <div class="col-12">


                            <label
                                class="form-label-custom"
                            >

                                Keterangan

                            </label>


                            <textarea
                                name="keterangan"
                                id="editKeterangan"
                                class="form-control"
                                rows="3"
                            ></textarea>


                        </div>


                    </div>


                </div>


                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-light btn-sm"
                        data-bs-dismiss="modal"
                    >

                        Batal

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary btn-sm px-3"
                    >

                        <i class="bi bi-check-lg me-1"></i>

                        Simpan Perubahan

                    </button>


                </div>


            </form>


        </div>

    </div>

</div>


<!-- =========================================================
     MODAL HAPUS
========================================================= -->

<div
    class="modal fade"
    id="modalHapus"
    tabindex="-1"
    aria-hidden="true"
>


    <div
        class="modal-dialog modal-dialog-centered"
    >


        <div class="modal-content">


            <form
                method="POST"
                action="index.php"
            >


                <div class="modal-header">

                    <h5 class="modal-title">

                        <i
                            class="bi bi-trash3 text-danger me-2"
                        ></i>

                        Hapus Sub Mesin

                    </h5>


                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    >
                    </button>

                </div>


                <div class="modal-body">


                    <input
                        type="hidden"
                        name="action"
                        value="hapus"
                    >


                    <input
                        type="hidden"
                        name="id"
                        id="hapusId"
                    >


                    <div class="text-center py-2">


                        <div
                            class="mx-auto mb-3"
                            style="
                                width:55px;
                                height:55px;
                                border-radius:50%;
                                background:#fff1f2;
                                color:#dc3545;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                font-size:23px;
                            "
                        >

                            <i class="bi bi-trash3"></i>

                        </div>


                        <h6
                            class="fw-semibold mb-2"
                            id="hapusNama"
                        >

                            -

                        </h6>


                        <p
                            class="text-muted mb-0"
                            style="font-size:11px;"
                            id="hapusInfo"
                        >

                            Apakah kamu yakin ingin
                            menghapus sub mesin ini?

                        </p>


                    </div>


                </div>


                <div class="modal-footer">


                    <button
                        type="button"
                        class="btn btn-light btn-sm"
                        data-bs-dismiss="modal"
                    >

                        Batal

                    </button>


                    <button
                        type="submit"
                        class="btn btn-danger btn-sm px-3"
                        id="btnHapusSubmit"
                    >

                        <i
                            class="bi bi-trash3 me-1"
                        ></i>

                        Hapus

                    </button>


                </div>


            </form>


        </div>

    </div>

</div>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


<script>

/* =========================================================
   MOBILE SIDEBAR
   Mengikuti admin/sidebar.php
========================================================= */

const mobileToggle =
    document.getElementById(
        'mobileToggle'
    );


if (mobileToggle) {

    mobileToggle.addEventListener(
        'click',
        function () {

            document.body.classList.toggle(
                'sidebar-mobile-open'
            );

        }
    );

}


/* =========================================================
   TUTUP SIDEBAR KETIKA MENU DIPILIH
========================================================= */

document
    .querySelectorAll(
        '.sidebar-menu a'
    )
    .forEach(
        function (item) {

            item.addEventListener(
                'click',
                function () {

                    if (
                        window.innerWidth
                        <= 991
                    ) {

                        document.body.classList.remove(
                            'sidebar-mobile-open'
                        );

                    }

                }
            );

        }
    );


/* =========================================================
   MODAL DETAIL
========================================================= */

const modalDetail =
    document.getElementById(
        'modalDetail'
    );


if (modalDetail) {

    modalDetail.addEventListener(
        'show.bs.modal',
        function (event) {

            const button =
                event.relatedTarget;


            document.getElementById(
                'detailNama'
            ).textContent =
                button.getAttribute(
                    'data-nama'
                )
                || '-';


            document.getElementById(
                'detailMesin'
            ).textContent =
                button.getAttribute(
                    'data-mesin'
                )
                || '-';


            document.getElementById(
                'detailJenis'
            ).textContent =
                button.getAttribute(
                    'data-jenis'
                )
                || '-';


            document.getElementById(
                'detailArea'
            ).textContent =
                button.getAttribute(
                    'data-area'
                )
                || '-';


            document.getElementById(
                'detailSerial'
            ).textContent =
                button.getAttribute(
                    'data-serial'
                )
                || '-';


            document.getElementById(
                'detailSerialMesin'
            ).textContent =
                button.getAttribute(
                    'data-serial-mesin'
                )
                || '-';


            document.getElementById(
                'detailLokasi'
            ).textContent =
                button.getAttribute(
                    'data-lokasi'
                )
                || '-';


            document.getElementById(
                'detailKomponen'
            ).textContent =
                (
                    button.getAttribute(
                        'data-komponen'
                    )
                    || '0'
                )
                + ' komponen';


            document.getElementById(
                'detailKeterangan'
            ).textContent =
                button.getAttribute(
                    'data-keterangan'
                )
                || '-';

        }
    );

}


/* =========================================================
   MODAL EDIT
========================================================= */

const modalEdit =
    document.getElementById(
        'modalEdit'
    );


if (modalEdit) {

    modalEdit.addEventListener(
        'show.bs.modal',
        function (event) {

            const button =
                event.relatedTarget;


            document.getElementById(
                'editId'
            ).value =
                button.getAttribute(
                    'data-id'
                )
                || '';


            document.getElementById(
                'editMesin'
            ).value =
                button.getAttribute(
                    'data-mesin'
                )
                || '';


            document.getElementById(
                'editNama'
            ).value =
                button.getAttribute(
                    'data-nama'
                )
                || '';


            document.getElementById(
                'editSerial'
            ).value =
                button.getAttribute(
                    'data-serial'
                )
                || '';


            document.getElementById(
                'editKeterangan'
            ).value =
                button.getAttribute(
                    'data-keterangan'
                )
                || '';

        }
    );

}


/* =========================================================
   MODAL HAPUS
========================================================= */

const modalHapus =
    document.getElementById(
        'modalHapus'
    );


if (modalHapus) {

    modalHapus.addEventListener(
        'show.bs.modal',
        function (event) {

            const button =
                event.relatedTarget;


            const id =
                button.getAttribute(
                    'data-id'
                )
                || '';


            const nama =
                button.getAttribute(
                    'data-nama'
                )
                || '-';


            const jumlahKomponen =
                parseInt(
                    button.getAttribute(
                        'data-komponen'
                    )
                    || '0'
                );


            document.getElementById(
                'hapusId'
            ).value =
                id;


            document.getElementById(
                'hapusNama'
            ).textContent =
                nama;


            const info =
                document.getElementById(
                    'hapusInfo'
                );


            const submit =
                document.getElementById(
                    'btnHapusSubmit'
                );


            if (
                jumlahKomponen > 0
            ) {

                info.innerHTML =
                    'Sub mesin ini masih digunakan oleh <strong>'
                    + jumlahKomponen
                    + '</strong> komponen dan tidak dapat dihapus.';


                submit.disabled = true;

            } else {

                info.textContent =
                    'Apakah kamu yakin ingin menghapus sub mesin ini?';


                submit.disabled = false;

            }

        }
    );

}

</script>


</body>

</html>