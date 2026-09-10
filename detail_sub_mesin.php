<?php
require_once __DIR__ . '/template/auth.php';
require_authenticated();

include "koneksi.php";
include "template/header.php";

/* =========================================================
   PARAMETER
========================================================= */
$id_sub  = isset($_GET['id_sub']) ? intval($_GET['id_sub']) : 0;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$kw      = "%" . $keyword . "%";

if ($id_sub <= 0) {
    echo "<script>window.location='hierarki.php';</script>";
    exit;
}

/* =========================================================
   AMBIL DATA SUB MESIN
   + MESIN
   + JENIS MESIN
   + AREA
   + LOKASI
========================================================= */

$stmt_info = mysqli_prepare($conn, "
    SELECT 
        sm.*,
        m.id AS id_mesin,
        m.nama_mesin,
        m.serial_number AS sn_mesin,
        jm.id AS id_jenis,
        jm.nama_jenis_mesin,
        a.id AS id_area,
        a.nama_area,
        a.lokasi
    FROM sub_mesin sm
    INNER JOIN mesin m 
        ON sm.id_mesin = m.id
    INNER JOIN jenis_mesin jm 
        ON m.id_jenis_mesin = jm.id
    INNER JOIN area_bagian a 
        ON jm.id_area = a.id
    WHERE sm.id = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt_info, "i", $id_sub);
mysqli_stmt_execute($stmt_info);

$result_info = mysqli_stmt_get_result($stmt_info);
$data_sub    = mysqli_fetch_assoc($result_info);

mysqli_stmt_close($stmt_info);

if (!$data_sub) {
    echo "<script>
        alert('Data sub mesin tidak ditemukan!');
        window.location='hierarki.php';
    </script>";
    exit;
}


/* =========================================================
   QUERY KOMPONEN
========================================================= */

if (!empty($keyword)) {

    $q_k_str = "
        SELECT *
        FROM komponen
        WHERE 
            id_sub_mesin = ?
            AND (
                nama_bagian LIKE ?
                OR serial_number LIKE ?
                OR kategori LIKE ?
                OR brand LIKE ?
                OR tipe LIKE ?
            )
        ORDER BY nama_bagian ASC
    ";

    $stmt_k = mysqli_prepare($conn, $q_k_str);

    mysqli_stmt_bind_param(
        $stmt_k,
        "isssss",
        $id_sub,
        $kw,
        $kw,
        $kw,
        $kw,
        $kw
    );

    mysqli_stmt_execute($stmt_k);

    $query_komponen = mysqli_stmt_get_result($stmt_k);

} else {

    $stmt_k = mysqli_prepare($conn, "
        SELECT *
        FROM komponen
        WHERE id_sub_mesin = ?
        ORDER BY nama_bagian ASC
    ");

    mysqli_stmt_bind_param($stmt_k, "i", $id_sub);
    mysqli_stmt_execute($stmt_k);

    $query_komponen = mysqli_stmt_get_result($stmt_k);
}


/* =========================================================
   JUMLAH KOMPONEN
========================================================= */

$jumlah_komponen = $query_komponen
    ? mysqli_num_rows($query_komponen)
    : 0;

?>

<style>

/* =========================================================
   DETAIL SUB MESIN
========================================================= */

.detail-sub-page {
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
}

/* =========================================================
   BREADCRUMB
========================================================= */

.detail-breadcrumb {
    overflow-x: auto;
    white-space: nowrap;
    scrollbar-width: thin;
}

.detail-breadcrumb .breadcrumb {
    flex-wrap: nowrap;
    margin-bottom: 0;
    font-size: 0.82rem;
}

/* =========================================================
   HEADER CARD
========================================================= */

.sub-header-card {
    border: 0;
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
}

.sub-header-top {
    min-width: 0;
}

.sub-icon {
    width: 64px;
    height: 64px;
    min-width: 64px;
    border-radius: 16px;
    background: #fff8e1;
    color: #d39e00;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
}

.sub-title {
    font-size: 1.4rem;
    line-height: 1.3;
    word-break: break-word;
}

/* =========================================================
   MACHINE BADGE
========================================================= */

.machine-badge {
    display: inline-flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 3px;
    max-width: 100%;
    font-size: 0.78rem;
}

/* =========================================================
   SEARCH
========================================================= */

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

/* =========================================================
   TABLE CARD
========================================================= */

.component-card {
    border: 0;
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
}

.component-table {
    min-width: 900px;
}

.component-table thead th {
    font-size: 0.78rem;
    font-weight: 700;
    color: #495057;
    white-space: nowrap;
    padding: 13px 12px;
    border-bottom: 1px solid #dee2e6;
}

.component-table tbody td {
    font-size: 0.82rem;
    padding: 13px 12px;
    vertical-align: middle;
}

.component-table tbody tr {
    transition: background 0.15s ease;
}

.component-table tbody tr:hover {
    background: #f8fbff;
}

/* =========================================================
   COMPONENT NAME
========================================================= */

.component-name {
    min-width: 180px;
    max-width: 260px;
    word-break: break-word;
}

.component-name-text {
    font-weight: 600;
    color: #212529;
}

/* =========================================================
   STATUS
========================================================= */

.status-badge {
    min-width: 70px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 10px;
    border-radius: 50px;
    font-size: 0.72rem;
    font-weight: 600;
}

.status-baik {
    background: #d1e7dd;
    color: #0f5132;
}

.status-rusak {
    background: #f8d7da;
    color: #842029;
}

.status-perbaikan {
    background: #fff3cd;
    color: #664d03;
}

/* =========================================================
   ACTION BUTTON
========================================================= */

.btn-detail {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
}

/* =========================================================
   EMPTY STATE
========================================================= */

.empty-component {
    min-height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 767.98px) {

    .detail-sub-page {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .detail-breadcrumb {
        margin-left: 2px;
        margin-right: 2px;
    }

    .detail-breadcrumb .breadcrumb {
        font-size: 0.72rem;
    }

    .sub-header-card {
        padding: 16px !important;
        border-radius: 14px;
    }

    .sub-header-top {
        align-items: flex-start !important;
    }

    .sub-icon {
        width: 50px;
        height: 50px;
        min-width: 50px;
        border-radius: 13px;
        font-size: 23px;
    }

    .sub-title {
        font-size: 1.05rem;
    }

    .machine-badge {
        font-size: 0.7rem;
        line-height: 1.4;
    }

    .back-button {
        width: 100%;
        margin-top: 12px;
    }

    .search-box {
        padding: 3px;
    }

    .search-buttons {
        display: flex;
        gap: 8px;
    }

    .search-buttons .btn:first-child {
        flex: 1;
    }

    .component-card {
        border-radius: 14px;
    }

    .component-card-header {
        padding: 16px !important;
    }

    .component-table {
        min-width: 900px;
    }

    .component-table thead th {
        font-size: 0.72rem;
        padding: 11px 10px;
    }

    .component-table tbody td {
        font-size: 0.75rem;
        padding: 11px 10px;
    }

    .empty-component {
        min-height: 170px;
        padding: 25px;
    }
}

/* =========================================================
   EXTRA SMALL MOBILE
========================================================= */

@media (max-width: 400px) {

    .sub-header-card {
        padding: 13px !important;
    }

    .sub-icon {
        width: 45px;
        height: 45px;
        min-width: 45px;
    }

    .sub-title {
        font-size: 0.98rem;
    }

    .machine-badge {
        font-size: 0.65rem;
    }
}

</style>


<div class="container-fluid mb-4 px-3 py-2 detail-sub-page">


    <!-- =====================================================
         BREADCRUMB
    ====================================================== -->

    <nav aria-label="breadcrumb"
         class="mb-3 detail-breadcrumb">

        <ol class="breadcrumb">

            <li class="breadcrumb-item">
                <a href="hierarki.php"
                   class="text-decoration-none">

                    <i class="bi bi-house-door me-1"></i>
                    Direktori Utama

                </a>
            </li>

            <li class="breadcrumb-item">

                <a href="detail_lokasi.php?lokasi=<?= urlencode($data_sub['lokasi']); ?>"
                   class="text-decoration-none">

                    <?= htmlspecialchars($data_sub['lokasi']); ?>

                </a>

            </li>

            <li class="breadcrumb-item">

                <a href="detail_area.php?id_area=<?= $data_sub['id_area']; ?>"
                   class="text-decoration-none">

                    <?= htmlspecialchars($data_sub['nama_area']); ?>

                </a>

            </li>

            <li class="breadcrumb-item">

                <a href="detail_jenis.php?id_jenis=<?= $data_sub['id_jenis']; ?>"
                   class="text-decoration-none">

                    <?= htmlspecialchars($data_sub['nama_jenis_mesin']); ?>

                </a>

            </li>

            <li class="breadcrumb-item">

                <a href="detail_mesin.php?id_mesin=<?= $data_sub['id_mesin']; ?>"
                   class="text-decoration-none">

                    <?= htmlspecialchars($data_sub['nama_mesin']); ?>

                </a>

            </li>

            <li class="breadcrumb-item active"
                aria-current="page">

                <?= htmlspecialchars($data_sub['nama_sub_mesin']); ?>

            </li>

        </ol>

    </nav>


    <!-- =====================================================
         HEADER SUB MESIN
    ====================================================== -->

    <div class="card sub-header-card p-4 mb-4">

        <div class="d-flex justify-content-between align-items-start gap-3 sub-header-top mb-4">


            <!-- INFO SUB MESIN -->

            <div class="d-flex align-items-center gap-3 flex-grow-1 min-width-0">

                <div class="sub-icon">

                    <i class="bi bi-folder2-open"></i>

                </div>


                <div class="min-width-0">

                    <!-- MESIN INDUK -->

                    <div class="mb-2">

                        <span class="badge bg-light text-dark border px-2 py-1 fw-normal machine-badge">

                            <i class="bi bi-cpu me-1"
                               style="color:#0056a6;"></i>

                            Mesin:

                            <strong>
                                <?= htmlspecialchars($data_sub['nama_mesin']); ?>
                            </strong>

                            <?php if (!empty($data_sub['sn_mesin'])) : ?>

                                <span class="text-muted ms-1">

                                    (SN:
                                    <?= htmlspecialchars($data_sub['sn_mesin']); ?>)

                                </span>

                            <?php endif; ?>

                        </span>

                    </div>


                    <!-- JUDUL -->

                    <h2 class="fw-bold text-dark m-0 sub-title">

                        Sub Mesin:
                        <?= htmlspecialchars($data_sub['nama_sub_mesin']); ?>

                    </h2>


                    <p class="text-muted small m-0 mt-1">

                        Daftar Komponen di dalam Sub Mesin ini

                    </p>

                </div>

            </div>


            <!-- KEMBALI -->

            <a href="detail_mesin.php?id_mesin=<?= $data_sub['id_mesin']; ?>"
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
              class="row g-2">

            <input type="hidden"
                   name="id_sub"
                   value="<?= $id_sub; ?>">


            <div class="col-lg-10 col-md-9 col-12">

                <div class="input-group search-box">

                    <span class="input-group-text">

                        <i class="bi bi-search text-muted"></i>

                    </span>

                    <input type="text"
                           name="keyword"
                           class="form-control"
                           placeholder="Cari Nama Bagian, Serial Number, Kategori, Brand, atau Tipe..."
                           value="<?= htmlspecialchars($keyword); ?>">

                </div>

            </div>


            <div class="col-lg-2 col-md-3 col-12 search-buttons d-flex gap-2">

                <button type="submit"
                        class="btn btn-primary fw-semibold"
                        style="background-color:#0056a6;border:none;">

                    <i class="bi bi-search me-1"></i>

                    Cari

                </button>


                <?php if (!empty($keyword)) : ?>

                    <a href="detail_sub_mesin.php?id_sub=<?= $id_sub; ?>"
                       class="btn btn-outline-secondary"
                       title="Reset pencarian">

                        <i class="bi bi-arrow-counterclockwise"></i>

                    </a>

                <?php endif; ?>

            </div>

        </form>

    </div>


    <!-- =====================================================
         KOMPONEN
    ====================================================== -->

    <div class="card component-card">


        <!-- HEADER -->

        <div class="component-card-header p-4 pb-3">

            <div class="d-flex justify-content-between align-items-center gap-3">

                <div>

                    <h5 class="fw-bold text-dark mb-1">

                        <i class="bi bi-gear-fill me-2"
                           style="color:#0056a6;"></i>

                        Daftar Komponen

                    </h5>

                    <small class="text-muted">

                        <?php if (!empty($keyword)) : ?>

                            Hasil pencarian untuk:
                            <strong>
                                "<?= htmlspecialchars($keyword); ?>"
                            </strong>

                        <?php else : ?>

                            Komponen yang terdaftar pada sub mesin ini

                        <?php endif; ?>

                    </small>

                </div>


                <!-- JUMLAH -->

                <span class="badge rounded-pill bg-light text-dark border">

                    <?= $jumlah_komponen; ?> Komponen

                </span>

            </div>

        </div>


        <!-- =================================================
             TABLE
        ================================================== -->

        <?php if ($query_komponen && mysqli_num_rows($query_komponen) > 0) : ?>


            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0 component-table">


                    <thead class="table-light">

                        <tr>

                            <th>
                                Nama Bagian / Komponen
                            </th>

                            <th>
                                Serial Number
                            </th>

                            <th>
                                Kategori
                            </th>

                            <th>
                                Brand / Tipe
                            </th>

                            <th>
                                Kondisi
                            </th>

                            <th class="text-center">
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php while ($k = mysqli_fetch_assoc($query_komponen)) : ?>


                            <tr>


                                <!-- NAMA -->

                                <td class="component-name">

                                    <div class="d-flex align-items-center gap-2">

                                        <div class="text-muted">

                                            <i class="bi bi-gear"></i>

                                        </div>

                                        <div class="component-name-text">

                                            <?= htmlspecialchars($k['nama_bagian']); ?>

                                        </div>

                                    </div>

                                </td>


                                <!-- SERIAL -->

                                <td>

                                    <?php if (!empty($k['serial_number'])) : ?>

                                        <code>
                                            <?= htmlspecialchars($k['serial_number']); ?>
                                        </code>

                                    <?php else : ?>

                                        <span class="text-muted">
                                            -
                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- KATEGORI -->

                                <td>

                                    <?= !empty($k['kategori'])
                                        ? htmlspecialchars($k['kategori'])
                                        : '<span class="text-muted">-</span>'; ?>

                                </td>


                                <!-- BRAND / TIPE -->

                                <td>

                                    <?php

                                    $brand = trim($k['brand'] ?? '');
                                    $tipe  = trim($k['tipe'] ?? '');

                                    if ($brand !== '') :

                                    ?>

                                        <span class="fw-semibold">

                                            <?= htmlspecialchars($brand); ?>

                                        </span>

                                    <?php else : ?>

                                        <span class="text-muted">
                                            -
                                        </span>

                                    <?php endif; ?>


                                    <?php if ($tipe !== '') : ?>

                                        <div>

                                            <small class="text-muted">

                                                Tipe:
                                                <?= htmlspecialchars($tipe); ?>

                                            </small>

                                        </div>

                                    <?php endif; ?>

                                </td>


                                <!-- KONDISI -->

                                <td>

                                    <?php

                                    $kondisi = trim($k['kondisi'] ?? '');

                                    if (strcasecmp($kondisi, 'Baik') === 0) :

                                    ?>

                                        <span class="status-badge status-baik">

                                            <i class="bi bi-check-circle me-1"></i>
                                            Baik

                                        </span>


                                    <?php elseif (strcasecmp($kondisi, 'Rusak') === 0) : ?>

                                        <span class="status-badge status-rusak">

                                            <i class="bi bi-x-circle me-1"></i>
                                            Rusak

                                        </span>


                                    <?php elseif (
                                        strcasecmp($kondisi, 'Perbaikan') === 0
                                    ) : ?>

                                        <span class="status-badge status-perbaikan">

                                            <i class="bi bi-tools me-1"></i>
                                            Perbaikan

                                        </span>


                                    <?php else : ?>

                                        <span class="status-badge bg-light text-muted border">

                                            <?= $kondisi !== ''
                                                ? htmlspecialchars($kondisi)
                                                : 'Tidak diketahui'; ?>

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- AKSI -->

                                <td class="text-center">

                                    <a href="komponen/detail.php?id=<?= $k['id']; ?>"
                                       class="btn btn-sm btn-outline-info btn-detail"
                                       title="Lihat Detail Komponen">

                                        <i class="bi bi-eye"></i>

                                    </a>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    </tbody>

                </table>

            </div>


        <?php else : ?>


            <!-- =================================================
                 EMPTY STATE
            ================================================== -->

            <div class="empty-component">

                <div class="text-center px-3">

                    <div class="mb-3">

                        <i class="bi bi-gear-wide-connected text-muted"
                           style="font-size:3rem;"></i>

                    </div>


                    <h6 class="fw-bold text-dark">

                        Komponen Tidak Ditemukan

                    </h6>


                    <p class="text-muted small mb-0">

                        <?php if (!empty($keyword)) : ?>

                            Tidak ada komponen yang sesuai dengan
                            kata kunci

                            <strong>
                                "<?= htmlspecialchars($keyword); ?>"
                            </strong>.

                        <?php else : ?>

                            Belum ada komponen yang terdaftar
                            pada sub mesin ini.

                        <?php endif; ?>

                    </p>


                    <?php if (!empty($keyword)) : ?>

                        <a href="detail_sub_mesin.php?id_sub=<?= $id_sub; ?>"
                           class="btn btn-sm btn-outline-secondary mt-3">

                            <i class="bi bi-arrow-counterclockwise me-1"></i>

                            Tampilkan Semua

                        </a>

                    <?php endif; ?>

                </div>

            </div>


        <?php endif; ?>


    </div>

</div>


<?php include "template/footer.php"; ?>