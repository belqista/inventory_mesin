<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

include "../koneksi.php";
include "../template/header.php";

// =========================================================
// PARAMETER FILTER
// =========================================================

$keyword  = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$id_mesin = isset($_GET['id_mesin']) ? intval($_GET['id_mesin']) : 0;
$id_sub   = isset($_GET['id_sub_mesin']) ? intval($_GET['id_sub_mesin']) : 0;
$kondisi  = isset($_GET['kondisi']) ? trim($_GET['kondisi']) : '';


// =========================================================
// KONSTRUKSI WHERE DINAMIS
// =========================================================

$where_conditions = ["1=1"];
$params = [];
$types  = "";


// =========================================================
// FILTER KEYWORD
// =========================================================

if (!empty($keyword)) {

    $where_conditions[] = "
        (
            k.nama_bagian LIKE ?
            OR k.serial_number LIKE ?
            OR k.part_number LIKE ?
            OR k.brand LIKE ?
            OR k.tipe LIKE ?
        )
    ";

    $searchTerm = "%{$keyword}%";

    for ($i = 0; $i < 5; $i++) {

        $params[] = $searchTerm;
        $types .= "s";

    }
}


// =========================================================
// FILTER MESIN
// =========================================================

if (!empty($id_mesin)) {

    $where_conditions[] = "sm.id_mesin = ?";

    $params[] = $id_mesin;
    $types .= "i";
}


// =========================================================
// FILTER SUB MESIN
// =========================================================

if (!empty($id_sub)) {

    $where_conditions[] = "k.id_sub_mesin = ?";

    $params[] = $id_sub;
    $types .= "i";
}


// =========================================================
// FILTER KONDISI
// =========================================================

if (!empty($kondisi)) {

    $where_conditions[] = "k.kondisi = ?";

    $params[] = $kondisi;
    $types .= "s";
}


$where_clause = implode(" AND ", $where_conditions);


// =========================================================
// QUERY DATA KOMPONEN
// =========================================================

$query = "
    SELECT
        k.*,
        m.nama_mesin AS nama_mesin_relasi,
        sm.nama_sub_mesin AS nama_sub_relasi

    FROM komponen k

    LEFT JOIN sub_mesin sm
        ON k.id_sub_mesin = sm.id

    LEFT JOIN mesin m
        ON sm.id_mesin = m.id

    WHERE $where_clause

    ORDER BY k.id ASC
";


$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );

}

mysqli_stmt_execute($stmt);

$sql = mysqli_stmt_get_result($stmt);


// =========================================================
// AMBIL DATA MESIN UNTUK FILTER
// =========================================================

$q_mesin = mysqli_query(
    $conn,
    "
    SELECT
        id,
        nama_mesin
    FROM mesin
    ORDER BY nama_mesin ASC
    "
);

?>

<style>

/* =========================================================
   KOMPONEN INDEX - RESPONSIVE
========================================================= */

.komponen-page {
    width: 100%;
    max-width: 100%;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.dashboard-header {
    background: #ffffff;
    border: 1px solid #e5ebf3;
    border-radius: 16px;
    box-shadow: 0 5px 18px rgba(20, 50, 90, .05);
}

.dashboard-title {
    color: #163d66;
}

.dashboard-subtitle {
    color: #7b8796;
}


/* =========================================================
   CONTENT CARD
========================================================= */

.content-card {
    background: #ffffff;
    border: 1px solid #e5ebf3;
    border-radius: 16px;
    box-shadow: 0 5px 18px rgba(20, 50, 90, .05);
    overflow: hidden;
}

.card-body-custom {
    background: #ffffff;
}

.card-header-custom {
    background: #ffffff;
    border-bottom: 1px solid #edf1f5;
}

.card-title-custom {
    color: #075cb0;
}


/* =========================================================
   FILTER
========================================================= */

.filter-form label {
    color: #263d55;
}

.filter-actions {
    display: flex;
    gap: 5px;
}


/* =========================================================
   TABLE
========================================================= */

.komponen-table-wrapper {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.komponen-table {
    min-width: 950px;
    margin-bottom: 0;
}

.komponen-table thead th {
    background: #f7f9fc;
    color: #68778b;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .4px;
    white-space: nowrap;
    border-bottom: 1px solid #e7edf4;
    padding: 12px 13px;
    vertical-align: middle;
}

.komponen-table tbody td {
    padding: 12px 13px;
    font-size: 12px;
    border-color: #edf1f5;
    vertical-align: middle;
}

.komponen-table tbody tr {
    transition: background .2s ease;
}

.komponen-table tbody tr:hover {
    background: #f9fbfd;
}


/* =========================================================
   FOTO
========================================================= */

.component-photo {
    width: 42px;
    height: 42px;
    min-width: 42px;
    object-fit: cover;
    border-radius: 8px;
}

.component-photo-empty {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 8px;
}


/* =========================================================
   KOMPONEN INFO
========================================================= */

.component-name {
    display: block;
    color: #263d55;
    font-size: 12px;
    font-weight: 700;
    line-height: 1.4;
}

.component-sn {
    display: inline-block;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}


/* =========================================================
   MESIN INFO
========================================================= */

.machine-info {
    min-width: 150px;
}

.machine-info-name {
    display: block;
    color: #263d55;
    font-weight: 700;
    font-size: 11px;
    line-height: 1.4;
}

.machine-info-sub {
    display: block;
    color: #8995a4;
    font-size: 10px;
    margin-top: 2px;
}


/* =========================================================
   ACTION
========================================================= */

.action-buttons {
    display: flex;
    justify-content: center;
    gap: 4px;
    white-space: nowrap;
}

.action-buttons .btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}


/* =========================================================
   BADGE
========================================================= */

.badge-condition {
    white-space: nowrap;
    font-size: 10px;
}


/* =========================================================
   FILTER SELECT
========================================================= */

.form-select,
.form-control {
    border-color: #dfe6ef;
}

.form-select:focus,
.form-control:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 .15rem rgba(13, 110, 253, .10);
}


/* =========================================================
   RESPONSIVE TABLET
========================================================= */

@media (max-width: 991.98px) {

    .dashboard-header {
        padding: 18px !important;
    }

    .dashboard-header .btn {
        width: 100%;
        justify-content: center;
    }

    .filter-actions {
        width: 100%;
    }

    .filter-actions .btn {
        flex: 1;
    }

}


/* =========================================================
   RESPONSIVE HP
========================================================= */

@media (max-width: 767.98px) {

    .komponen-page {
        padding-left: 0;
        padding-right: 0;
    }


    /* HEADER */

    .dashboard-header {
        margin-bottom: 12px !important;
        padding: 16px !important;
        border-radius: 13px;
    }

    .dashboard-header .d-flex {
        align-items: stretch !important;
    }

    .dashboard-title {
        font-size: 19px !important;
        line-height: 1.3;
    }

    .dashboard-subtitle {
        font-size: 11px !important;
        line-height: 1.5;
        margin-top: 5px !important;
    }

    .dashboard-header .btn {
        margin-top: 5px;
        min-height: 42px;
        border-radius: 10px;
    }


    /* CARD */

    .content-card {
        border-radius: 13px;
        margin-bottom: 12px !important;
    }


    /* FILTER */

    .card-body-custom {
        padding: 14px !important;
    }

    .filter-form {
        row-gap: 10px !important;
    }

    .filter-form .form-label {
        font-size: 11px !important;
        margin-bottom: 4px !important;
    }

    .filter-form .form-select,
    .filter-form .form-control {
        min-height: 40px;
        font-size: 12px;
        border-radius: 8px;
    }

    .filter-actions {
        margin-top: 2px;
    }

    .filter-actions .btn {
        min-height: 40px;
        border-radius: 8px;
    }


    /* TABLE HEADER */

    .card-header-custom {
        padding: 12px 14px !important;
    }

    .card-title-custom {
        font-size: 13px !important;
    }

    .card-header-custom .badge {
        font-size: 9px !important;
    }


    /* TABLE */

    .komponen-table {
        min-width: 900px;
    }

    .komponen-table thead th {
        font-size: 9px;
        padding: 10px 11px;
    }

    .komponen-table tbody td {
        padding: 10px 11px;
        font-size: 11px;
    }


    /* FOTO */

    .component-photo,
    .component-photo-empty {
        width: 38px;
        height: 38px;
        min-width: 38px;
    }


    /* NAMA */

    .component-name {
        font-size: 11px;
    }

    .component-sn {
        font-size: 9px;
        max-width: 145px;
    }


    /* MESIN */

    .machine-info {
        min-width: 130px;
    }

    .machine-info-name {
        font-size: 10px;
    }

    .machine-info-sub {
        font-size: 9px;
    }


    /* BADGE */

    .badge-condition {
        font-size: 9px;
        padding: 5px 7px !important;
    }


    /* ACTION */

    .action-buttons .btn {
        width: 30px;
        height: 30px;
    }

}


/* =========================================================
   RESPONSIVE HP KECIL
========================================================= */

@media (max-width: 575.98px) {

    .dashboard-header {
        padding: 14px !important;
    }

    .dashboard-title {
        font-size: 17px !important;
    }

    .dashboard-subtitle {
        font-size: 10px !important;
    }


    .content-card {
        border-radius: 11px;
    }


    .card-header-custom {
        padding: 11px 12px !important;
    }

    .card-title-custom {
        font-size: 12px !important;
    }


    .card-header-custom .badge {
        font-size: 8px !important;
        padding: 5px 7px !important;
    }


    .komponen-table {
        min-width: 870px;
    }


    .table-responsive::-webkit-scrollbar,
    .komponen-table-wrapper::-webkit-scrollbar {
        height: 6px;
    }

    .table-responsive::-webkit-scrollbar-track,
    .komponen-table-wrapper::-webkit-scrollbar-track {
        background: #f1f3f5;
        border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb,
    .komponen-table-wrapper::-webkit-scrollbar-thumb {
        background: #b8c4d2;
        border-radius: 10px;
    }

}


/* =========================================================
   EXTRA SMALL
========================================================= */

@media (max-width: 400px) {

    .dashboard-title {
        font-size: 16px !important;
    }

    .dashboard-subtitle {
        font-size: 9px !important;
    }

    .dashboard-header .btn {
        font-size: 11px;
    }

    .filter-form .form-select,
    .filter-form .form-control {
        font-size: 11px;
    }

}

</style>


<div class="container-fluid komponen-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="dashboard-header mb-3 py-3 px-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div>

                <h3 class="dashboard-title m-0 fs-4 fw-bold">
                    Data Komponen / Part
                </h3>

                <p class="dashboard-subtitle m-0 small text-muted">
                    Kelola daftar komponen dan sparepart mesin
                </p>

            </div>


            <?php if (has_role('user')): ?>
<a
                href="tambah.php"
                class="btn btn-primary px-3 py-2 d-inline-flex align-items-center justify-content-center gap-2 shadow-sm"
            >

                <i class="bi bi-plus-lg fs-6"></i>

                <span>
                    Tambah Komponen
                </span>

            </a>
<?php endif; ?>

        </div>

    </div>



    <!-- =====================================================
         FILTER CARD
    ====================================================== -->

    <div class="content-card mb-3">

        <div class="card-body-custom p-3">

            <form
                method="GET"
                id="filterForm"
                class="row g-2 align-items-end filter-form"
            >


                <!-- MESIN -->

                <div class="col-12 col-sm-6 col-md-3">

                    <label class="form-label small fw-semibold text-dark mb-1">
                        Mesin
                    </label>

                    <select
                        name="id_mesin"
                        id="filter_mesin"
                        class="form-select form-select-sm"
                        onchange="loadSubMesinFilter(this.value)"
                    >

                        <option value="">
                            -- Semua Mesin --
                        </option>

                        <?php while ($m = mysqli_fetch_assoc($q_mesin)) : ?>

                            <option
                                value="<?= $m['id'] ?>"
                                <?= ($id_mesin == $m['id']) ? 'selected' : '' ?>
                            >

                                <?= htmlspecialchars($m['nama_mesin']) ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>



                <!-- SUB MESIN -->

                <div class="col-12 col-sm-6 col-md-3">

                    <label class="form-label small fw-semibold text-dark mb-1">
                        Sub Mesin
                    </label>

                    <select
                        name="id_sub_mesin"
                        id="filter_sub_mesin"
                        class="form-select form-select-sm"
                    >

                        <option value="">
                            -- Semua Sub Mesin --
                        </option>

                    </select>

                </div>



                <!-- KONDISI -->

                <div class="col-12 col-sm-6 col-md-2">

                    <label class="form-label small fw-semibold text-dark mb-1">
                        Kondisi
                    </label>

                    <select
                        name="kondisi"
                        class="form-select form-select-sm"
                    >

                        <option value="">
                            -- Semua Kondisi --
                        </option>

                        <option
                            value="Baik"
                            <?= ($kondisi === 'Baik') ? 'selected' : '' ?>
                        >
                            Baik
                        </option>

                        <option
                            value="Perlu Pemeriksaan"
                            <?= ($kondisi === 'Perlu Pemeriksaan') ? 'selected' : '' ?>
                        >
                            Perlu Pemeriksaan
                        </option>

                        <option
                            value="Dalam Perbaikan"
                            <?= ($kondisi === 'Dalam Perbaikan') ? 'selected' : '' ?>
                        >
                            Dalam Perbaikan
                        </option>

                    </select>

                </div>



                <!-- KATA KUNCI -->

                <div class="col-12 col-sm-6 col-md-3">

                    <label class="form-label small fw-semibold text-dark mb-1">
                        Kata Kunci
                    </label>

                    <input
                        type="text"
                        name="keyword"
                        class="form-control form-control-sm"
                        placeholder="Nama / SN / Part No / Brand..."
                        value="<?= htmlspecialchars($keyword) ?>"
                    >

                </div>



                <!-- BUTTON -->

                <div class="col-12 col-md-1">

                    <div class="filter-actions">

                        <button
                            type="submit"
                            class="btn btn-sm btn-primary flex-fill"
                            title="Terapkan Filter"
                        >

                            <i class="bi bi-funnel"></i>

                            <span class="d-md-none ms-1">
                                Terapkan Filter
                            </span>

                        </button>


                        <a
                            href="index.php"
                            class="btn btn-sm btn-outline-secondary"
                            title="Reset Filter"
                        >

                            <i class="bi bi-arrow-counterclockwise"></i>

                            <span class="d-md-none ms-1">
                                Reset
                            </span>

                        </a>

                    </div>

                </div>


            </form>

        </div>

    </div>



    <!-- =====================================================
         TABLE CARD
    ====================================================== -->

    <div class="content-card mb-3">


        <!-- HEADER TABLE -->

        <div class="card-header-custom py-2 px-3 d-flex align-items-center justify-content-between gap-2 flex-wrap">

            <h6 class="card-title-custom m-0 fw-bold">

                <i class="bi bi-cpu me-2"></i>

                Daftar Komponen

            </h6>


            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-7">

                Total:
                <?= mysqli_num_rows($sql) ?>
                Data

            </span>

        </div>



        <!-- TABLE -->

        <div class="komponen-table-wrapper">

            <table class="table table-hover align-middle komponen-table">


                <thead>

                    <tr>

                        <th
                            width="40"
                            class="text-center"
                        >
                            NO
                        </th>


                        <th
                            width="60"
                            class="text-center"
                        >
                            FOTO
                        </th>


                        <th>
                            SERIAL NUMBER & NAMA BAGIAN
                        </th>


                        <th>
                            BRAND / TIPE / PART NO
                        </th>


                        <th>
                            MESIN & SUB MESIN
                        </th>


                        <th>
                            KONDISI
                        </th>


                        <th
                            width="120"
                            class="text-center"
                        >
                            AKSI
                        </th>

                    </tr>

                </thead>



                <tbody>

                    <?php

                    $no = 1;

                    if (
                        $sql &&
                        mysqli_num_rows($sql) > 0
                    ) :

                        while (
                            $d = mysqli_fetch_assoc($sql)
                        ) :


                            // =================================================
                            // BADGE KONDISI
                            // =================================================

                            $badgeKondisi = 'bg-success';

                            if (
                                $d['kondisi'] === 'Dalam Perbaikan'
                            ) {

                                $badgeKondisi =
                                    'bg-danger';

                            } elseif (
                                $d['kondisi'] === 'Perlu Pemeriksaan'
                            ) {

                                $badgeKondisi =
                                    'bg-warning text-dark';

                            }


                            // =================================================
                            // RELASI MESIN
                            // =================================================

                            $nama_m =
                                $d['nama_mesin_relasi']
                                ?: '-';

                            $nama_s =
                                $d['nama_sub_relasi']
                                ?: '-';


                            // =================================================
                            // CEK FOTO
                            // =================================================

                            $foto_path =
                                "../uploads/komponen/"
                                . $d['gambar'];

                            $has_foto =
                                !empty($d['gambar'])
                                &&
                                file_exists($foto_path);

                    ?>


                    <tr>


                        <!-- NO -->

                        <td class="text-center fw-medium text-muted">

                            <?= $no++ ?>

                        </td>



                        <!-- FOTO -->

                        <td class="text-center">

                            <?php if ($has_foto) : ?>

                                <img
                                    src="<?= htmlspecialchars($foto_path) ?>"
                                    alt="Foto Komponen"
                                    class="component-photo rounded border"
                                >

                            <?php else : ?>

                                <div
                                    class="component-photo-empty border bg-light d-flex align-items-center justify-content-center mx-auto text-muted"
                                >

                                    <i class="bi bi-image fs-6"></i>

                                </div>

                            <?php endif; ?>

                        </td>



                        <!-- NAMA & SERIAL NUMBER -->

                        <td>

                            <strong class="component-name">

                                <?= htmlspecialchars(
                                    $d['nama_bagian']
                                    ?: '-'
                                ) ?>

                            </strong>


                            <span
                                class="badge bg-light text-primary border font-monospace mt-1 component-sn"
                            >

                                <i class="bi bi-qr-code me-1"></i>

                                <?= htmlspecialchars(
                                    $d['serial_number']
                                    ?: '-'
                                ) ?>

                            </span>

                        </td>



                        <!-- BRAND / TIPE / PART NUMBER -->

                        <td>

                            <span class="fw-semibold text-dark">

                                <?= htmlspecialchars(
                                    $d['brand']
                                    ?: '-'
                                ) ?>

                            </span>


                            <small class="text-muted">

                                (
                                <?= htmlspecialchars(
                                    $d['tipe']
                                    ?: '-'
                                ) ?>
                                )

                            </small>


                            <br>


                            <small class="text-muted">

                                PN:
                                <?= htmlspecialchars(
                                    $d['part_number']
                                    ?: '-'
                                ) ?>

                            </small>

                        </td>



                        <!-- MESIN & SUB MESIN -->

                        <td>

                            <div class="machine-info">

                                <small class="machine-info-name">

                                    <?= htmlspecialchars(
                                        $nama_m
                                    ) ?>

                                </small>


                                <small class="machine-info-sub">

                                    <?= htmlspecialchars(
                                        $nama_s
                                    ) ?>

                                </small>

                            </div>

                        </td>



                        <!-- KONDISI -->

                        <td>

                            <span
                                class="badge <?= $badgeKondisi ?> badge-condition px-2 py-1"
                            >

                                <?= htmlspecialchars(
                                    $d['kondisi']
                                    ?: 'Baik'
                                ) ?>

                            </span>

                        </td>



                        <!-- AKSI -->

                        <td class="text-center">

                            <div
                                class="action-buttons"
                                role="group"
                            >


                                <!-- DETAIL -->

                                <a
                                    href="detail.php?id=<?= $d['id'] ?>"
                                    class="btn btn-outline-info"
                                    title="Detail Data"
                                >

                                    <i class="bi bi-eye"></i>

                                </a>



                                <!-- EDIT -->

                                <?php if (has_role('user')): ?>
<a
                                    href="edit.php?id=<?= $d['id'] ?>"
                                    class="btn btn-outline-warning"
                                    title="Edit Data"
                                >

                                    <i class="bi bi-pencil-square"></i>

                                </a>
<?php endif; ?>



                                <!-- HAPUS -->

                                <?php if (has_role('admin')): ?>
<a
                                    href="hapus.php?id=<?= $d['id'] ?>"
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus komponen ini?')"
                                    class="btn btn-outline-danger"
                                    title="Hapus Data"
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


                    <!-- DATA KOSONG -->

                    <tr>

                        <td
                            colspan="7"
                            class="text-center py-5"
                        >

                            <div class="text-muted">

                                <i
                                    class="bi bi-inbox display-6 d-block mb-2 text-secondary"
                                ></i>

                                <p class="mb-0 fw-medium">

                                    Data komponen tidak ditemukan
                                    atau belum ada.

                                </p>

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
   LOAD SUB MESIN FILTER
========================================================= */

function loadSubMesinFilter(
    id_mesin,
    selectedSub = ''
) {

    const subDropdown =
        document.getElementById(
            'filter_sub_mesin'
        );


    if (!subDropdown) {
        return;
    }


    if (!id_mesin) {

        subDropdown.innerHTML =
            '<option value="">-- Semua Sub Mesin --</option>';

        return;

    }


    subDropdown.innerHTML =
        '<option value="">Memuat...</option>';


    fetch(
        'get_sub_mesin.php?id_mesin='
        + encodeURIComponent(id_mesin)
    )

    .then(
        response => {

            if (!response.ok) {
                throw new Error(
                    'HTTP error ' + response.status
                );
            }

            return response.text();

        }
    )

    .then(
        data => {

            subDropdown.innerHTML =
                '<option value="">-- Semua Sub Mesin --</option>'
                + data;


            if (selectedSub !== '') {

                subDropdown.value =
                    selectedSub;

            }

        }
    )

    .catch(
        err => {

            console.error(
                'Gagal memuat Sub Mesin:',
                err
            );

            subDropdown.innerHTML =
                '<option value="">-- Semua Sub Mesin --</option>';

        }
    );

}


/* =========================================================
   INISIALISASI FILTER
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function() {

        const currentMesin =
            "<?= (int)$id_mesin ?>";

        const currentSub =
            "<?= (int)$id_sub ?>";


        if (
            currentMesin !== "0"
            &&
            currentMesin !== ""
        ) {

            loadSubMesinFilter(
                currentMesin,
                currentSub
            );

        }

    }
);

</script>



<?php

mysqli_stmt_close($stmt);

include "../template/footer.php";

?>