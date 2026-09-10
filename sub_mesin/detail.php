<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

include "../koneksi.php";

/* =========================================================
   AMBIL ID SUB MESIN
========================================================= */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   FUNGSI ESCAPE HTML
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   BADGE KONDISI KOMPONEN
========================================================= */

function badgeKondisi($kondisi)
{
    switch (trim((string)$kondisi)) {

        case 'Baik':
            return 'bg-success-subtle text-success';

        case 'Perlu Pemeriksaan':
            return 'bg-warning-subtle text-warning-emphasis';

        case 'Dalam Perbaikan':
            return 'bg-danger-subtle text-danger';

        case 'Rusak':
            return 'bg-danger text-white';

        default:
            return 'bg-secondary-subtle text-secondary';
    }
}


/* =========================================================
   BADGE STATUS MAINTENANCE
========================================================= */

function badgeStatusMaintenance($status)
{
    switch (trim((string)$status)) {

        case 'Selesai':
            return 'bg-success-subtle text-success';

        case 'Proses':
            return 'bg-warning-subtle text-warning-emphasis';

        case 'Pending':
            return 'bg-danger-subtle text-danger';

        default:
            return 'bg-secondary-subtle text-secondary';
    }
}


/* =========================================================
   DETAIL SUB MESIN
========================================================= */

/*
 * Kolom serial_number sekarang digunakan dari tabel sub_mesin.
 * Pastikan kolom serial_number sudah ditambahkan pada tabel tersebut.
 */

$stmt = mysqli_prepare($conn, "
    SELECT
        sm.id,
        sm.id_mesin,
        sm.nama_sub_mesin,

        sm.serial_number AS serial_number,

        sm.keterangan,
        sm.gambar,

        m.nama_mesin,
        m.serial_number AS sn_mesin,
        m.gambar AS gambar_mesin,
        m.keterangan AS keterangan_mesin,

        jm.nama_jenis_mesin,

        ab.nama_area,
        ab.lokasi

    FROM sub_mesin sm

    LEFT JOIN mesin m
        ON sm.id_mesin = m.id

    LEFT JOIN jenis_mesin jm
        ON m.id_jenis_mesin = jm.id

    LEFT JOIN area_bagian ab
        ON m.id_area = ab.id

    WHERE sm.id = ?

    LIMIT 1
");

if (!$stmt) {
    die("Query detail sub mesin gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$d = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   JIKA DATA TIDAK DITEMUKAN
========================================================= */

if (!$d) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   DATA KOMPONEN
========================================================= */

$stmt_komponen = mysqli_prepare($conn, "
    SELECT
        k.id,
        k.nama_bagian,
        k.serial_number,
        k.kategori,
        k.brand,
        k.tipe,
        k.kondisi,
        k.gambar

    FROM komponen k

    WHERE k.id_sub_mesin = ?

    ORDER BY k.id DESC
");

if (!$stmt_komponen) {
    die("Query komponen gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt_komponen,
    "i",
    $id
);

mysqli_stmt_execute($stmt_komponen);

$result_komponen = mysqli_stmt_get_result(
    $stmt_komponen
);

$data_komponen = [];

while ($row = mysqli_fetch_assoc($result_komponen)) {
    $data_komponen[] = $row;
}

mysqli_stmt_close($stmt_komponen);


/* =========================================================
   DATA MAINTENANCE
========================================================= */

$stmt_maintenance = mysqli_prepare($conn, "
    SELECT
        rm.id,
        rm.id_komponen,
        rm.tanggal,
        rm.jenis,
        rm.tindakan,
        rm.teknisi,
        rm.status,

        k.nama_bagian

    FROM riwayat_maintenance rm

    INNER JOIN komponen k
        ON rm.id_komponen = k.id

    WHERE k.id_sub_mesin = ?

    ORDER BY
        rm.tanggal DESC,
        rm.id DESC
");

if (!$stmt_maintenance) {
    die("Query maintenance gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt_maintenance,
    "i",
    $id
);

mysqli_stmt_execute($stmt_maintenance);

$result_maintenance = mysqli_stmt_get_result(
    $stmt_maintenance
);

$data_maintenance = [];

while ($row = mysqli_fetch_assoc($result_maintenance)) {
    $data_maintenance[] = $row;
}

mysqli_stmt_close($stmt_maintenance);


/* =========================================================
   FOTO SUB MESIN
========================================================= */

$nama_gambar_sub = trim(
    $d['gambar'] ?? ''
);

$gambar_sub = "../uploads/sub_mesin/" . $nama_gambar_sub;

$ada_gambar_sub =
    $nama_gambar_sub !== '' &&
    is_file($gambar_sub);


/* =========================================================
   TOTAL DATA
========================================================= */

$total_komponen = count($data_komponen);

$total_maintenance = count($data_maintenance);


/* =========================================================
   INFORMASI SERIAL NUMBER
========================================================= */

$serial_sub_mesin = trim(
    $d['serial_number'] ?? ''
);

$serial_mesin = trim(
    $d['sn_mesin'] ?? ''
);


/* =========================================================
   INCLUDE HEADER
========================================================= */

include "../template/header.php";

?>


<style>

/* =========================================================
   GLOBAL
========================================================= */

.sub-detail-page {
    width: 100%;
    max-width: 100%;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.sub-page-header {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 18px 20px;
    margin-bottom: 20px;
    box-shadow: 0 3px 12px rgba(15, 23, 42, .04);
}

.sub-page-title {
    font-size: 24px;
    font-weight: 800;
    color: #172033;
    margin: 0;
    line-height: 1.3;
}

.sub-page-subtitle {
    color: #64748b;
    font-size: 13px;
    margin-top: 3px;
}


/* =========================================================
   BACK BUTTON
========================================================= */

.sub-back-btn {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #dbe3ea;
    background: #ffffff;
    color: #475569;
    text-decoration: none;
    transition: .2s;
    flex-shrink: 0;
}

.sub-back-btn:hover {
    background: #005baa;
    border-color: #005baa;
    color: #ffffff;
}


/* =========================================================
   ACTION BUTTON
========================================================= */

.sub-action-btn {
    border-radius: 9px;
    padding: 9px 14px;
    font-size: 13px;
    font-weight: 600;
}


/* =========================================================
   HERO
========================================================= */

.sub-detail-hero {
    background: linear-gradient(
        135deg,
        #005baa,
        #0076c8
    );

    border-radius: 18px;

    overflow: hidden;

    color: #ffffff;

    position: relative;

    box-shadow:
        0 8px 25px rgba(0, 91, 170, .16);
}

.sub-detail-hero::after {
    content: "";
    position: absolute;
    width: 280px;
    height: 280px;
    right: -110px;
    top: -140px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
    pointer-events: none;
}

.sub-detail-hero::before {
    content: "";
    position: absolute;
    width: 190px;
    height: 190px;
    right: 160px;
    bottom: -130px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
    pointer-events: none;
}

.sub-hero-content {
    position: relative;
    z-index: 2;
}


/* =========================================================
   SUB MESIN PHOTO
========================================================= */

.sub-photo-box {
    width: 175px;
    height: 175px;
    border-radius: 17px;

    background: rgba(255,255,255,.14);

    border: 1px solid rgba(255,255,255,.28);

    display: flex;
    align-items: center;
    justify-content: center;

    overflow: hidden;

    position: relative;

    flex-shrink: 0;
}

.sub-photo-box img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 10px;
}

.sub-photo-empty {
    text-align: center;
    opacity: .75;
    padding: 10px;
}

.sub-photo-empty i {
    font-size: 55px;
}


/* =========================================================
   HERO TEXT
========================================================= */

.sub-hero-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .8px;
    font-weight: 700;
    opacity: .72;
    margin-bottom: 4px;
}

.sub-hero-title {
    font-size: 29px;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 22px;
    word-break: break-word;
}

.sub-info-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .5px;
    opacity: .68;
    font-weight: 700;
    margin-bottom: 4px;
}

.sub-info-value {
    font-size: 13px;
    font-weight: 600;
    line-height: 1.45;
    word-break: break-word;
}


/* =========================================================
   SERIAL SUB MESIN BADGE
========================================================= */

.sub-serial-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;

    background: rgba(255,255,255,.13);

    border: 1px solid rgba(255,255,255,.22);

    border-radius: 7px;

    padding: 5px 8px;

    font-family: monospace;

    font-size: 11px;

    max-width: 100%;

    word-break: break-all;
}


/* =========================================================
   STAT CARD
========================================================= */

.sub-stat {
    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 17px;

    transition: .2s;

    height: 100%;
}

.sub-stat:hover {
    transform: translateY(-2px);

    box-shadow:
        0 7px 20px rgba(15,23,42,.07);
}

.sub-stat-icon {
    width: 44px;
    height: 44px;

    border-radius: 11px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eef5ff;

    color: #005baa;

    font-size: 20px;

    flex-shrink: 0;
}

.sub-stat-number {
    font-size: 24px;
    font-weight: 800;
    color: #172033;
    line-height: 1.1;
}


/* =========================================================
   CONTENT CARD
========================================================= */

.sub-content-card {
    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    overflow: hidden;

    box-shadow:
        0 3px 12px rgba(15,23,42,.03);

    margin-bottom: 20px;
}

.sub-card-header {
    padding: 17px 20px;

    border-bottom: 1px solid #e5e7eb;

    background: #ffffff;
}

.sub-card-title {
    font-size: 16px;
    font-weight: 700;
    color: #172033;
    margin: 0;
}

.sub-card-subtitle {
    color: #94a3b8;
    font-size: 12px;
    margin-top: 3px;
}


/* =========================================================
   DESCRIPTION
========================================================= */

.sub-description {
    background: #f8fafc;

    border: 1px solid #e5e7eb;

    border-radius: 11px;

    padding: 14px;

    color: #475569;

    font-size: 13px;

    line-height: 1.7;

    word-break: break-word;
}


/* =========================================================
   TABLE
========================================================= */

.sub-table-wrapper {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.sub-table {
    width: 100%;
    min-width: 850px;

    margin: 0;

    border-collapse: separate;

    border-spacing: 0;
}

.sub-table thead th {
    background: #f8fafc;

    color: #475569;

    font-size: 11px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .3px;

    padding: 13px 14px;

    border-bottom: 1px solid #e5e7eb;

    white-space: nowrap;
}

.sub-table tbody td {
    padding: 14px;

    border-bottom: 1px solid #f1f5f9;

    font-size: 13px;

    color: #334155;

    vertical-align: middle;
}

.sub-table tbody tr {
    transition: .15s;
}

.sub-table tbody tr:hover {
    background: #f8fbff;
}

.sub-table tbody tr:last-child td {
    border-bottom: none;
}


/* =========================================================
   COMPONENT
========================================================= */

.component-icon {
    width: 38px;
    height: 38px;

    border-radius: 9px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eef5ff;

    color: #005baa;

    flex-shrink: 0;
}

.component-name {
    font-size: 13px;
    font-weight: 700;
    color: #172033;
    word-break: break-word;
}

.component-meta {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 2px;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.sub-empty {
    padding: 55px 20px;
    text-align: center;
    color: #94a3b8;
}

.sub-empty-icon {
    width: 65px;
    height: 65px;

    margin: 0 auto 14px;

    border-radius: 17px;

    background: #f1f5f9;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 28px;

    color: #94a3b8;
}

.sub-empty-title {
    font-size: 14px;
    font-weight: 700;
    color: #475569;
}

.sub-empty-text {
    font-size: 12px;
    margin-top: 3px;
}


/* =========================================================
   TIMELINE
========================================================= */

.sub-timeline {
    position: relative;
    padding-left: 34px;
}

.sub-timeline::before {
    content: "";

    position: absolute;

    left: 9px;

    top: 8px;

    bottom: 8px;

    width: 2px;

    background: #e2e8f0;
}

.sub-timeline-item {
    position: relative;
    padding-bottom: 18px;
}

.sub-timeline-item:last-child {
    padding-bottom: 0;
}

.sub-timeline-dot {
    position: absolute;

    left: -33px;

    top: 6px;

    width: 20px;

    height: 20px;

    border-radius: 50%;

    background: #005baa;

    border: 4px solid #dbeafe;

    z-index: 2;
}

.sub-timeline-card {
    border: 1px solid #e5e7eb;

    border-radius: 12px;

    padding: 15px;

    background: #ffffff;

    transition: .2s;
}

.sub-timeline-card:hover {
    box-shadow:
        0 5px 16px rgba(15,23,42,.06);
}


/* =========================================================
   TIMELINE LABEL
========================================================= */

.timeline-label {
    font-size: 10px;

    text-transform: uppercase;

    letter-spacing: .4px;

    color: #94a3b8;

    font-weight: 700;

    margin-bottom: 3px;
}

.timeline-value {
    font-size: 12px;

    font-weight: 600;

    color: #334155;

    line-height: 1.5;

    word-break: break-word;
}


/* =========================================================
   MAINTENANCE ACTION
========================================================= */

.maintenance-detail-btn {
    font-size: 12px;
    border-radius: 8px;
}


/* =========================================================
   MOBILE INFO BOX
========================================================= */

.mobile-info-box {
    background: rgba(255,255,255,.10);

    border: 1px solid rgba(255,255,255,.18);

    border-radius: 10px;

    padding: 10px 12px;
}


/* =========================================================
   RESPONSIVE TABLET
========================================================= */

@media (max-width: 1100px) {

    .sub-photo-box {
        width: 145px;
        height: 145px;
    }

    .sub-hero-title {
        font-size: 25px;
    }

}


/* =========================================================
   RESPONSIVE TABLET / MOBILE
========================================================= */

@media (max-width: 768px) {

    .sub-page-header {
        padding: 15px;
        border-radius: 13px;
    }

    .sub-page-title {
        font-size: 20px;
    }

    .sub-page-subtitle {
        font-size: 12px;
    }

    .sub-page-header > .d-flex {
        align-items: flex-start !important;
    }

    .sub-page-header .d-flex.justify-content-between {
        flex-direction: column;
        align-items: stretch !important;
    }

    .sub-page-header .d-flex.gap-2 {
        width: 100%;
    }

    .sub-page-header .sub-action-btn {
        flex: 1;
        text-align: center;
    }

    .sub-detail-hero {
        border-radius: 14px;
    }

    .sub-detail-hero > .row {
        padding: 20px !important;
    }

    .sub-photo-box {
        width: 115px;
        height: 115px;
        margin: 0 auto;
    }

    .sub-photo-empty i {
        font-size: 40px;
    }

    .sub-hero-title {
        font-size: 21px;
        margin-bottom: 18px;
    }

    .sub-info-value {
        font-size: 12px;
    }

    .sub-card-header {
        padding: 15px;
    }

    .sub-card-title {
        font-size: 15px;
    }

    .sub-content-card {
        border-radius: 13px;
        margin-bottom: 15px;
    }

    .sub-timeline {
        padding-left: 29px;
    }

    .sub-timeline-dot {
        left: -28px;
    }

    .sub-timeline-card {
        padding: 13px;
    }

    .sub-stat {
        padding: 14px;
    }

    .sub-stat-number {
        font-size: 21px;
    }

}


/* =========================================================
   RESPONSIVE HP KECIL
========================================================= */

@media (max-width: 576px) {

    .sub-page-header {
        padding: 13px;
    }

    .sub-page-header > .d-flex:first-child {
        gap: 10px !important;
    }

    .sub-back-btn {
        width: 36px;
        height: 36px;
    }

    .sub-page-title {
        font-size: 18px;
    }

    .sub-page-subtitle {
        font-size: 11px;
    }

    .sub-action-btn {
        width: 100%;
        padding: 9px 10px;
        font-size: 12px;
    }

    .sub-page-header .d-flex.gap-2 {
        flex-direction: column;
    }

    .sub-detail-hero > .row {
        padding: 16px !important;
    }

    .sub-photo-box {
        width: 100px;
        height: 100px;
    }

    .sub-hero-label {
        font-size: 10px;
    }

    .sub-hero-title {
        font-size: 19px;
    }

    .sub-info-label {
        font-size: 9px;
    }

    .sub-info-value {
        font-size: 12px;
    }

    .sub-stat-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }

    .sub-stat-number {
        font-size: 20px;
    }

    .sub-card-header {
        padding: 13px;
    }

    .sub-card-header .d-flex {
        align-items: flex-start !important;
    }

    .sub-description {
        padding: 12px;
        font-size: 12px;
    }

    .sub-content-card .p-4 {
        padding: 13px !important;
    }

    .sub-empty {
        padding: 45px 15px;
    }

    .sub-timeline {
        padding-left: 26px;
    }

    .sub-timeline-dot {
        left: -25px;
        width: 18px;
        height: 18px;
    }

    .sub-timeline-card {
        padding: 12px;
    }

    .maintenance-detail-btn {
        width: 100%;
    }

}


/* =========================================================
   EXTRA SMALL
========================================================= */

@media (max-width: 400px) {

    .sub-photo-box {
        width: 90px;
        height: 90px;
    }

    .sub-hero-title {
        font-size: 18px;
    }

    .sub-stat {
        padding: 12px;
    }

}

</style>


<div class="container-fluid p-0 sub-detail-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="sub-page-header">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">


            <!-- LEFT -->

            <div class="d-flex align-items-center gap-3">

                <a
                    href="index.php"
                    class="sub-back-btn"
                    title="Kembali"
                >

                    <i class="bi bi-arrow-left"></i>

                </a>

                <div>

                    <h2 class="sub-page-title">
                        Detail Sub Mesin
                    </h2>

                    <div class="sub-page-subtitle">
                        Informasi lengkap sub mesin, komponen,
                        dan riwayat maintenance.
                    </div>

                </div>

            </div>


            <!-- RIGHT -->

            <div class="d-flex gap-2 flex-wrap">

                <a
                    href="download_pdf.php?id=<?= $id ?>"
                    class="btn btn-danger sub-action-btn"
                    target="_blank"
                >

                    <i class="bi bi-file-earmark-pdf me-1"></i>

                    Download PDF

                </a>


                <a
                    href="edit.php?id=<?= $id ?>"
                    class="btn btn-warning sub-action-btn"
                >

                    <i class="bi bi-pencil-square me-1"></i>

                    Edit Sub Mesin

                </a>

            </div>

        </div>

    </div>



    <!-- =====================================================
         HERO SUB MESIN
    ====================================================== -->

    <div class="sub-detail-hero mb-4">

        <div class="row align-items-center g-4 p-4 sub-hero-content">


            <!-- FOTO -->

            <div class="col-auto">

                <div class="sub-photo-box">

                    <?php if ($ada_gambar_sub): ?>

                        <img
                            src="<?= e($gambar_sub) ?>"
                            alt="<?= e($d['nama_sub_mesin'] ?? 'Sub Mesin') ?>"
                        >

                    <?php else: ?>

                        <div class="sub-photo-empty">

                            <i class="bi bi-diagram-3"></i>

                            <div class="small mt-1">
                                Tidak ada foto
                            </div>

                        </div>

                    <?php endif; ?>

                </div>

            </div>



            <!-- INFORMASI -->

            <div class="col">

                <div class="sub-hero-label">
                    SUB MESIN
                </div>


                <div class="sub-hero-title">
                    <?= e($d['nama_sub_mesin'] ?? '-') ?>
                </div>


                <div class="row g-3">


                    <!-- MESIN INDUK -->

                    <div class="col-sm-6 col-lg-3">

                        <div class="sub-info-label">
                            Mesin Induk
                        </div>

                        <div class="sub-info-value">

                            <i class="bi bi-gear-wide-connected me-1"></i>

                            <?= e($d['nama_mesin'] ?? '-') ?>

                        </div>

                    </div>


                    <!-- SERIAL SUB MESIN -->

                    <div class="col-sm-6 col-lg-3">

                        <div class="sub-info-label">
                            Serial Number Sub Mesin
                        </div>

                        <div class="sub-info-value">

                            <?php if ($serial_sub_mesin !== ''): ?>

                                <span class="sub-serial-badge">

                                    <i class="bi bi-upc-scan"></i>

                                    <?= e($serial_sub_mesin) ?>

                                </span>

                            <?php else: ?>

                                <span>
                                    -
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- SERIAL MESIN -->

                    <div class="col-sm-6 col-lg-3">

                        <div class="sub-info-label">
                            Serial Number Mesin
                        </div>

                        <div class="sub-info-value">

                            <i class="bi bi-upc-scan me-1"></i>

                            <?= e(
                                $serial_mesin !== ''
                                    ? $serial_mesin
                                    : '-'
                            ) ?>

                        </div>

                    </div>


                    <!-- JENIS MESIN -->

                    <div class="col-sm-6 col-lg-3">

                        <div class="sub-info-label">
                            Jenis Mesin
                        </div>

                        <div class="sub-info-value">

                            <i class="bi bi-grid me-1"></i>

                            <?= e(
                                $d['nama_jenis_mesin']
                                    ?? '-'
                            ) ?>

                        </div>

                    </div>


                    <!-- LOKASI -->

                    <div class="col-sm-6 col-lg-3">

                        <div class="sub-info-label">
                            Lokasi / Area
                        </div>

                        <div class="sub-info-value">

                            <i class="bi bi-geo-alt me-1"></i>

                            <?= e(
                                $d['lokasi']
                                    ?? $d['nama_area']
                                    ?? '-'
                            ) ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- =====================================================
         STATISTIK
    ====================================================== -->

    <div class="row g-3 mb-4">


        <!-- KOMPONEN -->

        <div class="col-12 col-sm-6 col-lg-4">

            <div class="sub-stat">

                <div class="d-flex align-items-center gap-3">

                    <div class="sub-stat-icon">
                        <i class="bi bi-cpu"></i>
                    </div>

                    <div>

                        <div class="sub-stat-number">
                            <?= $total_komponen ?>
                        </div>

                        <div class="text-muted small">
                            Komponen
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- MAINTENANCE -->

        <div class="col-12 col-sm-6 col-lg-4">

            <div class="sub-stat">

                <div class="d-flex align-items-center gap-3">

                    <div class="sub-stat-icon">
                        <i class="bi bi-tools"></i>
                    </div>

                    <div>

                        <div class="sub-stat-number">
                            <?= $total_maintenance ?>
                        </div>

                        <div class="text-muted small">
                            Riwayat Maintenance
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- KETERANGAN -->

        <div class="col-12 col-sm-6 col-lg-4">

            <div class="sub-stat">

                <div class="d-flex align-items-center gap-3">

                    <div class="sub-stat-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>

                    <div>

                        <div class="sub-stat-number">

                            <?= !empty(trim($d['keterangan'] ?? ''))
                                ? 'Ada'
                                : '-'
                            ?>

                        </div>

                        <div class="text-muted small">
                            Keterangan
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <!-- =====================================================
         KETERANGAN SUB MESIN
    ====================================================== -->

    <?php if (!empty(trim($d['keterangan'] ?? ''))): ?>

        <div class="sub-content-card">

            <div class="sub-card-header">

                <h5 class="sub-card-title">

                    <i class="bi bi-journal-text text-primary me-2"></i>

                    Keterangan Sub Mesin

                </h5>

                <div class="sub-card-subtitle">
                    Informasi tambahan mengenai sub mesin.
                </div>

            </div>


            <div class="p-3">

                <div class="sub-description">

                    <?= nl2br(
                        e($d['keterangan'])
                    ) ?>

                </div>

            </div>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         KOMPONEN
    ====================================================== -->

    <div class="sub-content-card">

        <div class="sub-card-header">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                <div>

                    <h5 class="sub-card-title">

                        <i class="bi bi-cpu text-primary me-2"></i>

                        Komponen

                    </h5>

                    <div class="sub-card-subtitle">

                        Komponen yang terhubung dengan sub mesin ini.

                    </div>

                </div>


                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">

                    <?= $total_komponen ?>

                    Komponen

                </span>

            </div>

        </div>



        <?php if (!empty($data_komponen)): ?>

            <div class="sub-table-wrapper">

                <table class="sub-table">

                    <thead>

                        <tr>

                            <th
                                width="55"
                                class="text-center"
                            >
                                No
                            </th>

                            <th width="230">
                                Komponen
                            </th>

                            <th width="170">
                                Serial Number
                            </th>

                            <th width="130">
                                Brand
                            </th>

                            <th width="150">
                                Tipe
                            </th>

                            <th width="150">
                                Kondisi
                            </th>

                            <th
                                width="80"
                                class="text-center"
                            >
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    $no = 1;

                    foreach ($data_komponen as $k):

                    ?>

                        <tr>


                            <!-- NO -->

                            <td class="text-center">

                                <span class="text-muted fw-semibold">

                                    <?= $no++ ?>

                                </span>

                            </td>



                            <!-- KOMPONEN -->

                            <td>

                                <div class="d-flex align-items-center gap-2">

                                    <div class="component-icon">

                                        <i class="bi bi-cpu"></i>

                                    </div>


                                    <div>

                                        <div class="component-name">

                                            <?= e(
                                                $k['nama_bagian']
                                                    ?? '-'
                                            ) ?>

                                        </div>

                                        <div class="component-meta">

                                            <?= e(
                                                $k['kategori']
                                                    ?? '-'
                                            ) ?>

                                        </div>

                                    </div>

                                </div>

                            </td>



                            <!-- SERIAL -->

                            <td>

                                <?php if (!empty($k['serial_number'])): ?>

                                    <span class="badge bg-light text-dark border font-monospace">

                                        <?= e(
                                            $k['serial_number']
                                        ) ?>

                                    </span>

                                <?php else: ?>

                                    <span class="text-muted">
                                        -
                                    </span>

                                <?php endif; ?>

                            </td>



                            <!-- BRAND -->

                            <td>

                                <?= e(
                                    $k['brand']
                                        ?? '-'
                                ) ?>

                            </td>



                            <!-- TIPE -->

                            <td>

                                <?= e(
                                    $k['tipe']
                                        ?? '-'
                                ) ?>

                            </td>



                            <!-- KONDISI -->

                            <td>

                                <span
                                    class="badge <?= badgeKondisi(
                                        $k['kondisi'] ?? ''
                                    ) ?> rounded-pill"
                                >

                                    <?= e(
                                        $k['kondisi']
                                            ?? '-'
                                    ) ?>

                                </span>

                            </td>



                            <!-- AKSI -->

                            <td class="text-center">

                                <a
                                    href="../komponen/detail.php?id=<?= intval($k['id']) ?>"
                                    class="btn btn-sm btn-outline-primary"
                                    title="Lihat Detail Komponen"
                                >

                                    <i class="bi bi-eye"></i>

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


        <?php else: ?>

            <div class="sub-empty">

                <div class="sub-empty-icon">

                    <i class="bi bi-cpu"></i>

                </div>

                <div class="sub-empty-title">

                    Belum ada komponen

                </div>

                <div class="sub-empty-text">

                    Belum ada komponen yang terhubung
                    dengan sub mesin ini.

                </div>

            </div>

        <?php endif; ?>

    </div>



    <!-- =====================================================
         RIWAYAT MAINTENANCE
    ====================================================== -->

    <div class="sub-content-card">

        <div class="sub-card-header">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                <div>

                    <h5 class="sub-card-title">

                        <i class="bi bi-tools text-warning me-2"></i>

                        Riwayat Maintenance

                    </h5>

                    <div class="sub-card-subtitle">

                        Riwayat maintenance seluruh komponen
                        pada sub mesin ini.

                    </div>

                </div>


                <span class="badge bg-warning-subtle text-warning-emphasis">

                    <?= $total_maintenance ?>

                    Riwayat

                </span>

            </div>

        </div>



        <div class="p-4">

            <?php if (!empty($data_maintenance)): ?>

                <div class="sub-timeline">


                    <?php foreach ($data_maintenance as $rm): ?>

                        <div class="sub-timeline-item">

                            <div class="sub-timeline-dot"></div>


                            <div class="sub-timeline-card">


                                <!-- HEADER TIMELINE -->

                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">

                                    <div>

                                        <div class="fw-bold text-dark">

                                            <?= e(
                                                $rm['nama_bagian']
                                                    ?? 'Komponen'
                                            ) ?>

                                        </div>


                                        <div class="small text-muted mt-1">

                                            <i class="bi bi-calendar3 me-1"></i>

                                            <?php

                                            if (!empty($rm['tanggal'])) {

                                                $timestamp = strtotime(
                                                    $rm['tanggal']
                                                );

                                                echo $timestamp !== false
                                                    ? e(
                                                        date(
                                                            'd M Y H:i',
                                                            $timestamp
                                                        )
                                                    )
                                                    : e(
                                                        $rm['tanggal']
                                                    );

                                            } else {

                                                echo '-';

                                            }

                                            ?>

                                        </div>

                                    </div>


                                    <span
                                        class="badge <?= badgeStatusMaintenance(
                                            $rm['status'] ?? ''
                                        ) ?> rounded-pill"
                                    >

                                        <?= e(
                                            $rm['status']
                                                ?? '-'
                                        ) ?>

                                    </span>

                                </div>



                                <!-- DETAIL MAINTENANCE -->

                                <div class="row g-3 mt-2">


                                    <!-- JENIS -->

                                    <div class="col-12 col-md-4">

                                        <div class="timeline-label">
                                            Jenis Maintenance
                                        </div>

                                        <div class="timeline-value">

                                            <?= e(
                                                $rm['jenis']
                                                    ?? '-'
                                            ) ?>

                                        </div>

                                    </div>



                                    <!-- TEKNISI -->

                                    <div class="col-12 col-md-4">

                                        <div class="timeline-label">
                                            Teknisi
                                        </div>

                                        <div class="timeline-value">

                                            <?= e(
                                                $rm['teknisi']
                                                    ?? '-'
                                            ) ?>

                                        </div>

                                    </div>



                                    <!-- TINDAKAN -->

                                    <div class="col-12 col-md-4">

                                        <div class="timeline-label">
                                            Tindakan
                                        </div>

                                        <div class="timeline-value">

                                            <?= e(
                                                $rm['tindakan']
                                                    ?? '-'
                                            ) ?>

                                        </div>

                                    </div>

                                </div>



                                <!-- DETAIL BUTTON -->

                                <div class="mt-3">

                                    <a
                                        href="../maintenance/detail.php?id=<?= intval($rm['id']) ?>"
                                        class="btn btn-sm btn-outline-primary maintenance-detail-btn"
                                    >

                                        <i class="bi bi-eye me-1"></i>

                                        Lihat Detail Maintenance

                                    </a>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>


                </div>


            <?php else: ?>

                <div class="sub-empty">

                    <div class="sub-empty-icon">

                        <i class="bi bi-tools"></i>

                    </div>

                    <div class="sub-empty-title">

                        Belum ada riwayat maintenance

                    </div>

                    <div class="sub-empty-text">

                        Belum ada aktivitas maintenance
                        untuk sub mesin ini.

                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>


</div>


<?php include "../template/footer.php"; ?>