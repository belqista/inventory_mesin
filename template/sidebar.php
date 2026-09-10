<?php
/* =========================================================
   SIDEBAR
   Inventory Maintenance System
   PT Garudafood Putra Putri Jaya Tbk
========================================================= */

$base_url = "/inventory_mesin/";

$current_uri  = $_SERVER['REQUEST_URI'] ?? '';
$current_file = basename($_SERVER['PHP_SELF'] ?? '');

/* =========================================================
   HELPER ACTIVE MENU
========================================================= */

function sidebarActive($folder)
{
    global $current_uri;

    return strpos($current_uri, '/' . $folder . '/') !== false
        ? 'active'
        : '';
}

function sidebarExact($file)
{
    global $current_file;

    return $current_file === $file
        ? 'active'
        : '';
}
?>

<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar" id="sidebar">

    <!-- =====================================================
         BRAND
    ====================================================== -->

    <div class="sidebar-brand">

        <div class="sidebar-brand-icon">
            <i class="bi bi-gear-wide-connected"></i>
        </div>

        <div class="sidebar-brand-text">

            <div class="sidebar-brand-title">
                Inventory
            </div>

            <div class="sidebar-brand-subtitle">
                Maintenance System
            </div>

        </div>

    </div>


    <!-- =====================================================
         MENU
    ====================================================== -->

    <div class="sidebar-menu-wrapper">

        <div class="sidebar-section-title">
            MENU UTAMA
        </div>


        <!-- DASHBOARD -->

        <a
            href="<?= $base_url ?>index.php"
            class="nav-item-link <?= ($current_file === 'index.php' && strpos($current_uri, '/inventory_mesin/') !== false && !strpos($current_uri, '/mesin/') && !strpos($current_uri, '/maintenance/')) ? 'active' : '' ?>"
        >

            <span class="nav-item-icon">
                <i class="bi bi-grid-1x2-fill"></i>
            </span>

            <span class="nav-item-text">
                Dashboard
            </span>

        </a>


        <!-- =================================================
             MESIN
        ================================================== -->

        <a
            href="<?= $base_url ?>mesin/index.php"
            class="nav-item-link <?= sidebarActive('mesin') ?>"
        >

            <span class="nav-item-icon">
                <i class="bi bi-cpu-fill"></i>
            </span>

            <span class="nav-item-text">
                Mesin
            </span>

        </a>


        <!-- =================================================
             SUB MESIN
        ================================================== -->

        <a
            href="<?= $base_url ?>sub_mesin/index.php"
            class="nav-item-link <?= sidebarActive('sub_mesin') ?>"
        >

            <span class="nav-item-icon">
                <i class="bi bi-diagram-3-fill"></i>
            </span>

            <span class="nav-item-text">
                Sub Mesin
            </span>

        </a>


        <!-- =================================================
             KOMPONEN
        ================================================== -->

        <a
            href="<?= $base_url ?>komponen/index.php"
            class="nav-item-link <?= sidebarActive('komponen') ?>"
        >

            <span class="nav-item-icon">
                <i class="bi bi-box-seam-fill"></i>
            </span>

            <span class="nav-item-text">
                Komponen
            </span>

        </a>


        <!-- =================================================
             MAINTENANCE
        ================================================== -->

        <a
            href="<?= $base_url ?>maintenance/index.php"
            class="nav-item-link <?= sidebarActive('maintenance') ?>"
        >

            <span class="nav-item-icon">
                <i class="bi bi-tools"></i>
            </span>

            <span class="nav-item-text">
                Maintenance
            </span>

        </a>


        <!-- =================================================
             LAPORAN
        ================================================== -->

        <div class="sidebar-section-title mt-3">
            LAPORAN
        </div>


        <a
            href="<?= $base_url ?>laporan/index.php"
            class="nav-item-link <?= sidebarActive('laporan') ?>"
        >

            <span class="nav-item-icon">
                <i class="bi bi-file-earmark-bar-graph-fill"></i>
            </span>

            <span class="nav-item-text">
                Laporan
            </span>

        </a>

    </div>


    <!-- =====================================================
         LOGO POLITEKNIK
         SELALU DI BAGIAN PALING BAWAH SIDEBAR
    ====================================================== -->

   <!-- =========================================================
     LOGO POLITEKNIK - PALING BAWAH SIDEBAR
========================================================= -->

<div class="sidebar-institution">

    <div class="sidebar-institution-logo">
        <img
            src="<?= $base_url ?>assets/img/POLTEKSI_LG.jpeg"
            alt="Politeknik Semen Indonesia"
        >
    </div>

    <div class="sidebar-institution-text">
        <div class="sidebar-institution-title">
            POLITEKNIK SEMEN INDONESIA
        </div>

        <div class="sidebar-institution-subtitle">
            D-3 TEKNOLOGI INFORMASI
        </div>
    </div>

</div>

</aside>


<!-- =========================================================
     SIDEBAR STYLE
========================================================= -->

<style>

/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    --sidebar-width: 265px;
    --sidebar-collapsed-width: 78px;

    position: fixed;

    top: 0;
    left: 0;

    width: var(--sidebar-width);
    height: 100vh;

    background: #ffffff;

    border-right: 1px solid #e5e7eb;

    display: flex;
    flex-direction: column;

    z-index: 1050;

    overflow: hidden;

    transition:
        width .25s ease,
        transform .25s ease;

}


/* =========================================================
   BRAND
========================================================= */

.sidebar-brand {

    height: 76px;

    padding: 0 18px;

    display: flex;
    align-items: center;

    gap: 12px;

    border-bottom: 1px solid #eef0f3;

    flex: 0 0 76px;

}


.sidebar-brand-icon {

    width: 42px;
    height: 42px;

    min-width: 42px;

    border-radius: 11px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: linear-gradient(
        135deg,
        #005baa,
        #0076c8
    );

    color: #ffffff;

    font-size: 21px;

    box-shadow:
        0 5px 14px rgba(
            0,
            91,
            170,
            .18
        );

}


.sidebar-brand-text {

    min-width: 0;

    overflow: hidden;

}


.sidebar-brand-title {

    font-size: 16px;

    font-weight: 800;

    color: #172033;

    line-height: 1.2;

}


.sidebar-brand-subtitle {

    font-size: 9px;

    color: #94a3b8;

    font-weight: 700;

    margin-top: 3px;

    white-space: nowrap;

}


/* =========================================================
   MENU WRAPPER
========================================================= */

.sidebar-menu-wrapper {

    flex: 1;

    overflow-y: auto;
    overflow-x: hidden;

    padding: 17px 12px 12px;

}


/* Scrollbar */

.sidebar-menu-wrapper::-webkit-scrollbar {

    width: 4px;

}


.sidebar-menu-wrapper::-webkit-scrollbar-thumb {

    background: #dbe2ea;

    border-radius: 10px;

}


/* =========================================================
   SECTION TITLE
========================================================= */

.sidebar-section-title {

    padding: 7px 12px 8px;

    font-size: 9px;

    font-weight: 800;

    color: #94a3b8;

    letter-spacing: .8px;

}


/* =========================================================
   NAV ITEM
========================================================= */

.nav-item-link {

    width: 100%;

    min-height: 45px;

    padding: 9px 12px;

    margin-bottom: 4px;

    display: flex;
    align-items: center;

    gap: 12px;

    border-radius: 10px;

    color: #64748b;

    text-decoration: none;

    font-size: 13px;

    font-weight: 600;

    transition:
        background .2s ease,
        color .2s ease,
        transform .2s ease;

}


.nav-item-link:hover {

    background: #f1f7fc;

    color: #005baa;

}


.nav-item-link.active {

    background: linear-gradient(
        135deg,
        #005baa,
        #0076c8
    );

    color: #ffffff;

    box-shadow:
        0 5px 14px rgba(
            0,
            91,
            170,
            .15
        );

}


.nav-item-icon {

    width: 25px;
    min-width: 25px;

    height: 25px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 17px;

}


.nav-item-text {

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;

}


/* =========================================================
   FOOTER LOGO
========================================================= */

.sidebar-footer-logo {

    flex: 0 0 auto;

    padding: 10px 15px 14px;

    background: #ffffff;

}


/* garis pemisah */

.sidebar-footer-logo-line {

    height: 1px;

    background: #e5e7eb;

    margin-bottom: 12px;

}


/* logo */

.sidebar-politeknik-logo {

    display: block;

    width: 100%;

    max-width: 225px;

    height: auto;

    max-height: 70px;

    object-fit: contain;

    object-position: center;

    margin: 0 auto;

}


/* tulisan kecil */

.sidebar-footer-text {

    text-align: center;

    margin-top: 6px;

    font-size: 8px;

    font-weight: 700;

    color: #94a3b8;

    letter-spacing: .2px;

}


/* =========================================================
   SIDEBAR COLLAPSED
========================================================= */

body.sidebar-collapsed .sidebar {

    width: var(--sidebar-collapsed-width);

}


body.sidebar-collapsed .sidebar-brand {

    justify-content: center;

    padding: 0;

}


body.sidebar-collapsed .sidebar-brand-text {

    display: none;

}


body.sidebar-collapsed .sidebar-menu-wrapper {

    padding-left: 10px;
    padding-right: 10px;

}


body.sidebar-collapsed .sidebar-section-title {

    display: none;

}


body.sidebar-collapsed .nav-item-link {

    justify-content: center;

    padding-left: 0;
    padding-right: 0;

}


body.sidebar-collapsed .nav-item-text {

    display: none;

}


body.sidebar-collapsed .nav-item-icon {

    width: 100%;

}


body.sidebar-collapsed .sidebar-footer-logo {

    padding: 10px 8px 12px;

}


body.sidebar-collapsed .sidebar-politeknik-logo {

    width: 48px;

    max-height: 48px;

    object-fit: contain;

}


body.sidebar-collapsed .sidebar-footer-text {

    display: none;

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 768px) {

    .sidebar {

        width: 270px;

        transform: translateX(-100%);

        box-shadow:
            8px 0 30px rgba(
                15,
                23,
                42,
                .12
            );

    }


    /*
     * Saat mobile dibuka
     */

    body.sidebar-mobile-open .sidebar {

        transform: translateX(0);

    }


    /*
     * Jangan gunakan collapsed mode
     * pada mobile
     */

    body.sidebar-collapsed .sidebar {

        width: 270px;

    }


    body.sidebar-collapsed .sidebar-brand {

        justify-content: flex-start;

        padding: 0 18px;

    }


    body.sidebar-collapsed .sidebar-brand-text {

        display: block;

    }


    body.sidebar-collapsed .sidebar-section-title {

        display: block;

    }


    body.sidebar-collapsed .nav-item-link {

        justify-content: flex-start;

        padding-left: 12px;

        padding-right: 12px;

    }


    body.sidebar-collapsed .nav-item-text {

        display: block;

    }


    body.sidebar-collapsed .nav-item-icon {

        width: 25px;

    }


    body.sidebar-collapsed .sidebar-footer-logo {

        padding: 10px 15px 14px;

    }


    body.sidebar-collapsed .sidebar-politeknik-logo {

        width: 100%;

        max-width: 225px;

        max-height: 70px;

    }


    body.sidebar-collapsed .sidebar-footer-text {

        display: block;

    }

}


/* =========================================================
   MOBILE LOGO
========================================================= */

@media (max-width: 480px) {

    .sidebar-politeknik-logo {

        max-width: 210px;

        max-height: 65px;

    }

}

</style>