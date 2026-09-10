<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

include "../koneksi.php";

/* =========================================================
   UPDATE STATUS MAINTENANCE
========================================================= */
if (isset($_POST['update_status_maintenance'])) {

    $id_maint = intval($_POST['id_maintenance']);
    $status_baru = mysqli_real_escape_string($conn, $_POST['status']);

    mysqli_query(
        $conn,
        "UPDATE riwayat_maintenance 
         SET status = '$status_baru' 
         WHERE id = $id_maint"
    );

    /* Jika maintenance selesai -> kondisi komponen menjadi Baik */
    if ($status_baru == 'Selesai') {

        $get_maint = mysqli_query(
            $conn,
            "SELECT id_komponen 
             FROM riwayat_maintenance 
             WHERE id = $id_maint"
        );

        if ($data_maint = mysqli_fetch_assoc($get_maint)) {

            $id_komp = intval($data_maint['id_komponen']);

            if ($id_komp > 0) {

                mysqli_query(
                    $conn,
                    "UPDATE komponen 
                     SET kondisi = 'Baik' 
                     WHERE id = $id_komp"
                );
            }
        }
    }

    header("Location: index.php");
    exit;
}


/* =========================================================
   UPDATE KONDISI KOMPONEN
========================================================= */
if (isset($_POST['update_kondisi_komponen'])) {

    $id_komp = intval($_POST['id_komponen']);
    $kondisi_baru = mysqli_real_escape_string(
        $conn,
        $_POST['kondisi']
    );

    mysqli_query(
        $conn,
        "UPDATE komponen 
         SET kondisi = '$kondisi_baru' 
         WHERE id = $id_komp"
    );

    header("Location: index.php");
    exit;
}


/* =========================================================
   HEADER
========================================================= */
include "../template/header.php";


/* =========================================================
   STATISTIK DASHBOARD
========================================================= */

/* TOTAL LOKASI UNIK */
$d_total_lokasi = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(DISTINCT lokasi) AS total
         FROM area_bagian
         WHERE lokasi IS NOT NULL
         AND lokasi != ''"
    )
)['total'];


/* TOTAL AREA / BAGIAN */
$d_total_area = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM area_bagian"
    )
)['total'];


/* TOTAL MESIN */
$d_total_mesin = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM mesin"
    )
)['total'];


/* TOTAL KOMPONEN */
$d_total_komponen = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM komponen"
    )
)['total'];


/* KOMPONEN PERLU PERHATIAN */
$d_komponen_perhatian = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total 
         FROM komponen 
         WHERE kondisi != 'Baik'"
    )
)['total'];


/* MAINTENANCE BULAN INI */
$bulan_ini = date('Y-m');

$d_maint_bulan_ini = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM riwayat_maintenance
         WHERE DATE_FORMAT(tanggal,'%Y-%m')='$bulan_ini'"
    )
)['total'];


/* =========================================================
   MAINTENANCE TERBARU
========================================================= */

$q_maintenance = mysqli_query(
    $conn,
    "SELECT
        rm.*,
        k.nama_bagian,
        m.nama_mesin

     FROM riwayat_maintenance rm

     LEFT JOIN komponen k
        ON rm.id_komponen = k.id

     LEFT JOIN sub_mesin sm
        ON k.id_sub_mesin = sm.id

     LEFT JOIN mesin m
        ON sm.id_mesin = m.id

     ORDER BY rm.tanggal DESC
     LIMIT 5"
);

$data_maintenance_list = [];

if ($q_maintenance && mysqli_num_rows($q_maintenance) > 0) {

    while ($row = mysqli_fetch_assoc($q_maintenance)) {

        $data_maintenance_list[] = $row;
    }
}


/* =========================================================
   KOMPONEN BERMASALAH
========================================================= */

$q_komponen = mysqli_query(
    $conn,
    "SELECT
        k.*,
        m.nama_mesin

     FROM komponen k

     LEFT JOIN sub_mesin sm
        ON k.id_sub_mesin = sm.id

     LEFT JOIN mesin m
        ON sm.id_mesin = m.id

     WHERE k.kondisi != 'Baik'

     ORDER BY k.id DESC

     LIMIT 5"
);

$data_komponen_list = [];

if ($q_komponen && mysqli_num_rows($q_komponen) > 0) {

    while ($row = mysqli_fetch_assoc($q_komponen)) {

        $data_komponen_list[] = $row;
    }
}


/* =========================================================
   DATA AREA + MESIN
========================================================= */

$q_area_mesin = mysqli_query(
    $conn,
    "SELECT
        ab.id AS area_id,
        ab.lokasi,

        m.id AS mesin_id,
        m.nama_mesin,
        m.serial_number AS sn_mesin

     FROM area_bagian ab

     LEFT JOIN jenis_mesin jm
        ON jm.id_area = ab.id

     LEFT JOIN mesin m
        ON m.id_jenis_mesin = jm.id

     ORDER BY ab.lokasi ASC, m.nama_mesin ASC"
);


/* =========================================================
   GROUPING AREA
========================================================= */

$area_list = [];

if ($q_area_mesin) {

    while ($row = mysqli_fetch_assoc($q_area_mesin)) {

        $area_id = $row['area_id'];

        if (!isset($area_list[$area_id])) {

            $area_list[$area_id] = [
                'id' => $area_id,
                'lokasi' => $row['lokasi'],
                'mesin' => []
            ];
        }

        if (!empty($row['mesin_id'])) {

            $area_list[$area_id]['mesin'][] = [
                'id' => $row['mesin_id'],
                'nama' => $row['nama_mesin'],
                'sn' => $row['sn_mesin']
            ];
        }
    }
}

?>


<style>

/* =========================================================
   GLOBAL RESPONSIVE DASHBOARD
========================================================= */

.dashboard-page {
    padding-bottom: 30px;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}


/* =========================================================
   HEADER
========================================================= */

.dashboard-main-header {
    background: #ffffff;
    border: 1px solid #e7edf5;
    border-radius: 18px;
    padding: 22px 25px;
    box-shadow: 0 5px 20px rgba(20, 50, 90, 0.05);
}

.dashboard-main-header .dashboard-title {
    font-size: 27px;
    font-weight: 800;
    color: #123b67;
    margin-bottom: 5px;
    line-height: 1.25;
}

.dashboard-main-header .dashboard-subtitle {
    color: #738197;
    font-size: 14px;
    margin: 0;
    line-height: 1.5;
}

.dashboard-date-box {
    background: #f0f6ff;
    border: 1px solid #dceaff;
    border-radius: 12px;
    padding: 11px 16px;
    color: #075cb0;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
}


/* =========================================================
   STAT CARD
========================================================= */

.dashboard-stat {
    position: relative;
    overflow: hidden;
    min-height: 130px;
    height: 100%;
    border-radius: 18px;
    padding: 20px;
    color: #ffffff;
    box-shadow: 0 8px 22px rgba(20, 50, 90, 0.10);
    transition: all .25s ease;
}

.dashboard-stat:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(20, 50, 90, 0.15);
}

.dashboard-stat::after {
    content: "";
    position: absolute;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    right: -30px;
    top: -35px;
    background: rgba(255,255,255,.10);
}

.dashboard-stat .stat-icon-modern {
    position: absolute;
    right: 20px;
    bottom: 17px;
    font-size: 43px;
    color: rgba(255,255,255,.25);
    z-index: 2;
}

.dashboard-stat .stat-label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .7px;
    opacity: .9;
}

.dashboard-stat .stat-number {
    font-size: 34px;
    line-height: 1.1;
    font-weight: 800;
    margin-top: 7px;
}

.dashboard-stat .stat-desc {
    font-size: 12px;
    margin-top: 5px;
    opacity: .85;
}


/* =========================================================
   WARNA STAT CARD
========================================================= */

.stat-blue {
    background: linear-gradient(135deg, #0866c6, #1453c7);
}

.stat-cyan {
    background: linear-gradient(135deg, #079eb7, #13b8c9);
}

.stat-orange {
    background: linear-gradient(135deg, #ed7300, #f89a10);
}

.stat-purple {
    background: linear-gradient(135deg, #6f42c1, #8e5bd6);
}

.stat-green {
    background: linear-gradient(135deg, #05976e, #13b98a);
}


/* =========================================================
   QUICK ACTION
========================================================= */

.quick-action-card {
    background: #ffffff;
    border: 1px solid #e5ebf3;
    border-radius: 18px;
    padding: 20px 22px;
    box-shadow: 0 5px 20px rgba(20, 50, 90, .05);
}

.quick-action-title {
    font-size: 19px;
    font-weight: 800;
    color: #163d66;
}

.quick-action-subtitle {
    color: #7a8798;
    font-size: 13px;
    line-height: 1.5;
}

.quick-action-buttons {
    width: 100%;
}

.quick-btn {
    border-radius: 10px;
    padding: 9px 13px;
    font-size: 12px;
    font-weight: 700;
    transition: all .2s ease;
    white-space: nowrap;
}

.quick-btn:hover {
    transform: translateY(-2px);
}


/* =========================================================
   CARD UMUM
========================================================= */

.modern-card {
    background: #ffffff;
    border: 1px solid #e5ebf3;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(20, 50, 90, .05);
    width: 100%;
}

.modern-card-header {
    padding: 17px 20px;
    border-bottom: 1px solid #edf1f6;
    background: #ffffff;
}

.modern-card-title {
    color: #075cb0;
    font-size: 15px;
    font-weight: 800;
    margin: 0;
    line-height: 1.4;
}

.modern-card-subtitle {
    color: #8490a0;
    font-size: 12px;
    line-height: 1.4;
}


/* =========================================================
   AREA & MESIN
========================================================= */

.area-section {
    background: #f7faff;
    border: 1px solid #e3ebf5;
    border-radius: 15px;
    padding: 15px;
    height: 100%;
    transition: all .2s ease;
}

.area-section:hover {
    border-color: #a9c9ed;
    box-shadow: 0 7px 20px rgba(20, 80, 140, .07);
}

.area-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.area-name {
    font-size: 14px;
    font-weight: 800;
    color: #163d66;
    word-break: break-word;
}

.area-icon {
    width: 35px;
    height: 35px;
    min-width: 35px;
    border-radius: 10px;
    background: #e5f1ff;
    color: #0866c6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.area-count {
    background: #e8f2ff;
    color: #0866c6;
    font-size: 10px;
    font-weight: 800;
    padding: 5px 9px;
    border-radius: 20px;
    white-space: nowrap;
}

.machine-item {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    border: 1px solid #e9eef5;
    border-radius: 11px;
    padding: 10px 11px;
    margin-bottom: 8px;
    text-decoration: none;
    transition: all .2s ease;
    min-width: 0;
}

.machine-item:last-child {
    margin-bottom: 0;
}

.machine-item:hover {
    border-color: #b5d4f5;
    background: #f9fcff;
    transform: translateX(2px);
}

.machine-icon {
    width: 33px;
    height: 33px;
    min-width: 33px;
    border-radius: 9px;
    background: #edf5ff;
    color: #0866c6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.machine-item > div:nth-child(2) {
    min-width: 0;
    flex: 1;
}

.machine-name {
    font-size: 12px;
    font-weight: 800;
    color: #263d55;
    word-break: break-word;
}

.machine-sn {
    font-size: 10px;
    color: #8995a4;
    word-break: break-word;
}

.machine-arrow {
    margin-left: auto;
    color: #a4afbd;
    min-width: 12px;
}

.empty-machine {
    padding: 15px;
    text-align: center;
    border: 1px dashed #d8e0ea;
    border-radius: 10px;
    color: #98a3b0;
    font-size: 11px;
}


/* =========================================================
   TABLE
========================================================= */

.dashboard-table {
    min-width: 650px;
}

.dashboard-table thead th {
    background: #f7f9fc;
    color: #68778b;
    font-size: 10px;
    letter-spacing: .5px;
    font-weight: 800;
    border-bottom: 1px solid #e7edf4;
    padding: 12px 15px;
    white-space: nowrap;
}

.dashboard-table tbody td {
    padding: 12px 15px;
    font-size: 12px;
    border-color: #edf1f5;
    vertical-align: middle;
}

.dashboard-table tbody tr:hover {
    background: #f9fbfd;
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}


/* =========================================================
   KOMPONEN BERMASALAH
========================================================= */

.problem-item {
    padding: 13px 17px;
    border-bottom: 1px solid #edf1f5;
}

.problem-item:last-child {
    border-bottom: 0;
}

.problem-icon {
    width: 37px;
    height: 37px;
    min-width: 37px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff0f0;
    color: #dc3545;
}

.problem-item .flex-grow-1 {
    min-width: 0;
}

.problem-item .fw-bold {
    word-break: break-word;
}


/* =========================================================
   MODAL
========================================================= */

.modal-content {
    max-width: 100%;
}

.modal-dialog {
    width: calc(100% - 30px);
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 991.98px) {

    .dashboard-main-header {
        padding: 20px;
    }

    .dashboard-main-header .dashboard-title {
        font-size: 24px;
    }

    .dashboard-date-box {
        margin-top: 15px;
    }

    .quick-action-buttons {
        margin-top: 15px;
        justify-content: flex-start !important;
    }

    .area-section {
        min-height: auto;
    }
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 767.98px) {

    .dashboard-page {
        padding-left: 0;
        padding-right: 0;
        padding-bottom: 20px;
    }


    /* HEADER */

    .dashboard-main-header {
        padding: 17px;
        border-radius: 15px;
        margin-bottom: 18px !important;
    }

    .dashboard-main-header .row {
        --bs-gutter-x: 0;
    }

    .dashboard-main-header .dashboard-title {
        font-size: 20px;
        line-height: 1.3;
    }

    .dashboard-main-header .dashboard-subtitle {
        font-size: 12px;
        line-height: 1.5;
    }

    .dashboard-main-header .d-flex.align-items-center.gap-3 {
        align-items: flex-start !important;
        gap: 10px !important;
    }

    .dashboard-main-header .d-flex.align-items-center.gap-3 > div:first-child {
        width: 42px !important;
        height: 42px !important;
        min-width: 42px;
        border-radius: 12px !important;
        font-size: 19px !important;
    }

    .dashboard-date-box {
        width: 100%;
        justify-content: center;
        padding: 10px 8px;
        font-size: 11px;
        margin-top: 15px;
        white-space: nowrap;
    }


    /* STAT */

    .dashboard-stat {
        min-height: 115px;
        padding: 17px;
        border-radius: 15px;
    }

    .dashboard-stat .stat-label {
        font-size: 9px;
        letter-spacing: .5px;
    }

    .dashboard-stat .stat-number {
        font-size: 28px;
        margin-top: 5px;
    }

    .dashboard-stat .stat-desc {
        font-size: 10px;
        max-width: 75%;
    }

    .dashboard-stat .stat-icon-modern {
        right: 15px;
        bottom: 15px;
        font-size: 35px;
    }


    /* QUICK ACTION */

    .quick-action-card {
        padding: 17px;
        border-radius: 15px;
    }

    .quick-action-title {
        font-size: 17px;
    }

    .quick-action-subtitle {
        font-size: 12px;
    }

    .quick-action-buttons {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px !important;
        margin-top: 15px;
    }

    .quick-btn {
        width: 100%;
        padding: 9px 6px;
        font-size: 11px;
        min-height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }


    /* CARD */

    .modern-card {
        border-radius: 15px;
    }

    .modern-card-header {
        padding: 15px;
    }

    .modern-card-title {
        font-size: 14px;
    }

    .modern-card-subtitle {
        font-size: 11px;
    }


    /* AREA */

    .area-section {
        padding: 13px;
        border-radius: 13px;
    }

    .area-name {
        font-size: 13px;
    }

    .area-icon {
        width: 32px;
        height: 32px;
        min-width: 32px;
    }

    .area-count {
        font-size: 9px;
        padding: 4px 7px;
    }

    .machine-item {
        padding: 9px;
    }

    .machine-icon {
        width: 31px;
        height: 31px;
        min-width: 31px;
    }

    .machine-name {
        font-size: 11px;
    }

    .machine-sn {
        font-size: 9px;
    }


    /* TABLE */

    .dashboard-table {
        min-width: 620px;
    }

    .dashboard-table thead th {
        padding: 10px 12px;
        font-size: 9px;
    }

    .dashboard-table tbody td {
        padding: 10px 12px;
        font-size: 11px;
    }


    /* PROBLEM */

    .problem-item {
        padding: 12px 15px;
    }

    .problem-icon {
        width: 34px;
        height: 34px;
        min-width: 34px;
    }

    .problem-item .badge {
        font-size: 8px !important;
        white-space: nowrap;
    }


    /* MODAL */

    .modal-dialog {
        width: calc(100% - 24px);
        margin: 12px auto;
    }

    .modal-content {
        border-radius: 16px !important;
    }

}


/* =========================================================
   HP KECIL
========================================================= */

@media (max-width: 575.98px) {

    .dashboard-main-header {
        padding: 15px;
    }

    .dashboard-main-header .dashboard-title {
        font-size: 18px;
    }

    .dashboard-main-header .dashboard-subtitle {
        font-size: 11px;
    }

    .dashboard-date-box {
        font-size: 10px;
        gap: 6px !important;
    }

    .dashboard-date-box span:nth-of-type(2) {
        display: none;
    }


    /* STAT CARD 2 KOLOM */

    .row.g-3.mb-4 > .col-xl,
    .row.g-3.mb-4 > .col-lg-4,
    .row.g-3.mb-4 > .col-md-6 {
        width: 50%;
    }

    .dashboard-stat {
        min-height: 105px;
        padding: 14px;
    }

    .dashboard-stat .stat-number {
        font-size: 25px;
    }

    .dashboard-stat .stat-desc {
        font-size: 9px;
    }

    .dashboard-stat .stat-icon-modern {
        font-size: 29px;
        right: 12px;
        bottom: 12px;
    }


    /* QUICK ACTION */

    .quick-action-buttons {
        grid-template-columns: 1fr 1fr;
    }

    .quick-btn {
        font-size: 10px;
        padding: 8px 4px;
    }

    .quick-btn i {
        margin-right: 3px !important;
    }


    /* CARD HEADER */

    .modern-card-header {
        padding: 13px;
    }

    .modern-card-header .btn {
        font-size: 10px;
        padding: 6px 8px;
    }


    /* AREA */

    .area-section {
        padding: 12px;
    }

    .area-header {
        align-items: flex-start;
    }


    /* MAINTENANCE */

    .dashboard-table {
        min-width: 600px;
    }


    /* MODAL */

    .modal-dialog {
        width: calc(100% - 20px);
    }

}


/* =========================================================
   HP SANGAT KECIL
========================================================= */

@media (max-width: 380px) {

    .dashboard-main-header .dashboard-title {
        font-size: 16px;
    }

    .dashboard-main-header .dashboard-subtitle {
        font-size: 10px;
    }

    .dashboard-date-box {
        font-size: 9px;
    }

    .dashboard-stat {
        min-height: 100px;
        padding: 12px;
    }

    .dashboard-stat .stat-label {
        font-size: 8px;
    }

    .dashboard-stat .stat-number {
        font-size: 22px;
    }

    .dashboard-stat .stat-desc {
        font-size: 8px;
    }

    .dashboard-stat .stat-icon-modern {
        font-size: 25px;
    }

    .quick-action-card {
        padding: 14px;
    }

    .quick-action-title {
        font-size: 15px;
    }

    .quick-action-subtitle {
        font-size: 10px;
    }

    .quick-btn {
        font-size: 9px;
        min-height: 38px;
    }

    .modern-card-title {
        font-size: 13px;
    }

    .modern-card-subtitle {
        font-size: 10px;
    }

}


/* =========================================================
   MENCEGAH OVERFLOW
========================================================= */

img,
svg,
video,
iframe {
    max-width: 100%;
}

button,
a {
    max-width: 100%;
}

</style>


<div class="container-fluid dashboard-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="dashboard-main-header mb-4">

        <div class="row align-items-center">

            <div class="col-lg-8">

                <div class="d-flex align-items-center gap-3">

                    <div
                        style="
                        width:48px;
                        height:48px;
                        border-radius:14px;
                        background:#eaf3ff;
                        color:#0866c6;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        font-size:23px;
                        flex-shrink:0;
                        "
                    >
                        <i class="bi bi-speedometer2"></i>
                    </div>

                    <div style="min-width:0;">

                        <h2 class="dashboard-title">
                            Inventory & Maintenance System
                        </h2>

                        <p class="dashboard-subtitle">
                            PT Garudafood Putra Putri Jaya Tbk —
                            Monitoring & Rekapitulasi Pemeliharaan.
                        </p>

                    </div>

                </div>

            </div>


            <div class="col-lg-4 text-lg-end">

                <div class="dashboard-date-box d-inline-flex align-items-center gap-2">

                    <i class="bi bi-calendar3"></i>

                    <span>
                        <?= date('d F Y') ?>
                    </span>

                    <span
                        style="
                        width:1px;
                        height:18px;
                        background:#cbd9ea;
                        "
                    ></span>

                    <i class="bi bi-clock"></i>

                    <span id="jam-realtime">
                        --:--:--
                    </span>

                    <span>
                        WIB
                    </span>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         STATISTIK
    ====================================================== -->

    <div class="row g-3 mb-4">


        <!-- LOKASI -->

        <div class="col-xl col-lg-4 col-md-6">

            <div class="dashboard-stat stat-blue">

                <div class="stat-label">
                    LOKASI
                </div>

                <div class="stat-number">
                    <?= $d_total_lokasi ?>
                </div>

                <div class="stat-desc">
                    Lokasi Terdaftar
                </div>

                <div class="stat-icon-modern">
                    <i class="bi bi-geo-alt-fill"></i>
                </div>

            </div>

        </div>


        <!-- AREA -->

        <div class="col-xl col-lg-4 col-md-6">

            <div class="dashboard-stat stat-cyan">

                <div class="stat-label">
                    AREA / BAGIAN
                </div>

                <div class="stat-number">
                    <?= $d_total_area ?>
                </div>

                <div class="stat-desc">
                    Area / Bagian Terdaftar
                </div>

                <div class="stat-icon-modern">
                    <i class="bi bi-diagram-3-fill"></i>
                </div>

            </div>

        </div>


        <!-- MESIN -->

        <div class="col-xl col-lg-4 col-md-6">

            <div class="dashboard-stat stat-orange">

                <div class="stat-label">
                    TOTAL MESIN
                </div>

                <div class="stat-number">
                    <?= $d_total_mesin ?>
                </div>

                <div class="stat-desc">
                    Mesin Terdaftar
                </div>

                <div class="stat-icon-modern">
                    <i class="bi bi-gear-wide-connected"></i>
                </div>

            </div>

        </div>


        <!-- KOMPONEN -->

        <div class="col-xl col-lg-4 col-md-6">

            <div class="dashboard-stat stat-purple">

                <div class="stat-label">
                    TOTAL KOMPONEN
                </div>

                <div class="stat-number">
                    <?= $d_total_komponen ?>
                </div>

                <div class="stat-desc">
                    Komponen Terdaftar
                </div>

                <div class="stat-icon-modern">
                    <i class="bi bi-cpu-fill"></i>
                </div>

            </div>

        </div>


        <!-- MAINTENANCE -->

        <div class="col-xl col-lg-4 col-md-6">

            <div class="dashboard-stat stat-green">

                <div class="stat-label">
                    MAINTENANCE
                </div>

                <div class="stat-number">
                    <?= $d_maint_bulan_ini ?>
                </div>

                <div class="stat-desc">
                    Aktivitas Bulan Ini
                </div>

                <div class="stat-icon-modern">
                    <i class="bi bi-tools"></i>
                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         QUICK ACTION
    ====================================================== -->

    <div class="quick-action-card mb-4">

        <div class="row align-items-center">

            <div class="col-lg-4">

                <div class="quick-action-title">

                    <i class="bi bi-lightning-charge-fill text-warning me-1"></i>

                    Quick Action

                </div>

                <div class="quick-action-subtitle mt-1">

                    Akses cepat untuk mengelola data sistem.

                </div>

            </div>


            <div class="col-lg-8">

                <div class="quick-action-buttons d-flex justify-content-lg-end flex-wrap gap-2">


                    <a
                        href="../master/area.php"
                        class="btn btn-outline-primary quick-btn"
                    >
                        <i class="bi bi-geo-alt me-1"></i>
                        Area
                    </a>


                    <a
                        href="../master/jenis_mesin.php"
                        class="btn btn-outline-primary quick-btn"
                    >
                        <i class="bi bi-grid me-1"></i>
                        Jenis Mesin
                    </a>


                    <a
                        href="../mesin/tambah.php"
                        class="btn btn-primary quick-btn"
                    >
                        <i class="bi bi-plus-circle me-1"></i>
                        Mesin
                    </a>


                    <a
                        href="../komponen/tambah.php"
                        class="btn btn-primary quick-btn"
                    >
                        <i class="bi bi-cpu me-1"></i>
                        Komponen
                    </a>


                    <a
                        href="../maintenance/tambah.php"
                        class="btn btn-warning quick-btn text-dark"
                    >
                        <i class="bi bi-tools me-1"></i>
                        Maintenance
                    </a>


                    <a
                        href="#daftar-area"
                        class="btn btn-dark quick-btn"
                    >
                        <i class="bi bi-diagram-3 me-1"></i>
                        Area & Mesin
                    </a>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         DAFTAR AREA & MESIN
    ====================================================== -->

    <div
        class="modern-card mb-4"
        id="daftar-area"
    >

        <div class="modern-card-header">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                <div style="min-width:0;">

                    <h5 class="modern-card-title">

                        <i class="bi bi-diagram-3 me-2"></i>

                        Daftar Area & Mesin

                    </h5>

                    <div class="modern-card-subtitle mt-1">

                        Struktur lokasi area beserta mesin yang terdaftar.

                    </div>

                </div>


                <a
                    href="../master/area.php"
                    class="btn btn-sm btn-outline-primary flex-shrink-0"
                >
                    <i class="bi bi-pencil-square me-1"></i>
                    Kelola Area
                </a>

            </div>

        </div>


        <div class="p-3">

            <?php if (!empty($area_list)): ?>

                <div class="row g-3">

                    <?php foreach ($area_list as $area): ?>

                        <div class="col-xl-4 col-lg-6">

                            <div class="area-section">


                                <!-- AREA HEADER -->

                                <div class="area-header">

                                    <div class="d-flex align-items-center gap-2"
                                         style="min-width:0;">

                                        <div class="area-icon">

                                            <i class="bi bi-geo-alt-fill"></i>

                                        </div>

                                        <div style="min-width:0;">

                                            <div class="area-name">

                                                <?= htmlspecialchars(
                                                    $area['lokasi']
                                                ) ?>

                                            </div>

                                            <div
                                                style="
                                                font-size:10px;
                                                color:#8a96a6;
                                                "
                                            >
                                                Area / Bagian
                                            </div>

                                        </div>

                                    </div>


                                    <span class="area-count">

                                        <?= count($area['mesin']) ?> Mesin

                                    </span>

                                </div>


                                <!-- DAFTAR MESIN -->

                                <?php if (!empty($area['mesin'])): ?>

                                    <?php foreach ($area['mesin'] as $mesin): ?>

                                        <a
                                            href="../mesin/detail.php?id=<?= $mesin['id'] ?>"
                                            class="machine-item"
                                        >

                                            <div class="machine-icon">

                                                <i class="bi bi-gear-wide-connected"></i>

                                            </div>


                                            <div>

                                                <div class="machine-name">

                                                    <?= htmlspecialchars(
                                                        $mesin['nama']
                                                    ) ?>

                                                </div>


                                                <div class="machine-sn">

                                                    <i class="bi bi-upc-scan me-1"></i>

                                                    SN:
                                                    <?= !empty($mesin['sn'])
                                                        ? htmlspecialchars($mesin['sn'])
                                                        : '-'
                                                    ?>

                                                </div>

                                            </div>


                                            <div class="machine-arrow">

                                                <i class="bi bi-chevron-right"></i>

                                            </div>

                                        </a>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <div class="empty-machine">

                                        <i class="bi bi-inbox me-1"></i>

                                        Belum ada mesin pada area ini.

                                    </div>

                                <?php endif; ?>


                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="text-center py-5">

                    <div
                        style="
                        width:60px;
                        height:60px;
                        border-radius:16px;
                        background:#f0f4f8;
                        color:#9aa7b5;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        margin:auto;
                        font-size:25px;
                        "
                    >

                        <i class="bi bi-geo-alt"></i>

                    </div>

                    <h6 class="fw-bold mt-3">
                        Belum ada data area
                    </h6>

                    <p class="text-muted small mb-3">
                        Tambahkan area terlebih dahulu untuk menampilkan struktur mesin.
                    </p>

                    <a
                        href="../master/area.php"
                        class="btn btn-primary btn-sm"
                    >
                        <i class="bi bi-plus-circle me-1"></i>
                        Tambah Area
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!-- =====================================================
         MAINTENANCE + KOMPONEN
    ====================================================== -->

    <div class="row g-4">


        <!-- MAINTENANCE -->

        <div class="col-xl-8">

            <div class="modern-card h-100">

                <div class="modern-card-header">

                    <div class="d-flex justify-content-between align-items-center gap-2">

                        <div style="min-width:0;">

                            <h5 class="modern-card-title">

                                <i class="bi bi-clock-history me-2"></i>

                                Maintenance Terbaru

                            </h5>

                            <div class="modern-card-subtitle mt-1">

                                5 aktivitas maintenance terakhir.

                            </div>

                        </div>


                        <a
                            href="../maintenance/index.php"
                            class="btn btn-sm btn-outline-primary flex-shrink-0"
                        >
                            Lihat Semua
                        </a>

                    </div>

                </div>


                <div class="table-responsive">

                    <table class="table dashboard-table align-middle mb-0">

                        <thead>

                            <tr>

                                <th width="125">
                                    TANGGAL
                                </th>

                                <th>
                                    KOMPONEN
                                </th>

                                <th>
                                    TINDAKAN
                                </th>

                                <th
                                    width="120"
                                    class="text-center"
                                >
                                    STATUS
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                        <?php if (!empty($data_maintenance_list)): ?>

                            <?php foreach ($data_maintenance_list as $m): ?>

                                <?php

                                $badge = "bg-success";

                                if ($m['status'] == "Pending") {
                                    $badge = "bg-danger";
                                }

                                if ($m['status'] == "Proses") {
                                    $badge = "bg-warning text-dark";
                                }

                                ?>


                                <tr>

                                    <td>

                                        <strong>

                                            <?= date(
                                                'd M Y',
                                                strtotime($m['tanggal'])
                                            ) ?>

                                        </strong>

                                        <br>

                                        <small class="text-muted">

                                            <?= date(
                                                'H:i',
                                                strtotime($m['tanggal'])
                                            ) ?>

                                        </small>

                                    </td>


                                    <td>

                                        <div class="d-flex align-items-center">

                                            <div
                                                class="icon-circle me-2"
                                                style="
                                                width:34px;
                                                height:34px;
                                                flex-shrink:0;
                                                "
                                            >

                                                <i class="bi bi-cpu"></i>

                                            </div>


                                            <div style="min-width:0;">

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $m['nama_bagian'] ?? '-'
                                                    ) ?>

                                                </strong>

                                                <br>

                                                <small class="text-muted">

                                                    <?= htmlspecialchars(
                                                        $m['nama_mesin'] ?? '-'
                                                    ) ?>

                                                </small>

                                            </div>

                                        </div>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $m['tindakan'] ?? '-'
                                        ) ?>

                                    </td>


                                    <td class="text-center">

                                        <button
                                            type="button"
                                            class="btn badge <?= $badge ?> border-0 px-3 py-2 rounded-pill fw-semibold"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalStatusMaint<?= $m['id'] ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $m['status']
                                            ) ?>

                                            <i
                                                class="bi bi-pencil-fill ms-1"
                                                style="font-size:8px"
                                            ></i>

                                        </button>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="4"
                                    class="text-center py-5"
                                >

                                    <i
                                        class="bi bi-inbox fs-1 text-secondary"
                                    ></i>

                                    <div class="mt-2 text-muted small">

                                        Belum ada data maintenance.

                                    </div>

                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        <!-- KOMPONEN BERMASALAH -->

        <div class="col-xl-4">

            <div class="modern-card h-100">

                <div class="modern-card-header">

                    <h5 class="modern-card-title">

                        <i class="bi bi-exclamation-triangle text-danger me-2"></i>

                        Komponen Bermasalah

                    </h5>

                    <div class="modern-card-subtitle mt-1">

                        Kondisi selain Baik.

                    </div>

                </div>


                <?php if (!empty($data_komponen_list)): ?>

                    <?php foreach ($data_komponen_list as $k): ?>

                        <div class="problem-item">

                            <div class="d-flex align-items-center gap-3">

                                <div class="problem-icon">

                                    <i class="bi bi-cpu"></i>

                                </div>


                                <div class="flex-grow-1">

                                    <div class="fw-bold small">

                                        <?= htmlspecialchars(
                                            $k['nama_bagian']
                                        ) ?>

                                    </div>

                                    <div
                                        class="text-muted"
                                        style="font-size:10px"
                                    >

                                        <?= htmlspecialchars(
                                            $k['nama_mesin'] ?? '-'
                                        ) ?>

                                    </div>

                                </div>


                                <?php

                                $badge_problem = "bg-danger";

                                if (
                                    $k['kondisi'] ==
                                    "Perlu Pemeriksaan"
                                ) {

                                    $badge_problem =
                                        "bg-warning text-dark";
                                }

                                ?>


                                <button
                                    type="button"
                                    class="btn badge <?= $badge_problem ?> border-0 rounded-pill px-2 py-2"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalKondisiKomp<?= $k['id'] ?>"
                                    style="font-size:9px; flex-shrink:0;"
                                >

                                    <?= htmlspecialchars(
                                        $k['kondisi']
                                    ) ?>

                                </button>

                            </div>

                        </div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="text-center py-5 px-3">

                        <div
                            style="
                            width:60px;
                            height:60px;
                            margin:auto;
                            border-radius:50%;
                            background:#e9f8f1;
                            color:#0a996c;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size:28px;
                            "
                        >

                            <i class="bi bi-check-lg"></i>

                        </div>

                        <div class="fw-bold mt-3">

                            Semua Komponen Baik

                        </div>

                        <div class="text-muted small mt-1">

                            Tidak ada komponen yang membutuhkan perhatian.

                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     MODAL STATUS MAINTENANCE
========================================================= -->

<?php foreach ($data_maintenance_list as $m): ?>

<div
    class="modal fade"
    id="modalStatusMaint<?= $m['id'] ?>"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered modal-sm">

        <div class="modal-content rounded-4 border-0 shadow">

            <form method="POST">

                <div class="modal-header border-0 pb-0">

                    <h6 class="modal-title fw-bold">
                        Update Status Maintenance
                    </h6>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body py-3">

                    <input
                        type="hidden"
                        name="id_maintenance"
                        value="<?= $m['id'] ?>"
                    >


                    <label class="form-label small text-muted">
                        Pilih Status Baru:
                    </label>


                    <select
                        name="status"
                        class="form-select rounded-3"
                    >

                        <option
                            value="Proses"
                            <?= $m['status'] == 'Proses'
                                ? 'selected'
                                : '' ?>
                        >
                            Proses
                        </option>

                        <option
                            value="Selesai"
                            <?= $m['status'] == 'Selesai'
                                ? 'selected'
                                : '' ?>
                        >
                            Selesai
                        </option>

                        <option
                            value="Pending"
                            <?= $m['status'] == 'Pending'
                                ? 'selected'
                                : '' ?>
                        >
                            Pending
                        </option>

                    </select>

                </div>


                <div class="modal-footer border-0 pt-0">

                    <button
                        type="submit"
                        name="update_status_maintenance"
                        class="btn btn-primary btn-sm rounded-3 w-100"
                    >

                        <i class="bi bi-check-lg me-1"></i>

                        Simpan Status

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endforeach; ?>


<!-- =========================================================
     MODAL KONDISI KOMPONEN
========================================================= -->

<?php foreach ($data_komponen_list as $k): ?>

<div
    class="modal fade"
    id="modalKondisiKomp<?= $k['id'] ?>"
    tabindex="-1"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered modal-sm">

        <div class="modal-content rounded-4 border-0 shadow">

            <form method="POST">

                <div class="modal-header border-0 pb-0">

                    <h6 class="modal-title fw-bold">
                        Update Kondisi Komponen
                    </h6>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>

                </div>


                <div class="modal-body py-3">

                    <input
                        type="hidden"
                        name="id_komponen"
                        value="<?= $k['id'] ?>"
                    >


                    <label class="form-label small text-muted">
                        Ubah Kondisi Menjadi:
                    </label>


                    <select
                        name="kondisi"
                        class="form-select rounded-3"
                    >

                        <option
                            value="Baik"
                            <?= $k['kondisi'] == 'Baik'
                                ? 'selected'
                                : '' ?>
                        >
                            Baik
                        </option>

                        <option
                            value="Perlu Pemeriksaan"
                            <?= $k['kondisi'] == 'Perlu Pemeriksaan'
                                ? 'selected'
                                : '' ?>
                        >
                            Perlu Pemeriksaan
                        </option>

                        <option
                            value="Dalam Perbaikan"
                            <?= $k['kondisi'] == 'Dalam Perbaikan'
                                ? 'selected'
                                : '' ?>
                        >
                            Dalam Perbaikan
                        </option>

                        <option
                            value="Rusak"
                            <?= $k['kondisi'] == 'Rusak'
                                ? 'selected'
                                : '' ?>
                        >
                            Rusak
                        </option>

                    </select>

                </div>


                <div class="modal-footer border-0 pt-0">

                    <button
                        type="submit"
                        name="update_kondisi_komponen"
                        class="btn btn-primary btn-sm rounded-3 w-100"
                    >

                        <i class="bi bi-check-lg me-1"></i>

                        Simpan Kondisi

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endforeach; ?>


<script>

/* =========================================================
   JAM REALTIME
========================================================= */

function updateClock() {

    const now = new Date();

    const hours =
        String(now.getHours()).padStart(2, '0');

    const minutes =
        String(now.getMinutes()).padStart(2, '0');

    const seconds =
        String(now.getSeconds()).padStart(2, '0');

    const clock =
        document.getElementById('jam-realtime');

    if (clock) {

        clock.textContent =
            `${hours}:${minutes}:${seconds}`;

    }
}

updateClock();

setInterval(updateClock, 1000);


/* =========================================================
   SMOOTH SCROLL QUICK ACTION
========================================================= */

document.querySelectorAll('a[href^="#"]').forEach(
    function(anchor) {

        anchor.addEventListener('click', function(e) {

            const target =
                document.querySelector(
                    this.getAttribute('href')
                );

            if (target) {

                e.preventDefault();

                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

            }

        });

    }
);

</script>


<?php include "../template/footer.php"; ?>