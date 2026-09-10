<?php
require_once __DIR__ . '/../template/auth.php';
require_user_role();


/* =========================================================
   TAMBAH SUB MESIN
   Maksimal upload foto: 50 MB
========================================================= */

mysqli_report(MYSQLI_REPORT_OFF);

include "../koneksi.php";


/* =========================================================
   KONFIGURASI UPLOAD
========================================================= */

$maxUploadSize = 50 * 1024 * 1024; // 50 MB

$allowedMime = array(
    'image/jpeg',
    'image/png',
    'image/webp'
);

$allowedExt = array(
    'jpg',
    'jpeg',
    'png',
    'webp'
);

$error = '';

$uploadedFilePath = '';

$nama_gambar = null;


/* =========================================================
   HELPER
========================================================= */

function submesin_e($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


function submesin_filename($extension)
{
    return 'submesin_'
        . date('YmdHis')
        . '_'
        . mt_rand(100000, 999999)
        . '.'
        . $extension;
}


function submesin_upload_error_message($code)
{
    switch ((int)$code) {

        case UPLOAD_ERR_INI_SIZE:
            return 'Upload foto gagal karena ukuran foto melebihi batas upload server. '
                . 'Batas server saat ini: '
                . ini_get('upload_max_filesize')
                . '. Maksimal aplikasi: 50 MB.';

        case UPLOAD_ERR_FORM_SIZE:
            return 'Upload foto gagal karena ukuran foto melebihi batas yang ditentukan form.';

        case UPLOAD_ERR_PARTIAL:
            return 'Upload foto tidak selesai. Silakan coba upload kembali.';

        case UPLOAD_ERR_NO_FILE:
            return 'Tidak ada foto yang dipilih.';

        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Upload foto gagal karena folder temporary server tidak tersedia. '
                . 'Silakan hubungi administrator hosting.';

        case UPLOAD_ERR_CANT_WRITE:
            return 'Upload foto gagal karena server tidak dapat menulis file. '
                . 'Periksa permission folder upload.';

        case UPLOAD_ERR_EXTENSION:
            return 'Upload foto dihentikan oleh ekstensi PHP di server.';

        default:
            return 'Upload foto gagal. Kode error PHP: ' . (int)$code;
    }
}


/* =========================================================
   NILAI FORM
========================================================= */

$val_id_mesin = isset($_POST['id_mesin'])
    ? (int)$_POST['id_mesin']
    : 0;

$val_nama_sub_mesin = isset($_POST['nama_sub_mesin'])
    ? trim($_POST['nama_sub_mesin'])
    : '';

$val_serial_number = isset($_POST['serial_number'])
    ? trim($_POST['serial_number'])
    : '';

$val_keterangan = isset($_POST['keterangan'])
    ? trim($_POST['keterangan'])
    : '';


/* =========================================================
   PROSES SIMPAN
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        /* -------------------------------------------------
           CEK KONEKSI
        ------------------------------------------------- */

        if (
            !isset($conn)
            || !($conn instanceof mysqli)
        ) {

            throw new Exception(
                'Koneksi database ($conn) tidak tersedia.'
            );
        }


        /* -------------------------------------------------
           VALIDASI MESIN INDUK
        ------------------------------------------------- */

        if ($val_id_mesin <= 0) {

            throw new Exception(
                'Mesin Induk wajib dipilih.'
            );
        }


        /* -------------------------------------------------
           VALIDASI NAMA SUB MESIN
        ------------------------------------------------- */

        if ($val_nama_sub_mesin === '') {

            throw new Exception(
                'Nama Sub Mesin wajib diisi.'
            );
        }


        /*
         * Sesuai struktur database sebelumnya:
         * nama_sub_mesin maksimal 100 karakter.
         */
        if (strlen($val_nama_sub_mesin) > 100) {

            throw new Exception(
                'Nama Sub Mesin maksimal 100 karakter.'
            );
        }


        /* -------------------------------------------------
           VALIDASI SERIAL NUMBER
        ------------------------------------------------- */

        if (strlen($val_serial_number) > 100) {

            throw new Exception(
                'Serial Number maksimal 100 karakter.'
            );
        }


        /* -------------------------------------------------
           CEK MESIN INDUK
        ------------------------------------------------- */

        $idMesinEsc = mysqli_real_escape_string(
            $conn,
            (string)$val_id_mesin
        );

        $cekMesin = mysqli_query(
            $conn,
            "SELECT id
             FROM mesin
             WHERE id = {$idMesinEsc}
             LIMIT 1"
        );


        if ($cekMesin === false) {

            throw new Exception(
                'Gagal memeriksa Mesin Induk: '
                . mysqli_error($conn)
            );
        }


        if (mysqli_num_rows($cekMesin) === 0) {

            throw new Exception(
                'Mesin Induk yang dipilih tidak ditemukan di database.'
            );
        }


        /* =================================================
           FOTO SUB MESIN
        ================================================= */

        if (
            isset($_FILES['gambar'])
            && isset($_FILES['gambar']['error'])
        ) {

            $fileError = (int)$_FILES['gambar']['error'];


            /* -------------------------------------------------
               TIDAK ADA FILE
            ------------------------------------------------- */

            if ($fileError === UPLOAD_ERR_NO_FILE) {

                /*
                 * Foto bersifat opsional.
                 * Tidak melakukan apa-apa.
                 */

            } else {

                /* -------------------------------------------------
                   CEK ERROR UPLOAD PHP
                ------------------------------------------------- */

                if ($fileError !== UPLOAD_ERR_OK) {

                    throw new Exception(
                        submesin_upload_error_message(
                            $fileError
                        )
                    );
                }


                /* -------------------------------------------------
                   AMBIL DATA FILE
                ------------------------------------------------- */

                $tmpName = isset($_FILES['gambar']['tmp_name'])
                    ? $_FILES['gambar']['tmp_name']
                    : '';

                $originalName = isset($_FILES['gambar']['name'])
                    ? $_FILES['gambar']['name']
                    : '';

                $fileSize = isset($_FILES['gambar']['size'])
                    ? (int)$_FILES['gambar']['size']
                    : 0;


                /* -------------------------------------------------
                   FILE VALID
                ------------------------------------------------- */

                if (
                    $tmpName === ''
                    || !is_uploaded_file($tmpName)
                ) {

                    throw new Exception(
                        'File foto tidak valid.'
                    );
                }


                /* -------------------------------------------------
                   CEK FILE KOSONG
                ------------------------------------------------- */

                if ($fileSize <= 0) {

                    throw new Exception(
                        'File foto kosong.'
                    );
                }


                /* -------------------------------------------------
                   CEK MAKSIMAL 50 MB
                ------------------------------------------------- */

                if ($fileSize > $maxUploadSize) {

                    throw new Exception(
                        'Ukuran foto terlalu besar. '
                        . 'Maksimal 50 MB.'
                    );
                }


                /* -------------------------------------------------
                   CEK EXTENSION
                ------------------------------------------------- */

                $extension = strtolower(
                    pathinfo(
                        $originalName,
                        PATHINFO_EXTENSION
                    )
                );


                if (
                    !in_array(
                        $extension,
                        $allowedExt,
                        true
                    )
                ) {

                    throw new Exception(
                        'Format foto tidak valid. '
                        . 'Gunakan JPG, JPEG, PNG atau WEBP.'
                    );
                }


                /* -------------------------------------------------
                   CEK GAMBAR DENGAN getimagesize
                ------------------------------------------------- */

                $imageInfo = @getimagesize($tmpName);


                if ($imageInfo === false) {

                    throw new Exception(
                        'File yang dipilih bukan gambar yang valid.'
                    );
                }


                /* -------------------------------------------------
                   CEK MIME TYPE
                ------------------------------------------------- */

                $mime = '';


                if (function_exists('finfo_open')) {

                    $finfo = @finfo_open(
                        FILEINFO_MIME_TYPE
                    );


                    if ($finfo) {

                        $mime = strtolower(
                            (string)@finfo_file(
                                $finfo,
                                $tmpName
                            )
                        );


                        @finfo_close($finfo);
                    }
                }


                if (
                    $mime !== ''
                    && !in_array(
                        $mime,
                        $allowedMime,
                        true
                    )
                ) {

                    throw new Exception(
                        'Tipe file gambar tidak valid. '
                        . 'Gunakan JPG, JPEG, PNG atau WEBP.'
                    );
                }


                /* -------------------------------------------------
                   CEK DIMENSI GAMBAR
                ------------------------------------------------- */

                $imageWidth = isset($imageInfo[0])
                    ? (int)$imageInfo[0]
                    : 0;

                $imageHeight = isset($imageInfo[1])
                    ? (int)$imageInfo[1]
                    : 0;


                if (
                    $imageWidth <= 0
                    || $imageHeight <= 0
                ) {

                    throw new Exception(
                        'Dimensi gambar tidak valid.'
                    );
                }


                /* -------------------------------------------------
                   FOLDER UPLOAD
                ------------------------------------------------- */

                $uploadDir =
                    __DIR__
                    . '/../uploads/sub_mesin/';


                /*
                 * Jika folder belum ada,
                 * otomatis dibuat.
                 */
                if (!is_dir($uploadDir)) {

                    if (
                        !@mkdir(
                            $uploadDir,
                            0755,
                            true
                        )
                    ) {

                        throw new Exception(
                            'Folder uploads/sub_mesin tidak dapat dibuat.'
                        );
                    }
                }


                /* -------------------------------------------------
                   CEK FOLDER BISA DITULIS
                ------------------------------------------------- */

                if (!is_writable($uploadDir)) {

                    throw new Exception(
                        'Folder uploads/sub_mesin tidak dapat ditulis oleh server. '
                        . 'Silakan periksa permission folder.'
                    );
                }


                /* -------------------------------------------------
                   BUAT NAMA FILE
                ------------------------------------------------- */

                $nama_gambar =
                    submesin_filename(
                        $extension
                    );


                $uploadedFilePath =
                    $uploadDir
                    . $nama_gambar;


                /* -------------------------------------------------
                   PINDAHKAN FILE
                ------------------------------------------------- */

                if (
                    !@move_uploaded_file(
                        $tmpName,
                        $uploadedFilePath
                    )
                ) {

                    throw new Exception(
                        'Foto gagal dipindahkan ke folder uploads/sub_mesin.'
                    );
                }


                /* -------------------------------------------------
                   PASTIKAN FILE BENAR-BENAR ADA
                ------------------------------------------------- */

                if (
                    !is_file(
                        $uploadedFilePath
                    )
                ) {

                    throw new Exception(
                        'Foto gagal disimpan di server.'
                    );
                }
            }
        }


        /* =================================================
           SIAPKAN DATA INSERT
        ================================================= */

        $idMesinSql = (int)$val_id_mesin;


        $namaSql = mysqli_real_escape_string(
            $conn,
            $val_nama_sub_mesin
        );


        if ($val_serial_number === '') {

            $serialSql = 'NULL';

        } else {

            $serialSql =
                "'"
                . mysqli_real_escape_string(
                    $conn,
                    $val_serial_number
                )
                . "'";
        }


        if ($val_keterangan === '') {

            $ketSql = 'NULL';

        } else {

            $ketSql =
                "'"
                . mysqli_real_escape_string(
                    $conn,
                    $val_keterangan
                )
                . "'";
        }


        if ($nama_gambar === null) {

            $gambarSql = 'NULL';

        } else {

            $gambarSql =
                "'"
                . mysqli_real_escape_string(
                    $conn,
                    $nama_gambar
                )
                . "'";
        }


        /* =================================================
           INSERT DATABASE
        ================================================= */

        $sql = "
            INSERT INTO sub_mesin
            (
                id_mesin,
                nama_sub_mesin,
                serial_number,
                keterangan,
                gambar
            )
            VALUES
            (
                {$idMesinSql},
                '{$namaSql}',
                {$serialSql},
                {$ketSql},
                {$gambarSql}
            )
        ";


        $insert = mysqli_query(
            $conn,
            $sql
        );


        if ($insert === false) {

            throw new Exception(
                'Gagal menyimpan Sub Mesin: '
                . mysqli_error($conn)
            );
        }


        /* =================================================
           BERHASIL
        ================================================= */

        header(
            'Location: index.php?simpan=berhasil'
        );

        exit;


    } catch (Throwable $ex) {

        $error = $ex->getMessage();


        /* -------------------------------------------------
           HAPUS FOTO JIKA DATABASE GAGAL
        ------------------------------------------------- */

        if (
            $uploadedFilePath !== ''
            && is_file($uploadedFilePath)
        ) {

            @unlink(
                $uploadedFilePath
            );
        }
    }
}


/* =========================================================
   DATA MESIN UNTUK DROPDOWN
========================================================= */

$q_mesin = mysqli_query(
    $conn,
    'SELECT id, nama_mesin, serial_number
     FROM mesin
     ORDER BY nama_mesin ASC'
);


if (
    $q_mesin === false
    && $error === ''
) {

    $error =
        'Gagal mengambil data Mesin Induk: '
        . mysqli_error($conn);
}


/* =========================================================
   HEADER
========================================================= */

include "../template/header.php";

?>

<style>

/* =========================================================
   PAGE
========================================================= */

.submachine-add-page {
    width: 100%;
}


/* =========================================================
   PAGE HEADER
========================================================= */

.submachine-add-header {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    padding: 18px 22px;

    margin-bottom: 20px;

    box-shadow:
        0 3px 12px rgba(15, 23, 42, .04);
}


.submachine-add-header-inner {

    display: flex;

    align-items: center;

    gap: 14px;
}


.submachine-back {

    width: 42px;

    height: 42px;

    flex-shrink: 0;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    background: #f8fafc;

    border: 1px solid #e2e8f0;

    color: #475569;

    text-decoration: none;

    transition: .2s;
}


.submachine-back:hover {

    background: #005baa;

    border-color: #005baa;

    color: #fff;

    transform: translateX(-2px);
}


.submachine-add-title {

    margin: 0;

    color: #172033;

    font-size: 22px;

    font-weight: 800;
}


.submachine-add-subtitle {

    color: #64748b;

    font-size: 12px;

    margin-top: 3px;
}


/* =========================================================
   MAIN FORM CARD
========================================================= */

.submachine-form-card {

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    overflow: hidden;

    box-shadow:
        0 3px 12px rgba(15, 23, 42, .04);
}


/* =========================================================
   FORM HEADER
========================================================= */

.submachine-form-header {

    padding: 17px 20px;

    border-bottom: 1px solid #e5e7eb;

    display: flex;

    align-items: center;

    gap: 10px;
}


.submachine-form-icon {

    width: 38px;

    height: 38px;

    border-radius: 10px;

    background: #eef5ff;

    color: #005baa;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 18px;
}


.submachine-form-title {

    margin: 0;

    color: #172033;

    font-size: 16px;

    font-weight: 750;
}


.submachine-form-subtitle {

    color: #94a3b8;

    font-size: 11px;

    margin-top: 2px;
}


/* =========================================================
   FORM BODY
========================================================= */

.submachine-form-body {

    padding: 24px;
}


/* =========================================================
   FORM LABEL
========================================================= */

.submachine-label {

    display: block;

    color: #334155;

    font-size: 13px;

    font-weight: 700;

    margin-bottom: 7px;
}


.submachine-label .required {

    color: #dc2626;
}


/* =========================================================
   INPUT
========================================================= */

.submachine-input,
.submachine-select,
.submachine-textarea {

    width: 100%;

    border: 1px solid #dbe3ea;

    border-radius: 9px;

    background: #fff;

    color: #172033;

    font-size: 13px;

    transition: .2s;
}


.submachine-input,
.submachine-select {

    min-height: 43px;

    padding: 9px 12px;
}


.submachine-textarea {

    padding: 11px 12px;

    resize: vertical;

    min-height: 110px;
}


.submachine-input:focus,
.submachine-select:focus,
.submachine-textarea:focus {

    border-color: #0076c8;

    outline: none;

    box-shadow:
        0 0 0 3px rgba(0, 118, 200, .08);
}


/* =========================================================
   INPUT GROUP
========================================================= */

.submachine-input-group {

    position: relative;
}


.submachine-input-icon {

    position: absolute;

    left: 13px;

    top: 50%;

    transform: translateY(-50%);

    color: #94a3b8;

    pointer-events: none;

    z-index: 2;
}


.submachine-input.with-icon {

    padding-left: 38px;
}


/* =========================================================
   HELP TEXT
========================================================= */

.submachine-help {

    display: block;

    margin-top: 6px;

    color: #94a3b8;

    font-size: 11px;

    line-height: 1.5;
}


/* =========================================================
   MACHINE SELECT INFO
========================================================= */

.selected-machine-info {

    display: none;

    margin-top: 8px;

    padding: 9px 11px;

    background: #f8fafc;

    border: 1px solid #e5e7eb;

    border-radius: 8px;

    font-size: 11px;

    color: #64748b;
}


.selected-machine-info.show {

    display: block;
}


.selected-machine-info strong {

    color: #334155;
}


/* =========================================================
   PHOTO UPLOAD
========================================================= */

.photo-upload-box {

    border: 1px dashed #cbd5e1;

    background: #f8fafc;

    border-radius: 12px;

    padding: 14px;

    transition: .2s;
}


.photo-upload-box:hover {

    border-color: #0076c8;

    background: #f7fbff;
}


.photo-preview-wrapper {

    display: none;

    align-items: center;

    gap: 12px;

    margin-bottom: 12px;

    padding: 10px;

    background: #ffffff;

    border: 1px solid #e2e8f0;

    border-radius: 10px;
}


.photo-preview-wrapper.show {

    display: flex;
}


.photo-preview {

    width: 70px;

    height: 70px;

    object-fit: cover;

    border-radius: 9px;

    border: 1px solid #dbe3ea;

    background: #f8fafc;
}


.photo-preview-name {

    font-size: 12px;

    font-weight: 700;

    color: #334155;

    word-break: break-word;
}


.photo-preview-size {

    color: #94a3b8;

    font-size: 10px;

    margin-top: 2px;
}


/* =========================================================
   ERROR
========================================================= */

.submachine-error {

    border: none;

    border-radius: 10px;

    background: #fff1f2;

    color: #991b1b;

    padding: 11px 13px;

    font-size: 12px;

    display: flex;

    align-items: flex-start;

    gap: 9px;

    margin-bottom: 20px;
}


.submachine-error i {

    font-size: 16px;

    flex-shrink: 0;
}


/* =========================================================
   FORM FOOTER
========================================================= */

.submachine-form-footer {

    margin-top: 25px;

    padding-top: 18px;

    border-top: 1px solid #e5e7eb;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    flex-wrap: wrap;
}


.submachine-form-footer-left {

    color: #94a3b8;

    font-size: 11px;
}


.submachine-form-actions {

    display: flex;

    align-items: center;

    gap: 8px;
}


.btn-submachine-save {

    background:
        linear-gradient(
            135deg,
            #005baa,
            #0076c8
        );

    border: none;

    color: #fff;

    min-height: 41px;

    padding: 9px 18px;

    border-radius: 9px;

    font-size: 13px;

    font-weight: 700;

    transition: .2s;
}


.btn-submachine-save:hover {

    color: #fff;

    transform: translateY(-1px);

    box-shadow:
        0 6px 16px rgba(0, 91, 170, .18);
}


.btn-submachine-cancel {

    min-height: 41px;

    padding: 9px 18px;

    border-radius: 9px;

    font-size: 13px;

    font-weight: 600;

    background: #fff;

    border: 1px solid #dbe3ea;

    color: #475569;

    text-decoration: none;

    display: inline-flex;

    align-items: center;

    justify-content: center;
}


.btn-submachine-cancel:hover {

    background: #f8fafc;

    color: #334155;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 768px) {

    .submachine-add-header {

        padding: 15px;

        border-radius: 13px;
    }


    .submachine-add-header-inner {

        align-items: flex-start;
    }


    .submachine-back {

        width: 38px;

        height: 38px;
    }


    .submachine-add-title {

        font-size: 19px;
    }


    .submachine-add-subtitle {

        font-size: 11px;

        line-height: 1.5;
    }


    .submachine-form-header {

        padding: 14px 15px;
    }


    .submachine-form-body {

        padding: 17px 15px;
    }


    .submachine-form-footer {

        align-items: stretch;

        flex-direction: column;
    }


    .submachine-form-footer-left {

        order: 2;
    }


    .submachine-form-actions {

        width: 100%;

        display: grid;

        grid-template-columns: 1fr 1fr;
    }


    .btn-submachine-save,
    .btn-submachine-cancel {

        width: 100%;
    }


    .photo-preview {

        width: 60px;

        height: 60px;
    }
}


@media (max-width: 480px) {

    .submachine-add-header {

        margin-bottom: 14px;
    }


    .submachine-add-title {

        font-size: 17px;
    }


    .submachine-add-subtitle {

        font-size: 10px;
    }


    .submachine-form-card {

        border-radius: 13px;
    }


    .submachine-form-title {

        font-size: 14px;
    }


    .submachine-form-body {

        padding: 15px 12px;
    }


    .submachine-label {

        font-size: 12px;
    }


    .submachine-input,
    .submachine-select {

        min-height: 41px;

        font-size: 12px;
    }


    .submachine-textarea {

        font-size: 12px;
    }


    .submachine-form-actions {

        grid-template-columns: 1fr;
    }


    .btn-submachine-save,
    .btn-submachine-cancel {

        min-height: 42px;
    }


    .photo-preview-wrapper {

        align-items: flex-start;
    }
}

</style>


<div class="container-fluid p-0 submachine-add-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="submachine-add-header">

        <div class="submachine-add-header-inner">

            <a
                href="index.php"
                class="submachine-back"
                title="Kembali"
            >

                <i class="bi bi-arrow-left"></i>

            </a>


            <div>

                <h2 class="submachine-add-title">
                    Tambah Sub Mesin
                </h2>


                <div class="submachine-add-subtitle">

                    Tambahkan sub mesin baru dan hubungkan
                    dengan mesin induknya.

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <div class="submachine-form-card">


        <!-- =================================================
             FORM HEADER
        ================================================== -->

        <div class="submachine-form-header">

            <div class="submachine-form-icon">

                <i class="bi bi-diagram-3"></i>

            </div>


            <div>

                <div class="submachine-form-title">

                    Form Sub Mesin Baru

                </div>


                <div class="submachine-form-subtitle">

                    Lengkapi informasi sub mesin berikut.

                </div>

            </div>

        </div>


        <!-- =================================================
             FORM BODY
        ================================================== -->

        <div class="submachine-form-body">


            <!-- ERROR -->

            <?php if (!empty($error)): ?>

                <div class="submachine-error">

                    <i class="bi bi-exclamation-triangle-fill"></i>


                    <div>

                        <?= submesin_e($error) ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
                method="POST"
                enctype="multipart/form-data"
                id="formSubMesin"
            >


                <div class="row g-4">


                    <!-- =================================================
                         MESIN INDUK
                    ================================================== -->

                    <div class="col-12 col-md-6">

                        <label class="submachine-label">

                            Mesin Induk

                            <span class="required">*</span>

                        </label>


                        <div class="submachine-input-group">

                            <i
                                class="bi bi-gear-wide-connected submachine-input-icon"
                            ></i>


                            <select
                                name="id_mesin"
                                id="id_mesin"
                                class="submachine-select"
                                required
                            >

                                <option value="">
                                    -- Pilih Mesin Induk --
                                </option>


                                <?php if ($q_mesin): ?>

                                    <?php while (
                                        $m = mysqli_fetch_assoc($q_mesin)
                                    ): ?>

                                        <option
                                            value="<?= intval($m['id']) ?>"
                                            data-sn="<?= submesin_e($m['serial_number'] ?? '') ?>"
                                            <?= (
                                                $val_id_mesin
                                                ==
                                                intval($m['id'])
                                            )
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            <?= submesin_e(
                                                $m['nama_mesin']
                                            ) ?>

                                        </option>

                                    <?php endwhile; ?>

                                <?php endif; ?>

                            </select>

                        </div>


                        <div
                            class="selected-machine-info"
                            id="selectedMachineInfo"
                        >

                            <i class="bi bi-info-circle me-1"></i>

                            SN Mesin:

                            <strong id="selectedMachineSN">
                                -
                            </strong>

                        </div>


                        <small class="submachine-help">

                            Pilih mesin induk tempat sub mesin ini
                            terpasang.

                        </small>

                    </div>


                    <!-- =================================================
                         NAMA SUB MESIN
                    ================================================== -->

                    <div class="col-12 col-md-6">

                        <label class="submachine-label">

                            Nama Sub Mesin

                            <span class="required">*</span>

                        </label>


                        <div class="submachine-input-group">

                            <i
                                class="bi bi-diagram-3 submachine-input-icon"
                            ></i>


                            <input
                                type="text"
                                name="nama_sub_mesin"
                                class="submachine-input with-icon"
                                value="<?= submesin_e($val_nama_sub_mesin) ?>"
                                placeholder="Contoh: Conveyor Feeder"
                                maxlength="100"
                                required
                            >

                        </div>


                        <small class="submachine-help">

                            Masukkan nama bagian atau sub-sistem
                            dari mesin induk.

                        </small>

                    </div>


                    <!-- =================================================
                         SERIAL NUMBER
                    ================================================== -->

                    <div class="col-12 col-md-6">

                        <label class="submachine-label">

                            Serial Number Sub Mesin

                        </label>


                        <div class="submachine-input-group">

                            <i
                                class="bi bi-upc-scan submachine-input-icon"
                            ></i>


                            <input
                                type="text"
                                name="serial_number"
                                class="submachine-input with-icon"
                                value="<?= submesin_e($val_serial_number) ?>"
                                placeholder="Contoh: SM-SN-001"
                                maxlength="100"
                            >

                        </div>


                        <small class="submachine-help">

                            Nomor seri sub mesin jika tersedia.
                            Boleh dikosongkan.

                        </small>

                    </div>


                    <!-- =================================================
                         FOTO
                    ================================================== -->

                    <div class="col-12 col-md-6">

                        <label class="submachine-label">

                            Foto Sub Mesin

                        </label>


                        <div class="photo-upload-box">


                            <!-- PREVIEW -->

                            <div
                                class="photo-preview-wrapper"
                                id="photoPreviewWrapper"
                            >

                                <img
                                    src=""
                                    id="photoPreview"
                                    class="photo-preview"
                                    alt="Preview Foto"
                                >


                                <div>

                                    <div
                                        class="photo-preview-name"
                                        id="photoPreviewName"
                                    >
                                    </div>


                                    <div
                                        class="photo-preview-size"
                                        id="photoPreviewSize"
                                    >
                                    </div>

                                </div>

                            </div>


                            <input
                                type="file"
                                name="gambar"
                                id="gambar"
                                class="form-control"
                                accept="image/jpeg,image/png,image/webp"
                            >


                            <small class="submachine-help">

                                Format:
                                JPG, JPEG, PNG, WEBP.

                                <strong>
                                    Maksimal 50 MB.
                                </strong>

                            </small>

                        </div>

                    </div>


                    <!-- =================================================
                         KETERANGAN
                    ================================================== -->

                    <div class="col-12">

                        <label class="submachine-label">

                            Keterangan / Deskripsi

                        </label>


                        <textarea
                            name="keterangan"
                            class="submachine-textarea"
                            rows="5"
                            maxlength="1000"
                            placeholder="Masukkan fungsi, posisi, spesifikasi singkat, atau catatan sub mesin..."
                        ><?= submesin_e($val_keterangan) ?></textarea>


                        <small class="submachine-help">

                            Jelaskan fungsi atau informasi tambahan
                            mengenai sub mesin.

                        </small>

                    </div>


                </div>


                <!-- =================================================
                     FOOTER FORM
                ================================================== -->

                <div class="submachine-form-footer">


                    <div class="submachine-form-footer-left">

                        <i class="bi bi-info-circle me-1"></i>

                        Field bertanda
                        <span class="text-danger">*</span>
                        wajib diisi.

                    </div>


                    <div class="submachine-form-actions">


                        <a
                            href="index.php"
                            class="btn-submachine-cancel"
                        >

                            <i class="bi bi-x-lg me-1"></i>

                            Batal

                        </a>


                        <button
                            type="submit"
                            name="simpan"
                            class="btn-submachine-save"
                            id="btnSimpan"
                        >

                            <i class="bi bi-check-lg me-1"></i>

                            Simpan Sub Mesin

                        </button>


                    </div>

                </div>


            </form>

        </div>

    </div>

</div>


<script>

/* =========================================================
   JAVASCRIPT
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* =====================================================
           MESIN INDUK INFO
        ===================================================== */

        const mesinSelect =
            document.getElementById(
                "id_mesin"
            );


        const machineInfo =
            document.getElementById(
                "selectedMachineInfo"
            );


        const machineSN =
            document.getElementById(
                "selectedMachineSN"
            );


        function updateMachineInfo() {

            if (!mesinSelect) {

                return;
            }


            const selectedOption =
                mesinSelect.options[
                    mesinSelect.selectedIndex
                ];


            if (
                mesinSelect.value
                && selectedOption
            ) {

                const sn =
                    selectedOption.getAttribute(
                        "data-sn"
                    );


                if (sn) {

                    machineSN.textContent =
                        sn;

                } else {

                    machineSN.textContent =
                        "Tidak tersedia";
                }


                machineInfo.classList.add(
                    "show"
                );

            } else {

                machineInfo.classList.remove(
                    "show"
                );

                machineSN.textContent =
                    "-";
            }
        }


        if (mesinSelect) {

            mesinSelect.addEventListener(
                "change",
                updateMachineInfo
            );


            updateMachineInfo();
        }


        /* =====================================================
           FOTO PREVIEW
        ===================================================== */

        const gambarInput =
            document.getElementById(
                "gambar"
            );


        const previewWrapper =
            document.getElementById(
                "photoPreviewWrapper"
            );


        const preview =
            document.getElementById(
                "photoPreview"
            );


        const previewName =
            document.getElementById(
                "photoPreviewName"
            );


        const previewSize =
            document.getElementById(
                "photoPreviewSize"
            );


        /*
         * 50 MB
         */
        const MAX_FILE_SIZE =
            50 * 1024 * 1024;


        if (gambarInput) {

            gambarInput.addEventListener(
                "change",
                function () {

                    const file =
                        this.files[0];


                    /* -------------------------------------------------
                       RESET
                    ------------------------------------------------- */

                    if (!file) {

                        previewWrapper.classList.remove(
                            "show"
                        );

                        preview.removeAttribute(
                            "src"
                        );

                        previewName.textContent =
                            "";

                        previewSize.textContent =
                            "";

                        return;
                    }


                    /* -------------------------------------------------
                       CEK FORMAT
                    ------------------------------------------------- */

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
                            "Format foto tidak valid.\n\n"
                            + "Gunakan JPG, JPEG, PNG atau WEBP."
                        );


                        this.value = "";


                        previewWrapper.classList.remove(
                            "show"
                        );


                        return;
                    }


                    /* -------------------------------------------------
                       CEK UKURAN
                    ------------------------------------------------- */

                    if (
                        file.size >
                        MAX_FILE_SIZE
                    ) {

                        alert(
                            "Ukuran foto terlalu besar!\n\n"
                            + "Maksimal ukuran foto adalah 50 MB."
                        );


                        this.value = "";


                        previewWrapper.classList.remove(
                            "show"
                        );


                        return;
                    }


                    /* -------------------------------------------------
                       PREVIEW
                    ------------------------------------------------- */

                    const reader =
                        new FileReader();


                    reader.onload =
                        function (event) {

                            preview.src =
                                event.target.result;


                            previewName.textContent =
                                file.name;


                            previewSize.textContent =
                                formatFileSize(
                                    file.size
                                );


                            previewWrapper.classList.add(
                                "show"
                            );
                        };


                    reader.readAsDataURL(
                        file
                    );

                }
            );
        }


        /* =====================================================
           FORMAT FILE SIZE
        ===================================================== */

        function formatFileSize(bytes) {

            if (bytes === 0) {

                return "0 Bytes";
            }


            const units = [
                "Bytes",
                "KB",
                "MB",
                "GB"
            ];


            const i =
                Math.floor(
                    Math.log(bytes)
                    /
                    Math.log(1024)
                );


            const index =
                Math.min(
                    i,
                    units.length - 1
                );


            return (
                parseFloat(
                    (
                        bytes
                        /
                        Math.pow(
                            1024,
                            index
                        )
                    ).toFixed(2)
                )
                + " "
                + units[index]
            );
        }


        /* =====================================================
           FORM SUBMIT
        ===================================================== */

        const form =
            document.getElementById(
                "formSubMesin"
            );


        const btnSimpan =
            document.getElementById(
                "btnSimpan"
            );


        if (
            form
            && btnSimpan
        ) {

            form.addEventListener(
                "submit",
                function (event) {


                    /* -------------------------------------------------
                       VALIDASI HTML5
                    ------------------------------------------------- */

                    if (
                        !form.checkValidity()
                    ) {

                        return;
                    }


                    /* -------------------------------------------------
                       VALIDASI FOTO
                    ------------------------------------------------- */

                    const file =
                        gambarInput
                        && gambarInput.files
                            ? gambarInput.files[0]
                            : null;


                    if (file) {


                        /* -------------------------------------------------
                           CEK UKURAN
                        ------------------------------------------------- */

                        if (
                            file.size >
                            MAX_FILE_SIZE
                        ) {

                            event.preventDefault();


                            alert(
                                "Ukuran foto terlalu besar!\n\n"
                                + "Maksimal ukuran foto adalah 50 MB."
                            );


                            return;
                        }


                        /* -------------------------------------------------
                           CEK TIPE
                        ------------------------------------------------- */

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

                            event.preventDefault();


                            alert(
                                "Format foto tidak valid.\n\n"
                                + "Gunakan JPG, JPEG, PNG atau WEBP."
                            );


                            return;
                        }
                    }


                    /* -------------------------------------------------
                       CEGAH DOUBLE SUBMIT
                    ------------------------------------------------- */

                    if (
                        btnSimpan.dataset.submitted
                        ===
                        "true"
                    ) {

                        event.preventDefault();

                        return;
                    }


                    btnSimpan.dataset.submitted =
                        "true";


                    btnSimpan.disabled =
                        true;


                    btnSimpan.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2"></span>'
                        + 'Menyimpan...';

                }
            );
        }

    }
);

</script>


<?php

include "../template/footer.php";

?>