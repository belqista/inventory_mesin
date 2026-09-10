<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

include "../koneksi.php";

/* =========================================================
   PARAMETER
========================================================= */

$keyword = isset($_GET['keyword'])
    ? trim($_GET['keyword'])
    : '';

$keyword = substr($keyword, 0, 100);


/* =========================================================
   QUERY DATA
========================================================= */

$sql = false;
$total_data = 0;

if ($keyword !== '') {

    $query = "
        SELECT
            jm.id,
            jm.id_area,
            jm.nama_jenis_mesin,
            a.nama_area,
            a.lokasi
        FROM jenis_mesin jm
        LEFT JOIN area_bagian a
            ON jm.id_area = a.id
        WHERE
            jm.nama_jenis_mesin LIKE ?
            OR a.nama_area LIKE ?
            OR a.lokasi LIKE ?
        ORDER BY jm.id DESC
    ";

    $stmt = mysqli_prepare($conn, $query);

    if ($stmt) {

        $kw = "%" . $keyword . "%";

        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $kw,
            $kw,
            $kw
        );

        mysqli_stmt_execute($stmt);

        $sql = mysqli_stmt_get_result($stmt);
    }

} else {

    $query = "
        SELECT
            jm.id,
            jm.id_area,
            jm.nama_jenis_mesin,
            a.nama_area,
            a.lokasi
        FROM jenis_mesin jm
        LEFT JOIN area_bagian a
            ON jm.id_area = a.id
        ORDER BY jm.id DESC
    ";

    $sql = mysqli_query($conn, $query);
}


if ($sql) {
    $total_data = mysqli_num_rows($sql);
}


/* =========================================================
   HEADER
========================================================= */

include "../template/header.php";

?>


<style>

/* =========================================================
   PAGE
========================================================= */

.jenis-mesin-page {
    width: 100%;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.jenis-page-header {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    padding: 20px;

    margin-bottom: 18px;

    box-shadow:
        0 2px 8px rgba(15, 23, 42, .035);
}


.jenis-page-header-inner {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    flex-wrap: wrap;
}


.jenis-page-title {

    margin: 0;

    color: #172033;

    font-size: 22px;

    font-weight: 800;

    line-height: 1.3;
}


.jenis-page-subtitle {

    margin-top: 4px;

    color: #94a3b8;

    font-size: 12px;

    line-height: 1.5;
}


.btn-add-jenis {

    min-height: 42px;

    padding: 9px 15px;

    border-radius: 9px;

    background: #005baa;

    border: 1px solid #005baa;

    color: #ffffff;

    font-size: 13px;

    font-weight: 700;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    text-decoration: none;

    transition: .2s ease;

    white-space: nowrap;
}


.btn-add-jenis:hover {

    background: #004987;

    border-color: #004987;

    color: #ffffff;

    transform: translateY(-1px);

    box-shadow:
        0 5px 12px rgba(0, 91, 170, .15);
}


/* =========================================================
   SEARCH CARD
========================================================= */

.search-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 15px;

    margin-bottom: 18px;

    box-shadow:
        0 2px 8px rgba(15, 23, 42, .025);
}


.search-form {

    display: flex;

    align-items: center;

    gap: 9px;
}


.search-input-wrapper {

    flex: 1;

    position: relative;
}


.search-input-icon {

    position: absolute;

    left: 13px;

    top: 50%;

    transform: translateY(-50%);

    color: #94a3b8;

    font-size: 15px;

    pointer-events: none;

    z-index: 2;
}


.search-input {

    width: 100%;

    min-height: 42px;

    padding: 9px 12px 9px 38px;

    border: 1px solid #dbe2ea;

    border-radius: 9px;

    outline: none;

    font-size: 13px;

    color: #172033;

    transition: .2s ease;
}


.search-input:focus {

    border-color: #005baa;

    box-shadow:
        0 0 0 3px rgba(0, 91, 170, .08);
}


.search-input::placeholder {

    color: #a0aec0;
}


.btn-search {

    min-height: 42px;

    padding: 9px 18px;

    border-radius: 9px;

    border: 1px solid #005baa;

    background: #005baa;

    color: #ffffff;

    font-size: 13px;

    font-weight: 700;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    white-space: nowrap;

    transition: .2s ease;
}


.btn-search:hover {

    background: #004987;

    border-color: #004987;

    color: #ffffff;
}


.btn-reset-search {

    min-height: 42px;

    min-width: 42px;

    padding: 8px 11px;

    border-radius: 9px;

    border: 1px solid #dbe2ea;

    background: #ffffff;

    color: #64748b;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    transition: .2s ease;
}


.btn-reset-search:hover {

    background: #f8fafc;

    color: #334155;

    border-color: #cbd5e1;
}


/* =========================================================
   SEARCH INFO
========================================================= */

.search-result-info {

    margin-top: 10px;

    padding: 8px 11px;

    background: #f8fafc;

    border: 1px solid #e5e7eb;

    border-radius: 8px;

    color: #64748b;

    font-size: 11px;
}


.search-result-info strong {

    color: #334155;
}


/* =========================================================
   TABLE CARD
========================================================= */

.data-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    overflow: hidden;

    box-shadow:
        0 2px 8px rgba(15, 23, 42, .025);
}


/* =========================================================
   CARD HEADER
========================================================= */

.data-card-header {

    min-height: 62px;

    padding: 14px 18px;

    border-bottom: 1px solid #e5e7eb;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    flex-wrap: wrap;
}


.data-card-title {

    margin: 0;

    color: #172033;

    font-size: 15px;

    font-weight: 800;

    display: flex;

    align-items: center;

    gap: 8px;
}


.data-card-title i {

    color: #005baa;

    font-size: 17px;
}


.total-badge {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-height: 28px;

    padding: 5px 10px;

    border-radius: 20px;

    background: #eef5ff;

    border: 1px solid #d8e9ff;

    color: #005baa;

    font-size: 11px;

    font-weight: 700;

    white-space: nowrap;
}


/* =========================================================
   TABLE
========================================================= */

.jenis-table {

    width: 100%;

    margin: 0;

    border-collapse: separate;

    border-spacing: 0;
}


.jenis-table thead th {

    background: #f8fafc;

    color: #64748b;

    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .4px;

    font-weight: 800;

    padding: 12px 14px;

    border-bottom: 1px solid #e5e7eb;

    white-space: nowrap;
}


.jenis-table tbody td {

    padding: 13px 14px;

    border-bottom: 1px solid #f1f5f9;

    color: #334155;

    font-size: 13px;

    vertical-align: middle;
}


.jenis-table tbody tr:last-child td {

    border-bottom: 0;
}


.jenis-table tbody tr {

    transition: .15s ease;
}


.jenis-table tbody tr:hover {

    background: #f8fbff;
}


.number-cell {

    width: 60px;

    text-align: center;

    color: #94a3b8 !important;

    font-weight: 700;

    font-size: 12px !important;
}


.nama-jenis {

    font-weight: 750;

    color: #172033;

    line-height: 1.4;

    word-break: break-word;
}


.area-wrapper {

    display: flex;

    align-items: flex-start;

    gap: 7px;
}


.area-icon {

    color: #dc3545;

    font-size: 14px;

    margin-top: 1px;

    flex-shrink: 0;
}


.area-name {

    color: #475569;

    font-weight: 600;

    line-height: 1.4;
}


.area-location {

    display: block;

    margin-top: 2px;

    color: #94a3b8;

    font-size: 10px;

    line-height: 1.4;
}


/* =========================================================
   ACTION
========================================================= */

.action-cell {

    width: 130px;

    text-align: center;
}


.action-buttons {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 5px;
}


.btn-action {

    width: 34px;

    height: 34px;

    border-radius: 8px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    border: 1px solid;

    transition: .2s ease;

    font-size: 14px;
}


.btn-edit {

    color: #d97706;

    border-color: #fcd34d;

    background: #fffbeb;
}


.btn-edit:hover {

    background: #f59e0b;

    border-color: #f59e0b;

    color: #ffffff;

    transform: translateY(-1px);
}


.btn-delete {

    color: #dc2626;

    border-color: #fecaca;

    background: #fef2f2;
}


.btn-delete:hover {

    background: #dc2626;

    border-color: #dc2626;

    color: #ffffff;

    transform: translateY(-1px);
}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty-state {

    padding: 55px 20px;

    text-align: center;

    color: #94a3b8;
}


.empty-icon {

    width: 65px;

    height: 65px;

    margin: 0 auto 14px;

    border-radius: 18px;

    background: #f8fafc;

    border: 1px solid #e5e7eb;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #cbd5e1;

    font-size: 28px;
}


.empty-title {

    color: #475569;

    font-size: 14px;

    font-weight: 750;

    margin-bottom: 4px;
}


.empty-subtitle {

    color: #94a3b8;

    font-size: 11px;

    margin: 0;
}


.empty-reset {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    margin-top: 14px;

    padding: 7px 12px;

    border: 1px solid #dbe2ea;

    border-radius: 8px;

    color: #64748b;

    text-decoration: none;

    font-size: 11px;

    font-weight: 700;

    transition: .2s ease;
}


.empty-reset:hover {

    background: #f8fafc;

    color: #334155;
}


/* =========================================================
   SUCCESS ALERT
========================================================= */

.page-alert {

    border: 0;

    border-left: 4px solid #198754;

    background: #f0fdf4;

    color: #166534;

    border-radius: 9px;

    padding: 11px 14px;

    font-size: 12px;

    display: flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 18px;
}


/* =========================================================
   RESPONSIVE TABLET
========================================================= */

@media (max-width: 768px) {

    .jenis-page-header {

        padding: 16px;

        border-radius: 13px;
    }


    .jenis-page-title {

        font-size: 19px;
    }


    .jenis-page-subtitle {

        font-size: 11px;
    }


    .btn-add-jenis {

        width: 100%;

        min-height: 40px;
    }


    .search-card {

        padding: 12px;

        border-radius: 12px;
    }


    .search-form {

        flex-wrap: wrap;
    }


    .search-input-wrapper {

        flex: 1 1 100%;
    }


    .btn-search {

        flex: 1;
    }


    .btn-reset-search {

        min-width: 42px;
    }


    .data-card {

        border-radius: 13px;
    }


    .data-card-header {

        padding: 13px;

        min-height: auto;
    }


    .jenis-table {

        min-width: 650px;
    }

}


/* =========================================================
   RESPONSIVE MOBILE
========================================================= */

@media (max-width: 576px) {

    .jenis-page-header {

        padding: 13px;

        margin-bottom: 12px;

        border-radius: 11px;
    }


    .jenis-page-title {

        font-size: 17px;
    }


    .jenis-page-subtitle {

        font-size: 10px;

        line-height: 1.5;
    }


    .btn-add-jenis {

        font-size: 12px;

        min-height: 40px;

        padding: 8px 12px;
    }


    .search-card {

        padding: 10px;

        margin-bottom: 12px;

        border-radius: 11px;
    }


    .search-form {

        display: grid;

        grid-template-columns: 1fr auto;

        gap: 7px;
    }


    .search-input-wrapper {

        grid-column: 1 / -1;
    }


    .search-input {

        min-height: 40px;

        font-size: 12px;

        padding-left: 35px;
    }


    .btn-search {

        min-height: 40px;

        font-size: 12px;

        padding: 8px 12px;
    }


    .btn-reset-search {

        min-height: 40px;

        min-width: 40px;
    }


    .search-result-info {

        font-size: 10px;

        line-height: 1.5;
    }


    .data-card {

        border-radius: 11px;
    }


    .data-card-header {

        padding: 12px;

        align-items: flex-start;
    }


    .data-card-title {

        font-size: 13px;
    }


    .data-card-title i {

        font-size: 15px;
    }


    .total-badge {

        font-size: 10px;

        min-height: 25px;

        padding: 4px 8px;
    }


    /* -----------------------------------------------------
       MOBILE CARD TABLE
    ----------------------------------------------------- */

    .table-responsive {

        overflow: visible;
    }


    .jenis-table,
    .jenis-table tbody {

        display: block;

        width: 100%;

        min-width: 0;
    }


    .jenis-table thead {

        display: none;
    }


    .jenis-table tbody tr {

        display: block;

        margin: 10px;

        padding: 12px;

        border: 1px solid #e5e7eb;

        border-radius: 11px;

        background: #ffffff;

        box-shadow:
            0 2px 6px rgba(15, 23, 42, .025);
    }


    .jenis-table tbody tr:hover {

        background: #ffffff;
    }


    .jenis-table tbody td {

        display: block;

        width: 100% !important;

        padding: 0;

        border: 0;

        text-align: left !important;
    }


    .jenis-table tbody td.number-cell {

        display: none;
    }


    .jenis-table tbody td:nth-child(2) {

        padding-bottom: 8px;

        margin-bottom: 8px;

        border-bottom: 1px dashed #e5e7eb;
    }


    .jenis-table tbody td:nth-child(2)::before {

        content: "NAMA JENIS MESIN";

        display: block;

        color: #94a3b8;

        font-size: 9px;

        font-weight: 800;

        letter-spacing: .4px;

        margin-bottom: 4px;
    }


    .jenis-table tbody td:nth-child(3) {

        padding-bottom: 10px;
    }


    .jenis-table tbody td:nth-child(3)::before {

        content: "AREA / BAGIAN";

        display: block;

        color: #94a3b8;

        font-size: 9px;

        font-weight: 800;

        letter-spacing: .4px;

        margin-bottom: 5px;
    }


    .jenis-table tbody td.action-cell {

        padding-top: 9px;

        border-top: 1px solid #f1f5f9;

        text-align: right !important;
    }


    .action-buttons {

        width: 100%;

        justify-content: flex-end;
    }


    .btn-action {

        width: 36px;

        height: 36px;
    }


    .nama-jenis {

        font-size: 13px;
    }


    .area-name {

        font-size: 12px;
    }


    .area-location {

        font-size: 10px;
    }


    .empty-state {

        padding: 40px 15px;
    }

}

</style>


<div class="container-fluid p-0 jenis-mesin-page">


    <!-- =====================================================
         SUCCESS MESSAGE
    ====================================================== -->

    <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>

        <div
            class="page-alert"
            id="success-alert"
        >

            <i class="bi bi-check-circle-fill"></i>

            <span>
                Data jenis mesin berhasil diperbarui.
            </span>

        </div>

    <?php endif; ?>


    <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>

        <div
            class="page-alert"
            id="success-alert"
        >

            <i class="bi bi-check-circle-fill"></i>

            <span>
                Data jenis mesin berhasil ditambahkan.
            </span>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="jenis-page-header">

        <div class="jenis-page-header-inner">


            <div>

                <h2 class="jenis-page-title">

                    Data Jenis Mesin

                </h2>


                <div class="jenis-page-subtitle">

                    Kelola daftar kategori atau jenis mesin
                    berdasarkan area pabrik.

                </div>

            </div>


            <?php if (has_role('user')): ?>
<a
                href="tambah_jenis_mesin.php"
                class="btn-add-jenis"
            >

                <i class="bi bi-plus-lg"></i>

                <span>
                    Tambah Jenis Mesin
                </span>

            </a>
<?php endif; ?>

        </div>

    </div>



    <!-- =====================================================
         SEARCH
    ====================================================== -->

    <div class="search-card">

        <form
            method="GET"
            class="search-form"
        >


            <div class="search-input-wrapper">

                <i class="bi bi-search search-input-icon"></i>


                <input
                    type="text"
                    name="keyword"
                    class="search-input"
                    placeholder="Cari nama jenis mesin, area, atau lokasi..."
                    value="<?= htmlspecialchars($keyword) ?>"
                    maxlength="100"
                    autocomplete="off"
                >

            </div>


            <button
                type="submit"
                class="btn-search"
            >

                <i class="bi bi-search"></i>

                <span>Cari</span>

            </button>


            <?php if ($keyword !== ''): ?>

                <a
                    href="jenis_mesin.php"
                    class="btn-reset-search"
                    title="Reset pencarian"
                >

                    <i class="bi bi-arrow-counterclockwise"></i>

                </a>

            <?php endif; ?>


        </form>


        <?php if ($keyword !== ''): ?>

            <div class="search-result-info">

                <i class="bi bi-funnel me-1"></i>

                Hasil pencarian untuk:

                <strong>
                    "<?= htmlspecialchars($keyword) ?>"
                </strong>

                — ditemukan
                <strong>
                    <?= $total_data ?>
                </strong>
                data.

            </div>

        <?php endif; ?>

    </div>



    <!-- =====================================================
         DATA CARD
    ====================================================== -->

    <div class="data-card">


        <!-- HEADER -->

        <div class="data-card-header">


            <h5 class="data-card-title">

                <i class="bi bi-tags"></i>

                <span>
                    Daftar Jenis Mesin Terdaftar
                </span>

            </h5>


            <span class="total-badge">

                Total:
                <?= $total_data ?>
                Jenis Mesin

            </span>

        </div>



        <!-- TABLE -->

        <div class="table-responsive">

            <table class="jenis-table">


                <thead>

                    <tr>

                        <th
                            width="60"
                            class="text-center"
                        >
                            No
                        </th>


                        <th>
                            Nama Jenis Mesin
                        </th>


                        <th>
                            Area / Bagian
                        </th>


                        <th
                            width="130"
                            class="text-center"
                        >
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if ($sql && $total_data > 0): ?>


                        <?php

                        $no = 1;

                        while (
                            $d = mysqli_fetch_assoc($sql)
                        ):

                        ?>


                            <tr>


                                <!-- NO -->

                                <td class="number-cell">

                                    <?= $no++ ?>

                                </td>



                                <!-- NAMA -->

                                <td>

                                    <div class="nama-jenis">

                                        <?= htmlspecialchars(
                                            $d['nama_jenis_mesin']
                                            ?? '-'
                                        ) ?>

                                    </div>

                                </td>



                                <!-- AREA -->

                                <td>

                                    <div class="area-wrapper">


                                        <i
                                            class="bi bi-geo-alt-fill area-icon"
                                        ></i>


                                        <div>

                                            <div class="area-name">

                                                <?= htmlspecialchars(
                                                    $d['nama_area']
                                                    ?? 'Belum ada area'
                                                ) ?>

                                            </div>


                                            <?php if (!empty($d['lokasi'])): ?>

                                                <span class="area-location">

                                                    <?= htmlspecialchars(
                                                        $d['lokasi']
                                                    ) ?>

                                                </span>

                                            <?php endif; ?>


                                        </div>

                                    </div>

                                </td>



                                <!-- AKSI -->

                                <td class="action-cell">


                                    <div class="action-buttons">


                                        <!-- EDIT -->

                                        <?php if (has_role('user')): ?>
<a
                                            href="edit_jenis_mesin.php?id=<?= (int)$d['id'] ?>"
                                            class="btn-action btn-edit"
                                            title="Edit Data"
                                        >

                                            <i class="bi bi-pencil-square"></i>

                                        </a>
<?php endif; ?>



                                        <!-- DELETE -->

                                        <?php if (has_role('admin')): ?>
<a
                                            href="hapus_jenis_mesin.php?id=<?= (int)$d['id'] ?>"
                                            class="btn-action btn-delete"
                                            title="Hapus Data"
                                            onclick="return confirm(
                                                'Apakah Anda yakin ingin menghapus jenis mesin ini?\\n\\nData yang sudah dihapus tidak dapat dikembalikan.'
                                            );"
                                        >

                                            <i class="bi bi-trash"></i>

                                        </a>
<?php endif; ?>


                                    </div>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <!-- EMPTY -->

                        <tr>

                            <td
                                colspan="4"
                                style="padding:0; border:0;"
                            >

                                <div class="empty-state">


                                    <div class="empty-icon">

                                        <i class="bi bi-inbox"></i>

                                    </div>


                                    <?php if ($keyword !== ''): ?>

                                        <div class="empty-title">

                                            Data tidak ditemukan

                                        </div>


                                        <p class="empty-subtitle">

                                            Tidak ada jenis mesin yang
                                            sesuai dengan kata kunci
                                            pencarian.

                                        </p>


                                        <a
                                            href="jenis_mesin.php"
                                            class="empty-reset"
                                        >

                                            <i class="bi bi-arrow-counterclockwise"></i>

                                            Reset Pencarian

                                        </a>


                                    <?php else: ?>

                                        <div class="empty-title">

                                            Belum Ada Data Jenis Mesin

                                        </div>


                                        <p class="empty-subtitle">

                                            Belum ada jenis mesin yang
                                            terdaftar di dalam sistem.

                                        </p>


                                        <?php if (has_role('user')): ?>
<a
                                            href="tambah_jenis_mesin.php"
                                            class="empty-reset"
                                        >

                                            <i class="bi bi-plus-lg"></i>

                                            Tambah Jenis Mesin

                                        </a>
<?php endif; ?>

                                    <?php endif; ?>


                                </div>

                            </td>

                        </tr>


                    <?php endif; ?>


                </tbody>


            </table>

        </div>

    </div>


</div>


<script>

/* =========================================================
   AUTO HIDE SUCCESS ALERT
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const alertBox =
            document.getElementById(
                "success-alert"
            );


        if (!alertBox) {
            return;
        }


        setTimeout(
            function () {

                alertBox.style.transition =
                    "opacity .4s ease, transform .4s ease";

                alertBox.style.opacity = "0";

                alertBox.style.transform =
                    "translateY(-5px)";


                setTimeout(
                    function () {

                        if (alertBox) {
                            alertBox.remove();
                        }

                    },
                    400
                );

            },
            3500
        );

    }
);


/* =========================================================
   SEARCH SHORTCUT
========================================================= */

document.addEventListener(
    "keydown",
    function (event) {

        /*
         * Tekan "/" untuk langsung fokus
         * ke kolom pencarian.
         */

        if (
            event.key === "/" &&
            document.activeElement.tagName !== "INPUT" &&
            document.activeElement.tagName !== "TEXTAREA" &&
            document.activeElement.tagName !== "SELECT"
        ) {

            event.preventDefault();

            const search =
                document.querySelector(
                    'input[name="keyword"]'
                );


            if (search) {

                search.focus();

                search.select();

            }

        }

    }
);

</script>


<?php

include "../template/footer.php";

?>