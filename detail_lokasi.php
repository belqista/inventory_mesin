<?php
require_once __DIR__ . '/template/auth.php';
require_authenticated();

include "koneksi.php";

/* =========================================================
   PARAMETER
========================================================= */

$lokasi  = isset($_GET['lokasi']) ? trim($_GET['lokasi']) : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

$kw = "%" . $keyword . "%";


/* =========================================================
   VALIDASI LOKASI
========================================================= */

if (empty($lokasi)) {
    header("Location: hierarki.php");
    exit;
}


/* =========================================================
   QUERY AREA
========================================================= */

$query_area = false;

if ($lokasi === 'Lain-lain') {

    $q_str = "
        SELECT
            id,
            nama_area,
            lokasi,
            keterangan
        FROM area_bagian
        WHERE
            (lokasi IS NULL OR lokasi = '')
            AND (
                nama_area LIKE ?
                OR keterangan LIKE ?
            )
        ORDER BY nama_area ASC
    ";

    $stmt = mysqli_prepare($conn, $q_str);

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            "ss",
            $kw,
            $kw
        );

        mysqli_stmt_execute($stmt);

        $query_area = mysqli_stmt_get_result($stmt);
    }

} else {

    $q_str = "
        SELECT
            id,
            nama_area,
            lokasi,
            keterangan
        FROM area_bagian
        WHERE
            lokasi = ?
            AND (
                nama_area LIKE ?
                OR keterangan LIKE ?
            )
        ORDER BY nama_area ASC
    ";

    $stmt = mysqli_prepare($conn, $q_str);

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $lokasi,
            $kw,
            $kw
        );

        mysqli_stmt_execute($stmt);

        $query_area = mysqli_stmt_get_result($stmt);
    }
}


/* =========================================================
   TOTAL AREA
========================================================= */

$total_area = 0;

if ($query_area) {
    $total_area = mysqli_num_rows($query_area);
}


/* =========================================================
   HEADER
========================================================= */

include "template/header.php";
?>


<style>

/* =========================================================
   PAGE
========================================================= */

.detail-lokasi-page {
    width: 100%;
    max-width: 100%;
    padding-bottom: 30px;
}


/* =========================================================
   BREADCRUMB
========================================================= */

.detail-lokasi-breadcrumb {
    margin-bottom: 16px;
    overflow-x: auto;
    white-space: nowrap;
    scrollbar-width: thin;
}

.detail-lokasi-breadcrumb .breadcrumb {
    margin: 0;
    padding: 8px 0;
    flex-wrap: nowrap;
}

.detail-lokasi-breadcrumb .breadcrumb-item {
    font-size: 13px;
}

.detail-lokasi-breadcrumb .breadcrumb-item a {
    color: #005baa;
    font-weight: 500;
}

.detail-lokasi-breadcrumb .breadcrumb-item.active {
    color: #64748b;
    font-weight: 600;
}


/* =========================================================
   MAIN HEADER CARD
========================================================= */

.lokasi-header-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 22px;
    box-shadow: 0 5px 20px rgba(15, 23, 42, .05);
}


/* =========================================================
   TITLE
========================================================= */

.lokasi-title-wrapper {
    min-width: 0;
}

.lokasi-title {
    margin: 0;
    color: #172033;
    font-size: 25px;
    font-weight: 800;
    line-height: 1.3;
    word-break: break-word;
}

.lokasi-title-icon {
    color: #005baa !important;
}

.lokasi-subtitle {
    color: #64748b;
    font-size: 13px;
    margin-top: 5px;
    line-height: 1.5;
}


/* =========================================================
   BACK BUTTON
========================================================= */

.lokasi-back-btn {
    flex-shrink: 0;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 13px;
    white-space: nowrap;
}


/* =========================================================
   SEARCH AREA
========================================================= */

.lokasi-search-form {
    margin-top: 22px;
}

.lokasi-search-box {
    height: 43px;
    border-radius: 10px;
    overflow: hidden;
}

.lokasi-search-box .input-group-text {
    background: #f8fafc;
    border-color: #dbe3ea;
    color: #64748b;
    padding-left: 14px;
    padding-right: 10px;
}

.lokasi-search-box .form-control {
    height: 43px;
    border-color: #dbe3ea;
    font-size: 13px;
    box-shadow: none !important;
}

.lokasi-search-box .form-control:focus {
    border-color: #005baa;
}

.lokasi-search-btn {
    height: 43px;
    border-radius: 10px !important;
    background: #005baa !important;
    border: none !important;
    font-size: 13px;
    font-weight: 600;
}

.lokasi-reset-btn {
    width: 43px;
    height: 43px;
    flex-shrink: 0;
    border-radius: 10px !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}


/* =========================================================
   SEARCH INFO
========================================================= */

.lokasi-result-info {
    margin-top: 14px;
    font-size: 12px;
    color: #94a3b8;
}

.lokasi-result-info strong {
    color: #475569;
}


/* =========================================================
   AREA GRID
========================================================= */

.area-grid {
    margin-top: 0;
}


/* =========================================================
   AREA CARD LINK
========================================================= */

.area-card-link {
    display: block;
    height: 100%;
    text-decoration: none;
}


/* =========================================================
   AREA CARD
========================================================= */

.area-card {
    position: relative;
    height: 100%;
    min-height: 118px;

    background: #ffffff;

    border: 1px solid #f0dfad;

    border-radius: 16px;

    padding: 20px;

    box-shadow:
        0 4px 15px rgba(15, 23, 42, .045);

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        border-color .2s ease;

    overflow: hidden;
}

.area-card::after {
    content: "";

    position: absolute;

    width: 100px;
    height: 100px;

    right: -40px;
    bottom: -45px;

    border-radius: 50%;

    background: rgba(255, 193, 7, .06);

    pointer-events: none;
}

.area-card:hover {
    transform: translateY(-4px);

    border-color: #e6c45a;

    box-shadow:
        0 10px 25px rgba(15, 23, 42, .09);
}


/* =========================================================
   AREA ICON
========================================================= */

.area-icon {
    width: 56px;
    height: 56px;

    flex: 0 0 56px;

    border-radius: 15px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #fff8e1;
    color: #d39e00;

    font-size: 27px;

    transition: .2s;
}

.area-card:hover .area-icon {
    transform: scale(1.05);
}


/* =========================================================
   AREA CONTENT
========================================================= */

.area-content {
    min-width: 0;
    flex: 1;
}

.area-name {
    margin: 0;

    color: #172033;

    font-size: 15px;
    font-weight: 750;

    line-height: 1.4;

    word-break: break-word;
}

.area-link-text {
    margin-top: 6px;

    color: #64748b;

    font-size: 11px;

    display: flex;
    align-items: center;

    gap: 4px;
}

.area-card:hover .area-link-text {
    color: #005baa;
}


/* =========================================================
   LOCATION BADGE
========================================================= */

.area-location-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;

    margin-top: 7px;

    padding: 4px 8px;

    border-radius: 6px;

    background: #f8fafc;

    color: #64748b;

    border: 1px solid #e2e8f0;

    font-size: 10px;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.lokasi-empty {
    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    padding: 55px 25px;

    text-align: center;

    box-shadow:
        0 4px 15px rgba(15, 23, 42, .04);
}

.lokasi-empty-icon {
    width: 65px;
    height: 65px;

    margin: 0 auto 15px;

    border-radius: 17px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #f8fafc;

    color: #94a3b8;

    font-size: 28px;
}

.lokasi-empty-title {
    color: #334155;

    font-size: 15px;

    font-weight: 700;

    margin-bottom: 4px;
}

.lokasi-empty-text {
    color: #94a3b8;

    font-size: 12px;

    line-height: 1.6;

    max-width: 450px;

    margin: 0 auto;
}


/* =========================================================
   RESPONSIVE - TABLET
========================================================= */

@media (max-width: 992px) {

    .lokasi-header-card {
        padding: 20px;
    }

    .lokasi-title {
        font-size: 22px;
    }

    .area-card {
        padding: 17px;
    }

}


/* =========================================================
   RESPONSIVE - MOBILE
========================================================= */

@media (max-width: 768px) {

    .detail-lokasi-page {
        padding-left: 10px;
        padding-right: 10px;
    }

    .detail-lokasi-breadcrumb {
        margin-bottom: 10px;
    }

    .detail-lokasi-breadcrumb .breadcrumb-item {
        font-size: 11px;
    }

    .lokasi-header-card {
        padding: 17px;
        border-radius: 15px;
    }

    .lokasi-title {
        font-size: 20px;
    }

    .lokasi-subtitle {
        font-size: 12px;
    }

    .lokasi-header-top {
        align-items: flex-start !important;
    }

    .lokasi-back-btn {
        padding: 7px 10px;
        font-size: 11px;
    }

    .lokasi-back-btn span {
        display: none;
    }

    .lokasi-back-btn i {
        margin: 0 !important;
    }

    .lokasi-search-form {
        margin-top: 17px;
    }

    .lokasi-search-row {
        display: flex;
        flex-direction: column;
    }

    .lokasi-search-col {
        width: 100%;
    }

    .lokasi-search-actions {
        width: 100%;
        display: flex;
    }

    .lokasi-search-btn {
        flex: 1;
    }

    .area-card {
        min-height: 100px;
        padding: 16px;
        border-radius: 14px;
    }

    .area-icon {
        width: 48px;
        height: 48px;
        flex-basis: 48px;
        font-size: 23px;
        border-radius: 13px;
    }

    .area-name {
        font-size: 14px;
    }

    .area-link-text {
        font-size: 10px;
    }

}


/* =========================================================
   RESPONSIVE - SMALL MOBILE
========================================================= */

@media (max-width: 480px) {

    .detail-lokasi-page {
        padding-left: 7px;
        padding-right: 7px;
    }

    .lokasi-header-card {
        padding: 14px;
        border-radius: 13px;
    }

    .lokasi-title {
        font-size: 18px;
    }

    .lokasi-title i {
        font-size: 17px;
        margin-right: 4px !important;
    }

    .lokasi-subtitle {
        font-size: 11px;
    }

    .area-card {
        padding: 14px;
    }

    .area-icon {
        width: 44px;
        height: 44px;
        flex-basis: 44px;
        font-size: 21px;
    }

    .area-name {
        font-size: 13px;
    }

    .area-link-text {
        font-size: 9.5px;
        margin-top: 4px;
    }

    .lokasi-empty {
        padding: 40px 18px;
    }

}


/* =========================================================
   TOUCH DEVICE
========================================================= */

@media (hover: none) {

    .area-card:hover {
        transform: none;
        box-shadow:
            0 4px 15px rgba(15, 23, 42, .045);
    }

    .area-card:active {
        transform: scale(.99);
    }

}

</style>


<div class="container-fluid detail-lokasi-page px-3 px-md-3 py-2">


    <!-- =====================================================
         BREADCRUMB
    ====================================================== -->

    <nav
        aria-label="breadcrumb"
        class="detail-lokasi-breadcrumb"
    >

        <ol class="breadcrumb">

            <li class="breadcrumb-item">

                <a
                    href="hierarki.php"
                    class="text-decoration-none"
                >

                    <i class="bi bi-house-door me-1"></i>

                    Direktori Utama

                </a>

            </li>

            <li
                class="breadcrumb-item active"
                aria-current="page"
            >

                <i class="bi bi-geo-alt me-1"></i>

                Lokasi:
                <?= htmlspecialchars($lokasi); ?>

            </li>

        </ol>

    </nav>



    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="lokasi-header-card">

        <div
            class="lokasi-header-top d-flex justify-content-between align-items-center gap-3"
        >


            <!-- TITLE -->

            <div class="lokasi-title-wrapper">

                <h1 class="lokasi-title">

                    <i
                        class="bi bi-pin-map-fill lokasi-title-icon me-2"
                    ></i>

                    Lokasi:
                    <?= htmlspecialchars($lokasi); ?>

                </h1>

                <p class="lokasi-subtitle">

                    Daftar Area Bagian dalam lokasi ini

                </p>

            </div>


            <!-- BACK -->

            <a
                href="hierarki.php"
                class="btn btn-outline-secondary lokasi-back-btn"
                title="Kembali ke Direktori Utama"
            >

                <i class="bi bi-arrow-left me-1"></i>

                <span>Kembali</span>

            </a>

        </div>



        <!-- =================================================
             SEARCH
        ================================================== -->

        <form
            method="GET"
            action=""
            class="lokasi-search-form"
        >

            <input
                type="hidden"
                name="lokasi"
                value="<?= htmlspecialchars($lokasi); ?>"
            >


            <div class="row g-2 lokasi-search-row">


                <!-- SEARCH INPUT -->

                <div class="col-md-10 lokasi-search-col">

                    <div class="input-group lokasi-search-box">

                        <span class="input-group-text border-end-0">

                            <i class="bi bi-search"></i>

                        </span>

                        <input
                            type="text"
                            name="keyword"
                            class="form-control border-start-0 ps-1"
                            placeholder="Cari nama area di lokasi ini..."
                            value="<?= htmlspecialchars($keyword); ?>"
                            autocomplete="off"
                        >

                    </div>

                </div>


                <!-- ACTION -->

                <div
                    class="col-md-2 lokasi-search-actions d-flex gap-2"
                >

                    <button
                        type="submit"
                        class="btn btn-primary lokasi-search-btn"
                    >

                        <i class="bi bi-search me-1"></i>

                        Cari

                    </button>


                    <?php if (!empty($keyword)): ?>

                        <a
                            href="detail_lokasi.php?lokasi=<?= urlencode($lokasi); ?>"
                            class="btn btn-outline-secondary lokasi-reset-btn"
                            title="Reset Pencarian"
                        >

                            <i class="bi bi-arrow-counterclockwise"></i>

                        </a>

                    <?php endif; ?>

                </div>

            </div>

        </form>


        <!-- RESULT INFO -->

        <div class="lokasi-result-info">

            <i class="bi bi-info-circle me-1"></i>

            <?php if (!empty($keyword)): ?>

                Menampilkan
                <strong><?= $total_area; ?></strong>
                area untuk pencarian
                "<strong><?= htmlspecialchars($keyword); ?></strong>".

            <?php else: ?>

                Total
                <strong><?= $total_area; ?></strong>
                area tersedia pada lokasi ini.

            <?php endif; ?>

        </div>

    </div>



    <!-- =====================================================
         DAFTAR AREA
    ====================================================== -->

    <div class="row g-3 area-grid">


        <?php if ($query_area && $total_area > 0): ?>


            <?php while ($area = mysqli_fetch_assoc($query_area)): ?>


                <div class="col-12 col-sm-6 col-lg-4">


                    <a
                        href="detail_area.php?id_area=<?= intval($area['id']); ?>"
                        class="area-card-link"
                    >

                        <div class="area-card">


                            <div class="d-flex align-items-center gap-3">


                                <!-- ICON -->

                                <div class="area-icon">

                                    <i class="bi bi-folder2-open"></i>

                                </div>


                                <!-- CONTENT -->

                                <div class="area-content">


                                    <h2 class="area-name">

                                        Area:
                                        <?= htmlspecialchars(
                                            $area['nama_area']
                                        ); ?>

                                    </h2>


                                    <?php if (!empty($area['lokasi'])): ?>

                                        <div class="area-location-badge">

                                            <i class="bi bi-geo-alt"></i>

                                            <?= htmlspecialchars(
                                                $area['lokasi']
                                            ); ?>

                                        </div>

                                    <?php endif; ?>


                                    <div class="area-link-text">

                                        <i class="bi bi-arrow-right-circle"></i>

                                        Lihat Mesin & Komponen

                                    </div>


                                </div>

                            </div>


                        </div>

                    </a>


                </div>


            <?php endwhile; ?>


        <?php else: ?>


            <!-- =================================================
                 EMPTY STATE
            ================================================== -->

            <div class="col-12">

                <div class="lokasi-empty">


                    <div class="lokasi-empty-icon">

                        <i class="bi bi-folder-x"></i>

                    </div>


                    <div class="lokasi-empty-title">

                        <?php if (!empty($keyword)): ?>

                            Area Tidak Ditemukan

                        <?php else: ?>

                            Belum Ada Area

                        <?php endif; ?>

                    </div>


                    <p class="lokasi-empty-text">

                        <?php if (!empty($keyword)): ?>

                            Tidak ada area yang sesuai dengan
                            kata kunci
                            "<strong><?= htmlspecialchars($keyword); ?></strong>"
                            pada lokasi ini.

                        <?php else: ?>

                            Tidak ada area yang ditemukan
                            pada lokasi ini.

                        <?php endif; ?>

                    </p>


                    <?php if (!empty($keyword)): ?>

                        <a
                            href="detail_lokasi.php?lokasi=<?= urlencode($lokasi); ?>"
                            class="btn btn-outline-primary btn-sm mt-3"
                        >

                            <i class="bi bi-arrow-counterclockwise me-1"></i>

                            Reset Pencarian

                        </a>

                    <?php endif; ?>


                </div>

            </div>


        <?php endif; ?>


    </div>

</div>


<?php

/* =========================================================
   CLOSE STATEMENT
========================================================= */

if (isset($stmt) && $stmt) {
    mysqli_stmt_close($stmt);
}


include "template/footer.php";
?>