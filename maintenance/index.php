<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

include "../koneksi.php";

/* =========================================================
   FILTER PARAMETERS
========================================================= */

$filter_mesin     = isset($_GET['mesin']) ? trim($_GET['mesin']) : '';
$filter_sub_mesin = isset($_GET['sub_mesin']) ? trim($_GET['sub_mesin']) : '';
$filter_status    = isset($_GET['status']) ? trim($_GET['status']) : '';
$keyword          = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';


/* =========================================================
   VALIDASI FILTER ID
========================================================= */

$filter_mesin_id = ctype_digit($filter_mesin) && $filter_mesin !== ''
    ? (int) $filter_mesin
    : 0;

$filter_sub_mesin_id = ctype_digit($filter_sub_mesin) && $filter_sub_mesin !== ''
    ? (int) $filter_sub_mesin
    : 0;


/* =========================================================
   BUILD QUERY MAINTENANCE
========================================================= */

$query = "
    SELECT
        rm.*,

        k.nama_bagian,
        k.id_sub_mesin,

        sm.nama_sub_mesin,
        sm.id_mesin,

        m.nama_mesin,
        m.serial_number

    FROM riwayat_maintenance rm

    LEFT JOIN komponen k
        ON rm.id_komponen = k.id

    LEFT JOIN sub_mesin sm
        ON k.id_sub_mesin = sm.id

    LEFT JOIN mesin m
        ON sm.id_mesin = m.id

    WHERE 1 = 1
";


$params = [];
$types  = "";


/* =========================================================
   FILTER MESIN
========================================================= */

if ($filter_mesin_id > 0) {

    $query .= " AND sm.id_mesin = ?";

    $params[] = $filter_mesin_id;
    $types .= "i";
}


/* =========================================================
   FILTER SUB MESIN
========================================================= */

if ($filter_sub_mesin_id > 0) {

    $query .= " AND k.id_sub_mesin = ?";

    $params[] = $filter_sub_mesin_id;
    $types .= "i";
}


/* =========================================================
   FILTER STATUS
========================================================= */

$allowed_status = [
    'Selesai',
    'Proses',
    'Pending'
];

if (
    $filter_status !== '' &&
    in_array($filter_status, $allowed_status, true)
) {

    $query .= " AND rm.status = ?";

    $params[] = $filter_status;
    $types .= "s";
}


/* =========================================================
   SEARCH
========================================================= */

if ($keyword !== '') {

    $search = "%" . $keyword . "%";

    $query .= "
        AND (
            k.nama_bagian LIKE ?
            OR m.nama_mesin LIKE ?
            OR m.serial_number LIKE ?
            OR sm.nama_sub_mesin LIKE ?
            OR rm.teknisi LIKE ?
            OR rm.tindakan LIKE ?
            OR rm.sparepart_diganti LIKE ?
        )
    ";

    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;

    $types .= "sssssss";
}


/* =========================================================
   ORDER
========================================================= */

$query .= "
    ORDER BY
        rm.tanggal DESC,
        rm.id DESC
";


/* =========================================================
   EXECUTE QUERY
========================================================= */

$data_maint_list = [];

$stmt = mysqli_prepare($conn, $query);

if ($stmt) {

    if (!empty($params)) {

        mysqli_stmt_bind_param(
            $stmt,
            $types,
            ...$params
        );
    }

    if (mysqli_stmt_execute($stmt)) {

        $result = mysqli_stmt_get_result($stmt);

        if ($result) {

            while ($row = mysqli_fetch_assoc($result)) {

                $data_maint_list[] = $row;
            }
        }
    }

    mysqli_stmt_close($stmt);
}


/* =========================================================
   DATA MESIN UNTUK FILTER
========================================================= */

$list_mesin = mysqli_query(
    $conn,
    "
    SELECT
        id,
        nama_mesin
    FROM mesin
    ORDER BY nama_mesin ASC
    "
);


/* =========================================================
   DATA SUB MESIN UNTUK FILTER
========================================================= */

$list_sub_mesin = mysqli_query(
    $conn,
    "
    SELECT
        id,
        nama_sub_mesin,
        id_mesin
    FROM sub_mesin
    ORDER BY nama_sub_mesin ASC
    "
);


include "../template/header.php";
?>


<style>

/* =========================================================
   MAINTENANCE PAGE
========================================================= */

.maintenance-page {
    width: 100%;
}


/* =========================================================
   HEADER
========================================================= */

.maintenance-header {
    background: #ffffff;
    border: 0;
    border-radius: 18px;
    box-shadow: 0 3px 15px rgba(15, 23, 42, .06);
}

.maintenance-header h3 {
    letter-spacing: -.3px;
}


/* =========================================================
   CARD
========================================================= */

.maintenance-card {
    background: #ffffff;
    border: 0;
    border-radius: 18px;
    box-shadow: 0 3px 15px rgba(15, 23, 42, .06);
}


/* =========================================================
   FILTER
========================================================= */

.filter-card {
    background: #ffffff;
    border: 0;
    border-radius: 18px;
    box-shadow: 0 3px 15px rgba(15, 23, 42, .06);
}

.filter-card .form-label {
    font-size: 12px;
    color: #64748b;
}

.filter-card .form-select,
.filter-card .form-control {
    min-height: 38px;
    border-color: #e2e8f0;
    font-size: 13px;
}

.filter-card .form-select:focus,
.filter-card .form-control:focus {
    border-color: #0056a6;
    box-shadow: 0 0 0 .2rem rgba(0, 86, 166, .10);
}


/* =========================================================
   TABLE
========================================================= */

.maintenance-table-wrapper {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.maintenance-table {
    min-width: 1050px;
    margin-bottom: 0;
}

.maintenance-table th {
    white-space: nowrap;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    letter-spacing: .3px;
    padding: 13px 12px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.maintenance-table td {
    padding: 13px 12px;
    font-size: 13px;
    border-bottom: 1px solid #edf2f7;
}

.maintenance-table tbody tr:last-child td {
    border-bottom: 0;
}

.maintenance-table tbody tr:hover {
    background: #f8fbff;
}


/* =========================================================
   TABLE CONTENT
========================================================= */

.component-name {
    font-weight: 700;
    color: #1e293b;
    line-height: 1.35;
}

.machine-name {
    color: #0056a6;
    font-weight: 600;
}

.serial-number {
    font-family: monospace;
    color: #0056a6;
    font-size: 11px;
}

.sub-machine {
    color: #64748b;
    font-size: 11px;
}

.action-text {
    color: #334155;
    line-height: 1.45;
}

.sparepart-text {
    color: #64748b;
    font-size: 11px;
    line-height: 1.4;
}


/* =========================================================
   STATUS
========================================================= */

.status-badge {
    min-width: 78px;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    font-size: 11px;
    font-weight: 600;
}


/* =========================================================
   ACTION BUTTON
========================================================= */

.maintenance-actions {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .05);
}

.maintenance-actions .btn {
    width: 36px;
    height: 34px;
    border: 0;
    border-right: 1px solid #e2e8f0;
    border-radius: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
}

.maintenance-actions .btn:last-child {
    border-right: 0;
}

.maintenance-actions .btn:hover {
    background: #f8fafc;
}

.maintenance-actions .btn-detail {
    color: #0dcaf0;
}

.maintenance-actions .btn-edit {
    color: #e0a800;
}

.maintenance-actions .btn-delete {
    color: #dc3545;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.empty-maintenance {
    padding: 60px 20px;
}

.empty-maintenance i {
    font-size: 48px;
    color: #cbd5e1;
}


/* =========================================================
   RESPONSIVE TABLET
========================================================= */

@media (max-width: 991.98px) {

    .maintenance-header {
        padding: 18px !important;
    }

    .maintenance-header h3 {
        font-size: 20px;
    }

    .filter-card .card-body {
        padding: 16px !important;
    }

}


/* =========================================================
   RESPONSIVE MOBILE
========================================================= */

@media (max-width: 767.98px) {

    .maintenance-page {
        padding-left: 8px !important;
        padding-right: 8px !important;
    }

    .maintenance-header {
        border-radius: 14px;
        padding: 16px !important;
        margin-bottom: 14px !important;
    }

    .maintenance-header h3 {
        font-size: 18px;
    }

    .maintenance-header p {
        font-size: 11px;
        line-height: 1.5;
    }

    .maintenance-header .btn {
        width: 100%;
    }

    .filter-card {
        border-radius: 14px;
        margin-bottom: 14px !important;
    }

    .filter-card .card-body {
        padding: 14px !important;
    }

    .filter-buttons {
        width: 100%;
    }

    .filter-buttons .btn {
        flex: 1;
    }

    .maintenance-card {
        border-radius: 14px;
    }

    .maintenance-card-header {
        padding: 14px !important;
        flex-wrap: wrap;
        gap: 8px;
    }

    .maintenance-card-header h5 {
        font-size: 14px;
    }

    .maintenance-table-wrapper {
        border-radius: 0 0 14px 14px;
    }

}


/* =========================================================
   EXTRA SMALL MOBILE
========================================================= */

@media (max-width: 480px) {

    .maintenance-page {
        padding-left: 5px !important;
        padding-right: 5px !important;
    }

    .maintenance-header {
        padding: 14px !important;
    }

    .maintenance-header h3 {
        font-size: 17px;
    }

    .maintenance-header .btn {
        font-size: 12px;
        padding: 9px 12px;
    }

    .filter-card .form-label {
        font-size: 11px;
    }

    .filter-card .form-control,
    .filter-card .form-select {
        font-size: 12px;
    }

}

</style>


<div class="container-fluid maintenance-page mb-3 px-3 py-2">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="maintenance-header p-4 mb-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

            <div>

                <h3 class="fw-bold text-dark mb-1 fs-4">
                    Riwayat Maintenance
                </h3>

                <p class="text-muted small mb-0">
                    Kelola dan pantau seluruh catatan pemeliharaan mesin
                </p>

            </div>


            <div>

                <?php if (has_role('user')): ?>
<a
                    href="tambah.php"
                    class="btn btn-primary fw-semibold rounded-3 px-3 shadow-sm text-nowrap"
                >

                    <i class="bi bi-plus-lg me-1"></i>

                    Tambah Maintenance

                </a>
<?php endif; ?>

            </div>

        </div>

    </div>



    <!-- =====================================================
         FILTER CARD
    ====================================================== -->

    <div class="filter-card mb-4">

        <div class="card-body p-3">

            <form
                method="GET"
                action=""
                class="row g-3 align-items-end"
            >


                <!-- MESIN -->

                <div class="col-12 col-md-3">

                    <label class="form-label fw-bold mb-1">
                        Mesin
                    </label>

                    <select
                        name="mesin"
                        id="filter_mesin"
                        class="form-select form-select-sm rounded-3"
                    >

                        <option value="">
                            -- Semua Mesin --
                        </option>

                        <?php if ($list_mesin): ?>

                            <?php while ($m = mysqli_fetch_assoc($list_mesin)): ?>

                                <option
                                    value="<?= (int) $m['id'] ?>"
                                    <?= ($filter_mesin_id === (int) $m['id']) ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars(
                                        $m['nama_mesin'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>

                </div>



                <!-- SUB MESIN -->

                <div class="col-12 col-md-3">

                    <label class="form-label fw-bold mb-1">
                        Sub Mesin
                    </label>

                    <select
                        name="sub_mesin"
                        id="filter_sub_mesin"
                        class="form-select form-select-sm rounded-3"
                    >

                        <option value="">
                            -- Semua Sub Mesin --
                        </option>

                        <?php if ($list_sub_mesin): ?>

                            <?php while ($sm = mysqli_fetch_assoc($list_sub_mesin)): ?>

                                <?php

                                /*
                                 * Jika mesin dipilih,
                                 * tampilkan sub mesin dari mesin tersebut.
                                 */

                                if (
                                    $filter_mesin_id > 0 &&
                                    (int) $sm['id_mesin'] !== $filter_mesin_id
                                ) {
                                    continue;
                                }

                                ?>

                                <option
                                    value="<?= (int) $sm['id'] ?>"
                                    data-mesin="<?= (int) $sm['id_mesin'] ?>"
                                    <?= ($filter_sub_mesin_id === (int) $sm['id']) ? 'selected' : '' ?>
                                >

                                    <?= htmlspecialchars(
                                        $sm['nama_sub_mesin'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>

                </div>



                <!-- STATUS -->

                <div class="col-12 col-md-2">

                    <label class="form-label fw-bold mb-1">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select form-select-sm rounded-3"
                    >

                        <option value="">
                            -- Semua Status --
                        </option>

                        <option
                            value="Selesai"
                            <?= ($filter_status === 'Selesai') ? 'selected' : '' ?>
                        >
                            Selesai
                        </option>

                        <option
                            value="Proses"
                            <?= ($filter_status === 'Proses') ? 'selected' : '' ?>
                        >
                            Proses
                        </option>

                        <option
                            value="Pending"
                            <?= ($filter_status === 'Pending') ? 'selected' : '' ?>
                        >
                            Pending
                        </option>

                    </select>

                </div>



                <!-- KEYWORD -->

                <div class="col-12 col-md-4">

                    <label class="form-label fw-bold mb-1">
                        Kata Kunci
                    </label>

                    <div class="d-flex gap-2">

                        <input
                            type="text"
                            name="keyword"
                            class="form-control form-control-sm rounded-3"
                            placeholder="Nama bagian / SN / teknisi..."
                            value="<?= htmlspecialchars(
                                $keyword,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >


                        <!-- FILTER -->

                        <button
                            type="submit"
                            class="btn btn-primary btn-sm rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:40px;height:38px;"
                            title="Filter"
                        >

                            <i class="bi bi-funnel"></i>

                        </button>


                        <!-- RESET -->

                        <a
                            href="index.php"
                            class="btn btn-outline-secondary btn-sm rounded-3 d-inline-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:40px;height:38px;"
                            title="Reset Filter"
                        >

                            <i class="bi bi-arrow-counterclockwise"></i>

                        </a>

                    </div>

                </div>

            </form>

        </div>

    </div>



    <!-- =====================================================
         TABLE CARD
    ====================================================== -->

    <div class="maintenance-card overflow-hidden">


        <!-- HEADER TABLE -->

        <div class="maintenance-card-header card-header bg-white py-3 px-4 border-0 d-flex justify-content-between align-items-center">

            <h5 class="fw-bold text-primary mb-0 fs-6">

                <i class="bi bi-tools me-2"></i>

                Daftar Log Maintenance

            </h5>


            <span class="badge bg-light text-primary border rounded-pill px-3 py-2">

                Total:
                <?= count($data_maint_list) ?>
                Data

            </span>

        </div>



        <!-- TABLE -->

        <div class="card-body p-0">

            <div class="maintenance-table-wrapper">

                <table class="table table-hover align-middle maintenance-table">

                    <thead>

                        <tr>

                            <th
                                width="50"
                                class="text-center"
                            >
                                NO
                            </th>

                            <th width="110">
                                TANGGAL
                            </th>

                            <th width="250">
                                KOMPONEN / MESIN
                            </th>

                            <th width="170">
                                JENIS & TEKNISI
                            </th>

                            <th width="300">
                                TINDAKAN & SPAREPART
                            </th>

                            <th
                                width="110"
                                class="text-center"
                            >
                                STATUS
                            </th>

                            <th
                                width="130"
                                class="text-center"
                            >
                                AKSI
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (!empty($data_maint_list)): ?>

                            <?php

                            $no = 1;

                            foreach ($data_maint_list as $m):

                                $status = trim(
                                    $m['status'] ?? ''
                                );


                                /*
                                 * STATUS BADGE
                                 */

                                switch ($status) {

                                    case 'Pending':

                                        $badge_status =
                                            'bg-danger';

                                        break;


                                    case 'Proses':

                                        $badge_status =
                                            'bg-warning text-dark';

                                        break;


                                    case 'Selesai':

                                        $badge_status =
                                            'bg-success';

                                        break;


                                    default:

                                        $badge_status =
                                            'bg-secondary';

                                        break;

                                }


                                /*
                                 * TANGGAL
                                 */

                                $tanggal_display = '-';

                                if (
                                    !empty($m['tanggal']) &&
                                    strtotime($m['tanggal']) !== false
                                ) {

                                    $tanggal_display =
                                        date(
                                            'd/m/Y',
                                            strtotime($m['tanggal'])
                                        );
                                }

                                ?>


                                <tr>


                                    <!-- NO -->

                                    <td class="text-center text-muted">

                                        <?= $no++ ?>

                                    </td>



                                    <!-- TANGGAL -->

                                    <td>

                                        <strong class="text-dark">

                                            <?= $tanggal_display ?>

                                        </strong>

                                    </td>



                                    <!-- KOMPONEN / MESIN -->

                                    <td>

                                        <div class="component-name">

                                            <?= htmlspecialchars(
                                                $m['nama_bagian'] ?? '-',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </div>


                                        <div class="mt-1">

                                            <i class="bi bi-gear me-1 text-secondary"></i>

                                            <span class="machine-name">

                                                <?= htmlspecialchars(
                                                    $m['nama_mesin'] ?? '-',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </span>

                                        </div>


                                        <?php if (!empty($m['nama_sub_mesin'])): ?>

                                            <div class="sub-machine mt-1">

                                                <i class="bi bi-diagram-3 me-1"></i>

                                                <?= htmlspecialchars(
                                                    $m['nama_sub_mesin'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </div>

                                        <?php endif; ?>


                                        <?php if (!empty($m['serial_number'])): ?>

                                            <div class="mt-1">

                                                <small class="text-muted">

                                                    SN:

                                                    <span class="serial-number">

                                                        <?= htmlspecialchars(
                                                            $m['serial_number'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                    </span>

                                                </small>

                                            </div>

                                        <?php endif; ?>

                                    </td>



                                    <!-- JENIS & TEKNISI -->

                                    <td>

                                        <?php

                                        $jenis_maintenance =
                                            $m['jenis_maintenance']
                                            ?? $m['jenis']
                                            ?? 'Maintenance';

                                        ?>


                                        <span class="badge bg-info text-dark rounded-pill mb-2 fw-normal">

                                            <?= htmlspecialchars(
                                                $jenis_maintenance,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </span>


                                        <div>

                                            <small class="text-muted">

                                                <i class="bi bi-person me-1"></i>

                                                <?= htmlspecialchars(
                                                    $m['teknisi'] ?? '-',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </small>

                                        </div>

                                    </td>



                                    <!-- TINDAKAN & SPAREPART -->

                                    <td>

                                        <div class="action-text">

                                            <strong>
                                                Tindakan:
                                            </strong>

                                            <?= htmlspecialchars(
                                                $m['tindakan'] ?? '-',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </div>


                                        <div class="sparepart-text mt-1">

                                            <strong>
                                                Sparepart:
                                            </strong>

                                            <?= htmlspecialchars(
                                                $m['sparepart_diganti'] ?? '-',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </div>

                                    </td>



                                    <!-- STATUS -->

                                    <td class="text-center">

                                        <span class="badge <?= $badge_status ?> status-badge rounded-pill px-3 py-2">

                                            <?= htmlspecialchars(
                                                $status !== ''
                                                    ? $status
                                                    : 'Tidak Ada',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </span>

                                    </td>



                                    <!-- AKSI -->

                                    <td class="text-center">

                                        <?php if (!empty($m['id'])): ?>

                                            <div
                                                class="maintenance-actions"
                                                role="group"
                                                aria-label="Aksi Maintenance"
                                            >


                                                <!-- DETAIL -->

                                                <a
                                                    href="detail.php?id=<?= (int) $m['id'] ?>"
                                                    class="btn btn-detail"
                                                    title="Lihat Detail"
                                                >

                                                    <i class="bi bi-eye"></i>

                                                </a>



                                                <!-- EDIT -->

                                                <?php if (has_role('user')): ?>
<a
                                                    href="edit.php?id=<?= (int) $m['id'] ?>"
                                                    class="btn btn-edit"
                                                    title="Edit Maintenance"
                                                >

                                                    <i class="bi bi-pencil-square"></i>

                                                </a>
<?php endif; ?>



                                                <!-- HAPUS -->

                                                <?php if (has_role('admin')): ?>
<a
                                                    href="hapus.php?id=<?= (int) $m['id'] ?>"
                                                    class="btn btn-delete"
                                                    title="Hapus Maintenance"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus log maintenance ini?');"
                                                >

                                                    <i class="bi bi-trash"></i>

                                                </a>
<?php endif; ?>

                                            </div>

                                        <?php else: ?>

                                            <span class="text-muted small">
                                                -
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <!-- EMPTY -->

                            <tr>

                                <td
                                    colspan="7"
                                    class="text-center"
                                >

                                    <div class="empty-maintenance">

                                        <i class="bi bi-inbox d-block mb-3"></i>

                                        <div class="fw-semibold text-secondary">

                                            Belum ada data log maintenance.

                                        </div>

                                        <?php if (
                                            $filter_mesin_id > 0 ||
                                            $filter_sub_mesin_id > 0 ||
                                            $filter_status !== '' ||
                                            $keyword !== ''
                                        ): ?>

                                            <small class="text-muted d-block mt-1">

                                                Tidak ada data yang sesuai
                                                dengan filter yang dipilih.

                                            </small>


                                            <a
                                                href="index.php"
                                                class="btn btn-sm btn-outline-primary rounded-3 mt-3"
                                            >

                                                <i class="bi bi-arrow-counterclockwise me-1"></i>

                                                Reset Filter

                                            </a>

                                        <?php else: ?>

                                            <small class="text-muted d-block mt-1">

                                                Silakan tambahkan data maintenance
                                                untuk mulai membuat riwayat.

                                            </small>

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

</div>



<script>

/* =========================================================
   FILTER SUB MESIN BERDASARKAN MESIN
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const mesinSelect =
            document.getElementById(
                "filter_mesin"
            );

        const subMesinSelect =
            document.getElementById(
                "filter_sub_mesin"
            );


        if (
            !mesinSelect ||
            !subMesinSelect
        ) {
            return;
        }


        function filterSubMesin() {

            const selectedMesin =
                mesinSelect.value;


            const options =
                subMesinSelect.querySelectorAll(
                    "option[data-mesin]"
                );


            /*
             * Jika semua mesin dipilih,
             * tampilkan semua sub mesin.
             */

            options.forEach(
                function (option) {

                    const mesinId =
                        option.getAttribute(
                            "data-mesin"
                        );


                    if (
                        selectedMesin === "" ||
                        mesinId === selectedMesin
                    ) {

                        option.hidden = false;

                    } else {

                        option.hidden = true;

                        /*
                         * Jika option yang sedang
                         * terpilih bukan bagian mesin,
                         * reset pilihan.
                         */

                        if (
                            option.selected
                        ) {

                            subMesinSelect.value = "";

                        }

                    }

                }
            );

        }


        mesinSelect.addEventListener(
            "change",
            function () {

                filterSubMesin();

                /*
                 * Saat mesin berubah,
                 * sub mesin lama direset.
                 */

                subMesinSelect.value = "";

            }
        );


        filterSubMesin();

    }
);

</script>


<?php
include "../template/footer.php";
?>