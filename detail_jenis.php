<?php
require_once __DIR__ . '/template/auth.php';
require_authenticated();

include "koneksi.php";

/* =========================================================
   PARAMETER
========================================================= */

$id_jenis = isset($_GET['id_jenis']) ? intval($_GET['id_jenis']) : 0;
$keyword  = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

$kw = "%" . $keyword . "%";


/* =========================================================
   VALIDASI ID
========================================================= */

if ($id_jenis <= 0) {
    header("Location: hierarki.php");
    exit;
}


/* =========================================================
   AMBIL INFORMASI JENIS MESIN
========================================================= */

$stmt_info = mysqli_prepare($conn, "
    SELECT
        jm.id,
        jm.nama_jenis_mesin,
        jm.id_area,
        a.nama_area,
        a.lokasi
    FROM jenis_mesin jm
    LEFT JOIN area_bagian a
        ON jm.id_area = a.id
    WHERE jm.id = ?
    LIMIT 1
");

if (!$stmt_info) {
    die("Query informasi jenis mesin gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt_info, "i", $id_jenis);
mysqli_stmt_execute($stmt_info);

$result_info = mysqli_stmt_get_result($stmt_info);
$data_jenis  = mysqli_fetch_assoc($result_info);

mysqli_stmt_close($stmt_info);


if (!$data_jenis) {
    header("Location: hierarki.php");
    exit;
}


/* =========================================================
   AMBIL DATA MESIN
========================================================= */

$data_mesin = [];


if ($keyword !== '') {

    $stmt_mesin = mysqli_prepare($conn, "
        SELECT DISTINCT
            m.id,
            m.nama_mesin,
            m.serial_number,
            m.gambar,
            m.keterangan
        FROM mesin m

        LEFT JOIN sub_mesin sm
            ON sm.id_mesin = m.id

        LEFT JOIN komponen k
            ON k.id_sub_mesin = sm.id

        WHERE
            m.id_jenis_mesin = ?
            AND (
                m.nama_mesin LIKE ?
                OR m.serial_number LIKE ?
                OR sm.nama_sub_mesin LIKE ?
                OR k.nama_bagian LIKE ?
                OR k.serial_number LIKE ?
            )

        ORDER BY m.nama_mesin ASC
    ");

    if (!$stmt_mesin) {
        die("Query mesin gagal: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt_mesin,
        "isssss",
        $id_jenis,
        $kw,
        $kw,
        $kw,
        $kw,
        $kw
    );

    mysqli_stmt_execute($stmt_mesin);

    $result_mesin = mysqli_stmt_get_result($stmt_mesin);

    while ($row = mysqli_fetch_assoc($result_mesin)) {
        $data_mesin[] = $row;
    }

    mysqli_stmt_close($stmt_mesin);

} else {

    $stmt_mesin = mysqli_prepare($conn, "
        SELECT
            id,
            nama_mesin,
            serial_number,
            gambar,
            keterangan
        FROM mesin
        WHERE id_jenis_mesin = ?
        ORDER BY nama_mesin ASC
    ");

    if (!$stmt_mesin) {
        die("Query mesin gagal: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt_mesin, "i", $id_jenis);
    mysqli_stmt_execute($stmt_mesin);

    $result_mesin = mysqli_stmt_get_result($stmt_mesin);

    while ($row = mysqli_fetch_assoc($result_mesin)) {
        $data_mesin[] = $row;
    }

    mysqli_stmt_close($stmt_mesin);
}


/* =========================================================
   TOTAL MESIN
========================================================= */

$total_mesin = count($data_mesin);


/* =========================================================
   LOAD HEADER
========================================================= */

include "template/header.php";
?>


<style>

/* =========================================================
   PAGE
========================================================= */

.jenis-page {
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
}


/* =========================================================
   BREADCRUMB
========================================================= */

.jenis-breadcrumb-wrapper {
    margin-bottom: 18px;
}

.jenis-breadcrumb {
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    font-size: 12px;
}

.jenis-breadcrumb .breadcrumb-item {
    max-width: 100%;
}

.jenis-breadcrumb a {
    color: #005baa;
    font-weight: 500;
}

.jenis-breadcrumb .active {
    color: #64748b;
    font-weight: 600;
}


/* =========================================================
   HEADER CARD
========================================================= */

.jenis-header-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 18px;

    padding: 22px;

    margin-bottom: 20px;

    box-shadow:
        0 4px 15px rgba(15, 23, 42, .04);
}


/* =========================================================
   TITLE
========================================================= */

.jenis-title-wrapper {
    min-width: 0;
}

.jenis-title {

    color: #172033;

    font-size: 24px;

    line-height: 1.3;

    font-weight: 800;

    margin: 0;

    word-break: break-word;
}

.jenis-title-icon {

    width: 42px;

    height: 42px;

    border-radius: 11px;

    background: #eef5ff;

    color: #005baa;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    flex-shrink: 0;

    font-size: 20px;
}

.jenis-subtitle {

    color: #64748b;

    font-size: 13px;

    margin-top: 5px;
}


/* =========================================================
   BACK BUTTON
========================================================= */

.jenis-back-btn {

    border-radius: 10px;

    font-size: 13px;

    font-weight: 600;

    padding: 9px 14px;

    white-space: nowrap;
}


/* =========================================================
   SEARCH
========================================================= */

.jenis-search-wrapper {
    margin-top: 20px;
}

.jenis-search-group {

    border: 1px solid #dbe3ea;

    border-radius: 10px;

    overflow: hidden;

    background: #ffffff;

    transition: .2s;
}

.jenis-search-group:focus-within {

    border-color: #005baa;

    box-shadow:
        0 0 0 3px rgba(0, 91, 170, .08);
}

.jenis-search-icon {

    background: #f8fafc;

    border: none;

    color: #64748b;

    padding-left: 14px;

    padding-right: 10px;
}

.jenis-search-input {

    border: none !important;

    box-shadow: none !important;

    font-size: 13px;

    min-height: 42px;
}

.jenis-search-button {

    background: #005baa;

    border-color: #005baa;

    min-height: 42px;

    border-radius: 9px !important;

    font-size: 13px;

    font-weight: 600;
}

.jenis-reset-button {

    min-height: 42px;

    border-radius: 9px !important;

}


/* =========================================================
   SUMMARY
========================================================= */

.jenis-summary {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    margin-bottom: 15px;

    flex-wrap: wrap;
}

.jenis-section-title {

    color: #172033;

    font-size: 17px;

    font-weight: 750;

    margin: 0;
}

.jenis-section-subtitle {

    color: #94a3b8;

    font-size: 12px;

    margin-top: 2px;
}

.jenis-count-badge {

    background: #eef5ff;

    color: #005baa;

    border: 1px solid #dbeafe;

    border-radius: 999px;

    padding: 6px 11px;

    font-size: 11px;

    font-weight: 700;

    white-space: nowrap;
}


/* =========================================================
   MACHINE CARD
========================================================= */

.machine-card-link {

    text-decoration: none;

    display: block;

    height: 100%;
}

.machine-card {

    height: 100%;

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    padding: 18px;

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        border-color .2s ease;

    position: relative;

    overflow: hidden;
}

.machine-card:hover {

    transform: translateY(-3px);

    border-color: #bfdbfe;

    box-shadow:
        0 10px 28px rgba(15, 23, 42, .08);
}

.machine-card::after {

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


/* =========================================================
   MACHINE ICON
========================================================= */

.machine-icon {

    width: 58px;

    height: 58px;

    border-radius: 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eef8ff;

    color: #0084bd;

    font-size: 25px;

    flex-shrink: 0;

    border: 1px solid #d9f0fb;
}


/* =========================================================
   MACHINE INFO
========================================================= */

.machine-info {

    min-width: 0;

    flex: 1;
}

.machine-name {

    color: #172033;

    font-size: 15px;

    line-height: 1.4;

    font-weight: 750;

    margin: 0;

    word-break: break-word;
}

.machine-serial {

    margin-top: 5px;

    font-size: 11px;

    color: #64748b;

    word-break: break-all;
}

.machine-serial i {
    color: #94a3b8;
}

.machine-link-text {

    margin-top: 8px;

    color: #005baa;

    font-size: 11px;

    font-weight: 600;
}

.machine-link-text i {

    transition: transform .2s;
}

.machine-card:hover .machine-link-text i {

    transform: translateX(3px);
}


/* =========================================================
   EMPTY STATE
========================================================= */

.jenis-empty {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    padding: 60px 20px;

    text-align: center;

    box-shadow:
        0 4px 15px rgba(15, 23, 42, .03);
}

.jenis-empty-icon {

    width: 68px;

    height: 68px;

    margin: 0 auto 15px;

    border-radius: 18px;

    background: #f1f5f9;

    color: #94a3b8;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 28px;
}

.jenis-empty-title {

    color: #475569;

    font-size: 15px;

    font-weight: 700;

    margin-bottom: 4px;
}

.jenis-empty-text {

    color: #94a3b8;

    font-size: 12px;

    max-width: 500px;

    margin: 0 auto;
}


/* =========================================================
   RESPONSIVE TABLET
========================================================= */

@media (max-width: 992px) {

    .jenis-title {
        font-size: 21px;
    }

    .jenis-header-card {
        padding: 18px;
    }

    .machine-card {
        padding: 16px;
    }

}


/* =========================================================
   RESPONSIVE MOBILE
========================================================= */

@media (max-width: 768px) {

    .jenis-page {
        padding-left: 2px;
        padding-right: 2px;
    }

    .jenis-breadcrumb {
        font-size: 11px;
        line-height: 1.7;
    }

    .jenis-header-card {
        border-radius: 14px;
        padding: 15px;
    }

    .jenis-title {
        font-size: 19px;
    }

    .jenis-title-icon {
        width: 38px;
        height: 38px;
        font-size: 18px;
    }

    .jenis-subtitle {
        font-size: 12px;
    }

    .jenis-header-top {
        align-items: flex-start !important;
    }

    .jenis-back-btn {
        width: 100%;
        justify-content: center;
    }

    .jenis-search-wrapper {
        margin-top: 15px;
    }

    .jenis-search-input {
        font-size: 12px;
    }

    .jenis-search-button,
    .jenis-reset-button {
        width: 100%;
    }

    .jenis-summary {
        margin-bottom: 12px;
    }

    .jenis-section-title {
        font-size: 15px;
    }

    .machine-card {
        padding: 15px;
        border-radius: 14px;
    }

    .machine-icon {
        width: 50px;
        height: 50px;
        border-radius: 13px;
        font-size: 22px;
    }

    .machine-name {
        font-size: 14px;
    }

}


/* =========================================================
   EXTRA SMALL MOBILE
========================================================= */

@media (max-width: 480px) {

    .jenis-title-row {
        align-items: flex-start !important;
    }

    .jenis-title {
        font-size: 17px;
    }

    .jenis-title-icon {
        width: 35px;
        height: 35px;
        font-size: 16px;
    }

    .machine-card {
        padding: 13px;
    }

    .machine-icon {
        width: 46px;
        height: 46px;
        font-size: 20px;
    }

    .machine-name {
        font-size: 13px;
    }

    .machine-link-text {
        font-size: 10px;
    }

    .jenis-empty {
        padding: 45px 15px;
    }

}

</style>


<div class="container-fluid mb-4 px-3 py-2 jenis-page">


    <!-- =====================================================
         BREADCRUMB
    ====================================================== -->

    <div class="jenis-breadcrumb-wrapper">

        <nav aria-label="breadcrumb">

            <ol class="breadcrumb jenis-breadcrumb">

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
                        href="detail_lokasi.php?lokasi=<?= urlencode($data_jenis['lokasi'] ?? '') ?>"
                        class="text-decoration-none"
                    >

                        Lokasi:
                        <?= htmlspecialchars(
                            $data_jenis['lokasi'] ?? '-'
                        ) ?>

                    </a>

                </li>


                <li class="breadcrumb-item">

                    <a
                        href="detail_area.php?id_area=<?= intval($data_jenis['id_area']) ?>"
                        class="text-decoration-none"
                    >

                        Area:
                        <?= htmlspecialchars(
                            $data_jenis['nama_area'] ?? '-'
                        ) ?>

                    </a>

                </li>


                <li
                    class="breadcrumb-item active"
                    aria-current="page"
                >

                    Jenis:
                    <?= htmlspecialchars(
                        $data_jenis['nama_jenis_mesin'] ?? '-'
                    ) ?>

                </li>

            </ol>

        </nav>

    </div>



    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="jenis-header-card">


        <div
            class="d-flex justify-content-between align-items-center gap-3 flex-wrap jenis-header-top"
        >


            <!-- TITLE -->

            <div class="jenis-title-wrapper flex-grow-1">

                <div class="d-flex align-items-center gap-3 jenis-title-row">

                    <div class="jenis-title-icon">

                        <i class="bi bi-folder2-open"></i>

                    </div>


                    <div>

                        <h1 class="jenis-title">

                            Jenis Mesin:
                            <?= htmlspecialchars(
                                $data_jenis['nama_jenis_mesin'] ?? '-'
                            ) ?>

                        </h1>


                        <div class="jenis-subtitle">

                            <i class="bi bi-geo-alt me-1"></i>

                            <?= htmlspecialchars(
                                $data_jenis['nama_area'] ?? '-'
                            ) ?>

                            &nbsp;•&nbsp;

                            <?= htmlspecialchars(
                                $data_jenis['lokasi'] ?? '-'
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>


            <!-- BACK -->

            <a
                href="detail_area.php?id_area=<?= intval($data_jenis['id_area']) ?>"
                class="btn btn-outline-secondary jenis-back-btn d-inline-flex align-items-center justify-content-center"
            >

                <i class="bi bi-arrow-left me-1"></i>

                Kembali

            </a>

        </div>



        <!-- =================================================
             SEARCH
        ================================================== -->

        <div class="jenis-search-wrapper">

            <form
                method="GET"
                action=""
            >

                <input
                    type="hidden"
                    name="id_jenis"
                    value="<?= $id_jenis ?>"
                >


                <div class="row g-2">


                    <!-- INPUT -->

                    <div class="col-lg-10">

                        <div class="input-group jenis-search-group">

                            <span class="input-group-text jenis-search-icon">

                                <i class="bi bi-search"></i>

                            </span>


                            <input
                                type="text"
                                name="keyword"
                                class="form-control jenis-search-input"
                                placeholder="Cari nama mesin, serial number, sub mesin, atau komponen..."
                                value="<?= htmlspecialchars($keyword) ?>"
                                autocomplete="off"
                            >

                        </div>

                    </div>


                    <!-- BUTTON -->

                    <div class="col-lg-2">

                        <div class="d-flex gap-2">


                            <button
                                type="submit"
                                class="btn btn-primary jenis-search-button flex-grow-1"
                            >

                                <i class="bi bi-search me-1"></i>

                                Cari

                            </button>


                            <?php if ($keyword !== ''): ?>

                                <a
                                    href="detail_jenis.php?id_jenis=<?= $id_jenis ?>"
                                    class="btn btn-outline-secondary jenis-reset-button d-flex align-items-center justify-content-center"
                                    title="Reset pencarian"
                                >

                                    <i class="bi bi-arrow-counterclockwise"></i>

                                </a>

                            <?php endif; ?>


                        </div>

                    </div>


                </div>

            </form>

        </div>

    </div>



    <!-- =====================================================
         SUMMARY
    ====================================================== -->

    <div class="jenis-summary">


        <div>

            <h2 class="jenis-section-title">

                <i class="bi bi-cpu me-1 text-primary"></i>

                Daftar Mesin

            </h2>


            <div class="jenis-section-subtitle">

                Mesin yang terdaftar dalam jenis mesin ini.

                <?php if ($keyword !== ''): ?>

                    Hasil pencarian untuk:

                    <strong>
                        "<?= htmlspecialchars($keyword) ?>"
                    </strong>

                <?php endif; ?>

            </div>

        </div>


        <div class="jenis-count-badge">

            <i class="bi bi-hdd-stack me-1"></i>

            <?= $total_mesin ?>

            Mesin

        </div>


    </div>



    <!-- =====================================================
         MACHINE GRID
    ====================================================== -->

    <?php if (!empty($data_mesin)): ?>

        <div class="row g-3">


            <?php foreach ($data_mesin as $mesin): ?>


                <div class="col-12 col-md-6 col-xl-4">


                    <a
                        href="detail_mesin.php?id_mesin=<?= intval($mesin['id']) ?>"
                        class="machine-card-link"
                    >


                        <div class="machine-card">


                            <div class="d-flex align-items-start gap-3">


                                <!-- ICON -->

                                <div class="machine-icon">

                                    <i class="bi bi-cpu"></i>

                                </div>


                                <!-- INFO -->

                                <div class="machine-info">


                                    <h3 class="machine-name">

                                        <?= htmlspecialchars(
                                            $mesin['nama_mesin'] ?? '-'
                                        ) ?>

                                    </h3>


                                    <?php if (!empty($mesin['serial_number'])): ?>

                                        <div class="machine-serial">

                                            <i class="bi bi-upc-scan me-1"></i>

                                            SN:

                                            <span class="font-monospace">

                                                <?= htmlspecialchars(
                                                    $mesin['serial_number']
                                                ) ?>

                                            </span>

                                        </div>

                                    <?php else: ?>

                                        <div class="machine-serial">

                                            <i class="bi bi-upc-scan me-1"></i>

                                            Serial number belum tersedia

                                        </div>

                                    <?php endif; ?>


                                    <div class="machine-link-text">

                                        Lihat Sub Mesin & Komponen

                                        <i class="bi bi-arrow-right-circle ms-1"></i>

                                    </div>


                                </div>


                            </div>


                        </div>


                    </a>


                </div>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <!-- =================================================
             EMPTY STATE
        ================================================== -->

        <div class="jenis-empty">


            <div class="jenis-empty-icon">

                <i class="bi bi-search"></i>

            </div>


            <div class="jenis-empty-title">

                <?php if ($keyword !== ''): ?>

                    Mesin Tidak Ditemukan

                <?php else: ?>

                    Belum Ada Mesin

                <?php endif; ?>

            </div>


            <p class="jenis-empty-text">

                <?php if ($keyword !== ''): ?>

                    Tidak ditemukan mesin yang sesuai dengan kata kunci
                    <strong>
                        "<?= htmlspecialchars($keyword) ?>"
                    </strong>
                    pada jenis mesin ini.

                    <br>

                    Coba gunakan kata kunci lain.

                <?php else: ?>

                    Belum terdapat data mesin yang terdaftar
                    pada jenis mesin ini.

                <?php endif; ?>

            </p>


            <?php if ($keyword !== ''): ?>

                <a
                    href="detail_jenis.php?id_jenis=<?= $id_jenis ?>"
                    class="btn btn-sm btn-outline-primary mt-2"
                >

                    <i class="bi bi-arrow-counterclockwise me-1"></i>

                    Reset Pencarian

                </a>

            <?php endif; ?>


        </div>


    <?php endif; ?>


</div>


<?php include "template/footer.php"; ?>