<?php

session_start();

require_once "../../koneksi.php";

date_default_timezone_set('Asia/Jakarta');


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

$user_id = (int) ($_SESSION['user_id'] ?? 0);

$stmt_auth = mysqli_prepare(
    $conn,
    "
    SELECT
        id,
        username,
        nama_lengkap,
        role,
        status
    FROM users
    WHERE id = ?
    LIMIT 1
    "
);

if (!$stmt_auth) {

    die("Terjadi kesalahan sistem.");

}

mysqli_stmt_bind_param(
    $stmt_auth,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt_auth);

$result_auth = mysqli_stmt_get_result($stmt_auth);

$current_user = mysqli_fetch_assoc($result_auth);

mysqli_stmt_close($stmt_auth);


if (!$current_user) {

    $_SESSION = [];

    header("Location: ../../login.php");
    exit;

}


if (
    strtolower(
        trim(
            $current_user['role'] ?? ''
        )
    ) !== 'admin'
) {

    header("Location: ../../dashboard/index.php");
    exit;

}


/* =========================================================
   SIDEBAR ADMIN
   100% menggunakan admin/sidebar.php
========================================================= */

$active_menu = 'users';


/* =========================================================
   HELPER
========================================================= */

function e($value)
{

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );

}


/* =========================================================
   BADGE ROLE
========================================================= */

function roleBadge($role)
{

    $role = strtolower(
        trim(
            (string) $role
        )
    );


    if ($role === 'admin') {

        return '
            <span class="badge-custom badge-admin">
                <i class="bi bi-shield-fill-check"></i>
                Admin
            </span>
        ';

    }


    return '
        <span class="badge-custom badge-user">
            <i class="bi bi-person-fill"></i>
            User
        </span>
    ';

}


/* =========================================================
   BADGE STATUS
========================================================= */

function statusBadge($status)
{

    $status = strtolower(
        trim(
            (string) $status
        )
    );


    if ($status === 'aktif') {

        return '
            <span class="badge-custom badge-active">
                <i class="bi bi-check-circle-fill"></i>
                Aktif
            </span>
        ';

    }


    return '
        <span class="badge-custom badge-inactive">
            <i class="bi bi-x-circle-fill"></i>
            Nonaktif
        </span>
    ';

}


/* =========================================================
   HITUNG USER
========================================================= */

function getUserCount($conn, $where = '')
{

    $sql = "
        SELECT COUNT(*) AS total
        FROM users
        $where
    ";


    $result = mysqli_query(
        $conn,
        $sql
    );


    if (!$result) {

        return 0;

    }


    $row = mysqli_fetch_assoc($result);


    return (int) (
        $row['total'] ?? 0
    );

}


/* =========================================================
   FLASH MESSAGE
========================================================= */

$success = $_SESSION['user_success'] ?? '';
$error   = $_SESSION['user_error'] ?? '';

unset($_SESSION['user_success']);
unset($_SESSION['user_error']);


/* =========================================================
   FILTER
========================================================= */

$keyword = trim(
    $_GET['keyword'] ?? ''
);

$role = trim(
    $_GET['role'] ?? ''
);

$status = trim(
    $_GET['status'] ?? ''
);


/* =========================================================
   QUERY USER
========================================================= */

$users = [];

$sql = "
    SELECT
        id,
        username,
        nama_lengkap,
        role,
        status
    FROM users
    WHERE 1 = 1
";


$types = '';

$params = [];


/* =========================================================
   SEARCH
========================================================= */

if ($keyword !== '') {

    $sql .= "
        AND (
            username LIKE ?
            OR nama_lengkap LIKE ?
        )
    ";


    $search = '%' . $keyword . '%';


    $types .= 'ss';

    $params[] = $search;
    $params[] = $search;

}


/* =========================================================
   FILTER ROLE
========================================================= */

if ($role !== '') {

    $sql .= "
        AND role = ?
    ";


    $types .= 's';

    $params[] = $role;

}


/* =========================================================
   FILTER STATUS
========================================================= */

if ($status !== '') {

    $sql .= "
        AND status = ?
    ";


    $types .= 's';

    $params[] = $status;

}


/* =========================================================
   ORDER
========================================================= */

$sql .= "
    ORDER BY
        CASE
            WHEN LOWER(role) = 'admin' THEN 0
            ELSE 1
        END,
        nama_lengkap ASC,
        id DESC
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);


if ($stmt) {

    if ($types !== '') {

        mysqli_stmt_bind_param(
            $stmt,
            $types,
            ...$params
        );

    }


    mysqli_stmt_execute($stmt);


    $result = mysqli_stmt_get_result(
        $stmt
    );


    while (
        $row = mysqli_fetch_assoc($result)
    ) {

        $users[] = $row;

    }


    mysqli_stmt_close($stmt);

}


/* =========================================================
   STATISTIK USER
========================================================= */

$total_user = getUserCount(
    $conn
);


$total_admin = getUserCount(
    $conn,
    "WHERE LOWER(role) = 'admin'"
);


$total_user_biasa = getUserCount(
    $conn,
    "WHERE LOWER(role) = 'user'"
);


$total_aktif = getUserCount(
    $conn,
    "WHERE LOWER(status) = 'aktif'"
);


$total_nonaktif = getUserCount(
    $conn,
    "WHERE LOWER(status) = 'nonaktif'"
);


/* =========================================================
   JUMLAH HASIL FILTER
========================================================= */

$total_ditampilkan = count(
    $users
);

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
    Manajemen User - Inventory Maintenance
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
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    rel="stylesheet"
>


<!-- =====================================================
     POPPINS
====================================================== -->

<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>


<style>

/* =========================================================
   ROOT
========================================================= */

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

    --purple: #7456d8;
    --purple-bg: #f1edff;

    /*
     * PENTING
     * Harus mengikuti sidebar.php.
     *
     * Jika sidebar.php menggunakan 265px,
     * maka area content juga menggunakan 265px.
     */
    --sidebar-width: 265px;

}


/* =========================================================
   RESET
========================================================= */

* {

    box-sizing: border-box;

}


html {

    width: 100%;

    min-height: 100%;

}


body {

    margin: 0;

    padding: 0;

    width: 100%;

    min-height: 100vh;

    font-family: 'Poppins', sans-serif;

    background: var(--bg);

    color: var(--text);

    font-size: 14px;

    overflow-x: hidden;

}


/* =========================================================
   MAIN
   Memberikan ruang agar content tidak tertutup sidebar
========================================================= */

.main {

    margin-left: var(--sidebar-width);

    width: calc(100% - var(--sidebar-width));

    min-height: 100vh;

    position: relative;

}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    width: 100%;

    min-height: 76px;

    background: #ffffff;

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

    gap: 12px;

    min-width: 0;

}


.page-title {

    margin: 0;

    font-size: 20px;

    font-weight: 700;

    line-height: 1.2;

}


.page-subtitle {

    font-size: 11px;

    color: var(--muted);

    margin-top: 3px;

}


.topbar-right {

    display: flex;

    align-items: center;

    gap: 14px;

}


.admin-profile {

    display: flex;

    align-items: center;

    gap: 9px;

}


.admin-avatar {

    width: 38px;

    height: 38px;

    border-radius: 50%;

    background: var(--primary);

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-shrink: 0;

}


.admin-name {

    font-size: 12px;

    font-weight: 600;

    color: var(--text);

}


.admin-role {

    font-size: 9px;

    color: var(--muted);

    margin-top: 2px;

}


/* =========================================================
   CONTENT
========================================================= */

.content {

    width: 100%;

    padding: 26px 28px 35px;

}


/* =========================================================
   PAGE HEADER
========================================================= */

.page-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 20px;

}


.back-link {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    color: var(--muted);

    text-decoration: none;

    font-size: 11px;

    font-weight: 500;

}


.back-link:hover {

    color: var(--primary);

}


.page-heading {

    margin: 5px 0 0;

    font-size: 18px;

    font-weight: 700;

    color: var(--text);

}


.page-heading-sub {

    margin-top: 3px;

    color: var(--muted);

    font-size: 10px;

}


/* =========================================================
   PRIMARY BUTTON
========================================================= */

.btn-primary-custom {

    background: var(--primary);

    border: none;

    color: white;

    border-radius: 9px;

    padding: 10px 15px;

    font-size: 11px;

    font-weight: 600;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    transition: .2s ease;

    white-space: nowrap;

}


.btn-primary-custom:hover {

    background: var(--primary-dark);

    color: white;

    transform: translateY(-1px);

}


/* =========================================================
   ALERT
========================================================= */

.alert-custom {

    border: 0;

    border-radius: 10px;

    font-size: 11px;

    padding: 11px 14px;

}


/* =========================================================
   STATISTICS
========================================================= */

.stat-card {

    background: white;

    border: 1px solid var(--border);

    border-radius: 13px;

    padding: 16px;

    height: 100%;

    transition: .2s ease;

}


.stat-card:hover {

    transform: translateY(-2px);

    box-shadow: 0 8px 22px rgba(25,55,90,.06);

}


.stat-icon {

    width: 39px;

    height: 39px;

    border-radius: 10px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 17px;

    margin-bottom: 10px;

}


.blue {

    background: var(--primary-light);

    color: var(--primary);

}


.purple {

    background: var(--purple-bg);

    color: var(--purple);

}


.green {

    background: var(--success-bg);

    color: var(--success);

}


.red {

    background: var(--danger-bg);

    color: var(--danger);

}


.stat-label {

    color: var(--muted);

    font-size: 10px;

}


.stat-value {

    font-size: 22px;

    font-weight: 700;

    margin-top: 2px;

    color: var(--text);

}


/* =========================================================
   PANEL
========================================================= */

.panel {

    background: white;

    border: 1px solid var(--border);

    border-radius: 14px;

    overflow: hidden;

}


.panel-header {

    padding: 16px 18px;

    border-bottom: 1px solid var(--border);

}


.panel-title {

    display: flex;

    align-items: center;

    gap: 7px;

    font-size: 12px;

    font-weight: 700;

    color: var(--text);

    margin-bottom: 13px;

}


.panel-title i {

    color: var(--primary);

}


.result-info {

    color: var(--muted);

    font-size: 10px;

    margin: 0;

}


.result-info strong {

    color: var(--text);

}


/* =========================================================
   FORM
========================================================= */

.form-label {

    font-size: 10px;

    font-weight: 600;

    color: #596579;

    margin-bottom: 5px;

}


.form-control,
.form-select {

    font-family: 'Poppins', sans-serif;

    font-size: 11px;

    border-color: var(--border);

    border-radius: 8px;

    padding: 9px 11px;

    color: var(--text);

    min-height: 39px;

}


.form-control::placeholder {

    color: #a4adba;

}


.form-control:focus,
.form-select:focus {

    border-color: #8fc5ee;

    box-shadow: 0 0 0 .2rem rgba(7,94,170,.08);

}


.btn-filter {

    background: var(--primary);

    color: white;

    border: none;

    border-radius: 8px;

    padding: 9px 15px;

    min-height: 39px;

    font-size: 10px;

    font-weight: 600;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

}


.btn-filter:hover {

    background: var(--primary-dark);

    color: white;

}


.btn-reset {

    background: #f1f3f6;

    color: #596579;

    border: none;

    border-radius: 8px;

    padding: 9px 15px;

    min-height: 39px;

    font-size: 10px;

    font-weight: 600;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

}


.btn-reset:hover {

    background: #e6e9ee;

    color: #404b5c;

}


/* =========================================================
   FILTER ACTIVE
========================================================= */

.filter-tags {

    display: flex;

    flex-wrap: wrap;

    gap: 6px;

    margin-top: 12px;

}


.filter-tag {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    background: var(--primary-light);

    color: var(--primary);

    border-radius: 20px;

    padding: 4px 9px;

    font-size: 9px;

    font-weight: 500;

}


/* =========================================================
   USER TABLE
========================================================= */

.table-responsive {

    width: 100%;

    overflow-x: auto;

    -webkit-overflow-scrolling: touch;

}


.user-table {

    width: 100%;

    margin: 0;

    min-width: 760px;

}


.user-table th {

    background: #fafbfd;

    color: #8993a3;

    font-size: 9px;

    text-transform: uppercase;

    letter-spacing: .35px;

    padding: 12px 14px;

    white-space: nowrap;

    border-bottom: 1px solid var(--border);

}


.user-table td {

    font-size: 11px;

    padding: 13px 14px;

    vertical-align: middle;

    color: #596579;

    border-bottom: 1px solid #f0f2f5;

}


.user-table tbody tr:last-child td {

    border-bottom: 0;

}


.user-table tbody tr {

    transition: .15s ease;

}


.user-table tbody tr:hover {

    background: #fafcff;

}


.number-cell {

    color: #9aa3b1;

    font-weight: 600;

    width: 55px;

}


.user-info {

    display: flex;

    align-items: center;

    gap: 10px;

    min-width: 170px;

}


.user-avatar {

    width: 35px;

    height: 35px;

    border-radius: 9px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: var(--primary-light);

    color: var(--primary);

    font-size: 14px;

    flex-shrink: 0;

}


.user-avatar.admin {

    background: var(--purple-bg);

    color: var(--purple);

}


.user-name {

    font-weight: 600;

    color: var(--text);

    line-height: 1.3;

}


.username {

    color: var(--muted);

    font-size: 9px;

    margin-top: 3px;

}


.name-cell {

    color: #596579;

}


/* =========================================================
   BADGE
========================================================= */

.badge-custom {

    display: inline-flex;

    align-items: center;

    gap: 4px;

    border-radius: 20px;

    padding: 5px 9px;

    font-size: 9px;

    font-weight: 600;

    white-space: nowrap;

}


.badge-admin {

    background: var(--purple-bg);

    color: #6d4bc1;

}


.badge-user {

    background: var(--primary-light);

    color: var(--primary);

}


.badge-active {

    background: var(--success-bg);

    color: #11875a;

}


.badge-inactive {

    background: var(--danger-bg);

    color: #c7353b;

}


/* =========================================================
   ACTION
========================================================= */

.action-group {

    display: flex;

    align-items: center;

    gap: 5px;

}


.action-btn {

    width: 30px;

    height: 30px;

    border: 0;

    border-radius: 7px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    font-size: 13px;

    transition: .18s ease;

}


.action-detail {

    background: var(--primary-light);

    color: var(--primary);

}


.action-edit {

    background: var(--warning-bg);

    color: #b66b00;

}


.action-status {

    background: var(--success-bg);

    color: var(--success);

}


.action-delete {

    background: var(--danger-bg);

    color: var(--danger);

}


.action-btn:hover {

    filter: brightness(.94);

    transform: translateY(-1px);

}


/* =========================================================
   CURRENT USER
========================================================= */

.current-user-label {

    display: inline-flex;

    align-items: center;

    gap: 4px;

    color: #8b95a5;

    font-size: 9px;

    white-space: nowrap;

}


/* =========================================================
   EMPTY
========================================================= */

.empty {

    padding: 55px 20px;

    text-align: center;

    color: var(--muted);

}


.empty-icon {

    width: 60px;

    height: 60px;

    margin: 0 auto 13px;

    border-radius: 16px;

    background: #f1f4f8;

    color: #a2aab7;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 27px;

}


.empty-title {

    font-size: 12px;

    font-weight: 600;

    color: #596579;

    margin-bottom: 3px;

}


.empty-text {

    font-size: 10px;

    color: var(--muted);

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1200px) {

    .content {

        padding-left: 22px;

        padding-right: 22px;

    }


    .topbar {

        padding-left: 22px;

        padding-right: 22px;

    }

}


@media (max-width: 991px) {

    /*
     * Pada mobile/tablet sidebar.php menjadi overlay.
     * Karena itu content tidak boleh memiliki margin kiri.
     */

    .main {

        margin-left: 0;

        width: 100%;

    }


    .topbar {

        min-height: 70px;

        padding: 0 17px;

    }


    .content {

        padding: 20px 17px 30px;

    }


    .page-header {

        gap: 12px;

    }

}


@media (max-width: 767px) {

    .page-header {

        align-items: flex-start;

        flex-direction: column;

    }


    .btn-primary-custom {

        width: 100%;

    }


    .topbar-right {

        gap: 0;

    }


    .content {

        padding: 18px 15px 28px;

    }


    .panel {

        border-radius: 12px;

    }

}


@media (max-width: 575px) {

    .admin-name,
    .admin-role {

        display: none;

    }


    .page-title {

        font-size: 16px;

    }


    .page-subtitle {

        font-size: 9px;

    }


    .content {

        padding: 18px 12px 25px;

    }


    .stat-card {

        padding: 13px;

    }


    .stat-value {

        font-size: 20px;

    }


    .panel-header {

        padding: 14px;

    }


    .btn-filter,
    .btn-reset {

        flex: 1;

    }


    .user-table {

        min-width: 760px;

    }

}

</style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
     WAJIB MENGGUNAKAN admin/sidebar.php
========================================================= -->

<?php include "../sidebar.php"; ?>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<div class="main">


    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <header class="topbar">


        <div class="topbar-left">


            <div>

                <h1 class="page-title">
                    Manajemen User
                </h1>


                <div class="page-subtitle">
                    Kelola akun pengguna sistem Inventory Maintenance
                </div>

            </div>


        </div>


        <div class="topbar-right">


            <div class="admin-profile">


                <div class="admin-avatar">

                    <i class="bi bi-shield-lock-fill"></i>

                </div>


                <div>

                    <div class="admin-name">

                        <?= e(
                            $current_user['nama_lengkap']
                            ?: $current_user['username']
                        ) ?>

                    </div>


                    <div class="admin-role">
                        Administrator
                    </div>

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

        <div class="page-header">


            <div>

                <a
                    href="../index.php"
                    class="back-link"
                >

                    <i class="bi bi-arrow-left"></i>

                    Kembali ke Dashboard Admin

                </a>


                <h2 class="page-heading">
                    Daftar Pengguna
                </h2>


                <div class="page-heading-sub">
                    Kelola akun, role, dan status pengguna sistem.
                </div>

            </div>


            <a
                href="tambah.php"
                class="btn-primary-custom"
            >

                <i class="bi bi-person-plus-fill"></i>

                Tambah User

            </a>


        </div>


        <!-- =================================================
             ALERT SUCCESS
        ================================================== -->

        <?php if ($success): ?>

            <div
                class="alert alert-success alert-custom mb-3"
                role="alert"
            >

                <i class="bi bi-check-circle-fill me-2"></i>

                <?= e($success) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             ALERT ERROR
        ================================================== -->

        <?php if ($error): ?>

            <div
                class="alert alert-danger alert-custom mb-3"
                role="alert"
            >

                <i class="bi bi-exclamation-circle-fill me-2"></i>

                <?= e($error) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             STATISTIK
        ================================================== -->

        <div class="row g-3 mb-4">


            <!-- TOTAL USER -->

            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon blue">

                        <i class="bi bi-people-fill"></i>

                    </div>


                    <div class="stat-label">
                        Total User
                    </div>


                    <div class="stat-value">
                        <?= number_format($total_user) ?>
                    </div>

                </div>

            </div>


            <!-- ADMIN -->

            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon purple">

                        <i class="bi bi-shield-fill-check"></i>

                    </div>


                    <div class="stat-label">
                        Administrator
                    </div>


                    <div class="stat-value">
                        <?= number_format($total_admin) ?>
                    </div>

                </div>

            </div>


            <!-- AKTIF -->

            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon green">

                        <i class="bi bi-person-check-fill"></i>

                    </div>


                    <div class="stat-label">
                        User Aktif
                    </div>


                    <div class="stat-value">
                        <?= number_format($total_aktif) ?>
                    </div>

                </div>

            </div>


            <!-- NONAKTIF -->

            <div class="col-6 col-xl-3">

                <div class="stat-card">

                    <div class="stat-icon red">

                        <i class="bi bi-person-x-fill"></i>

                    </div>


                    <div class="stat-label">
                        User Nonaktif
                    </div>


                    <div class="stat-value">
                        <?= number_format($total_nonaktif) ?>
                    </div>

                </div>

            </div>


        </div>


        <!-- =================================================
             FILTER
        ================================================== -->

        <div class="panel mb-3">


            <div class="panel-header">


                <div class="panel-title">

                    <i class="bi bi-funnel-fill"></i>

                    Filter Pengguna

                </div>


                <form
                    method="GET"
                    class="row g-2 align-items-end"
                >


                    <!-- SEARCH -->

                    <div class="col-lg-5 col-md-6">

                        <label
                            for="keyword"
                            class="form-label"
                        >
                            Cari Pengguna
                        </label>


                        <input
                            type="text"
                            id="keyword"
                            name="keyword"
                            class="form-control"
                            placeholder="Username atau nama lengkap..."
                            value="<?= e($keyword) ?>"
                        >

                    </div>


                    <!-- ROLE -->

                    <div class="col-lg-2 col-md-3">

                        <label
                            for="role"
                            class="form-label"
                        >
                            Role
                        </label>


                        <select
                            name="role"
                            id="role"
                            class="form-select"
                        >

                            <option value="">
                                Semua Role
                            </option>


                            <option
                                value="admin"
                                <?= $role === 'admin'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Admin
                            </option>


                            <option
                                value="user"
                                <?= $role === 'user'
                                    ? 'selected'
                                    : '' ?>
                            >
                                User
                            </option>

                        </select>

                    </div>


                    <!-- STATUS -->

                    <div class="col-lg-2 col-md-3">

                        <label
                            for="status"
                            class="form-label"
                        >
                            Status
                        </label>


                        <select
                            name="status"
                            id="status"
                            class="form-select"
                        >

                            <option value="">
                                Semua Status
                            </option>


                            <option
                                value="Aktif"
                                <?= $status === 'Aktif'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Aktif
                            </option>


                            <option
                                value="Nonaktif"
                                <?= $status === 'Nonaktif'
                                    ? 'selected'
                                    : '' ?>
                            >
                                Nonaktif
                            </option>

                        </select>

                    </div>


                    <!-- BUTTON -->

                    <div class="col-lg-3 col-md-12">


                        <div class="d-flex gap-2">


                            <button
                                type="submit"
                                class="btn-filter"
                            >

                                <i class="bi bi-search"></i>

                                Terapkan Filter

                            </button>


                            <a
                                href="index.php"
                                class="btn-reset"
                            >

                                <i class="bi bi-arrow-counterclockwise"></i>

                                Reset

                            </a>


                        </div>


                    </div>


                </form>


                <!-- FILTER TAGS -->

                <?php if (
                    $keyword !== ''
                    || $role !== ''
                    || $status !== ''
                ): ?>

                    <div class="filter-tags">


                        <?php if ($keyword !== ''): ?>

                            <span class="filter-tag">

                                <i class="bi bi-search"></i>

                                Pencarian:
                                <?= e($keyword) ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($role !== ''): ?>

                            <span class="filter-tag">

                                <i class="bi bi-person-badge"></i>

                                Role:
                                <?= $role === 'admin'
                                    ? 'Admin'
                                    : 'User'
                                ?>

                            </span>

                        <?php endif; ?>


                        <?php if ($status !== ''): ?>

                            <span class="filter-tag">

                                <i class="bi bi-circle-fill"></i>

                                Status:
                                <?= e($status) ?>

                            </span>

                        <?php endif; ?>


                    </div>

                <?php endif; ?>


            </div>

        </div>


        <!-- =================================================
             USER LIST
        ================================================== -->

        <div class="panel">


            <!-- TABLE HEADER -->

            <div
                class="panel-header"
                style="border-bottom:1px solid var(--border);"
            >

                <p class="result-info">

                    Menampilkan

                    <strong>
                        <?= number_format($total_ditampilkan) ?>
                    </strong>

                    pengguna

                    <?php if (
                        $keyword !== ''
                        || $role !== ''
                        || $status !== ''
                    ): ?>

                        dari hasil filter.

                    <?php else: ?>

                        terdaftar dalam sistem.

                    <?php endif; ?>

                </p>

            </div>


            <!-- TABLE -->

            <div class="table-responsive">


                <table class="table user-table">


                    <thead>

                        <tr>

                            <th width="55">
                                No
                            </th>


                            <th>
                                Pengguna
                            </th>


                            <th>
                                Nama Lengkap
                            </th>


                            <th>
                                Role
                            </th>


                            <th>
                                Status
                            </th>


                            <th width="150">
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php if (!empty($users)): ?>


                            <?php
                            $no = 1;
                            ?>


                            <?php foreach ($users as $user): ?>


                                <?php

                                $is_current_user =
                                    (
                                        (int) $user['id']
                                        === $user_id
                                    );


                                $is_admin =
                                    strtolower(
                                        trim(
                                            $user['role'] ?? ''
                                        )
                                    ) === 'admin';

                                ?>


                                <tr>


                                    <!-- NO -->

                                    <td class="number-cell">

                                        <?= $no++ ?>

                                    </td>


                                    <!-- PENGGUNA -->

                                    <td>


                                        <div class="user-info">


                                            <div
                                                class="user-avatar <?= $is_admin ? 'admin' : '' ?>"
                                            >

                                                <?php if ($is_admin): ?>

                                                    <i class="bi bi-shield-fill-check"></i>

                                                <?php else: ?>

                                                    <i class="bi bi-person-fill"></i>

                                                <?php endif; ?>

                                            </div>


                                            <div>


                                                <div class="user-name">

                                                    <?= e(
                                                        $user['username']
                                                    ) ?>

                                                </div>


                                                <div class="username">

                                                    <?= $is_current_user
                                                        ? 'Akun yang sedang digunakan'
                                                        : 'Akun pengguna sistem'
                                                    ?>

                                                </div>


                                            </div>


                                        </div>


                                    </td>


                                    <!-- NAMA LENGKAP -->

                                    <td class="name-cell">

                                        <?= e(
                                            $user['nama_lengkap']
                                            ?: '-'
                                        ) ?>

                                    </td>


                                    <!-- ROLE -->

                                    <td>

                                        <?= roleBadge(
                                            $user['role']
                                        ) ?>

                                    </td>


                                    <!-- STATUS -->

                                    <td>

                                        <?= statusBadge(
                                            $user['status']
                                        ) ?>

                                    </td>


                                    <!-- AKSI -->

                                    <td>


                                        <div class="action-group">


                                            <!-- DETAIL -->

                                            <a
                                                href="detail.php?id=<?= (int) $user['id'] ?>"
                                                class="action-btn action-detail"
                                                title="Lihat Detail"
                                            >

                                                <i class="bi bi-eye-fill"></i>

                                            </a>


                                            <!-- EDIT -->

                                            <a
                                                href="edit.php?id=<?= (int) $user['id'] ?>"
                                                class="action-btn action-edit"
                                                title="Edit User"
                                            >

                                                <i class="bi bi-pencil-fill"></i>

                                            </a>


                                            <?php if (!$is_current_user): ?>


                                                <!-- STATUS -->

                                                <a
                                                    href="status.php?id=<?= (int) $user['id'] ?>"
                                                    class="action-btn action-status"
                                                    title="<?= strtolower(trim($user['status'])) === 'aktif'
                                                        ? 'Nonaktifkan User'
                                                        : 'Aktifkan User'
                                                    ?>"
                                                    onclick="return confirm('Apakah Anda yakin ingin mengubah status user ini?');"
                                                >


                                                    <?php if (
                                                        strtolower(
                                                            trim(
                                                                $user['status']
                                                            )
                                                        ) === 'aktif'
                                                    ): ?>

                                                        <i class="bi bi-person-x-fill"></i>

                                                    <?php else: ?>

                                                        <i class="bi bi-person-check-fill"></i>

                                                    <?php endif; ?>


                                                </a>


                                                <!-- HAPUS -->

                                                <a
                                                    href="hapus.php?id=<?= (int) $user['id'] ?>"
                                                    class="action-btn action-delete"
                                                    title="Hapus User"
                                                    onclick="return confirm('PERINGATAN! User ini akan dihapus secara permanen. Apakah Anda yakin ingin melanjutkan?');"
                                                >

                                                    <i class="bi bi-trash-fill"></i>

                                                </a>


                                            <?php else: ?>


                                                <span
                                                    class="current-user-label"
                                                    title="Akun yang sedang digunakan"
                                                >

                                                    <i class="bi bi-person-check-fill"></i>

                                                    Anda

                                                </span>


                                            <?php endif; ?>


                                        </div>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>


                                <td
                                    colspan="6"
                                    class="p-0"
                                >


                                    <div class="empty">


                                        <div class="empty-icon">

                                            <i class="bi bi-people"></i>

                                        </div>


                                        <div class="empty-title">

                                            Tidak ada pengguna ditemukan

                                        </div>


                                        <div class="empty-text">

                                            Coba ubah kata pencarian atau filter yang digunakan.

                                        </div>


                                    </div>


                                </td>


                            </tr>


                        <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </div>


    </main>


</div>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>


</body>

</html>