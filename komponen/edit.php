<?php
require_once __DIR__ . '/../template/auth.php';
require_user_role();


ob_start();

include "../koneksi.php";


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


/* =========================================================
   KONFIGURASI UPLOAD GAMBAR
========================================================= */

$maxUploadSize = 10 * 1024 * 1024; // 10 MB
$maxImageWidth = 1600;
$maxImageHeight = 1600;
$imageQuality = 82;

$allowedMime = [
    'image/jpeg',
    'image/png',
    'image/webp'
];


/* =========================================================
   HELPER GAMBAR
========================================================= */

/**
 * Membuat nama file gambar acak.
 */
function generateImageName($id, $extension)
{
    try {
        $random = bin2hex(random_bytes(8));
    } catch (Throwable $e) {
        $random = bin2hex((string) mt_rand());
    }

    return 'komponen_' .
        $id . '_' .
        date('YmdHis') . '_' .
        $random . '.' .
        $extension;
}


/**
 * Membuat resource gambar dari file upload.
 */
function createImageResource($source, $mime)
{
    switch ($mime) {
        case 'image/jpeg':
            return @imagecreatefromjpeg($source);

        case 'image/png':
            return @imagecreatefrompng($source);

        case 'image/webp':
            return @imagecreatefromwebp($source);

        default:
            return false;
    }
}


/**
 * Memproses gambar:
 * - Resize maksimal 1600 x 1600 px
 * - Kompresi kualitas 82
 * - PNG mempertahankan transparansi
 */
function processUploadedImage(
    $source,
    $target,
    $mime,
    $originalWidth,
    $originalHeight,
    $maxWidth,
    $maxHeight,
    $quality
) {
    $sourceImage = createImageResource($source, $mime);

    if (!$sourceImage) {
        return false;
    }

    /* =====================================================
       TENTUKAN UKURAN BARU
    ===================================================== */

    $newWidth = $originalWidth;
    $newHeight = $originalHeight;

    if (
        $originalWidth > $maxWidth ||
        $originalHeight > $maxHeight
    ) {
        $ratio = min(
            $maxWidth / $originalWidth,
            $maxHeight / $originalHeight
        );

        $newWidth = max(
            1,
            (int) round($originalWidth * $ratio)
        );

        $newHeight = max(
            1,
            (int) round($originalHeight * $ratio)
        );
    }


    /* =====================================================
       BUAT CANVAS
    ===================================================== */

    $destinationImage = imagecreatetruecolor(
        $newWidth,
        $newHeight
    );

    if (!$destinationImage) {
        imagedestroy($sourceImage);
        return false;
    }


    /* =====================================================
       PERTAHANKAN TRANSPARANSI PNG
    ===================================================== */

    if ($mime === 'image/png') {
        imagealphablending(
            $destinationImage,
            false
        );

        imagesavealpha(
            $destinationImage,
            true
        );

        $transparent = imagecolorallocatealpha(
            $destinationImage,
            0,
            0,
            0,
            127
        );

        imagefilledrectangle(
            $destinationImage,
            0,
            0,
            $newWidth,
            $newHeight,
            $transparent
        );
    }


    /* =====================================================
       RESIZE
    ===================================================== */

    $resampled = imagecopyresampled(
        $destinationImage,
        $sourceImage,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $originalWidth,
        $originalHeight
    );

    if (!$resampled) {
        imagedestroy($sourceImage);
        imagedestroy($destinationImage);
        return false;
    }


    /* =====================================================
       SIMPAN FILE
    ===================================================== */

    $saved = false;

    switch ($mime) {
        case 'image/jpeg':
            $saved = imagejpeg(
                $destinationImage,
                $target,
                $quality
            );
            break;

        case 'image/png':
            // Compression level PNG: 0 - 9
            $saved = imagepng(
                $destinationImage,
                $target,
                6
            );
            break;

        case 'image/webp':
            $saved = imagewebp(
                $destinationImage,
                $target,
                $quality
            );
            break;
    }


    imagedestroy($sourceImage);
    imagedestroy($destinationImage);

    return $saved;
}


/* =========================================================
   AMBIL DATA KOMPONEN
========================================================= */

function getKomponen($conn, $id)
{
    $sql = "
        SELECT
            k.*,

            sm.id AS sub_mesin_id,
            sm.nama_sub_mesin,
            sm.id_mesin AS sm_id_mesin,

            m.id AS mesin_id,
            m.nama_mesin,
            m.serial_number AS serial_number_mesin,
            m.id_jenis_mesin,
            m.id_area,

            jm.nama_jenis_mesin,

            ab.id AS area_id,
            ab.nama_area,
            ab.lokasi AS lokasi_area

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
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $data;
}


/* =========================================================
   ID KOMPONEN
========================================================= */

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   DATA AWAL
========================================================= */

$error = "";

$d = getKomponen(
    $conn,
    $id
);

if (!$d) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   PROSES UPDATE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =====================================================
       DATA FORM
    ===================================================== */

    $serial_number = trim(
        $_POST['serial_number'] ?? ''
    );

    $nama_bagian = trim(
        $_POST['nama_bagian'] ?? ''
    );

    $jenis_komponen = trim(
        $_POST['jenis_komponen'] ?? ''
    );

    $spesifikasi = trim(
        $_POST['spesifikasi'] ?? ''
    );

    $id_sub_mesin = (int) (
        $_POST['id_sub_mesin'] ?? 0
    );

    $brand = trim(
        $_POST['brand'] ?? ''
    );

    $tipe = trim(
        $_POST['tipe'] ?? ''
    );

    $part_number = trim(
        $_POST['part_number'] ?? ''
    );

    $daya = trim(
        $_POST['daya'] ?? ''
    );

    $io_address = trim(
        $_POST['io_address'] ?? ''
    );

    $ip_address = trim(
        $_POST['ip_address'] ?? ''
    );

    $input_voltage = trim(
        $_POST['input_voltage'] ?? ''
    );

    $frekuensi_input = trim(
        $_POST['frekuensi_input'] ?? ''
    );

    $arus_input = trim(
        $_POST['arus_input'] ?? ''
    );

    $output = trim(
        $_POST['output'] ?? ''
    );

    $frekuensi_output = trim(
        $_POST['frekuensi_output'] ?? ''
    );

    $ip_rating = trim(
        $_POST['ip_rating'] ?? ''
    );

    $kondisi = trim(
        $_POST['kondisi'] ?? ''
    );

    $keterangan = trim(
        $_POST['keterangan'] ?? ''
    );

    $kategori = "";


    /* =====================================================
       VALIDASI FORM
    ===================================================== */

    if ($nama_bagian === '') {

        $error = "Nama Komponen wajib diisi.";

    } elseif ($id_sub_mesin <= 0) {

        $error = "Sub Mesin wajib dipilih.";

    } elseif (!in_array(
        $kondisi,
        [
            'Baik',
            'Perlu Pemeriksaan',
            'Dalam Perbaikan'
        ],
        true
    )) {

        $error = "Kondisi komponen tidak valid.";
    }


    /* =====================================================
       DATA RELASI
    ===================================================== */

    $mesin_str = "";
    $sub_mesin_str = "";
    $lokasi_str = "";

    $id_mesin = 0;
    $id_jenis_mesin = 0;
    $id_area = 0;


    if ($error === '') {

        $sql_relasi = "
            SELECT
                sm.id AS id_sub_mesin,
                sm.nama_sub_mesin,

                m.id AS id_mesin,
                m.nama_mesin,
                m.id_jenis_mesin,
                m.id_area,

                ab.lokasi

            FROM sub_mesin sm

            INNER JOIN mesin m
                ON sm.id_mesin = m.id

            LEFT JOIN area_bagian ab
                ON m.id_area = ab.id

            WHERE sm.id = ?

            LIMIT 1
        ";

        $stmt_relasi = mysqli_prepare(
            $conn,
            $sql_relasi
        );

        if (!$stmt_relasi) {

            $error =
                "Gagal menyiapkan relasi: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt_relasi,
                "i",
                $id_sub_mesin
            );

            if (!mysqli_stmt_execute($stmt_relasi)) {

                $error =
                    "Gagal mengambil relasi: " .
                    mysqli_stmt_error($stmt_relasi);

            } else {

                $result_relasi =
                    mysqli_stmt_get_result(
                        $stmt_relasi
                    );

                $relasi =
                    mysqli_fetch_assoc(
                        $result_relasi
                    );

                if (!$relasi) {

                    $error =
                        "Sub Mesin tidak ditemukan.";

                } else {

                    $id_mesin = (int) (
                        $relasi['id_mesin'] ?? 0
                    );

                    $id_jenis_mesin = (int) (
                        $relasi['id_jenis_mesin'] ?? 0
                    );

                    $id_area = (int) (
                        $relasi['id_area'] ?? 0
                    );

                    $mesin_str =
                        $relasi['nama_mesin'] ?? '';

                    $sub_mesin_str =
                        $relasi['nama_sub_mesin'] ?? '';

                    $lokasi_str =
                        $relasi['lokasi'] ?? '';


                    if ($id_mesin <= 0) {

                        $error =
                            "Sub Mesin belum memiliki Mesin Induk.";

                    } elseif ($id_jenis_mesin <= 0) {

                        $error =
                            "Mesin belum memiliki Jenis Mesin.";

                    } elseif ($id_area <= 0) {

                        $error =
                            "Mesin belum memiliki Area.";
                    }
                }
            }

            mysqli_stmt_close(
                $stmt_relasi
            );
        }
    }


    /* =====================================================
       PROSES GAMBAR
    ===================================================== */

    $nama_gambar_lama =
        $d['gambar'] ?? '';

    $nama_gambar_baru =
        $nama_gambar_lama;

    $gambar_baru_terupload = false;
    $file_gambar_baru = "";


    if (
        $error === '' &&
        isset($_FILES['gambar']) &&
        $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES['gambar'];


        /* =================================================
           VALIDASI UPLOAD
        ================================================= */

        if ($file['error'] !== UPLOAD_ERR_OK) {

            $error =
                "Gagal upload gambar.";

        } elseif (
            !isset($file['size']) ||
            $file['size'] <= 0
        ) {

            $error =
                "File gambar tidak valid.";

        } elseif (
            $file['size'] > $maxUploadSize
        ) {

            $error =
                "Ukuran gambar maksimal 10 MB.";

        } elseif (
            !isset($file['tmp_name']) ||
            !is_uploaded_file($file['tmp_name'])
        ) {

            $error =
                "File upload tidak valid.";

        } else {

            /* =============================================
               VALIDASI MIME DENGAN FINO
            ============================================= */

            $finfo = finfo_open(
                FILEINFO_MIME_TYPE
            );

            if (!$finfo) {

                $error =
                    "Gagal memeriksa tipe file.";

            } else {

                $mime = finfo_file(
                    $finfo,
                    $file['tmp_name']
                );

                finfo_close($finfo);


                if (!in_array(
                    $mime,
                    $allowedMime,
                    true
                )) {

                    $error =
                        "Format gambar harus JPG, PNG, atau WEBP.";

                } else {

                    /* =====================================
                       VALIDASI GAMBAR
                    ===================================== */

                    $imageInfo =
                        @getimagesize(
                            $file['tmp_name']
                        );

                    if ($imageInfo === false) {

                        $error =
                            "File bukan gambar yang valid.";

                    } else {

                        $originalWidth = (int) (
                            $imageInfo[0] ?? 0
                        );

                        $originalHeight = (int) (
                            $imageInfo[1] ?? 0
                        );


                        if (
                            $originalWidth <= 0 ||
                            $originalHeight <= 0
                        ) {

                            $error =
                                "Ukuran gambar tidak valid.";

                        } else {

                            /* =================================
                               EXTENSION
                            ================================= */

                            switch ($mime) {
                                case 'image/jpeg':
                                    $extension = 'jpg';
                                    break;

                                case 'image/png':
                                    $extension = 'png';
                                    break;

                                default:
                                    $extension = 'webp';
                                    break;
                            }


                            /* =================================
                               FOLDER UPLOAD
                            ================================= */

                            $upload_dir =
                                __DIR__ .
                                "/../uploads/komponen/";

                            if (!is_dir($upload_dir)) {

                                if (
                                    !mkdir(
                                        $upload_dir,
                                        0755,
                                        true
                                    ) &&
                                    !is_dir($upload_dir)
                                ) {

                                    $error =
                                        "Folder upload gambar tidak dapat dibuat.";
                                }
                            }


                            /* =================================
                               PROSES GAMBAR
                            ================================= */

                            if ($error === '') {

                                $nama_file =
                                    generateImageName(
                                        $id,
                                        $extension
                                    );

                                $file_gambar_baru =
                                    $upload_dir .
                                    $nama_file;

                                $processed =
                                    processUploadedImage(
                                        $file['tmp_name'],
                                        $file_gambar_baru,
                                        $mime,
                                        $originalWidth,
                                        $originalHeight,
                                        $maxImageWidth,
                                        $maxImageHeight,
                                        $imageQuality
                                    );

                                if (!$processed) {

                                    $error =
                                        "Gagal memproses dan menyimpan gambar.";

                                } else {

                                    $nama_gambar_baru =
                                        $nama_file;

                                    $gambar_baru_terupload =
                                        true;
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    /* =====================================================
       UPDATE DATABASE
    ===================================================== */

    if ($error === '') {

        $sql = "
            UPDATE komponen
            SET
                serial_number = ?,
                id_sub_mesin = ?,
                mesin = ?,
                sub_mesin = ?,
                nama_bagian = ?,
                jenis_komponen = ?,
                spesifikasi = ?,
                kategori = ?,
                brand = ?,
                tipe = ?,
                part_number = ?,
                daya = ?,
                io_address = ?,
                ip_address = ?,
                input_voltage = ?,
                frekuensi_input = ?,
                arus_input = ?,
                output = ?,
                frekuensi_output = ?,
                ip_rating = ?,
                lokasi = ?,
                kondisi = ?,
                keterangan = ?,
                gambar = ?
            WHERE id = ?
        ";

        $stmt = mysqli_prepare(
            $conn,
            $sql
        );

        if (!$stmt) {

            $error =
                "SQL UPDATE ERROR: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "sissssssssssssssssssssssi",
                $serial_number,
                $id_sub_mesin,
                $mesin_str,
                $sub_mesin_str,
                $nama_bagian,
                $jenis_komponen,
                $spesifikasi,
                $kategori,
                $brand,
                $tipe,
                $part_number,
                $daya,
                $io_address,
                $ip_address,
                $input_voltage,
                $frekuensi_input,
                $arus_input,
                $output,
                $frekuensi_output,
                $ip_rating,
                $lokasi_str,
                $kondisi,
                $keterangan,
                $nama_gambar_baru,
                $id
            );


            /* =============================================
               EKSEKUSI
            ============================================= */

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);


                /* =========================================
                   HAPUS GAMBAR LAMA
                ========================================= */

                if (
                    $gambar_baru_terupload &&
                    !empty($nama_gambar_lama) &&
                    $nama_gambar_lama !== $nama_gambar_baru
                ) {

                    $file_lama =
                        __DIR__ .
                        "/../uploads/komponen/" .
                        basename($nama_gambar_lama);

                    if (is_file($file_lama)) {
                        @unlink($file_lama);
                    }
                }


                /* =========================================
                   REDIRECT
                ========================================= */

                ob_clean();

                header(
                    "Location: detail.php?id=" .
                    $id .
                    "&updated=1"
                );

                exit;

            } else {

                $error =
                    "UPDATE GAGAL: " .
                    mysqli_stmt_error($stmt);


                /* =========================================
                   HAPUS GAMBAR BARU JIKA DATABASE GAGAL
                ========================================= */

                if (
                    $gambar_baru_terupload &&
                    !empty($file_gambar_baru) &&
                    is_file($file_gambar_baru)
                ) {

                    @unlink(
                        $file_gambar_baru
                    );
                }

                mysqli_stmt_close($stmt);
            }
        }
    }


    /* =====================================================
       JIKA ERROR
       KEMBALIKAN DATA POST KE FORM
    ===================================================== */

    if ($error !== '') {

        $d['serial_number'] = $serial_number;
        $d['nama_bagian'] = $nama_bagian;
        $d['jenis_komponen'] = $jenis_komponen;
        $d['spesifikasi'] = $spesifikasi;
        $d['brand'] = $brand;
        $d['tipe'] = $tipe;
        $d['part_number'] = $part_number;
        $d['daya'] = $daya;
        $d['io_address'] = $io_address;
        $d['ip_address'] = $ip_address;
        $d['input_voltage'] = $input_voltage;
        $d['frekuensi_input'] = $frekuensi_input;
        $d['arus_input'] = $arus_input;
        $d['output'] = $output;
        $d['frekuensi_output'] = $frekuensi_output;
        $d['ip_rating'] = $ip_rating;
        $d['kondisi'] = $kondisi;
        $d['keterangan'] = $keterangan;
        $d['id_area'] = $id_area;
        $d['id_jenis_mesin'] = $id_jenis_mesin;
        $d['sm_id_mesin'] = $id_mesin;
        $d['id_sub_mesin'] = $id_sub_mesin;
        $d['lokasi_area'] = $lokasi_str;
    }
}


/* =========================================================
   DATA LOKASI
========================================================= */

$q_lokasi = mysqli_query(
    $conn,
    "
        SELECT DISTINCT lokasi
        FROM area_bagian
        WHERE lokasi IS NOT NULL
        AND lokasi != ''
        ORDER BY lokasi ASC
    "
);


/* =========================================================
   TEMPLATE HEADER
========================================================= */

include "../template/header.php";

?>


<style>

.edit-component-page {
    width: 100%;
}

.edit-page-header {
    background: linear-gradient(
        135deg,
        #ffffff 0%,
        #f7fbff 100%
    );
    border: 1px solid #e4eaf2;
    border-radius: 15px;
    padding: 15px 18px;
    box-shadow: 0 4px 15px rgba(15,23,42,.04);
}

.edit-page-header-inner {
    display: flex;
    align-items: center;
    gap: 12px;
}

.edit-back-btn {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    background: #f8fafc;
    border: 1px solid #dfe6ee;
    color: #475569;
}

.edit-back-btn:hover {
    background: #005baa;
    border-color: #005baa;
    color: white;
}

.edit-page-title {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: #172033;
}

.edit-page-subtitle {
    margin: 3px 0 0;
    color: #94a3b8;
    font-size: 12px;
}

.edit-form-card {
    background: white;
    border: 1px solid #e4eaf2;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 18px rgba(15,23,42,.05);
}

.edit-card-header {
    padding: 13px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e8edf3;
}

.edit-card-title {
    margin: 0;
    color: #172033;
    font-size: 13px;
    font-weight: 800;
}

.edit-card-body {
    padding: 18px;
}

.form-section-title {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #005baa;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 14px;
}

.form-divider {
    border: 0;
    border-top: 1px solid #e8edf3;
    margin: 20px 0;
}

.edit-form-card .form-label {
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 5px;
}

.edit-form-card .form-control,
.edit-form-card .form-select {
    min-height: 36px;
    border-color: #dbe3ec;
    border-radius: 8px;
    font-size: 12px;
}

.edit-form-card textarea.form-control {
    min-height: 80px;
}

.image-upload-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    flex-wrap: wrap;
}

.component-image-preview {
    width: 190px;
    height: 190px;
    min-width: 190px;
    border-radius: 14px;
    border: 1px solid #dfe6ee;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.component-image-preview img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.component-image-empty {
    text-align: center;
    color: #94a3b8;
}

.component-image-empty i {
    display: block;
    font-size: 44px;
    opacity: .3;
}

.image-upload-info {
    flex: 1;
    min-width: 230px;
}

.image-current-info {
    margin-top: 10px;
    padding: 9px 11px;
    background: #f8fafc;
    border: 1px solid #e8edf3;
    border-radius: 8px;
    color: #64748b;
    font-size: 11px;
}

.edit-action-bar {
    border-top: 1px solid #e8edf3;
    margin-top: 20px;
    padding-top: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}

.edit-action-left,
.edit-action-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.edit-action-bar .btn {
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    min-height: 36px;
}

.edit-alert {
    border-radius: 9px;
    font-size: 12px;
}

@media (max-width: 767.98px) {

    .edit-card-body {
        padding: 14px;
    }

    .image-upload-wrapper {
        display: block;
    }

    .component-image-preview {
        width: 100%;
        height: 220px;
        min-width: 0;
        margin-bottom: 13px;
    }

    .edit-action-bar {
        display: block;
    }

    .edit-action-left,
    .edit-action-right {
        width: 100%;
    }

    .edit-action-bar .btn {
        flex: 1;
    }
}

</style>


<div class="container-fluid p-0 edit-component-page">

    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="edit-page-header mb-3">

        <div class="edit-page-header-inner">

            <a
                href="index.php"
                class="edit-back-btn"
            >
                <i class="bi bi-arrow-left"></i>
            </a>

            <div>

                <h3 class="edit-page-title">
                    Edit Komponen
                </h3>

                <p class="edit-page-subtitle">
                    Perbarui data komponen.
                </p>

            </div>

        </div>

    </div>


    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <div class="edit-form-card">

        <div class="edit-card-header">

            <div class="edit-card-title">

                <i class="bi bi-pencil-square text-primary me-2"></i>

                Form Edit Komponen

            </div>

        </div>


        <div class="edit-card-body">

            <?php if ($error !== ''): ?>

                <div class="alert alert-danger edit-alert">

                    <i class="bi bi-exclamation-triangle-fill me-2"></i>

                    <?= e($error) ?>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                enctype="multipart/form-data"
                id="formEditKomponen"
            >

                <!-- =================================================
                     INFORMASI UMUM
                ================================================== -->

                <div class="form-section-title">

                    <i class="bi bi-info-circle-fill"></i>

                    INFORMASI UMUM

                </div>


                <div class="row g-3">

                    <div class="col-12 col-md-4">

                        <label class="form-label">
                            Serial Number
                        </label>

                        <input
                            type="text"
                            name="serial_number"
                            class="form-control"
                            value="<?= e($d['serial_number'] ?? '') ?>"
                        >

                    </div>


                    <div class="col-12 col-md-4">

                        <label class="form-label">
                            Nama Komponen
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="nama_bagian"
                            class="form-control"
                            value="<?= e($d['nama_bagian'] ?? '') ?>"
                            required
                        >

                    </div>


                    <div class="col-12 col-md-4">

                        <label class="form-label">
                            Jenis Komponen
                        </label>

                        <input
                            type="text"
                            name="jenis_komponen"
                            class="form-control"
                            value="<?= e($d['jenis_komponen'] ?? '') ?>"
                        >

                    </div>

                </div>


                <hr class="form-divider">


                <!-- =================================================
                     LOKASI & RELASI
                ================================================== -->

                <div class="form-section-title">

                    <i class="bi bi-diagram-3-fill"></i>

                    LOKASI & RELASI MESIN

                </div>


                <div class="row g-3">

                    <div class="col-12 col-md-4">

                        <label class="form-label">
                            Lokasi
                        </label>

                        <select
                            id="form_lokasi"
                            class="form-select"
                        >

                            <option value="">
                                -- Pilih Lokasi --
                            </option>

                            <?php if ($q_lokasi): ?>

                                <?php while (
                                    $l = mysqli_fetch_assoc($q_lokasi)
                                ): ?>

                                    <option
                                        value="<?= e($l['lokasi']) ?>"
                                        <?= (
                                            ($l['lokasi'] ?? '') ===
                                            ($d['lokasi_area'] ?? '')
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        <?= e($l['lokasi']) ?>
                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>

                    </div>


                    <div class="col-12 col-md-4">

                        <label class="form-label">
                            Area / Bagian
                        </label>

                        <select
                            name="id_area"
                            id="form_area"
                            class="form-select"
                        >

                            <option value="">
                                -- Pilih Area --
                            </option>

                        </select>

                    </div>


                    <div class="col-12 col-md-4">

                        <label class="form-label">
                            Jenis Mesin
                        </label>

                        <select
                            name="id_jenis_mesin"
                            id="form_jenis_mesin"
                            class="form-select"
                        >

                            <option value="">
                                -- Pilih Jenis Mesin --
                            </option>

                        </select>

                    </div>


                    <div class="col-12 col-md-6">

                        <label class="form-label">
                            Mesin Induk
                        </label>

                        <select
                            name="id_mesin"
                            id="form_mesin"
                            class="form-select"
                        >

                            <option value="">
                                -- Pilih Mesin --
                            </option>

                        </select>

                    </div>


                    <div class="col-12 col-md-6">

                        <label class="form-label">
                            Sub Mesin
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="id_sub_mesin"
                            id="form_sub_mesin"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- Pilih Sub Mesin --
                            </option>

                        </select>

                    </div>

                </div>


                <hr class="form-divider">


                <!-- =================================================
                     FOTO KOMPONEN
                ================================================== -->

                <div class="form-section-title">

                    <i class="bi bi-image-fill"></i>

                    FOTO KOMPONEN

                </div>


                <div class="image-upload-wrapper">

                    <div class="component-image-preview">

                        <?php

                        $gambar_sekarang =
                            $d['gambar'] ?? '';

                        $gambar_path =
                            "../uploads/komponen/" .
                            basename($gambar_sekarang);

                        $ada_gambar =
                            !empty($gambar_sekarang) &&
                            is_file($gambar_path);

                        ?>

                        <?php if ($ada_gambar): ?>

                            <img
                                src="<?= e($gambar_path) ?>"
                                id="previewGambar"
                                alt="Foto Komponen"
                            >

                            <div
                                class="component-image-empty"
                                id="emptyPreview"
                                style="display:none"
                            >

                                <i class="bi bi-image"></i>

                                <div>
                                    Belum ada gambar
                                </div>

                            </div>

                        <?php else: ?>

                            <img
                                src=""
                                id="previewGambar"
                                style="display:none"
                                alt="Preview"
                            >

                            <div
                                class="component-image-empty"
                                id="emptyPreview"
                            >

                                <i class="bi bi-image"></i>

                                <div>
                                    Belum ada gambar
                                </div>

                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="image-upload-info">

                        <label class="form-label">
                            Upload Foto Komponen
                        </label>

                        <input
                            type="file"
                            name="gambar"
                            id="gambar"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >

                        <div class="form-text mt-2">

                            JPG, PNG, WEBP.
                            Maksimal 10 MB.
                            Gambar lebih dari 1600 × 1600 px akan otomatis diperkecil.

                        </div>

                        <div class="image-current-info">

                            <i class="bi bi-info-circle me-1"></i>

                            Foto lama tetap digunakan jika
                            tidak memilih foto baru.

                        </div>

                    </div>

                </div>


                <hr class="form-divider">


                <!-- =================================================
                     SPESIFIKASI & BRAND
                ================================================== -->

                <div class="form-section-title">

                    <i class="bi bi-cpu-fill"></i>

                    SPESIFIKASI & BRAND

                </div>


                <div class="row g-3">

                    <div class="col-12 col-md-4">

                        <label class="form-label">
                            Brand / Merk
                        </label>

                        <input
                            type="text"
                            name="brand"
                            class="form-control"
                            value="<?= e($d['brand'] ?? '') ?>"
                        >

                    </div>


                    <div class="col-12 col-md-4">

                        <label class="form-label">
                            Tipe
                        </label>

                        <input
                            type="text"
                            name="tipe"
                            class="form-control"
                            value="<?= e($d['tipe'] ?? '') ?>"
                        >

                    </div>


                    <div class="col-12 col-md-4">

                        <label class="form-label">
                            Part Number
                        </label>

                        <input
                            type="text"
                            name="part_number"
                            class="form-control"
                            value="<?= e($d['part_number'] ?? '') ?>"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label">
                            Daya
                        </label>

                        <input
                            type="text"
                            name="daya"
                            class="form-control"
                            value="<?= e($d['daya'] ?? '') ?>"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label">
                            IO Address
                        </label>

                        <input
                            type="text"
                            name="io_address"
                            class="form-control"
                            value="<?= e($d['io_address'] ?? '') ?>"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label">
                            IP Address
                        </label>

                        <input
                            type="text"
                            name="ip_address"
                            class="form-control"
                            value="<?= e($d['ip_address'] ?? '') ?>"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label">
                            Input Voltage
                        </label>

                        <input
                            type="text"
                            name="input_voltage"
                            class="form-control"
                            value="<?= e($d['input_voltage'] ?? '') ?>"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label">
                            Frekuensi Input
                        </label>

                        <input
                            type="text"
                            name="frekuensi_input"
                            class="form-control"
                            value="<?= e($d['frekuensi_input'] ?? '') ?>"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label">
                            Arus Input
                        </label>

                        <input
                            type="text"
                            name="arus_input"
                            class="form-control"
                            value="<?= e($d['arus_input'] ?? '') ?>"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label">
                            Output
                        </label>

                        <input
                            type="text"
                            name="output"
                            class="form-control"
                            value="<?= e($d['output'] ?? '') ?>"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label">
                            Frekuensi Output
                        </label>

                        <input
                            type="text"
                            name="frekuensi_output"
                            class="form-control"
                            value="<?= e($d['frekuensi_output'] ?? '') ?>"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label">
                            IP Rating
                        </label>

                        <input
                            type="text"
                            name="ip_rating"
                            class="form-control"
                            value="<?= e($d['ip_rating'] ?? '') ?>"
                        >

                    </div>

                </div>


                <hr class="form-divider">


                <!-- =================================================
                     STATUS KOMPONEN
                ================================================== -->

                <div class="form-section-title">

                    <i class="bi bi-card-checklist"></i>

                    STATUS KOMPONEN

                </div>


                <div class="row g-3">

                    <div class="col-12 col-md-4">

                        <label class="form-label">
                            Kondisi
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="kondisi"
                            class="form-select"
                            required
                        >

                            <option value="">
                                -- Pilih Kondisi --
                            </option>

                            <?php

                            $kondisi_options = [
                                'Baik',
                                'Perlu Pemeriksaan',
                                'Dalam Perbaikan'
                            ];

                            foreach ($kondisi_options as $kondisi_option):

                            ?>

                                <option
                                    value="<?= e($kondisi_option) ?>"
                                    <?= (
                                        ($d['kondisi'] ?? '') ===
                                        $kondisi_option
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    <?= e($kondisi_option) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <div class="col-12 col-md-8">

                        <label class="form-label">
                            Keterangan Tambahan
                        </label>

                        <textarea
                            name="keterangan"
                            class="form-control"
                            rows="3"
                        ><?= e($d['keterangan'] ?? '') ?></textarea>

                    </div>

                </div>


                <!-- =================================================
                     ACTION
                ================================================== -->

                <div class="edit-action-bar">

                    <div class="edit-action-left">

                        <a
                            href="detail.php?id=<?= $id ?>"
                            class="btn btn-light border"
                        >

                            <i class="bi bi-eye me-1"></i>

                            Lihat Detail

                        </a>

                    </div>


                    <div class="edit-action-right">

                        <a
                            href="index.php"
                            class="btn btn-light border"
                        >

                            <i class="bi bi-x-lg me-1"></i>

                            Batal

                        </a>


                        <button
                            type="submit"
                            name="update"
                            value="1"
                            class="btn btn-warning text-dark"
                            id="btnUpdate"
                        >

                            <i class="bi bi-check-lg me-1"></i>

                            Update Data

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>


<script>

/* =========================================================
   HELPER JAVASCRIPT
========================================================= */

function el(id)
{
    return document.getElementById(id);
}


function resetSelect(select, text)
{
    if (!select) {
        return;
    }

    select.innerHTML =
        '<option value="">' +
        text +
        '</option>';
}


/* =========================================================
   PREVIEW GAMBAR
========================================================= */

const gambarInput = el('gambar');

if (gambarInput) {

    gambarInput.addEventListener(
        'change',
        function () {

            const file = this.files[0];

            if (!file) {
                return;
            }


            /* =============================================
               VALIDASI UKURAN
            ============================================= */

            if (file.size > 10 * 1024 * 1024) {

                alert(
                    'Ukuran gambar maksimal 10 MB.'
                );

                this.value = '';

                return;
            }


            /* =============================================
               VALIDASI MIME
            ============================================= */

            const allowed = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            if (!allowed.includes(file.type)) {

                alert(
                    'Format gambar harus JPG, PNG, atau WEBP.'
                );

                this.value = '';

                return;
            }


            /* =============================================
               PREVIEW
            ============================================= */

            const reader = new FileReader();

            reader.onload = function (e) {

                const preview = el('previewGambar');
                const empty = el('emptyPreview');

                if (preview) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }

                if (empty) {
                    empty.style.display = 'none';
                }
            };

            reader.readAsDataURL(file);
        }
    );
}


/* =========================================================
   LOAD AREA
========================================================= */

async function loadAreaByLokasi(
    lokasiValue,
    selectedArea = ''
) {
    const area = el('form_area');

    resetSelect(
        area,
        '-- Pilih Area --'
    );

    if (!lokasiValue) {
        return;
    }

    try {

        const response = await fetch(
            'get_area.php?lokasi=' +
            encodeURIComponent(lokasiValue) +
            '&_=' +
            Date.now()
        );

        if (!response.ok) {
            throw new Error(
                'HTTP ' + response.status
            );
        }

        const html =
            await response.text();

        area.innerHTML = html;

        if (selectedArea) {
            area.value = String(selectedArea);
        }

    } catch (error) {

        console.error(error);

        resetSelect(
            area,
            '-- Gagal memuat Area --'
        );
    }
}


/* =========================================================
   LOAD JENIS MESIN
========================================================= */

async function loadJenisMesin(
    idArea,
    selectedJenis = ''
) {
    const jenis = el('form_jenis_mesin');
    const mesin = el('form_mesin');
    const sub = el('form_sub_mesin');

    resetSelect(
        jenis,
        '-- Pilih Jenis Mesin --'
    );

    resetSelect(
        mesin,
        '-- Pilih Mesin --'
    );

    resetSelect(
        sub,
        '-- Pilih Sub Mesin --'
    );

    if (!idArea) {
        return;
    }

    try {

        const response = await fetch(
            'get_jenis_mesin.php?id_area=' +
            encodeURIComponent(idArea) +
            '&_=' +
            Date.now()
        );

        if (!response.ok) {
            throw new Error(
                'HTTP ' + response.status
            );
        }

        const html =
            await response.text();

        jenis.innerHTML = html;

        if (selectedJenis) {
            jenis.value = String(selectedJenis);
        }

    } catch (error) {

        console.error(error);

        resetSelect(
            jenis,
            '-- Gagal memuat Jenis Mesin --'
        );
    }
}


/* =========================================================
   LOAD MESIN
========================================================= */

async function loadMesin(
    idJenis,
    selectedMesin = ''
) {
    const mesin = el('form_mesin');
    const sub = el('form_sub_mesin');

    resetSelect(
        mesin,
        '-- Pilih Mesin --'
    );

    resetSelect(
        sub,
        '-- Pilih Sub Mesin --'
    );

    if (!idJenis) {
        return;
    }

    try {

        const response = await fetch(
            'get_mesin.php?id_jenis=' +
            encodeURIComponent(idJenis) +
            '&_=' +
            Date.now()
        );

        if (!response.ok) {
            throw new Error(
                'HTTP ' + response.status
            );
        }

        const html =
            await response.text();

        mesin.innerHTML = html;

        if (selectedMesin) {
            mesin.value = String(selectedMesin);
        }

    } catch (error) {

        console.error(error);

        resetSelect(
            mesin,
            '-- Gagal memuat Mesin --'
        );
    }
}


/* =========================================================
   LOAD SUB MESIN
========================================================= */

async function loadSubMesin(
    idMesin,
    selectedSub = ''
) {
    const sub = el('form_sub_mesin');

    resetSelect(
        sub,
        '-- Pilih Sub Mesin --'
    );

    if (!idMesin) {
        return;
    }

    try {

        const response = await fetch(
            'get_sub_mesin.php?id_mesin=' +
            encodeURIComponent(idMesin) +
            '&_=' +
            Date.now()
        );

        if (!response.ok) {
            throw new Error(
                'HTTP ' + response.status
            );
        }

        const html =
            await response.text();

        sub.innerHTML = html;

        if (selectedSub) {
            sub.value = String(selectedSub);
        }

    } catch (error) {

        console.error(error);

        resetSelect(
            sub,
            '-- Gagal memuat Sub Mesin --'
        );
    }
}


/* =========================================================
   EVENT LOKASI
========================================================= */

const lokasi = el('form_lokasi');

if (lokasi) {

    lokasi.addEventListener(
        'change',
        function () {

            loadAreaByLokasi(
                this.value,
                ''
            );
        }
    );
}


/* =========================================================
   EVENT AREA
========================================================= */

const area = el('form_area');

if (area) {

    area.addEventListener(
        'change',
        function () {

            loadJenisMesin(
                this.value,
                ''
            );
        }
    );
}


/* =========================================================
   EVENT JENIS MESIN
========================================================= */

const jenis = el('form_jenis_mesin');

if (jenis) {

    jenis.addEventListener(
        'change',
        function () {

            loadMesin(
                this.value,
                ''
            );
        }
    );
}


/* =========================================================
   EVENT MESIN
========================================================= */

const mesin = el('form_mesin');

if (mesin) {

    mesin.addEventListener(
        'change',
        function () {

            loadSubMesin(
                this.value,
                ''
            );
        }
    );
}


/* =========================================================
   INITIAL DATA
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    async function () {

        const initLokasi =
            <?= json_encode(
                $d['lokasi_area'] ?? ''
            ) ?>;

        const initArea =
            <?= json_encode(
                $d['id_area'] ?? ''
            ) ?>;

        const initJenis =
            <?= json_encode(
                $d['id_jenis_mesin'] ?? ''
            ) ?>;

        const initMesin =
            <?= json_encode(
                $d['sm_id_mesin'] ?? ''
            ) ?>;

        const initSub =
            <?= json_encode(
                $d['id_sub_mesin'] ?? ''
            ) ?>;


        if (initLokasi) {

            await loadAreaByLokasi(
                initLokasi,
                initArea
            );
        }


        if (initArea) {

            await loadJenisMesin(
                initArea,
                initJenis
            );
        }


        if (initJenis) {

            await loadMesin(
                initJenis,
                initMesin
            );
        }


        if (initMesin) {

            await loadSubMesin(
                initMesin,
                initSub
            );
        }
    }
);


/* =========================================================
   VALIDASI SUBMIT
========================================================= */

const form = el('formEditKomponen');

if (form) {

    form.addEventListener(
        'submit',
        function (event) {

            const nama =
                form.querySelector(
                    '[name="nama_bagian"]'
                );

            const sub =
                form.querySelector(
                    '[name="id_sub_mesin"]'
                );

            const kondisi =
                form.querySelector(
                    '[name="kondisi"]'
                );


            /* =============================================
               NAMA
            ============================================= */

            if (
                !nama ||
                !nama.value.trim()
            ) {

                event.preventDefault();

                alert(
                    'Nama Komponen wajib diisi.'
                );

                nama.focus();

                return;
            }


            /* =============================================
               SUB MESIN
            ============================================= */

            if (
                !sub ||
                !sub.value
            ) {

                event.preventDefault();

                alert(
                    'Sub Mesin wajib dipilih.'
                );

                sub.focus();

                return;
            }


            /* =============================================
               KONDISI
            ============================================= */

            if (
                !kondisi ||
                !kondisi.value
            ) {

                event.preventDefault();

                alert(
                    'Kondisi komponen wajib dipilih.'
                );

                kondisi.focus();

                return;
            }


            /* =============================================
               VALIDASI FILE
            ============================================= */

            const file =
                gambarInput &&
                gambarInput.files
                    ? gambarInput.files[0]
                    : null;

            if (file) {

                if (
                    file.size >
                    10 * 1024 * 1024
                ) {

                    event.preventDefault();

                    alert(
                        'Ukuran gambar maksimal 10 MB.'
                    );

                    gambarInput.focus();

                    return;
                }


                const allowed = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];

                if (
                    !allowed.includes(file.type)
                ) {

                    event.preventDefault();

                    alert(
                        'Format gambar harus JPG, PNG, atau WEBP.'
                    );

                    gambarInput.focus();

                    return;
                }
            }


            /* =============================================
               BUTTON LOADING
            ============================================= */

            const button = el('btnUpdate');

            if (button) {

                button.disabled = true;

                button.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span>' +
                    ' Menyimpan...';
            }
        }
    );
}

</script>


<?php

include "../template/footer.php";

if (ob_get_level() > 0) {
    ob_end_flush();
}

?>