<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();


include "../koneksi.php";

/* =========================================================
   ID MAINTENANCE
========================================================= */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   DATA MAINTENANCE
========================================================= */

$stmt = mysqli_prepare($conn, "
    SELECT
        rm.*,

        /* MESIN */
        m.id AS mesin_id,
        m.nama_mesin,
        m.serial_number AS serial_number_mesin,
        m.lokasi AS lokasi_mesin,

        /* SUB MESIN */
        sm.id AS sub_mesin_id,
        sm.nama_sub_mesin,

        /* KOMPONEN */
        k.id AS komponen_id,
        k.nama_bagian,
        k.serial_number AS serial_number_komponen,

        /* JENIS KOMPONEN */
        k.jenis_komponen AS jenis_komponen,

        k.lokasi AS lokasi_komponen,
        k.brand,
        k.tipe,
        k.part_number,
        k.daya,
        k.io_address,
        k.input_voltage,
        k.frekuensi_input,
        k.arus_input,
        k.output,
        k.frekuensi_output,
        k.ip_rating,
        k.kondisi

    FROM riwayat_maintenance rm

    LEFT JOIN komponen k
        ON rm.id_komponen = k.id

    LEFT JOIN sub_mesin sm
        ON k.id_sub_mesin = sm.id

    LEFT JOIN mesin m
        ON sm.id_mesin = m.id

    WHERE rm.id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Query detail maintenance gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$d = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$d) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   HELPER ESCAPE
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
   DATA DASAR
========================================================= */

$status = trim($d['status'] ?? '');

if ($status === '') {
    $status = 'Selesai';
}

switch ($status) {

    case 'Selesai':
        $badgeStatus = 'bg-success';
        $statusIcon = 'bi-check-circle-fill';
        break;

    case 'Proses':
        $badgeStatus = 'bg-warning text-dark';
        $statusIcon = 'bi-arrow-repeat';
        break;

    case 'Pending':
        $badgeStatus = 'bg-danger';
        $statusIcon = 'bi-exclamation-circle-fill';
        break;

    default:
        $badgeStatus = 'bg-secondary';
        $statusIcon = 'bi-info-circle-fill';
        break;
}


/* =========================================================
   JENIS MAINTENANCE
========================================================= */

$jenisMaintenance = trim(
    $d['jenis'] ??
    $d['jenis_maintenance'] ??
    'Maintenance'
);


/* =========================================================
   FOTO MAINTENANCE
========================================================= */

$fotoNama = '';

if (!empty($d['gambar'])) {
    $fotoNama = $d['gambar'];
} elseif (!empty($d['foto'])) {
    $fotoNama = $d['foto'];
}

$fotoPath = '';

if ($fotoNama !== '') {

    $fotoNamaAman = basename($fotoNama);

    $physicalPath =
        "../uploads/maintenance/" .
        $fotoNamaAman;

    if (file_exists($physicalPath)) {
        $fotoPath = $physicalPath;
    }
}


/* =========================================================
   FORMAT TANGGAL
========================================================= */

$tanggalFormatted = '-';

if (!empty($d['tanggal'])) {

    $timestamp = strtotime($d['tanggal']);

    if ($timestamp !== false) {

        $tanggalFormatted = date(
            'd M Y, H:i',
            $timestamp
        );
    }
}


/* =========================================================
   DATA NILAI
========================================================= */

$namaMesin =
    trim($d['nama_mesin'] ?? '');

$namaSubMesin =
    trim($d['nama_sub_mesin'] ?? '');

$namaKomponen =
    trim($d['nama_bagian'] ?? '');

$serialMesin =
    trim($d['serial_number_mesin'] ?? '');

$serialKomponen =
    trim($d['serial_number_komponen'] ?? '');

/*
 * JENIS KOMPONEN
 * SUMBER:
 * tabel komponen
 * kolom jenis_komponen
 */
$jenisKomponen =
    trim($d['jenis_komponen'] ?? '');

$teknisi =
    trim($d['teknisi'] ?? '');

$tindakan =
    trim($d['tindakan'] ?? '');

$sparepart =
    trim($d['sparepart'] ?? '');

$catatan =
    trim($d['catatan'] ?? '');

$lokasiKomponen =
    trim($d['lokasi_komponen'] ?? '');

$lokasiMesin =
    trim($d['lokasi_mesin'] ?? '');


/* =========================================================
   HEADER
========================================================= */

include "../template/header.php";

?>

<style>

/* =========================================================
   GLOBAL PAGE
========================================================= */

.maintenance-detail-page {
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.detail-page-header {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 18px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
}

.detail-page-header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    flex-wrap: wrap;
}

.detail-page-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.detail-back {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #475569;
    text-decoration: none;
    transition: all .2s ease;
}

.detail-back:hover {
    background: #005baa;
    border-color: #005baa;
    color: #ffffff;
    transform: translateX(-2px);
}

.detail-page-title {
    font-size: 21px;
    font-weight: 800;
    color: #172033;
    margin: 0;
    line-height: 1.3;
}

.detail-page-subtitle {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 4px;
    line-height: 1.5;
}

.detail-page-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.detail-page-actions .btn {
    border-radius: 9px;
    font-weight: 600;
    white-space: nowrap;
}


/* =========================================================
   MAINTENANCE HEADER
========================================================= */

.maintenance-detail-header {
    background: linear-gradient(
        135deg,
        #005baa,
        #0076c8
    );

    border-radius: 18px;
    padding: 26px;
    color: #ffffff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0, 91, 170, .12);
}

.maintenance-detail-header::before {
    content: "";
    position: absolute;
    width: 320px;
    height: 320px;
    right: -110px;
    top: -170px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .07);
}

.maintenance-detail-header::after {
    content: "";
    position: absolute;
    width: 190px;
    height: 190px;
    right: 110px;
    bottom: -135px;
    border-radius: 50%;
    background: rgba(255, 255, 255, .05);
}

.maintenance-detail-header > * {
    position: relative;
    z-index: 2;
}


/* =========================================================
   ICON
========================================================= */

.maintenance-icon-box {
    width: 112px;
    height: 112px;
    background: rgba(255, 255, 255, .14);
    border: 1px solid rgba(255, 255, 255, .25);
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.maintenance-icon-box i {
    font-size: 50px;
    opacity: .95;
}


/* =========================================================
   INFO
========================================================= */

.machine-info-label {
    font-size: 10px;
    text-transform: uppercase;
    opacity: .72;
    font-weight: 700;
    letter-spacing: .5px;
    margin-bottom: 4px;
}

.machine-info-value {
    font-size: 14px;
    font-weight: 600;
    word-break: break-word;
    line-height: 1.5;
}


/* =========================================================
   STATUS
========================================================= */

.maintenance-status-badge {
    position: relative;
    z-index: 5;
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}


/* =========================================================
   STAT CARD
========================================================= */

.detail-stat {
    border: 1px solid #e5e7eb;
    background: #ffffff;
    border-radius: 14px;
    padding: 18px;
    transition: all .2s ease;
    height: 100%;
}

.detail-stat:hover {
    transform: translateY(-2px);
    box-shadow: 0 7px 20px rgba(15, 23, 42, .07);
}

.detail-stat-icon {
    width: 45px;
    height: 45px;
    flex: 0 0 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    background: #eef5ff;
    color: #0066b3;
}

.detail-stat-icon.orange {
    background: #fff4e8;
    color: #f08a00;
}

.detail-stat-number {
    font-size: 18px;
    font-weight: 800;
    color: #172033;
    word-break: break-word;
    line-height: 1.3;
}

.detail-stat-label {
    font-size: 10px;
    color: #94a3b8;
    font-weight: 700;
    letter-spacing: .4px;
    margin-bottom: 3px;
}


/* =========================================================
   SECTION
========================================================= */

.detail-section {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .025);
}

.detail-section-header {
    padding: 17px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.detail-section-title {
    font-size: 16px;
    font-weight: 750;
    color: #172033;
    line-height: 1.4;
}

.detail-section-subtitle {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 3px;
    line-height: 1.5;
}


/* =========================================================
   CONTENT BOX
========================================================= */

.detail-content-box {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 14px;
    color: #172033;
    line-height: 1.7;
    min-height: 48px;
    word-break: break-word;
}


/* =========================================================
   LABEL
========================================================= */

.detail-label {
    font-size: 10px;
    text-transform: uppercase;
    color: #94a3b8;
    font-weight: 800;
    letter-spacing: .5px;
    margin-bottom: 7px;
}


/* =========================================================
   PHOTO
========================================================= */

.maintenance-photo-wrapper {
    width: 100%;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 12px;
    text-align: center;
}

.maintenance-photo {
    display: block;
    max-width: 100%;
    width: auto;
    max-height: 450px;
    object-fit: contain;
    margin: 0 auto;
    border-radius: 10px;
}

.photo-link {
    display: block;
    text-decoration: none;
}

.photo-caption {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 10px;
}


/* =========================================================
   EMPTY
========================================================= */

.empty-box {
    padding: 40px 20px;
    text-align: center;
    color: #9ca3af;
}

.empty-box i {
    opacity: .5;
}


/* =========================================================
   DEVICE INFO
========================================================= */

.device-item {
    padding-bottom: 14px;
    margin-bottom: 14px;
    border-bottom: 1px dashed #e5e7eb;
}

.device-item:last-child {
    padding-bottom: 0;
    margin-bottom: 0;
    border-bottom: 0;
}

.device-value {
    font-weight: 700;
    color: #172033;
    word-break: break-word;
    line-height: 1.5;
}


/* =========================================================
   TECH TABLE
========================================================= */

.tech-table {
    margin: 0;
    width: 100%;
}

.tech-table td {
    padding: 10px 14px;
    vertical-align: middle;
    border-color: #e5e7eb;
    word-break: break-word;
}

.tech-table td:first-child {
    background: #f8fafc;
    color: #64748b;
    font-size: 10px;
    text-transform: uppercase;
    font-weight: 800;
    width: 42%;
    letter-spacing: .2px;
}

.tech-table td:last-child {
    font-weight: 700;
    color: #172033;
}


/* =========================================================
   CATATAN
========================================================= */

.note-box {
    background: #fffaf0;
    border: 1px solid #f5dfaa;
    border-radius: 10px;
    padding: 14px;
    color: #6b4f00;
    line-height: 1.7;
    word-break: break-word;
}


/* =========================================================
   BADGE
========================================================= */

.badge-soft {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}


/* =========================================================
   RESPONSIVE TABLET
========================================================= */

@media (max-width: 991.98px) {

    .maintenance-detail-header {
        padding: 22px;
    }

    .maintenance-icon-box {
        width: 90px;
        height: 90px;
    }

    .maintenance-icon-box i {
        font-size: 42px;
    }

    .detail-page-actions {
        width: 100%;
    }

    .detail-page-actions .btn {
        flex: 1;
    }

}


/* =========================================================
   RESPONSIVE MOBILE
========================================================= */

@media (max-width: 767.98px) {

    .maintenance-detail-page {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .detail-page-header {
        padding: 14px;
        border-radius: 12px;
        margin-bottom: 14px;
    }

    .detail-page-header-inner {
        align-items: flex-start;
    }

    .detail-page-left {
        width: 100%;
        align-items: flex-start;
    }

    .detail-page-title {
        font-size: 18px;
    }

    .detail-page-subtitle {
        font-size: 11px;
    }

    .detail-page-actions {
        width: 100%;
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .detail-page-actions .btn {
        width: 100%;
        padding-left: 8px !important;
        padding-right: 8px !important;
    }

    .maintenance-detail-header {
        padding: 18px;
        border-radius: 14px;
    }

    .maintenance-detail-header .row {
        text-align: left;
    }

    .maintenance-icon-box {
        width: 72px;
        height: 72px;
        border-radius: 13px;
    }

    .maintenance-icon-box i {
        font-size: 34px;
    }

    .maintenance-detail-header h2 {
        font-size: 21px;
        margin-bottom: 18px !important;
    }

    .maintenance-status-badge {
        margin-top: 0;
    }

    .detail-stat {
        padding: 14px;
    }

    .detail-stat-icon {
        width: 40px;
        height: 40px;
        flex-basis: 40px;
        font-size: 18px;
    }

    .detail-stat-number {
        font-size: 15px;
    }

    .detail-section {
        border-radius: 13px;
    }

    .detail-section-header {
        padding: 14px;
        align-items: flex-start;
    }

    .detail-section-title {
        font-size: 14px;
    }

    .detail-section-subtitle {
        font-size: 11px;
    }

    .detail-section-header > .badge {
        font-size: 10px;
    }

    .tech-table td {
        padding: 9px 10px;
        font-size: 12px;
    }

    .tech-table td:first-child {
        width: 45%;
        font-size: 9px;
    }

    .maintenance-photo-wrapper {
        padding: 8px;
    }

    .maintenance-photo {
        max-height: 300px;
    }

}


/* =========================================================
   EXTRA SMALL MOBILE
========================================================= */

@media (max-width: 480px) {

    .detail-page-actions {
        grid-template-columns: 1fr;
    }

    .detail-page-actions .btn {
        width: 100%;
    }

    .maintenance-detail-header {
        padding: 15px;
    }

    .maintenance-detail-header h2 {
        font-size: 18px;
    }

    .machine-info-value {
        font-size: 13px;
    }

    .detail-stat-number {
        font-size: 14px;
    }

    .detail-section-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .detail-section-header > .badge {
        align-self: flex-start;
    }

    .tech-table td:first-child {
        width: 40%;
    }

}


/* =========================================================
   PRINT
========================================================= */

@media print {

    body {
        background: #ffffff !important;
    }

    .detail-page-header,
    .detail-page-actions,
    .detail-back {
        display: none !important;
    }

    .maintenance-detail-header,
    .detail-section,
    .detail-stat {
        box-shadow: none !important;
        break-inside: avoid;
    }

    .maintenance-detail-header {
        background: #005baa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

}

</style>


<div class="container-fluid p-0 maintenance-detail-page">

    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="detail-page-header">

        <div class="detail-page-header-inner">

            <div class="detail-page-left">

                <a
                    href="index.php"
                    class="detail-back"
                    title="Kembali ke Riwayat Maintenance"
                >
                    <i class="bi bi-arrow-left"></i>
                </a>

                <div class="min-w-0">

                    <h2 class="detail-page-title">
                        Detail Maintenance
                    </h2>

                    <div class="detail-page-subtitle">
                        Informasi maintenance, perangkat terkait,
                        tindakan, dan spesifikasi teknis
                    </div>

                </div>

            </div>


            <div class="detail-page-actions">

                <a
                    href="download.php?id=<?= $id ?>"
                    class="btn btn-danger btn-sm px-3"
                    target="_blank"
                    title="Download PDF"
                >
                    <i class="bi bi-file-earmark-pdf me-1"></i>
                    Download PDF
                </a>

                <a
                    href="edit.php?id=<?= $id ?>"
                    class="btn btn-warning btn-sm px-3"
                    title="Edit Maintenance"
                >
                    <i class="bi bi-pencil-square me-1"></i>
                    Edit Maintenance
                </a>

            </div>

        </div>

    </div>


    <!-- =====================================================
         HEADER MAINTENANCE
    ====================================================== -->

    <div class="maintenance-detail-header mb-4">

        <div class="row align-items-center g-4">

            <!-- ICON -->

            <div class="col-auto">

                <div class="maintenance-icon-box">
                    <i class="bi bi-tools"></i>
                </div>

            </div>


            <!-- INFO -->

            <div class="col min-w-0">

                <div
                    class="small opacity-75 mb-1"
                    style="
                        font-weight:700;
                        letter-spacing:.7px;
                    "
                >
                    RIWAYAT MAINTENANCE
                </div>

                <h2 class="fw-bold mb-3 text-break">
                    <?= e($namaKomponen !== '' ? $namaKomponen : 'Maintenance') ?>
                </h2>


                <div class="row g-3">

                    <div class="col-lg-3 col-md-6 col-6">

                        <div class="machine-info-label">
                            Mesin Induk
                        </div>

                        <div class="machine-info-value">
                            <?= e($namaMesin !== '' ? $namaMesin : '-') ?>
                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6 col-6">

                        <div class="machine-info-label">
                            Sub Mesin
                        </div>

                        <div class="machine-info-value">
                            <?= e($namaSubMesin !== '' ? $namaSubMesin : '-') ?>
                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6 col-6">

                        <div class="machine-info-label">
                            Tanggal
                        </div>

                        <div class="machine-info-value">
                            <?= e($tanggalFormatted) ?>
                        </div>

                    </div>


                    <div class="col-lg-3 col-md-6 col-6">

                        <div class="machine-info-label">
                            Teknisi
                        </div>

                        <div class="machine-info-value">
                            <?= e($teknisi !== '' ? $teknisi : '-') ?>
                        </div>

                    </div>

                </div>

            </div>


            <!-- STATUS -->

            <div class="col-auto">

                <span
                    class="badge <?= $badgeStatus ?> rounded-pill px-3 py-2 maintenance-status-badge"
                >
                    <i class="bi <?= $statusIcon ?> me-1"></i>
                    <?= e($status) ?>
                </span>

            </div>

        </div>

    </div>


    <!-- =====================================================
         STATISTIK
    ====================================================== -->

    <div class="row g-3 mb-4">

        <!-- JENIS MAINTENANCE -->

        <div class="col-md-4">

            <div class="detail-stat">

                <div class="d-flex align-items-center gap-3">

                    <div class="detail-stat-icon">
                        <i class="bi bi-clipboard-check"></i>
                    </div>

                    <div class="min-w-0">

                        <div class="detail-stat-label">
                            JENIS MAINTENANCE
                        </div>

                        <div class="detail-stat-number">
                            <?= e($jenisMaintenance !== '' ? $jenisMaintenance : '-') ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- JENIS KOMPONEN -->

        <div class="col-md-4">

            <div class="detail-stat">

                <div class="d-flex align-items-center gap-3">

                    <div class="detail-stat-icon">
                        <i class="bi bi-cpu"></i>
                    </div>

                    <div class="min-w-0">

                        <div class="detail-stat-label">
                            JENIS KOMPONEN
                        </div>

                        <div class="detail-stat-number">
                            <?= e($jenisKomponen !== '' ? $jenisKomponen : '-') ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- STATUS -->

        <div class="col-md-4">

            <div class="detail-stat">

                <div class="d-flex align-items-center gap-3">

                    <div class="detail-stat-icon orange">
                        <i class="bi <?= $statusIcon ?>"></i>
                    </div>

                    <div class="min-w-0">

                        <div class="detail-stat-label">
                            STATUS
                        </div>

                        <div class="detail-stat-number">
                            <?= e($status) ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <div class="row g-4">

        <!-- =================================================
             KIRI
        ================================================== -->

        <div class="col-lg-7 col-xl-8">


            <!-- =================================================
                 TINDAKAN
            ================================================== -->

            <div class="detail-section mb-4">

                <div class="detail-section-header">

                    <div>

                        <div class="detail-section-title">
                            <i class="bi bi-tools text-primary me-2"></i>
                            Ringkasan Tindakan
                        </div>

                        <div class="detail-section-subtitle">
                            Tindakan yang dilakukan saat maintenance
                        </div>

                    </div>

                    <span
                        class="badge <?= $badgeStatus ?> rounded-pill"
                    >
                        <?= e($status) ?>
                    </span>

                </div>


                <div class="p-3 p-md-4">

                    <div class="mb-4">

                        <div class="detail-label">
                            Tindakan Maintenance
                        </div>

                        <div class="detail-content-box">

                            <?php if ($tindakan !== ''): ?>

                                <?= nl2br(e($tindakan)) ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    Tidak ada keterangan tindakan.
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>


                    <div class="mb-4">

                        <div class="detail-label">
                            Sparepart Diganti / Digunakan
                        </div>

                        <div class="detail-content-box">

                            <?php if ($sparepart !== ''): ?>

                                <?= nl2br(e($sparepart)) ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    Tidak ada sparepart yang dicatat.
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>


                    <?php if ($catatan !== ''): ?>

                        <div>

                            <div class="detail-label">
                                Catatan / Keterangan
                            </div>

                            <div class="note-box">
                                <?= nl2br(e($catatan)) ?>
                            </div>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =================================================
                 FOTO
            ================================================== -->

            <div class="detail-section mb-4">

                <div class="detail-section-header">

                    <div>

                        <div class="detail-section-title">
                            <i class="bi bi-image text-primary me-2"></i>
                            Foto Dokumentasi
                        </div>

                        <div class="detail-section-subtitle">
                            Dokumentasi hasil maintenance
                        </div>

                    </div>

                </div>


                <div class="p-3 p-md-4">

                    <?php if (!empty($fotoPath)): ?>

                        <div class="maintenance-photo-wrapper">

                            <a
                                href="<?= e($fotoPath) ?>"
                                target="_blank"
                                class="photo-link"
                                title="Buka foto ukuran penuh"
                            >

                                <img
                                    src="<?= e($fotoPath) ?>"
                                    class="maintenance-photo"
                                    alt="Foto Maintenance"
                                    loading="lazy"
                                >

                            </a>

                            <div class="photo-caption">
                                Klik gambar untuk membuka ukuran penuh
                            </div>

                        </div>

                    <?php else: ?>

                        <div class="empty-box">

                            <i class="bi bi-image fs-1"></i>

                            <div class="mt-2">
                                Tidak ada foto dokumentasi
                                yang diunggah.
                            </div>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


        </div>


        <!-- =================================================
             KANAN
        ================================================== -->

        <div class="col-lg-5 col-xl-4">


            <!-- =================================================
                 PERANGKAT
            ================================================== -->

            <div class="detail-section mb-4">

                <div class="detail-section-header">

                    <div>

                        <div class="detail-section-title">
                            <i class="bi bi-cpu text-primary me-2"></i>
                            Perangkat Terkait
                        </div>

                        <div class="detail-section-subtitle">
                            Struktur perangkat maintenance
                        </div>

                    </div>

                </div>


                <div class="p-3 p-md-4">


                    <div class="device-item">

                        <div class="detail-label">
                            Mesin Induk
                        </div>

                        <div class="device-value fs-6">
                            <?= e($namaMesin !== '' ? $namaMesin : '-') ?>
                        </div>

                    </div>


                    <div class="device-item">

                        <div class="detail-label">
                            Serial Number Mesin
                        </div>

                        <div class="device-value font-monospace">
                            <?= e($serialMesin !== '' ? $serialMesin : '-') ?>
                        </div>

                    </div>


                    <div class="device-item">

                        <div class="detail-label">
                            Lokasi Mesin
                        </div>

                        <div class="device-value">
                            <?= e($lokasiMesin !== '' ? $lokasiMesin : '-') ?>
                        </div>

                    </div>


                    <div class="device-item">

                        <div class="detail-label">
                            Sub Mesin
                        </div>

                        <div class="device-value">
                            <?= e($namaSubMesin !== '' ? $namaSubMesin : '-') ?>
                        </div>

                    </div>


                    <div class="device-item">

                        <div class="detail-label">
                            Komponen
                        </div>

                        <div class="device-value">
                            <?= e($namaKomponen !== '' ? $namaKomponen : '-') ?>
                        </div>

                    </div>


                    <div class="device-item">

                        <div class="detail-label">
                            Serial Number Komponen
                        </div>

                        <div class="device-value font-monospace">
                            <?= e($serialKomponen !== '' ? $serialKomponen : '-') ?>
                        </div>

                    </div>


                    <!-- =================================================
                         JENIS KOMPONEN
                    ================================================== -->

                    <div class="device-item">

                        <div class="detail-label">
                            Jenis Komponen
                        </div>

                        <div class="device-value">

                            <?php if ($jenisKomponen !== ''): ?>

                                <span class="badge badge-soft rounded-pill px-3 py-2">
                                    <?= e($jenisKomponen) ?>
                                </span>

                            <?php else: ?>

                                -

                            <?php endif; ?>

                        </div>

                    </div>


                    <div>

                        <div class="detail-label">
                            Lokasi Penempatan
                        </div>

                        <div class="device-value">
                            <?= e($lokasiKomponen !== '' ? $lokasiKomponen : '-') ?>
                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 SPESIFIKASI TEKNIS
            ================================================== -->

            <div class="detail-section mb-4">

                <div class="detail-section-header">

                    <div>

                        <div class="detail-section-title">
                            <i class="bi bi-list-columns-reverse text-primary me-2"></i>
                            Spesifikasi Teknis
                        </div>

                        <div class="detail-section-subtitle">
                            Data teknis komponen
                        </div>

                    </div>

                </div>


                <div class="table-responsive">

                    <table class="table table-bordered tech-table mb-0">

                        <tbody>

                            <tr>
                                <td>Brand / Merk</td>
                                <td>
                                    <?= e($d['brand'] ?? '-') ?>
                                </td>
                            </tr>

                            <tr>
                                <td>Tipe</td>
                                <td>
                                    <?= e($d['tipe'] ?? '-') ?>
                                </td>
                            </tr>

                            <tr>
                                <td>Part Number</td>
                                <td class="font-monospace">
                                    <?= e($d['part_number'] ?? '-') ?>
                                </td>
                            </tr>

                            <tr>
                                <td>Daya</td>
                                <td>
                                    <?= e($d['daya'] ?? '-') ?>
                                </td>
                            </tr>

                            <tr>
                                <td>IO Address</td>
                                <td class="font-monospace">
                                    <?= e($d['io_address'] ?? '-') ?>
                                </td>
                            </tr>

                            <tr>
                                <td>Input Voltage</td>
                                <td>
                                    <?= e($d['input_voltage'] ?? '-') ?>
                                </td>
                            </tr>

                            <tr>
                                <td>Frekuensi Input</td>
                                <td>
                                    <?= e($d['frekuensi_input'] ?? '-') ?>
                                </td>
                            </tr>

                            <tr>
                                <td>Arus Input</td>
                                <td>
                                    <?= e($d['arus_input'] ?? '-') ?>
                                </td>
                            </tr>

                            <tr>
                                <td>Output</td>
                                <td>
                                    <?= e($d['output'] ?? '-') ?>
                                </td>
                            </tr>

                            <tr>
                                <td>Frekuensi Output</td>
                                <td>
                                    <?= e($d['frekuensi_output'] ?? '-') ?>
                                </td>
                            </tr>

                            <tr>
                                <td>IP Rating</td>
                                <td>
                                    <?= e($d['ip_rating'] ?? '-') ?>
                                </td>
                            </tr>

                            <tr>
                                <td>Kondisi Komponen</td>
                                <td>

                                    <?php

                                    $kondisi = trim(
                                        $d['kondisi'] ?? ''
                                    );

                                    if ($kondisi === '') {
                                        $kondisi = '-';
                                    }

                                    if ($kondisi === 'Baik') {

                                        $badgeKondisi =
                                            'bg-success';

                                    } elseif (
                                        $kondisi === 'Perlu Pemeriksaan'
                                    ) {

                                        $badgeKondisi =
                                            'bg-warning text-dark';

                                    } elseif (
                                        $kondisi === 'Rusak'
                                    ) {

                                        $badgeKondisi =
                                            'bg-danger';

                                    } else {

                                        $badgeKondisi =
                                            'badge-soft';
                                    }

                                    ?>

                                    <span
                                        class="badge <?= $badgeKondisi ?> rounded-pill px-2 py-1"
                                    >
                                        <?= e($kondisi) ?>
                                    </span>

                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>


        </div>

    </div>

</div>


<?php

include "../template/footer.php";

?>