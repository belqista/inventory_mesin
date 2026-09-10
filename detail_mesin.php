<?php
require_once __DIR__ . '/template/auth.php';
require_authenticated();

include "koneksi.php";
include "template/header.php";

/* =========================================================
   PARAMETER
========================================================= */
$id_mesin = isset($_GET['id_mesin']) ? intval($_GET['id_mesin']) : 0;
$keyword  = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$kw       = "%" . $keyword . "%";

if ($id_mesin <= 0) {
    echo "<script>window.location='hierarki.php';</script>";
    exit;
}

/* =========================================================
   AMBIL DATA MESIN + JENIS + AREA + LOKASI
========================================================= */
$stmt_info = mysqli_prepare($conn, "
    SELECT 
        m.*,
        jm.id AS id_jenis,
        jm.nama_jenis_mesin,
        a.id AS id_area,
        a.nama_area,
        a.lokasi
    FROM mesin m
    INNER JOIN jenis_mesin jm 
        ON m.id_jenis_mesin = jm.id
    INNER JOIN area_bagian a 
        ON jm.id_area = a.id
    WHERE m.id = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt_info, "i", $id_mesin);
mysqli_stmt_execute($stmt_info);

$result_info = mysqli_stmt_get_result($stmt_info);
$data_mesin  = mysqli_fetch_assoc($result_info);

mysqli_stmt_close($stmt_info);

if (!$data_mesin) {
    echo "<script>
        alert('Data mesin tidak ditemukan!');
        window.location='hierarki.php';
    </script>";
    exit;
}

/* =========================================================
   QUERY SUB MESIN
   SEARCH:
   - Nama Sub Mesin
   - Nama Komponen
   - Serial Number Komponen
   - Kategori Komponen
========================================================= */

if (!empty($keyword)) {

    $q_sub_str = "
        SELECT DISTINCT sm.*
        FROM sub_mesin sm
        LEFT JOIN komponen k 
            ON k.id_sub_mesin = sm.id
        WHERE 
            sm.id_mesin = ?
            AND (
                sm.nama_sub_mesin LIKE ?
                OR k.nama_bagian LIKE ?
                OR k.serial_number LIKE ?
                OR k.kategori LIKE ?
            )
        ORDER BY sm.nama_sub_mesin ASC
    ";

    $stmt_s = mysqli_prepare($conn, $q_sub_str);

    mysqli_stmt_bind_param(
        $stmt_s,
        "issss",
        $id_mesin,
        $kw,
        $kw,
        $kw,
        $kw
    );

    mysqli_stmt_execute($stmt_s);

    $query_sub = mysqli_stmt_get_result($stmt_s);

} else {

    $stmt_s = mysqli_prepare($conn, "
        SELECT *
        FROM sub_mesin
        WHERE id_mesin = ?
        ORDER BY nama_sub_mesin ASC
    ");

    mysqli_stmt_bind_param($stmt_s, "i", $id_mesin);
    mysqli_stmt_execute($stmt_s);

    $query_sub = mysqli_stmt_get_result($stmt_s);
}
?>

<style>
/* =========================================================
   DETAIL MESIN - RESPONSIVE
========================================================= */

.detail-page {
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
}

.detail-breadcrumb {
    overflow-x: auto;
    white-space: nowrap;
    scrollbar-width: thin;
}

.detail-breadcrumb .breadcrumb {
    margin-bottom: 0;
    flex-wrap: nowrap;
}

.machine-header-card {
    border: 0;
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
}

.machine-icon {
    width: 64px;
    height: 64px;
    min-width: 64px;
    border-radius: 16px;
    background: #eaf3ff;
    color: #0056a6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
}

.machine-title {
    font-size: 1.45rem;
    line-height: 1.3;
    word-break: break-word;
}

.machine-info {
    font-size: 0.82rem;
}

.search-box {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 5px;
}

.search-box .input-group-text {
    border: 0;
    background: transparent;
}

.search-box .form-control {
    border: 0;
    background: transparent;
    box-shadow: none;
}

.search-box .form-control:focus {
    box-shadow: none;
}

.sub-machine-card {
    border: 1px solid #ffe8a1;
    border-radius: 18px;
    background: #ffffff;
    padding: 20px;
    height: 100%;
    transition: all 0.2s ease;
}

.sub-machine-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.08);
    border-color: #ffd666;
}

.sub-machine-icon {
    width: 58px;
    height: 58px;
    min-width: 58px;
    border-radius: 50%;
    background: #fff8e1;
    color: #d39e00;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sub-machine-name {
    font-size: 1rem;
    line-height: 1.35;
    word-break: break-word;
}

.empty-card {
    border-radius: 18px;
    min-height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.back-button {
    white-space: nowrap;
}

/* =========================================================
   TABLET
========================================================= */

@media (max-width: 991.98px) {

    .machine-title {
        font-size: 1.25rem;
    }

    .machine-header-card {
        padding: 20px !important;
    }

    .search-button-wrapper {
        width: 100%;
    }

    .search-button-wrapper .btn {
        flex: 1;
    }
}

/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 767.98px) {

    .detail-page {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .detail-breadcrumb {
        margin-left: 2px;
        margin-right: 2px;
    }

    .detail-breadcrumb .breadcrumb {
        font-size: 0.75rem;
    }

    .machine-header-card {
        padding: 16px !important;
        border-radius: 14px;
    }

    .machine-header-top {
        align-items: flex-start !important;
        gap: 12px;
    }

    .machine-icon {
        width: 50px;
        height: 50px;
        min-width: 50px;
        border-radius: 13px;
        font-size: 23px;
    }

    .machine-title {
        font-size: 1.05rem;
    }

    .machine-info {
        font-size: 0.75rem;
    }

    .back-button {
        width: 100%;
        margin-top: 12px;
    }

    .search-box {
        padding: 3px;
    }

    .search-button-wrapper {
        display: flex;
        gap: 8px;
    }

    .search-button-wrapper .btn {
        width: auto !important;
    }

    .search-button-wrapper .btn:first-child {
        flex: 1;
    }

    .sub-machine-card {
        padding: 16px;
        border-radius: 15px;
    }

    .sub-machine-icon {
        width: 50px;
        height: 50px;
        min-width: 50px;
    }

    .sub-machine-icon i {
        font-size: 1.5rem !important;
    }

    .sub-machine-name {
        font-size: 0.92rem;
    }

    .sub-machine-card small {
        font-size: 0.72rem;
    }

    .empty-card {
        min-height: 150px;
        padding: 25px !important;
    }
}

/* =========================================================
   EXTRA SMALL MOBILE
========================================================= */

@media (max-width: 400px) {

    .machine-title {
        font-size: 0.98rem;
    }

    .machine-icon {
        width: 45px;
        height: 45px;
        min-width: 45px;
    }

    .machine-header-card {
        padding: 13px !important;
    }

    .sub-machine-card {
        padding: 13px;
    }
}
</style>


<div class="container-fluid mb-4 px-3 py-2 detail-page">

    <!-- =====================================================
         BREADCRUMB
    ====================================================== -->
    <nav aria-label="breadcrumb" class="mb-3 detail-breadcrumb">

        <ol class="breadcrumb">

            <li class="breadcrumb-item">
                <a href="hierarki.php" class="text-decoration-none">
                    <i class="bi bi-house-door me-1"></i>
                    Direktori Utama
                </a>
            </li>

            <li class="breadcrumb-item">
                <a href="detail_lokasi.php?lokasi=<?= urlencode($data_mesin['lokasi']); ?>"
                   class="text-decoration-none">
                    <?= htmlspecialchars($data_mesin['lokasi']); ?>
                </a>
            </li>

            <li class="breadcrumb-item">
                <a href="detail_area.php?id_area=<?= $data_mesin['id_area']; ?>"
                   class="text-decoration-none">
                    <?= htmlspecialchars($data_mesin['nama_area']); ?>
                </a>
            </li>

            <li class="breadcrumb-item">
                <a href="detail_jenis.php?id_jenis=<?= $data_mesin['id_jenis']; ?>"
                   class="text-decoration-none">
                    <?= htmlspecialchars($data_mesin['nama_jenis_mesin']); ?>
                </a>
            </li>

            <li class="breadcrumb-item active"
                aria-current="page">

                <?= htmlspecialchars($data_mesin['nama_mesin']); ?>

            </li>

        </ol>

    </nav>


    <!-- =====================================================
         HEADER MESIN + SEARCH
    ====================================================== -->

    <div class="card machine-header-card p-4 mb-4">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-start machine-header-top mb-4">

            <div class="d-flex align-items-center gap-3 flex-grow-1">

                <div class="machine-icon">
                    <i class="bi bi-cpu"></i>
                </div>

                <div class="min-width-0">

                    <h2 class="fw-bold text-dark m-0 machine-title">

                        Mesin:
                        <?= htmlspecialchars($data_mesin['nama_mesin']); ?>

                    </h2>

                    <?php if (!empty($data_mesin['serial_number'])) : ?>

                        <div class="machine-info mt-1">

                            <span class="text-muted">
                                <i class="bi bi-upc-scan me-1"></i>
                                Serial Number:
                            </span>

                            <code>
                                <?= htmlspecialchars($data_mesin['serial_number']); ?>
                            </code>

                        </div>

                    <?php endif; ?>

                    <p class="text-muted small m-0 mt-1">

                        Pilih Sub Mesin di dalam mesin ini

                    </p>

                </div>

            </div>


            <!-- TOMBOL KEMBALI -->

            <a href="detail_jenis.php?id_jenis=<?= $data_mesin['id_jenis']; ?>"
               class="btn btn-outline-secondary btn-sm back-button">

                <i class="bi bi-arrow-left me-1"></i>
                Kembali

            </a>

        </div>


        <!-- =================================================
             SEARCH
        ================================================== -->

        <form method="GET"
              action=""
              class="row g-2 align-items-center">

            <input type="hidden"
                   name="id_mesin"
                   value="<?= $id_mesin; ?>">


            <div class="col-lg-10 col-md-9 col-12">

                <div class="input-group search-box">

                    <span class="input-group-text">

                        <i class="bi bi-search text-muted"></i>

                    </span>

                    <input type="text"
                           name="keyword"
                           class="form-control"
                           placeholder="Cari Sub Mesin atau Komponen..."
                           value="<?= htmlspecialchars($keyword); ?>">

                </div>

            </div>


            <div class="col-lg-2 col-md-3 col-12 search-button-wrapper d-flex gap-2">

                <button type="submit"
                        class="btn btn-primary fw-semibold"
                        style="background-color:#0056a6;border:none;">

                    <i class="bi bi-search me-1"></i>
                    Cari

                </button>


                <?php if (!empty($keyword)) : ?>

                    <a href="detail_mesin.php?id_mesin=<?= $id_mesin; ?>"
                       class="btn btn-outline-secondary"
                       title="Reset pencarian">

                        <i class="bi bi-arrow-counterclockwise"></i>

                    </a>

                <?php endif; ?>

            </div>

        </form>

    </div>


    <!-- =====================================================
         JUDUL SUB MESIN
    ====================================================== -->

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h5 class="fw-bold mb-1">

                <i class="bi bi-diagram-3-fill me-2"
                   style="color:#0056a6;"></i>

                Daftar Sub Mesin

            </h5>

            <small class="text-muted">

                <?php
                $jumlah_sub = $query_sub
                    ? mysqli_num_rows($query_sub)
                    : 0;
                ?>

                <?= $jumlah_sub; ?> sub mesin ditemukan

            </small>

        </div>

    </div>


    <!-- =====================================================
         GRID SUB MESIN
    ====================================================== -->

    <div class="row g-3">

        <?php if ($query_sub && mysqli_num_rows($query_sub) > 0) : ?>

            <?php while ($sub = mysqli_fetch_assoc($query_sub)) : ?>

                <div class="col-xl-4 col-lg-4 col-md-6 col-12">

                    <a href="detail_sub_mesin.php?id_sub=<?= $sub['id']; ?>"
                       class="text-decoration-none d-block h-100">

                        <div class="sub-machine-card">

                            <div class="d-flex align-items-center gap-3">

                                <!-- ICON -->

                                <div class="sub-machine-icon">

                                    <i class="bi bi-folder-fill fs-3"></i>

                                </div>


                                <!-- INFO -->

                                <div class="flex-grow-1 min-width-0">

                                    <h5 class="fw-bold text-dark m-0 sub-machine-name">

                                        <?= htmlspecialchars($sub['nama_sub_mesin']); ?>

                                    </h5>

                                    <small class="text-muted d-block mt-1">

                                        <i class="bi bi-arrow-right-circle me-1"></i>

                                        Lihat Komponen

                                    </small>

                                </div>


                                <!-- ARROW -->

                                <i class="bi bi-chevron-right text-muted"></i>

                            </div>

                        </div>

                    </a>

                </div>

            <?php endwhile; ?>

        <?php else : ?>

            <!-- =================================================
                 DATA KOSONG
            ================================================== -->

            <div class="col-12">

                <div class="card border-0 shadow-sm rounded-4 bg-white empty-card">

                    <div class="text-center">

                        <div class="mb-3">

                            <i class="bi bi-folder-x text-muted"
                               style="font-size:3rem;"></i>

                        </div>

                        <h6 class="fw-bold text-dark">

                            Sub Mesin Tidak Ditemukan

                        </h6>

                        <p class="text-muted small mb-0">

                            <?php if (!empty($keyword)) : ?>

                                Tidak ada sub mesin atau komponen
                                yang sesuai dengan kata kunci
                                <strong>
                                    "<?= htmlspecialchars($keyword); ?>"
                                </strong>.

                            <?php else : ?>

                                Belum terdapat sub mesin
                                pada mesin ini.

                            <?php endif; ?>

                        </p>

                    </div>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>


<?php include "template/footer.php"; ?>