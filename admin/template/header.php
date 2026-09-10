<?php
// =========================================================
// ADMIN TEMPLATE HEADER
// Inventory Maintenance System
// PT Garudafood Putra Putri Jaya Tbk
// =========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// =========================================================
// PROTEKSI ADMIN
// =========================================================

if (!isset($_SESSION['user_id'])) {

    header("Location: ../login.php");
    exit;

}

if (($_SESSION['role'] ?? '') !== 'admin') {

    header("Location: ../dashboard/index.php");
    exit;

}


// =========================================================
// DATA ADMIN
// =========================================================

$admin_name =
    $_SESSION['nama_lengkap']
    ?? $_SESSION['username']
    ?? 'Administrator';


$current_uri =
    $_SERVER['REQUEST_URI'] ?? '';

$current_file =
    basename($_SERVER['PHP_SELF'] ?? '');

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
        Admin - Inventory Maintenance System
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
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <style>

        /* =====================================================
           RESET
        ====================================================== */

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }


        html,
        body {

            margin: 0;

            padding: 0;

            width: 100%;

            min-height: 100%;

            font-family: 'Poppins', sans-serif;

            background: #f4f7fb;

        }


        body {

            overflow-x: hidden;

        }


        /* =====================================================
           APP WRAPPER
        ====================================================== */

        .admin-app-wrapper {

            width: 100%;

            min-height: 100vh;

        }


        /* =====================================================
           SIDEBAR
        ====================================================== */

        .admin-sidebar {

            position: fixed;

            top: 0;

            left: 0;

            width: 240px;

            height: 100vh;

            background: #ffffff;

            border-right: 1px solid #e7edf5;

            z-index: 1050;

            display: flex;

            flex-direction: column;

            overflow: visible;

            box-shadow:
                2px 0 12px
                rgba(0, 0, 0, 0.03);

            transition:
                width .25s ease,
                transform .25s ease,
                box-shadow .25s ease;

        }


        /* =====================================================
           SIDEBAR HEADER
        ====================================================== */

        .admin-sidebar-header {

            position: relative;

            width: 100%;

            min-height: 110px;

            padding: 15px 12px;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            background: #ffffff;

            border-bottom: 1px solid #edf1f6;

            flex-shrink: 0;

        }


        .admin-sidebar-header img {

            width: 48px;

            height: 48px;

            object-fit: contain;

            display: block;

            margin-bottom: 6px;

        }


        .admin-sidebar-header h4 {

            margin: 0;

            font-size: 15px;

            font-weight: 700;

            color: #075eaa;

            white-space: nowrap;

        }


        .admin-sidebar-header span {

            margin-top: 2px;

            font-size: 9px;

            color: #8a98ab;

            white-space: nowrap;

        }


        /* =====================================================
           ADMIN BADGE
        ====================================================== */

        .admin-badge {

            position: absolute;

            top: 8px;

            right: 10px;

            font-size: 7px;

            font-weight: 700;

            color: #075eaa;

            background: #edf5ff;

            border: 1px solid #d7e8fb;

            border-radius: 20px;

            padding: 3px 7px;

            letter-spacing: .3px;

        }


        /* =====================================================
           SIDEBAR TOGGLE
        ====================================================== */

        .admin-sidebar-toggle {

            position: absolute;

            top: 18px;

            right: -14px;

            width: 28px;

            height: 28px;

            padding: 0;

            border-radius: 50%;

            border: 1px solid #dbe3ed;

            background: #ffffff;

            color: #075eaa;

            display: flex;

            align-items: center;

            justify-content: center;

            cursor: pointer;

            z-index: 3000;

            box-shadow:
                0 2px 6px
                rgba(0, 0, 0, 0.10);

            transition: .2s ease;

        }


        .admin-sidebar-toggle:hover {

            background: #075eaa;

            color: #ffffff;

            border-color: #075eaa;

            transform: scale(1.05);

        }


        .admin-sidebar-toggle i {

            font-size: 12px;

            line-height: 1;

            pointer-events: none;

        }


        /* =====================================================
           MENU
        ====================================================== */

        .admin-sidebar-menu {

            width: 100%;

            flex: 1;

            padding: 12px 10px 20px;

            overflow-y: auto;

            overflow-x: hidden;

        }


        .admin-sidebar-menu::-webkit-scrollbar {

            width: 4px;

        }


        .admin-sidebar-menu::-webkit-scrollbar-thumb {

            background: #dbe3ed;

            border-radius: 10px;

        }


        /* =====================================================
           MENU LINK
        ====================================================== */

        .admin-sidebar-menu > a {

            position: relative;

            width: 100%;

            min-height: 40px;

            margin-bottom: 4px;

            padding: 0 10px;

            display: flex;

            align-items: center;

            gap: 10px;

            color: #66758a;

            text-decoration: none;

            font-size: 12px;

            font-weight: 500;

            border-radius: 8px;

            white-space: nowrap;

            transition: .2s ease;

        }


        .admin-sidebar-menu > a i {

            width: 20px;

            min-width: 20px;

            font-size: 16px;

            text-align: center;

            color: #64748b;

            transition: .2s ease;

        }


        .admin-sidebar-menu > a:hover {

            background: #edf5ff;

            color: #075eaa;

        }


        .admin-sidebar-menu > a:hover i {

            color: #075eaa;

        }


        .admin-sidebar-menu > a.active {

            background: #075eaa;

            color: #ffffff;

            box-shadow:
                0 4px 10px
                rgba(7, 94, 170, 0.20);

        }


        .admin-sidebar-menu > a.active i {

            color: #ffffff;

        }


        /* =====================================================
           SECTION TITLE
        ====================================================== */

        .admin-section-title {

            padding: 14px 10px 6px;

            font-size: 9px;

            font-weight: 700;

            letter-spacing: .7px;

            text-transform: uppercase;

            color: #9aa7b8;

            white-space: nowrap;

        }


        /* =====================================================
           ADMIN PROFILE
        ====================================================== */

        .admin-profile {

            margin: 0 10px 8px;

            padding: 10px;

            border-radius: 10px;

            background: #f7faff;

            border: 1px solid #e6eef8;

            display: flex;

            align-items: center;

            gap: 9px;

            flex-shrink: 0;

        }


        .admin-profile-icon {

            width: 30px;

            height: 30px;

            border-radius: 50%;

            background: #075eaa;

            color: #ffffff;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

        }


        .admin-profile-icon i {

            font-size: 14px;

        }


        .admin-profile-info {

            min-width: 0;

        }


        .admin-profile-name {

            margin: 0;

            font-size: 9px;

            font-weight: 700;

            color: #334155;

            overflow: hidden;

            text-overflow: ellipsis;

            white-space: nowrap;

        }


        .admin-profile-role {

            margin: 1px 0 0;

            font-size: 8px;

            color: #075eaa;

            font-weight: 600;

        }


        /* =====================================================
           LOGOUT
        ====================================================== */

        .admin-sidebar-logout {

            width: calc(100% - 20px);

            min-height: 40px;

            margin: 0 10px 8px;

            padding: 0 10px;

            display: flex;

            align-items: center;

            gap: 10px;

            color: #66758a;

            text-decoration: none;

            font-size: 12px;

            font-weight: 500;

            border-radius: 8px;

            white-space: nowrap;

            flex-shrink: 0;

            transition: .2s ease;

        }


        .admin-sidebar-logout i {

            width: 20px;

            min-width: 20px;

            font-size: 16px;

            text-align: center;

            color: #64748b;

        }


        .admin-sidebar-logout:hover {

            background: #fff1f1;

            color: #dc3545;

        }


        .admin-sidebar-logout:hover i {

            color: #dc3545;

        }


        /* =====================================================
           FOOTER LOGO
        ====================================================== */

        .admin-sidebar-footer {

            width: 100%;

            flex-shrink: 0;

            padding: 12px 10px 13px;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            background: #ffffff;

            border-top: 1px solid #edf1f6;

        }


        .admin-sidebar-footer img {

            display: block;

            width: 180px;

            max-width: 100%;

            height: auto;

            object-fit: contain;

            margin-bottom: 5px;

        }


        .admin-prodi-text {

            margin: 0;

            text-align: center;

            font-size: 8px;

            font-weight: 700;

            letter-spacing: .4px;

            color: #075eaa;

            white-space: nowrap;

            line-height: 1.3;

        }


        /* =====================================================
           MAIN CONTENT
        ====================================================== */

        .admin-main-content {

            position: relative;

            width: calc(100% - 240px);

            min-height: 100vh;

            margin-left: 240px;

            padding: 25px;

            transition:
                width .25s ease,
                margin-left .25s ease;

            overflow-x: hidden;

        }


        /* =====================================================
           COLLAPSED
        ====================================================== */

        body.admin-sidebar-collapsed
        .admin-sidebar {

            width: 70px;

        }


        body.admin-sidebar-collapsed
        .admin-main-content {

            width: calc(100% - 70px);

            margin-left: 70px;

        }


        body.admin-sidebar-collapsed
        .admin-sidebar-header {

            width: 70px;

            min-height: 75px;

            height: 75px;

            padding: 10px 0;

        }


        body.admin-sidebar-collapsed
        .admin-sidebar-header img {

            width: 38px;

            height: 38px;

            margin: 0;

        }


        body.admin-sidebar-collapsed
        .admin-sidebar-header h4,
        body.admin-sidebar-collapsed
        .admin-sidebar-header span,
        body.admin-sidebar-collapsed
        .admin-badge {

            display: none;

        }


        body.admin-sidebar-collapsed
        .admin-sidebar-toggle {

            top: 8px;

            right: -14px;

        }


        body.admin-sidebar-collapsed
        .admin-sidebar-menu {

            padding: 12px 8px 20px;

        }


        body.admin-sidebar-collapsed
        .admin-sidebar-menu > a {

            justify-content: center;

            padding: 0;

            gap: 0;

        }


        body.admin-sidebar-collapsed
        .admin-sidebar-menu > a span {

            display: none;

        }


        body.admin-sidebar-collapsed
        .admin-sidebar-menu > a i {

            width: auto;

            min-width: 0;

            font-size: 18px;

        }


        body.admin-sidebar-collapsed
        .admin-section-title {

            font-size: 0;

            height: 14px;

            padding: 4px 0;

            overflow: hidden;

        }


        body.admin-sidebar-collapsed
        .admin-profile {

            margin: 0 8px 8px;

            padding: 7px 0;

            justify-content: center;

        }


        body.admin-sidebar-collapsed
        .admin-profile-info {

            display: none;

        }


        body.admin-sidebar-collapsed
        .admin-profile-icon {

            margin: 0;

        }


        body.admin-sidebar-collapsed
        .admin-sidebar-logout {

            width: calc(100% - 16px);

            margin: 0 8px 8px;

            padding: 0;

            justify-content: center;

            gap: 0;

        }


        body.admin-sidebar-collapsed
        .admin-sidebar-logout span {

            display: none;

        }


        body.admin-sidebar-collapsed
        .admin-sidebar-logout i {

            width: auto;

            min-width: 0;

            font-size: 18px;

        }


        body.admin-sidebar-collapsed
        .admin-sidebar-footer {

            padding: 10px 4px;

        }


        body.admin-sidebar-collapsed
        .admin-sidebar-footer img {

            width: 52px;

            margin-bottom: 0;

        }


        body.admin-sidebar-collapsed
        .admin-prodi-text {

            display: none;

        }


        /* =====================================================
           MOBILE OVERLAY
        ====================================================== */

        .admin-sidebar-overlay {

            display: none;

            position: fixed;

            inset: 0;

            background:
                rgba(15, 23, 42, .45);

            z-index: 1040;

        }


        /* =====================================================
           MOBILE BUTTON
        ====================================================== */

        .admin-mobile-button {

            display: none;

            position: fixed;

            top: 15px;

            left: 15px;

            width: 42px;

            height: 42px;

            padding: 0;

            border: 0;

            border-radius: 10px;

            background: #075eaa;

            color: #ffffff;

            align-items: center;

            justify-content: center;

            font-size: 20px;

            z-index: 1030;

            box-shadow:
                0 4px 12px
                rgba(0, 0, 0, .15);

        }


        /* =====================================================
           MOBILE / TABLET
        ====================================================== */

        @media (max-width: 991.98px) {

            .admin-sidebar {

                width: 240px;

                transform: translateX(-100%);

                box-shadow:
                    8px 0 30px
                    rgba(0, 0, 0, .12);

            }


            body.admin-sidebar-mobile-open
            .admin-sidebar {

                transform: translateX(0);

            }


            body.admin-sidebar-mobile-open
            .admin-sidebar-overlay {

                display: block;

            }


            .admin-main-content,
            body.admin-sidebar-collapsed
            .admin-main-content {

                width: 100%;

                margin-left: 0;

                padding: 20px 15px;

            }


            .admin-sidebar-toggle {

                display: none;

            }


            .admin-mobile-button {

                display: flex;

            }


            body.admin-sidebar-mobile-open
            .admin-mobile-button {

                left: 255px;

            }

        }


        /* =====================================================
           MOBILE
        ====================================================== */

        @media (max-width: 575.98px) {

            .admin-main-content,
            body.admin-sidebar-collapsed
            .admin-main-content {

                padding: 70px 12px 20px;

            }


            body.admin-sidebar-mobile-open
            .admin-mobile-button {

                left: auto;

                right: 15px;

            }


            .card {

                border-radius: 15px !important;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     MOBILE OVERLAY
========================================================= -->

<div
    class="admin-sidebar-overlay"
    id="adminSidebarOverlay"
></div>


<!-- =========================================================
     MOBILE MENU BUTTON
========================================================= -->

<button
    type="button"
    class="admin-mobile-button"
    id="adminMobileButton"
    aria-label="Buka menu"
>

    <i class="bi bi-list"></i>

</button>


<!-- =========================================================
     APP WRAPPER
========================================================= -->

<div class="admin-app-wrapper">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside
        class="admin-sidebar"
        id="adminSidebar"
    >


        <!-- =================================================
             TOGGLE
        ================================================== -->

        <button
            type="button"
            class="admin-sidebar-toggle"
            id="adminSidebarToggle"
            title="Tutup / Buka Sidebar"
            aria-label="Tutup / Buka Sidebar"
        >

            <i class="bi bi-list"></i>

        </button>


        <!-- =================================================
             HEADER
        ================================================== -->

        <div class="admin-sidebar-header">

            <div class="admin-badge">
                ADMIN
            </div>

            <img
                src="/inventory_mesin/assets/img/logo-garudafood.png"
                alt="Logo Garudafood"
            >

            <h4>
                GarudaFood
            </h4>

            <span>
                Inventory Maintenance
            </span>

        </div>


        <!-- =================================================
             MENU
        ================================================== -->

        <nav class="admin-sidebar-menu">


            <!-- =============================================
                 DASHBOARD
            ============================================== -->

            <a
                href="/inventory_mesin/admin/index.php"
                class="<?= ($current_file === 'index.php' && strpos($current_uri, '/admin/') !== false) ? 'active' : '' ?>"
            >

                <i class="bi bi-speedometer2"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <!-- =============================================
                 DAFTAR MESIN
            ============================================== -->

            <a
                href="/inventory_mesin/hierarki.php"
            >

                <i class="bi bi-diagram-3"></i>

                <span>
                    Daftar Mesin
                </span>

            </a>


            <!-- =============================================
                 MASTER DATA
            ============================================== -->

            <div class="admin-section-title">
                Master Data
            </div>


            <a
                href="/inventory_mesin/master/area.php"
            >

                <i class="bi bi-geo-alt"></i>

                <span>
                    Data Area
                </span>

            </a>


            <a
                href="/inventory_mesin/master/jenis_mesin.php"
            >

                <i class="bi bi-grid"></i>

                <span>
                    Jenis Mesin
                </span>

            </a>


            <a
                href="/inventory_mesin/mesin/index.php"
            >

                <i class="bi bi-building"></i>

                <span>
                    Data Mesin
                </span>

            </a>


            <a
                href="/inventory_mesin/sub_mesin/index.php"
            >

                <i class="bi bi-diagram-3"></i>

                <span>
                    Sub Mesin
                </span>

            </a>


            <a
                href="/inventory_mesin/komponen/index.php"
            >

                <i class="bi bi-cpu"></i>

                <span>
                    Data Komponen
                </span>

            </a>


            <!-- =============================================
                 TRANSAKSI
            ============================================== -->

            <div class="admin-section-title">
                Transaksi
            </div>


            <a
                href="/inventory_mesin/maintenance/index.php"
            >

                <i class="bi bi-tools"></i>

                <span>
                    Maintenance
                </span>

            </a>


            <!-- =============================================
                 ADMINISTRASI
            ============================================== -->

            <div class="admin-section-title">
                Administrasi
            </div>


            <a
                href="/inventory_mesin/admin/users/index.php"
            >

                <i class="bi bi-people"></i>

                <span>
                    User Management
                </span>

            </a>


        </nav>


        <!-- =================================================
             PROFILE ADMIN
        ================================================== -->

        <div class="admin-profile">

            <div class="admin-profile-icon">

                <i class="bi bi-person-fill"></i>

            </div>


            <div class="admin-profile-info">

                <p class="admin-profile-name">

                    <?= htmlspecialchars(
                        $admin_name,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </p>

                <p class="admin-profile-role">
                    Administrator
                </p>

            </div>

        </div>


        <!-- =================================================
             LOGOUT
        ================================================== -->

        <a
            href="/inventory_mesin/logout.php"
            class="admin-sidebar-logout"
            onclick="return confirm('Apakah Anda yakin ingin logout?');"
        >

            <i class="bi bi-box-arrow-right"></i>

            <span>
                Logout
            </span>

        </a>


        <!-- =================================================
             FOOTER LOGO
        ================================================== -->

        <div class="admin-sidebar-footer">

            <img
                src="/inventory_mesin/assets/img/POLTEKSI_LG.jpeg"
                alt="Politeknik Semen Indonesia"
            >

            <div class="admin-prodi-text">

                D3 - TEKNOLOGI INFORMASI

            </div>

        </div>


    </aside>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main
        class="admin-main-content"
        id="adminMainContent"
    >