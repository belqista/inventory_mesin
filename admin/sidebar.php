<?php

/*
|--------------------------------------------------------------------------
| SIDEBAR ADMIN
|--------------------------------------------------------------------------
| File:
| inventory_mesin/admin/sidebar.php
|
| Dipanggil dari seluruh halaman admin.
|
| Variabel:
| $active_menu
|
| Contoh:
| $active_menu = 'dashboard';
| $active_menu = 'struktur';
| $active_menu = 'area';
| $active_menu = 'jenis_mesin';
| $active_menu = 'sub_mesin';
| $active_menu = 'komponen';
| $active_menu = 'maintenance';
| $active_menu = 'users';
|--------------------------------------------------------------------------
*/


/* =========================================================
   DEFAULT
========================================================= */

$active_menu = $active_menu ?? '';

$sidebar_nama =
    $_SESSION['nama_lengkap']
    ?? $_SESSION['username']
    ?? 'Administrator';

?>

<style>

/* =========================================================
   SIDEBAR VARIABLE
========================================================= */

:root {

    --sidebar-width: 240px;

    --sidebar-primary: #123f7a;
    --sidebar-primary-dark: #092f63;
    --sidebar-primary-light: #eaf2ff;

    --sidebar-text: #172033;
    --sidebar-muted: #788396;

    --sidebar-border: #e7ebf1;

    --sidebar-white: #ffffff;

    --sidebar-danger: #dc3545;
    --sidebar-danger-bg: #fff0f0;

}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    top: 0;
    left: 0;

    width: var(--sidebar-width);
    height: 100vh;

    background: var(--sidebar-white);

    border-right: 1px solid var(--sidebar-border);

    z-index: 1050;

    display: flex;

    flex-direction: column;

}


/* =========================================================
   HEADER
========================================================= */

.sidebar-header {

    height: 76px;

    padding: 0 18px;

    display: flex;

    align-items: center;

    gap: 10px;

    border-bottom: 1px solid var(--sidebar-border);

    flex-shrink: 0;

}


.sidebar-logo {

    width: 42px;

    height: 42px;

    object-fit: contain;

    object-position: center;

    flex-shrink: 0;

}


.sidebar-brand {

    min-width: 0;

    overflow: hidden;

}


.sidebar-brand-title {

    font-size: 12px;

    font-weight: 700;

    color: var(--sidebar-text);

    line-height: 1.2;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;

}


.sidebar-brand-subtitle {

    margin-top: 3px;

    font-size: 8px;

    line-height: 1.3;

    color: var(--sidebar-muted);

    text-transform: uppercase;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;

}


/* =========================================================
   CONTENT
========================================================= */

.sidebar-content {

    flex: 1;

    overflow-y: auto;

    padding: 10px 12px 12px;

}


.sidebar-content::-webkit-scrollbar {

    width: 5px;

}


.sidebar-content::-webkit-scrollbar-track {

    background: transparent;

}


.sidebar-content::-webkit-scrollbar-thumb {

    background: #d5dce6;

    border-radius: 10px;

}


/* =========================================================
   LABEL
========================================================= */

.menu-label {

    padding: 14px 9px 7px;

    font-size: 9px;

    font-weight: 700;

    letter-spacing: .9px;

    color: #a1a9b8;

    text-transform: uppercase;

}


.menu-label:first-child {

    padding-top: 5px;

}


/* =========================================================
   MENU LINK
========================================================= */

.menu-link {

    display: flex;

    align-items: center;

    gap: 11px;

    width: 100%;

    padding: 10px 10px;

    margin-bottom: 3px;

    border-radius: 9px;

    color: #566174;

    font-size: 11px;

    font-weight: 500;

    text-decoration: none;

    transition: .2s ease;

}


.menu-link i {

    width: 18px;

    text-align: center;

    font-size: 16px;

    flex-shrink: 0;

}


.menu-link span {

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;

}


.menu-link:hover {

    background: var(--sidebar-primary-light);

    color: var(--sidebar-primary);

}


.menu-link.active {

    background: var(--sidebar-primary);

    color: #ffffff;

}


/* =========================================================
   LOGOUT
========================================================= */

.menu-link.logout {

    color: var(--sidebar-danger);

}


.menu-link.logout:hover {

    background: var(--sidebar-danger-bg);

    color: var(--sidebar-danger);

}


/* =========================================================
   FOOTER
========================================================= */

.sidebar-footer {

    padding: 14px 15px;

    border-top: 1px solid var(--sidebar-border);

    display: flex;

    align-items: center;

    gap: 10px;

    background: #ffffff;

    flex-shrink: 0;

}


.sidebar-footer img {

    width: 42px;

    height: 42px;

    object-fit: contain;

    object-position: center;

    border-radius: 8px;

    border: 1px solid var(--sidebar-border);

    background: #ffffff;

    padding: 2px;

    flex-shrink: 0;

}


.sidebar-footer-text {

    font-size: 8px;

    line-height: 1.5;

    color: var(--sidebar-muted);

    min-width: 0;

}


.sidebar-footer-text strong {

    display: block;

    color: var(--sidebar-text);

    font-size: 9px;

    font-weight: 700;

}


/* =========================================================
   MOBILE OVERLAY
========================================================= */

.sidebar-overlay {

    position: fixed;

    inset: 0;

    background: rgba(0,0,0,.35);

    z-index: 1040;

    opacity: 0;

    visibility: hidden;

    pointer-events: none;

    transition: .25s ease;

}


.sidebar-overlay.show {

    opacity: 1;

    visibility: visible;

    pointer-events: auto;

}


/* =========================================================
   MOBILE TOGGLE BUTTON
========================================================= */

.mobile-sidebar-toggle {

    display: none;

}


/* =========================================================
   MOBILE SIDEBAR
========================================================= */

@media (max-width: 991.98px) {

    .sidebar {

        transform: translateX(-100%);

        transition: transform .25s ease;

        box-shadow: 8px 0 30px rgba(0,0,0,.12);

    }


    .sidebar.show {

        transform: translateX(0);

    }


    /* =====================================================
       HAMBURGER
    ====================================================== */

    .mobile-sidebar-toggle {

        position: fixed;

        top: 15px;

        left: 15px;

        width: 42px;

        height: 42px;

        display: flex;

        align-items: center;

        justify-content: center;

        border: 1px solid var(--sidebar-border);

        border-radius: 10px;

        background: #ffffff;

        color: var(--sidebar-primary);

        box-shadow: 0 4px 16px rgba(0,0,0,.10);

        cursor: pointer;

        z-index: 1035;

        font-size: 19px;

        transition: .2s ease;

    }


    .mobile-sidebar-toggle:hover {

        background: var(--sidebar-primary-light);

        transform: translateY(-1px);

    }


    .mobile-sidebar-toggle:active {

        transform: scale(.96);

    }


    /* =====================================================
       SAAT SIDEBAR TERBUKA
    ====================================================== */

    .sidebar.show ~ .mobile-sidebar-toggle {

        display: none;

    }

}


/* =========================================================
   EXTRA SMALL DEVICE
========================================================= */

@media (max-width: 575.98px) {

    .mobile-sidebar-toggle {

        top: 12px;

        left: 12px;

        width: 40px;

        height: 40px;

        border-radius: 9px;

        font-size: 18px;

    }

}

</style>


<!-- =========================================================
    SIDEBAR
========================================================= -->

<aside
    class="sidebar"
    id="adminSidebar"
>


    <!-- =====================================================
        HEADER
    ====================================================== -->

    <div class="sidebar-header">


        <img
            src="/inventory_mesin/assets/img/logo-garudafood.png"
            class="sidebar-logo"
            alt="Garudafood"
            onerror="this.style.display='none';"
        >


        <div class="sidebar-brand">

            <div class="sidebar-brand-title">
                Inventory Maintenance
            </div>

            <div class="sidebar-brand-subtitle">
                PT GARUDAFOOD PUTRA PUTRI JAYA TBK
            </div>

        </div>


    </div>



    <!-- =====================================================
        MENU
    ====================================================== -->

    <div class="sidebar-content">


        <!-- =================================================
            ADMIN
        ================================================== -->

        <div class="menu-label">
            Admin
        </div>


        <!-- DASHBOARD -->

        <a
            href="/inventory_mesin/admin/index.php"
            class="menu-link <?= $active_menu === 'dashboard' ? 'active' : ''; ?>"
        >

            <i class="bi bi-grid-1x2"></i>

            <span>
                Dashboard
            </span>

        </a>


        <!-- STRUKTUR -->

        <a
            href="/inventory_mesin/admin/mesin/struktur.php"
            class="menu-link <?= $active_menu === 'struktur' ? 'active' : ''; ?>"
        >

            <i class="bi bi-folder2-open"></i>

            <span>
                Struktur
            </span>

        </a>



        <!-- =================================================
            DATA UTAMA
        ================================================== -->

        <div class="menu-label">
            Data Utama
        </div>


        <!-- DATA AREA -->

        <a
            href="/inventory_mesin/admin/area/index.php"
            class="menu-link <?= $active_menu === 'area' ? 'active' : ''; ?>"
        >

            <i class="bi bi-geo-alt"></i>

            <span>
                Data Area
            </span>

        </a>


        <!-- JENIS MESIN -->

        <a
            href="/inventory_mesin/admin/jenis_mesin/index.php"
            class="menu-link <?= $active_menu === 'jenis_mesin' ? 'active' : ''; ?>"
        >

            <i class="bi bi-cpu"></i>

            <span>
                Jenis Mesin
            </span>

        </a>


        <!-- SUB MESIN -->

        <a
            href="/inventory_mesin/admin/sub_mesin/index.php"
            class="menu-link <?= $active_menu === 'sub_mesin' ? 'active' : ''; ?>"
        >

            <i class="bi bi-diagram-3"></i>

            <span>
                Sub Mesin
            </span>

        </a>


        <!-- KOMPONEN -->

        <a
            href="/inventory_mesin/admin/komponen/index.php"
            class="menu-link <?= $active_menu === 'komponen' ? 'active' : ''; ?>"
        >

            <i class="bi bi-box-seam"></i>

            <span>
                Data Komponen
            </span>

        </a>



        <!-- =================================================
            TRANSAKSI
        ================================================== -->

        <div class="menu-label">
            Transaksi
        </div>


        <!-- MAINTENANCE -->

        <a
            href="/inventory_mesin/admin/maintenance/index.php"
            class="menu-link <?= $active_menu === 'maintenance' ? 'active' : ''; ?>"
        >

            <i class="bi bi-wrench-adjustable-circle"></i>

            <span>
                Riwayat Maintenance
            </span>

        </a>



        <!-- =================================================
            PENGGUNA
        ================================================== -->

        <div class="menu-label">
            Pengguna
        </div>


        <!-- USERS -->

        <a
            href="/inventory_mesin/admin/users/index.php"
            class="menu-link <?= $active_menu === 'users' ? 'active' : ''; ?>"
        >

            <i class="bi bi-people"></i>

            <span>
                Manajemen User
            </span>

        </a>



        <!-- =================================================
            AKUN
        ================================================== -->

        <div class="menu-label">
            Akun
        </div>


        <!-- LOGOUT -->

        <a
            href="/inventory_mesin/logout.php"
            class="menu-link logout"
            onclick="return confirm('Apakah Anda yakin ingin keluar?');"
        >

            <i class="bi bi-box-arrow-right"></i>

            <span>
                Logout
            </span>

        </a>


    </div>



    <!-- =====================================================
        FOOTER
    ====================================================== -->

    <div class="sidebar-footer">


        <img
            src="/inventory_mesin/assets/img/POLTEKSI_LG.jpeg"
            alt="Politeknik Semen Indonesia"
            onerror="this.style.display='none';"
        >


        <div class="sidebar-footer-text">

            <strong>
                D-3 Teknologi Informasi
            </strong>

            Politeknik Semen Indonesia

        </div>


    </div>


</aside>



<!-- =========================================================
    MOBILE TOGGLE
========================================================= -->

<button
    type="button"
    class="mobile-sidebar-toggle"
    id="mobileToggle"
    aria-label="Buka menu"
    aria-controls="adminSidebar"
    aria-expanded="false"
>

    <i class="bi bi-list"></i>

</button>



<!-- =========================================================
    OVERLAY
========================================================= -->

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
></div>



<script>

/* =========================================================
   MOBILE SIDEBAR CONTROLLER
========================================================= */

(function () {

    const sidebar =
        document.getElementById('adminSidebar');

    const overlay =
        document.getElementById('sidebarOverlay');

    const toggle =
        document.getElementById('mobileToggle');


    if (!sidebar || !overlay || !toggle) {

        return;

    }


    /* =====================================================
       OPEN SIDEBAR
    ====================================================== */

    function openSidebar() {

        sidebar.classList.add('show');

        overlay.classList.add('show');

        toggle.setAttribute(
            'aria-expanded',
            'true'
        );

        document.body.style.overflow = 'hidden';

    }


    /* =====================================================
       CLOSE SIDEBAR
    ====================================================== */

    function closeSidebar() {

        sidebar.classList.remove('show');

        overlay.classList.remove('show');

        toggle.setAttribute(
            'aria-expanded',
            'false'
        );

        document.body.style.overflow = '';

    }


    /* =====================================================
       TOGGLE
    ====================================================== */

    toggle.addEventListener(
        'click',
        function (event) {

            event.preventDefault();

            event.stopPropagation();


            if (
                sidebar.classList.contains('show')
            ) {

                closeSidebar();

            } else {

                openSidebar();

            }

        }
    );


    /* =====================================================
       OVERLAY CLICK
    ====================================================== */

    overlay.addEventListener(
        'click',
        function () {

            closeSidebar();

        }
    );


    /* =====================================================
       MENU CLICK
    ====================================================== */

    const menuLinks =
        sidebar.querySelectorAll('.menu-link');


    menuLinks.forEach(function (link) {

        link.addEventListener(
            'click',
            function () {

                if (
                    window.innerWidth <= 991.98
                ) {

                    closeSidebar();

                }

            }
        );

    });


    /* =====================================================
       ESC KEY
    ====================================================== */

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape'
                &&
                window.innerWidth <= 991.98
            ) {

                closeSidebar();

            }

        }
    );


    /* =====================================================
       RESIZE
    ====================================================== */

    window.addEventListener(
        'resize',
        function () {

            if (
                window.innerWidth > 991.98
            ) {

                closeSidebar();

            }

        }
    );


})();

</script>