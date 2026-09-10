<?php
include "koneksi.php";
include "template/header.php";

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$kw = "%" . $keyword . "%";

/* =========================================================
   QUERY LOKASI
========================================================= */
if (!empty($keyword)) {

    $stmt_lokasi = mysqli_prepare($conn, "
        SELECT DISTINCT
            CASE
                WHEN lokasi IS NULL OR lokasi = '' THEN 'Lain-lain'
                ELSE lokasi
            END AS lokasi_group
        FROM area_bagian
        WHERE lokasi LIKE ?
           OR nama_area LIKE ?
        ORDER BY lokasi_group ASC
    ");

    mysqli_stmt_bind_param(
        $stmt_lokasi,
        "ss",
        $kw,
        $kw
    );

    mysqli_stmt_execute($stmt_lokasi);
    $query_lokasi = mysqli_stmt_get_result($stmt_lokasi);

} else {

    $query_lokasi = mysqli_query($conn, "
        SELECT DISTINCT
            CASE
                WHEN lokasi IS NULL OR lokasi = '' THEN 'Lain-lain'
                ELSE lokasi
            END AS lokasi_group
        FROM area_bagian
        ORDER BY lokasi_group ASC
    ");
}

/* =========================================================
   HITUNG JUMLAH LOKASI
========================================================= */
$total_lokasi = 0;

if ($query_lokasi) {
    $total_lokasi = mysqli_num_rows($query_lokasi);
}
?>

<style>
/* =========================================================
   HIERARKI PAGE
========================================================= */

.hierarki-page {
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
}

.hierarki-header {
    background: #ffffff;
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 22px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
}

.hierarki-title {
    font-size: 25px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.3;
}

.hierarki-title-icon {
    color: #0056a6;
}

.hierarki-subtitle {
    font-size: 14px;
    color: #6b7280;
    margin-top: 5px;
}

.hierarki-search {
    margin-top: 20px;
}

.hierarki-search .input-group {
    min-height: 44px;
}

.hierarki-search .input-group-text {
    border-color: #dee2e6;
    border-radius: 10px 0 0 10px;
}

.hierarki-search .form-control {
    border-color: #dee2e6;
    min-height: 44px;
    font-size: 14px;
}

.hierarki-search .form-control:focus {
    box-shadow: none;
    border-color: #0056a6;
}

.btn-garuda {
    background: #0056a6;
    border: 1px solid #0056a6;
    color: #ffffff;
    min-height: 44px;
    border-radius: 10px;
    font-weight: 600;
}

.btn-garuda:hover,
.btn-garuda:focus {
    background: #00427f;
    border-color: #00427f;
    color: #ffffff;
}

.btn-reset {
    min-height: 44px;
    min-width: 44px;
    border-radius: 10px;
}

/* =========================================================
   INFO BAR
========================================================= */

.hierarki-info {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 18px;
}

.hierarki-info-title {
    font-size: 15px;
    font-weight: 600;
    color: #374151;
}

.hierarki-count {
    background: #e6f0fa;
    color: #0056a6;
    border-radius: 50px;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

/* =========================================================
   LOCATION CARD
========================================================= */

.location-card-link {
    display: block;
    height: 100%;
    text-decoration: none;
    color: inherit;
}

.location-card {
    height: 100%;
    min-height: 118px;
    background: #ffffff;
    border: 1px solid #e7edf3;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.045);
    transition: all 0.2s ease;
}

.location-card:hover {
    transform: translateY(-3px);
    border-color: #b8d2eb;
    box-shadow: 0 8px 22px rgba(0, 86, 166, 0.10);
}

.location-card-content {
    display: flex;
    align-items: center;
    gap: 15px;
    min-width: 0;
}

.location-icon {
    width: 56px;
    height: 56px;
    min-width: 56px;
    border-radius: 15px;
    background: #e6f0fa;
    color: #0056a6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.location-icon i {
    font-size: 27px;
}

.location-content {
    min-width: 0;
    flex: 1;
}

.location-name {
    color: #1f2937;
    font-size: 16px;
    font-weight: 700;
    line-height: 1.4;
    margin: 0;
    word-break: break-word;
}

.location-description {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #6b7280;
    font-size: 12px;
    margin-top: 6px;
}

.location-arrow {
    color: #0056a6;
    margin-left: auto;
    font-size: 18px;
    flex-shrink: 0;
}

/* =========================================================
   EMPTY STATE
========================================================= */

.hierarki-empty {
    background: #ffffff;
    border-radius: 16px;
    padding: 50px 25px;
    text-align: center;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.045);
}

.hierarki-empty-icon {
    width: 65px;
    height: 65px;
    margin: 0 auto 15px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hierarki-empty-icon i {
    font-size: 28px;
}

.hierarki-empty-title {
    font-size: 16px;
    font-weight: 600;
    color: #374151;
}

.hierarki-empty-text {
    color: #6b7280;
    font-size: 13px;
}

/* =========================================================
   RESPONSIVE TABLET
========================================================= */

@media (max-width: 991.98px) {

    .hierarki-header {
        padding: 20px;
    }

    .hierarki-title {
        font-size: 22px;
    }

    .location-card {
        min-height: 110px;
    }
}

/* =========================================================
   RESPONSIVE MOBILE
========================================================= */

@media (max-width: 767.98px) {

    .hierarki-page {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .hierarki-header {
        padding: 18px;
        border-radius: 14px;
        margin-bottom: 18px;
    }

    .hierarki-title {
        font-size: 19px;
    }

    .hierarki-title i {
        font-size: 19px;
    }

    .hierarki-subtitle {
        font-size: 12px;
    }

    .hierarki-search {
        margin-top: 16px;
    }

    .hierarki-search .row {
        gap: 8px !important;
    }

    .hierarki-search .col-md-10,
    .hierarki-search .col-md-2 {
        width: 100%;
    }

    .hierarki-search .col-md-2 {
        display: flex;
    }

    .btn-garuda {
        flex: 1;
    }

    .hierarki-info {
        margin-bottom: 14px;
    }

    .hierarki-info-title {
        font-size: 14px;
    }

    .location-card {
        min-height: auto;
        padding: 16px;
        border-radius: 14px;
    }

    .location-icon {
        width: 48px;
        height: 48px;
        min-width: 48px;
        border-radius: 12px;
    }

    .location-icon i {
        font-size: 23px;
    }

    .location-name {
        font-size: 14px;
    }

    .location-description {
        font-size: 11px;
    }

    .location-arrow {
        font-size: 16px;
    }

    .hierarki-empty {
        padding: 40px 20px;
    }
}

/* =========================================================
   EXTRA SMALL MOBILE
========================================================= */

@media (max-width: 400px) {

    .hierarki-header {
        padding: 15px;
    }

    .hierarki-title {
        font-size: 17px;
    }

    .hierarki-subtitle {
        font-size: 11px;
    }

    .location-card-content {
        gap: 10px;
    }

    .location-icon {
        width: 44px;
        height: 44px;
        min-width: 44px;
    }

    .location-icon i {
        font-size: 21px;
    }

    .location-name {
        font-size: 13px;
    }

    .location-description {
        font-size: 10px;
    }
}
</style>

<div class="container-fluid mb-4 px-3 py-2">

    <div class="hierarki-page">

        <!-- =====================================================
             HEADER
        ====================================================== -->
        <div class="hierarki-header">

            <div>
                <h2 class="hierarki-title m-0">
                    <i class="bi bi-diagram-3-fill me-2 hierarki-title-icon"></i>
                    Direktori Mesin & Komponen
                </h2>

                <p class="hierarki-subtitle mb-0">
                    Pilih lokasi untuk melihat struktur mesin, sub mesin, dan komponen.
                </p>
            </div>

            <!-- =================================================
                 SEARCH
            ================================================== -->
            <form method="GET" action="" class="hierarki-search">

                <div class="row g-2 align-items-center">

                    <div class="col-md-10">

                        <div class="input-group">

                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>

                            <input
                                type="text"
                                name="keyword"
                                class="form-control border-start-0 ps-0"
                                placeholder="Cari berdasarkan lokasi atau area..."
                                value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
                                autocomplete="off"
                            >

                        </div>

                    </div>

                    <div class="col-md-2 d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-garuda w-100"
                        >
                            <i class="bi bi-search me-1"></i>
                            Cari
                        </button>

                        <?php if (!empty($keyword)) : ?>

                            <a
                                href="hierarki.php"
                                class="btn btn-outline-secondary btn-reset d-flex align-items-center justify-content-center"
                                title="Reset pencarian"
                            >
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </form>

        </div>


        <!-- =====================================================
             INFO
        ====================================================== -->
        <div class="hierarki-info">

            <div class="hierarki-info-title">
                <i class="bi bi-folder2-open me-1"></i>
                Daftar Lokasi
            </div>

            <div class="hierarki-count">
                <?= $total_lokasi; ?> Lokasi
            </div>

        </div>


        <!-- =====================================================
             DAFTAR LOKASI
        ====================================================== -->
        <div class="row g-3">

            <?php if ($query_lokasi && $total_lokasi > 0) : ?>

                <?php while ($lokasi = mysqli_fetch_assoc($query_lokasi)) : ?>

                    <?php
                    $lokasi_name = $lokasi['lokasi_group'];
                    ?>

                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">

                        <a
                            href="detail_lokasi.php?lokasi=<?= urlencode($lokasi_name); ?>"
                            class="location-card-link"
                        >

                            <div class="location-card">

                                <div class="location-card-content">

                                    <!-- ICON -->
                                    <div class="location-icon">
                                        <i class="bi bi-folder-fill"></i>
                                    </div>

                                    <!-- CONTENT -->
                                    <div class="location-content">

                                        <h5 class="location-name">
                                            <?= htmlspecialchars($lokasi_name, ENT_QUOTES, 'UTF-8'); ?>
                                        </h5>

                                        <div class="location-description">
                                            <i class="bi bi-arrow-right-circle"></i>
                                            <span>Lihat Area & Mesin</span>
                                        </div>

                                    </div>

                                    <!-- ARROW -->
                                    <div class="location-arrow">
                                        <i class="bi bi-chevron-right"></i>
                                    </div>

                                </div>

                            </div>

                        </a>

                    </div>

                <?php endwhile; ?>

            <?php else : ?>

                <!-- =================================================
                     EMPTY STATE
                ================================================== -->
                <div class="col-12">

                    <div class="hierarki-empty">

                        <div class="hierarki-empty-icon">
                            <i class="bi bi-folder-x"></i>
                        </div>

                        <div class="hierarki-empty-title">
                            Lokasi Tidak Ditemukan
                        </div>

                        <p class="hierarki-empty-text mb-3">

                            <?php if (!empty($keyword)) : ?>

                                Tidak ditemukan lokasi atau area dengan kata kunci
                                <strong>
                                    "<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
                                </strong>.

                            <?php else : ?>

                                Belum ada data lokasi yang tersedia.

                            <?php endif; ?>

                        </p>

                        <?php if (!empty($keyword)) : ?>

                            <a
                                href="hierarki.php"
                                class="btn btn-outline-primary btn-sm"
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

</div>

<?php include "template/footer.php"; ?>