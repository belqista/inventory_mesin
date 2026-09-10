<?php
require_once __DIR__ . '/template/auth.php';
require_authenticated();

include "koneksi.php";

/* =========================================================
   PARAMETER
========================================================= */

$id_area = isset($_GET['id_area']) ? intval($_GET['id_area']) : 0;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

$kw = "%" . $keyword . "%";


/* =========================================================
   VALIDASI ID AREA
========================================================= */

if ($id_area <= 0) {
    header("Location: hierarki.php");
    exit;
}


/* =========================================================
   AMBIL DATA AREA
========================================================= */

$stmt_area = mysqli_prepare($conn, "
    SELECT 
        id,
        nama_area,
        lokasi
    FROM area_bagian
    WHERE id = ?
    LIMIT 1
");

if (!$stmt_area) {
    die("Gagal menyiapkan query area.");
}

mysqli_stmt_bind_param($stmt_area, "i", $id_area);
mysqli_stmt_execute($stmt_area);

$result_area = mysqli_stmt_get_result($stmt_area);
$data_area = mysqli_fetch_assoc($result_area);

mysqli_stmt_close($stmt_area);


/* =========================================================
   JIKA AREA TIDAK DITEMUKAN
========================================================= */

if (!$data_area) {
    header("Location: hierarki.php");
    exit;
}


/* =========================================================
   QUERY JENIS MESIN
========================================================= */

$query_jenis = false;

if ($keyword !== '') {

    /*
     * Pencarian mencakup:
     * - Jenis Mesin
     * - Nama Mesin
     * - Serial Number Mesin
     * - Sub Mesin
     * - Nama Komponen
     * - Serial Number Komponen
     */

    $sql_jenis = "
        SELECT DISTINCT
            jm.id,
            jm.nama_jenis_mesin
        FROM jenis_mesin jm

        LEFT JOIN mesin m
            ON m.id_jenis_mesin = jm.id

        LEFT JOIN sub_mesin sm
            ON sm.id_mesin = m.id

        LEFT JOIN komponen k
            ON k.id_sub_mesin = sm.id

        WHERE
            jm.id_area = ?
            AND (
                jm.nama_jenis_mesin LIKE ?
                OR m.nama_mesin LIKE ?
                OR m.serial_number LIKE ?
                OR sm.nama_sub_mesin LIKE ?
                OR k.nama_bagian LIKE ?
                OR k.serial_number LIKE ?
            )

        ORDER BY
            jm.nama_jenis_mesin ASC
    ";

    $stmt_jenis = mysqli_prepare($conn, $sql_jenis);

    if ($stmt_jenis) {

        mysqli_stmt_bind_param(
            $stmt_jenis,
            "issssss",
            $id_area,
            $kw,
            $kw,
            $kw,
            $kw,
            $kw,
            $kw
        );

        mysqli_stmt_execute($stmt_jenis);

        $query_jenis = mysqli_stmt_get_result($stmt_jenis);
    }

} else {

    $stmt_jenis = mysqli_prepare($conn, "
        SELECT
            id,
            nama_jenis_mesin
        FROM jenis_mesin
        WHERE id_area = ?
        ORDER BY nama_jenis_mesin ASC
    ");

    if ($stmt_jenis) {

        mysqli_stmt_bind_param(
            $stmt_jenis,
            "i",
            $id_area
        );

        mysqli_stmt_execute($stmt_jenis);

        $query_jenis = mysqli_stmt_get_result($stmt_jenis);
    }
}


/* =========================================================
   TOTAL JENIS MESIN
========================================================= */

$total_jenis = 0;

if ($query_jenis) {
    $total_jenis = mysqli_num_rows($query_jenis);
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

.area-detail-page {
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
}


/* =========================================================
   BREADCRUMB
========================================================= */

.area-breadcrumb-wrapper {
    margin-bottom: 18px;
}

.area-breadcrumb {
    margin: 0;
    padding: 0;

    font-size: 12px;

    display: flex;
    align-items: center;
    flex-wrap: wrap;

    gap: 3px;
}

.area-breadcrumb .breadcrumb-item a {
    color: #005baa;
    font-weight: 500;
}

.area-breadcrumb .breadcrumb-item.active {
    color: #64748b;
    font-weight: 600;
}


/* =========================================================
   HEADER CARD
========================================================= */

.area-header-card {
    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 18px;

    padding: 22px;

    margin-bottom: 22px;

    box-shadow:
        0 5px 18px rgba(15, 23, 42, .04);
}


/* =========================================================
   TITLE
========================================================= */

.area-title-wrapper {
    min-width: 0;
}

.area-title-icon {
    width: 48px;
    height: 48px;

    flex-shrink: 0;

    border-radius: 13px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #eef5ff;
    color: #005baa;

    font-size: 22px;
}

.area-title {
    margin: 0;

    font-size: 23px;

    line-height: 1.3;

    font-weight: 800;

    color: #172033;

    word-break: break-word;
}

.area-subtitle {
    margin-top: 4px;

    color: #64748b;

    font-size: 12px;
}


/* =========================================================
   BACK BUTTON
========================================================= */

.area-back-btn {
    border-radius: 10px;

    font-size: 12px;

    font-weight: 600;

    padding: 9px 14px;

    white-space: nowrap;
}


/* =========================================================
   SEARCH
========================================================= */

.area-search-wrapper {
    margin-top: 22px;

    padding-top: 18px;

    border-top: 1px solid #f1f5f9;
}

.area-search-input-group {
    height: 44px;
}

.area-search-input-group .input-group-text {
    background: #f8fafc;

    border-color: #e2e8f0;

    color: #94a3b8;

    padding-left: 14px;
    padding-right: 10px;
}

.area-search-input {
    height: 44px;

    border-left: none;

    border-color: #e2e8f0;

    font-size: 12px;
}

.area-search-input:focus {
    border-color: #86b7fe;

    box-shadow:
        0 0 0 .2rem rgba(13, 110, 253, .08);
}


/* =========================================================
   SEARCH BUTTON
========================================================= */

.area-search-btn {
    min-width: 100px;

    height: 44px;

    border-radius: 9px;

    background: #005baa;

    border-color: #005baa;

    font-size: 12px;

    font-weight: 600;
}

.area-search-btn:hover {
    background: #004987;

    border-color: #004987;
}

.area-reset-btn {
    width: 44px;
    height: 44px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 9px;
}


/* =========================================================
   RESULT INFO
========================================================= */

.area-result-info {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    flex-wrap: wrap;

    margin-bottom: 14px;
}

.area-result-title {
    color: #172033;

    font-size: 15px;

    font-weight: 700;
}

.area-result-subtitle {
    color: #94a3b8;

    font-size: 11px;
}

.area-count-badge {
    background: #eef5ff;

    color: #005baa;

    border: 1px solid #dbeafe;

    border-radius: 999px;

    padding: 6px 11px;

    font-size: 11px;

    font-weight: 700;
}


/* =========================================================
   JENIS MESIN CARD
========================================================= */

.area-machine-card {
    position: relative;

    height: 100%;

    display: block;

    padding: 20px;

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    text-decoration: none;

    overflow: hidden;

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        border-color .2s ease;
}

.area-machine-card::after {
    content: "";

    position: absolute;

    width: 100px;
    height: 100px;

    right: -45px;
    bottom: -45px;

    border-radius: 50%;

    background: rgba(0, 91, 170, .035);

    pointer-events: none;
}

.area-machine-card:hover {
    transform: translateY(-3px);

    border-color: #cbd5e1;

    box-shadow:
        0 10px 25px rgba(15, 23, 42, .08);
}


/* =========================================================
   FOLDER ICON
========================================================= */

.area-machine-icon {
    width: 56px;
    height: 56px;

    flex-shrink: 0;

    border-radius: 15px;

    display: flex;

    align-items: center;
    justify-content: center;

    background: #fff8e1;

    color: #d39e00;

    font-size: 25px;

    transition: .2s;
}

.area-machine-card:hover .area-machine-icon {
    transform: scale(1.05);
}


/* =========================================================
   MACHINE INFO
========================================================= */

.area-machine-info {
    min-width: 0;
}

.area-machine-name {
    margin: 0;

    color: #172033;

    font-size: 14px;

    line-height: 1.45;

    font-weight: 700;

    word-break: break-word;
}

.area-machine-link {
    display: block;

    margin-top: 5px;

    color: #94a3b8;

    font-size: 11px;

    font-weight: 500;
}

.area-machine-card:hover .area-machine-link {
    color: #005baa;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.area-empty-card {
    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    padding: 55px 20px;

    text-align: center;
}

.area-empty-icon {
    width: 68px;
    height: 68px;

    margin: 0 auto 15px;

    border-radius: 18px;

    display: flex;

    align-items: center;
    justify-content: center;

    background: #f1f5f9;

    color: #94a3b8;

    font-size: 28px;
}

.area-empty-title {
    color: #475569;

    font-size: 14px;

    font-weight: 700;
}

.area-empty-text {
    color: #94a3b8;

    font-size: 12px;

    margin-top: 4px;

    line-height: 1.6;
}


/* =========================================================
   RESPONSIVE TABLET
========================================================= */

@media (max-width: 992px) {

    .area-header-card {
        padding: 18px;
    }

    .area-title {
        font-size: 20px;
    }

    .area-machine-card {
        padding: 17px;
    }

}


/* =========================================================
   RESPONSIVE MOBILE
========================================================= */

@media (max-width: 768px) {

    .area-detail-page {
        padding-bottom: 15px;
    }

    .area-breadcrumb-wrapper {
        margin-bottom: 13px;
    }

    .area-breadcrumb {
        font-size: 11px;

        line-height: 1.8;
    }

    .area-header-card {
        padding: 15px;

        border-radius: 14px;

        margin-bottom: 18px;
    }

    .area-title-icon {
        width: 42px;
        height: 42px;

        border-radius: 11px;

        font-size: 19px;
    }

    .area-title {
        font-size: 18px;
    }

    .area-subtitle {
        font-size: 11px;
    }

    .area-back-btn {
        width: 100%;

        margin-top: 4px;
    }

    .area-search-wrapper {
        margin-top: 17px;

        padding-top: 15px;
    }

    .area-search-input-group {
        width: 100%;
    }

    .area-search-row {
        display: flex;

        flex-direction: column;
    }

    .area-search-button-wrapper {
        width: 100% !important;

        display: flex;

        margin-top: 7px;
    }

    .area-search-btn {
        flex: 1;
    }

    .area-result-info {
        margin-bottom: 12px;
    }

    .area-machine-card {
        padding: 16px;

        border-radius: 14px;
    }

}


/* =========================================================
   RESPONSIVE SMALL MOBILE
========================================================= */

@media (max-width: 480px) {

    .area-title {
        font-size: 17px;
    }

    .area-machine-icon {
        width: 50px;
        height: 50px;

        font-size: 22px;
    }

    .area-machine-name {
        font-size: 13px;
    }

    .area-machine-link {
        font-size: 10px;
    }

    .area-empty-card {
        padding: 45px 15px;
    }

}

</style>


<div class="container-fluid area-detail-page">


    <!-- =====================================================
         BREADCRUMB
    ====================================================== -->

    <div class="area-breadcrumb-wrapper">

        <nav aria-label="breadcrumb">

            <ol class="breadcrumb area-breadcrumb">

                <li class="breadcrumb-item">

                    <a
                        href="hierarki.php"
                        class="text-decoration-none"
                    >
                        <i class="bi bi-house-door me-1"></i>
                        Direktori Utama
                    </a>

                </li>

                <li class="breadcrumb-item">

                    <a
                        href="detail_lokasi.php?lokasi=<?= urlencode($data_area['lokasi']); ?>"
                        class="text-decoration-none"
                    >
                        Lokasi:
                        <?= htmlspecialchars($data_area['lokasi']); ?>
                    </a>

                </li>

                <li
                    class="breadcrumb-item active"
                    aria-current="page"
                >
                    Area:
                    <?= htmlspecialchars($data_area['nama_area']); ?>
                </li>

            </ol>

        </nav>

    </div>



    <!-- =====================================================
         HEADER AREA
    ====================================================== -->

    <div class="area-header-card">


        <!-- TITLE -->

        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">

            <div class="d-flex align-items-center gap-3 area-title-wrapper">

                <div class="area-title-icon">

                    <i class="bi bi-folder2-open"></i>

                </div>

                <div>

                    <h1 class="area-title">

                        Area:
                        <?= htmlspecialchars($data_area['nama_area']); ?>

                    </h1>

                    <div class="area-subtitle">

                        <i class="bi bi-geo-alt me-1"></i>

                        <?= htmlspecialchars($data_area['lokasi']); ?>

                        <span class="mx-1">•</span>

                        Pilih jenis mesin pada area ini

                    </div>

                </div>

            </div>


            <!-- BACK -->

            <a
                href="detail_lokasi.php?lokasi=<?= urlencode($data_area['lokasi']); ?>"
                class="btn btn-outline-secondary area-back-btn"
            >

                <i class="bi bi-arrow-left me-1"></i>

                Kembali

            </a>

        </div>



        <!-- =================================================
             SEARCH
        ================================================== -->

        <div class="area-search-wrapper">

            <form
                method="GET"
                action=""
            >

                <input
                    type="hidden"
                    name="id_area"
                    value="<?= $id_area; ?>"
                >


                <div class="row g-2 area-search-row">

                    <div class="col-md-10">

                        <div class="input-group area-search-input-group">

                            <span class="input-group-text">

                                <i class="bi bi-search"></i>

                            </span>

                            <input
                                type="text"
                                name="keyword"
                                class="form-control area-search-input"
                                placeholder="Cari jenis mesin, nama mesin, serial number, sub mesin, atau komponen..."
                                value="<?= htmlspecialchars($keyword); ?>"
                                autocomplete="off"
                            >

                        </div>

                    </div>


                    <div class="col-md-2 area-search-button-wrapper d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-primary area-search-btn"
                        >

                            <i class="bi bi-search me-1"></i>

                            Cari

                        </button>


                        <?php if ($keyword !== ''): ?>

                            <a
                                href="detail_area.php?id_area=<?= $id_area; ?>"
                                class="btn btn-outline-secondary area-reset-btn"
                                title="Reset Pencarian"
                            >

                                <i class="bi bi-arrow-counterclockwise"></i>

                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </form>

        </div>

    </div>



    <!-- =====================================================
         HASIL
    ====================================================== -->

    <div class="area-result-info">

        <div>

            <div class="area-result-title">

                <i class="bi bi-grid-3x3-gap me-1 text-primary"></i>

                Jenis Mesin

            </div>

            <div class="area-result-subtitle">

                <?php if ($keyword !== ''): ?>

                    Menampilkan hasil pencarian untuk:

                    <strong>
                        "<?= htmlspecialchars($keyword); ?>"
                    </strong>

                <?php else: ?>

                    Daftar jenis mesin yang tersedia di area ini.

                <?php endif; ?>

            </div>

        </div>


        <div class="area-count-badge">

            <?= $total_jenis; ?>

            Jenis Mesin

        </div>

    </div>



    <!-- =====================================================
         GRID JENIS MESIN
    ====================================================== -->

    <div class="row g-3">

        <?php if ($query_jenis && $total_jenis > 0): ?>


            <?php while ($jenis = mysqli_fetch_assoc($query_jenis)): ?>


                <div class="col-12 col-sm-6 col-lg-4 col-xl-3">


                    <a
                        href="detail_jenis.php?id_jenis=<?= intval($jenis['id']); ?>"
                        class="area-machine-card"
                    >

                        <div class="d-flex align-items-center gap-3">


                            <!-- ICON -->

                            <div class="area-machine-icon">

                                <i class="bi bi-folder-fill"></i>

                            </div>


                            <!-- INFO -->

                            <div class="area-machine-info">

                                <h2 class="area-machine-name">

                                    <?= htmlspecialchars(
                                        $jenis['nama_jenis_mesin']
                                    ); ?>

                                </h2>

                                <span class="area-machine-link">

                                    <i class="bi bi-arrow-right-circle me-1"></i>

                                    Lihat Daftar Mesin

                                </span>

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

                <div class="area-empty-card">


                    <div class="area-empty-icon">

                        <?php if ($keyword !== ''): ?>

                            <i class="bi bi-search"></i>

                        <?php else: ?>

                            <i class="bi bi-folder-x"></i>

                        <?php endif; ?>

                    </div>


                    <div class="area-empty-title">

                        <?php if ($keyword !== ''): ?>

                            Data tidak ditemukan

                        <?php else: ?>

                            Belum ada jenis mesin

                        <?php endif; ?>

                    </div>


                    <div class="area-empty-text">

                        <?php if ($keyword !== ''): ?>

                            Tidak ditemukan jenis mesin yang sesuai
                            dengan kata kunci
                            <strong>
                                "<?= htmlspecialchars($keyword); ?>"
                            </strong>
                            di area ini.

                            <div class="mt-3">

                                <a
                                    href="detail_area.php?id_area=<?= $id_area; ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >

                                    <i class="bi bi-arrow-counterclockwise me-1"></i>

                                    Reset Pencarian

                                </a>

                            </div>

                        <?php else: ?>

                            Belum terdapat jenis mesin yang
                            terdaftar pada area ini.

                        <?php endif; ?>

                    </div>


                </div>

            </div>


        <?php endif; ?>

    </div>


</div>


<?php

/* =========================================================
   TUTUP STATEMENT PENCARIAN
========================================================= */

if (isset($stmt_jenis) && $stmt_jenis) {
    mysqli_stmt_close($stmt_jenis);
}


/* =========================================================
   FOOTER
========================================================= */

include "template/footer.php";
?>