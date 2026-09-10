<?php
require_once __DIR__ . '/../template/auth.php';
require_user_role();


include "../koneksi.php";

$error = "";
$uploadedFilePath = "";


/* =========================================================
   KONFIGURASI UPLOAD & KOMPRESI
========================================================= */

$maxUploadSize = 10 * 1024 * 1024; // 10 MB

$maxImageWidth  = 1600;
$maxImageHeight = 1600;

$imageQuality = 82;


/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   HELPER RESIZE & COMPRESS GAMBAR
========================================================= */

function compressResizeImage(
    $sourcePath,
    $destinationPath,
    $mimeType,
    $maxWidth = 1600,
    $maxHeight = 1600,
    $quality = 82
) {
    if (!extension_loaded('gd')) {
        return [
            'success' => false,
            'message' => 'GD Library PHP tidak aktif pada server hosting. Silakan aktifkan extension GD.'
        ];
    }

    $imageInfo = @getimagesize($sourcePath);

    if (!$imageInfo) {
        return [
            'success' => false,
            'message' => 'File gambar tidak dapat dibaca oleh server.'
        ];
    }

    $sourceWidth  = (int) $imageInfo[0];
    $sourceHeight = (int) $imageInfo[1];

    if ($sourceWidth <= 0 || $sourceHeight <= 0) {
        return [
            'success' => false,
            'message' => 'Dimensi gambar tidak valid.'
        ];
    }


    /* =====================================================
       BUKA GAMBAR
    ===================================================== */

    switch ($mimeType) {

        case 'image/jpeg':

            if (!function_exists('imagecreatefromjpeg')) {
                return [
                    'success' => false,
                    'message' => 'Dukungan JPEG pada GD Library tidak tersedia.'
                ];
            }

            $sourceImage = @imagecreatefromjpeg($sourcePath);

            break;


        case 'image/png':

            if (!function_exists('imagecreatefrompng')) {
                return [
                    'success' => false,
                    'message' => 'Dukungan PNG pada GD Library tidak tersedia.'
                ];
            }

            $sourceImage = @imagecreatefrompng($sourcePath);

            break;


        case 'image/webp':

            if (!function_exists('imagecreatefromwebp')) {
                return [
                    'success' => false,
                    'message' => 'Dukungan WEBP pada GD Library tidak tersedia.'
                ];
            }

            $sourceImage = @imagecreatefromwebp($sourcePath);

            break;


        default:

            return [
                'success' => false,
                'message' => 'Format gambar tidak didukung.'
            ];
    }


    if (!$sourceImage) {
        return [
            'success' => false,
            'message' => 'Gambar tidak dapat diproses oleh server.'
        ];
    }


    /* =====================================================
       HITUNG UKURAN BARU
    ===================================================== */

    $scale = min(
        $maxWidth / $sourceWidth,
        $maxHeight / $sourceHeight,
        1
    );

    $newWidth = max(
        1,
        (int) round($sourceWidth * $scale)
    );

    $newHeight = max(
        1,
        (int) round($sourceHeight * $scale)
    );


    /* =====================================================
       BUAT CANVAS BARU
    ===================================================== */

    $destinationImage = @imagecreatetruecolor(
        $newWidth,
        $newHeight
    );

    if (!$destinationImage) {

        imagedestroy($sourceImage);

        return [
            'success' => false,
            'message' => 'Gagal membuat canvas gambar baru.'
        ];
    }


    /* =====================================================
       TRANSPARANSI PNG / WEBP
    ===================================================== */

    if (
        $mimeType === 'image/png' ||
        $mimeType === 'image/webp'
    ) {

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

    $resizeSuccess = imagecopyresampled(
        $destinationImage,
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


    if (!$resizeSuccess) {

        imagedestroy($sourceImage);
        imagedestroy($destinationImage);

        return [
            'success' => false,
            'message' => 'Gagal melakukan resize gambar.'
        ];
    }


    /* =====================================================
       SIMPAN HASIL
    ===================================================== */

    $saveSuccess = false;

    switch ($mimeType) {

        case 'image/jpeg':

            $saveSuccess = @imagejpeg(
                $destinationImage,
                $destinationPath,
                $quality
            );

            break;


        case 'image/png':

            /*
             * PNG menggunakan compression level 0-9.
             * 6 cukup seimbang antara ukuran dan kualitas.
             */
            $saveSuccess = @imagepng(
                $destinationImage,
                $destinationPath,
                6
            );

            break;


        case 'image/webp':

            if (function_exists('imagewebp')) {

                $saveSuccess = @imagewebp(
                    $destinationImage,
                    $destinationPath,
                    $quality
                );

            }

            break;
    }


    imagedestroy($sourceImage);
    imagedestroy($destinationImage);


    if (!$saveSuccess) {

        return [
            'success' => false,
            'message' => 'Gagal menyimpan gambar hasil kompresi. Pastikan folder uploads/mesin dapat ditulis oleh server.'
        ];
    }


    return [
        'success' => true,
        'width'   => $newWidth,
        'height'  => $newHeight
    ];
}


/* =========================================================
   PROSES SIMPAN DATA
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $serial_number  = trim($_POST['serial_number'] ?? '');
    $nama_mesin     = trim($_POST['nama_mesin'] ?? '');
    $id_area        = (int) ($_POST['id_area'] ?? 0);
    $id_jenis_mesin = (int) ($_POST['id_jenis_mesin'] ?? 0);
    $keterangan     = trim($_POST['keterangan'] ?? '');

    $nama_gambar = null;


    /* =====================================================
       VALIDASI FORM
    ===================================================== */

    if ($nama_mesin === '') {

        $error = "Nama Mesin wajib diisi!";

    } elseif ($id_area <= 0) {

        $error = "Area Bagian wajib dipilih!";

    } elseif ($id_jenis_mesin <= 0) {

        $error = "Jenis Mesin wajib dipilih!";
    }


    /* =====================================================
       CEK AREA
    ===================================================== */

    if (empty($error)) {

        $stmt_check_area = mysqli_prepare(
            $conn,
            "SELECT id
             FROM area_bagian
             WHERE id = ?
             LIMIT 1"
        );

        if (!$stmt_check_area) {

            $error =
                "Gagal memeriksa Area Bagian: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt_check_area,
                "i",
                $id_area
            );

            if (!mysqli_stmt_execute($stmt_check_area)) {

                $error =
                    "Gagal memeriksa Area Bagian: " .
                    mysqli_stmt_error($stmt_check_area);

            } else {

                $result_area =
                    mysqli_stmt_get_result(
                        $stmt_check_area
                    );

                if (
                    !$result_area ||
                    mysqli_num_rows($result_area) === 0
                ) {

                    $error =
                        "Area Bagian yang dipilih tidak ditemukan.";
                }
            }

            mysqli_stmt_close(
                $stmt_check_area
            );
        }
    }


    /* =====================================================
       CEK JENIS MESIN SESUAI AREA
    ===================================================== */

    if (empty($error)) {

        $stmt_check_jenis = mysqli_prepare(
            $conn,
            "SELECT id
             FROM jenis_mesin
             WHERE id = ?
             AND id_area = ?
             LIMIT 1"
        );

        if (!$stmt_check_jenis) {

            $error =
                "Gagal memeriksa Jenis Mesin: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt_check_jenis,
                "ii",
                $id_jenis_mesin,
                $id_area
            );

            if (!mysqli_stmt_execute($stmt_check_jenis)) {

                $error =
                    "Gagal memeriksa Jenis Mesin: " .
                    mysqli_stmt_error($stmt_check_jenis);

            } else {

                $result_jenis =
                    mysqli_stmt_get_result(
                        $stmt_check_jenis
                    );

                if (
                    !$result_jenis ||
                    mysqli_num_rows($result_jenis) === 0
                ) {

                    $error =
                        "Jenis Mesin yang dipilih tidak sesuai dengan Area Bagian.";
                }
            }

            mysqli_stmt_close(
                $stmt_check_jenis
            );
        }
    }


    /* =====================================================
       UPLOAD FOTO
       MAKSIMAL FILE ASLI 10 MB
       
       CATATAN:
       Tidak menggunakan PHP Fileinfo.
       MIME diambil dari getimagesize().
    ===================================================== */

    if (
        empty($error) &&
        isset($_FILES['gambar']) &&
        $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $fileError =
            (int) $_FILES['gambar']['error'];


        /* =================================================
           VALIDASI ERROR UPLOAD
        ================================================= */

        if ($fileError !== UPLOAD_ERR_OK) {

            switch ($fileError) {

                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:

                    $error =
                        "Ukuran gambar terlalu besar! Maksimal 10MB.";

                    break;


                case UPLOAD_ERR_PARTIAL:

                    $error =
                        "Upload gambar tidak selesai.";

                    break;


                case UPLOAD_ERR_NO_TMP_DIR:

                    $error =
                        "Folder temporary upload PHP tidak tersedia pada server.";

                    break;


                case UPLOAD_ERR_CANT_WRITE:

                    $error =
                        "Server gagal menulis file upload.";

                    break;


                case UPLOAD_ERR_EXTENSION:

                    $error =
                        "Upload gambar dihentikan oleh extension PHP.";

                    break;


                default:

                    $error =
                        "Terjadi kesalahan saat mengunggah gambar. Kode: " .
                        $fileError;

                    break;
            }

        } else {

            $fileTmpPath =
                $_FILES['gambar']['tmp_name'];

            $fileName =
                $_FILES['gambar']['name'];

            $fileSize =
                (int) $_FILES['gambar']['size'];


            /* =================================================
               VALIDASI FILE SIZE
            ================================================= */

            if ($fileSize <= 0) {

                $error =
                    "File gambar kosong atau tidak valid.";

            } elseif ($fileSize > $maxUploadSize) {

                $error =
                    "Ukuran gambar terlalu besar! Maksimal 10MB.";
            }


            /* =================================================
               VALIDASI UPLOAD
            ================================================= */

            elseif (!is_uploaded_file($fileTmpPath)) {

                $error =
                    "File upload tidak valid.";
            }


            /* =================================================
               VALIDASI EXTENSION
            ================================================= */

            $fileExtension = '';

            if (empty($error)) {

                $fileExtension =
                    strtolower(
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
                        "Format gambar tidak valid! Hanya JPG, JPEG, PNG, dan WEBP.";
                }
            }


            /* =================================================
               VALIDASI GAMBAR + MIME
               
               TIDAK MENGGUNAKAN FINO_OPEN()
               ================================================= */

            $imageInfo = null;
            $mimeType = '';

            if (empty($error)) {

                $imageInfo =
                    @getimagesize(
                        $fileTmpPath
                    );

                if (!$imageInfo) {

                    $error =
                        "File yang diupload bukan gambar yang valid.";

                } else {

                    /*
                     * getimagesize() memberikan MIME asli gambar.
                     * Jadi tidak membutuhkan extension Fileinfo.
                     */
                    $mimeType =
                        strtolower(
                            trim(
                                (string) ($imageInfo['mime'] ?? '')
                            )
                        );


                    $allowedMimeTypes = [
                        'image/jpeg',
                        'image/png',
                        'image/webp'
                    ];


                    if (
                        !in_array(
                            $mimeType,
                            $allowedMimeTypes,
                            true
                        )
                    ) {

                        $error =
                            "Format gambar tidak didukung. Gunakan JPG, JPEG, PNG atau WEBP.";
                    }
                }
            }


            /* =================================================
               PASTIKAN EXTENSION SESUAI MIME
            ================================================= */

            if (empty($error)) {

                $extensionValid = false;

                if (
                    $mimeType === 'image/jpeg' &&
                    in_array(
                        $fileExtension,
                        ['jpg', 'jpeg'],
                        true
                    )
                ) {
                    $extensionValid = true;
                }

                if (
                    $mimeType === 'image/png' &&
                    $fileExtension === 'png'
                ) {
                    $extensionValid = true;
                }

                if (
                    $mimeType === 'image/webp' &&
                    $fileExtension === 'webp'
                ) {
                    $extensionValid = true;
                }


                if (!$extensionValid) {

                    $error =
                        "Format file tidak sesuai dengan isi gambar.";
                }
            }


            /* =================================================
               DIMENSI GAMBAR
            ================================================= */

            if (empty($error)) {

                $imageWidth =
                    (int) ($imageInfo[0] ?? 0);

                $imageHeight =
                    (int) ($imageInfo[1] ?? 0);


                if (
                    $imageWidth <= 0 ||
                    $imageHeight <= 0
                ) {

                    $error =
                        "Dimensi gambar tidak valid.";
                }
            }


            /* =================================================
               SIAPKAN FOLDER UPLOAD
            ================================================= */

            if (empty($error)) {

                $targetDir =
                    __DIR__ . "/../uploads/mesin/";


                if (!is_dir($targetDir)) {

                    if (
                        !@mkdir(
                            $targetDir,
                            0755,
                            true
                        )
                    ) {

                        $error =
                            "Folder upload gambar tidak dapat dibuat oleh server.";
                    }
                }


                /*
                 * Cek ulang setelah mkdir.
                 */
                if (
                    empty($error) &&
                    !is_dir($targetDir)
                ) {

                    $error =
                        "Folder uploads/mesin tidak ditemukan.";
                }


                /*
                 * Cek apakah writable.
                 */
                if (
                    empty($error) &&
                    !is_writable($targetDir)
                ) {

                    $error =
                        "Folder upload gambar tidak dapat ditulis oleh server. Periksa permission folder uploads/mesin.";
                }
            }


            /* =================================================
               BUAT NAMA FILE
            ================================================= */

            if (empty($error)) {

                try {

                    $randomName =
                        bin2hex(
                            random_bytes(8)
                        );

                } catch (Exception $e) {

                    $randomName =
                        uniqid(
                            '',
                            true
                        );
                }


                /*
                 * Gunakan extension berdasarkan MIME asli.
                 */
                switch ($mimeType) {

                    case 'image/jpeg':
                        $saveExtension = 'jpg';
                        break;

                    case 'image/png':
                        $saveExtension = 'png';
                        break;

                    case 'image/webp':
                        $saveExtension = 'webp';
                        break;

                    default:
                        $saveExtension = $fileExtension;
                        break;
                }


                $nama_gambar =
                    "mesin_" .
                    date("YmdHis") .
                    "_" .
                    $randomName .
                    "." .
                    $saveExtension;


                $targetPath =
                    $targetDir .
                    $nama_gambar;


                /* =================================================
                   COMPRESS + RESIZE
                ================================================= */

                $compressResult =
                    compressResizeImage(
                        $fileTmpPath,
                        $targetPath,
                        $mimeType,
                        $maxImageWidth,
                        $maxImageHeight,
                        $imageQuality
                    );


                if (
                    !$compressResult['success']
                ) {

                    $error =
                        $compressResult['message'];

                    $nama_gambar = null;

                } else {

                    $uploadedFilePath =
                        $targetPath;
                }
            }
        }
    }


    /* =====================================================
       SIMPAN DATABASE
    ===================================================== */

    if (empty($error)) {

        $sql = "
            INSERT INTO mesin
            (
                serial_number,
                nama_mesin,
                id_area,
                id_jenis_mesin,
                keterangan,
                gambar
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ";


        $stmt =
            mysqli_prepare(
                $conn,
                $sql
            );


        if (!$stmt) {

            $error =
                "Gagal menyiapkan query INSERT: " .
                mysqli_error($conn);

        } else {

            /*
             * s = string
             * s = string
             * i = integer
             * i = integer
             * s = string
             * s = string
             */

            $bindSuccess =
                mysqli_stmt_bind_param(
                    $stmt,
                    "ssiiss",
                    $serial_number,
                    $nama_mesin,
                    $id_area,
                    $id_jenis_mesin,
                    $keterangan,
                    $nama_gambar
                );


            if (!$bindSuccess) {

                $error =
                    "Gagal mengikat data INSERT: " .
                    mysqli_stmt_error($stmt);

            } else {

                $executeSuccess =
                    mysqli_stmt_execute(
                        $stmt
                    );


                if ($executeSuccess) {

                    mysqli_stmt_close(
                        $stmt
                    );


                    /*
                     * BERHASIL
                     */
                    header(
                        "Location: index.php"
                    );

                    exit;

                } else {

                    $error =
                        "Gagal menyimpan data mesin: " .
                        mysqli_stmt_error($stmt);


                    mysqli_stmt_close(
                        $stmt
                    );


                    /* =============================================
                       HAPUS FILE JIKA DATABASE GAGAL
                    ============================================== */

                    if (
                        !empty($uploadedFilePath) &&
                        file_exists($uploadedFilePath)
                    ) {

                        @unlink(
                            $uploadedFilePath
                        );
                    }
                }
            }
        }
    }
}


/* =========================================================
   DATA POST UNTUK FORM
========================================================= */

$old_serial_number =
    $_POST['serial_number'] ?? '';

$old_nama_mesin =
    $_POST['nama_mesin'] ?? '';

$old_id_area =
    $_POST['id_area'] ?? '';

$old_id_jenis =
    $_POST['id_jenis_mesin'] ?? '';

$old_keterangan =
    $_POST['keterangan'] ?? '';


/* =========================================================
   HEADER
========================================================= */

include "../template/header.php";

?>


<style>

/* =========================================================
   PAGE
========================================================= */

.machine-form-page {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.machine-form-header {
    position: relative;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 20px 22px;
    margin-bottom: 20px;
    box-shadow:
        0 3px 12px rgba(15, 23, 42, .04);
}

.machine-form-header::before {
    content: "";
    position: absolute;
    left: 0;
    top: 18px;
    bottom: 18px;
    width: 4px;
    background: linear-gradient(
        180deg,
        #005baa,
        #0076c8
    );
    border-radius: 0 5px 5px 0;
}


/* =========================================================
   BACK BUTTON
========================================================= */

.machine-back-btn {
    width: 38px;
    height: 38px;
    min-width: 38px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    border: 1px solid #dbe3ea;
    background: #ffffff;
    text-decoration: none;
    transition: .2s;
}

.machine-back-btn:hover {
    background: #005baa;
    border-color: #005baa;
    color: #ffffff;
    transform: translateX(-2px);
}


/* =========================================================
   TITLE
========================================================= */

.machine-form-title {
    font-size: 24px;
    font-weight: 800;
    color: #172033;
    line-height: 1.25;
    margin: 0;
}

.machine-form-subtitle {
    font-size: 13px;
    color: #64748b;
    margin-top: 5px;
}


/* =========================================================
   FORM CARD
========================================================= */

.machine-form-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
    box-shadow:
        0 3px 12px rgba(15, 23, 42, .04);
}

.machine-form-card-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
    background: #ffffff;
}

.machine-form-card-title {
    color: #172033;
    font-size: 15px;
    font-weight: 700;
    margin: 0;
}

.machine-form-card-subtitle {
    color: #94a3b8;
    font-size: 11px;
    margin-top: 3px;
}

.machine-form-card-body {
    padding: 22px;
}


/* =========================================================
   LABEL
========================================================= */

.machine-form-label {
    font-size: 12px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 6px;
}

.machine-form-label .required {
    color: #dc2626;
}


/* =========================================================
   INPUT
========================================================= */

.machine-form-control {
    min-height: 40px;
    border: 1px solid #dbe3ea;
    border-radius: 8px;
    font-size: 12px;
    color: #334155;
    transition: .2s;
}

.machine-form-control:focus {
    border-color: #0076c8;
    box-shadow:
        0 0 0 3px rgba(0,118,200,.08);
}

.machine-form-control::placeholder {
    color: #a0aec0;
}


/* =========================================================
   SELECT
========================================================= */

.machine-form-select {
    min-height: 40px;
    border: 1px solid #dbe3ea;
    border-radius: 8px;
    font-size: 12px;
    color: #334155;
    cursor: pointer;
    transition: .2s;
}

.machine-form-select:focus {
    border-color: #0076c8;
    box-shadow:
        0 0 0 3px rgba(0,118,200,.08);
}

.machine-form-select:disabled {
    background-color: #f8fafc;
    cursor: not-allowed;
    color: #94a3b8;
}


/* =========================================================
   HELP TEXT
========================================================= */

.machine-form-help {
    color: #94a3b8;
    font-size: 10px;
    line-height: 1.5;
    margin-top: 5px;
}


/* =========================================================
   FILE UPLOAD
========================================================= */

.machine-file-wrapper {
    position: relative;
}

.machine-file-input {
    min-height: 40px;
    border: 1px solid #dbe3ea;
    border-radius: 8px;
    font-size: 11px;
    color: #475569;
    cursor: pointer;
}

.machine-file-input:focus {
    border-color: #0076c8;
    box-shadow:
        0 0 0 3px rgba(0,118,200,.08);
}


/* =========================================================
   IMAGE PREVIEW
========================================================= */

.machine-image-preview {
    display: none;
    margin-top: 12px;
    padding: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
    max-width: 220px;
}

.machine-image-preview img {
    display: block;
    width: 100%;
    max-height: 150px;
    object-fit: contain;
    border-radius: 7px;
}

.machine-image-preview-name {
    font-size: 10px;
    color: #64748b;
    margin-top: 7px;
    word-break: break-word;
}


/* =========================================================
   TEXTAREA
========================================================= */

.machine-textarea {
    min-height: 90px;
    resize: vertical;
}


/* =========================================================
   ERROR
========================================================= */

.machine-form-error {
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #991b1b;
    border-radius: 9px;
    padding: 11px 13px;
    font-size: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 9px;
}

.machine-form-error i {
    font-size: 15px;
    margin-top: 1px;
    flex-shrink: 0;
}


/* =========================================================
   SECTION
========================================================= */

.machine-form-section {
    font-size: 12px;
    font-weight: 700;
    color: #172033;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 10px;
    margin-bottom: 3px;
    border-bottom: 1px solid #eef2f7;
}

.machine-form-section i {
    color: #005baa;
    font-size: 15px;
}


/* =========================================================
   FOOTER BUTTON
========================================================= */

.machine-form-footer {
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
}

.machine-btn-save {
    background: linear-gradient(
        135deg,
        #005baa,
        #0076c8
    );
    border: none;
    color: #ffffff;
    min-height: 39px;
    padding: 8px 17px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    transition: .2s;
}

.machine-btn-save:hover {
    color: #ffffff;
    transform: translateY(-1px);
    box-shadow:
        0 6px 15px rgba(0,91,170,.20);
}

.machine-btn-cancel {
    min-height: 39px;
    padding: 8px 17px;
    border-radius: 8px;
    background: #ffffff;
    border: 1px solid #dbe3ea;
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: .2s;
}

.machine-btn-cancel:hover {
    background: #f8fafc;
    color: #334155;
    border-color: #cbd5e1;
}


/* =========================================================
   LOADING
========================================================= */

.machine-select-loading {
    color: #64748b;
    font-size: 10px;
    margin-top: 5px;
    display: none;
}


/* =========================================================
   RESPONSIVE TABLET
========================================================= */

@media (max-width: 768px) {

    .machine-form-header {
        padding: 17px 18px;
    }

    .machine-form-title {
        font-size: 21px;
    }

    .machine-form-subtitle {
        font-size: 12px;
    }

    .machine-form-card-body {
        padding: 17px;
    }

    .machine-form-footer {
        justify-content: stretch;
        flex-direction: column-reverse;
    }

    .machine-btn-save,
    .machine-btn-cancel {
        width: 100%;
    }
}


/* =========================================================
   RESPONSIVE MOBILE
========================================================= */

@media (max-width: 576px) {

    .machine-form-page {
        width: 100%;
    }

    .machine-form-header {
        padding: 15px;
        border-radius: 11px;
        margin-bottom: 14px;
    }

    .machine-form-header::before {
        top: 14px;
        bottom: 14px;
    }

    .machine-back-btn {
        width: 35px;
        height: 35px;
        min-width: 35px;
    }

    .machine-form-title {
        font-size: 18px;
    }

    .machine-form-subtitle {
        font-size: 11px;
        line-height: 1.5;
    }

    .machine-form-card {
        border-radius: 11px;
    }

    .machine-form-card-header {
        padding: 14px 15px;
    }

    .machine-form-card-body {
        padding: 15px;
    }

    .machine-form-card-title {
        font-size: 14px;
    }

    .machine-form-control,
    .machine-form-select {
        min-height: 42px;
        font-size: 12px;
    }

    .machine-file-input {
        font-size: 10px;
        padding: 9px;
    }

    .machine-form-help {
        font-size: 9px;
    }

    .machine-form-error {
        font-size: 11px;
    }
}

</style>


<div class="container-fluid p-0 machine-form-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="machine-form-header">

        <div class="d-flex align-items-center gap-3">

            <a
                href="index.php"
                class="machine-back-btn"
                title="Kembali"
            >
                <i class="bi bi-arrow-left"></i>
            </a>

            <div>

                <h2 class="machine-form-title">
                    Tambah Mesin Baru
                </h2>

                <div class="machine-form-subtitle">
                    Daftarkan mesin baru ke dalam sistem inventory
                    maintenance.
                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <div class="machine-form-card">


        <!-- CARD HEADER -->

        <div class="machine-form-card-header">

            <div class="machine-form-card-title">

                <i class="bi bi-plus-circle text-primary me-2"></i>

                Form Tambah Mesin

            </div>

            <div class="machine-form-card-subtitle">

                Lengkapi informasi mesin dengan data yang sesuai.

            </div>

        </div>


        <!-- CARD BODY -->

        <div class="machine-form-card-body">


            <!-- =================================================
                 ERROR
            ================================================== -->

            <?php if (!empty($error)): ?>

                <div class="machine-form-error">

                    <i class="bi bi-exclamation-triangle-fill"></i>

                    <div>
                        <?= e($error) ?>
                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
                method="POST"
                enctype="multipart/form-data"
                id="formMesin"
            >


                <!-- =================================================
                     SECTION IDENTITAS
                ================================================== -->

                <div class="machine-form-section">

                    <i class="bi bi-info-circle"></i>

                    Identitas Mesin

                </div>


                <div class="row g-3 mt-1">


                    <!-- SERIAL NUMBER -->

                    <div class="col-12 col-md-6">

                        <label class="machine-form-label">

                            Serial Number

                        </label>

                        <input
                            type="text"
                            name="serial_number"
                            class="form-control machine-form-control"
                            placeholder="Contoh: SN-2024-001"
                            value="<?= e($old_serial_number) ?>"
                            maxlength="100"
                        >

                        <div class="machine-form-help">

                            Nomor seri sebagai identitas unik mesin.

                        </div>

                    </div>


                    <!-- NAMA MESIN -->

                    <div class="col-12 col-md-6">

                        <label class="machine-form-label">

                            Nama Mesin

                            <span class="required">*</span>

                        </label>

                        <input
                            type="text"
                            name="nama_mesin"
                            class="form-control machine-form-control"
                            placeholder="Contoh: Mesin Packing Line 1"
                            value="<?= e($old_nama_mesin) ?>"
                            maxlength="150"
                            required
                        >

                        <div class="machine-form-help">

                            Nama resmi atau nama unit mesin.

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     SECTION LOKASI
                ================================================== -->

                <div class="machine-form-section mt-4">

                    <i class="bi bi-geo-alt"></i>

                    Lokasi & Jenis Mesin

                </div>


                <div class="row g-3 mt-1">


                    <!-- AREA -->

                    <div class="col-12 col-md-6">

                        <label
                            for="id_area"
                            class="machine-form-label"
                        >

                            Area Bagian

                            <span class="required">*</span>

                        </label>

                        <select
                            name="id_area"
                            id="id_area"
                            class="form-select machine-form-select"
                            required
                        >

                            <option value="">
                                -- Pilih Area --
                            </option>

                            <?php

                            $q_area = mysqli_query(
                                $conn,
                                "SELECT id, nama_area, lokasi
                                 FROM area_bagian
                                 ORDER BY nama_area ASC"
                            );

                            if ($q_area):

                                while (
                                    $a =
                                    mysqli_fetch_assoc(
                                        $q_area
                                    )
                                ):

                                    $selected =
                                        (
                                            (string) $old_id_area ===
                                            (string) $a['id']
                                        )
                                        ? 'selected'
                                        : '';

                            ?>

                                    <option
                                        value="<?= (int) $a['id'] ?>"
                                        <?= $selected ?>
                                    >

                                        <?= e($a['nama_area']) ?>

                                        <?php if (!empty($a['lokasi'])): ?>

                                            - <?= e($a['lokasi']) ?>

                                        <?php endif; ?>

                                    </option>

                            <?php

                                endwhile;

                            endif;

                            ?>

                        </select>

                        <div class="machine-form-help">

                            Pilih area tempat mesin berada.

                        </div>

                    </div>


                    <!-- JENIS MESIN -->

                    <div class="col-12 col-md-6">

                        <label
                            for="id_jenis_mesin"
                            class="machine-form-label"
                        >

                            Jenis Mesin

                            <span class="required">*</span>

                        </label>

                        <select
                            name="id_jenis_mesin"
                            id="id_jenis_mesin"
                            class="form-select machine-form-select"
                            required
                            <?= empty($old_id_area) ? 'disabled' : '' ?>
                        >

                            <option value="">

                                <?php if (empty($old_id_area)): ?>

                                    -- Pilih Area Terlebih Dahulu --

                                <?php else: ?>

                                    -- Pilih Jenis Mesin --

                                <?php endif; ?>

                            </option>

                        </select>

                        <div
                            id="jenisLoading"
                            class="machine-select-loading"
                        >

                            <i class="bi bi-arrow-repeat me-1"></i>

                            Memuat jenis mesin...

                        </div>

                        <div class="machine-form-help">

                            Jenis mesin akan menyesuaikan dengan area
                            yang dipilih.

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     SECTION FOTO
                ================================================== -->

                <div class="machine-form-section mt-4">

                    <i class="bi bi-image"></i>

                    Foto Mesin

                </div>


                <div class="row g-3 mt-1">

                    <div class="col-12">

                        <label
                            for="gambar"
                            class="machine-form-label"
                        >

                            Foto Mesin

                        </label>

                        <input
                            type="file"
                            name="gambar"
                            id="gambar"
                            class="form-control machine-file-input"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >

                        <div class="machine-form-help">

                            Format JPG, JPEG, PNG atau WEBP.
                            Ukuran maksimal 10MB.

                        </div>


                        <!-- PREVIEW -->

                        <div
                            id="imagePreview"
                            class="machine-image-preview"
                        >

                            <img
                                id="previewImage"
                                src=""
                                alt="Preview Foto Mesin"
                            >

                            <div
                                id="previewName"
                                class="machine-image-preview-name"
                            ></div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     SECTION KETERANGAN
                ================================================== -->

                <div class="machine-form-section mt-4">

                    <i class="bi bi-card-text"></i>

                    Keterangan

                </div>


                <div class="row g-3 mt-1">

                    <div class="col-12">

                        <label
                            for="keterangan"
                            class="machine-form-label"
                        >

                            Keterangan / Deskripsi

                        </label>

                        <textarea
                            name="keterangan"
                            id="keterangan"
                            class="form-control machine-form-control machine-textarea"
                            placeholder="Tambahkan deskripsi atau catatan tentang mesin..."
                        ><?= e($old_keterangan) ?></textarea>

                        <div class="machine-form-help">

                            Tambahkan informasi tambahan mengenai kondisi
                            atau karakteristik mesin.

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     BUTTON
                ================================================== -->

                <div class="machine-form-footer">

                    <a
                        href="index.php"
                        class="machine-btn-cancel"
                    >

                        <i class="bi bi-x-lg me-1"></i>

                        Batal

                    </a>

                    <button
                        type="submit"
                        name="simpan"
                        class="machine-btn-save"
                        id="btnSimpan"
                    >

                        <i class="bi bi-check-lg me-1"></i>

                        Simpan Data

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =========================================================
     JQUERY
========================================================= -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>

$(document).ready(function () {

    const MAX_FILE_SIZE = 10 * 1024 * 1024;


    /* =====================================================
       DROPDOWN JENIS MESIN
    ===================================================== */

    function loadJenisMesin(
        idArea,
        selectedJenis = ''
    ) {

        const $jenis = $('#id_jenis_mesin');
        const $loading = $('#jenisLoading');

        if (idArea === '') {

            $jenis
                .html(
                    '<option value="">-- Pilih Area Terlebih Dahulu --</option>'
                )
                .prop(
                    'disabled',
                    true
                );

            $loading.hide();

            return;
        }


        $jenis
            .html(
                '<option value="">-- Memuat Jenis Mesin... --</option>'
            )
            .prop(
                'disabled',
                true
            );

        $loading.show();


        $.ajax({

            url: '../master/get_jenis_mesin.php',

            type: 'GET',

            data: {
                id_area: idArea
            },

            dataType: 'html',

            timeout: 10000,


            success: function (data) {

                if ($.trim(data) === '') {

                    $jenis
                        .html(
                            '<option value="">-- Tidak ada Jenis Mesin --</option>'
                        )
                        .prop(
                            'disabled',
                            true
                        );

                    return;
                }


                $jenis
                    .html(data)
                    .prop(
                        'disabled',
                        false
                    );


                if (selectedJenis !== '') {

                    $jenis.val(
                        selectedJenis
                    );
                }

            },


            error: function () {

                $jenis
                    .html(
                        '<option value="">-- Gagal Memuat Data --</option>'
                    )
                    .prop(
                        'disabled',
                        true
                    );
            },


            complete: function () {

                $loading.hide();

            }

        });

    }


    /* =====================================================
       LOAD DATA AWAL
    ===================================================== */

    const initialArea =
        $('#id_area').val();


    const oldJenisMesin =
        <?= json_encode((string) $old_id_jenis) ?>;


    if (initialArea !== '') {

        loadJenisMesin(
            initialArea,
            oldJenisMesin
        );
    }


    /* =====================================================
       PERUBAHAN AREA
    ===================================================== */

    $('#id_area').on(
        'change',
        function () {

            const idArea =
                $(this).val();

            loadJenisMesin(
                idArea
            );

        }
    );


    /* =====================================================
       PREVIEW GAMBAR
    ===================================================== */

    $('#gambar').on(
        'change',
        function () {

            const file =
                this.files[0];


            const $preview =
                $('#imagePreview');

            const $image =
                $('#previewImage');

            const $name =
                $('#previewName');


            if (!file) {

                $preview.hide();

                $image.attr(
                    'src',
                    ''
                );

                $name.text(
                    ''
                );

                return;
            }


            const allowedTypes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];


            /*
             * Validasi browser.
             * Validasi server tetap dilakukan menggunakan getimagesize().
             */

            if (
                file.type &&
                !allowedTypes.includes(
                    file.type
                )
            ) {

                alert(
                    'Format gambar tidak valid. Gunakan JPG, JPEG, PNG atau WEBP.'
                );

                this.value = '';

                $preview.hide();

                return;
            }


            if (
                file.size >
                MAX_FILE_SIZE
            ) {

                alert(
                    'Ukuran gambar terlalu besar. Maksimal 10MB.'
                );

                this.value = '';

                $preview.hide();

                return;
            }


            const reader =
                new FileReader();


            reader.onload =
                function (event) {

                    $image.attr(
                        'src',
                        event.target.result
                    );

                    $name.text(
                        file.name
                    );

                    $preview.show();

                };


            reader.readAsDataURL(
                file
            );

        }
    );


    /* =====================================================
       PREVENT DOUBLE SUBMIT
    ===================================================== */

    $('#formMesin').on(
        'submit',
        function () {

            const $button =
                $('#btnSimpan');


            if (
                $button.data(
                    'submitted'
                )
            ) {

                return false;
            }


            $button.data(
                'submitted',
                true
            );


            $button
                .prop(
                    'disabled',
                    true
                )
                .html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...'
                );

        }
    );

});

</script>


<?php

include "../template/footer.php";

?>