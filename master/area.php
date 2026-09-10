<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

include "../koneksi.php";
include "../template/header.php";

/* =========================================================
   PARAMETER PENCARIAN
========================================================= */

$keyword = isset($_GET['keyword'])
    ? trim($_GET['keyword'])
    : '';



/* =========================================================
   AMBIL DATA AREA
========================================================= */

$sql = false;
$query_error = '';

if (!empty($keyword)) {

    $stmt = mysqli_prepare(
        $conn,
        "
        SELECT
            id,
            nama_area,
            lokasi
        FROM area_bagian
        WHERE
            nama_area LIKE ?
            OR lokasi LIKE ?
        ORDER BY id DESC
        "
    );

    if ($stmt) {

        $kw = "%" . $keyword . "%";

        mysqli_stmt_bind_param(
            $stmt,
            "ss",
            $kw,
            $kw
        );

        mysqli_stmt_execute($stmt);

        $sql = mysqli_stmt_get_result($stmt);

        mysqli_stmt_close($stmt);

    } else {

        $query_error = mysqli_error($conn);

    }

} else {

    $sql = mysqli_query(
        $conn,
        "
        SELECT
            id,
            nama_area,
            lokasi
        FROM area_bagian
        ORDER BY id DESC
        "
    );

    if (!$sql) {

        $query_error = mysqli_error($conn);

    }

}


/* =========================================================
   TOTAL DATA
========================================================= */

$total_area = $sql
    ? mysqli_num_rows($sql)
    : 0;

?>



<style>

/* =========================================================
   AREA PAGE
========================================================= */

.area-page {
    width: 100%;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.area-page-header {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    padding: 20px 22px;

    margin-bottom: 20px;

    box-shadow:
        0 3px 12px rgba(15, 23, 42, .04);

}


.area-page-header-inner {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    flex-wrap: wrap;

}


.area-title-wrapper {

    display: flex;

    align-items: center;

    gap: 13px;

}


.area-title-icon {

    width: 46px;

    height: 46px;

    min-width: 46px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eaf3ff;

    color: #005baa;

    font-size: 21px;

}


.area-page-title {

    margin: 0;

    font-size: 21px;

    font-weight: 800;

    color: #172033;

}


.area-page-subtitle {

    margin: 4px 0 0;

    font-size: 12px;

    color: #94a3b8;

}


.area-add-btn {

    background: #005baa;

    border-color: #005baa;

    color: #ffffff;

    border-radius: 9px;

    font-weight: 600;

    padding: 9px 15px;

    transition: .2s;

}


.area-add-btn:hover {

    background: #004b8d;

    border-color: #004b8d;

    color: #ffffff;

    transform: translateY(-1px);

    box-shadow:
        0 5px 14px rgba(0, 91, 170, .18);

}


/* =========================================================
   CONTENT CARD
========================================================= */

.area-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    overflow: hidden;

    box-shadow:
        0 3px 12px rgba(15, 23, 42, .035);

}


.area-card + .area-card {

    margin-top: 20px;

}


/* =========================================================
   SEARCH
========================================================= */

.area-search-wrapper {

    padding: 16px;

}


.area-search-form {

    display: flex;

    align-items: center;

    gap: 10px;

}


.area-search-box {

    flex: 1;

    position: relative;

}


.area-search-box .search-icon {

    position: absolute;

    left: 13px;

    top: 50%;

    transform: translateY(-50%);

    color: #94a3b8;

    pointer-events: none;

    z-index: 2;

}


.area-search-input {

    height: 43px;

    padding-left: 39px;

    padding-right: 14px;

    border: 1px solid #dbe2ea;

    border-radius: 9px !important;

    font-size: 13px;

    color: #172033;

    box-shadow: none !important;

}


.area-search-input:focus {

    border-color: #005baa;

    box-shadow:
        0 0 0 3px rgba(0, 91, 170, .08) !important;

}


.area-search-actions {

    display: flex;

    gap: 7px;

}


.area-search-btn {

    height: 43px;

    border-radius: 9px;

    padding: 0 18px;

    font-weight: 600;

    background: #005baa;

    border-color: #005baa;

}


.area-search-btn:hover {

    background: #004b8d;

    border-color: #004b8d;

}


.area-reset-btn {

    width: 43px;

    height: 43px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

}


/* =========================================================
   CARD HEADER
========================================================= */

.area-card-header {

    padding: 16px 18px;

    border-bottom: 1px solid #e5e7eb;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    flex-wrap: wrap;

}


.area-card-title {

    margin: 0;

    font-size: 15px;

    font-weight: 750;

    color: #172033;

}


.area-card-title i {

    color: #005baa;

}


.area-total-badge {

    background: #eef5ff;

    color: #005baa;

    border: 1px solid #d7e8ff;

    border-radius: 50px;

    padding: 6px 11px;

    font-size: 11px;

    font-weight: 700;

}


/* =========================================================
   TABLE
========================================================= */

.area-table-wrapper {

    width: 100%;

    overflow-x: auto;

    -webkit-overflow-scrolling: touch;

}


.area-table {

    margin: 0;

    min-width: 650px;

}


.area-table thead th {

    background: #f8fafc;

    color: #64748b;

    border-bottom: 1px solid #e5e7eb;

    padding: 12px 14px;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .45px;

    font-weight: 800;

    white-space: nowrap;

}


.area-table tbody td {

    padding: 13px 14px;

    border-bottom: 1px solid #f1f5f9;

    color: #334155;

    font-size: 13px;

}


.area-table tbody tr:last-child td {

    border-bottom: none;

}


.area-table tbody tr {

    transition: .15s;

}


.area-table tbody tr:hover {

    background: #f8fbff;

}


/* =========================================================
   NUMBER
========================================================= */

.area-number {

    width: 55px;

    color: #94a3b8 !important;

    font-weight: 700;

}


/* =========================================================
   AREA NAME
========================================================= */

.area-name-wrapper {

    display: flex;

    align-items: center;

    gap: 10px;

}


.area-name-icon {

    width: 34px;

    height: 34px;

    min-width: 34px;

    border-radius: 9px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eef5ff;

    color: #005baa;

    font-size: 15px;

}


.area-name {

    font-weight: 700;

    color: #172033;

    word-break: break-word;

}


.area-location {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    color: #64748b;

    word-break: break-word;

}


.area-location i {

    color: #ef4444;

}


/* =========================================================
   ACTION
========================================================= */

.area-actions {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

}


.area-action-btn {

    width: 34px;

    height: 34px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    border-radius: 8px !important;

    transition: .2s;

}


.area-edit-btn {

    color: #d97706;

    border-color: #fcd34d;

    background: #fffdf5;

}


.area-edit-btn:hover {

    background: #f59e0b;

    border-color: #f59e0b;

    color: #ffffff;

}


.area-delete-btn {

    color: #dc2626;

    border-color: #fecaca;

    background: #fffafa;

}


.area-delete-btn:hover {

    background: #dc2626;

    border-color: #dc2626;

    color: #ffffff;

}


/* =========================================================
   EMPTY STATE
========================================================= */

.area-empty {

    padding: 55px 20px !important;

    text-align: center;

}


.area-empty-icon {

    width: 65px;

    height: 65px;

    margin: 0 auto 14px;

    border-radius: 16px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #f1f5f9;

    color: #94a3b8;

    font-size: 28px;

}


.area-empty-title {

    font-weight: 700;

    color: #475569;

    margin-bottom: 4px;

}


.area-empty-text {

    font-size: 12px;

    color: #94a3b8;

    margin: 0;

}


/* =========================================================
   ERROR
========================================================= */

.area-error {

    margin: 0 16px 16px;

    border-radius: 10px;

    border: 1px solid #fecaca;

    background: #fef2f2;

    color: #b91c1c;

    font-size: 13px;

}


/* =========================================================
   RESPONSIVE TABLET
========================================================= */

@media (max-width: 768px) {

    .area-page-header {

        padding: 16px;

        border-radius: 14px;

    }


    .area-title-icon {

        width: 42px;

        height: 42px;

        min-width: 42px;

        font-size: 19px;

    }


    .area-page-title {

        font-size: 18px;

    }


    .area-page-subtitle {

        font-size: 11px;

    }


    .area-add-btn {

        width: 100%;

        justify-content: center;

    }


    .area-page-header-inner {

        display: block;

    }


    .area-title-wrapper {

        margin-bottom: 14px;

    }


    .area-search-wrapper {

        padding: 13px;

    }


    .area-search-form {

        display: block;

    }


    .area-search-box {

        margin-bottom: 9px;

    }


    .area-search-actions {

        width: 100%;

    }


    .area-search-btn {

        flex: 1;

    }


    .area-card-header {

        padding: 14px;

    }

}


/* =========================================================
   RESPONSIVE MOBILE
========================================================= */

@media (max-width: 576px) {

    .area-page-title {

        font-size: 17px;

    }


    .area-page-subtitle {

        line-height: 1.5;

    }


    .area-card {

        border-radius: 13px;

    }


    .area-card-title {

        font-size: 14px;

    }


    .area-total-badge {

        font-size: 10px;

    }


    .area-table {

        min-width: 600px;

    }


    .area-table thead th {

        padding: 11px 10px;

    }


    .area-table tbody td {

        padding: 11px 10px;

    }


    .area-name-icon {

        width: 31px;

        height: 31px;

        min-width: 31px;

        font-size: 13px;

    }


    .area-name {

        font-size: 12px;

    }


    .area-location {

        font-size: 12px;

    }


    .area-action-btn {

        width: 32px;

        height: 32px;

    }


    .area-empty {

        padding: 45px 15px !important;

    }

}


/* =========================================================
   VERY SMALL SCREEN
========================================================= */

@media (max-width: 400px) {

    .area-title-wrapper {

        align-items: flex-start;

    }


    .area-title-icon {

        width: 38px;

        height: 38px;

        min-width: 38px;

    }


    .area-page-title {

        font-size: 16px;

    }


    .area-search-btn {

        padding-left: 12px;

        padding-right: 12px;

    }

}

</style>



<div class="container-fluid p-0 area-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="area-page-header">

        <div class="area-page-header-inner">


            <div class="area-title-wrapper">

                <div class="area-title-icon">

                    <i class="bi bi-geo-alt-fill"></i>

                </div>


                <div>

                    <h2 class="area-page-title">

                        Data Area Bagian

                    </h2>


                    <p class="area-page-subtitle">

                        Kelola daftar area atau lokasi pabrik
                        untuk penempatan mesin

                    </p>

                </div>

            </div>



            <?php if (has_role('user')): ?>
<a
                href="tambah_area.php"
                class="btn area-add-btn d-inline-flex align-items-center gap-2"
            >

                <i class="bi bi-plus-lg"></i>

                <span>Tambah Area</span>

            </a>
<?php endif; ?>

        </div>

    </div>



    <!-- =====================================================
         SEARCH CARD
    ====================================================== -->

    <div class="area-card mb-4">


        <div class="area-search-wrapper">

            <form
                method="GET"
                action=""
                class="area-search-form"
            >


                <div class="area-search-box">

                    <i class="bi bi-search search-icon"></i>


                    <input
                        type="text"
                        name="keyword"
                        class="form-control area-search-input"
                        placeholder="Cari nama area atau lokasi..."
                        value="<?= htmlspecialchars($keyword) ?>"
                        autocomplete="off"
                    >

                </div>



                <div class="area-search-actions">


                    <button
                        type="submit"
                        class="btn btn-primary area-search-btn"
                    >

                        <i class="bi bi-search me-1"></i>

                        Cari

                    </button>



                    <?php if (!empty($keyword)): ?>

                        <a
                            href="area.php"
                            class="btn btn-outline-secondary area-reset-btn"
                            title="Reset pencarian"
                        >

                            <i class="bi bi-arrow-counterclockwise"></i>

                        </a>

                    <?php endif; ?>


                </div>

            </form>

        </div>


    </div>



    <!-- =====================================================
         ERROR DATABASE
    ====================================================== -->

    <?php if (!empty($query_error)): ?>

        <div class="alert area-error">

            <i class="bi bi-exclamation-triangle-fill me-2"></i>

            Terjadi kesalahan saat mengambil data area.

        </div>

    <?php endif; ?>



    <!-- =====================================================
         TABLE CARD
    ====================================================== -->

    <div class="area-card">


        <!-- CARD HEADER -->

        <div class="area-card-header">


            <h5 class="area-card-title">

                <i class="bi bi-list-ul me-2"></i>

                Daftar Area Terdaftar

            </h5>



            <span class="area-total-badge">

                Total:
                <?= $total_area ?>
                Area

            </span>


        </div>



        <!-- TABLE -->

        <div class="area-table-wrapper">

            <table class="table area-table align-middle">


                <thead>

                    <tr>

                        <th
                            width="60"
                            class="text-center"
                        >
                            No
                        </th>


                        <th>
                            Nama Area / Bagian
                        </th>


                        <th>
                            Lokasi Area
                        </th>


                        <th
                            width="120"
                            class="text-center"
                        >
                            Aksi
                        </th>

                    </tr>

                </thead>



                <tbody>


                    <?php

                    $no = 1;

                    if (
                        $sql &&
                        mysqli_num_rows($sql) > 0
                    ):

                        while (
                            $d = mysqli_fetch_assoc($sql)
                        ):

                            $id_area =
                                intval(
                                    $d['id'] ?? 0
                                );

                            $nama_area =
                                $d['nama_area']
                                ?? '-';

                            $lokasi =
                                $d['lokasi']
                                ?? '-';

                    ?>



                        <tr>


                            <!-- NO -->

                            <td class="text-center area-number">

                                <?= $no++ ?>

                            </td>



                            <!-- NAMA AREA -->

                            <td>

                                <div class="area-name-wrapper">


                                    <div class="area-name-icon">

                                        <i class="bi bi-geo-alt"></i>

                                    </div>


                                    <div class="area-name">

                                        <?= htmlspecialchars(
                                            $nama_area
                                        ) ?>

                                    </div>


                                </div>

                            </td>



                            <!-- LOKASI -->

                            <td>

                                <span class="area-location">

                                    <i class="bi bi-pin-map-fill"></i>

                                    <?= htmlspecialchars(
                                        $lokasi
                                    ) ?>

                                </span>

                            </td>



                            <!-- AKSI -->

                            <td>

                                <div class="area-actions">


                                    <?php if (has_role('user')): ?>
<a
                                        href="edit_area.php?id=<?= $id_area ?>"
                                        class="btn btn-outline-warning area-action-btn area-edit-btn"
                                        title="Edit Area"
                                    >

                                        <i class="bi bi-pencil-square"></i>

                                    </a>
<?php endif; ?>



                                    <?php if (has_role('admin')): ?>
<a
                                        href="hapus_area.php?id=<?= $id_area ?>"
                                        class="btn btn-outline-danger area-action-btn area-delete-btn"
                                        title="Hapus Area"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus area <?= htmlspecialchars($nama_area, ENT_QUOTES) ?>?');"
                                    >

                                        <i class="bi bi-trash"></i>

                                    </a>
<?php endif; ?>


                                </div>

                            </td>


                        </tr>



                    <?php

                        endwhile;

                    else:

                    ?>


                        <!-- EMPTY -->

                        <tr>

                            <td
                                colspan="4"
                                class="area-empty"
                            >


                                <div class="area-empty-icon">

                                    <?php if (!empty($keyword)): ?>

                                        <i class="bi bi-search"></i>

                                    <?php else: ?>

                                        <i class="bi bi-inbox"></i>

                                    <?php endif; ?>

                                </div>



                                <?php if (!empty($keyword)): ?>


                                    <div class="area-empty-title">

                                        Data area tidak ditemukan

                                    </div>


                                    <p class="area-empty-text">

                                        Tidak ada area yang cocok
                                        dengan kata kunci
                                        "<strong><?= htmlspecialchars($keyword) ?></strong>".

                                    </p>


                                    <a
                                        href="area.php"
                                        class="btn btn-sm btn-outline-primary mt-3"
                                    >

                                        <i class="bi bi-arrow-counterclockwise me-1"></i>

                                        Reset Pencarian

                                    </a>


                                <?php else: ?>


                                    <div class="area-empty-title">

                                        Belum ada data area

                                    </div>


                                    <p class="area-empty-text">

                                        Silakan tambahkan area
                                        atau lokasi pabrik terlebih dahulu.

                                    </p>


                                    <?php if (has_role('user')): ?>
<a
                                        href="tambah_area.php"
                                        class="btn btn-sm btn-primary mt-3"
                                    >

                                        <i class="bi bi-plus-lg me-1"></i>

                                        Tambah Area

                                    </a>
<?php endif; ?>


                                <?php endif; ?>


                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>

            </table>

        </div>


    </div>


</div>



<?php

include "../template/footer.php";

?>