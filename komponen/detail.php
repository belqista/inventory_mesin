<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();


include "../koneksi.php";

/* =========================================================
   PARAMETER
========================================================= */

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function showValue($value)
{
    return !empty(trim((string)$value))
        ? e($value)
        : '-';
}


/* =========================================================
   AMBIL DATA KOMPONEN
========================================================= */

$sql = "
    SELECT 
        k.*,

        m.nama_mesin AS nama_m,
        m.serial_number AS sn_mesin,

        sm.nama_sub_mesin AS nama_s,

        jm.nama_jenis_mesin AS nama_jenis,

        ab.nama_area AS nama_area,
        ab.lokasi AS lokasi_aktual

    FROM komponen k

    LEFT JOIN sub_mesin sm
        ON k.id_sub_mesin = sm.id

    LEFT JOIN mesin m
        ON sm.id_mesin = m.id

    LEFT JOIN jenis_mesin jm
        ON m.id_jenis_mesin = jm.id

    LEFT JOIN area_bagian ab
        ON m.id_area = ab.id

    WHERE k.id = ?

    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die(
        "Query gagal dipersiapkan: "
        . e(mysqli_error($conn))
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

if (!mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    die(
        "Query gagal dijalankan: "
        . e(mysqli_error($conn))
    );
}

$result = mysqli_stmt_get_result($stmt);

$d = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   DATA TIDAK DITEMUKAN
========================================================= */

if (!$d) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   DATA UTAMA
========================================================= */

$namaKomponen = !empty(
    trim((string)($d['nama_bagian'] ?? ''))
)
    ? $d['nama_bagian']
    : '-';


$snKomponen = !empty(
    trim((string)($d['serial_number'] ?? ''))
)
    ? $d['serial_number']
    : '-';


$snMesin = !empty(
    trim((string)($d['sn_mesin'] ?? ''))
)
    ? $d['sn_mesin']
    : '-';


$namaMesin = !empty(
    trim((string)($d['nama_m'] ?? ''))
)
    ? $d['nama_m']
    : (
        !empty(
            trim((string)($d['mesin'] ?? ''))
        )
            ? $d['mesin']
            : '-'
    );


$namaSubMesin = !empty(
    trim((string)($d['nama_s'] ?? ''))
)
    ? $d['nama_s']
    : (
        !empty(
            trim((string)($d['sub_mesin'] ?? ''))
        )
            ? $d['sub_mesin']
            : '-'
    );


$namaJenis = !empty(
    trim((string)($d['nama_jenis'] ?? ''))
)
    ? $d['nama_jenis']
    : '-';


$namaArea = !empty(
    trim((string)($d['nama_area'] ?? ''))
)
    ? $d['nama_area']
    : '-';


$lokasi = !empty(
    trim((string)($d['lokasi_aktual'] ?? ''))
)
    ? $d['lokasi_aktual']
    : (
        !empty(
            trim((string)($d['lokasi'] ?? ''))
        )
            ? $d['lokasi']
            : '-'
    );


$kondisi = !empty(
    trim((string)($d['kondisi'] ?? ''))
)
    ? $d['kondisi']
    : 'Baik';


/* =========================================================
   STATUS KONDISI
========================================================= */

$badgeKondisi = 'status-good';
$iconKondisi  = 'bi-check-circle-fill';

if ($kondisi === 'Dalam Perbaikan') {

    $badgeKondisi = 'status-danger';
    $iconKondisi  = 'bi-tools';

} elseif ($kondisi === 'Perlu Pemeriksaan') {

    $badgeKondisi = 'status-warning';
    $iconKondisi  = 'bi-exclamation-circle-fill';

} elseif ($kondisi === 'Rusak') {

    $badgeKondisi = 'status-danger';
    $iconKondisi  = 'bi-x-circle-fill';
}


/* =========================================================
   FOTO KOMPONEN
========================================================= */

$namaFileGambar = trim(
    (string)($d['gambar'] ?? '')
);

$gambar_path =
    "../uploads/komponen/"
    . basename($namaFileGambar);

$ada_gambar =
    !empty($namaFileGambar) &&
    file_exists($gambar_path);

$gambar_url = $ada_gambar
    ? $gambar_path
    : '';


/* =========================================================
   HEADER
========================================================= */

include "../template/header.php";

?>


<style>

/* =========================================================
   DETAIL COMPONENT PAGE
========================================================= */

.detail-component-page {

    width: 100%;
    max-width: 100%;

    box-sizing: border-box;

    padding-bottom: 20px;
}


/* =========================================================
   HERO
========================================================= */

.detail-hero {

    width: 100%;

    box-sizing: border-box;

    background:
        linear-gradient(
            135deg,
            #ffffff 0%,
            #f7fbff 100%
        );

    border: 1px solid #e2e8f0;

    border-radius: 16px;

    padding: 16px 20px;

    box-shadow:
        0 4px 18px rgba(15, 23, 42, .045);
}


.detail-hero-inner {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    flex-wrap: wrap;
}


.detail-hero-left {

    display: flex;

    align-items: center;

    gap: 12px;

    min-width: 0;
}


.detail-back {

    width: 40px;
    height: 40px;

    min-width: 40px;

    border-radius: 10px;

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


.detail-title-wrapper {

    min-width: 0;
}


.detail-page-title {

    font-size: 21px;

    font-weight: 800;

    color: #172033;

    margin: 0;

    line-height: 1.3;

    word-break: break-word;
}


.detail-page-subtitle {

    font-size: 12px;

    color: #94a3b8;

    margin-top: 4px;

    line-height: 1.5;
}


/* =========================================================
   HEADER ACTION
========================================================= */

.detail-header-actions {

    display: flex;

    align-items: center;

    justify-content: flex-end;

    gap: 8px;

    flex-wrap: wrap;
}


.detail-header-actions .btn {

    border-radius: 9px;

    font-size: 12px;

    font-weight: 700;

    min-height: 34px;

    white-space: nowrap;
}


/* =========================================================
   MAIN CARD
========================================================= */

.detail-main-card {

    width: 100%;

    max-width: 100%;

    box-sizing: border-box;

    background: #ffffff;

    border: 1px solid #e2e8f0;

    border-radius: 16px;

    overflow: hidden;

    box-shadow:
        0 4px 18px rgba(15, 23, 42, .05);
}


/* =========================================================
   SECTION
========================================================= */

.detail-section {

    width: 100%;

    max-width: 100%;

    box-sizing: border-box;

    background: #ffffff;

    border: 1px solid #e5eaf0;

    border-radius: 14px;

    overflow: hidden;
}


.detail-section-header {

    background: #f8fafc;

    border-bottom: 1px solid #e8edf3;

    padding: 12px 15px;

    min-height: 44px;

    box-sizing: border-box;
}


.detail-section-title {

    display: flex;

    align-items: center;

    gap: 8px;

    font-size: 13px;

    font-weight: 800;

    color: #1e293b;
}


.detail-section-title i {

    font-size: 15px;
}


.detail-section-body {

    padding: 15px;
}


/* =========================================================
   FOTO
========================================================= */

.detail-photo {

    position: relative;

    width: 100%;

    min-height: 290px;

    max-height: 360px;

    background:
        linear-gradient(
            145deg,
            #f8fafc,
            #eef4fa
        );

    border: 1px solid #e3eaf2;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

    box-sizing: border-box;
}


.detail-photo.has-image {

    cursor: zoom-in;
}


.detail-photo img {

    display: block;

    width: 100%;

    height: 100%;

    max-height: 360px;

    object-fit: contain;

    padding: 8px;

    transition: transform .25s ease;
}


.detail-photo.has-image:hover img {

    transform: scale(1.025);
}


.photo-zoom-label {

    position: absolute;

    right: 10px;

    bottom: 10px;

    background: rgba(15, 23, 42, .72);

    color: #ffffff;

    padding: 5px 8px;

    border-radius: 7px;

    font-size: 10px;

    font-weight: 600;

    opacity: 0;

    transition: .2s;

    pointer-events: none;
}


.detail-photo.has-image:hover
.photo-zoom-label {

    opacity: 1;
}


.photo-empty {

    text-align: center;

    color: #94a3b8;

    padding: 30px 15px;
}


.photo-empty i {

    font-size: 48px;

    opacity: .25;

    display: block;

    margin-bottom: 8px;
}


/* =========================================================
   INFO ROW
========================================================= */

.info-row {

    display: flex;

    align-items: flex-start;

    gap: 15px;

    padding: 11px 0;

    border-bottom: 1px dashed #e5e7eb;
}


.info-row:first-child {

    padding-top: 2px;
}


.info-row:last-child {

    border-bottom: 0;

    padding-bottom: 2px;
}


.info-label {

    width: 145px;

    min-width: 145px;

    color: #64748b;

    font-size: 12px;

    font-weight: 500;

    line-height: 1.5;
}


.info-value {

    flex: 1;

    min-width: 0;

    color: #172033;

    font-size: 13px;

    font-weight: 600;

    line-height: 1.5;

    word-break: break-word;
}


/* =========================================================
   CODE BADGE
========================================================= */

.code-badge {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    max-width: 100%;

    background: #f8fafc;

    border: 1px solid #dbe3ec;

    border-radius: 7px;

    padding: 5px 9px;

    color: #334155;

    font-family: monospace;

    font-size: 12px;

    word-break: break-all;

    box-sizing: border-box;
}


.code-badge i {

    flex-shrink: 0;
}


/* =========================================================
   STATUS
========================================================= */

.status-badge {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 7px 11px;

    border-radius: 8px;

    font-size: 12px;

    font-weight: 700;

    line-height: 1.2;
}


.status-good {

    background: #dcfce7;

    color: #15803d;

    border: 1px solid #bbf7d0;
}


.status-warning {

    background: #fef3c7;

    color: #a16207;

    border: 1px solid #fde68a;
}


.status-danger {

    background: #fee2e2;

    color: #b91c1c;

    border: 1px solid #fecaca;
}


/* =========================================================
   SPECIFICATION
========================================================= */

.spec-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 10px;

    width: 100%;
}


.spec-item {

    min-width: 0;

    background: #f8fafc;

    border: 1px solid #e8edf3;

    border-radius: 10px;

    padding: 11px 12px;

    min-height: 70px;

    box-sizing: border-box;

    transition: all .2s ease;
}


.spec-item:hover {

    border-color: #cbd5e1;

    background: #f5f9fd;
}


.spec-label {

    color: #64748b;

    font-size: 10px;

    font-weight: 500;

    margin-bottom: 5px;

    line-height: 1.4;
}


.spec-value {

    color: #172033;

    font-size: 13px;

    font-weight: 700;

    line-height: 1.5;

    word-break: break-word;
}


/* =========================================================
   HIERARCHY
========================================================= */

.hierarchy-box {

    background:
        linear-gradient(
            135deg,
            #f8fbff,
            #f1f7fd
        );

    border: 1px solid #dce9f5;

    border-radius: 12px;

    padding: 14px;
}


.hierarchy-item {

    position: relative;

    padding-left: 30px;

    padding-bottom: 15px;
}


.hierarchy-item:last-child {

    padding-bottom: 0;
}


.hierarchy-item:not(:last-child)::before {

    content: "";

    position: absolute;

    left: 9px;

    top: 22px;

    width: 1px;

    height: calc(100% - 8px);

    background: #cbd5e1;
}


.hierarchy-icon {

    position: absolute;

    left: 0;

    top: 1px;

    width: 20px;

    height: 20px;

    border-radius: 50%;

    background: #ffffff;

    border: 2px solid #0d6efd;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 8px;

    color: #0d6efd;

    z-index: 1;
}


.hierarchy-label {

    display: block;

    color: #64748b;

    font-size: 10px;

    margin-bottom: 2px;

    line-height: 1.3;
}


.hierarchy-value {

    display: block;

    color: #172033;

    font-size: 12px;

    font-weight: 700;

    line-height: 1.5;

    word-break: break-word;
}


/* =========================================================
   NOTE
========================================================= */

.note-box {

    background: #f8fafc;

    border: 1px solid #e5eaf0;

    border-radius: 12px;

    padding: 14px;

    color: #475569;

    font-size: 13px;

    line-height: 1.7;

    min-height: 80px;

    word-break: break-word;

    box-sizing: border-box;
}


/* =========================================================
   FOOTER
========================================================= */

.detail-footer {

    background: #f8fafc;

    border-top: 1px solid #e5e7eb;

    padding: 12px 16px;

    display: flex;

    align-items: center;

    justify-content: flex-start;

    gap: 10px;

    flex-wrap: wrap;

    box-sizing: border-box;
}


/* =========================================================
   MODAL FOTO
========================================================= */

.photo-modal {

    z-index: 1090 !important;
}


.photo-modal .modal-dialog {

    position: relative;

    z-index: 1091;
}


.photo-modal .modal-content {

    position: relative;

    background: #0f172a;

    border: 0;

    border-radius: 14px;

    overflow: hidden;

    z-index: 1092;
}


.photo-modal .modal-header {

    position: relative;

    border-bottom:
        1px solid rgba(255, 255, 255, .1);

    color: #ffffff;

    padding-right: 55px;

    z-index: 1095;
}


/* =========================================================
   CUSTOM CLOSE BUTTON
========================================================= */

.photo-modal-close {

    position: absolute;

    top: 10px;

    right: 10px;

    z-index: 1100;

    width: 34px;

    height: 34px;

    padding: 0;

    margin: 0;

    border: 1px solid rgba(255, 255, 255, .25);

    border-radius: 8px;

    background: rgba(255, 255, 255, .10);

    color: #ffffff;

    display: flex;

    align-items: center;

    justify-content: center;

    cursor: pointer;

    pointer-events: auto;

    transition:
        background .2s ease,
        border-color .2s ease,
        transform .2s ease;
}


.photo-modal-close i {

    font-size: 18px;

    line-height: 1;

    pointer-events: none;
}


.photo-modal-close:hover {

    background: rgba(255, 255, 255, .22);

    border-color: rgba(255, 255, 255, .45);

    color: #ffffff;

    transform: scale(1.05);
}


.photo-modal-close:active {

    transform: scale(.96);
}


.photo-modal .modal-body {

    position: relative;

    z-index: 1093;
}


.photo-modal-image {

    display: block;

    max-width: 100%;

    max-height: 78vh;

    object-fit: contain;

    border-radius: 8px;

    margin: 0 auto;
}


/* =========================================================
   MODAL BACKDROP
========================================================= */

.modal-backdrop {

    z-index: 1080 !important;
}


/* =========================================================
   IMPORTANT LAYOUT FIX
========================================================= */

@media (min-width: 769px) {

    .detail-component-page {

        margin-left: 0 !important;

        width: 100% !important;

        max-width: 100% !important;
    }


    .detail-component-page .row {

        width: auto !important;

        max-width: none !important;
    }

}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 991.98px) {

    .detail-hero {

        padding: 15px;
    }


    .detail-page-title {

        font-size: 19px;
    }


    .detail-photo {

        min-height: 250px;
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 768px) {

    .detail-component-page {

        width: 100% !important;

        max-width: 100% !important;

        margin: 0 !important;

        padding-left: 10px !important;

        padding-right: 10px !important;

        box-sizing: border-box;
    }


    .detail-hero {

        border-radius: 12px;

        padding: 13px;

        margin-bottom: 12px !important;
    }


    .detail-hero-inner {

        align-items: flex-start;
    }


    .detail-hero-left {

        width: 100%;
    }


    .detail-back {

        width: 36px;

        height: 36px;

        min-width: 36px;

        border-radius: 9px;
    }


    .detail-page-title {

        font-size: 17px;
    }


    .detail-page-subtitle {

        font-size: 10px;

        margin-top: 3px;
    }


    .detail-header-actions {

        width: 100%;

        display: grid;

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }


    .detail-header-actions .btn {

        width: 100%;

        justify-content: center;

        display: inline-flex;

        align-items: center;

        min-height: 38px;
    }


    .detail-main-card {

        border-radius: 12px;
    }


    .detail-main-card > .p-3 {

        padding: 12px !important;
    }


    .detail-section {

        border-radius: 11px;
    }


    .detail-section-header {

        padding: 11px 12px;
    }


    .detail-section-body {

        padding: 12px;
    }


    .detail-photo {

        min-height: 220px;

        max-height: 280px;
    }


    .detail-photo img {

        max-height: 280px;
    }


    .info-row {

        display: block;

        padding: 10px 0;
    }


    .info-row:first-child {

        padding-top: 2px;
    }


    .info-label {

        width: auto;

        min-width: auto;

        margin-bottom: 4px;

        font-size: 11px;
    }


    .info-value {

        font-size: 13px;
    }


    .spec-grid {

        grid-template-columns: 1fr;

        gap: 8px;
    }


    .spec-item {

        min-height: auto;

        padding: 10px 11px;
    }


    .spec-label {

        font-size: 10px;
    }


    .spec-value {

        font-size: 12px;
    }


    .hierarchy-box {

        padding: 12px;
    }


    .hierarchy-item {

        padding-left: 28px;

        padding-bottom: 14px;
    }


    .hierarchy-value {

        font-size: 12px;
    }


    .note-box {

        font-size: 12px;

        padding: 12px;
    }


    .detail-footer {

        padding: 11px 12px;
    }


    .detail-footer .btn {

        width: 100%;
    }


    /* =====================================================
       MODAL MOBILE
    ===================================================== */

    .photo-modal .modal-dialog {

        margin: 10px;
    }


    .photo-modal .modal-content {

        border-radius: 12px;
    }


    .photo-modal .modal-header {

        padding: 10px 50px 10px 12px;
    }


    .photo-modal-close {

        top: 8px;

        right: 8px;

        width: 36px;

        height: 36px;
    }


    .photo-modal-image {

        max-height: 75vh;
    }

}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 400px) {

    .detail-header-actions {

        grid-template-columns: 1fr;
    }


    .detail-page-title {

        font-size: 16px;
    }


    .detail-page-subtitle {

        font-size: 9px;
    }


    .detail-photo {

        min-height: 190px;
    }


    .status-badge {

        font-size: 11px;

        padding: 6px 9px;
    }

}

</style>


<!-- =========================================================
     DETAIL COMPONENT PAGE
========================================================= -->

<div class="container-fluid p-0 detail-component-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="detail-hero mb-3">

        <div class="detail-hero-inner">


            <!-- KIRI -->

            <div class="detail-hero-left">

                <a
                    href="index.php"
                    class="detail-back"
                    title="Kembali ke daftar komponen"
                    aria-label="Kembali"
                >

                    <i class="bi bi-arrow-left"></i>

                </a>


                <div class="detail-title-wrapper">

                    <h2 class="detail-page-title">

                        Detail Komponen

                    </h2>


                    <div class="detail-page-subtitle">

                        Informasi teknis, spesifikasi dan status komponen

                    </div>

                </div>

            </div>


            <!-- KANAN -->

            <div class="detail-header-actions">

                <a
                    href="download_pdf.php?id=<?= intval($d['id']) ?>"
                    class="btn btn-danger btn-sm px-3"
                    target="_blank"
                    rel="noopener"
                >

                    <i class="bi bi-file-earmark-pdf me-1"></i>

                    Download PDF

                </a>


                <a
                    href="edit.php?id=<?= intval($d['id']) ?>"
                    class="btn btn-warning btn-sm px-3 text-dark"
                >

                    <i class="bi bi-pencil-square me-1"></i>

                    Edit Data

                </a>

            </div>

        </div>

    </div>


    <!-- =====================================================
         MAIN CARD
    ====================================================== -->

    <div class="detail-main-card">

        <div class="p-3 p-md-4">

            <div class="row g-3 g-md-4">


                <!-- =================================================
                     KOLOM KIRI
                ================================================== -->

                <div class="col-12 col-lg-5 col-xl-5">


                    <!-- FOTO -->

                    <div class="detail-section mb-3">

                        <div class="detail-section-header">

                            <div class="detail-section-title">

                                <i class="bi bi-image text-primary"></i>

                                <span>
                                    Foto Komponen
                                </span>

                            </div>

                        </div>


                        <div class="detail-section-body">

                            <?php if ($ada_gambar): ?>

                                <div
                                    class="detail-photo has-image"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalFoto"
                                    title="Klik untuk memperbesar foto"
                                >

                                    <img
                                        src="<?= e($gambar_url) ?>"
                                        alt="Foto <?= e($namaKomponen) ?>"
                                        loading="lazy"
                                    >


                                    <div class="photo-zoom-label">

                                        <i class="bi bi-zoom-in me-1"></i>

                                        Klik untuk memperbesar

                                    </div>

                                </div>

                            <?php else: ?>

                                <div class="detail-photo">

                                    <div class="photo-empty">

                                        <i class="bi bi-image"></i>

                                        <div class="small">

                                            Tidak ada foto komponen

                                        </div>

                                    </div>

                                </div>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- =================================================
                         INFORMASI KOMPONEN
                    ================================================== -->

                    <div class="detail-section mb-3">

                        <div class="detail-section-header">

                            <div class="detail-section-title">

                                <i class="bi bi-info-circle text-primary"></i>

                                <span>
                                    Informasi Komponen
                                </span>

                            </div>

                        </div>


                        <div class="detail-section-body">


                            <!-- NAMA -->

                            <div class="info-row">

                                <div class="info-label">

                                    Nama Komponen

                                </div>


                                <div class="info-value">

                                    <?= e($namaKomponen) ?>

                                </div>

                            </div>


                            <!-- SERIAL -->

                            <div class="info-row">

                                <div class="info-label">

                                    Serial Number

                                </div>


                                <div class="info-value">

                                    <span class="code-badge">

                                        <i class="bi bi-upc-scan"></i>

                                        <?= e($snKomponen) ?>

                                    </span>

                                </div>

                            </div>


                            <!-- JENIS KOMPONEN -->

                            <div class="info-row">

                                <div class="info-label">

                                    Jenis Komponen

                                </div>


                                <div class="info-value">

                                    <?= showValue(
                                        $d['jenis_komponen'] ?? ''
                                    ) ?>

                                </div>

                            </div>


                            <!-- KONDISI -->

                            <div class="info-row">

                                <div class="info-label">

                                    Kondisi

                                </div>


                                <div class="info-value">

                                    <span
                                        class="status-badge <?= e($badgeKondisi) ?>"
                                    >

                                        <i
                                            class="bi <?= e($iconKondisi) ?>"
                                        ></i>

                                        <?= e($kondisi) ?>

                                    </span>

                                </div>

                            </div>


                        </div>

                    </div>


                    <!-- =================================================
                         HIERARKI
                    ================================================== -->

                    <div class="detail-section">

                        <div class="detail-section-header">

                            <div class="detail-section-title">

                                <i class="bi bi-diagram-3 text-primary"></i>

                                <span>

                                    Struktur Penempatan

                                </span>

                            </div>

                        </div>


                        <div class="detail-section-body">

                            <div class="hierarchy-box">


                                <!-- LOKASI -->

                                <div class="hierarchy-item">

                                    <span class="hierarchy-icon">

                                        <i class="bi bi-geo-alt"></i>

                                    </span>


                                    <span class="hierarchy-label">

                                        Lokasi

                                    </span>


                                    <span class="hierarchy-value">

                                        <?= e($lokasi) ?>

                                    </span>

                                </div>


                                <!-- AREA -->

                                <div class="hierarchy-item">

                                    <span class="hierarchy-icon">

                                        <i class="bi bi-building"></i>

                                    </span>


                                    <span class="hierarchy-label">

                                        Area / Bagian

                                    </span>


                                    <span class="hierarchy-value">

                                        <?= e($namaArea) ?>

                                    </span>

                                </div>


                                <!-- JENIS MESIN -->

                                <div class="hierarchy-item">

                                    <span class="hierarchy-icon">

                                        <i class="bi bi-grid"></i>

                                    </span>


                                    <span class="hierarchy-label">

                                        Jenis Mesin

                                    </span>


                                    <span class="hierarchy-value">

                                        <?= e($namaJenis) ?>

                                    </span>

                                </div>


                                <!-- MESIN -->

                                <div class="hierarchy-item">

                                    <span class="hierarchy-icon">

                                        <i class="bi bi-cpu"></i>

                                    </span>


                                    <span class="hierarchy-label">

                                        Mesin Induk

                                    </span>


                                    <span class="hierarchy-value">

                                        <?= e($namaMesin) ?>

                                    </span>

                                </div>


                                <!-- SUB MESIN -->

                                <div class="hierarchy-item">

                                    <span class="hierarchy-icon">

                                        <i class="bi bi-diagram-2"></i>

                                    </span>


                                    <span class="hierarchy-label">

                                        Sub Mesin

                                    </span>


                                    <span class="hierarchy-value">

                                        <?= e($namaSubMesin) ?>

                                    </span>

                                </div>


                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     KOLOM KANAN
                ================================================== -->

                <div class="col-12 col-lg-7 col-xl-7">


                    <!-- =================================================
                         SPESIFIKASI
                    ================================================== -->

                    <div class="detail-section mb-3">

                        <div class="detail-section-header">

                            <div class="detail-section-title">

                                <i class="bi bi-cpu text-primary"></i>

                                <span>

                                    Spesifikasi Teknis

                                </span>

                            </div>

                        </div>


                        <div class="detail-section-body">

                            <div class="spec-grid">


                                <!-- BRAND -->

                                <div class="spec-item">

                                    <div class="spec-label">

                                        Brand / Merk

                                    </div>


                                    <div class="spec-value">

                                        <?= showValue(
                                            $d['brand'] ?? ''
                                        ) ?>

                                    </div>

                                </div>


                                <!-- TIPE -->

                                <div class="spec-item">

                                    <div class="spec-label">

                                        Tipe

                                    </div>


                                    <div class="spec-value">

                                        <?= showValue(
                                            $d['tipe'] ?? ''
                                        ) ?>

                                    </div>

                                </div>


                                <!-- PART NUMBER -->

                                <div class="spec-item">

                                    <div class="spec-label">

                                        Part Number

                                    </div>


                                    <div class="spec-value">

                                        <?= showValue(
                                            $d['part_number'] ?? ''
                                        ) ?>

                                    </div>

                                </div>


                                <!-- DAYA -->

                                <div class="spec-item">

                                    <div class="spec-label">

                                        Daya

                                    </div>


                                    <div class="spec-value">

                                        <?= showValue(
                                            $d['daya'] ?? ''
                                        ) ?>

                                    </div>

                                </div>


                                <!-- IO ADDRESS -->

                                <div class="spec-item">

                                    <div class="spec-label">

                                        IO Address

                                    </div>


                                    <div class="spec-value">

                                        <span class="code-badge">

                                            <i class="bi bi-diagram-2"></i>

                                            <?= showValue(
                                                $d['io_address'] ?? ''
                                            ) ?>

                                        </span>

                                    </div>

                                </div>


                                <!-- IP ADDRESS -->

                                <div class="spec-item">

                                    <div class="spec-label">

                                        IP Address

                                    </div>


                                    <div class="spec-value">

                                        <span class="code-badge">

                                            <i class="bi bi-globe2"></i>

                                            <?= showValue(
                                                $d['ip_address'] ?? ''
                                            ) ?>

                                        </span>

                                    </div>

                                </div>


                                <!-- INPUT VOLTAGE -->

                                <div class="spec-item">

                                    <div class="spec-label">

                                        Input Voltage

                                    </div>


                                    <div class="spec-value">

                                        <?= showValue(
                                            $d['input_voltage'] ?? ''
                                        ) ?>

                                    </div>

                                </div>


                                <!-- FREKUENSI INPUT -->

                                <div class="spec-item">

                                    <div class="spec-label">

                                        Frekuensi Input

                                    </div>


                                    <div class="spec-value">

                                        <?= showValue(
                                            $d['frekuensi_input'] ?? ''
                                        ) ?>

                                    </div>

                                </div>


                                <!-- ARUS INPUT -->

                                <div class="spec-item">

                                    <div class="spec-label">

                                        Arus Input

                                    </div>


                                    <div class="spec-value">

                                        <?= showValue(
                                            $d['arus_input'] ?? ''
                                        ) ?>

                                    </div>

                                </div>


                                <!-- OUTPUT -->

                                <div class="spec-item">

                                    <div class="spec-label">

                                        Output

                                    </div>


                                    <div class="spec-value">

                                        <?= showValue(
                                            $d['output'] ?? ''
                                        ) ?>

                                    </div>

                                </div>


                                <!-- FREKUENSI OUTPUT -->

                                <div class="spec-item">

                                    <div class="spec-label">

                                        Frekuensi Output

                                    </div>


                                    <div class="spec-value">

                                        <?= showValue(
                                            $d['frekuensi_output'] ?? ''
                                        ) ?>

                                    </div>

                                </div>


                                <!-- IP RATING -->

                                <div class="spec-item">

                                    <div class="spec-label">

                                        IP Rating

                                    </div>


                                    <div class="spec-value">

                                        <?= showValue(
                                            $d['ip_rating'] ?? ''
                                        ) ?>

                                    </div>

                                </div>


                                <!-- SERIAL MESIN -->

                                <div class="spec-item">

                                    <div class="spec-label">

                                        Serial Number Mesin

                                    </div>


                                    <div class="spec-value">

                                        <span class="code-badge">

                                            <i class="bi bi-qr-code"></i>

                                            <?= e($snMesin) ?>

                                        </span>

                                    </div>

                                </div>


                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         KETERANGAN
                    ================================================== -->

                    <div class="detail-section">

                        <div class="detail-section-header">

                            <div class="detail-section-title">

                                <i class="bi bi-journal-text text-primary"></i>

                                <span>

                                    Keterangan / Catatan

                                </span>

                            </div>

                        </div>


                        <div class="detail-section-body">

                            <div class="note-box">

                                <?php if (
                                    !empty(
                                        trim(
                                            (string)(
                                                $d['keterangan'] ?? ''
                                            )
                                        )
                                    )
                                ): ?>

                                    <?= nl2br(
                                        e($d['keterangan'])
                                    ) ?>

                                <?php else: ?>

                                    <span class="text-muted">

                                        Tidak ada keterangan tambahan.

                                    </span>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>


                </div>

            </div>

        </div>


        <!-- =====================================================
             FOOTER CARD
        ====================================================== -->

        <div class="detail-footer">

            <a
                href="index.php"
                class="btn btn-light border btn-sm px-3 fw-semibold"
            >

                <i class="bi bi-arrow-left me-1"></i>

                Kembali

            </a>

        </div>

    </div>

</div>


<!-- =========================================================
     MODAL FOTO
========================================================= -->

<?php if ($ada_gambar): ?>

<div
    class="modal fade photo-modal"
    id="modalFoto"
    tabindex="-1"
    aria-labelledby="modalFotoLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered modal-xl">

        <div class="modal-content">


            <!-- =================================================
                 HEADER MODAL
            ================================================== -->

            <div class="modal-header">


                <div>

                    <h6
                        class="modal-title fw-bold"
                        id="modalFotoLabel"
                    >

                        Foto Komponen

                    </h6>


                    <div class="small text-white-50">

                        <?= e($namaKomponen) ?>

                    </div>

                </div>


                <!-- =================================================
                     TOMBOL X
                ================================================== -->

                <button
                    type="button"
                    class="photo-modal-close"
                    data-bs-dismiss="modal"
                    aria-label="Tutup"
                    title="Tutup"
                >

                    <i class="bi bi-x-lg"></i>

                </button>


            </div>


            <!-- =================================================
                 FOTO
            ================================================== -->

            <div class="modal-body text-center p-2 p-md-3">

                <img
                    src="<?= e($gambar_url) ?>"
                    alt="Foto <?= e($namaKomponen) ?>"
                    class="photo-modal-image"
                >

            </div>


        </div>

    </div>

</div>

<?php endif; ?>


<?php

include "../template/footer.php";

?>