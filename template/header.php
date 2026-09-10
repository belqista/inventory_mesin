<?php

require_once __DIR__ . '/auth.php';
require_authenticated();

// =========================================================
// TEMPLATE HEADER
// Inventory Maintenance System
// PT Garudafood Putra Putri Jaya Tbk
// =========================================================

$base_url = "/inventory_mesin/";
$current_uri = $_SERVER['REQUEST_URI'] ?? '';
$current_file = basename($_SERVER['PHP_SELF'] ?? '');

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Inventory Maintenance System</title>


    <!-- =====================================================
         BOOTSTRAP 5
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
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         CSS UTAMA
    ====================================================== -->

    <link
        rel="stylesheet"
        href="/inventory_mesin/assets/css/style.css"
    >


    <!-- =====================================================
         SIDEBAR FIX CSS
         SIDEBAR RAMPING & PROPOSIONAL
    ====================================================== -->

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

        .app-wrapper {
            width: 100%;
            min-height: 100vh;
        }


        /* =====================================================
           SIDEBAR
        ====================================================== */

        .sidebar {
            position: fixed !important;

            top: 0 !important;
            left: 0 !important;

            width: 240px !important;
            height: 100vh !important;

            background: #ffffff !important;

            border-right: 1px solid #e7edf5;

            z-index: 1050;

            display: flex;
            flex-direction: column;

            overflow: visible !important;

            transition:
                width 0.25s ease,
                transform 0.25s ease,
                box-shadow 0.25s ease;

            box-shadow:
                2px 0 12px rgba(0, 0, 0, 0.03);
        }


        /* =====================================================
           SIDEBAR HEADER
        ====================================================== */

        .sidebar-header {
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

            transition: 0.25s ease;
        }


        /* =====================================================
           LOGO GARUDAFOOD
        ====================================================== */

        .sidebar-header img {
            width: 48px;
            height: 48px;

            object-fit: contain;

            margin-bottom: 6px;

            display: block;

            transition: 0.25s ease;
        }


        /* =====================================================
           NAMA APLIKASI
        ====================================================== */

        .sidebar-header h4 {
            margin: 0;

            font-size: 15px;
            font-weight: 700;

            color: #075eaa;

            white-space: nowrap;

            transition: 0.2s ease;
        }


        /* =====================================================
           SUBTITLE
        ====================================================== */

        .sidebar-header span {
            margin-top: 2px;

            font-size: 9px;

            color: #8a98ab;

            white-space: nowrap;

            transition: 0.2s ease;
        }


        /* =====================================================
           TOGGLE BUTTON
        ====================================================== */

        .sidebar-toggle {
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
                0 2px 6px rgba(0, 0, 0, 0.10);

            transition:
                background 0.2s ease,
                color 0.2s ease,
                border-color 0.2s ease,
                transform 0.2s ease;
        }


        .sidebar-toggle:hover {
            background: #075eaa;

            color: #ffffff;

            border-color: #075eaa;

            transform: scale(1.05);
        }


        .sidebar-toggle i {
            font-size: 12px;

            line-height: 1;

            pointer-events: none;
        }


        /* =====================================================
           SIDEBAR MENU
        ====================================================== */

        .sidebar-menu {
            width: 100%;

            flex: 1;

            padding: 12px 10px 20px;

            overflow-y: auto;

            overflow-x: hidden;
        }


        .sidebar-menu::-webkit-scrollbar {
            width: 4px;
        }


        .sidebar-menu::-webkit-scrollbar-track {
            background: transparent;
        }


        .sidebar-menu::-webkit-scrollbar-thumb {
            background: #dbe3ed;

            border-radius: 10px;
        }


        /* =====================================================
           MENU LINK
        ====================================================== */

        .sidebar-menu > a {
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

            transition:
                background 0.2s ease,
                color 0.2s ease,
                transform 0.2s ease;

            white-space: nowrap;
        }


        /* =====================================================
           ICON MENU
        ====================================================== */

        .sidebar-menu > a i {
            width: 20px;

            min-width: 20px;

            font-size: 16px;

            text-align: center;

            color: #64748b;

            transition: 0.2s ease;
        }


        /* =====================================================
           HOVER MENU
        ====================================================== */

        .sidebar-menu > a:hover {
            background: #edf5ff;

            color: #075eaa;
        }


        .sidebar-menu > a:hover i {
            color: #075eaa;
        }


        /* =====================================================
           ACTIVE MENU
        ====================================================== */

        .sidebar-menu > a.active {
            background: #075eaa;

            color: #ffffff;

            box-shadow:
                0 4px 10px rgba(7, 94, 170, 0.20);
        }


        .sidebar-menu > a.active i {
            color: #ffffff;
        }


        /* =====================================================
           SECTION TITLE
        ====================================================== */

        .sidebar-section-title {
            padding: 14px 10px 6px;

            font-size: 9px;

            font-weight: 700;

            letter-spacing: 0.7px;

            text-transform: uppercase;

            color: #9aa7b8;

            white-space: nowrap;

            transition: 0.2s ease;
        }


        /* =====================================================
           MAIN CONTENT
        ====================================================== */

        .main-content {
            position: relative;

            width: calc(100% - 240px);

            min-height: 100vh;

            margin-left: 240px;

            padding: 25px;

            transition:
                width 0.25s ease,
                margin-left 0.25s ease;

            overflow-x: hidden;
        }


        /* =====================================================
           COLLAPSED DESKTOP
        ====================================================== */

        body.sidebar-collapsed .sidebar {
            width: 70px !important;
        }


        /* =====================================================
           MAIN CONTENT COLLAPSED
        ====================================================== */

        body.sidebar-collapsed .main-content {
            width: calc(100% - 70px);

            margin-left: 70px;
        }


        /* =====================================================
           HEADER COLLAPSED
        ====================================================== */

        body.sidebar-collapsed .sidebar-header {

            position: relative;

            width: 70px;

            height: 75px;

            min-height: 75px;

            padding: 10px 0;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        /* =====================================================
           LOGO COLLAPSED
        ====================================================== */

        body.sidebar-collapsed .sidebar-header img {

            width: 38px;

            height: 38px;

            margin: 0;

            object-fit: contain;

            display: block;
        }


        /* =====================================================
           HILANGKAN TEKS SAAT COLLAPSED
        ====================================================== */

        body.sidebar-collapsed .sidebar-header h4,
        body.sidebar-collapsed .sidebar-header span {

            display: none;
        }


        /* =====================================================
           TOGGLE COLLAPSED
        ====================================================== */

        body.sidebar-collapsed .sidebar-toggle {

            position: absolute;

            top: 8px;

            right: -14px;

            width: 28px;

            height: 28px;

            padding: 0;

            margin: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            background: #ffffff;

            border: 1px solid #dbe3ed;

            color: #075eaa;

            z-index: 3000;

            box-shadow:
                0 2px 7px rgba(0, 0, 0, 0.14);
        }


        body.sidebar-collapsed .sidebar-toggle:hover {

            background: #075eaa;

            color: #ffffff;

            border-color: #075eaa;
        }


        body.sidebar-collapsed .sidebar-toggle i {

            font-size: 12px;

            line-height: 1;
        }


        /* =====================================================
           MENU COLLAPSED
        ====================================================== */

        body.sidebar-collapsed .sidebar-menu {

            padding: 12px 8px 20px;
        }


        /* =====================================================
           LINK COLLAPSED
        ====================================================== */

        body.sidebar-collapsed .sidebar-menu > a {

            justify-content: center;

            padding: 0;

            gap: 0;
        }


        /* =====================================================
           HILANGKAN TEXT MENU
        ====================================================== */

        body.sidebar-collapsed .sidebar-menu > a span {

            display: none;
        }


        /* =====================================================
           ICON COLLAPSED
        ====================================================== */

        body.sidebar-collapsed .sidebar-menu > a i {

            width: auto;

            min-width: 0;

            font-size: 18px;
        }


        /* =====================================================
           SECTION TITLE COLLAPSED
        ====================================================== */

        body.sidebar-collapsed .sidebar-section-title {

            font-size: 0;

            height: 14px;

            padding: 4px 0;

            overflow: hidden;
        }


        /* =====================================================
           LOGOUT SIDEBAR
        ====================================================== */

        .sidebar-logout {
            position: relative;

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

            background: transparent;

            transition:
                background 0.2s ease,
                color 0.2s ease,
                transform 0.2s ease;

            white-space: nowrap;

            flex-shrink: 0;
        }


        /* =====================================================
           ICON LOGOUT
        ====================================================== */

        .sidebar-logout i {
            width: 20px;

            min-width: 20px;

            font-size: 16px;

            text-align: center;

            color: #64748b;

            transition: 0.2s ease;
        }


        /* =====================================================
           HOVER LOGOUT
        ====================================================== */

        .sidebar-logout:hover {
            background: #fff1f1;

            color: #dc3545;

            text-decoration: none;
        }


        .sidebar-logout:hover i {
            color: #dc3545;
        }


        /* =====================================================
           LOGOUT COLLAPSED
        ====================================================== */

        body.sidebar-collapsed .sidebar-logout {
            width: calc(100% - 16px);

            margin: 0 8px 8px;

            padding: 0;

            justify-content: center;

            gap: 0;
        }


        body.sidebar-collapsed .sidebar-logout span {
            display: none;
        }


        body.sidebar-collapsed .sidebar-logout i {
            width: auto;

            min-width: 0;

            font-size: 18px;
        }


        /* =====================================================
           LOGO POLITEKNIK SEMEN INDONESIA
        ====================================================== */

        .sidebar-footer-logo {
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


        .sidebar-footer-logo img {
            display: block;

            width: 180px;

            max-width: 100%;

            height: auto;

            object-fit: contain;

            margin-bottom: 5px;
        }


        /* =====================================================
           TEKS PROGRAM STUDI
        ====================================================== */

        .sidebar-footer-logo .prodi-text {
            margin: 0;

            text-align: center;

            font-size: 8px;

            font-weight: 700;

            letter-spacing: 0.4px;

            color: #075eaa;

            white-space: nowrap;

            line-height: 1.3;
        }


        /* =====================================================
           SIDEBAR COLLAPSED - LOGO POLTEKSI
        ====================================================== */

        body.sidebar-collapsed .sidebar-footer-logo {
            padding: 10px 4px;
        }


        body.sidebar-collapsed .sidebar-footer-logo img {
            width: 52px;

            height: auto;

            margin-bottom: 0;
        }


        body.sidebar-collapsed .sidebar-footer-logo .prodi-text {
            display: none;
        }


        /* =====================================================
           MOBILE FOOTER
        ====================================================== */

        @media (max-width: 991.98px) {

            .sidebar-footer-logo {
                padding: 12px 10px;
            }

            .sidebar-footer-logo img {
                width: 180px;
            }

            .sidebar-footer-logo .prodi-text {
                font-size: 8px;
            }

            .sidebar-logout {
                width: calc(100% - 20px);

                margin: 0 10px 8px;
            }

        }


        @media (max-width: 575.98px) {

            .sidebar-footer-logo {
                padding: 10px 10px 12px;
            }

            .sidebar-footer-logo img {
                width: 170px;
            }

            .sidebar-footer-logo .prodi-text {
                font-size: 8px;
            }

        }


        /* =====================================================
           MOBILE OVERLAY
        ====================================================== */

        .sidebar-overlay {

            display: none;

            position: fixed;

            inset: 0;

            background: rgba(15, 23, 42, 0.45);

            z-index: 1040;
        }


        /* =====================================================
           MOBILE TOGGLE
        ====================================================== */

        .mobile-menu-button {

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
                0 4px 12px rgba(0, 0, 0, 0.15);

            cursor: pointer;
        }


        /* =====================================================
           TABLET
        ====================================================== */

        @media (max-width: 991.98px) {

            .sidebar {

                width: 240px !important;

                transform: translateX(-100%) !important;

                box-shadow:
                    8px 0 30px rgba(0, 0, 0, 0.12);
            }


            body.sidebar-mobile-open .sidebar {

                transform: translateX(0) !important;
            }


            body.sidebar-mobile-open .sidebar-overlay {

                display: block;
            }


            .main-content,
            body.sidebar-collapsed .main-content {

                width: 100% !important;

                margin-left: 0 !important;

                padding: 20px 15px;
            }


            .sidebar-toggle {

                display: none;
            }


            .mobile-menu-button {

                display: flex;
            }


            body.sidebar-mobile-open .mobile-menu-button {

                left: 255px;
            }


            .sidebar-footer-logo img {
                width: 180px;
            }

        }


        /* =====================================================
           MOBILE
        ====================================================== */

        @media (max-width: 575.98px) {

            .main-content,
            body.sidebar-collapsed .main-content {

                padding: 70px 12px 20px;
            }


            .sidebar {

                width: 240px !important;
            }


            body.sidebar-mobile-open .mobile-menu-button {

                left: auto;

                right: 15px;
            }


            .container,
            .container-fluid {

                padding-left: 10px !important;

                padding-right: 10px !important;
            }


            .card {

                border-radius: 15px !important;
            }


            h1 {
                font-size: 24px;
            }


            h2 {
                font-size: 21px;
            }


            h3 {
                font-size: 19px;
            }


            h4 {
                font-size: 17px;
            }


            h5 {
                font-size: 15px;
            }


            .breadcrumb {

                font-size: 11px;

                overflow-x: auto;

                white-space: nowrap;

                flex-wrap: nowrap;
            }


            .input-group {

                width: 100%;
            }


            .btn {

                font-size: 12px;
            }


            .sidebar-footer-logo {
                padding: 12px 10px;
            }


            .sidebar-footer-logo img {
                width: 170px;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     MOBILE OVERLAY
========================================================= -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>


<!-- =========================================================
     MOBILE MENU BUTTON
========================================================= -->

<button
    type="button"
    class="mobile-menu-button"
    id="mobileMenuButton"
    aria-label="Buka menu"
>
    <i class="bi bi-list"></i>
</button>


<!-- =========================================================
     APP WRAPPER
========================================================= -->

<div class="app-wrapper">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside
        class="sidebar"
        id="sidebar"
    >


        <!-- =================================================
             TOGGLE DESKTOP
        ================================================== -->

        <button
            type="button"
            class="sidebar-toggle"
            id="sidebarToggle"
            title="Tutup / Buka Sidebar"
            aria-label="Tutup / Buka Sidebar"
        >
            <i class="bi bi-list"></i>
        </button>


        <!-- =================================================
             HEADER SIDEBAR
        ================================================== -->

        <div class="sidebar-header">

            <img
                src="/inventory_mesin/assets/img/logo-garudafood.png"
                alt="Logo Garudafood"
            >

            <h4>GarudaFood</h4>

            <span>Inventory Maintenance</span>

        </div>


        <!-- =================================================
             MENU
        ================================================== -->

        <nav class="sidebar-menu">


            <!-- =================================================
                 DASHBOARD
            ================================================== -->

            <a
                href="/inventory_mesin/dashboard/index.php"
                class="<?= (strpos($current_uri, '/dashboard/') !== false) ? 'active' : '' ?>"
            >

                <i class="bi bi-speedometer2"></i>

                <span>Dashboard</span>

            </a>


            <!-- =================================================
                 STRUKTUR
            ================================================== -->

            <a
                href="/inventory_mesin/struktur.php"
                class="<?= ($current_file === 'struktur.php') ? 'active' : '' ?>"
            >

                <i class="bi bi-diagram-3"></i>

                <span>Struktur</span>

            </a>


            <!-- =================================================
                 MASTER DATA
            ================================================== -->

            <div class="sidebar-section-title">
                Master Data
            </div>


            <!-- AREA -->

            <a
                href="/inventory_mesin/master/area.php"
                class="<?= ($current_file === 'area.php') ? 'active' : '' ?>"
            >

                <i class="bi bi-geo-alt"></i>

                <span>Data Area</span>

            </a>


            <!-- JENIS MESIN -->

            <a
                href="/inventory_mesin/master/jenis_mesin.php"
                class="<?= ($current_file === 'jenis_mesin.php') ? 'active' : '' ?>"
            >

                <i class="bi bi-grid"></i>

                <span>Jenis Mesin</span>

            </a>


            <!-- MESIN -->

            <a
                href="/inventory_mesin/mesin/index.php"
                class="<?= (strpos($current_uri, '/mesin/') !== false) ? 'active' : '' ?>"
            >

                <i class="bi bi-building"></i>

                <span>Data Mesin</span>

            </a>


            <!-- SUB MESIN -->

            <a
                href="/inventory_mesin/sub_mesin/index.php"
                class="<?= (strpos($current_uri, '/sub_mesin/') !== false) ? 'active' : '' ?>"
            >

                <i class="bi bi-diagram-3"></i>

                <span>Sub Mesin</span>

            </a>


            <!-- KOMPONEN -->

            <a
                href="/inventory_mesin/komponen/index.php"
                class="<?= (strpos($current_uri, '/komponen/') !== false) ? 'active' : '' ?>"
            >

                <i class="bi bi-cpu"></i>

                <span>Data Komponen</span>

            </a>


            <!-- =================================================
                 TRANSAKSI & LAINNYA
            ================================================== -->

            <div class="sidebar-section-title">
                Transaksi & Lainnya
            </div>


            <!-- MAINTENANCE -->

            <a
                href="/inventory_mesin/maintenance/index.php"
                class="<?= (strpos($current_uri, '/maintenance/') !== false) ? 'active' : '' ?>"
            >

                <i class="bi bi-tools"></i>

                <span>Maintenance</span>

            </a>


        </nav>


        <!-- =================================================
             LOGOUT
        ================================================== -->

        <a
            href="/inventory_mesin/logout.php"
            class="sidebar-logout"
            onclick="return confirm('Apakah Anda yakin ingin logout?');"
        >

            <i class="bi bi-box-arrow-right"></i>

            <span>Logout</span>

        </a>


        <!-- =================================================
             LOGO POLITEKNIK SEMEN INDONESIA
        ================================================== -->

        <div class="sidebar-footer-logo">

            <img
                src="/inventory_mesin/assets/img/POLTEKSI_LG.jpeg"
                alt="Politeknik Semen Indonesia"
            >

            <div class="prodi-text">
                D3 - TEKNOLOGI INFORMASI
            </div>

        </div>


    </aside>


    <!-- =====================================================
         MAIN CONTENT

         Jangan hapus bagian ini.
         Semua halaman akan masuk setelah bagian ini.
    ====================================================== -->

    <main
        class="main-content"
        id="mainContent"
    >
