<?php
require_once __DIR__ . '/../template/auth.php';
require_user_role();


/*
|--------------------------------------------------------------------------
| TAMBAH KOMPONEN
|--------------------------------------------------------------------------
| File : komponen/tambah.php
|--------------------------------------------------------------------------
*/

mysqli_report(MYSQLI_REPORT_OFF);

include "../koneksi.php";

$error   = "";
$success = "";


/* =========================================================
   CEK KONEKSI DATABASE
========================================================= */

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    die("Koneksi database tidak tersedia. Periksa file ../koneksi.php");
}


/* =========================================================
   KONFIGURASI UPLOAD
========================================================= */

/*
 * Batas file yang diperbolehkan oleh aplikasi:
 * 50 MB
 *
 * Catatan:
 * upload_max_filesize dan post_max_size di hosting
 * tetap harus mendukung ukuran tersebut.
 */

$maxUploadSize = 50 * 1024 * 1024;

$maxImageWidth  = 1600;
$maxImageHeight = 1600;

/*
 * Kualitas JPEG / WEBP setelah resize.
 */
$imageQuality = 82;

/*
 * Resolusi sumber maksimum.
 *
 * Bukan batas ukuran file.
 * Ini untuk mencegah gambar dengan resolusi ekstrem
 * menghabiskan RAM server ketika diproses GD.
 */
$maxSourceDimension = 10000;

$uploadDir = __DIR__ . '/../uploads/komponen/';


/* =========================================================
   HELPER ESCAPE HTML
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
   HELPER FORMAT UKURAN
========================================================= */

function formatUploadSize($bytes)
{
    $bytes = (int)$bytes;

    if ($bytes >= 1024 * 1024 * 1024) {
        return number_format(
            $bytes / (1024 * 1024 * 1024),
            2,
            ',',
            '.'
        ) . ' GB';
    }

    if ($bytes >= 1024 * 1024) {
        return number_format(
            $bytes / (1024 * 1024),
            2,
            ',',
            '.'
        ) . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format(
            $bytes / 1024,
            2,
            ',',
            '.'
        ) . ' KB';
    }

    return $bytes . ' byte';
}


/* =========================================================
   HELPER PARSE PHP INI SIZE
========================================================= */

function parseIniBytes($value)
{
    $value = trim((string)$value);

    if ($value === '') {
        return 0;
    }

    if ($value === '-1') {
        return -1;
    }

    $last = strtolower(
        substr($value, -1)
    );

    $number = (float)$value;

    switch ($last) {

        case 'g':
            $number *= 1024;
            /* no break */

        case 'm':
            $number *= 1024;
            /* no break */

        case 'k':
            $number *= 1024;
            break;
    }

    return (int)round($number);
}


/* =========================================================
   HELPER CEK MEMORY LIMIT
========================================================= */

function getMemoryLimitBytes()
{
    if (!function_exists('ini_get')) {
        return 0;
    }

    $value = ini_get('memory_limit');

    if ($value === false || $value === '') {
        return 0;
    }

    return parseIniBytes($value);
}


/* =========================================================
   HELPER BUAT NAMA FILE
========================================================= */

function generateComponentFileName($extension)
{
    try {

        $randomName = bin2hex(
            random_bytes(12)
        );

    } catch (Exception $e) {

        $randomName = str_replace(
            '.',
            '',
            uniqid(
                '',
                true
            )
        );
    }

    return
        'KMP_' .
        date('YmdHis') .
        '_' .
        $randomName .
        '.' .
        $extension;
}


/* =========================================================
   UPLOAD GAMBAR KOMPONEN
========================================================= */

function uploadKomponenImage(
    $file,
    $targetDir,
    $maxUploadSize,
    $maxImageWidth,
    $maxImageHeight,
    $imageQuality,
    $maxSourceDimension
) {

    /* =====================================================
       CEK FILE
    ===================================================== */

    if (
        !isset($file) ||
        !is_array($file) ||
        !isset($file['error'])
    ) {

        return [
            'success'  => true,
            'filename' => ''
        ];
    }


    /* =====================================================
       TIDAK ADA FILE
    ===================================================== */

    if (
        (int)$file['error'] === UPLOAD_ERR_NO_FILE
    ) {

        return [
            'success'  => true,
            'filename' => ''
        ];
    }


    /* =====================================================
       ERROR UPLOAD PHP
    ===================================================== */

    if (
        (int)$file['error'] !== UPLOAD_ERR_OK
    ) {

        switch ((int)$file['error']) {

            case UPLOAD_ERR_INI_SIZE:

                $message =
                    'Ukuran gambar melebihi batas upload server. ' .
                    'Periksa upload_max_filesize pada hosting. ' .
                    'Batas aplikasi adalah 50 MB.';

                break;


            case UPLOAD_ERR_FORM_SIZE:

                $message =
                    'Ukuran gambar melebihi batas form. ' .
                    'Maksimal 50 MB.';

                break;


            case UPLOAD_ERR_PARTIAL:

                $message =
                    'Upload gambar tidak selesai. ' .
                    'Koneksi mungkin terputus. Silakan coba lagi.';

                break;


            case UPLOAD_ERR_NO_TMP_DIR:

                $message =
                    'Folder temporary upload server tidak tersedia. ' .
                    'Hubungi administrator hosting.';

                break;


            case UPLOAD_ERR_CANT_WRITE:

                $message =
                    'Server gagal menulis file upload. ' .
                    'Periksa permission folder uploads/komponen/.';

                break;


            case UPLOAD_ERR_EXTENSION:

                $message =
                    'Upload gambar dihentikan oleh ekstensi PHP di server.';

                break;


            default:

                $message =
                    'Terjadi kesalahan saat mengunggah gambar. ' .
                    'Kode error upload: ' .
                    (int)$file['error'];

                break;
        }

        return [
            'success'  => false,
            'filename' => '',
            'error'    => $message
        ];
    }


    /* =====================================================
       DATA FILE
    ===================================================== */

    $tmpFile =
        isset($file['tmp_name'])
            ? (string)$file['tmp_name']
            : '';

    $fileName =
        isset($file['name'])
            ? (string)$file['name']
            : '';

    $fileSize =
        isset($file['size'])
            ? (int)$file['size']
            : 0;


    /* =====================================================
       CEK UKURAN FILE
    ===================================================== */

    if ($fileSize <= 0) {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'File gambar kosong atau tidak valid.'
        ];
    }


    if ($fileSize > $maxUploadSize) {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'Ukuran gambar maksimal 50 MB. ' .
                'Ukuran file Anda: ' .
                formatUploadSize($fileSize) .
                '.'
        ];
    }


    /* =====================================================
       CEK TEMP FILE
    ===================================================== */

    if ($tmpFile === '') {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'File upload tidak memiliki file temporary.'
        ];
    }


    if (!file_exists($tmpFile)) {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'File upload temporary tidak ditemukan. ' .
                'Silakan pilih foto kembali.'
        ];
    }


    if (!is_uploaded_file($tmpFile)) {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'File upload tidak valid. ' .
                'Silakan pilih foto kembali lalu coba lagi.'
        ];
    }


    /* =====================================================
       EXTENSION
    ===================================================== */

    $extension = strtolower(
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
            $extension,
            $allowedExtensions,
            true
        )
    ) {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'Format gambar harus JPG, JPEG, PNG, atau WEBP.'
        ];
    }


    /* =====================================================
       MIME TYPE
    ===================================================== */

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    $mimeType = '';


    /*
     * finfo digunakan jika tersedia.
     * Jika tidak tersedia, kita tidak langsung menggagalkan upload.
     * Validasi getimagesize() tetap dilakukan.
     */

    if (function_exists('finfo_open')) {

        $finfo = @finfo_open(
            FILEINFO_MIME_TYPE
        );

        if ($finfo) {

            $detectedMime =
                @finfo_file(
                    $finfo,
                    $tmpFile
                );

            if (
                is_string($detectedMime)
            ) {
                $mimeType = trim(
                    $detectedMime
                );
            }

            @finfo_close(
                $finfo
            );
        }
    }


    if (
        $mimeType !== '' &&
        !isset($allowedMimes[$mimeType])
    ) {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'File yang diupload bukan gambar JPG, PNG, atau WEBP yang valid.'
        ];
    }


    if (
        $mimeType !== '' &&
        isset($allowedMimes[$mimeType])
    ) {

        $extension =
            $allowedMimes[$mimeType];
    }


    /* =====================================================
       VALIDASI GAMBAR
    ===================================================== */

    if (!function_exists('getimagesize')) {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'Server tidak mendukung pemeriksaan gambar (getimagesize). ' .
                'Hubungi administrator hosting.'
        ];
    }


    $imageInfo =
        @getimagesize(
            $tmpFile
        );


    if (!$imageInfo) {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'File yang diupload bukan gambar yang valid.'
        ];
    }


    $originalWidth =
        isset($imageInfo[0])
            ? (int)$imageInfo[0]
            : 0;

    $originalHeight =
        isset($imageInfo[1])
            ? (int)$imageInfo[1]
            : 0;


    if (
        $originalWidth <= 0 ||
        $originalHeight <= 0
    ) {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'Ukuran gambar tidak valid.'
        ];
    }


    /* =====================================================
       CEK TIPE GAMBAR BERDASARKAN GETIMAGESIZE
    ===================================================== */

    $detectedImageMime = '';

    if (
        isset($imageInfo['mime']) &&
        is_string($imageInfo['mime'])
    ) {

        $detectedImageMime =
            strtolower(
                trim(
                    $imageInfo['mime']
                )
            );
    }


    if (
        $detectedImageMime !== '' &&
        !isset($allowedMimes[$detectedImageMime])
    ) {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'Format file gambar tidak didukung. ' .
                'Gunakan JPG, JPEG, PNG, atau WEBP.'
        ];
    }


    /*
     * Gunakan MIME sebenarnya sebagai acuan extension.
     */
    if (
        $detectedImageMime !== '' &&
        isset($allowedMimes[$detectedImageMime])
    ) {

        $extension =
            $allowedMimes[
                $detectedImageMime
            ];
    }


    /* =====================================================
       BATASI RESOLUSI SUMBER
    ===================================================== */

    if (
        $originalWidth > $maxSourceDimension ||
        $originalHeight > $maxSourceDimension
    ) {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'Resolusi gambar terlalu besar. ' .
                'Maksimal dimensi sumber adalah ' .
                $maxSourceDimension .
                ' × ' .
                $maxSourceDimension .
                ' px.'
        ];
    }


    /* =====================================================
       FOLDER UPLOAD
    ===================================================== */

    if (!is_dir($targetDir)) {

        if (
            !@mkdir(
                $targetDir,
                0755,
                true
            )
        ) {

            return [
                'success'  => false,
                'filename' => '',
                'error' =>
                    'Folder uploads/komponen tidak dapat dibuat.'
            ];
        }
    }


    if (!is_writable($targetDir)) {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'Folder uploads/komponen tidak dapat ditulis oleh server.'
        ];
    }


    /* =====================================================
       NAMA FILE BARU
    ===================================================== */

    $newFileName =
        generateComponentFileName(
            $extension
        );


    $targetFile =
        rtrim(
            $targetDir,
            '/\\'
        ) .
        DIRECTORY_SEPARATOR .
        $newFileName;


    /* =====================================================
       STRATEGI PENTING:
       GAMBAR SUDAH KECIL → LANGSUNG PINDAHKAN
    ===================================================== */

    $needResize =
        (
            $originalWidth > $maxImageWidth ||
            $originalHeight > $maxImageHeight
        );


    if (!$needResize) {

        /*
         * Tidak menggunakan GD.
         *
         * Ini membuat upload foto 50 MB yang kebetulan
         * memiliki dimensi kecil menjadi jauh lebih ringan.
         */

        if (
            @move_uploaded_file(
                $tmpFile,
                $targetFile
            )
        ) {

            @chmod(
                $targetFile,
                0644
            );

            return [
                'success'  => true,
                'filename' => $newFileName
            ];
        }


        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'Server gagal menyimpan gambar. ' .
                'Pastikan folder uploads/komponen dapat ditulis.'
        ];
    }


    /* =====================================================
       CEK GD UNTUK RESIZE
    ===================================================== */

    if (
        !function_exists('imagecreatetruecolor') ||
        !function_exists('imagecopyresampled')
    ) {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'Foto memiliki resolusi lebih besar dari ' .
                $maxImageWidth .
                ' × ' .
                $maxImageHeight .
                ' px, tetapi server tidak menyediakan GD untuk melakukan resize. ' .
                'Silakan gunakan foto dengan resolusi lebih kecil.'
        ];
    }


    /* =====================================================
       CEK MEMORY SEBELUM GD
    ===================================================== */

    /*
     * Perkiraan kasar memory yang dibutuhkan GD.
     *
     * Tidak harus tepat 100%, tujuannya untuk mencegah
     * proses resize gambar besar membuat PHP mati.
     */

    $pixelCount =
        (float)$originalWidth *
        (float)$originalHeight;


    $estimatedMemory =
        (int)ceil(
            $pixelCount * 8
        );


    $currentMemory =
        function_exists('memory_get_usage')
            ? (int)memory_get_usage(true)
            : 0;


    $memoryLimit =
        getMemoryLimitBytes();


    if (
        $memoryLimit > 0 &&
        $memoryLimit !== -1
    ) {

        $availableMemory =
            $memoryLimit -
            $currentMemory;


        /*
         * Sisakan ruang sekitar 25%.
         */
        $safeMemory =
            (int)floor(
                $availableMemory * 0.75
            );


        if (
            $estimatedMemory >
            $safeMemory
        ) {

            return [
                'success'  => false,
                'filename' => '',
                'error' =>
                    'Resolusi foto terlalu besar untuk diproses oleh memory server. ' .
                    'Silakan gunakan foto dengan resolusi lebih kecil dari ' .
                    $originalWidth .
                    ' × ' .
                    $originalHeight .
                    ' px.'
            ];
        }
    }


    /* =====================================================
       SOURCE IMAGE
    ===================================================== */

    $sourceImage = false;


    switch ($extension) {

        case 'jpg':
        case 'jpeg':

            if (
                function_exists(
                    'imagecreatefromjpeg'
                )
            ) {

                $sourceImage =
                    @imagecreatefromjpeg(
                        $tmpFile
                    );
            }

            break;


        case 'png':

            if (
                function_exists(
                    'imagecreatefrompng'
                )
            ) {

                $sourceImage =
                    @imagecreatefrompng(
                        $tmpFile
                    );
            }

            break;


        case 'webp':

            if (
                function_exists(
                    'imagecreatefromwebp'
                )
            ) {

                $sourceImage =
                    @imagecreatefromwebp(
                        $tmpFile
                    );
            }

            break;
    }


    if (!$sourceImage) {

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'Server tidak dapat memproses gambar untuk resize. ' .
                'Pastikan GD dan format ' .
                strtoupper($extension) .
                ' didukung oleh hosting.'
        ];
    }


    /* =====================================================
       UKURAN BARU
    ===================================================== */

    $newWidth =
        $originalWidth;

    $newHeight =
        $originalHeight;


    if (
        $originalWidth > $maxImageWidth ||
        $originalHeight > $maxImageHeight
    ) {

        $ratio =
            min(
                $maxImageWidth / $originalWidth,
                $maxImageHeight / $originalHeight
            );


        $newWidth =
            max(
                1,
                (int)round(
                    $originalWidth * $ratio
                )
            );


        $newHeight =
            max(
                1,
                (int)round(
                    $originalHeight * $ratio
                )
            );
    }


    /* =====================================================
       CANVAS
    ===================================================== */

    $destinationImage =
        @imagecreatetruecolor(
            $newWidth,
            $newHeight
        );


    if (!$destinationImage) {

        @imagedestroy(
            $sourceImage
        );

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'Server gagal menyiapkan gambar. ' .
                'Memory server mungkin tidak mencukupi.'
        ];
    }


    /* =====================================================
       TRANSPARANSI PNG / WEBP
    ===================================================== */

    if (
        $extension === 'png' ||
        $extension === 'webp'
    ) {

        @imagealphablending(
            $destinationImage,
            false
        );


        @imagesavealpha(
            $destinationImage,
            true
        );


        $transparent =
            @imagecolorallocatealpha(
                $destinationImage,
                255,
                255,
                255,
                127
            );


        if (
            $transparent !== false
        ) {

            @imagefilledrectangle(
                $destinationImage,
                0,
                0,
                $newWidth,
                $newHeight,
                $transparent
            );
        }
    }


    /*
     * JPEG tidak mempunyai transparansi.
     * Isi background putih.
     */

    if (
        $extension === 'jpg' ||
        $extension === 'jpeg'
    ) {

        $white =
            @imagecolorallocate(
                $destinationImage,
                255,
                255,
                255
            );


        if ($white !== false) {

            @imagefilledrectangle(
                $destinationImage,
                0,
                0,
                $newWidth,
                $newHeight,
                $white
            );
        }
    }


    /* =====================================================
       RESIZE
    ===================================================== */

    $resizeSuccess =
        @imagecopyresampled(
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


    if (!$resizeSuccess) {

        @imagedestroy(
            $sourceImage
        );

        @imagedestroy(
            $destinationImage
        );

        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'Gagal melakukan resize gambar.'
        ];
    }


    /* =====================================================
       SIMPAN HASIL
    ===================================================== */

    $saveSuccess = false;


    switch ($extension) {

        case 'jpg':
        case 'jpeg':

            if (
                function_exists('imagejpeg')
            ) {

                $saveSuccess =
                    @imagejpeg(
                        $destinationImage,
                        $targetFile,
                        $imageQuality
                    );
            }

            break;


        case 'png':

            if (
                function_exists('imagepng')
            ) {

                /*
                 * Compression level 6.
                 * Tidak terlalu berat dan kualitas tetap baik.
                 */

                $saveSuccess =
                    @imagepng(
                        $destinationImage,
                        $targetFile,
                        6
                    );
            }

            break;


        case 'webp':

            if (
                function_exists('imagewebp')
            ) {

                $saveSuccess =
                    @imagewebp(
                        $destinationImage,
                        $targetFile,
                        $imageQuality
                    );

            } else {

                @imagedestroy(
                    $sourceImage
                );

                @imagedestroy(
                    $destinationImage
                );

                return [
                    'success'  => false,
                    'filename' => '',
                    'error' =>
                        'Server belum mendukung pemrosesan WEBP.'
                ];
            }

            break;
    }


    /* =====================================================
       BERSIHKAN MEMORY
    ===================================================== */

    @imagedestroy(
        $sourceImage
    );

    @imagedestroy(
        $destinationImage
    );


    /* =====================================================
       CEK HASIL SAVE
    ===================================================== */

    if (!$saveSuccess) {

        if (
            file_exists(
                $targetFile
            )
        ) {

            @unlink(
                $targetFile
            );
        }


        return [
            'success'  => false,
            'filename' => '',
            'error' =>
                'Gagal menyimpan gambar komponen ke server.'
        ];
    }


    @chmod(
        $targetFile,
        0644
    );


    return [
        'success'  => true,
        'filename' => $newFileName
    ];
}


/* =========================================================
   NILAI FORM
========================================================= */

$serial_number =
    trim(
        $_POST['serial_number'] ?? ''
    );

$nama_bagian =
    trim(
        $_POST['nama_bagian'] ?? ''
    );

$jenis_komponen =
    trim(
        $_POST['jenis_komponen'] ?? ''
    );

$brand =
    trim(
        $_POST['brand'] ?? ''
    );

$tipe =
    trim(
        $_POST['tipe'] ?? ''
    );

$part_number =
    trim(
        $_POST['part_number'] ?? ''
    );

$daya =
    trim(
        $_POST['daya'] ?? ''
    );

$io_address =
    trim(
        $_POST['io_address'] ?? ''
    );

$ip_address =
    trim(
        $_POST['ip_address'] ?? ''
    );

$input_voltage =
    trim(
        $_POST['input_voltage'] ?? ''
    );

$frekuensi_input =
    trim(
        $_POST['frekuensi_input'] ?? ''
    );

$arus_input =
    trim(
        $_POST['arus_input'] ?? ''
    );

$output =
    trim(
        $_POST['output'] ?? ''
    );

$frekuensi_output =
    trim(
        $_POST['frekuensi_output'] ?? ''
    );

$ip_rating =
    trim(
        $_POST['ip_rating'] ?? ''
    );

$kondisi =
    trim(
        $_POST['kondisi'] ?? 'Baik'
    );

$keterangan =
    trim(
        $_POST['keterangan'] ?? ''
    );

$lokasi_post =
    trim(
        $_POST['lokasi'] ?? ''
    );


/* =========================================================
   ID HIERARCHY
========================================================= */

$id_area =
    (
        isset($_POST['id_area']) &&
        $_POST['id_area'] !== ''
    )
        ? (int)$_POST['id_area']
        : 0;


$id_jenis_mesin =
    (
        isset($_POST['id_jenis_mesin']) &&
        $_POST['id_jenis_mesin'] !== ''
    )
        ? (int)$_POST['id_jenis_mesin']
        : 0;


$id_mesin =
    (
        isset($_POST['id_mesin']) &&
        $_POST['id_mesin'] !== ''
    )
        ? (int)$_POST['id_mesin']
        : 0;


$id_sub_mesin =
    (
        isset($_POST['id_sub_mesin']) &&
        $_POST['id_sub_mesin'] !== ''
    )
        ? (int)$_POST['id_sub_mesin']
        : 0;


/* =========================================================
   VARIABEL
========================================================= */

$lokasi_str    = '';
$mesin_str     = '';
$sub_mesin_str = '';

$nama_gambar   = '';
$uploadedImage = '';

$newComponentId = 0;


/* =========================================================
   PROSES SIMPAN
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    try {

        /* =====================================================
           CEK CONTENT LENGTH
        ===================================================== */

        if (
            isset($_SERVER['CONTENT_LENGTH'])
        ) {

            $contentLength =
                (int)$_SERVER['CONTENT_LENGTH'];


            /*
             * Beri toleransi untuk data form selain file.
             */
            $absolutePostLimit =
                60 * 1024 * 1024;


            if (
                $contentLength >
                $absolutePostLimit
            ) {

                throw new Exception(
                    'Data upload terlalu besar. ' .
                    'Maksimal ukuran foto adalah 50 MB.'
                );
            }
        }


        /* =====================================================
           VALIDASI FORM
        ===================================================== */

        if ($nama_bagian === '') {

            throw new Exception(
                'Nama Komponen wajib diisi.'
            );
        }


        if ($id_area <= 0) {

            throw new Exception(
                'Area / Bagian wajib dipilih.'
            );
        }


        if ($id_jenis_mesin <= 0) {

            throw new Exception(
                'Jenis Mesin wajib dipilih.'
            );
        }


        if ($id_mesin <= 0) {

            throw new Exception(
                'Mesin Induk wajib dipilih.'
            );
        }


        if ($id_sub_mesin <= 0) {

            throw new Exception(
                'Sub Mesin wajib dipilih.'
            );
        }


        $allowedKondisi = [
            'Baik',
            'Perlu Pemeriksaan',
            'Dalam Perbaikan'
        ];


        if (
            !in_array(
                $kondisi,
                $allowedKondisi,
                true
            )
        ) {

            throw new Exception(
                'Kondisi komponen tidak valid.'
            );
        }


        /* =====================================================
           CEK KONEKSI
        ===================================================== */

        if (!mysqli_ping($conn)) {

            throw new Exception(
                'Koneksi database terputus.'
            );
        }


        /* =====================================================
           CEK HIERARCHY
        ===================================================== */

        $sqlHierarchy = "
            SELECT
                sm.id AS sub_id,
                sm.nama_sub_mesin AS sub_nama,

                m.id AS mesin_id,
                m.nama_mesin AS mesin_nama,
                m.lokasi AS mesin_lokasi,

                jm.id AS jenis_id,
                jm.nama_jenis_mesin AS jenis_nama,

                a.id AS area_id,
                a.nama_area AS area_nama,
                a.lokasi AS area_lokasi

            FROM sub_mesin sm

            INNER JOIN mesin m
                ON m.id = sm.id_mesin

            INNER JOIN jenis_mesin jm
                ON jm.id = m.id_jenis_mesin

            INNER JOIN area_bagian a
                ON a.id = jm.id_area

            WHERE sm.id = ?

            LIMIT 1
        ";


        $stmtHierarchy =
            mysqli_prepare(
                $conn,
                $sqlHierarchy
            );


        if (!$stmtHierarchy) {

            throw new Exception(
                'GAGAL MENYIAPKAN CEK HIERARCHY: ' .
                mysqli_error($conn)
            );
        }


        mysqli_stmt_bind_param(
            $stmtHierarchy,
            'i',
            $id_sub_mesin
        );


        if (
            !mysqli_stmt_execute(
                $stmtHierarchy
            )
        ) {

            $dbError =
                mysqli_stmt_error(
                    $stmtHierarchy
                );


            mysqli_stmt_close(
                $stmtHierarchy
            );


            throw new Exception(
                'GAGAL MEMERIKSA SUB MESIN: ' .
                $dbError
            );
        }


        mysqli_stmt_store_result(
            $stmtHierarchy
        );


        if (
            mysqli_stmt_num_rows(
                $stmtHierarchy
            ) < 1
        ) {

            mysqli_stmt_close(
                $stmtHierarchy
            );


            throw new Exception(
                'Sub Mesin yang dipilih tidak ditemukan di database.'
            );
        }


        mysqli_stmt_bind_result(
            $stmtHierarchy,

            $db_sub_id,
            $db_sub_nama,

            $db_mesin_id,
            $db_mesin_nama,
            $db_mesin_lokasi,

            $db_jenis_id,
            $db_jenis_nama,

            $db_area_id,
            $db_area_nama,
            $db_area_lokasi
        );


        mysqli_stmt_fetch(
            $stmtHierarchy
        );


        mysqli_stmt_close(
            $stmtHierarchy
        );


        $db_sub_id =
            (int)$db_sub_id;

        $db_mesin_id =
            (int)$db_mesin_id;

        $db_jenis_id =
            (int)$db_jenis_id;

        $db_area_id =
            (int)$db_area_id;


        /* =====================================================
           VALIDASI HIERARCHY
        ===================================================== */

        if (
            $id_area !==
            $db_area_id
        ) {

            throw new Exception(
                'Area tidak sesuai dengan Sub Mesin yang dipilih.'
            );
        }


        if (
            $id_jenis_mesin !==
            $db_jenis_id
        ) {

            throw new Exception(
                'Jenis Mesin tidak sesuai dengan Sub Mesin yang dipilih.'
            );
        }


        if (
            $id_mesin !==
            $db_mesin_id
        ) {

            throw new Exception(
                'Mesin tidak sesuai dengan Sub Mesin yang dipilih.'
            );
        }


        /* =====================================================
           PAKAI DATA DATABASE
        ===================================================== */

        $id_area =
            $db_area_id;

        $id_jenis_mesin =
            $db_jenis_id;

        $id_mesin =
            $db_mesin_id;

        $id_sub_mesin =
            $db_sub_id;


        $mesin_str =
            trim(
                (string)$db_mesin_nama
            );


        $sub_mesin_str =
            trim(
                (string)$db_sub_nama
            );


        $lokasi_str =
            trim(
                (string)$db_area_lokasi
            );


        if ($lokasi_str === '') {

            $lokasi_str =
                trim(
                    (string)$db_mesin_lokasi
                );
        }


        if ($lokasi_str === '') {

            $lokasi_str =
                $lokasi_post;
        }


        /* =====================================================
           UPLOAD GAMBAR
        ===================================================== */

        if (
            isset($_FILES['gambar']) &&
            isset($_FILES['gambar']['error']) &&
            (int)$_FILES['gambar']['error'] !==
            UPLOAD_ERR_NO_FILE
        ) {

            $uploadResult =
                uploadKomponenImage(
                    $_FILES['gambar'],
                    $uploadDir,
                    $maxUploadSize,
                    $maxImageWidth,
                    $maxImageHeight,
                    $imageQuality,
                    $maxSourceDimension
                );


            if (
                !$uploadResult['success']
            ) {

                throw new Exception(
                    $uploadResult['error']
                );
            }


            $nama_gambar =
                $uploadResult['filename'];


            $uploadedImage =
                $nama_gambar;
        }


        /* =====================================================
           DATA TAMBAHAN
        ===================================================== */

        $spesifikasi = '';
        $kategori    = '';


        /* =====================================================
           INSERT DATABASE
        ===================================================== */

        $sqlInsert = "
            INSERT INTO komponen
            (
                serial_number,
                id_area,
                id_jenis_mesin,
                id_mesin,
                id_sub_mesin,
                mesin,
                sub_mesin,
                nama_bagian,
                jenis_komponen,
                spesifikasi,
                kategori,
                brand,
                tipe,
                part_number,
                daya,
                io_address,
                ip_address,
                input_voltage,
                frekuensi_input,
                arus_input,
                output,
                frekuensi_output,
                ip_rating,
                lokasi,
                kondisi,
                keterangan,
                gambar
            )
            VALUES
            (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?
            )
        ";


        $stmt =
            mysqli_prepare(
                $conn,
                $sqlInsert
            );


        if (!$stmt) {

            throw new Exception(
                'GAGAL MENYIAPKAN INSERT DATA KOMPONEN: ' .
                mysqli_error($conn)
            );
        }


        /* =====================================================
           BIND PARAMETER
        ===================================================== */

        /*
         * Total parameter = 27
         *
         * 1 string  = serial_number
         * 4 integer = id_area sampai id_sub_mesin
         * 22 string = data lainnya
         */

        $types =
            'siiii' .
            str_repeat(
                's',
                22
            );


        $bindSuccess =
            mysqli_stmt_bind_param(
                $stmt,
                $types,

                $serial_number,

                $id_area,
                $id_jenis_mesin,
                $id_mesin,
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
                $nama_gambar
            );


        if (!$bindSuccess) {

            $dbError =
                mysqli_stmt_error(
                    $stmt
                );


            mysqli_stmt_close(
                $stmt
            );


            throw new Exception(
                'GAGAL MENGIKAT DATA KOMPONEN: ' .
                $dbError
            );
        }


        /* =====================================================
           EXECUTE INSERT
        ===================================================== */

        $executeSuccess =
            mysqli_stmt_execute(
                $stmt
            );


        if (!$executeSuccess) {

            $dbError =
                mysqli_stmt_error(
                    $stmt
                );


            $dbErrorNumber =
                mysqli_stmt_errno(
                    $stmt
                );


            mysqli_stmt_close(
                $stmt
            );


            throw new Exception(
                'GAGAL MENYIMPAN DATA KOMPONEN: ' .
                '[MYSQL ' .
                $dbErrorNumber .
                '] ' .
                $dbError
            );
        }


        /* =====================================================
           AMBIL ID BARU
        ===================================================== */

        $newComponentId =
            mysqli_insert_id(
                $conn
            );


        mysqli_stmt_close(
            $stmt
        );


        /* =====================================================
           WAJIB CEK ID
        ===================================================== */

        if (
            (int)$newComponentId <= 0
        ) {

            throw new Exception(
                'Data berhasil di-INSERT, tetapi ID komponen tidak ditemukan. ' .
                'Periksa PRIMARY KEY AUTO_INCREMENT pada tabel komponen.'
            );
        }


        /* =====================================================
           VERIFIKASI DATA
        ===================================================== */

        $checkStmt =
            mysqli_prepare(
                $conn,
                "
                SELECT id
                FROM komponen
                WHERE id = ?
                LIMIT 1
                "
            );


        if (!$checkStmt) {

            throw new Exception(
                'Data sudah disimpan, tetapi gagal melakukan verifikasi data: ' .
                mysqli_error($conn)
            );
        }


        mysqli_stmt_bind_param(
            $checkStmt,
            'i',
            $newComponentId
        );


        if (
            !mysqli_stmt_execute(
                $checkStmt
            )
        ) {

            $verifyError =
                mysqli_stmt_error(
                    $checkStmt
                );


            mysqli_stmt_close(
                $checkStmt
            );


            throw new Exception(
                'Data sudah disimpan, tetapi gagal memverifikasi hasil penyimpanan: ' .
                $verifyError
            );
        }


        mysqli_stmt_store_result(
            $checkStmt
        );


        $verified =
            (
                mysqli_stmt_num_rows(
                    $checkStmt
                ) > 0
            );


        mysqli_stmt_close(
            $checkStmt
        );


        if (!$verified) {

            throw new Exception(
                'Data komponen tidak ditemukan setelah proses INSERT.'
            );
        }


        /* =====================================================
           BERHASIL
        ===================================================== */

        header(
            'Location: index.php?status=success&id=' .
            (int)$newComponentId,
            true,
            302
        );

        exit;


    } catch (Exception $ex) {

        /* =====================================================
           AMBIL ERROR
        ===================================================== */

        $error =
            $ex->getMessage();


        /* =====================================================
           HAPUS GAMBAR JIKA DATABASE GAGAL
        ===================================================== */

        if (
            !empty($uploadedImage)
        ) {

            $fileDelete =
                rtrim(
                    $uploadDir,
                    '/\\'
                ) .
                DIRECTORY_SEPARATOR .
                $uploadedImage;


            if (
                file_exists(
                    $fileDelete
                )
            ) {

                @unlink(
                    $fileDelete
                );
            }


            $nama_gambar = '';
        }
    }
}


/* =========================================================
   DATA LOKASI
========================================================= */

$q_lokasi =
    mysqli_query(
        $conn,
        "
        SELECT DISTINCT lokasi
        FROM area_bagian
        WHERE lokasi IS NOT NULL
          AND TRIM(lokasi) != ''
        ORDER BY lokasi ASC
        "
    );


/* =========================================================
   HEADER
========================================================= */

include "../template/header.php";

?>

<style>

.komponen-form-wrapper {
    width: 100%;
    max-width: 100%;
}

.komponen-page-header {
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,.04);
}

.komponen-content-card {
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,.04);
}

.komponen-card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.form-section-title {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: .82rem;
    font-weight: 700;
    color: #0d6efd;
    margin-bottom: 14px;
    text-transform: uppercase;
    letter-spacing: .2px;
}

.komponen-form-wrapper .form-label {
    font-size: .78rem;
    margin-bottom: 5px;
}

.komponen-form-wrapper .form-control,
.komponen-form-wrapper .form-select {
    min-height: 36px;
    font-size: .82rem;
    border-radius: 7px;
}

.komponen-form-wrapper textarea.form-control {
    min-height: 70px;
    resize: vertical;
}

.komponen-form-wrapper .form-control:focus,
.komponen-form-wrapper .form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 .18rem rgba(13,110,253,.10);
}

.hierarchy-info {
    background: rgba(13,110,253,.05);
    border: 1px solid rgba(13,110,253,.10);
    border-radius: 8px;
    padding: 9px 12px;
    font-size: .72rem;
    color: #6c757d;
}

.preview-container-komponen {
    width: 100%;
    height: 180px;
    border: 1px dashed #ced4da;
    border-radius: 10px;
    background: #f8f9fa;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-container-komponen img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: none;
}

.preview-placeholder-komponen {
    text-align: center;
    color: #adb5bd;
    padding: 15px;
}

.preview-placeholder-komponen i {
    font-size: 2.2rem;
    display: block;
    margin-bottom: 5px;
}

.upload-box-komponen {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 15px;
    height: 100%;
}

.upload-icon-komponen {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(13,110,253,.10);
    color: #0d6efd;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.form-action-komponen {
    border-top: 1px solid #e9ecef;
    margin-top: 20px;
    padding-top: 15px;
}

.form-action-komponen .btn {
    min-height: 36px;
    border-radius: 7px;
}

@media (max-width: 767.98px) {

    .komponen-page-header {
        border-radius: 9px;
        padding: 14px !important;
    }

    .komponen-page-header h3 {
        font-size: 1rem !important;
    }

    .komponen-page-header p {
        font-size: .72rem !important;
    }

    .komponen-page-header .back-button {
        width: 34px !important;
        height: 34px !important;
    }

    .komponen-content-card {
        border-radius: 9px;
    }

    .komponen-card-header {
        padding: 11px 13px !important;
    }

    .komponen-card-body {
        padding: 13px !important;
    }

    .form-section-title {
        font-size: .76rem;
        margin-bottom: 12px;
    }

    .preview-container-komponen {
        height: 190px;
    }

    .upload-box-komponen {
        padding: 12px;
    }

    .form-action-komponen {
        flex-direction: column;
        align-items: stretch !important;
    }

    .form-action-komponen .btn {
        width: 100%;
    }
}

@media (max-width: 575.98px) {

    .komponen-form-wrapper {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .komponen-page-header {
        margin-left: 0;
        margin-right: 0;
    }

    .komponen-content-card {
        margin-left: 0;
        margin-right: 0;
    }

    .komponen-form-wrapper .row {
        --bs-gutter-x: .75rem;
        --bs-gutter-y: .75rem;
    }

    .preview-container-komponen {
        height: 160px;
    }

    .upload-box-komponen .d-flex {
        align-items: flex-start !important;
    }
}

</style>


<div class="container-fluid p-0 komponen-form-wrapper">

    <div class="komponen-page-header mb-3 py-3 px-4">

        <div class="d-flex align-items-center gap-3">

            <a
                href="index.php"
                class="btn btn-outline-secondary btn-sm rounded-circle d-inline-flex align-items-center justify-content-center back-button"
                style="
                    width:38px;
                    height:38px;
                    flex-shrink:0;
                "
                title="Kembali"
            >
                <i class="bi bi-arrow-left fs-5"></i>
            </a>


            <div class="min-width-0">

                <h3 class="dashboard-title m-0 fs-4 fw-bold text-dark">
                    Tambah Komponen Baru
                </h3>

                <p class="dashboard-subtitle m-0 small text-muted">
                    Lengkapi rincian spesifikasi dan data komponen
                </p>

            </div>

        </div>

    </div>


    <div class="komponen-content-card mb-3">

        <div class="komponen-card-header py-2 px-3">

            <h6 class="m-0 fw-bold text-dark">

                <i class="bi bi-plus-circle me-2 text-primary"></i>

                Form Input Komponen

            </h6>

        </div>


        <div class="komponen-card-body p-3 p-md-4">

            <?php if (!empty($error)) : ?>

                <div
                    class="alert alert-danger border-0 d-flex align-items-start py-2 px-3 mb-3"
                    role="alert"
                    style="display:flex !important;"
                >

                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>

                    <div class="small">

                        <strong>
                            GAGAL MENYIMPAN DATA KOMPONEN
                        </strong>

                        <br>

                        <?= e($error); ?>

                    </div>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
                enctype="multipart/form-data"
                id="formKomponen"
                autocomplete="off"
            >

                <div class="form-section-title">

                    <i class="bi bi-info-circle"></i>

                    Informasi Umum

                </div>


                <div class="row g-3 mb-3">

                    <div class="col-12 col-md-3">

                        <label class="form-label fw-semibold text-dark">
                            Serial Number (SN)
                        </label>

                        <input
                            type="text"
                            name="serial_number"
                            class="form-control form-control-sm"
                            placeholder="Contoh: SN-8829102"
                            value="<?= e($serial_number); ?>"
                        >

                    </div>


                    <div class="col-12 col-md-5">

                        <label class="form-label fw-semibold text-dark">

                            Nama Komponen

                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="text"
                            name="nama_bagian"
                            class="form-control form-control-sm"
                            placeholder="Contoh: Inverter / Motor Conveyor"
                            value="<?= e($nama_bagian); ?>"
                            maxlength="100"
                            required
                        >

                    </div>


                    <div class="col-12 col-md-4">

                        <label class="form-label fw-semibold text-dark">
                            Jenis Komponen
                        </label>

                        <input
                            type="text"
                            name="jenis_komponen"
                            class="form-control form-control-sm"
                            placeholder="Contoh: Drive / Motor / Sensor"
                            value="<?= e($jenis_komponen); ?>"
                            maxlength="100"
                        >

                    </div>


                    <div class="col-12 col-md-4">

                        <label class="form-label fw-semibold text-dark">
                            Lokasi
                        </label>

                        <select
                            name="lokasi"
                            id="form_lokasi"
                            class="form-select form-select-sm"
                            onchange="loadAreaByLokasi(this.value)"
                        >

                            <option value="">
                                -- Pilih Lokasi --
                            </option>

                            <?php if ($q_lokasi) : ?>

                                <?php while (
                                    $l = mysqli_fetch_assoc($q_lokasi)
                                ) : ?>

                                    <?php
                                    $lokasi_option =
                                        trim(
                                            $l['lokasi'] ?? ''
                                        );
                                    ?>

                                    <option
                                        value="<?= e($lokasi_option); ?>"
                                        <?= (
                                            $lokasi_post ===
                                            $lokasi_option
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >
                                        <?= e($lokasi_option); ?>
                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>

                    </div>


                    <div class="col-12 col-md-3">

                        <label class="form-label fw-semibold text-dark">

                            Area / Bagian

                            <span class="text-danger">*</span>

                        </label>

                        <select
                            name="id_area"
                            id="form_area"
                            class="form-select form-select-sm"
                            onchange="loadJenisMesin(this.value)"
                            required
                        >

                            <option value="">
                                -- Pilih Area --
                            </option>

                        </select>

                    </div>


                    <div class="col-12 col-md-3">

                        <label class="form-label fw-semibold text-dark">

                            Jenis Mesin

                            <span class="text-danger">*</span>

                        </label>

                        <select
                            name="id_jenis_mesin"
                            id="form_jenis_mesin"
                            class="form-select form-select-sm"
                            onchange="loadMesin(this.value)"
                            required
                        >

                            <option value="">
                                -- Pilih Jenis Mesin --
                            </option>

                        </select>

                    </div>


                    <div class="col-12 col-md-3">

                        <label class="form-label fw-semibold text-dark">

                            Mesin Induk

                            <span class="text-danger">*</span>

                        </label>

                        <select
                            name="id_mesin"
                            id="form_mesin"
                            class="form-select form-select-sm"
                            onchange="loadSubMesinForm(this.value)"
                            required
                        >

                            <option value="">
                                -- Pilih Mesin --
                            </option>

                        </select>

                    </div>


                    <div class="col-12 col-md-3">

                        <label class="form-label fw-semibold text-dark">

                            Sub Mesin

                            <span class="text-danger">*</span>

                        </label>

                        <select
                            name="id_sub_mesin"
                            id="form_sub_mesin"
                            class="form-select form-select-sm"
                            required
                        >

                            <option value="">
                                -- Pilih Sub Mesin --
                            </option>

                        </select>

                    </div>


                    <div class="col-12">

                        <div class="hierarchy-info">

                            <i class="bi bi-diagram-3 me-1 text-primary"></i>

                            Pilih data secara berurutan:

                            <strong>Lokasi</strong>
                            →
                            <strong>Area</strong>
                            →
                            <strong>Jenis Mesin</strong>
                            →
                            <strong>Mesin</strong>
                            →
                            <strong>Sub Mesin</strong>

                        </div>

                    </div>


                    <div class="col-12">

                        <label class="form-label fw-semibold text-dark">
                            Foto Komponen / Part
                        </label>

                        <div class="row g-3 align-items-stretch">

                            <div class="col-12 col-md-4">

                                <div class="preview-container-komponen">

                                    <div
                                        id="preview-placeholder"
                                        class="preview-placeholder-komponen"
                                    >

                                        <i class="bi bi-image"></i>

                                        <span class="small">
                                            Preview Foto
                                        </span>

                                    </div>

                                    <img
                                        id="preview-gambar"
                                        src=""
                                        alt="Preview Gambar Komponen"
                                    >

                                </div>

                            </div>


                            <div class="col-12 col-md-8">

                                <div class="upload-box-komponen">

                                    <div class="d-flex align-items-center gap-3 mb-3">

                                        <div class="upload-icon-komponen">

                                            <i class="bi bi-cloud-arrow-up fs-5"></i>

                                        </div>

                                        <div>

                                            <div class="fw-semibold small text-dark">
                                                Upload Foto Komponen
                                            </div>

                                            <div class="text-muted small">
                                                Pilih foto komponen atau part
                                            </div>

                                        </div>

                                    </div>


                                    <input
                                        type="file"
                                        name="gambar"
                                        id="input-gambar"
                                        class="form-control form-control-sm"
                                        accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                                    >


                                    <div class="form-text text-muted small mt-2">

                                        <i class="bi bi-info-circle me-1"></i>

                                        Format JPG, JPEG, PNG, WEBP

                                        <span class="mx-1">•</span>

                                        Maksimal <strong>50 MB</strong>

                                        <span class="mx-1">•</span>

                                        Otomatis dikompres maksimal 1600×1600 px

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <hr class="my-4 text-muted opacity-25">


                <div class="form-section-title">

                    <i class="bi bi-tools"></i>

                    Spesifikasi & Brand

                </div>


                <div class="row g-3 mb-3">

                    <div class="col-12 col-md-4">

                        <label class="form-label fw-semibold text-dark">
                            Brand / Merk
                        </label>

                        <input
                            type="text"
                            name="brand"
                            class="form-control form-control-sm"
                            placeholder="Contoh: Schneider / Siemens"
                            value="<?= e($brand); ?>"
                            maxlength="50"
                        >

                    </div>


                    <div class="col-12 col-md-4">

                        <label class="form-label fw-semibold text-dark">
                            Tipe
                        </label>

                        <input
                            type="text"
                            name="tipe"
                            class="form-control form-control-sm"
                            placeholder="Contoh: ATV320 / CPU 314C-2"
                            value="<?= e($tipe); ?>"
                            maxlength="50"
                        >

                    </div>


                    <div class="col-12 col-md-4">

                        <label class="form-label fw-semibold text-dark">
                            Part Number
                        </label>

                        <input
                            type="text"
                            name="part_number"
                            class="form-control form-control-sm"
                            placeholder="Contoh: PN-99201"
                            value="<?= e($part_number); ?>"
                            maxlength="100"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label fw-semibold text-dark">
                            Daya
                        </label>

                        <input
                            type="text"
                            name="daya"
                            class="form-control form-control-sm"
                            placeholder="Contoh: 1.5 kW"
                            value="<?= e($daya); ?>"
                            maxlength="20"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label fw-semibold text-dark">
                            IO Address
                        </label>

                        <input
                            type="text"
                            name="io_address"
                            class="form-control form-control-sm"
                            placeholder="Contoh: I:0/1"
                            value="<?= e($io_address); ?>"
                            maxlength="30"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label fw-semibold text-dark">
                            IP Address
                        </label>

                        <input
                            type="text"
                            name="ip_address"
                            class="form-control form-control-sm"
                            placeholder="Contoh: 192.168.1.10"
                            value="<?= e($ip_address); ?>"
                            maxlength="100"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label fw-semibold text-dark">
                            IP Rating
                        </label>

                        <input
                            type="text"
                            name="ip_rating"
                            class="form-control form-control-sm"
                            placeholder="Contoh: IP65"
                            value="<?= e($ip_rating); ?>"
                            maxlength="20"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label fw-semibold text-dark">
                            Input Voltage
                        </label>

                        <input
                            type="text"
                            name="input_voltage"
                            class="form-control form-control-sm"
                            placeholder="Contoh: 380V AC"
                            value="<?= e($input_voltage); ?>"
                            maxlength="20"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label fw-semibold text-dark">
                            Frekuensi Input
                        </label>

                        <input
                            type="text"
                            name="frekuensi_input"
                            class="form-control form-control-sm"
                            placeholder="Contoh: 50/60 Hz"
                            value="<?= e($frekuensi_input); ?>"
                            maxlength="20"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label fw-semibold text-dark">
                            Arus Input
                        </label>

                        <input
                            type="text"
                            name="arus_input"
                            class="form-control form-control-sm"
                            placeholder="Contoh: 5A"
                            value="<?= e($arus_input); ?>"
                            maxlength="20"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label fw-semibold text-dark">
                            Output
                        </label>

                        <input
                            type="text"
                            name="output"
                            class="form-control form-control-sm"
                            placeholder="Contoh: 0-220V"
                            value="<?= e($output); ?>"
                            maxlength="50"
                        >

                    </div>


                    <div class="col-12 col-sm-6 col-md-3">

                        <label class="form-label fw-semibold text-dark">
                            Frekuensi Output
                        </label>

                        <input
                            type="text"
                            name="frekuensi_output"
                            class="form-control form-control-sm"
                            placeholder="Contoh: 0-400 Hz"
                            value="<?= e($frekuensi_output); ?>"
                            maxlength="20"
                        >

                    </div>

                </div>


                <hr class="my-4 text-muted opacity-25">


                <div class="form-section-title">

                    <i class="bi bi-card-checklist"></i>

                    Status Komponen

                </div>


                <div class="row g-3">

                    <div class="col-12 col-md-4">

                        <label class="form-label fw-semibold text-dark">

                            Kondisi

                            <span class="text-danger">*</span>

                        </label>

                        <select
                            name="kondisi"
                            class="form-select form-select-sm"
                            required
                        >

                            <option
                                value="Baik"
                                <?= (
                                    $kondisi === 'Baik'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Baik
                            </option>


                            <option
                                value="Perlu Pemeriksaan"
                                <?= (
                                    $kondisi === 'Perlu Pemeriksaan'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Perlu Pemeriksaan
                            </option>


                            <option
                                value="Dalam Perbaikan"
                                <?= (
                                    $kondisi === 'Dalam Perbaikan'
                                )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                Dalam Perbaikan
                            </option>

                        </select>

                    </div>


                    <div class="col-12 col-md-8">

                        <label class="form-label fw-semibold text-dark">
                            Keterangan Tambahan
                        </label>

                        <textarea
                            name="keterangan"
                            class="form-control form-control-sm"
                            rows="2"
                            placeholder="Catatan kondisi atau deskripsi tambahan..."
                        ><?= e($keterangan); ?></textarea>

                    </div>

                </div>


                <div class="form-action-komponen d-flex align-items-center gap-2">

                    <button
                        type="submit"
                        name="simpan"
                        value="1"
                        id="btnSimpan"
                        class="btn btn-primary px-4 btn-sm fw-semibold"
                    >

                        <i class="bi bi-check-lg me-1"></i>

                        Simpan Data

                    </button>


                    <a
                        href="index.php"
                        class="btn btn-light border px-4 btn-sm fw-semibold text-secondary"
                    >

                        <i class="bi bi-x-lg me-1"></i>

                        Batal

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>


<script>

const MAX_IMAGE_SIZE =
    <?= (int)$maxUploadSize; ?>;


const MAX_IMAGE_SIZE_TEXT =
    "50 MB";


const selectedArea =
    <?= json_encode($id_area); ?>;


const selectedJenisMesin =
    <?= json_encode($id_jenis_mesin); ?>;


const selectedMesin =
    <?= json_encode($id_mesin); ?>;


const selectedSubMesin =
    <?= json_encode($id_sub_mesin); ?>;


/* =========================================================
   HELPER ELEMENT
========================================================= */

function getElement(id)
{
    return document.getElementById(id);
}


/* =========================================================
   LOADING SELECT
========================================================= */

function setLoading(select, text)
{
    if (!select) {
        return;
    }

    select.innerHTML =
        '<option value="">' +
        text +
        '</option>';

    select.disabled = true;
}


function setDefault(select, text)
{
    if (!select) {
        return;
    }

    select.innerHTML =
        '<option value="">' +
        text +
        '</option>';

    select.disabled = false;
}


/* =========================================================
   PREVIEW
========================================================= */

function resetPreview()
{
    const input =
        getElement('input-gambar');

    const preview =
        getElement('preview-gambar');

    const placeholder =
        getElement('preview-placeholder');


    if (input) {
        input.value = "";
    }


    if (preview) {

        preview.src = "";

        preview.style.display =
            "none";
    }


    if (placeholder) {

        placeholder.style.display =
            "block";
    }
}


function previewGambar(input)
{
    const preview =
        getElement('preview-gambar');

    const placeholder =
        getElement('preview-placeholder');


    if (
        !input ||
        !input.files ||
        !input.files[0]
    ) {

        resetPreview();

        return;
    }


    const file =
        input.files[0];


    /* =====================================================
       CEK SIZE
    ===================================================== */

    if (
        file.size >
        MAX_IMAGE_SIZE
    ) {

        alert(
            "Ukuran gambar maksimal " +
            MAX_IMAGE_SIZE_TEXT +
            "."
        );

        resetPreview();

        return;
    }


    /* =====================================================
       CEK MIME
    ===================================================== */

    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];


    if (
        file.type &&
        !allowedTypes.includes(
            file.type
        )
    ) {

        alert(
            "Format gambar harus JPG, JPEG, PNG, atau WEBP."
        );

        resetPreview();

        return;
    }


    /* =====================================================
       PREVIEW
    ===================================================== */

    const reader =
        new FileReader();


    reader.onload =
        function(event)
        {
            if (preview) {

                preview.src =
                    event.target.result;

                preview.style.display =
                    "block";
            }


            if (placeholder) {

                placeholder.style.display =
                    "none";
            }
        };


    reader.onerror =
        function()
        {
            alert(
                "Gagal membaca file gambar."
            );

            resetPreview();
        };


    reader.readAsDataURL(
        file
    );
}


/* =========================================================
   INPUT GAMBAR
========================================================= */

const inputGambar =
    getElement(
        'input-gambar'
    );


if (inputGambar) {

    inputGambar.addEventListener(
        'change',
        function()
        {
            previewGambar(
                this
            );
        }
    );
}


/* =========================================================
   LOAD AREA
========================================================= */

function loadAreaByLokasi(
    lokasi,
    restore = false
)
{
    const areaSelect =
        getElement(
            'form_area'
        );


    const jenisSelect =
        getElement(
            'form_jenis_mesin'
        );


    const mesinSelect =
        getElement(
            'form_mesin'
        );


    const subSelect =
        getElement(
            'form_sub_mesin'
        );


    setDefault(
        areaSelect,
        '-- Pilih Area --'
    );


    setDefault(
        jenisSelect,
        '-- Pilih Jenis Mesin --'
    );


    setDefault(
        mesinSelect,
        '-- Pilih Mesin --'
    );


    setDefault(
        subSelect,
        '-- Pilih Sub Mesin --'
    );


    if (!lokasi) {
        return;
    }


    setLoading(
        areaSelect,
        'Memuat Area...'
    );


    fetch(
        'get_area.php?lokasi=' +
        encodeURIComponent(
            lokasi
        ),
        {
            method: 'GET',
            cache: 'no-store'
        }
    )
    .then(
        function(response)
        {
            if (!response.ok) {

                throw new Error(
                    'HTTP ' +
                    response.status
                );
            }


            return response.text();
        }
    )
    .then(
        function(data)
        {
            areaSelect.innerHTML =
                data;

            areaSelect.disabled =
                false;


            if (
                restore &&
                selectedArea
            ) {

                areaSelect.value =
                    String(
                        selectedArea
                    );


                if (
                    areaSelect.value ===
                    String(
                        selectedArea
                    )
                ) {

                    loadJenisMesin(
                        selectedArea,
                        true
                    );
                }
            }
        }
    )
    .catch(
        function(error)
        {
            console.error(
                'Gagal memuat Area:',
                error
            );


            setDefault(
                areaSelect,
                '-- Gagal memuat Area --'
            );


            alert(
                'Gagal memuat data Area.'
            );
        }
    );
}


/* =========================================================
   LOAD JENIS MESIN
========================================================= */

function loadJenisMesin(
    id_area,
    restore = false
)
{
    const jenisSelect =
        getElement(
            'form_jenis_mesin'
        );


    const mesinSelect =
        getElement(
            'form_mesin'
        );


    const subSelect =
        getElement(
            'form_sub_mesin'
        );


    setDefault(
        jenisSelect,
        '-- Pilih Jenis Mesin --'
    );


    setDefault(
        mesinSelect,
        '-- Pilih Mesin --'
    );


    setDefault(
        subSelect,
        '-- Pilih Sub Mesin --'
    );


    if (!id_area) {
        return;
    }


    setLoading(
        jenisSelect,
        'Memuat Jenis Mesin...'
    );


    fetch(
        'get_jenis_mesin.php?id_area=' +
        encodeURIComponent(
            id_area
        ),
        {
            method: 'GET',
            cache: 'no-store'
        }
    )
    .then(
        function(response)
        {
            if (!response.ok) {

                throw new Error(
                    'HTTP ' +
                    response.status
                );
            }


            return response.text();
        }
    )
    .then(
        function(data)
        {
            jenisSelect.innerHTML =
                data;

            jenisSelect.disabled =
                false;


            if (
                restore &&
                selectedJenisMesin
            ) {

                jenisSelect.value =
                    String(
                        selectedJenisMesin
                    );


                if (
                    jenisSelect.value ===
                    String(
                        selectedJenisMesin
                    )
                ) {

                    loadMesin(
                        selectedJenisMesin,
                        true
                    );
                }
            }
        }
    )
    .catch(
        function(error)
        {
            console.error(
                'Gagal memuat Jenis Mesin:',
                error
            );


            setDefault(
                jenisSelect,
                '-- Gagal memuat Jenis Mesin --'
            );


            alert(
                'Gagal memuat data Jenis Mesin.'
            );
        }
    );
}


/* =========================================================
   LOAD MESIN
========================================================= */

function loadMesin(
    id_jenis,
    restore = false
)
{
    const mesinSelect =
        getElement(
            'form_mesin'
        );


    const subSelect =
        getElement(
            'form_sub_mesin'
        );


    setDefault(
        mesinSelect,
        '-- Pilih Mesin --'
    );


    setDefault(
        subSelect,
        '-- Pilih Sub Mesin --'
    );


    if (!id_jenis) {
        return;
    }


    setLoading(
        mesinSelect,
        'Memuat Mesin...'
    );


    fetch(
        'get_mesin.php?id_jenis=' +
        encodeURIComponent(
            id_jenis
        ),
        {
            method: 'GET',
            cache: 'no-store'
        }
    )
    .then(
        function(response)
        {
            if (!response.ok) {

                throw new Error(
                    'HTTP ' +
                    response.status
                );
            }


            return response.text();
        }
    )
    .then(
        function(data)
        {
            mesinSelect.innerHTML =
                data;

            mesinSelect.disabled =
                false;


            if (
                restore &&
                selectedMesin
            ) {

                mesinSelect.value =
                    String(
                        selectedMesin
                    );


                if (
                    mesinSelect.value ===
                    String(
                        selectedMesin
                    )
                ) {

                    loadSubMesinForm(
                        selectedMesin,
                        true
                    );
                }
            }
        }
    )
    .catch(
        function(error)
        {
            console.error(
                'Gagal memuat Mesin:',
                error
            );


            setDefault(
                mesinSelect,
                '-- Gagal memuat Mesin --'
            );


            alert(
                'Gagal memuat data Mesin.'
            );
        }
    );
}


/* =========================================================
   LOAD SUB MESIN
========================================================= */

function loadSubMesinForm(
    id_mesin,
    restore = false
)
{
    const subSelect =
        getElement(
            'form_sub_mesin'
        );


    setDefault(
        subSelect,
        '-- Pilih Sub Mesin --'
    );


    if (!id_mesin) {
        return;
    }


    setLoading(
        subSelect,
        'Memuat Sub Mesin...'
    );


    fetch(
        'get_sub_mesin.php?id_mesin=' +
        encodeURIComponent(
            id_mesin
        ),
        {
            method: 'GET',
            cache: 'no-store'
        }
    )
    .then(
        function(response)
        {
            if (!response.ok) {

                throw new Error(
                    'HTTP ' +
                    response.status
                );
            }


            return response.text();
        }
    )
    .then(
        function(data)
        {
            subSelect.innerHTML =
                data;

            subSelect.disabled =
                false;


            if (
                restore &&
                selectedSubMesin
            ) {

                subSelect.value =
                    String(
                        selectedSubMesin
                    );
            }
        }
    )
    .catch(
        function(error)
        {
            console.error(
                'Gagal memuat Sub Mesin:',
                error
            );


            setDefault(
                subSelect,
                '-- Gagal memuat Sub Mesin --'
            );


            alert(
                'Gagal memuat data Sub Mesin.'
            );
        }
    );
}


/* =========================================================
   RESTORE DROPDOWN
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function()
    {
        const lokasiSelect =
            getElement(
                'form_lokasi'
            );


        if (
            lokasiSelect &&
            lokasiSelect.value
        ) {

            <?php if (
                $_SERVER['REQUEST_METHOD'] === 'POST'
            ) : ?>

            loadAreaByLokasi(
                lokasiSelect.value,
                true
            );

            <?php endif; ?>
        }
    }
);


/* =========================================================
   SUBMIT FORM
========================================================= */

const formKomponen =
    getElement(
        'formKomponen'
    );


const btnSimpan =
    getElement(
        'btnSimpan'
    );


if (formKomponen) {

    formKomponen.addEventListener(
        'submit',
        function(event)
        {

            /* =================================================
               NAMA KOMPONEN
            ================================================= */

            const namaKomponen =
                formKomponen.querySelector(
                    '[name="nama_bagian"]'
                );


            const area =
                getElement(
                    'form_area'
                );


            const jenis =
                getElement(
                    'form_jenis_mesin'
                );


            const mesin =
                getElement(
                    'form_mesin'
                );


            const subMesin =
                getElement(
                    'form_sub_mesin'
                );


            if (
                namaKomponen &&
                namaKomponen.value.trim() === ''
            ) {

                event.preventDefault();

                namaKomponen.focus();

                alert(
                    'Nama Komponen wajib diisi.'
                );

                return;
            }


            /* =================================================
               AREA
            ================================================= */

            if (
                area &&
                !area.value
            ) {

                event.preventDefault();

                area.focus();

                alert(
                    'Area / Bagian wajib dipilih.'
                );

                return;
            }


            /* =================================================
               JENIS MESIN
            ================================================= */

            if (
                jenis &&
                !jenis.value
            ) {

                event.preventDefault();

                jenis.focus();

                alert(
                    'Jenis Mesin wajib dipilih.'
                );

                return;
            }


            /* =================================================
               MESIN
            ================================================= */

            if (
                mesin &&
                !mesin.value
            ) {

                event.preventDefault();

                mesin.focus();

                alert(
                    'Mesin Induk wajib dipilih.'
                );

                return;
            }


            /* =================================================
               SUB MESIN
            ================================================= */

            if (
                subMesin &&
                !subMesin.value
            ) {

                event.preventDefault();

                subMesin.focus();

                alert(
                    'Sub Mesin wajib dipilih.'
                );

                return;
            }


            /* =================================================
               CEK GAMBAR
            ================================================= */

            const fileInput =
                getElement(
                    'input-gambar'
                );


            if (
                fileInput &&
                fileInput.files &&
                fileInput.files[0]
            ) {

                const file =
                    fileInput.files[0];


                if (
                    file.size >
                    MAX_IMAGE_SIZE
                ) {

                    event.preventDefault();

                    alert(
                        'Ukuran gambar maksimal ' +
                        MAX_IMAGE_SIZE_TEXT +
                        '.'
                    );

                    return;
                }


                const allowedTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];


                if (
                    file.type &&
                    !allowedTypes.includes(
                        file.type
                    )
                ) {

                    event.preventDefault();

                    alert(
                        'Format gambar harus JPG, JPEG, PNG, atau WEBP.'
                    );

                    return;
                }
            }


            /* =================================================
               JANGAN MENCEGAH SUBMIT
            ================================================= */

            if (btnSimpan) {

                btnSimpan.disabled =
                    true;


                btnSimpan.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span>' +
                    ' Menyimpan...';
            }

        }
    );
}

</script>


<?php

include "../template/footer.php";

?>