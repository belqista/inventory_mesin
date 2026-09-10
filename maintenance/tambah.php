<?php
require_once __DIR__ . '/../template/auth.php';
require_user_role();

include "../koneksi.php";

date_default_timezone_set('Asia/Jakarta');

$error = "";
$success = "";

/* =========================================================
   KONFIGURASI UPLOAD FOTO
========================================================= */

$maxFotoSize = 50 * 1024 * 1024; // 50 MB

$allowedFotoMime = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'
];


/* =========================================================
   FUNGSI ESCAPE
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   FUNGSI AMBIL JENIS KOMPONEN
========================================================= */

function getJenisKomponen($data)
{
    $fields = [
        'jenis_komponen',
        'kategori',
        'jenis'
    ];

    foreach ($fields as $field) {

        if (
            isset($data[$field]) &&
            trim((string)$data[$field]) !== ''
        ) {

            return trim(
                (string)$data[$field]
            );
        }
    }

    return '';
}


/* =========================================================
   FUNGSI HAPUS FILE
========================================================= */

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


/* =========================================================
   FUNGSI DETEKSI MIME GAMBAR
   TANPA finfo_open()
========================================================= */

function detectImageMime($filePath)
{
    if (
        empty($filePath) ||
        !is_file($filePath)
    ) {

        return false;
    }


    /*
     * getimagesize() tidak membutuhkan Fileinfo.
     * Fungsi ini membaca isi file gambar.
     */

    $imageInfo =
        @getimagesize($filePath);


    if (
        $imageInfo === false ||
        empty($imageInfo[2])
    ) {

        return false;
    }


    $imageType =
        (int)$imageInfo[2];


    switch ($imageType) {

        case IMAGETYPE_JPEG:

            return 'image/jpeg';


        case IMAGETYPE_PNG:

            return 'image/png';


        case IMAGETYPE_WEBP:

            /*
             * IMAGETYPE_WEBP tersedia pada
             * PHP modern. Cek terlebih dahulu
             * agar tidak error pada server lama.
             */

            if (
                defined('IMAGETYPE_WEBP')
            ) {

                return 'image/webp';
            }

            return false;


        default:

            return false;
    }
}


/* =========================================================
   FUNGSI VALIDASI TANGGAL
========================================================= */

function validDateYmd($date)
{
    if (
        !is_string($date) ||
        trim($date) === ''
    ) {

        return false;
    }


    $dateObject =
        DateTime::createFromFormat(
            'Y-m-d',
            $date
        );


    if (!$dateObject) {

        return false;
    }


    $errors =
        DateTime::getLastErrors();


    if (
        is_array($errors) &&
        (
            $errors['warning_count'] > 0 ||
            $errors['error_count'] > 0
        )
    ) {

        return false;
    }


    return
        $dateObject->format('Y-m-d') === $date;
}


/* =========================================================
   PROSES SIMPAN MAINTENANCE
========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['simpan_maintenance'])
) {

    /* =====================================================
       DATA UTAMA
    ===================================================== */

    $id_komponen = intval(
        $_POST['id_komponen'] ?? 0
    );

    $status = trim(
        $_POST['status'] ?? ''
    );

    $teknisi = trim(
        $_POST['teknisi'] ?? ''
    );

    $tindakan = trim(
        $_POST['tindakan'] ?? ''
    );

    $jenis = trim(
        $_POST['jenis'] ?? ''
    );

    $sparepart = trim(
        $_POST['sparepart'] ?? ''
    );

    $catatan = trim(
        $_POST['catatan'] ?? ''
    );

    $input_tanggal = trim(
        $_POST['tanggal'] ?? date('Y-m-d')
    );


    /* =====================================================
       DATA INFORMASI UMUM
    ===================================================== */

    $serial_number = '';

    $nama_bagian = '';

    $jenis_komponen = '';

    $nama_mesin = '';

    $nama_sub_mesin = '';

    $lokasi_penempatan = '';


    /* =====================================================
       SPESIFIKASI MANUAL
    ===================================================== */

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


    /* =====================================================
       VALIDASI DASAR
    ===================================================== */

    if ($id_komponen <= 0) {

        $error =
            "Silakan pilih komponen terlebih dahulu.";

    } elseif ($tindakan === '') {

        $error =
            "Tindakan maintenance wajib diisi.";

    } elseif ($status === '') {

        $error =
            "Status pekerjaan wajib dipilih.";

    } elseif ($input_tanggal === '') {

        $error =
            "Tanggal maintenance wajib diisi.";
    }


    /* =====================================================
       VALIDASI TANGGAL
    ===================================================== */

    if (empty($error)) {

        if (
            !validDateYmd(
                $input_tanggal
            )
        ) {

            $error =
                "Format tanggal maintenance tidak valid.";
        }
    }


    /* =====================================================
       VALIDASI STATUS
    ===================================================== */

    $status_allowed = [
        'Selesai',
        'Proses',
        'Pending'
    ];


    if (
        empty($error) &&
        !in_array(
            $status,
            $status_allowed,
            true
        )
    ) {

        $error =
            "Status maintenance tidak valid.";
    }


    /* =====================================================
       VALIDASI JENIS MAINTENANCE
    ===================================================== */

    $jenis_allowed = [
        'Preventive',
        'Corrective',
        'Breakdown',
        'Predictive'
    ];


    if (
        empty($error) &&
        $jenis !== '' &&
        !in_array(
            $jenis,
            $jenis_allowed,
            true
        )
    ) {

        $error =
            "Jenis maintenance tidak valid.";
    }


    /* =====================================================
       AMBIL DATA KOMPONEN
    ===================================================== */

    $data_komponen = null;


    if (empty($error)) {

        $sql_komponen = "
            SELECT
                k.*,
                sm.nama_sub_mesin,
                m.nama_mesin

            FROM komponen k

            LEFT JOIN sub_mesin sm
                ON k.id_sub_mesin = sm.id

            LEFT JOIN mesin m
                ON sm.id_mesin = m.id

            WHERE k.id = ?

            LIMIT 1
        ";


        $stmt_komponen =
            mysqli_prepare(
                $conn,
                $sql_komponen
            );


        if (!$stmt_komponen) {

            $error =
                "Gagal menyiapkan data komponen: " .
                mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt_komponen,
                "i",
                $id_komponen
            );


            if (
                !mysqli_stmt_execute(
                    $stmt_komponen
                )
            ) {

                $error =
                    "Gagal mengambil data komponen: " .
                    mysqli_stmt_error(
                        $stmt_komponen
                    );

            } else {

                /*
                 * mysqli_stmt_get_result()
                 * digunakan seperti kode sebelumnya.
                 */

                $result_komponen =
                    mysqli_stmt_get_result(
                        $stmt_komponen
                    );


                if (!$result_komponen) {

                    $error =
                        "Gagal membaca data komponen: " .
                        mysqli_stmt_error(
                            $stmt_komponen
                        );

                } else {

                    $data_komponen =
                        mysqli_fetch_assoc(
                            $result_komponen
                        );
                }
            }


            mysqli_stmt_close(
                $stmt_komponen
            );
        }


        if (
            empty($error) &&
            !$data_komponen
        ) {

            $error =
                "Komponen yang dipilih tidak ditemukan.";

        } elseif (
            empty($error) &&
            $data_komponen
        ) {

            /* =============================================
               DATA UMUM
            ============================================= */

            $serial_number =
                trim(
                    (string)(
                        $data_komponen['serial_number']
                        ?? ''
                    )
                );


            $nama_bagian =
                trim(
                    (string)(
                        $data_komponen['nama_bagian']
                        ?? ''
                    )
                );


            $jenis_komponen =
                getJenisKomponen(
                    $data_komponen
                );


            $nama_mesin =
                trim(
                    (string)(
                        $data_komponen['nama_mesin']
                        ?? ''
                    )
                );


            $nama_sub_mesin =
                trim(
                    (string)(
                        $data_komponen['nama_sub_mesin']
                        ?? ''
                    )
                );


            $lokasi_penempatan =
                trim(
                    (string)(
                        $data_komponen['lokasi']
                        ?? ''
                    )
                );
        }
    }


    /* =====================================================
       UPLOAD FOTO
       MAKSIMAL 50 MB
       TANPA finfo_open()
    ===================================================== */

    $foto_nama = null;

    $foto_baru_path = "";


    if (
        empty($error) &&
        isset($_FILES['foto']) &&
        isset($_FILES['foto']['error']) &&
        $_FILES['foto']['error'] !==
        UPLOAD_ERR_NO_FILE
    ) {

        $file =
            $_FILES['foto'];


        /* =================================================
           CEK ERROR UPLOAD
        ================================================= */

        if (
            $file['error'] !==
            UPLOAD_ERR_OK
        ) {

            switch ($file['error']) {

                case UPLOAD_ERR_INI_SIZE:

                case UPLOAD_ERR_FORM_SIZE:

                    $error =
                        "Ukuran foto terlalu besar. Maksimal 50 MB.";

                    break;


                case UPLOAD_ERR_PARTIAL:

                    $error =
                        "Upload foto tidak selesai. Silakan coba lagi.";

                    break;


                case UPLOAD_ERR_NO_TMP_DIR:

                    $error =
                        "Folder temporary upload PHP tidak tersedia.";

                    break;


                case UPLOAD_ERR_CANT_WRITE:

                    $error =
                        "Foto gagal ditulis ke server.";

                    break;


                case UPLOAD_ERR_EXTENSION:

                    $error =
                        "Upload foto dihentikan oleh ekstensi PHP.";

                    break;


                default:

                    $error =
                        "Gagal mengupload foto. Kode error: " .
                        (int)$file['error'];

                    break;
            }
        }


        /* =================================================
           CEK UKURAN 50 MB
        ================================================= */

        if (empty($error)) {

            $fileSize =
                isset($file['size'])
                    ? (int)$file['size']
                    : 0;


            if (
                $fileSize <= 0
            ) {

                $error =
                    "File foto kosong atau tidak valid.";

            } elseif (
                $fileSize > $maxFotoSize
            ) {

                $error =
                    "Ukuran foto maksimal 50 MB.";
            }
        }


        /* =================================================
           CEK FILE UPLOAD
        ================================================= */

        if (empty($error)) {

            if (
                empty($file['tmp_name']) ||
                !is_uploaded_file(
                    $file['tmp_name']
                )
            ) {

                $error =
                    "File foto tidak valid.";
            }
        }


        /* =================================================
           VALIDASI ISI GAMBAR
           TANPA FILEINFO
        ================================================= */

        $mime_type = "";


        if (empty($error)) {

            $mime_type =
                detectImageMime(
                    $file['tmp_name']
                );


            if (
                $mime_type === false ||
                !isset(
                    $allowedFotoMime[
                        $mime_type
                    ]
                )
            ) {

                $error =
                    "Format foto harus JPG, PNG, atau WEBP.";
            }
        }


        /* =================================================
           VALIDASI TIPE GAMBAR LEBIH KETAT
        ================================================= */

        if (empty($error)) {

            $imageInfo =
                @getimagesize(
                    $file['tmp_name']
                );


            if (
                $imageInfo === false ||
                empty($imageInfo[0]) ||
                empty($imageInfo[1]) ||
                empty($imageInfo[2])
            ) {

                $error =
                    "File yang diupload bukan gambar yang valid.";

            } else {

                $detectedType =
                    (int)$imageInfo[2];


                $expectedTypes = [];


                if (
                    defined('IMAGETYPE_JPEG')
                ) {

                    $expectedTypes[
                        'image/jpeg'
                    ] =
                        IMAGETYPE_JPEG;
                }


                if (
                    defined('IMAGETYPE_PNG')
                ) {

                    $expectedTypes[
                        'image/png'
                    ] =
                        IMAGETYPE_PNG;
                }


                if (
                    defined('IMAGETYPE_WEBP')
                ) {

                    $expectedTypes[
                        'image/webp'
                    ] =
                        IMAGETYPE_WEBP;
                }


                if (
                    !isset(
                        $expectedTypes[
                            $mime_type
                        ]
                    ) ||
                    $detectedType !==
                    $expectedTypes[
                        $mime_type
                    ]
                ) {

                    $error =
                        "Isi file foto tidak sesuai dengan formatnya.";
                }
            }
        }


        /* =================================================
           BUAT FOLDER UPLOAD
        ================================================= */

        $target_dir =
            "../uploads/maintenance/";


        if (empty($error)) {

            if (!is_dir($target_dir)) {

                if (
                    !mkdir(
                        $target_dir,
                        0777,
                        true
                    )
                ) {

                    $error =
                        "Folder upload foto tidak dapat dibuat.";
                }
            }
        }


        /* =================================================
           CEK FOLDER BISA DITULIS
        ================================================= */

        if (empty($error)) {

            if (
                !is_dir($target_dir)
            ) {

                $error =
                    "Folder upload foto tidak tersedia.";

            } elseif (
                !is_writable($target_dir)
            ) {

                $error =
                    "Folder upload foto tidak dapat ditulis oleh server.";
            }
        }


        /* =================================================
           SIMPAN FOTO
        ================================================= */

        if (empty($error)) {

            $extension =
                $allowedFotoMime[
                    $mime_type
                ];


            /*
             * random_bytes() tersedia pada PHP 7+.
             * Fallback tetap disediakan.
             */

            try {

                if (
                    function_exists(
                        'random_bytes'
                    )
                ) {

                    $random =
                        bin2hex(
                            random_bytes(10)
                        );

                } else {

                    $random =
                        md5(
                            uniqid(
                                (string)mt_rand(),
                                true
                            )
                        );
                }

            } catch (Exception $e) {

                $random =
                    md5(
                        uniqid(
                            (string)mt_rand(),
                            true
                        )
                    );
            }


            $foto_nama =
                'maintenance_' .
                $id_komponen .
                '_' .
                time() .
                '_' .
                $random .
                '.' .
                $extension;


            $foto_baru_path =
                $target_dir .
                $foto_nama;


            if (
                !move_uploaded_file(
                    $file['tmp_name'],
                    $foto_baru_path
                )
            ) {

                $error =
                    "Gagal menyimpan foto ke server.";

                $foto_nama = null;

                $foto_baru_path = "";

            } else {

                /*
                 * Pastikan file benar-benar tersimpan.
                 */

                if (
                    !file_exists(
                        $foto_baru_path
                    ) ||
                    filesize(
                        $foto_baru_path
                    ) <= 0
                ) {

                    deleteUploadedFile(
                        $foto_baru_path
                    );


                    $foto_nama = null;

                    $foto_baru_path = "";


                    $error =
                        "Foto gagal disimpan dengan benar.";
                }
            }
        }
    }


    /* =====================================================
       SIMPAN KE DATABASE
    ===================================================== */

    if (empty($error)) {

        $tanggal_lengkap =
            $input_tanggal .
            ' ' .
            date('H:i:s');


        $sql_insert = "
            INSERT INTO riwayat_maintenance (

                id_komponen,
                tanggal,
                status,
                teknisi,
                tindakan,
                jenis,

                serial_number,
                nama_bagian,
                kategori,
                nama_mesin,
                nama_sub_mesin,
                lokasi_penempatan,

                brand,
                tipe,
                part_number,
                daya,
                io_address,
                input_voltage,
                frekuensi_input,

                arus_input,
                output,
                frekuensi_output,
                ip_rating,

                sparepart,
                catatan,
                gambar

            ) VALUES (

                ?,
                ?,
                ?,
                ?,
                ?,
                ?,

                ?,
                ?,
                ?,
                ?,
                ?,
                ?,

                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,

                ?,
                ?,
                ?,
                ?,

                ?,
                ?,
                ?

            )
        ";


        $stmt =
            mysqli_prepare(
                $conn,
                $sql_insert
            );


        if (!$stmt) {

            $error =
                "Gagal menyiapkan query database: " .
                mysqli_error($conn);

        } else {

            /*
             * 26 parameter:
             *
             * 1 integer
             * 25 string
             */

            $bind =
                mysqli_stmt_bind_param(
                    $stmt,
                    "isssssssssssssssssssssssss",

                    $id_komponen,
                    $tanggal_lengkap,
                    $status,
                    $teknisi,
                    $tindakan,
                    $jenis,

                    $serial_number,
                    $nama_bagian,
                    $jenis_komponen,
                    $nama_mesin,
                    $nama_sub_mesin,
                    $lokasi_penempatan,

                    $brand,
                    $tipe,
                    $part_number,
                    $daya,
                    $io_address,
                    $input_voltage,
                    $frekuensi_input,

                    $arus_input,
                    $output,
                    $frekuensi_output,
                    $ip_rating,

                    $sparepart,
                    $catatan,
                    $foto_nama
                );


            if (!$bind) {

                $error =
                    "Gagal menghubungkan data ke query: " .
                    mysqli_stmt_error($stmt);

            } elseif (
                !mysqli_stmt_execute($stmt)
            ) {

                $error =
                    "Gagal menyimpan data maintenance: " .
                    mysqli_stmt_error($stmt);

            } else {

                /* =========================================
                   UPDATE KONDISI KOMPONEN
                ========================================= */

                $kondisi_baru = "";


                if ($status === 'Selesai') {

                    $kondisi_baru =
                        'Baik';

                } elseif ($status === 'Proses') {

                    $kondisi_baru =
                        'Dalam Perbaikan';

                } elseif ($status === 'Pending') {

                    $kondisi_baru =
                        'Perlu Pemeriksaan';
                }


                if (
                    $kondisi_baru !== ''
                ) {

                    $stmt_update =
                        mysqli_prepare(
                            $conn,
                            "
                            UPDATE komponen
                            SET kondisi = ?
                            WHERE id = ?
                            "
                        );


                    if ($stmt_update) {

                        mysqli_stmt_bind_param(
                            $stmt_update,
                            "si",
                            $kondisi_baru,
                            $id_komponen
                        );


                        mysqli_stmt_execute(
                            $stmt_update
                        );


                        mysqli_stmt_close(
                            $stmt_update
                        );
                    }
                }


                mysqli_stmt_close(
                    $stmt
                );


                /*
                 * BERHASIL
                 */

                header(
                    "Location: index.php?simpan=berhasil"
                );

                exit;
            }


            /*
             * Kalau INSERT gagal,
             * hapus foto yang baru saja diupload.
             */

            if (!empty($error)) {

                mysqli_stmt_close(
                    $stmt
                );


                if (
                    !empty($foto_baru_path)
                ) {

                    deleteUploadedFile(
                        $foto_baru_path
                    );
                }
            }
        }
    }


    /* =====================================================
       PASTIKAN FOTO DIHAPUS JIKA ERROR
    ===================================================== */

    if (
        !empty($error) &&
        !empty($foto_baru_path)
    ) {

        deleteUploadedFile(
            $foto_baru_path
        );
    }
}


/* =========================================================
   AMBIL DATA KOMPONEN
========================================================= */

$q_komponen = mysqli_query(
    $conn,
    "
    SELECT
        k.*,
        sm.nama_sub_mesin,
        m.nama_mesin

    FROM komponen k

    LEFT JOIN sub_mesin sm
        ON k.id_sub_mesin = sm.id

    LEFT JOIN mesin m
        ON sm.id_mesin = m.id

    ORDER BY
        k.nama_bagian ASC
    "
);


if (!$q_komponen) {

    $error =
        "Gagal mengambil data komponen: " .
        mysqli_error($conn);
}


include "../template/header.php";
?>


<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
>

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
>


<style>

.maintenance-page {
    max-width: 100%;
}

.maintenance-card {
    border: 0;
    border-radius: 18px;
    box-shadow: 0 4px 18px rgba(0,0,0,.05);
}

.section-title {
    color: #0056a6;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .3px;
    text-transform: uppercase;
}

.form-label {
    font-size: 13px;
    margin-bottom: 6px;
}

.form-control,
.form-select {
    min-height: 40px;
}

textarea.form-control {
    min-height: auto;
}

.auto-field {
    background-color: #f8fafc !important;
    cursor: not-allowed;
}

.manual-field {
    background-color: #fff !important;
}

.manual-field:focus {
    border-color: #0056a6 !important;
    box-shadow: 0 0 0 .2rem rgba(0,86,166,.10) !important;
}

.select2-container {
    width: 100% !important;
}

.select2-container--bootstrap-5 .select2-selection {
    min-height: 40px !important;
    border-radius: 10px !important;
    border-color: #dee2e6 !important;
    padding-top: 3px;
}

.select2-container--bootstrap-5
.select2-selection__rendered {
    padding-left: 10px !important;
    line-height: 31px !important;
}

.photo-preview-wrapper {
    width: 100%;
    min-height: 180px;
    border: 1px dashed #cbd5e1;
    border-radius: 14px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

.photo-preview-wrapper img {
    max-width: 100%;
    max-height: 240px;
    width: auto;
    height: auto;
    object-fit: contain;
    border-radius: 10px;
}

.photo-empty {
    text-align: center;
    color: #94a3b8;
}

.photo-empty i {
    font-size: 42px;
    opacity: .45;
}

.btn-primary-garuda {
    background-color: #0056a6;
    border-color: #0056a6;
    color: #fff;
}

.btn-primary-garuda:hover {
    background-color: #004685;
    border-color: #004685;
    color: #fff;
}

@media (max-width: 767.98px) {

    .container-fluid {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }

    .maintenance-card {
        border-radius: 14px;
    }

    .maintenance-card .card-body {
        padding: 15px !important;
    }

    .page-header {
        padding: 15px !important;
    }

    .page-header h2 {
        font-size: 19px !important;
    }

    .submit-wrapper {
        flex-direction: column;
    }

    .submit-wrapper .btn {
        width: 100%;
    }

    .photo-preview-wrapper {
        min-height: 150px;
    }
}

</style>


<div class="container-fluid maintenance-page px-3 py-2">


    <!-- HEADER -->

    <div class="card maintenance-card bg-white p-4 mb-4 page-header">

        <div class="d-flex align-items-center gap-3">

            <a
                href="index.php"
                class="btn btn-outline-secondary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                style="width:42px;height:42px;"
                title="Kembali"
            >

                <i class="bi bi-arrow-left fs-5"></i>

            </a>


            <div class="min-w-0">

                <h2
                    class="fw-bold text-dark m-0"
                    style="font-size:23px;"
                >

                    Tambah Catatan Maintenance

                </h2>

                <p class="text-muted small m-0 mt-1">

                    Pilih komponen untuk mengambil
                    informasi komponen secara otomatis.

                </p>

            </div>

        </div>

    </div>



    <!-- FORM -->

    <div class="card maintenance-card bg-white mb-4">

        <div class="card-body p-4">


            <?php if (!empty($error)) : ?>

                <div class="alert alert-danger border-0 rounded-3">

                    <i class="bi bi-exclamation-triangle-fill me-2"></i>

                    <strong>Gagal menyimpan data</strong>

                    <div class="small mt-1">

                        <?= e($error); ?>

                    </div>

                </div>

            <?php endif; ?>


            <form
                method="POST"
                action=""
                enctype="multipart/form-data"
                id="maintenanceForm"
            >

                <input
                    type="hidden"
                    name="simpan_maintenance"
                    value="1"
                >


                <!-- PILIH KOMPONEN -->

                <div class="mb-4">

                    <div class="section-title mb-3">

                        <i class="bi bi-search me-2"></i>

                        Pilih Komponen / Part

                    </div>


                    <select
                        name="id_komponen"
                        id="select-komponen"
                        class="form-select"
                        required
                    >

                        <option value=""></option>


                        <?php if ($q_komponen) : ?>

                            <?php while (
                                $k =
                                mysqli_fetch_assoc($q_komponen)
                            ) : ?>

                                <?php

                                $jenis_data =
                                    getJenisKomponen($k);


                                $label =
                                    ($k['nama_bagian'] ?? 'Tanpa Nama') .
                                    ' — [SN: ' .
                                    (
                                        !empty($k['serial_number'])
                                            ? $k['serial_number']
                                            : '-'
                                    ) .
                                    '] (Mesin: ' .
                                    (
                                        !empty($k['nama_mesin'])
                                            ? $k['nama_mesin']
                                            : 'Tanpa Mesin'
                                    ) .
                                    ')';

                                ?>

                                <option
                                    value="<?= e($k['id']); ?>"
                                    data-sn="<?= e($k['serial_number'] ?? ''); ?>"
                                    data-namabagian="<?= e($k['nama_bagian'] ?? ''); ?>"
                                    data-jenis-komponen="<?= e($jenis_data); ?>"
                                    data-mesin="<?= e($k['nama_mesin'] ?? ''); ?>"
                                    data-submesin="<?= e($k['nama_sub_mesin'] ?? ''); ?>"
                                    data-lokasi="<?= e($k['lokasi'] ?? ''); ?>"
                                >

                                    <?= e($label); ?>

                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>


                    <div class="form-text">

                        Ketik nama komponen, nama bagian,
                        serial number, atau nama mesin.

                    </div>

                </div>


                <hr class="my-4 border-light-subtle">


                <!-- INFORMASI UMUM -->

                <div class="mb-4">

                    <div class="section-title mb-3">

                        <i class="bi bi-info-circle me-2"></i>

                        Informasi Umum

                    </div>


                    <div class="row g-3">


                        <div class="col-12 col-md-4">

                            <label class="form-label fw-semibold">
                                Serial Number (SN)
                            </label>

                            <input
                                type="text"
                                name="serial_number"
                                id="f-sn"
                                class="form-control rounded-3 auto-field"
                                readonly
                            >

                        </div>


                        <div class="col-12 col-md-4">

                            <label class="form-label fw-semibold">

                                Nama Bagian
                                <span class="text-danger">*</span>

                            </label>

                            <input
                                type="text"
                                name="nama_bagian"
                                id="f-namabagian"
                                class="form-control rounded-3 auto-field"
                                readonly
                                required
                            >

                        </div>


                        <div class="col-12 col-md-4">

                            <label class="form-label fw-semibold">

                                Jenis Komponen

                            </label>

                            <input
                                type="text"
                                name="kategori"
                                id="f-jenis-komponen"
                                class="form-control rounded-3 auto-field"
                                readonly
                                placeholder="Jenis komponen akan muncul otomatis"
                            >

                        </div>


                        <div class="col-12 col-md-4">

                            <label class="form-label fw-semibold">
                                Mesin Induk
                            </label>

                            <input
                                type="text"
                                name="nama_mesin"
                                id="f-mesin"
                                class="form-control rounded-3 auto-field"
                                readonly
                            >

                        </div>


                        <div class="col-12 col-md-4">

                            <label class="form-label fw-semibold">
                                Sub Mesin
                            </label>

                            <input
                                type="text"
                                name="nama_sub_mesin"
                                id="f-submesin"
                                class="form-control rounded-3 auto-field"
                                readonly
                            >

                        </div>


                        <div class="col-12 col-md-4">

                            <label class="form-label fw-semibold">
                                Lokasi Penempatan
                            </label>

                            <input
                                type="text"
                                name="lokasi_penempatan"
                                id="f-lokasi"
                                class="form-control rounded-3 auto-field"
                                readonly
                            >

                        </div>

                    </div>

                </div>


                <!-- FOTO -->

                <div class="mb-4">

                    <div class="section-title mb-3">

                        <i class="bi bi-camera me-2"></i>

                        Dokumentasi Maintenance

                    </div>


                    <div class="row g-3">


                        <div class="col-12 col-md-7">

                            <label class="form-label fw-semibold">

                                Foto Dokumentasi / Bukti Maintenance

                            </label>

                            <input
                                type="file"
                                name="foto"
                                id="input-foto"
                                class="form-control rounded-3"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            >

                            <div class="form-text">

                                Format JPG, PNG, atau WEBP.
                                Maksimal 50 MB.

                            </div>

                        </div>


                        <div class="col-12 col-md-5">

                            <div
                                class="photo-preview-wrapper"
                                id="preview-container"
                            >

                                <div
                                    class="photo-empty"
                                    id="photo-empty"
                                >

                                    <i class="bi bi-image d-block"></i>

                                    <small>
                                        Preview foto
                                    </small>

                                </div>


                                <img
                                    src=""
                                    id="image-preview"
                                    alt="Preview Foto"
                                    class="d-none"
                                >


                                <button
                                    type="button"
                                    id="btn-remove-foto"
                                    class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 rounded-circle d-none"
                                    style="width:32px;height:32px;"
                                >

                                    <i class="bi bi-x-lg"></i>

                                </button>

                            </div>

                        </div>

                    </div>

                </div>


                <hr class="my-4 border-light-subtle">


                <!-- SPESIFIKASI -->

                <div class="mb-4">

                    <div class="section-title mb-3">

                        <i class="bi bi-cpu me-2"></i>

                        Spesifikasi & Brand

                    </div>


                    <div class="alert alert-info border-0 rounded-3 small">

                        <i class="bi bi-info-circle me-1"></i>

                        Spesifikasi dapat diisi secara manual.

                    </div>


                    <div class="row g-3">

                        <div class="col-12 col-md-4">

                            <label class="form-label fw-semibold">
                                Brand / Merk
                            </label>

                            <input
                                type="text"
                                name="brand"
                                class="form-control rounded-3 manual-field"
                                value="<?= e($_POST['brand'] ?? ''); ?>"
                                placeholder="Contoh: Danfoss"
                            >

                        </div>


                        <div class="col-12 col-md-4">

                            <label class="form-label fw-semibold">
                                Tipe
                            </label>

                            <input
                                type="text"
                                name="tipe"
                                class="form-control rounded-3 manual-field"
                                value="<?= e($_POST['tipe'] ?? ''); ?>"
                                placeholder="Contoh: FC-301"
                            >

                        </div>


                        <div class="col-12 col-md-4">

                            <label class="form-label fw-semibold">
                                Part Number
                            </label>

                            <input
                                type="text"
                                name="part_number"
                                class="form-control rounded-3 manual-field"
                                value="<?= e($_POST['part_number'] ?? ''); ?>"
                                placeholder="Part Number"
                            >

                        </div>


                        <div class="col-12 col-md-3">

                            <label class="form-label fw-semibold">
                                Daya
                            </label>

                            <input
                                type="text"
                                name="daya"
                                class="form-control rounded-3 manual-field"
                                value="<?= e($_POST['daya'] ?? ''); ?>"
                                placeholder="0.55 kW"
                            >

                        </div>


                        <div class="col-12 col-md-3">

                            <label class="form-label fw-semibold">
                                IO Address
                            </label>

                            <input
                                type="text"
                                name="io_address"
                                class="form-control rounded-3 manual-field"
                                value="<?= e($_POST['io_address'] ?? ''); ?>"
                                placeholder="I0.0 / Q0.0"
                            >

                        </div>


                        <div class="col-12 col-md-3">

                            <label class="form-label fw-semibold">
                                Input Voltage
                            </label>

                            <input
                                type="text"
                                name="input_voltage"
                                class="form-control rounded-3 manual-field"
                                value="<?= e($_POST['input_voltage'] ?? ''); ?>"
                                placeholder="3x380-480 VAC"
                            >

                        </div>


                        <div class="col-12 col-md-3">

                            <label class="form-label fw-semibold">
                                Frekuensi Input
                            </label>

                            <input
                                type="text"
                                name="frekuensi_input"
                                class="form-control rounded-3 manual-field"
                                value="<?= e($_POST['frekuensi_input'] ?? ''); ?>"
                                placeholder="50/60 Hz"
                            >

                        </div>


                        <div class="col-12 col-md-3">

                            <label class="form-label fw-semibold">
                                Arus Input
                            </label>

                            <input
                                type="text"
                                name="arus_input"
                                class="form-control rounded-3 manual-field"
                                value="<?= e($_POST['arus_input'] ?? ''); ?>"
                                placeholder="1.5 A"
                            >

                        </div>


                        <div class="col-12 col-md-3">

                            <label class="form-label fw-semibold">
                                Output
                            </label>

                            <input
                                type="text"
                                name="output"
                                class="form-control rounded-3 manual-field"
                                value="<?= e($_POST['output'] ?? ''); ?>"
                                placeholder="3x0-Vin"
                            >

                        </div>


                        <div class="col-12 col-md-3">

                            <label class="form-label fw-semibold">
                                Frekuensi Output
                            </label>

                            <input
                                type="text"
                                name="frekuensi_output"
                                class="form-control rounded-3 manual-field"
                                value="<?= e($_POST['frekuensi_output'] ?? ''); ?>"
                                placeholder="0-590 Hz"
                            >

                        </div>


                        <div class="col-12 col-md-3">

                            <label class="form-label fw-semibold">
                                IP Rating
                            </label>

                            <input
                                type="text"
                                name="ip_rating"
                                class="form-control rounded-3 manual-field"
                                value="<?= e($_POST['ip_rating'] ?? ''); ?>"
                                placeholder="IP20"
                            >

                        </div>

                    </div>

                </div>


                <hr class="my-4 border-light-subtle">


                <!-- MAINTENANCE -->

                <div class="mb-4">

                    <div class="section-title mb-3">

                        <i class="bi bi-tools me-2"></i>

                        Status & Tindakan Maintenance

                    </div>


                    <div class="row g-3">


                        <div class="col-12 col-md-4">

                            <label class="form-label fw-semibold">

                                Tanggal Maintenance
                                <span class="text-danger">*</span>

                            </label>

                            <input
                                type="date"
                                name="tanggal"
                                class="form-control rounded-3"
                                value="<?= e($_POST['tanggal'] ?? date('Y-m-d')); ?>"
                                required
                            >

                        </div>


                        <div class="col-12 col-md-4">

                            <label class="form-label fw-semibold">

                                Jenis Maintenance

                            </label>

                            <select
                                name="jenis"
                                class="form-select rounded-3"
                            >

                                <?php
                                $jenis_post =
                                    $_POST['jenis']
                                    ?? 'Preventive';
                                ?>

                                <option
                                    value="Preventive"
                                    <?= $jenis_post === 'Preventive' ? 'selected' : ''; ?>
                                >
                                    Preventive
                                </option>

                                <option
                                    value="Corrective"
                                    <?= $jenis_post === 'Corrective' ? 'selected' : ''; ?>
                                >
                                    Corrective
                                </option>

                                <option
                                    value="Breakdown"
                                    <?= $jenis_post === 'Breakdown' ? 'selected' : ''; ?>
                                >
                                    Breakdown
                                </option>

                                <option
                                    value="Predictive"
                                    <?= $jenis_post === 'Predictive' ? 'selected' : ''; ?>
                                >
                                    Predictive
                                </option>

                            </select>

                        </div>


                        <div class="col-12 col-md-4">

                            <label class="form-label fw-semibold">

                                Status Pekerjaan
                                <span class="text-danger">*</span>

                            </label>

                            <select
                                name="status"
                                class="form-select rounded-3"
                                required
                            >

                                <?php
                                $status_post =
                                    $_POST['status']
                                    ?? 'Selesai';
                                ?>

                                <option
                                    value="Selesai"
                                    <?= $status_post === 'Selesai' ? 'selected' : ''; ?>
                                >
                                    Selesai
                                </option>

                                <option
                                    value="Proses"
                                    <?= $status_post === 'Proses' ? 'selected' : ''; ?>
                                >
                                    Proses
                                </option>

                                <option
                                    value="Pending"
                                    <?= $status_post === 'Pending' ? 'selected' : ''; ?>
                                >
                                    Pending
                                </option>

                            </select>

                        </div>


                        <div class="col-12">

                            <label class="form-label fw-semibold">
                                Teknisi / Petugas
                            </label>

                            <input
                                type="text"
                                name="teknisi"
                                class="form-control rounded-3"
                                value="<?= e($_POST['teknisi'] ?? ''); ?>"
                                placeholder="Nama Teknisi / Tim Maintenance"
                            >

                        </div>


                        <div class="col-12">

                            <label class="form-label fw-semibold">

                                Tindakan Perbaikan / Maintenance
                                <span class="text-danger">*</span>

                            </label>

                            <textarea
                                name="tindakan"
                                class="form-control rounded-3"
                                rows="4"
                                placeholder="Rincian pekerjaan yang dilakukan..."
                                required
                            ><?= e($_POST['tindakan'] ?? ''); ?></textarea>

                        </div>


                        <div class="col-12">

                            <label class="form-label fw-semibold">

                                Sparepart yang Digunakan / Diganti

                            </label>

                            <input
                                type="text"
                                name="sparepart"
                                class="form-control rounded-3"
                                value="<?= e($_POST['sparepart'] ?? ''); ?>"
                                placeholder="Contoh: Bearing 6204 (1 Pcs)"
                            >

                        </div>


                        <div class="col-12">

                            <label class="form-label fw-semibold">

                                Catatan / Keterangan Tambahan

                            </label>

                            <textarea
                                name="catatan"
                                class="form-control rounded-3"
                                rows="3"
                                placeholder="Catatan lanjutan..."
                            ><?= e($_POST['catatan'] ?? ''); ?></textarea>

                        </div>

                    </div>

                </div>


                <!-- BUTTON -->

                <div class="border-top pt-4 mt-4 d-flex gap-2 submit-wrapper">

                    <button
                        type="submit"
                        class="btn btn-primary-garuda fw-semibold px-4 rounded-3"
                        id="btn-submit"
                    >

                        <i class="bi bi-save me-1"></i>

                        Simpan Data Maintenance

                    </button>


                    <a
                        href="index.php"
                        class="btn btn-light border px-4 rounded-3 fw-semibold"
                    >

                        Batal

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>


<script
    src="https://code.jquery.com/jquery-3.7.1.min.js"
></script>

<script
    src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"
></script>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* =====================================================
           SELECT2
        ===================================================== */

        $('#select-komponen').select2({

            theme: 'bootstrap-5',

            placeholder:
                '-- Ketik untuk mencari Komponen / Serial Number --',

            allowClear: true,

            width: '100%'

        });


        /* =====================================================
           KOMPONEN DIPILIH
        ===================================================== */

        $('#select-komponen').on(
            'change',
            function () {

                const option =
                    $(this).find(':selected');


                if (
                    !option ||
                    !option.val()
                ) {

                    clearComponentFields();

                    return;
                }


                setField(
                    '#f-sn',
                    option.attr('data-sn')
                );


                setField(
                    '#f-namabagian',
                    option.attr('data-namabagian')
                );


                const jenisKomponen =
                    option.attr(
                        'data-jenis-komponen'
                    ) || '';


                setField(
                    '#f-jenis-komponen',
                    jenisKomponen
                );


                setField(
                    '#f-mesin',
                    option.attr('data-mesin')
                );


                setField(
                    '#f-submesin',
                    option.attr('data-submesin')
                );


                setField(
                    '#f-lokasi',
                    option.attr('data-lokasi')
                );

            }
        );


        /* =====================================================
           FOTO
           MAKSIMAL 50 MB
        ===================================================== */

        const inputFoto =
            document.getElementById(
                'input-foto'
            );

        const imagePreview =
            document.getElementById(
                'image-preview'
            );

        const photoEmpty =
            document.getElementById(
                'photo-empty'
            );

        const btnRemoveFoto =
            document.getElementById(
                'btn-remove-foto'
            );


        if (inputFoto) {

            inputFoto.addEventListener(
                'change',
                function () {

                    const file =
                        this.files &&
                        this.files[0];


                    if (!file) {

                        resetPhoto();

                        return;
                    }


                    /*
                     * 50 MB
                     */

                    const maxFotoSize =
                        50 * 1024 * 1024;


                    if (
                        file.size >
                        maxFotoSize
                    ) {

                        alert(
                            'Ukuran foto maksimal 50 MB.'
                        );

                        this.value = '';

                        resetPhoto();

                        return;
                    }


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
                            'Format foto harus JPG, PNG, atau WEBP.'
                        );

                        this.value = '';

                        resetPhoto();

                        return;
                    }


                    const reader =
                        new FileReader();


                    reader.onload =
                        function (event) {

                            imagePreview.src =
                                event.target.result;

                            imagePreview.classList.remove(
                                'd-none'
                            );

                            photoEmpty.classList.add(
                                'd-none'
                            );

                            btnRemoveFoto.classList.remove(
                                'd-none'
                            );

                        };


                    reader.onerror =
                        function () {

                            alert(
                                'Foto tidak dapat dibaca oleh browser.'
                            );

                            inputFoto.value = '';

                            resetPhoto();
                        };


                    reader.readAsDataURL(
                        file
                    );

                }
            );

        }


        /* =====================================================
           REMOVE FOTO
        ===================================================== */

        if (btnRemoveFoto) {

            btnRemoveFoto.addEventListener(
                'click',
                function () {

                    if (inputFoto) {

                        inputFoto.value = '';

                    }

                    resetPhoto();

                }
            );

        }


        /* =====================================================
           SUBMIT
        ===================================================== */

        const form =
            document.getElementById(
                'maintenanceForm'
            );

        const submitButton =
            document.getElementById(
                'btn-submit'
            );


        if (form) {

            form.addEventListener(
                'submit',
                function (event) {

                    const selected =
                        document.getElementById(
                            'select-komponen'
                        );


                    if (
                        !selected ||
                        !selected.value
                    ) {

                        event.preventDefault();

                        alert(
                            'Silakan pilih komponen terlebih dahulu.'
                        );

                        return;
                    }


                    const tindakan =
                        form.querySelector(
                            '[name="tindakan"]'
                        );


                    if (
                        !tindakan ||
                        !tindakan.value.trim()
                    ) {

                        event.preventDefault();

                        alert(
                            'Tindakan maintenance wajib diisi.'
                        );

                        tindakan.focus();

                        return;
                    }


                    /*
                     * Cek lagi ukuran foto sebelum dikirim.
                     */

                    if (
                        inputFoto &&
                        inputFoto.files &&
                        inputFoto.files[0]
                    ) {

                        const file =
                            inputFoto.files[0];


                        const maxFotoSize =
                            50 * 1024 * 1024;


                        if (
                            file.size >
                            maxFotoSize
                        ) {

                            event.preventDefault();

                            alert(
                                'Ukuran foto maksimal 50 MB.'
                            );

                            inputFoto.value = '';

                            resetPhoto();

                            return;
                        }
                    }


                    if (submitButton) {

                        submitButton.disabled =
                            true;

                        submitButton.innerHTML =
                            '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';

                    }

                }
            );

        }


        /* =====================================================
           HELPER
        ===================================================== */

        function setField(
            selector,
            value
        ) {

            const element =
                document.querySelector(
                    selector
                );


            if (element) {

                element.value =
                    value || '';

            }

        }


        function clearComponentFields()
        {

            const fields = [

                '#f-sn',

                '#f-namabagian',

                '#f-jenis-komponen',

                '#f-mesin',

                '#f-submesin',

                '#f-lokasi'

            ];


            fields.forEach(
                function (selector) {

                    setField(
                        selector,
                        ''
                    );

                }
            );

        }


        function resetPhoto()
        {

            if (imagePreview) {

                imagePreview.src = '';

                imagePreview.classList.add(
                    'd-none'
                );

            }


            if (photoEmpty) {

                photoEmpty.classList.remove(
                    'd-none'
                );

            }


            if (btnRemoveFoto) {

                btnRemoveFoto.classList.add(
                    'd-none'
                );

            }

        }

    }
);

</script>


<?php include "../template/footer.php"; ?>