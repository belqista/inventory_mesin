<?php
require_once __DIR__ . '/../template/auth.php';
require_user_role();


include "../koneksi.php";

/* =========================================================
   KONFIGURASI UPLOAD & KOMPRESI
========================================================= */

$maxUploadSize = 50 * 1024 * 1024; // 50 MB

$maxImageWidth  = 1600;
$maxImageHeight = 1600;

$imageQuality = 82;

$targetDir = "../uploads/mesin/";


/* =========================================================
   HELPER
========================================================= */

/**
 * Escape output HTML.
 */
function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/**
 * Hapus file jika tersedia.
 */
function deleteUploadedFile($path)
{
    if (
        !empty($path) &&
        file_exists($path) &&
        is_file($path)
    ) {
        @unlink($path);
    }
}


/**
 * Buat nama file acak yang aman.
 */
function generateSafeImageName($mimeType)
{
    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    $extension = $extensionMap[$mimeType] ?? 'jpg';

    return 'mesin_' .
        bin2hex(random_bytes(16)) .
        '.' .
        $extension;
}


/**
 * Buat resource gambar dari file upload.
 */
function createImageResource($filePath, $mimeType)
{
    switch ($mimeType) {

        case 'image/jpeg':

            if (!function_exists('imagecreatefromjpeg')) {
                return false;
            }

            return @imagecreatefromjpeg($filePath);


        case 'image/png':

            if (!function_exists('imagecreatefrompng')) {
                return false;
            }

            return @imagecreatefrompng($filePath);


        case 'image/webp':

            if (!function_exists('imagecreatefromwebp')) {
                return false;
            }

            return @imagecreatefromwebp($filePath);


        default:

            return false;
    }
}


/**
 * Simpan gambar hasil resize / kompresi.
 */
function saveProcessedImage(
    $sourceImage,
    $targetPath,
    $mimeType,
    $quality
) {
    switch ($mimeType) {

        case 'image/jpeg':

            if (!function_exists('imagejpeg')) {
                return false;
            }

            return @imagejpeg(
                $sourceImage,
                $targetPath,
                $quality
            );


        case 'image/png':

            if (!function_exists('imagepng')) {
                return false;
            }

            $pngCompression = 9 - (int)round(
                ($quality / 100) * 9
            );

            $pngCompression = max(
                0,
                min(
                    9,
                    $pngCompression
                )
            );

            imagealphablending(
                $sourceImage,
                false
            );

            imagesavealpha(
                $sourceImage,
                true
            );

            return @imagepng(
                $sourceImage,
                $targetPath,
                $pngCompression
            );


        case 'image/webp':

            if (!function_exists('imagewebp')) {
                return false;
            }

            return @imagewebp(
                $sourceImage,
                $targetPath,
                $quality
            );


        default:

            return false;
    }
}


/**
 * Resize gambar jika melebihi batas maksimum.
 */
function resizeImageIfNeeded(
    $sourceImage,
    $sourceWidth,
    $sourceHeight,
    $maxWidth,
    $maxHeight
) {
    if (
        $sourceWidth <= $maxWidth &&
        $sourceHeight <= $maxHeight
    ) {
        return $sourceImage;
    }


    $ratio = min(
        $maxWidth / $sourceWidth,
        $maxHeight / $sourceHeight
    );


    $newWidth = max(
        1,
        (int)round($sourceWidth * $ratio)
    );

    $newHeight = max(
        1,
        (int)round($sourceHeight * $ratio)
    );


    if (!function_exists('imagecreatetruecolor')) {
        return false;
    }


    $newImage = imagecreatetruecolor(
        $newWidth,
        $newHeight
    );


    if (!$newImage) {
        return false;
    }


    imagealphablending(
        $newImage,
        false
    );

    imagesavealpha(
        $newImage,
        true
    );


    $transparent = imagecolorallocatealpha(
        $newImage,
        0,
        0,
        0,
        127
    );

    imagefill(
        $newImage,
        0,
        0,
        $transparent
    );


    $success = imagecopyresampled(
        $newImage,
        $sourceImage,
        0,
        0,
        0,
        0,
        $newWidth,
        $newHeight,
        $sourceWidth,
        $sourceHeight
    );


    if (!$success) {

        @imagedestroy($newImage);

        return false;
    }


    if (
        is_object($sourceImage) ||
        is_resource($sourceImage)
    ) {
        @imagedestroy(
            $sourceImage
        );
    }


    return $newImage;
}


/* =========================================================
   AMBIL ID MESIN
========================================================= */

$id = isset($_GET['id'])
    ? intval($_GET['id'])
    : 0;


if ($id <= 0) {

    header("Location: index.php");

    exit;
}


/* =========================================================
   AMBIL DATA MESIN
========================================================= */

$stmt_get = mysqli_prepare(
    $conn,
    "
    SELECT 
        m.*,
        jm.nama_jenis_mesin,
        ab.nama_area,
        ab.lokasi
    FROM mesin m
    LEFT JOIN jenis_mesin jm
        ON m.id_jenis_mesin = jm.id
    LEFT JOIN area_bagian ab
        ON m.id_area = ab.id
    WHERE m.id = ?
    LIMIT 1
    "
);


if (!$stmt_get) {

    die(
        "Query data mesin gagal: " .
        mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $stmt_get,
    "i",
    $id
);


if (!mysqli_stmt_execute($stmt_get)) {

    $error_get = mysqli_stmt_error($stmt_get);

    mysqli_stmt_close($stmt_get);

    die(
        "Gagal mengambil data mesin: " .
        $error_get
    );
}


$result = mysqli_stmt_get_result(
    $stmt_get
);


if (!$result) {

    $error_get = mysqli_stmt_error($stmt_get);

    mysqli_stmt_close($stmt_get);

    die(
        "Gagal membaca data mesin: " .
        $error_get
    );
}


$data = mysqli_fetch_assoc(
    $result
);


mysqli_stmt_close(
    $stmt_get
);


/* =========================================================
   CEK DATA
========================================================= */

if (!$data) {

    header("Location: index.php");

    exit;
}


/* =========================================================
   VARIABEL FORM
========================================================= */

$error = "";

$val_serial_number  = $data['serial_number'] ?? '';
$val_nama_mesin     = $data['nama_mesin'] ?? '';
$val_id_area        = $data['id_area'] ?? '';
$val_id_jenis_mesin = $data['id_jenis_mesin'] ?? '';
$val_keterangan     = $data['keterangan'] ?? '';
$val_gambar         = $data['gambar'] ?? '';


/* =========================================================
   PROSES UPDATE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * Jika post_max_size di hosting lebih kecil daripada file,
     * PHP dapat membuang seluruh POST sebelum script memprosesnya.
     */
    if (
        empty($_POST) &&
        empty($_FILES) &&
        !empty($_SERVER['CONTENT_LENGTH'])
    ) {
        $contentLength = (int)$_SERVER['CONTENT_LENGTH'];
        $postMax = ini_get('post_max_size');

        $error =
            "Upload tidak diproses oleh PHP karena ukuran POST melebihi batas hosting (post_max_size: " .
            $postMax . "). Naikkan upload_max_filesize dan post_max_size di hosting.";
    }

    $val_serial_number = trim(
        $_POST['serial_number'] ?? ''
    );

    $val_nama_mesin = trim(
        $_POST['nama_mesin'] ?? ''
    );

    $val_id_area = intval(
        $_POST['id_area'] ?? 0
    );

    $val_id_jenis_mesin = intval(
        $_POST['id_jenis_mesin'] ?? 0
    );

    $val_keterangan = trim(
        $_POST['keterangan'] ?? ''
    );


    /*
     * Ambil nama gambar lama dari database.
     * Tidak menggunakan nilai dari POST.
     */
    $gambar_lama = $data['gambar'] ?? '';

    $nama_gambar_baru = $gambar_lama;

    $gambar_baru_terupload = false;

    $targetPath = "";


    /* =====================================================
       VALIDASI FORM
    ===================================================== */

    if (empty($error) && $val_nama_mesin === '') {

        $error = "Nama Mesin wajib diisi!";

    } elseif (empty($error) && $val_id_area <= 0) {

        $error = "Area wajib dipilih!";

    } elseif (empty($error) && $val_id_jenis_mesin <= 0) {

        $error = "Jenis Mesin wajib dipilih!";

    }


    /* =====================================================
       VALIDASI AREA
    ===================================================== */

    if (empty($error)) {

        $stmt_area = mysqli_prepare(
            $conn,
            "
            SELECT id
            FROM area_bagian
            WHERE id = ?
            LIMIT 1
            "
        );


        if (!$stmt_area) {

            $error =
                "Query validasi area gagal: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt_area,
                "i",
                $val_id_area
            );


            if (!mysqli_stmt_execute($stmt_area)) {

                $error =
                    "Validasi area gagal: " .
                    mysqli_stmt_error($stmt_area);

            } else {

                $result_area =
                    mysqli_stmt_get_result(
                        $stmt_area
                    );


                if (!$result_area) {

                    $error =
                        "Gagal membaca validasi area: " .
                        mysqli_stmt_error($stmt_area);

                } elseif (
                    mysqli_num_rows(
                        $result_area
                    ) === 0
                ) {

                    $error =
                        "Area yang dipilih tidak ditemukan.";
                }
            }


            mysqli_stmt_close(
                $stmt_area
            );
        }
    }


    /* =====================================================
       VALIDASI JENIS MESIN
       HARUS SESUAI DENGAN AREA
    ===================================================== */

    if (empty($error)) {

        $stmt_jenis = mysqli_prepare(
            $conn,
            "
            SELECT id
            FROM jenis_mesin
            WHERE id = ?
              AND id_area = ?
            LIMIT 1
            "
        );


        if (!$stmt_jenis) {

            $error =
                "Query validasi jenis mesin gagal: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt_jenis,
                "ii",
                $val_id_jenis_mesin,
                $val_id_area
            );


            if (!mysqli_stmt_execute($stmt_jenis)) {

                $error =
                    "Validasi jenis mesin gagal: " .
                    mysqli_stmt_error($stmt_jenis);

            } else {

                $result_jenis =
                    mysqli_stmt_get_result(
                        $stmt_jenis
                    );


                if (!$result_jenis) {

                    $error =
                        "Gagal membaca validasi jenis mesin: " .
                        mysqli_stmt_error($stmt_jenis);

                } elseif (
                    mysqli_num_rows(
                        $result_jenis
                    ) === 0
                ) {

                    $error =
                        "Jenis Mesin tidak sesuai dengan Area yang dipilih.";
                }
            }


            mysqli_stmt_close(
                $stmt_jenis
            );
        }
    }


    /* =====================================================
       PROSES UPLOAD GAMBAR BARU
    ===================================================== */

    if (
        empty($error) &&
        isset($_FILES['gambar']) &&
        isset($_FILES['gambar']['error']) &&
        $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        /* =================================================
           CEK ERROR UPLOAD
        ================================================= */

        if (
            $_FILES['gambar']['error'] !==
            UPLOAD_ERR_OK
        ) {

            switch (
                $_FILES['gambar']['error']
            ) {

                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:

                    $error =
                        "Ukuran gambar terlalu besar! Maksimal 50MB.";

                    break;


                case UPLOAD_ERR_PARTIAL:

                    $error =
                        "Upload gambar tidak selesai. Silakan coba lagi.";

                    break;


                case UPLOAD_ERR_NO_TMP_DIR:

                    $error =
                        "Folder temporary upload tidak tersedia.";

                    break;


                case UPLOAD_ERR_CANT_WRITE:

                    $error =
                        "Gambar gagal ditulis ke server.";

                    break;


                default:

                    $error =
                        "Terjadi kesalahan saat mengunggah gambar.";

                    break;
            }
        }


        if (empty($error)) {

            $fileTmpPath =
                $_FILES['gambar']['tmp_name'];

            $fileName =
                $_FILES['gambar']['name'];

            $fileSize =
                (int)$_FILES['gambar']['size'];


            /* =============================================
               VALIDASI FILE TEMP
            ============================================= */

            if (
                empty($fileTmpPath) ||
                !is_uploaded_file($fileTmpPath)
            ) {

                $error =
                    "File gambar upload tidak valid.";
            }


            /* =============================================
               VALIDASI UKURAN
            ============================================= */

            elseif (
                $fileSize > $maxUploadSize
            ) {

                $error =
                    "Ukuran gambar terlalu besar! Maksimal 50MB.";
            }


            /* =============================================
               VALIDASI EXTENSION
            ============================================= */

            else {

                $fileExtension = strtolower(
                    pathinfo(
                        $fileName,
                        PATHINFO_EXTENSION
                    )
                );


                $allowedExtensions = [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];


                if (
                    !in_array(
                        $fileExtension,
                        $allowedExtensions,
                        true
                    )
                ) {

                    $error =
                        "Format gambar tidak valid! " .
                        "Hanya diperbolehkan JPG, JPEG, PNG, dan WEBP.";
                }
            }
        }


        /* =============================================
           VALIDASI ISI GAMBAR TANPA FILEINFO / GD
           Kompatibel dengan shared hosting.
        ============================================= */

        if (empty($error)) {

            $imageInfo = @getimagesize($fileTmpPath);

            if (
                $imageInfo === false ||
                empty($imageInfo[0]) ||
                empty($imageInfo[1])
            ) {

                $error =
                    "File yang diupload bukan gambar yang valid.";

            } else {

                $imageWidth =
                    (int)$imageInfo[0];

                $imageHeight =
                    (int)$imageInfo[1];

                $detectedImageType =
                    isset($imageInfo[2])
                        ? (int)$imageInfo[2]
                        : 0;

                /*
                 * Jangan menggunakan finfo_open().
                 * Banyak shared hosting tidak mengaktifkan Fileinfo.
                 *
                 * getimagesize() membaca struktur file gambar,
                 * sehingga dapat dipakai untuk validasi dasar
                 * tanpa ekstensi Fileinfo.
                 */
                $mimeMap = [
                    IMAGETYPE_JPEG => 'image/jpeg',
                    IMAGETYPE_PNG  => 'image/png',
                    IMAGETYPE_WEBP => 'image/webp'
                ];

                if (!isset($mimeMap[$detectedImageType])) {

                    $error =
                        "Format gambar tidak valid! Hanya diperbolehkan JPG, JPEG, PNG, dan WEBP.";

                } else {

                    $mimeType =
                        $mimeMap[$detectedImageType];

                    /*
                     * Cocokkan MIME hasil browser/extension
                     * dengan tipe gambar yang benar-benar dibaca
                     * dari isi file.
                     */
                    $expectedExtension = [
                        IMAGETYPE_JPEG => ['jpg', 'jpeg'],
                        IMAGETYPE_PNG  => ['png'],
                        IMAGETYPE_WEBP => ['webp']
                    ];

                    if (
                        !isset($expectedExtension[$detectedImageType]) ||
                        !in_array(
                            $fileExtension,
                            $expectedExtension[$detectedImageType],
                            true
                        )
                    ) {

                        $error =
                            "Ekstensi file tidak sesuai dengan isi gambar.";
                    }
                }
            }
        }


        /* =============================================
           VALIDASI DIMENSI
        ============================================= */

        if (empty($error)) {

            /*
             * Cegah file gambar dengan dimensi sangat besar
             * yang dapat membebani server.
             *
             * Batas ini hanya validasi keamanan.
             * File tetap boleh berukuran sampai 50 MB.
             */
            $maxSourceDimension = 12000;

            if (
                $imageWidth > $maxSourceDimension ||
                $imageHeight > $maxSourceDimension
            ) {

                $error =
                    "Dimensi gambar terlalu besar. Maksimal " .
                    $maxSourceDimension . " x " .
                    $maxSourceDimension . " pixel.";
            }
        }


        /* =============================================
           BUAT FOLDER UPLOAD
        ============================================= */

        if (empty($error)) {

            if (!is_dir($targetDir)) {

                if (!@mkdir(
                    $targetDir,
                    0755,
                    true
                )) {

                    $error =
                        "Folder upload gambar tidak dapat dibuat. " .
                        "Pastikan folder uploads/mesin tersedia.";
                }
            }
        }


        /* =============================================
           CEK FOLDER DAPAT DITULIS
        ============================================= */

        if (empty($error)) {

            if (!is_writable($targetDir)) {

                $error =
                    "Folder upload gambar tidak dapat ditulis oleh server. " .
                    "Periksa permission folder uploads/mesin.";
            }
        }


        /* =============================================
           SIMPAN FILE ASLI
           TANPA GD
           TANPA FILEINFO
        ============================================= */

        if (empty($error)) {

            $nama_gambar_baru =
                generateSafeImageName($mimeType);

            $targetPath =
                $targetDir .
                $nama_gambar_baru;

            /*
             * move_uploaded_file() lebih aman untuk shared hosting
             * dan tidak membutuhkan GD.
             *
             * Ini juga mencegah penggunaan RAM besar ketika file
             * foto berukuran puluhan MB.
             */
            if (!@move_uploaded_file(
                $fileTmpPath,
                $targetPath
            )) {

                $error =
                    "Gambar gagal disimpan ke server. " .
                    "Pastikan folder uploads/mesin dapat ditulis.";

                $targetPath = "";

            } else {

                $gambar_baru_terupload = true;

                /*
                 * Pastikan file benar-benar ada dan tidak kosong.
                 */
                if (
                    !file_exists($targetPath) ||
                    @filesize($targetPath) <= 0
                ) {

                    deleteUploadedFile($targetPath);

                    $gambar_baru_terupload = false;
                    $targetPath = "";

                    $error =
                        "Gambar berhasil diupload tetapi file tidak dapat dibaca server.";
                }
            }
        }
    }


    /* =====================================================
       UPDATE DATABASE
    ===================================================== */

    if (empty($error)) {

        /*
         * Query UPDATE dibuat terpisah agar mudah dicek
         * dan tidak bergantung pada nilai tombol submit.
         */
        $sql_update = "
            UPDATE mesin
            SET
                serial_number = ?,
                nama_mesin = ?,
                id_area = ?,
                id_jenis_mesin = ?,
                keterangan = ?,
                gambar = ?
            WHERE id = ?
        ";


        $stmt_update = mysqli_prepare(
            $conn,
            $sql_update
        );


        /* =============================================
           CEK PREPARE
        ============================================= */

        if (!$stmt_update) {

            if (
                $gambar_baru_terupload
            ) {

                deleteUploadedFile(
                    $targetPath
                );
            }


            $error =
                "Query update gagal: " .
                mysqli_error($conn);

        } else {

            /*
             * 7 parameter:
             *
             * serial_number  = string
             * nama_mesin     = string
             * id_area        = integer
             * id_jenis_mesin = integer
             * keterangan     = string
             * gambar         = string
             * id             = integer
             *
             * Jadi:
             * s s i i s s i
             * = ssiissi
             */

            $bindSuccess = mysqli_stmt_bind_param(
                $stmt_update,
                "ssiissi",
                $val_serial_number,
                $val_nama_mesin,
                $val_id_area,
                $val_id_jenis_mesin,
                $val_keterangan,
                $nama_gambar_baru,
                $id
            );


            if (!$bindSuccess) {

                if (
                    $gambar_baru_terupload
                ) {

                    deleteUploadedFile(
                        $targetPath
                    );
                }


                $error =
                    "Parameter update gagal: " .
                    mysqli_stmt_error($stmt_update);

                mysqli_stmt_close(
                    $stmt_update
                );

            } else {

                /* =========================================
                   EKSEKUSI UPDATE
                ========================================= */

                $executeSuccess =
                    mysqli_stmt_execute(
                        $stmt_update
                    );


                if (!$executeSuccess) {

                    /*
                     * Ambil error SEBELUM statement ditutup.
                     */
                    $dbUpdateError =
                        mysqli_stmt_error(
                            $stmt_update
                        );


                    if (
                        $gambar_baru_terupload
                    ) {

                        deleteUploadedFile(
                            $targetPath
                        );
                    }


                    $error =
                        "Gagal memperbarui data: " .
                        $dbUpdateError;


                    mysqli_stmt_close(
                        $stmt_update
                    );

                } else {

                    /*
                     * PENTING:
                     *
                     * affected_rows = 0 BUKAN berarti gagal.
                     *
                     * Itu bisa terjadi jika user menekan
                     * Simpan tanpa mengubah data.
                     *
                     * Selama execute berhasil, update
                     * dianggap berhasil.
                     */
                    $affectedRows =
                        mysqli_stmt_affected_rows(
                            $stmt_update
                        );


                    mysqli_stmt_close(
                        $stmt_update
                    );


                    /* =====================================
                       HAPUS GAMBAR LAMA
                       HANYA JIKA GAMBAR BARU BERHASIL
                       DISIMPAN
                    ===================================== */

                    if (
                        $gambar_baru_terupload &&
                        !empty($gambar_lama) &&
                        $gambar_lama !== $nama_gambar_baru
                    ) {

                        deleteUploadedFile(
                            $targetDir .
                            $gambar_lama
                        );
                    }


                    /* =====================================
                       REDIRECT KE DETAIL
                    ===================================== */

                    header(
                        "Location: detail.php?id=" .
                        $id
                    );

                    exit;
                }
            }
        }
    }
}


/* =========================================================
   HEADER TEMPLATE
========================================================= */

include "../template/header.php";

?>

<style>

/* =========================================================
   HALAMAN EDIT MESIN
========================================================= */

.machine-edit-page {
    padding-bottom: 40px;
}

.edit-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.edit-page-header h4 {
    font-weight: 700;
    color: #172033;
    margin: 0;
}

.edit-page-header p {
    margin: 4px 0 0;
    color: #788396;
    font-size: 14px;
}

.edit-card {
    background: #fff;
    border: 1px solid #e7ebf1;
    border-radius: 14px;
    box-shadow: 0 4px 16px rgba(16, 24, 40, .04);
}

.edit-card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e7ebf1;
}

.edit-card-header h5 {
    margin: 0;
    font-weight: 700;
    color: #172033;
}

.edit-card-body {
    padding: 24px;
}

.form-label {
    font-weight: 600;
    color: #172033;
    margin-bottom: 8px;
}

.form-control,
.form-select {
    border-color: #dfe4ec;
    min-height: 44px;
    border-radius: 9px;
}

.form-control:focus,
.form-select:focus {
    border-color: #123f7a;
    box-shadow: 0 0 0 .2rem rgba(18, 63, 122, .12);
}

textarea.form-control {
    min-height: 110px;
}

.image-preview-box {
    border: 1px dashed #ccd5e2;
    border-radius: 12px;
    padding: 12px;
    background: #f8fafc;
    text-align: center;
}

.image-preview-box img {
    max-width: 100%;
    max-height: 280px;
    object-fit: contain;
    border-radius: 8px;
}

.current-image-title {
    font-size: 13px;
    font-weight: 600;
    color: #788396;
    margin-bottom: 8px;
}

.new-image-preview {
    display: none;
    margin-top: 14px;
}

.info-card {
    background: #fff;
    border: 1px solid #e7ebf1;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 4px 16px rgba(16, 24, 40, .04);
}

.info-card h6 {
    font-weight: 700;
    color: #172033;
    margin-bottom: 16px;
}

.info-item {
    margin-bottom: 14px;
}

.info-item:last-child {
    margin-bottom: 0;
}

.info-label {
    font-size: 12px;
    color: #788396;
    margin-bottom: 3px;
}

.info-value {
    font-size: 14px;
    color: #172033;
    font-weight: 600;
}

@media (max-width: 991.98px) {

    .edit-page-header {
        align-items: flex-start;
        gap: 15px;
        flex-direction: column;
    }

}

@media (max-width: 767.98px) {

    .edit-card-body {
        padding: 18px;
    }

    .edit-card-header {
        padding: 18px;
    }

}

</style>


<div class="container-fluid machine-edit-page">

    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="edit-page-header">

        <div>

            <a
                href="detail.php?id=<?= (int)$id ?>"
                class="text-decoration-none d-inline-flex align-items-center gap-2 mb-2"
                style="color:#123f7a;font-weight:600;"
            >
                <i class="bi bi-arrow-left"></i>
                Kembali ke Detail Mesin
            </a>

            <h4>
                Edit Data Mesin
            </h4>

            <p>
                Perbarui informasi mesin sesuai data terbaru.
            </p>

        </div>

    </div>


    <!-- =====================================================
         ALERT ERROR
    ====================================================== -->

    <?php if (!empty($error)): ?>

        <div
            class="alert alert-danger d-flex align-items-start gap-2"
            role="alert"
        >

            <i class="bi bi-exclamation-triangle-fill"></i>

            <div>
                <?= e($error) ?>
            </div>

        </div>

    <?php endif; ?>


    <div class="row g-4">

        <!-- =================================================
             FORM EDIT
        ================================================== -->

        <div class="col-lg-9">

            <div class="edit-card">

                <div class="edit-card-header">

                    <h5>
                        <i class="bi bi-pencil-square me-2"></i>
                        Informasi Mesin
                    </h5>

                </div>


                <div class="edit-card-body">

                    <form
                        method="POST"
                        enctype="multipart/form-data"
                        id="formEditMesin"
                    >

                        <div class="row g-3">

                            <!-- =================================
                                 SERIAL NUMBER
                            ================================== -->

                            <div class="col-md-6">

                                <label
                                    for="serial_number"
                                    class="form-label"
                                >
                                    Serial Number
                                </label>

                                <input
                                    type="text"
                                    name="serial_number"
                                    id="serial_number"
                                    class="form-control"
                                    value="<?= e($val_serial_number) ?>"
                                    autocomplete="off"
                                >

                            </div>


                            <!-- =================================
                                 NAMA MESIN
                            ================================== -->

                            <div class="col-md-6">

                                <label
                                    for="nama_mesin"
                                    class="form-label"
                                >
                                    Nama Mesin
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="nama_mesin"
                                    id="nama_mesin"
                                    class="form-control"
                                    value="<?= e($val_nama_mesin) ?>"
                                    required
                                    autocomplete="off"
                                >

                            </div>


                            <!-- =================================
                                 AREA
                            ================================== -->

                            <div class="col-md-6">

                                <label
                                    for="id_area"
                                    class="form-label"
                                >
                                    Area
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="id_area"
                                    id="id_area"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        -- Pilih Area --
                                    </option>

                                    <?php

                                    $result_area_form =
                                        mysqli_query(
                                            $conn,
                                            "
                                            SELECT
                                                id,
                                                nama_area,
                                                lokasi
                                            FROM area_bagian
                                            ORDER BY nama_area ASC
                                            "
                                        );

                                    if ($result_area_form):

                                        while (
                                            $area =
                                            mysqli_fetch_assoc(
                                                $result_area_form
                                            )
                                        ):
                                    ?>

                                        <option
                                            value="<?= (int)$area['id'] ?>"
                                            <?= (
                                                (int)$val_id_area ===
                                                (int)$area['id']
                                            )
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >
                                            <?= e($area['nama_area']) ?>

                                            <?php if (!empty($area['lokasi'])): ?>
                                                - <?= e($area['lokasi']) ?>
                                            <?php endif; ?>

                                        </option>

                                    <?php

                                        endwhile;

                                    endif;

                                    ?>

                                </select>

                            </div>


                            <!-- =================================
                                 JENIS MESIN
                            ================================== -->

                            <div class="col-md-6">

                                <label
                                    for="id_jenis_mesin"
                                    class="form-label"
                                >
                                    Jenis Mesin
                                    <span class="text-danger">*</span>
                                </label>

                                <select
                                    name="id_jenis_mesin"
                                    id="id_jenis_mesin"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        -- Pilih Jenis Mesin --
                                    </option>

                                </select>

                            </div>


                            <!-- =================================
                                 KETERANGAN
                            ================================== -->

                            <div class="col-12">

                                <label
                                    for="keterangan"
                                    class="form-label"
                                >
                                    Keterangan
                                </label>

                                <textarea
                                    name="keterangan"
                                    id="keterangan"
                                    class="form-control"
                                    rows="4"
                                ><?= e($val_keterangan) ?></textarea>

                            </div>


                            <!-- =================================
                                 GAMBAR
                            ================================== -->

                            <div class="col-12">

                                <label
                                    for="gambar"
                                    class="form-label"
                                >
                                    Gambar Mesin
                                </label>

                                <input
                                    type="file"
                                    name="gambar"
                                    id="gambar"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                >

                                <div class="form-text">
                                    Maksimal 50MB. Format JPG, JPEG, PNG, atau WEBP.
                                </div>

                            </div>


                            <!-- =================================
                                 PREVIEW GAMBAR
                            ================================== -->

                            <div class="col-12">

                                <div class="image-preview-box">

                                    <?php if (!empty($val_gambar)): ?>

                                        <div class="current-image-title">
                                            Gambar Saat Ini
                                        </div>

                                        <img
                                            src="../uploads/mesin/<?= e($val_gambar) ?>"
                                            alt="Gambar Mesin"
                                            id="currentImagePreview"
                                        >

                                    <?php else: ?>

                                        <div
                                            class="text-muted py-5"
                                            id="currentImagePreview"
                                        >
                                            <i
                                                class="bi bi-image"
                                                style="font-size:42px;"
                                            ></i>

                                            <div class="mt-2">
                                                Belum ada gambar mesin.
                                            </div>

                                        </div>

                                    <?php endif; ?>


                                    <div
                                        class="new-image-preview"
                                        id="newImagePreviewContainer"
                                    >

                                        <div class="current-image-title">
                                            Preview Gambar Baru
                                        </div>

                                        <img
                                            src=""
                                            alt="Preview Gambar Baru"
                                            id="newImagePreview"
                                        >

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- =================================
                             BUTTON
                        ================================== -->

                        <div
                            class="d-flex justify-content-end gap-2 mt-4"
                        >

                            <a
                                href="detail.php?id=<?= (int)$id ?>"
                                class="btn btn-light px-4"
                            >
                                Batal
                            </a>

                            <button
                                type="submit"
                                name="update"
                                id="btnUpdate"
                                class="btn btn-primary px-4"
                            >
                                <i class="bi bi-check-lg me-1"></i>
                                Simpan Perubahan
                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>


        <!-- =================================================
             INFO MESIN
        ================================================== -->

        <div class="col-lg-3">

            <div class="info-card">

                <h6>
                    <i class="bi bi-info-circle me-2"></i>
                    Informasi Mesin
                </h6>


                <div class="info-item">

                    <div class="info-label">
                        Nama Mesin
                    </div>

                    <div class="info-value">
                        <?= e($data['nama_mesin'] ?? '-') ?>
                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Serial Number
                    </div>

                    <div class="info-value">
                        <?= e($data['serial_number'] ?? '-') ?>
                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Area
                    </div>

                    <div class="info-value">
                        <?= e($data['nama_area'] ?? '-') ?>
                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Lokasi
                    </div>

                    <div class="info-value">
                        <?= e($data['lokasi'] ?? '-') ?>
                    </div>

                </div>


                <div class="info-item">

                    <div class="info-label">
                        Jenis Mesin
                    </div>

                    <div class="info-value">
                        <?= e($data['nama_jenis_mesin'] ?? '-') ?>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

/* =========================================================
   DATA AWAL
========================================================= */

const initialArea =
    <?= json_encode((string)$val_id_area) ?>;

const initialJenis =
    <?= json_encode((string)$val_id_jenis_mesin) ?>;


/* =========================================================
   LOAD JENIS MESIN
========================================================= */

function loadJenisMesin(
    idArea,
    selectedJenis = ''
) {

    const selectJenis =
        document.getElementById(
            'id_jenis_mesin'
        );


    if (!selectJenis) {
        return;
    }


    selectJenis.innerHTML =
        '<option value="">Memuat Jenis Mesin...</option>';

    selectJenis.disabled = true;


    if (!idArea) {

        selectJenis.innerHTML =
            '<option value="">-- Pilih Jenis Mesin --</option>';

        selectJenis.disabled = false;

        return;
    }


    fetch(
        '../master/get_jenis_mesin.php?id_area=' +
        encodeURIComponent(idArea),
        {
            method: 'GET',
            cache: 'no-store'
        }
    )

    .then(function(response) {

        if (!response.ok) {
            throw new Error(
                'HTTP ' + response.status
            );
        }

        return response.text();
    })

    .then(function(html) {

        selectJenis.innerHTML = html;

        selectJenis.disabled = false;


        /*
         * Set kembali jenis mesin lama
         * setelah option berhasil dimuat.
         */
        if (selectedJenis) {

            const exists =
                Array.from(
                    selectJenis.options
                ).some(function(option) {

                    return (
                        String(option.value) ===
                        String(selectedJenis)
                    );

                });


            if (exists) {

                selectJenis.value =
                    String(selectedJenis);
            }
        }

    })

    .catch(function(error) {

        console.error(
            'Gagal memuat jenis mesin:',
            error
        );


        selectJenis.innerHTML =
            '<option value="">-- Gagal Memuat Jenis Mesin --</option>';

        selectJenis.disabled = false;
    });
}


/* =========================================================
   EVENT AREA
========================================================= */

const selectArea =
    document.getElementById(
        'id_area'
    );


if (selectArea) {

    selectArea.addEventListener(
        'change',
        function() {

            loadJenisMesin(
                this.value,
                ''
            );

        }
    );

}


/* =========================================================
   LOAD AWAL JENIS MESIN
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function() {

        loadJenisMesin(
            initialArea,
            initialJenis
        );

    }
);


/* =========================================================
   PREVIEW GAMBAR
========================================================= */

const inputGambar =
    document.getElementById(
        'gambar'
    );

const newImagePreview =
    document.getElementById(
        'newImagePreview'
    );

const newImagePreviewContainer =
    document.getElementById(
        'newImagePreviewContainer'
    );


if (inputGambar) {

    inputGambar.addEventListener(
        'change',
        function() {

            const file =
                this.files &&
                this.files[0]
                    ? this.files[0]
                    : null;


            if (!file) {

                if (newImagePreviewContainer) {

                    newImagePreviewContainer.style.display =
                        'none';

                }

                if (newImagePreview) {

                    newImagePreview.src = '';

                }

                return;
            }


            /*
             * Validasi ukuran di sisi client.
             * Validasi server tetap dilakukan PHP.
             */
            const maxSize =
                50 * 1024 * 1024;


            if (file.size > maxSize) {

                alert(
                    'Ukuran gambar terlalu besar! Maksimal 50MB.'
                );


                this.value = '';


                if (newImagePreviewContainer) {

                    newImagePreviewContainer.style.display =
                        'none';

                }

                return;
            }


            /*
             * Validasi tipe file.
             */
            const allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];


            if (
                !allowedTypes.includes(
                    file.type
                )
            ) {

                alert(
                    'Format gambar tidak valid! Gunakan JPG, JPEG, PNG, atau WEBP.'
                );


                this.value = '';


                if (newImagePreviewContainer) {

                    newImagePreviewContainer.style.display =
                        'none';

                }

                return;
            }


            const reader =
                new FileReader();


            reader.onload =
                function(event) {

                    if (newImagePreview) {

                        newImagePreview.src =
                            event.target.result;

                    }


                    if (
                        newImagePreviewContainer
                    ) {

                        newImagePreviewContainer.style.display =
                            'block';

                    }

                };


            reader.readAsDataURL(
                file
            );

        }
    );

}


/* =========================================================
   SUBMIT FORM
========================================================= */

const formEditMesin =
    document.getElementById(
        'formEditMesin'
    );

const btnUpdate =
    document.getElementById(
        'btnUpdate'
    );


if (
    formEditMesin &&
    btnUpdate
) {

    formEditMesin.addEventListener(
        'submit',
        function() {

            /*
             * Jangan preventDefault.
             * Form tetap dikirim ke PHP.
             */

            btnUpdate.disabled = true;

            btnUpdate.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2" role="status"></span>' +
                'Menyimpan...';

        }
    );

}

</script>


<?php

/* =========================================================
   FOOTER TEMPLATE
========================================================= */

include "../template/footer.php";

?>