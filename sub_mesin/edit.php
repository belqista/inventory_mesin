<?php
require_once __DIR__ . '/../template/auth.php';
require_user_role();


/*
|--------------------------------------------------------------------------
| EDIT SUB MESIN
|--------------------------------------------------------------------------
| File : sub_mesin/edit.php
|--------------------------------------------------------------------------
*/

mysqli_report(MYSQLI_REPORT_OFF);

require_once "../koneksi.php";


/* =========================================================
   KONFIGURASI UPLOAD & KOMPRESI
========================================================= */

$maxUploadSize = 10 * 1024 * 1024; // 10 MB

$maxImageWidth  = 1600;
$maxImageHeight = 1600;

$imageQuality = 82;

$targetDir = "../uploads/sub_mesin/";


/* =========================================================
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/**
 * Hapus file.
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
 * Generate nama file gambar yang aman.
 */
function generateSafeImageName($mimeType)
{
    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    $extension = $extensionMap[$mimeType] ?? 'jpg';

    try {

        $randomName = bin2hex(
            random_bytes(16)
        );

    } catch (Exception $e) {

        $randomName = md5(
            uniqid(
                '',
                true
            )
        );
    }

    return 'submesin_' .
        $randomName .
        '.' .
        $extension;
}


/**
 * Membuat resource gambar.
 */
function createImageResource(
    $filePath,
    $mimeType
) {
    switch ($mimeType) {

        case 'image/jpeg':

            if (
                !function_exists(
                    'imagecreatefromjpeg'
                )
            ) {
                return false;
            }

            return @imagecreatefromjpeg(
                $filePath
            );


        case 'image/png':

            if (
                !function_exists(
                    'imagecreatefrompng'
                )
            ) {
                return false;
            }

            return @imagecreatefrompng(
                $filePath
            );


        case 'image/webp':

            if (
                !function_exists(
                    'imagecreatefromwebp'
                )
            ) {
                return false;
            }

            return @imagecreatefromwebp(
                $filePath
            );


        default:

            return false;
    }
}


/**
 * Menyimpan gambar hasil proses.
 */
function saveProcessedImage(
    $sourceImage,
    $targetPath,
    $mimeType,
    $quality
) {
    switch ($mimeType) {

        /* =================================================
           JPEG
        ================================================= */

        case 'image/jpeg':

            if (
                !function_exists(
                    'imagejpeg'
                )
            ) {
                return false;
            }

            return @imagejpeg(
                $sourceImage,
                $targetPath,
                $quality
            );


        /* =================================================
           PNG
        ================================================= */

        case 'image/png':

            if (
                !function_exists(
                    'imagepng'
                )
            ) {
                return false;
            }

            /*
             * PNG menggunakan compression 0-9.
             */
            $pngCompression = 9 -
                (int)round(
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


        /* =================================================
           WEBP
        ================================================= */

        case 'image/webp':

            if (
                !function_exists(
                    'imagewebp'
                )
            ) {
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
 * Resize gambar jika lebih besar dari batas.
 */
function resizeImageIfNeeded(
    $sourceImage,
    $sourceWidth,
    $sourceHeight,
    $maxWidth,
    $maxHeight
) {

    /*
     * Jika ukuran sudah sesuai,
     * resource asli digunakan.
     */
    if (
        $sourceWidth <= $maxWidth &&
        $sourceHeight <= $maxHeight
    ) {
        return $sourceImage;
    }


    /*
     * Hitung rasio.
     */
    $ratio = min(
        $maxWidth / $sourceWidth,
        $maxHeight / $sourceHeight
    );


    $newWidth = max(
        1,
        (int)round(
            $sourceWidth * $ratio
        )
    );


    $newHeight = max(
        1,
        (int)round(
            $sourceHeight * $ratio
        )
    );


    if (
        !function_exists(
            'imagecreatetruecolor'
        )
    ) {
        return false;
    }


    $newImage = @imagecreatetruecolor(
        $newWidth,
        $newHeight
    );


    if (!$newImage) {
        return false;
    }


    /*
     * Transparansi.
     */
    imagealphablending(
        $newImage,
        false
    );


    imagesavealpha(
        $newImage,
        true
    );


    $transparent =
        imagecolorallocatealpha(
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


    /*
     * Resize.
     */
    $success =
        imagecopyresampled(
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

        @imagedestroy(
            $newImage
        );

        return false;
    }


    /*
     * Resource lama tidak dibutuhkan.
     */
    @imagedestroy(
        $sourceImage
    );


    return $newImage;
}


/* =========================================================
   CEK KONEKSI DATABASE
========================================================= */

if (
    !isset($conn) ||
    !$conn
) {

    die(
        "Koneksi database gagal. " .
        "Pastikan file ../koneksi.php menghasilkan variabel \$conn."
    );
}


/* =========================================================
   PARAMETER ID
========================================================= */

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


if ($id <= 0) {

    header(
        "Location: index.php"
    );

    exit;
}


/* =========================================================
   VARIABEL FORM
========================================================= */

$error = "";

$val_id_mesin       = 0;
$val_nama_sub_mesin = "";
$val_keterangan     = "";
$nama_gambar        = "";


/* =========================================================
   AMBIL DATA SUB MESIN
========================================================= */

$stmt_get = mysqli_prepare(
    $conn,
    "
    SELECT
        id,
        id_mesin,
        nama_sub_mesin,
        serial_number,
        keterangan,
        gambar
    FROM sub_mesin
    WHERE id = ?
    LIMIT 1
    "
);


if (!$stmt_get) {

    die(
        "Gagal menyiapkan query data sub mesin: " .
        mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $stmt_get,
    "i",
    $id
);


if (
    !mysqli_stmt_execute(
        $stmt_get
    )
) {

    $queryError =
        mysqli_stmt_error(
            $stmt_get
        );

    mysqli_stmt_close(
        $stmt_get
    );

    die(
        "Gagal mengambil data sub mesin: " .
        $queryError
    );
}


/*
 * Tidak menggunakan mysqli_stmt_get_result().
 * Ini lebih kompatibel dengan beberapa hosting.
 */
mysqli_stmt_bind_result(
    $stmt_get,
    $db_id,
    $db_id_mesin,
    $db_nama_sub_mesin,
    $db_serial_number,
    $db_keterangan,
    $db_gambar
);


$found =
    mysqli_stmt_fetch(
        $stmt_get
    );


mysqli_stmt_close(
    $stmt_get
);


/* =========================================================
   DATA TIDAK DITEMUKAN
========================================================= */

if (!$found) {

    header(
        "Location: index.php"
    );

    exit;
}


/* =========================================================
   NILAI AWAL FORM
========================================================= */

$val_id_mesin =
    (int)$db_id_mesin;


$val_nama_sub_mesin =
    $db_nama_sub_mesin ?? "";


$val_keterangan =
    $db_keterangan ?? "";


$nama_gambar =
    $db_gambar ?? "";


/* =========================================================
   PROSES FORM UPDATE
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    /* =====================================================
       AMBIL DATA FORM
    ====================================================== */

    $val_id_mesin =
        (int)(
            $_POST['id_mesin'] ?? 0
        );


    $val_nama_sub_mesin =
        trim(
            $_POST['nama_sub_mesin'] ?? ''
        );


    $val_keterangan =
        trim(
            $_POST['keterangan'] ?? ''
        );


    /*
     * Simpan nama foto lama.
     */
    $gambar_lama =
        $nama_gambar;


    /*
     * Default tetap menggunakan
     * foto lama.
     */
    $foto_baru =
        $gambar_lama;


    /*
     * Penanda apakah ada foto baru.
     */
    $upload_berhasil = false;


    /*
     * Lokasi foto baru.
     */
    $targetPath = "";


    /* =====================================================
       VALIDASI DATA
    ====================================================== */

    if (
        $val_id_mesin <= 0
    ) {

        $error =
            "Mesin Induk wajib dipilih.";

    } elseif (
        $val_nama_sub_mesin === ''
    ) {

        $error =
            "Nama Sub Mesin wajib diisi.";

    } elseif (
        strlen($val_nama_sub_mesin) > 100
    ) {

        $error =
            "Nama Sub Mesin maksimal 100 karakter.";
    }


    /* =====================================================
       CEK MESIN INDUK
    ====================================================== */

    if (
        empty($error)
    ) {

        $stmt_check =
            mysqli_prepare(
                $conn,
                "
                SELECT id
                FROM mesin
                WHERE id = ?
                LIMIT 1
                "
            );


        if (!$stmt_check) {

            $error =
                "Gagal memeriksa Mesin Induk: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt_check,
                "i",
                $val_id_mesin
            );


            if (
                !mysqli_stmt_execute(
                    $stmt_check
                )
            ) {

                $error =
                    "Gagal memeriksa Mesin Induk: " .
                    mysqli_stmt_error(
                        $stmt_check
                    );

            } else {

                mysqli_stmt_store_result(
                    $stmt_check
                );


                if (
                    mysqli_stmt_num_rows(
                        $stmt_check
                    ) <= 0
                ) {

                    $error =
                        "Mesin Induk yang dipilih tidak ditemukan.";
                }
            }


            mysqli_stmt_close(
                $stmt_check
            );
        }
    }


    /* =====================================================
       CEK UPLOAD FOTO
    ====================================================== */

    if (
        empty($error) &&
        isset($_FILES['gambar']) &&
        isset($_FILES['gambar']['error']) &&
        $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $uploadError =
            (int)$_FILES['gambar']['error'];


        /* =================================================
           ERROR UPLOAD
        ================================================= */

        if (
            $uploadError !== UPLOAD_ERR_OK
        ) {

            switch ($uploadError) {

                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:

                    $error =
                        "Ukuran foto terlalu besar! Maksimal 10MB.";

                    break;


                case UPLOAD_ERR_PARTIAL:

                    $error =
                        "Upload foto tidak selesai. Silakan coba lagi.";

                    break;


                case UPLOAD_ERR_NO_TMP_DIR:

                    $error =
                        "Folder temporary upload tidak tersedia.";

                    break;


                case UPLOAD_ERR_CANT_WRITE:

                    $error =
                        "Foto gagal ditulis ke server.";

                    break;


                case UPLOAD_ERR_EXTENSION:

                    $error =
                        "Upload foto dihentikan oleh ekstensi server.";

                    break;


                default:

                    $error =
                        "Terjadi kesalahan saat mengunggah foto.";
                    break;
            }
        }


        /* =================================================
           DATA FILE
        ================================================= */

        if (
            empty($error)
        ) {

            $fileTmpPath =
                $_FILES['gambar']['tmp_name'] ?? '';


            $fileName =
                $_FILES['gambar']['name'] ?? '';


            $fileSize =
                (int)(
                    $_FILES['gambar']['size'] ?? 0
                );


            if (
                empty($fileTmpPath) ||
                !is_uploaded_file(
                    $fileTmpPath
                )
            ) {

                $error =
                    "File foto upload tidak valid.";

            } elseif (
                $fileSize <= 0
            ) {

                $error =
                    "File foto kosong.";

            } elseif (
                $fileSize > $maxUploadSize
            ) {

                $error =
                    "Ukuran foto terlalu besar! Maksimal 10MB.";
            }
        }


        /* =================================================
           CEK EKSTENSI
        ================================================= */

        if (
            empty($error)
        ) {

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
                    "Format foto tidak valid! " .
                    "Gunakan JPG, JPEG, PNG, atau WEBP.";
            }
        }


        /* =================================================
           VALIDASI MIME
        ================================================= */

        if (
            empty($error)
        ) {

            if (
                !function_exists(
                    'finfo_open'
                )
            ) {

                $error =
                    "Fitur validasi MIME tidak tersedia di server.";

            } else {

                $finfo =
                    @finfo_open(
                        FILEINFO_MIME_TYPE
                    );


                if (!$finfo) {

                    $error =
                        "Validasi MIME foto gagal.";

                } else {

                    $mimeType =
                        @finfo_file(
                            $finfo,
                            $fileTmpPath
                        );


                    @finfo_close(
                        $finfo
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
                            "File yang diupload bukan gambar yang valid.";
                    }
                }
            }
        }


        /* =================================================
           VALIDASI GETIMAGESIZE
        ================================================= */

        if (
            empty($error)
        ) {

            $imageInfo =
                @getimagesize(
                    $fileTmpPath
                );


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
                    $imageInfo[2] ?? null;


                $expectedImageType = [
                    'image/jpeg' =>
                        IMAGETYPE_JPEG,

                    'image/png' =>
                        IMAGETYPE_PNG,

                    'image/webp' =>
                        IMAGETYPE_WEBP
                ];


                if (
                    !isset(
                        $expectedImageType[
                            $mimeType
                        ]
                    ) ||
                    $detectedImageType !==
                    $expectedImageType[
                        $mimeType
                    ]
                ) {

                    $error =
                        "Jenis gambar tidak sesuai dengan isi file.";
                }
            }
        }


        /* =================================================
           CEK GD
        ================================================= */

        if (
            empty($error)
        ) {

            if (
                !function_exists(
                    'imagecreatetruecolor'
                )
            ) {

                $error =
                    "Fitur GD PHP belum tersedia di server.";
            }
        }


        /* =================================================
           CEK FUNGSI GD SESUAI FORMAT
        ================================================= */

        if (
            empty($error)
        ) {

            $gdFunctionsAvailable =
                true;


            switch ($mimeType) {

                case 'image/jpeg':

                    if (
                        !function_exists(
                            'imagecreatefromjpeg'
                        ) ||
                        !function_exists(
                            'imagejpeg'
                        )
                    ) {

                        $gdFunctionsAvailable =
                            false;
                    }

                    break;


                case 'image/png':

                    if (
                        !function_exists(
                            'imagecreatefrompng'
                        ) ||
                        !function_exists(
                            'imagepng'
                        )
                    ) {

                        $gdFunctionsAvailable =
                            false;
                    }

                    break;


                case 'image/webp':

                    if (
                        !function_exists(
                            'imagecreatefromwebp'
                        ) ||
                        !function_exists(
                            'imagewebp'
                        )
                    ) {

                        $gdFunctionsAvailable =
                            false;
                    }

                    break;
            }


            if (
                !$gdFunctionsAvailable
            ) {

                $error =
                    "Format gambar tidak dapat diproses oleh GD di server.";
            }
        }


        /* =================================================
           BUAT FOLDER
        ================================================= */

        if (
            empty($error)
        ) {

            if (
                !is_dir(
                    $targetDir
                )
            ) {

                if (
                    !@mkdir(
                        $targetDir,
                        0755,
                        true
                    )
                ) {

                    $error =
                        "Folder uploads/sub_mesin tidak dapat dibuat.";
                }
            }
        }


        /* =================================================
           CEK WRITE PERMISSION
        ================================================= */

        if (
            empty($error)
        ) {

            if (
                !is_writable(
                    $targetDir
                )
            ) {

                $error =
                    "Folder uploads/sub_mesin tidak dapat ditulis oleh server.";
            }
        }


        /* =================================================
           PROSES GAMBAR
        ================================================= */

        if (
            empty($error)
        ) {

            $sourceImage =
                createImageResource(
                    $fileTmpPath,
                    $mimeType
                );


            if (!$sourceImage) {

                $error =
                    "Foto tidak dapat diproses oleh server.";

            } else {

                /*
                 * PNG transparansi.
                 */
                if (
                    $mimeType ===
                    'image/png'
                ) {

                    imagealphablending(
                        $sourceImage,
                        false
                    );


                    imagesavealpha(
                        $sourceImage,
                        true
                    );
                }


                /*
                 * Resize.
                 */
                $processedImage =
                    resizeImageIfNeeded(
                        $sourceImage,
                        $imageWidth,
                        $imageHeight,
                        $maxImageWidth,
                        $maxImageHeight
                    );


                if (!$processedImage) {

                    $error =
                        "Gagal melakukan resize foto.";

                } else {

                    /*
                     * Generate nama baru.
                     */
                    $foto_baru =
                        generateSafeImageName(
                            $mimeType
                        );


                    $targetPath =
                        $targetDir .
                        $foto_baru;


                    /*
                     * Simpan gambar.
                     */
                    $saveSuccess =
                        saveProcessedImage(
                            $processedImage,
                            $targetPath,
                            $mimeType,
                            $imageQuality
                        );


                    @imagedestroy(
                        $processedImage
                    );


                    if (!$saveSuccess) {

                        deleteUploadedFile(
                            $targetPath
                        );


                        $error =
                            "Gagal menyimpan foto hasil kompresi.";

                    } elseif (
                        !file_exists(
                            $targetPath
                        ) ||
                        filesize(
                            $targetPath
                        ) <= 0
                    ) {

                        deleteUploadedFile(
                            $targetPath
                        );


                        $error =
                            "Foto hasil upload tidak berhasil disimpan.";

                    } else {

                        /*
                         * Upload berhasil.
                         */
                        $upload_berhasil =
                            true;
                    }
                }
            }
        }
    }


    /* =====================================================
       UPDATE DATABASE
    ====================================================== */

    if (
        empty($error)
    ) {

        /*
         * Jika upload foto baru berhasil,
         * gunakan foto baru.
         *
         * Jika tidak,
         * tetap gunakan foto lama.
         */
        $gambar_update =
            $upload_berhasil
                ? $foto_baru
                : $gambar_lama;


        /* =================================================
           QUERY UPDATE
        ================================================= */

        $stmt_update =
            mysqli_prepare(
                $conn,
                "
                UPDATE sub_mesin
                SET
                    id_mesin = ?,
                    nama_sub_mesin = ?,
                    keterangan = ?,
                    gambar = ?
                WHERE id = ?
                "
            );


        /* =================================================
           QUERY GAGAL
        ================================================= */

        if (!$stmt_update) {

            /*
             * Hapus foto baru jika query gagal.
             */
            if (
                $upload_berhasil
            ) {

                deleteUploadedFile(
                    $targetPath
                );
            }


            $error =
                "Query update tidak dapat diproses: " .
                mysqli_error($conn);

        } else {

            /* =============================================
               BIND PARAMETER
            ============================================= */

            mysqli_stmt_bind_param(
                $stmt_update,
                "isssi",
                $val_id_mesin,
                $val_nama_sub_mesin,
                $val_keterangan,
                $gambar_update,
                $id
            );


            /* =============================================
               EXECUTE
            ============================================= */

            $execute =
                mysqli_stmt_execute(
                    $stmt_update
                );


            /* =============================================
               JIKA GAGAL
            ============================================= */

            if (!$execute) {

                $sqlError =
                    mysqli_stmt_error(
                        $stmt_update
                    );


                /*
                 * Hapus foto baru karena
                 * database tidak berhasil.
                 */
                if (
                    $upload_berhasil
                ) {

                    deleteUploadedFile(
                        $targetPath
                    );
                }


                $error =
                    "Gagal memperbarui data sub mesin: " .
                    $sqlError;


                mysqli_stmt_close(
                    $stmt_update
                );

            } else {

                /* =========================================
                   UPDATE BERHASIL
                ========================================= */

                mysqli_stmt_close(
                    $stmt_update
                );


                /*
                 * Hapus foto lama setelah database
                 * berhasil diperbarui.
                 */
                if (
                    $upload_berhasil &&
                    !empty($gambar_lama) &&
                    $gambar_lama !== $foto_baru
                ) {

                    $oldFile =
                        $targetDir .
                        basename(
                            $gambar_lama
                        );


                    deleteUploadedFile(
                        $oldFile
                    );
                }


                /*
                 * Redirect setelah berhasil.
                 */
                header(
                    "Location: index.php?updated=1"
                );

                exit;
            }
        }
    }
}


/* =========================================================
   AMBIL DAFTAR MESIN INDUK
========================================================= */

$q_mesin =
    mysqli_query(
        $conn,
        "
        SELECT
            id,
            nama_mesin,
            lokasi,
            serial_number
        FROM mesin
        ORDER BY nama_mesin ASC
        "
    );


if (!$q_mesin) {

    die(
        "Gagal mengambil daftar Mesin Induk: " .
        mysqli_error($conn)
    );
}


/* =========================================================
   FOTO LAMA
========================================================= */

$foto_lama_path =
    $targetDir .
    basename(
        $nama_gambar
    );


$ada_foto_lama =
    !empty($nama_gambar) &&
    file_exists(
        $foto_lama_path
    ) &&
    is_file(
        $foto_lama_path
    );


/* =========================================================
   HEADER
========================================================= */

include "../template/header.php";

?>


<style>

/* =========================================================
   PAGE
========================================================= */

.sub-edit-page {
    width: 100%;
}


/* =========================================================
   TOP HEADER
========================================================= */

.sub-edit-header {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    padding: 18px 20px;

    margin-bottom: 20px;

    box-shadow:
        0 3px 12px rgba(15, 23, 42, .04);
}


.sub-edit-back {

    width: 42px;

    height: 42px;

    flex-shrink: 0;

    border-radius: 10px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    background: #ffffff;

    border: 1px solid #dbe3ea;

    color: #475569;

    text-decoration: none;

    transition: .2s;
}


.sub-edit-back:hover {

    background: #005baa;

    border-color: #005baa;

    color: #ffffff;
}


.sub-edit-title {

    font-size: 24px;

    font-weight: 800;

    color: #172033;

    margin: 0;

    line-height: 1.3;
}


.sub-edit-subtitle {

    color: #64748b;

    font-size: 13px;

    margin-top: 3px;
}


/* =========================================================
   MAIN CARD
========================================================= */

.sub-edit-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    overflow: hidden;

    box-shadow:
        0 3px 12px rgba(15, 23, 42, .04);
}


.sub-edit-card-header {

    padding: 17px 20px;

    border-bottom: 1px solid #e5e7eb;

    background: #ffffff;
}


.sub-edit-card-title {

    color: #172033;

    font-size: 16px;

    font-weight: 700;

    margin: 0;
}


.sub-edit-card-subtitle {

    color: #94a3b8;

    font-size: 12px;

    margin-top: 3px;
}


.sub-edit-card-body {

    padding: 22px;
}


/* =========================================================
   FORM
========================================================= */

.sub-edit-label {

    display: block;

    color: #334155;

    font-size: 13px;

    font-weight: 700;

    margin-bottom: 7px;
}


.sub-edit-required {

    color: #dc2626;
}


.sub-edit-input,
.sub-edit-select,
.sub-edit-textarea {

    border: 1px solid #dbe3ea;

    border-radius: 9px;

    font-size: 13px;

    color: #334155;

    transition: .2s;
}


.sub-edit-input,
.sub-edit-select {

    min-height: 42px;
}


.sub-edit-textarea {

    resize: vertical;

    min-height: 110px;
}


.sub-edit-input:focus,
.sub-edit-select:focus,
.sub-edit-textarea:focus {

    border-color: #0076c8;

    box-shadow:
        0 0 0 3px rgba(0, 118, 200, .08);

    outline: none;
}


/* =========================================================
   MESIN INDUK INFO
========================================================= */

.sub-machine-info {

    margin-top: 8px;

    padding: 9px 11px;

    background: #f8fafc;

    border: 1px solid #e5e7eb;

    border-radius: 8px;

    font-size: 11px;

    color: #64748b;

    line-height: 1.5;
}


.sub-machine-info strong {

    color: #334155;
}


/* =========================================================
   PHOTO SECTION
========================================================= */

.sub-photo-current {

    margin-bottom: 12px;

    padding: 12px;

    background: #f8fafc;

    border: 1px solid #e5e7eb;

    border-radius: 12px;
}


.sub-photo-preview {

    width: 110px;

    height: 110px;

    border-radius: 10px;

    border: 1px solid #dbe3ea;

    background: #ffffff;

    overflow: hidden;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;
}


.sub-photo-preview img {

    width: 100%;

    height: 100%;

    object-fit: cover;
}


.sub-photo-file {

    font-size: 12px;

    font-weight: 700;

    color: #334155;

    word-break: break-word;
}


.sub-photo-description {

    font-size: 11px;

    color: #94a3b8;

    margin-top: 3px;

    line-height: 1.5;
}


.sub-no-photo {

    width: 110px;

    height: 110px;

    border-radius: 10px;

    background: #eef5ff;

    border: 1px solid #d8eaff;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    color: #005baa;

    flex-shrink: 0;
}


.sub-no-photo i {

    font-size: 30px;
}


.sub-no-photo span {

    font-size: 10px;

    margin-top: 4px;
}


/* =========================================================
   FILE INPUT
========================================================= */

.sub-edit-file {

    border: 1px solid #dbe3ea;

    border-radius: 9px;

    font-size: 12px;

    padding: 8px;
}


.sub-edit-file:focus {

    border-color: #0076c8;

    box-shadow:
        0 0 0 3px rgba(0, 118, 200, .08);
}


.sub-file-help {

    color: #94a3b8;

    font-size: 11px;

    margin-top: 5px;

    line-height: 1.5;
}


/* =========================================================
   NEW PHOTO PREVIEW
========================================================= */

.sub-new-photo-preview {

    display: none;

    margin-top: 10px;

    padding: 10px;

    background: #f8fbff;

    border: 1px dashed #93c5fd;

    border-radius: 10px;

    align-items: center;

    gap: 10px;
}


.sub-new-photo-preview.show {

    display: flex;
}


.sub-new-photo-preview img {

    width: 70px;

    height: 70px;

    object-fit: cover;

    border-radius: 8px;

    border: 1px solid #dbeafe;

    background: #ffffff;
}


.sub-new-photo-name {

    font-size: 11px;

    color: #64748b;

    word-break: break-all;

    margin-top: 2px;
}


/* =========================================================
   ALERT
========================================================= */

.sub-edit-alert {

    border-radius: 10px;

    font-size: 12px;

    margin-bottom: 20px;
}


/* =========================================================
   FORM FOOTER
========================================================= */

.sub-edit-footer {

    border-top: 1px solid #e5e7eb;

    margin-top: 22px;

    padding-top: 18px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12px;

    flex-wrap: wrap;
}


.sub-edit-actions {

    display: flex;

    align-items: center;

    gap: 8px;

    flex-wrap: wrap;
}


.sub-btn {

    min-height: 40px;

    border-radius: 9px;

    padding: 8px 16px;

    font-size: 13px;

    font-weight: 600;
}


.sub-btn-primary {

    background: linear-gradient(
        135deg,
        #005baa,
        #0076c8
    );

    border: none;

    color: #ffffff;
}


.sub-btn-primary:hover {

    color: #ffffff;

    transform: translateY(-1px);

    box-shadow:
        0 6px 16px rgba(0, 91, 170, .18);
}


.sub-btn-cancel {

    background: #ffffff;

    border: 1px solid #dbe3ea;

    color: #475569;
}


.sub-btn-cancel:hover {

    background: #f8fafc;

    color: #334155;
}


/* =========================================================
   INFO NOTE
========================================================= */

.sub-edit-note {

    display: flex;

    align-items: flex-start;

    gap: 8px;

    color: #64748b;

    font-size: 11px;

    line-height: 1.5;

    max-width: 480px;
}


.sub-edit-note i {

    color: #005baa;

    margin-top: 1px;
}


/* =========================================================
   RESPONSIVE TABLET
========================================================= */

@media (max-width: 992px) {

    .sub-edit-title {

        font-size: 22px;
    }


    .sub-edit-card-body {

        padding: 18px;
    }

}


/* =========================================================
   RESPONSIVE MOBILE
========================================================= */

@media (max-width: 768px) {

    .sub-edit-header {

        padding: 15px;

        border-radius: 13px;
    }


    .sub-edit-title {

        font-size: 20px;
    }


    .sub-edit-subtitle {

        font-size: 12px;
    }


    .sub-edit-back {

        width: 38px;

        height: 38px;
    }


    .sub-edit-card {

        border-radius: 13px;
    }


    .sub-edit-card-header {

        padding: 15px;
    }


    .sub-edit-card-body {

        padding: 15px;
    }


    .sub-photo-current {

        align-items: flex-start !important;

        flex-direction: column;
    }


    .sub-photo-preview,
    .sub-no-photo {

        width: 100%;

        max-width: 180px;

        height: 140px;
    }


    .sub-edit-footer {

        align-items: stretch;

        flex-direction: column;
    }


    .sub-edit-note {

        max-width: none;

        order: 2;
    }


    .sub-edit-actions {

        width: 100%;

        display: flex;
    }


    .sub-edit-actions .sub-btn {

        flex: 1;

        min-width: 120px;
    }

}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 480px) {

    .sub-edit-header {

        padding: 13px;
    }


    .sub-edit-title {

        font-size: 18px;
    }


    .sub-edit-card-body {

        padding: 13px;
    }


    .sub-edit-card-title {

        font-size: 15px;
    }


    .sub-edit-actions {

        flex-direction: column;
    }


    .sub-edit-actions .sub-btn {

        width: 100%;

        flex: none;
    }


    .sub-new-photo-preview {

        align-items: flex-start;
    }

}

</style>


<div class="container-fluid p-0 sub-edit-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="sub-edit-header">

        <div class="d-flex align-items-center gap-3">

            <a
                href="index.php"
                class="sub-edit-back"
                title="Kembali"
            >

                <i class="bi bi-arrow-left"></i>

            </a>


            <div>

                <h2 class="sub-edit-title">

                    Edit Sub Mesin

                </h2>

                <div class="sub-edit-subtitle">

                    Perbarui informasi sub mesin yang terdaftar
                    di dalam sistem.

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         MAIN CARD
    ====================================================== -->

    <div class="sub-edit-card">


        <!-- =================================================
             CARD HEADER
        ================================================== -->

        <div class="sub-edit-card-header">

            <div>

                <h5 class="sub-edit-card-title">

                    <i class="bi bi-pencil-square text-primary me-2"></i>

                    Form Edit Sub Mesin

                </h5>

                <div class="sub-edit-card-subtitle">

                    Periksa kembali data sebelum menyimpan perubahan.

                </div>

            </div>

        </div>


        <!-- =================================================
             CARD BODY
        ================================================== -->

        <div class="sub-edit-card-body">


            <!-- =================================================
                 ERROR
            ================================================== -->

            <?php if (!empty($error)): ?>

                <div
                    class="alert alert-danger border-0 sub-edit-alert d-flex align-items-start gap-2"
                    role="alert"
                >

                    <i class="bi bi-exclamation-triangle-fill mt-1"></i>

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
                id="formEditSubMesin"
                action=""
            >


                <div class="row g-4">


                    <!-- =================================================
                         MESIN INDUK
                    ================================================== -->

                    <div class="col-lg-6">

                        <label class="sub-edit-label">

                            Mesin Induk

                            <span class="sub-edit-required">*</span>

                        </label>


                        <select
                            name="id_mesin"
                            class="form-select sub-edit-select"
                            required
                        >

                            <option value="">

                                -- Pilih Mesin Induk --

                            </option>


                            <?php if ($q_mesin): ?>

                                <?php while (
                                    $m =
                                    mysqli_fetch_assoc(
                                        $q_mesin
                                    )
                                ): ?>

                                    <option
                                        value="<?= (int)$m['id'] ?>"
                                        <?= (
                                            $val_id_mesin ==
                                            (int)$m['id']
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= e(
                                            $m['nama_mesin'] ?? '-'
                                        ) ?>


                                        <?php if (
                                            !empty(
                                                $m['serial_number']
                                            )
                                        ): ?>

                                            -
                                            <?= e(
                                                $m['serial_number']
                                            ) ?>

                                        <?php endif; ?>

                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>


                        <div class="sub-machine-info">

                            <i class="bi bi-info-circle me-1"></i>

                            Pastikan sub mesin ditempatkan pada
                            <strong>mesin induk yang benar</strong>.

                        </div>

                    </div>


                    <!-- =================================================
                         NAMA SUB MESIN
                    ================================================== -->

                    <div class="col-lg-6">

                        <label class="sub-edit-label">

                            Nama Sub Mesin

                            <span class="sub-edit-required">*</span>

                        </label>


                        <input
                            type="text"
                            name="nama_sub_mesin"
                            class="form-control sub-edit-input"
                            value="<?= e(
                                $val_nama_sub_mesin
                            ) ?>"
                            placeholder="Contoh: Conveyor Feeder"
                            maxlength="100"
                            required
                        >


                        <div class="form-text">

                            Gunakan nama yang mudah dikenali
                            oleh teknisi atau operator.

                        </div>

                    </div>


                    <!-- =================================================
                         FOTO
                    ================================================== -->

                    <div class="col-lg-6">

                        <label class="sub-edit-label">

                            Foto Sub Mesin

                        </label>


                        <!-- FOTO SAAT INI -->

                        <div class="sub-photo-current">

                            <div class="d-flex align-items-center gap-3">


                                <?php if ($ada_foto_lama): ?>

                                    <div class="sub-photo-preview">

                                        <img
                                            src="<?= e(
                                                $foto_lama_path
                                            ) ?>"
                                            alt="Foto Sub Mesin"
                                        >

                                    </div>

                                <?php else: ?>

                                    <div class="sub-no-photo">

                                        <i class="bi bi-image"></i>

                                        <span>
                                            Tidak ada foto
                                        </span>

                                    </div>

                                <?php endif; ?>


                                <div>

                                    <div class="sub-photo-file">

                                        <?php if ($ada_foto_lama): ?>

                                            Foto Saat Ini

                                        <?php else: ?>

                                            Belum Ada Foto

                                        <?php endif; ?>

                                    </div>


                                    <?php if ($ada_foto_lama): ?>

                                        <div class="sub-photo-description">

                                            <?= e(
                                                $nama_gambar
                                            ) ?>

                                        </div>

                                    <?php endif; ?>


                                    <div class="sub-photo-description mt-2">

                                        Pilih foto baru jika ingin
                                        mengganti foto saat ini.

                                    </div>

                                </div>

                            </div>

                        </div>


                        <!-- INPUT FOTO -->

                        <input
                            type="file"
                            name="gambar"
                            id="gambar"
                            class="form-control sub-edit-file"
                            accept="image/jpeg,image/png,image/webp"
                        >


                        <div class="sub-file-help">

                            <i class="bi bi-info-circle me-1"></i>

                            Format yang diperbolehkan:
                            JPG, JPEG, PNG, WEBP.
                            Maksimal ukuran 10MB.
                            Gambar otomatis disesuaikan maksimal
                            1600 × 1600 px dan dikompresi dengan
                            kualitas 82.

                        </div>


                        <!-- PREVIEW FOTO BARU -->

                        <div
                            class="sub-new-photo-preview"
                            id="subNewPhotoPreview"
                        >

                            <img
                                src=""
                                alt="Preview Foto Baru"
                                id="subNewPhotoImage"
                            >


                            <div>

                                <div
                                    class="fw-semibold small text-dark"
                                >

                                    Preview Foto Baru

                                </div>


                                <div
                                    class="sub-new-photo-name"
                                    id="subNewPhotoName"
                                ></div>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         KETERANGAN
                    ================================================== -->

                    <div class="col-lg-6">

                        <label class="sub-edit-label">

                            Keterangan / Deskripsi

                        </label>


                        <textarea
                            name="keterangan"
                            class="form-control sub-edit-textarea"
                            rows="5"
                            maxlength="1000"
                            placeholder="Masukkan fungsi, spesifikasi, atau catatan mengenai sub mesin..."
                        ><?= e(
                            $val_keterangan
                        ) ?></textarea>


                        <div class="form-text">

                            Jelaskan fungsi atau informasi tambahan
                            mengenai sub mesin jika diperlukan.

                        </div>

                    </div>


                </div>


                <!-- =================================================
                     FOOTER FORM
                ================================================== -->

                <div class="sub-edit-footer">


                    <div class="sub-edit-note">

                        <i class="bi bi-shield-check"></i>

                        <span>

                            Data akan diperbarui setelah tombol
                            <strong>Perbarui Data</strong> ditekan.
                            Foto lama akan tetap digunakan jika
                            tidak memilih foto baru.

                        </span>

                    </div>


                    <div class="sub-edit-actions">


                        <a
                            href="index.php"
                            class="btn sub-btn sub-btn-cancel"
                        >

                            <i class="bi bi-x-lg me-1"></i>

                            Batal

                        </a>


                        <button
                            type="submit"
                            name="update"
                            value="1"
                            id="btnUpdate"
                            class="btn sub-btn sub-btn-primary"
                        >

                            <i class="bi bi-check-lg me-1"></i>

                            Perbarui Data

                        </button>

                    </div>

                </div>


            </form>

        </div>

    </div>

</div>


<script>

/* =========================================================
   PREVIEW FOTO + VALIDASI FRONTEND
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const inputGambar =
            document.getElementById(
                "gambar"
            );


        const previewWrapper =
            document.getElementById(
                "subNewPhotoPreview"
            );


        const previewImage =
            document.getElementById(
                "subNewPhotoImage"
            );


        const previewName =
            document.getElementById(
                "subNewPhotoName"
            );


        if (
            !inputGambar ||
            !previewWrapper ||
            !previewImage ||
            !previewName
        ) {

            return;
        }


        inputGambar.addEventListener(
            "change",
            function () {

                const file =
                    this.files &&
                    this.files[0];


                /* =========================================
                   TIDAK ADA FILE
                ========================================== */

                if (!file) {

                    previewWrapper.classList.remove(
                        "show"
                    );


                    previewImage.src = "";

                    previewName.textContent = "";

                    return;
                }


                /* =========================================
                   VALIDASI UKURAN
                ========================================== */

                if (
                    file.size >
                    10 * 1024 * 1024
                ) {

                    alert(
                        "Ukuran foto terlalu besar! " +
                        "Maksimal 10MB."
                    );


                    this.value = "";


                    previewWrapper.classList.remove(
                        "show"
                    );


                    previewImage.src = "";

                    previewName.textContent = "";

                    return;
                }


                /* =========================================
                   VALIDASI MIME
                ========================================== */

                const allowedTypes = [
                    "image/jpeg",
                    "image/png",
                    "image/webp"
                ];


                if (
                    !allowedTypes.includes(
                        file.type
                    )
                ) {

                    alert(
                        "Format foto tidak valid! " +
                        "Gunakan JPG, JPEG, PNG, atau WEBP."
                    );


                    this.value = "";


                    previewWrapper.classList.remove(
                        "show"
                    );


                    previewImage.src = "";

                    previewName.textContent = "";

                    return;
                }


                /* =========================================
                   PREVIEW
                ========================================== */

                const reader =
                    new FileReader();


                reader.onload =
                    function (event) {

                        previewImage.src =
                            event.target.result;


                        previewName.textContent =
                            file.name;


                        previewWrapper.classList.add(
                            "show"
                        );
                    };


                reader.onerror =
                    function () {

                        inputGambar.value = "";


                        previewWrapper.classList.remove(
                            "show"
                        );


                        previewImage.src = "";

                        previewName.textContent = "";


                        alert(
                            "Preview foto tidak dapat ditampilkan."
                        );
                    };


                reader.readAsDataURL(
                    file
                );

            }
        );

    }
);

</script>


<?php

include "../template/footer.php";

?>