<?php
require_once __DIR__ . '/../template/auth.php';
require_user_role();

/*
|--------------------------------------------------------------------------
| EDIT RIWAYAT MAINTENANCE
|--------------------------------------------------------------------------
| File : maintenance/edit.php
|--------------------------------------------------------------------------
| - Upload foto maksimal 50 MB
| - JPG / JPEG / PNG / WEBP
| - Aman untuk hosting tanpa mysqlnd
| - Tidak menggunakan mysqli_stmt_get_result()
| - Mempertahankan tampilan form
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| MATIKAN EXCEPTION MYSQLI
|--------------------------------------------------------------------------
| Supaya error query dapat ditangani manual dan tidak langsung
| menyebabkan HTTP ERROR 500.
|--------------------------------------------------------------------------
*/
mysqli_report(MYSQLI_REPORT_OFF);

include "../koneksi.php";

date_default_timezone_set('Asia/Jakarta');

$error   = "";
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

$uploadDir = __DIR__ . "/../uploads/maintenance/";


/* =========================================================
   HELPER ESCAPE
========================================================= */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   HELPER JENIS KOMPONEN
========================================================= */

function getJenisKomponen($data)
{
    if (!is_array($data)) {
        return "";
    }

    if (isset($data['jenis_komponen']) && trim((string)$data['jenis_komponen']) !== '') {
        return trim((string)$data['jenis_komponen']);
    }

    if (isset($data['kategori']) && trim((string)$data['kategori']) !== '') {
        return trim((string)$data['kategori']);
    }

    if (isset($data['jenis']) && trim((string)$data['jenis']) !== '') {
        return trim((string)$data['jenis']);
    }

    return "";
}


/* =========================================================
   HELPER HAPUS FILE
========================================================= */

function deleteUploadedFile($path)
{
    if (!$path) {
        return;
    }

    $path = basename((string)$path);

    if ($path === '' || $path === '.' || $path === '..') {
        return;
    }

    $fullPath = __DIR__ . "/../uploads/maintenance/" . $path;

    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}


/* =========================================================
   HELPER DETEKSI MIME GAMBAR
========================================================= */

function detectImageMime($filePath)
{
    if (!is_file($filePath)) {
        return false;
    }

    $imageInfo = @getimagesize($filePath);

    if (!$imageInfo || empty($imageInfo['mime'])) {
        return false;
    }

    $mime = strtolower(trim($imageInfo['mime']));

    $allowed = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    return in_array($mime, $allowed, true) ? $mime : false;
}


/* =========================================================
   HELPER VALIDASI TANGGAL
========================================================= */

function validDateYmd($date)
{
    if (!is_string($date) || trim($date) === '') {
        return false;
    }

    $date = trim($date);

    $d = DateTime::createFromFormat('Y-m-d', $date);

    return $d && $d->format('Y-m-d') === $date;
}


/* =========================================================
   CEK KONEKSI
========================================================= */

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak tersedia.");
}


/* =========================================================
   AMBIL ID
========================================================= */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   AMBIL DATA MAINTENANCE
========================================================= */
/*
| PENTING:
| Jangan mengambil kolom nama_mesin / nama_sub_mesin langsung
| dari tabel komponen karena pada struktur database yang digunakan,
| data tersebut berasal dari tabel mesin dan sub_mesin.
|
| Untuk data riwayat kita cukup mengambil rm.*
|--------------------------------------------------------------------------
*/

$idSafe = (int)$id;

$sql_detail = "
    SELECT *
    FROM riwayat_maintenance
    WHERE id = {$idSafe}
    LIMIT 1
";

$result_detail = mysqli_query($conn, $sql_detail);

if (!$result_detail) {
    die(
        "Gagal mengambil data maintenance. " .
        e(mysqli_error($conn))
    );
}

$data_maintenance = mysqli_fetch_assoc($result_detail);

if (!$data_maintenance) {
    header("Location: index.php");
    exit;
}


/* =========================================================
   NILAI AWAL FORM
========================================================= */

$id_komponen_lama = isset($data_maintenance['id_komponen'])
    ? (int)$data_maintenance['id_komponen']
    : 0;

$status_form = isset($data_maintenance['status'])
    ? trim((string)$data_maintenance['status'])
    : 'Selesai';

$teknisi_form = isset($data_maintenance['teknisi'])
    ? (string)$data_maintenance['teknisi']
    : '';

$tindakan_form = isset($data_maintenance['tindakan'])
    ? (string)$data_maintenance['tindakan']
    : '';

$jenis_form = isset($data_maintenance['jenis'])
    ? (string)$data_maintenance['jenis']
    : 'Preventive';

$sparepart_form = isset($data_maintenance['sparepart'])
    ? (string)$data_maintenance['sparepart']
    : '';

$catatan_form = isset($data_maintenance['catatan'])
    ? (string)$data_maintenance['catatan']
    : '';


/* =========================================================
   TANGGAL
========================================================= */

$tanggal_form = '';

if (
    isset($data_maintenance['tanggal']) &&
    trim((string)$data_maintenance['tanggal']) !== ''
) {
    $timestampTanggal = strtotime($data_maintenance['tanggal']);

    if ($timestampTanggal !== false) {
        $tanggal_form = date('Y-m-d', $timestampTanggal);
    }
}

if ($tanggal_form === '') {
    $tanggal_form = date('Y-m-d');
}


/* =========================================================
   DATA SPESIFIKASI
========================================================= */

$brand_form = isset($data_maintenance['brand'])
    ? (string)$data_maintenance['brand']
    : '';

$tipe_form = isset($data_maintenance['tipe'])
    ? (string)$data_maintenance['tipe']
    : '';

$part_number_form = isset($data_maintenance['part_number'])
    ? (string)$data_maintenance['part_number']
    : '';

$daya_form = isset($data_maintenance['daya'])
    ? (string)$data_maintenance['daya']
    : '';

$io_address_form = isset($data_maintenance['io_address'])
    ? (string)$data_maintenance['io_address']
    : '';

$input_voltage_form = isset($data_maintenance['input_voltage'])
    ? (string)$data_maintenance['input_voltage']
    : '';

$frekuensi_input_form = isset($data_maintenance['frekuensi_input'])
    ? (string)$data_maintenance['frekuensi_input']
    : '';

$arus_input_form = isset($data_maintenance['arus_input'])
    ? (string)$data_maintenance['arus_input']
    : '';

$output_form = isset($data_maintenance['output'])
    ? (string)$data_maintenance['output']
    : '';

$frekuensi_output_form = isset($data_maintenance['frekuensi_output'])
    ? (string)$data_maintenance['frekuensi_output']
    : '';

$ip_rating_form = isset($data_maintenance['ip_rating'])
    ? (string)$data_maintenance['ip_rating']
    : '';


/* =========================================================
   FOTO LAMA
========================================================= */

$foto_lama = isset($data_maintenance['gambar'])
    ? trim((string)$data_maintenance['gambar'])
    : '';

$foto_lama = basename($foto_lama);


/* =========================================================
   DATA KOMPONEN TERPILIH
========================================================= */

$komponen_terpilih = null;

if ($id_komponen_lama > 0) {

    $idKomponenSafe = (int)$id_komponen_lama;

    $sql_komponen_terpilih = "
        SELECT
            k.*,
            sm.nama_sub_mesin,
            m.nama_mesin
        FROM komponen k
        LEFT JOIN sub_mesin sm
            ON k.id_sub_mesin = sm.id
        LEFT JOIN mesin m
            ON sm.id_mesin = m.id
        WHERE k.id = {$idKomponenSafe}
        LIMIT 1
    ";

    $result_komponen_terpilih = mysqli_query(
        $conn,
        $sql_komponen_terpilih
    );

    if ($result_komponen_terpilih) {
        $komponen_terpilih = mysqli_fetch_assoc(
            $result_komponen_terpilih
        );
    }
}


/* =========================================================
   DATA OTOMATIS KOMPONEN
========================================================= */

$serial_number_form = '';
$nama_bagian_form = '';
$jenis_komponen_form = '';
$nama_mesin_form = '';
$nama_sub_mesin_form = '';
$lokasi_form = '';

if (is_array($komponen_terpilih)) {

    $serial_number_form = isset($komponen_terpilih['serial_number'])
        ? (string)$komponen_terpilih['serial_number']
        : '';

    $nama_bagian_form = isset($komponen_terpilih['nama_bagian'])
        ? (string)$komponen_terpilih['nama_bagian']
        : '';

    $jenis_komponen_form = getJenisKomponen($komponen_terpilih);

    $nama_mesin_form = isset($komponen_terpilih['nama_mesin'])
        ? (string)$komponen_terpilih['nama_mesin']
        : '';

    $nama_sub_mesin_form = isset($komponen_terpilih['nama_sub_mesin'])
        ? (string)$komponen_terpilih['nama_sub_mesin']
        : '';

    if (isset($komponen_terpilih['lokasi_penempatan'])) {
        $lokasi_form = (string)$komponen_terpilih['lokasi_penempatan'];
    } elseif (isset($komponen_terpilih['lokasi'])) {
        $lokasi_form = (string)$komponen_terpilih['lokasi'];
    }
}


/* =========================================================
   FALLBACK DARI RIWAYAT
========================================================= */

if ($serial_number_form === '' && isset($data_maintenance['serial_number'])) {
    $serial_number_form = (string)$data_maintenance['serial_number'];
}

if ($nama_bagian_form === '' && isset($data_maintenance['nama_bagian'])) {
    $nama_bagian_form = (string)$data_maintenance['nama_bagian'];
}

if ($jenis_komponen_form === '') {
    if (isset($data_maintenance['kategori'])) {
        $jenis_komponen_form = (string)$data_maintenance['kategori'];
    }
}

if ($nama_mesin_form === '' && isset($data_maintenance['nama_mesin'])) {
    $nama_mesin_form = (string)$data_maintenance['nama_mesin'];
}

if ($nama_sub_mesin_form === '' && isset($data_maintenance['nama_sub_mesin'])) {
    $nama_sub_mesin_form = (string)$data_maintenance['nama_sub_mesin'];
}

if ($lokasi_form === '' && isset($data_maintenance['lokasi_penempatan'])) {
    $lokasi_form = (string)$data_maintenance['lokasi_penempatan'];
}


/* =========================================================
   AMBIL SEMUA KOMPONEN
========================================================= */

$komponen_list = [];

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
    ORDER BY k.nama_bagian ASC
";

$result_komponen = mysqli_query($conn, $sql_komponen);

if ($result_komponen) {

    while ($row = mysqli_fetch_assoc($result_komponen)) {
        $komponen_list[] = $row;
    }
}


/* =========================================================
   PROSES UPDATE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $simpan_maintenance = isset($_POST['simpan_maintenance'])
        ? (string)$_POST['simpan_maintenance']
        : '';

    if ($simpan_maintenance === '1') {

        /* -----------------------------------------------------
           AMBIL INPUT
        ----------------------------------------------------- */

        $id_komponen = isset($_POST['id_komponen'])
            ? (int)$_POST['id_komponen']
            : 0;

        $status = isset($_POST['status'])
            ? trim((string)$_POST['status'])
            : '';

        $teknisi = isset($_POST['teknisi'])
            ? trim((string)$_POST['teknisi'])
            : '';

        $tindakan = isset($_POST['tindakan'])
            ? trim((string)$_POST['tindakan'])
            : '';

        $jenis = isset($_POST['jenis'])
            ? trim((string)$_POST['jenis'])
            : '';

        $sparepart = isset($_POST['sparepart'])
            ? trim((string)$_POST['sparepart'])
            : '';

        $catatan = isset($_POST['catatan'])
            ? trim((string)$_POST['catatan'])
            : '';

        $tanggal = isset($_POST['tanggal'])
            ? trim((string)$_POST['tanggal'])
            : '';


        /* -----------------------------------------------------
           SPESIFIKASI
        ----------------------------------------------------- */

        $brand = isset($_POST['brand'])
            ? trim((string)$_POST['brand'])
            : '';

        $tipe = isset($_POST['tipe'])
            ? trim((string)$_POST['tipe'])
            : '';

        $part_number = isset($_POST['part_number'])
            ? trim((string)$_POST['part_number'])
            : '';

        $daya = isset($_POST['daya'])
            ? trim((string)$_POST['daya'])
            : '';

        $io_address = isset($_POST['io_address'])
            ? trim((string)$_POST['io_address'])
            : '';

        $input_voltage = isset($_POST['input_voltage'])
            ? trim((string)$_POST['input_voltage'])
            : '';

        $frekuensi_input = isset($_POST['frekuensi_input'])
            ? trim((string)$_POST['frekuensi_input'])
            : '';

        $arus_input = isset($_POST['arus_input'])
            ? trim((string)$_POST['arus_input'])
            : '';

        $output = isset($_POST['output'])
            ? trim((string)$_POST['output'])
            : '';

        $frekuensi_output = isset($_POST['frekuensi_output'])
            ? trim((string)$_POST['frekuensi_output'])
            : '';

        $ip_rating = isset($_POST['ip_rating'])
            ? trim((string)$_POST['ip_rating'])
            : '';


        /* -----------------------------------------------------
           FOTO
        ----------------------------------------------------- */

        $foto_nama_akhir = $foto_lama;

        $foto_baru_path = '';

        $hapus_foto = (
            isset($_POST['hapus_foto']) &&
            $_POST['hapus_foto'] === '1'
        );


        /* -----------------------------------------------------
           VALIDASI
        ----------------------------------------------------- */

        if ($id_komponen <= 0) {

            $error = "Komponen wajib dipilih.";

        } elseif ($tindakan === '') {

            $error = "Tindakan maintenance wajib diisi.";

        } elseif ($status === '') {

            $error = "Status maintenance wajib dipilih.";

        } elseif (!validDateYmd($tanggal)) {

            $error = "Tanggal maintenance tidak valid.";

        } elseif (!in_array(
            $status,
            ['Selesai', 'Proses', 'Pending'],
            true
        )) {

            $error = "Status maintenance tidak valid.";

        } elseif (
            $jenis !== '' &&
            !in_array(
                $jenis,
                [
                    'Preventive',
                    'Corrective',
                    'Breakdown',
                    'Predictive'
                ],
                true
            )
        ) {

            $error = "Jenis maintenance tidak valid.";
        }


        /* -----------------------------------------------------
           AMBIL DATA KOMPONEN YANG DIPILIH
        ----------------------------------------------------- */

        $data_komponen = null;

        if ($error === '') {

            $idKomponenSafe = (int)$id_komponen;

            $sql_selected_component = "
                SELECT
                    k.*,
                    sm.nama_sub_mesin,
                    m.nama_mesin
                FROM komponen k
                LEFT JOIN sub_mesin sm
                    ON k.id_sub_mesin = sm.id
                LEFT JOIN mesin m
                    ON sm.id_mesin = m.id
                WHERE k.id = {$idKomponenSafe}
                LIMIT 1
            ";

            $result_selected_component = mysqli_query(
                $conn,
                $sql_selected_component
            );

            if (!$result_selected_component) {

                $error =
                    "Gagal mengambil data komponen: " .
                    mysqli_error($conn);

            } else {

                $data_komponen = mysqli_fetch_assoc(
                    $result_selected_component
                );

                if (!$data_komponen) {
                    $error = "Komponen yang dipilih tidak ditemukan.";
                }
            }
        }


        /* -----------------------------------------------------
           ISI DATA OTOMATIS DARI KOMPONEN
        ----------------------------------------------------- */

        if ($error === '' && is_array($data_komponen)) {

            $serial_number = isset($data_komponen['serial_number'])
                ? trim((string)$data_komponen['serial_number'])
                : '';

            $nama_bagian = isset($data_komponen['nama_bagian'])
                ? trim((string)$data_komponen['nama_bagian'])
                : '';

            $kategori = getJenisKomponen($data_komponen);

            $nama_mesin = isset($data_komponen['nama_mesin'])
                ? trim((string)$data_komponen['nama_mesin'])
                : '';

            $nama_sub_mesin = isset($data_komponen['nama_sub_mesin'])
                ? trim((string)$data_komponen['nama_sub_mesin'])
                : '';

            if (isset($data_komponen['lokasi_penempatan'])) {

                $lokasi_penempatan = trim(
                    (string)$data_komponen['lokasi_penempatan']
                );

            } elseif (isset($data_komponen['lokasi'])) {

                $lokasi_penempatan = trim(
                    (string)$data_komponen['lokasi']
                );

            } else {

                $lokasi_penempatan = '';
            }
        }


        /* -----------------------------------------------------
           UPLOAD FOTO BARU
        ----------------------------------------------------- */

        if (
            $error === '' &&
            isset($_FILES['gambar']) &&
            is_array($_FILES['gambar']) &&
            isset($_FILES['gambar']['error']) &&
            (int)$_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            $uploadError = (int)$_FILES['gambar']['error'];

            if ($uploadError !== UPLOAD_ERR_OK) {

                switch ($uploadError) {

                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $error = "Ukuran foto terlalu besar. Maksimal 50 MB.";
                        break;

                    case UPLOAD_ERR_PARTIAL:
                        $error = "Upload foto tidak selesai.";
                        break;

                    case UPLOAD_ERR_NO_TMP_DIR:
                        $error = "Folder temporary upload tidak tersedia.";
                        break;

                    case UPLOAD_ERR_CANT_WRITE:
                        $error = "Server gagal menulis file upload.";
                        break;

                    default:
                        $error = "Terjadi kesalahan saat upload foto.";
                        break;
                }

            } else {

                $tmpName = $_FILES['gambar']['tmp_name'];
                $fileSize = (int)$_FILES['gambar']['size'];

                if ($fileSize <= 0) {

                    $error = "File foto tidak valid.";

                } elseif ($fileSize > $maxFotoSize) {

                    $error = "Ukuran foto maksimal 50 MB.";

                } elseif (!is_uploaded_file($tmpName)) {

                    $error = "File upload tidak valid.";

                } else {

                    $detectedMime = detectImageMime($tmpName);

                    if (!$detectedMime) {

                        $error =
                            "Format foto tidak valid. " .
                            "Gunakan JPG, PNG, atau WEBP.";

                    } elseif (
                        !isset($allowedFotoMime[$detectedMime])
                    ) {

                        $error = "Format foto tidak diizinkan.";

                    } else {

                        /* -------------------------------------------------
                           PASTIKAN FOLDER UPLOAD ADA
                        ------------------------------------------------- */

                        if (!is_dir($uploadDir)) {

                            if (!@mkdir(
                                $uploadDir,
                                0755,
                                true
                            )) {

                                $error =
                                    "Folder upload maintenance tidak dapat dibuat.";
                            }
                        }


                        /* -------------------------------------------------
                           CEK WRITE
                        ------------------------------------------------- */

                        if (
                            $error === '' &&
                            !is_writable($uploadDir)
                        ) {

                            $error =
                                "Folder uploads/maintenance tidak dapat ditulis.";
                        }


                        /* -------------------------------------------------
                           SIMPAN FILE
                        ------------------------------------------------- */

                        if ($error === '') {

                            $extension =
                                $allowedFotoMime[$detectedMime];

                            try {

                                $randomName =
                                    bin2hex(random_bytes(16)) .
                                    '_' .
                                    time() .
                                    '.' .
                                    $extension;

                            } catch (Exception $ex) {

                                $randomName =
                                    uniqid('', true) .
                                    '_' .
                                    time() .
                                    '.' .
                                    $extension;
                            }

                            $targetPath =
                                $uploadDir . $randomName;

                            if (
                                !@move_uploaded_file(
                                    $tmpName,
                                    $targetPath
                                )
                            ) {

                                $error =
                                    "Foto gagal disimpan ke server.";

                            } else {

                                $foto_baru_path = $targetPath;

                                $foto_nama_akhir =
                                    $randomName;

                                /*
                                 * Jika user upload foto baru,
                                 * otomatis foto lama tidak digunakan.
                                 */
                                $hapus_foto = false;
                            }
                        }
                    }
                }
            }
        }


        /* -----------------------------------------------------
           JIKA HAPUS FOTO TANPA UPLOAD FOTO BARU
        ----------------------------------------------------- */

        if (
            $error === '' &&
            $foto_baru_path === '' &&
            $hapus_foto
        ) {

            $foto_nama_akhir = '';
        }


        /* -----------------------------------------------------
           UPDATE DATABASE
        ----------------------------------------------------- */

        if ($error === '') {

            $sql_update = "
                UPDATE riwayat_maintenance
                SET
                    id_komponen = ?,
                    tanggal = ?,
                    status = ?,
                    teknisi = ?,
                    tindakan = ?,
                    jenis = ?,
                    serial_number = ?,
                    nama_bagian = ?,
                    kategori = ?,
                    nama_mesin = ?,
                    nama_sub_mesin = ?,
                    lokasi_penempatan = ?,
                    brand = ?,
                    tipe = ?,
                    part_number = ?,
                    daya = ?,
                    io_address = ?,
                    input_voltage = ?,
                    frekuensi_input = ?,
                    arus_input = ?,
                    output = ?,
                    frekuensi_output = ?,
                    ip_rating = ?,
                    sparepart = ?,
                    catatan = ?,
                    gambar = ?
                WHERE id = ?
                LIMIT 1
            ";

            $stmt_update = mysqli_prepare(
                $conn,
                $sql_update
            );

            if (!$stmt_update) {

                $error =
                    "Query update gagal dipersiapkan: " .
                    mysqli_error($conn);

            } else {

                /*
                 * 26 string/integer parameter:
                 *
                 * id_komponen = i
                 * tanggal sampai gambar = 25 string
                 * id = i
                 */

                $types = 'i' . str_repeat('s', 25) . 'i';

                $bindOK = mysqli_stmt_bind_param(
                    $stmt_update,
                    $types,
                    $id_komponen,
                    $tanggal,
                    $status,
                    $teknisi,
                    $tindakan,
                    $jenis,
                    $serial_number,
                    $nama_bagian,
                    $kategori,
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
                    $foto_nama_akhir,
                    $id
                );

                if (!$bindOK) {

                    $error =
                        "Parameter update gagal: " .
                        mysqli_stmt_error($stmt_update);

                } elseif (!mysqli_stmt_execute($stmt_update)) {

                    $error =
                        "Data maintenance gagal diperbarui: " .
                        mysqli_stmt_error($stmt_update);
                }

                mysqli_stmt_close($stmt_update);
            }
        }


        /* -----------------------------------------------------
           JIKA UPDATE GAGAL, HAPUS FOTO BARU
        ----------------------------------------------------- */

        if (
            $error !== '' &&
            $foto_baru_path !== ''
        ) {

            if (is_file($foto_baru_path)) {
                @unlink($foto_baru_path);
            }

            $foto_nama_akhir = $foto_lama;
        }


        /* -----------------------------------------------------
           UPDATE KONDISI KOMPONEN
        ----------------------------------------------------- */

        if ($error === '') {

            /*
             * Kondisi komponen mengikuti maintenance terakhir.
             * Ini lebih aman jika satu komponen memiliki banyak
             * riwayat maintenance.
             */

            $idKomponenSafe = (int)$id_komponen;

            $sql_latest_status = "
                SELECT status
                FROM riwayat_maintenance
                WHERE id_komponen = {$idKomponenSafe}
                ORDER BY tanggal DESC, id DESC
                LIMIT 1
            ";

            $result_latest_status = mysqli_query(
                $conn,
                $sql_latest_status
            );

            $latest_status = '';

            if ($result_latest_status) {

                $latest_row = mysqli_fetch_assoc(
                    $result_latest_status
                );

                if ($latest_row) {
                    $latest_status = trim(
                        (string)$latest_row['status']
                    );
                }
            }


            $kondisi_baru = '';

            if ($latest_status === 'Selesai') {

                $kondisi_baru = 'Baik';

            } elseif ($latest_status === 'Proses') {

                $kondisi_baru = 'Dalam Perbaikan';

            } elseif ($latest_status === 'Pending') {

                $kondisi_baru = 'Perlu Pemeriksaan';
            }


            if ($kondisi_baru !== '') {

                $kondisiSafe = mysqli_real_escape_string(
                    $conn,
                    $kondisi_baru
                );

                mysqli_query(
                    $conn,
                    "
                    UPDATE komponen
                    SET kondisi = '{$kondisiSafe}'
                    WHERE id = {$idKomponenSafe}
                    LIMIT 1
                    "
                );
            }


            /* -------------------------------------------------
               HAPUS FOTO LAMA
            ------------------------------------------------- */

            $fotoLamaUntukDihapus = '';

            if ($foto_lama !== '') {

                if (
                    $foto_baru_path !== '' ||
                    $hapus_foto
                ) {

                    $fotoLamaUntukDihapus = $foto_lama;
                }
            }

            if ($fotoLamaUntukDihapus !== '') {

                deleteUploadedFile(
                    $fotoLamaUntukDihapus
                );
            }


            /* -------------------------------------------------
               REDIRECT
            ------------------------------------------------- */

            header(
                "Location: index.php?edit=berhasil"
            );

            exit;
        }


        /* -----------------------------------------------------
           JIKA ERROR, KEMBALIKAN NILAI FORM
        ----------------------------------------------------- */

        $id_komponen_lama = $id_komponen;

        $status_form = $status;
        $teknisi_form = $teknisi;
        $tindakan_form = $tindakan;
        $jenis_form = $jenis;
        $sparepart_form = $sparepart;
        $catatan_form = $catatan;

        $tanggal_form = $tanggal;

        $brand_form = $brand;
        $tipe_form = $tipe;
        $part_number_form = $part_number;
        $daya_form = $daya;
        $io_address_form = $io_address;
        $input_voltage_form = $input_voltage;
        $frekuensi_input_form = $frekuensi_input;
        $arus_input_form = $arus_input;
        $output_form = $output;
        $frekuensi_output_form = $frekuensi_output;
        $ip_rating_form = $ip_rating;


        /* -----------------------------------------------------
           DATA KOMPONEN TERPILIH UNTUK PREVIEW
        ----------------------------------------------------- */

        $komponen_terpilih = null;

        if ($id_komponen > 0) {

            $idKomponenSafe = (int)$id_komponen;

            $sql_component_preview = "
                SELECT
                    k.*,
                    sm.nama_sub_mesin,
                    m.nama_mesin
                FROM komponen k
                LEFT JOIN sub_mesin sm
                    ON k.id_sub_mesin = sm.id
                LEFT JOIN mesin m
                    ON sm.id_mesin = m.id
                WHERE k.id = {$idKomponenSafe}
                LIMIT 1
            ";

            $result_component_preview = mysqli_query(
                $conn,
                $sql_component_preview
            );

            if ($result_component_preview) {

                $komponen_terpilih =
                    mysqli_fetch_assoc(
                        $result_component_preview
                    );
            }
        }


        /* -----------------------------------------------------
           DATA OTOMATIS
        ----------------------------------------------------- */

        $serial_number_form = '';
        $nama_bagian_form = '';
        $jenis_komponen_form = '';
        $nama_mesin_form = '';
        $nama_sub_mesin_form = '';
        $lokasi_form = '';

        if (is_array($komponen_terpilih)) {

            $serial_number_form =
                isset($komponen_terpilih['serial_number'])
                    ? (string)$komponen_terpilih['serial_number']
                    : '';

            $nama_bagian_form =
                isset($komponen_terpilih['nama_bagian'])
                    ? (string)$komponen_terpilih['nama_bagian']
                    : '';

            $jenis_komponen_form =
                getJenisKomponen(
                    $komponen_terpilih
                );

            $nama_mesin_form =
                isset($komponen_terpilih['nama_mesin'])
                    ? (string)$komponen_terpilih['nama_mesin']
                    : '';

            $nama_sub_mesin_form =
                isset($komponen_terpilih['nama_sub_mesin'])
                    ? (string)$komponen_terpilih['nama_sub_mesin']
                    : '';

            if (
                isset(
                    $komponen_terpilih['lokasi_penempatan']
                )
            ) {

                $lokasi_form =
                    (string)$komponen_terpilih[
                        'lokasi_penempatan'
                    ];

            } elseif (
                isset(
                    $komponen_terpilih['lokasi']
                )
            ) {

                $lokasi_form =
                    (string)$komponen_terpilih['lokasi'];
            }
        }


        /* -----------------------------------------------------
           FOTO LAMA TETAP
        ----------------------------------------------------- */

        if (
            !isset($foto_nama_akhir) ||
            $foto_nama_akhir === ''
        ) {

            $foto_nama_akhir =
                $foto_lama;
        }
    }
}


/* =========================================================
   URL FOTO LAMA
========================================================= */

$foto_url = '';

if ($foto_lama !== '') {

    $foto_url =
        "../uploads/maintenance/" .
        rawurlencode(basename($foto_lama));
}


/* =========================================================
   INCLUDE HEADER
========================================================= */

include "../template/header.php";
?>

<!-- =========================================================
     SELECT2 CSS
========================================================= -->

<link
    href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
    rel="stylesheet"
/>

<link
    href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
    rel="stylesheet"
/>


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

    .select2-container--bootstrap-5 .select2-selection__rendered {
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

    .current-photo-label {
        font-size: 11px;
        color: #64748b;
        margin-top: 7px;
        text-align: center;
    }

    @media (max-width: 767.98px) {

        .maintenance-card {
            border-radius: 14px;
        }

        .photo-preview-wrapper {
            min-height: 150px;
        }

        .btn {
            width: 100%;
        }

    }
</style>


<div class="container-fluid py-3 maintenance-page">

    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="card maintenance-card mb-3">

        <div class="card-body">

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">

                <div>

                    <a
                        href="index.php"
                        class="btn btn-light border btn-sm mb-2"
                    >
                        <i class="bi bi-arrow-left me-1"></i>
                        Kembali
                    </a>

                    <h4 class="fw-bold mb-1">
                        Edit Catatan Maintenance
                    </h4>

                    <div class="text-muted small">
                        Perbarui informasi dan hasil maintenance pada komponen yang dipilih.
                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         ALERT ERROR
    ====================================================== -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger alert-dismissible fade show" role="alert">

            <i class="bi bi-exclamation-triangle-fill me-2"></i>

            <?= e($error) ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         FORM
    ====================================================== -->

    <form
        method="POST"
        enctype="multipart/form-data"
        id="formMaintenance"
        novalidate
    >

        <input
            type="hidden"
            name="simpan_maintenance"
            value="1"
        >


        <!-- =================================================
             DATA KOMPONEN
        ================================================== -->

        <div class="card maintenance-card mb-3">

            <div class="card-body p-4">

                <div class="section-title mb-3">
                    <i class="bi bi-cpu me-1"></i>
                    Data Komponen
                </div>


                <div class="row g-3">

                    <!-- COMPONENT -->

                    <div class="col-12">

                        <label class="form-label fw-semibold">
                            Komponen
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="id_komponen"
                            id="id_komponen"
                            class="form-select"
                            required
                        >

                            <option value="">
                                Pilih komponen...
                            </option>

                            <?php foreach ($komponen_list as $komponen): ?>

                                <?php
                                $componentId =
                                    isset($komponen['id'])
                                        ? (int)$komponen['id']
                                        : 0;

                                $selected =
                                    ($componentId === (int)$id_komponen_lama)
                                        ? 'selected'
                                        : '';

                                $sn =
                                    isset($komponen['serial_number'])
                                        ? $komponen['serial_number']
                                        : '';

                                $namaBagian =
                                    isset($komponen['nama_bagian'])
                                        ? $komponen['nama_bagian']
                                        : '';

                                $jenisKomponen =
                                    getJenisKomponen($komponen);

                                $namaMesin =
                                    isset($komponen['nama_mesin'])
                                        ? $komponen['nama_mesin']
                                        : '';

                                $namaSubMesin =
                                    isset($komponen['nama_sub_mesin'])
                                        ? $komponen['nama_sub_mesin']
                                        : '';

                                $lokasiKomponen = '';

                                if (
                                    isset(
                                        $komponen['lokasi_penempatan']
                                    )
                                ) {

                                    $lokasiKomponen =
                                        $komponen['lokasi_penempatan'];

                                } elseif (
                                    isset(
                                        $komponen['lokasi']
                                    )
                                ) {

                                    $lokasiKomponen =
                                        $komponen['lokasi'];
                                }
                                ?>

                                <option
                                    value="<?= $componentId ?>"
                                    <?= $selected ?>
                                    data-sn="<?= e($sn) ?>"
                                    data-namabagian="<?= e($namaBagian) ?>"
                                    data-jenis-komponen="<?= e($jenisKomponen) ?>"
                                    data-mesin="<?= e($namaMesin) ?>"
                                    data-submesin="<?= e($namaSubMesin) ?>"
                                    data-lokasi="<?= e($lokasiKomponen) ?>"
                                >
                                    <?= e($namaBagian) ?>
                                    <?php if ($sn !== ''): ?>
                                        — SN: <?= e($sn) ?>
                                    <?php endif; ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- SN -->

                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Serial Number
                        </label>

                        <input
                            type="text"
                            id="serial_number"
                            class="form-control auto-field"
                            value="<?= e($serial_number_form) ?>"
                            readonly
                        >

                    </div>


                    <!-- NAMA BAGIAN -->

                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Nama Bagian
                        </label>

                        <input
                            type="text"
                            id="nama_bagian"
                            class="form-control auto-field"
                            value="<?= e($nama_bagian_form) ?>"
                            readonly
                        >

                    </div>


                    <!-- JENIS KOMPONEN -->

                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Jenis Komponen
                        </label>

                        <input
                            type="text"
                            id="jenis_komponen"
                            class="form-control auto-field"
                            value="<?= e($jenis_komponen_form) ?>"
                            readonly
                        >

                    </div>


                    <!-- MESIN INDUK -->

                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Mesin Induk
                        </label>

                        <input
                            type="text"
                            id="nama_mesin"
                            class="form-control auto-field"
                            value="<?= e($nama_mesin_form) ?>"
                            readonly
                        >

                    </div>


                    <!-- SUB MESIN -->

                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Sub Mesin
                        </label>

                        <input
                            type="text"
                            id="nama_sub_mesin"
                            class="form-control auto-field"
                            value="<?= e($nama_sub_mesin_form) ?>"
                            readonly
                        >

                    </div>


                    <!-- LOKASI -->

                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Lokasi Penempatan
                        </label>

                        <input
                            type="text"
                            id="lokasi_penempatan"
                            class="form-control auto-field"
                            value="<?= e($lokasi_form) ?>"
                            readonly
                        >

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             FOTO + SPESIFIKASI
        ================================================== -->

        <div class="card maintenance-card mb-3">

            <div class="card-body p-4">

                <div class="section-title mb-3">
                    <i class="bi bi-image me-1"></i>
                    Foto & Spesifikasi Komponen
                </div>


                <div class="row g-4">

                    <!-- FOTO -->

                    <div class="col-lg-4">

                        <label class="form-label fw-semibold">
                            Foto Komponen
                        </label>

                        <input
                            type="file"
                            name="gambar"
                            id="gambar"
                            class="form-control manual-field"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >

                        <div class="form-text">
                            Maksimal 50 MB. Format JPG, PNG, atau WEBP.
                        </div>


                        <div
                            class="photo-preview-wrapper mt-2"
                            id="photoPreviewWrapper"
                        >

                            <?php if ($foto_url !== ''): ?>

                                <img
                                    src="<?= e($foto_url) ?>"
                                    alt="Foto Komponen"
                                    id="photoPreview"
                                >

                            <?php else: ?>

                                <div
                                    class="photo-empty"
                                    id="photoEmpty"
                                >
                                    <i class="bi bi-image"></i>
                                    <div class="small mt-1">
                                        Belum ada foto
                                    </div>
                                </div>

                            <?php endif; ?>

                        </div>


                        <?php if ($foto_lama !== ''): ?>

                            <div class="current-photo-label">

                                Foto saat ini:
                                <?= e($foto_lama) ?>

                            </div>

                            <div class="form-check mt-2">

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="hapus_foto"
                                    value="1"
                                    id="hapus_foto"
                                >

                                <label
                                    class="form-check-label small text-danger"
                                    for="hapus_foto"
                                >
                                    Hapus foto saat ini
                                </label>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- SPESIFIKASI -->

                    <div class="col-lg-8">

                        <div class="row g-3">

                            <!-- BRAND -->

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Brand / Merk
                                </label>

                                <input
                                    type="text"
                                    name="brand"
                                    id="brand"
                                    class="form-control manual-field"
                                    value="<?= e($brand_form) ?>"
                                >

                            </div>


                            <!-- TIPE -->

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Tipe
                                </label>

                                <input
                                    type="text"
                                    name="tipe"
                                    id="tipe"
                                    class="form-control manual-field"
                                    value="<?= e($tipe_form) ?>"
                                >

                            </div>


                            <!-- PART NUMBER -->

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Part Number
                                </label>

                                <input
                                    type="text"
                                    name="part_number"
                                    id="part_number"
                                    class="form-control manual-field"
                                    value="<?= e($part_number_form) ?>"
                                >

                            </div>


                            <!-- DAYA -->

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Daya
                                </label>

                                <input
                                    type="text"
                                    name="daya"
                                    id="daya"
                                    class="form-control manual-field"
                                    value="<?= e($daya_form) ?>"
                                >

                            </div>


                            <!-- IO ADDRESS -->

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    IO Address
                                </label>

                                <input
                                    type="text"
                                    name="io_address"
                                    id="io_address"
                                    class="form-control manual-field"
                                    value="<?= e($io_address_form) ?>"
                                >

                            </div>


                            <!-- INPUT VOLTAGE -->

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Input Voltage
                                </label>

                                <input
                                    type="text"
                                    name="input_voltage"
                                    id="input_voltage"
                                    class="form-control manual-field"
                                    value="<?= e($input_voltage_form) ?>"
                                >

                            </div>


                            <!-- FREKUENSI INPUT -->

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Frekuensi Input
                                </label>

                                <input
                                    type="text"
                                    name="frekuensi_input"
                                    id="frekuensi_input"
                                    class="form-control manual-field"
                                    value="<?= e($frekuensi_input_form) ?>"
                                >

                            </div>


                            <!-- ARUS INPUT -->

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Arus Input
                                </label>

                                <input
                                    type="text"
                                    name="arus_input"
                                    id="arus_input"
                                    class="form-control manual-field"
                                    value="<?= e($arus_input_form) ?>"
                                >

                            </div>


                            <!-- OUTPUT -->

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Output
                                </label>

                                <input
                                    type="text"
                                    name="output"
                                    id="output"
                                    class="form-control manual-field"
                                    value="<?= e($output_form) ?>"
                                >

                            </div>


                            <!-- FREKUENSI OUTPUT -->

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Frekuensi Output
                                </label>

                                <input
                                    type="text"
                                    name="frekuensi_output"
                                    id="frekuensi_output"
                                    class="form-control manual-field"
                                    value="<?= e($frekuensi_output_form) ?>"
                                >

                            </div>


                            <!-- IP RATING -->

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    IP Rating
                                </label>

                                <input
                                    type="text"
                                    name="ip_rating"
                                    id="ip_rating"
                                    class="form-control manual-field"
                                    value="<?= e($ip_rating_form) ?>"
                                >

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             DATA MAINTENANCE
        ================================================== -->

        <div class="card maintenance-card mb-3">

            <div class="card-body p-4">

                <div class="section-title mb-3">
                    <i class="bi bi-wrench-adjustable me-1"></i>
                    Data Maintenance
                </div>


                <div class="row g-3">

                    <!-- TANGGAL -->

                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Tanggal
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="date"
                            name="tanggal"
                            id="tanggal"
                            class="form-control manual-field"
                            value="<?= e($tanggal_form) ?>"
                            required
                        >

                    </div>


                    <!-- JENIS -->

                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Jenis Maintenance
                        </label>

                        <select
                            name="jenis"
                            id="jenis"
                            class="form-select manual-field"
                        >

                            <option
                                value="Preventive"
                                <?= $jenis_form === 'Preventive' ? 'selected' : '' ?>
                            >
                                Preventive
                            </option>

                            <option
                                value="Corrective"
                                <?= $jenis_form === 'Corrective' ? 'selected' : '' ?>
                            >
                                Corrective
                            </option>

                            <option
                                value="Breakdown"
                                <?= $jenis_form === 'Breakdown' ? 'selected' : '' ?>
                            >
                                Breakdown
                            </option>

                            <option
                                value="Predictive"
                                <?= $jenis_form === 'Predictive' ? 'selected' : '' ?>
                            >
                                Predictive
                            </option>

                        </select>

                    </div>


                    <!-- STATUS -->

                    <div class="col-md-4">

                        <label class="form-label fw-semibold">
                            Status
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="status"
                            id="status"
                            class="form-select manual-field"
                            required
                        >

                            <option value="">
                                Pilih status...
                            </option>

                            <option
                                value="Selesai"
                                <?= $status_form === 'Selesai' ? 'selected' : '' ?>
                            >
                                Selesai
                            </option>

                            <option
                                value="Proses"
                                <?= $status_form === 'Proses' ? 'selected' : '' ?>
                            >
                                Proses
                            </option>

                            <option
                                value="Pending"
                                <?= $status_form === 'Pending' ? 'selected' : '' ?>
                            >
                                Pending
                            </option>

                        </select>

                    </div>


                    <!-- TEKNISI -->

                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Teknisi
                        </label>

                        <input
                            type="text"
                            name="teknisi"
                            id="teknisi"
                            class="form-control manual-field"
                            value="<?= e($teknisi_form) ?>"
                        >

                    </div>


                    <!-- TINDAKAN -->

                    <div class="col-md-6">

                        <label class="form-label fw-semibold">
                            Tindakan
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="tindakan"
                            id="tindakan"
                            class="form-control manual-field"
                            value="<?= e($tindakan_form) ?>"
                            required
                        >

                    </div>


                    <!-- SPAREPART -->

                    <div class="col-12">

                        <label class="form-label fw-semibold">
                            Sparepart
                        </label>

                        <textarea
                            name="sparepart"
                            id="sparepart"
                            class="form-control manual-field"
                            rows="3"
                        ><?= e($sparepart_form) ?></textarea>

                    </div>


                    <!-- CATATAN -->

                    <div class="col-12">

                        <label class="form-label fw-semibold">
                            Catatan
                        </label>

                        <textarea
                            name="catatan"
                            id="catatan"
                            class="form-control manual-field"
                            rows="4"
                        ><?= e($catatan_form) ?></textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             BUTTON
        ================================================== -->

        <div class="card maintenance-card mb-4">

            <div class="card-body p-4">

                <div class="d-flex justify-content-end gap-2 flex-wrap">

                    <a
                        href="index.php"
                        class="btn btn-light border"
                    >
                        <i class="bi bi-x-lg me-1"></i>
                        Batal
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary-garuda"
                        id="btnSimpan"
                    >
                        <i class="bi bi-check-lg me-1"></i>
                        Simpan Perubahan
                    </button>

                </div>

            </div>

        </div>

    </form>

</div>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script
    src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"
></script>


<script>
document.addEventListener('DOMContentLoaded', function () {

    /* =====================================================
       SELECT2
    ====================================================== */

    if (window.jQuery && $('#id_komponen').length) {

        $('#id_komponen').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Pilih komponen...',
            allowClear: true
        });

    }


    /* =====================================================
       HELPER SET FIELD
    ====================================================== */

    function setField(id, value) {

        const element = document.getElementById(id);

        if (element) {
            element.value = value || '';
        }
    }


    /* =====================================================
       CLEAR COMPONENT FIELDS
    ====================================================== */

    function clearComponentFields() {

        setField('serial_number', '');
        setField('nama_bagian', '');
        setField('jenis_komponen', '');
        setField('nama_mesin', '');
        setField('nama_sub_mesin', '');
        setField('lokasi_penempatan', '');

    }


    /* =====================================================
       UPDATE COMPONENT INFO
    ====================================================== */

    function updateComponentInfo(element) {

        if (!element || !element.value) {

            clearComponentFields();

            return;
        }

        const selectedOption =
            element.options[element.selectedIndex];

        if (!selectedOption) {

            clearComponentFields();

            return;
        }

        setField(
            'serial_number',
            selectedOption.dataset.sn || ''
        );

        setField(
            'nama_bagian',
            selectedOption.dataset.namabagian || ''
        );

        setField(
            'jenis_komponen',
            selectedOption.dataset.jenisKomponen || ''
        );

        setField(
            'nama_mesin',
            selectedOption.dataset.mesin || ''
        );

        setField(
            'nama_sub_mesin',
            selectedOption.dataset.submesin || ''
        );

        setField(
            'lokasi_penempatan',
            selectedOption.dataset.lokasi || ''
        );
    }


    /* =====================================================
       COMPONENT CHANGE
    ====================================================== */

    const componentSelect =
        document.getElementById('id_komponen');

    if (componentSelect) {

        componentSelect.addEventListener(
            'change',
            function () {
                updateComponentInfo(this);
            }
        );

    }


    if (
        window.jQuery &&
        $('#id_komponen').length
    ) {

        $('#id_komponen').on(
            'select2:select',
            function () {
                updateComponentInfo(this);
            }
        );

        $('#id_komponen').on(
            'select2:clear',
            function () {
                clearComponentFields();
            }
        );
    }


    /* =====================================================
       FOTO
    ====================================================== */

    const gambarInput =
        document.getElementById('gambar');

    const previewWrapper =
        document.getElementById(
            'photoPreviewWrapper'
        );

    const hapusFoto =
        document.getElementById('hapus_foto');

    const maxFotoSize =
        50 * 1024 * 1024;


    /* =====================================================
       FOTO LAMA
    ====================================================== */

    const fotoLamaUrl =
        <?= json_encode($foto_url) ?>;


    /* =====================================================
       RESET PHOTO PREVIEW
    ====================================================== */

    function resetPhotoPreview() {

        if (!previewWrapper) {
            return;
        }

        if (fotoLamaUrl) {

            previewWrapper.innerHTML =
                '<img src="' +
                fotoLamaUrl +
                '" alt="Foto Komponen" id="photoPreview">';

        } else {

            previewWrapper.innerHTML =
                '<div class="photo-empty" id="photoEmpty">' +
                    '<i class="bi bi-image"></i>' +
                    '<div class="small mt-1">' +
                        'Belum ada foto' +
                    '</div>' +
                '</div>';
        }
    }


    /* =====================================================
       PREVIEW FOTO BARU
    ====================================================== */

    if (gambarInput) {

        gambarInput.addEventListener(
            'change',
            function () {

                const file = this.files &&
                    this.files.length
                    ? this.files[0]
                    : null;

                if (!file) {

                    resetPhotoPreview();

                    return;
                }


                /* -----------------------------------------
                   SIZE
                ----------------------------------------- */

                if (file.size > maxFotoSize) {

                    alert(
                        'Ukuran foto maksimal 50 MB.'
                    );

                    this.value = '';

                    resetPhotoPreview();

                    return;
                }


                /* -----------------------------------------
                   MIME
                ----------------------------------------- */

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

                    resetPhotoPreview();

                    return;
                }


                /* -----------------------------------------
                   PREVIEW
                ----------------------------------------- */

                const reader =
                    new FileReader();

                reader.onload =
                    function (event) {

                        if (!previewWrapper) {
                            return;
                        }

                        previewWrapper.innerHTML =
                            '<img src="' +
                            event.target.result +
                            '" alt="Preview Foto" id="photoPreview">';
                    };

                reader.readAsDataURL(file);


                /* -----------------------------------------
                   FOTO BARU MEMBATALKAN HAPUS FOTO LAMA
                ----------------------------------------- */

                if (hapusFoto) {
                    hapusFoto.checked = false;
                }

            }
        );
    }


    /* =====================================================
       HAPUS FOTO
    ====================================================== */

    if (hapusFoto) {

        hapusFoto.addEventListener(
            'change',
            function () {

                if (this.checked) {

                    if (gambarInput) {
                        gambarInput.value = '';
                    }

                    if (previewWrapper) {

                        previewWrapper.innerHTML =
                            '<div class="photo-empty">' +
                                '<i class="bi bi-image"></i>' +
                                '<div class="small mt-1">' +
                                    'Foto akan dihapus' +
                                '</div>' +
                            '</div>';
                    }

                } else {

                    resetPhotoPreview();

                }

            }
        );
    }


    /* =====================================================
       VALIDASI SUBMIT
    ====================================================== */

    const form =
        document.getElementById(
            'formMaintenance'
        );

    const btnSimpan =
        document.getElementById(
            'btnSimpan'
        );


    if (form) {

        form.addEventListener(
            'submit',
            function (event) {

                const component =
                    document.getElementById(
                        'id_komponen'
                    );

                const status =
                    document.getElementById(
                        'status'
                    );

                const tindakan =
                    document.getElementById(
                        'tindakan'
                    );

                const tanggal =
                    document.getElementById(
                        'tanggal'
                    );


                if (
                    !component ||
                    !component.value
                ) {

                    event.preventDefault();

                    alert(
                        'Silakan pilih komponen terlebih dahulu.'
                    );

                    if (
                        window.jQuery &&
                        $('#id_komponen').length
                    ) {

                        $('#id_komponen')
                            .select2('open');
                    }

                    return;
                }


                if (
                    !tanggal ||
                    !tanggal.value
                ) {

                    event.preventDefault();

                    alert(
                        'Tanggal maintenance wajib diisi.'
                    );

                    if (tanggal) {
                        tanggal.focus();
                    }

                    return;
                }


                if (
                    !status ||
                    !status.value
                ) {

                    event.preventDefault();

                    alert(
                        'Status maintenance wajib dipilih.'
                    );

                    if (status) {
                        status.focus();
                    }

                    return;
                }


                if (
                    !tindakan ||
                    !tindakan.value.trim()
                ) {

                    event.preventDefault();

                    alert(
                        'Tindakan maintenance wajib diisi.'
                    );

                    if (tindakan) {
                        tindakan.focus();
                    }

                    return;
                }


                /* -----------------------------------------
                   TAMPILKAN LOADING
                ----------------------------------------- */

                if (btnSimpan) {

                    btnSimpan.disabled = true;

                    btnSimpan.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-1" role="status"></span>' +
                        'Menyimpan...';
                }

            }
        );
    }

});
</script>


<?php
/* =========================================================
   FOOTER
========================================================= */

include "../template/footer.php";
?>