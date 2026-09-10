<?php

session_start();
require_once "../../koneksi.php";

/* =========================================================
   PROTEKSI LOGIN
========================================================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

/* =========================================================
   PROTEKSI ADMIN
========================================================= */
if (strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') {
    header("Location: ../../dashboard/index.php");
    exit;
}

/* =========================================================
   TIMEZONE
========================================================= */
date_default_timezone_set('Asia/Jakarta');

/* =========================================================
   ACTIVE SIDEBAR
   Sidebar sepenuhnya menggunakan admin/sidebar.php
========================================================= */
$active_menu = 'komponen';

/* =========================================================
   HELPER
========================================================= */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function tampil($value)
{
    return ($value !== null && trim((string)$value) !== '')
        ? e($value)
        : '-';
}

function badgeKondisi($kondisi)
{
    $kondisi = trim((string)$kondisi);

    if (strcasecmp($kondisi, 'Baik') === 0) {
        return '
            <span class="badge kondisi-baik">
                <i class="bi bi-check-circle-fill"></i>
                Baik
            </span>
        ';
    }

    if (stripos($kondisi, 'Dalam Perbaikan') !== false) {
        return '
            <span class="badge kondisi-perbaikan">
                <i class="bi bi-tools"></i>
                Dalam Perbaikan
            </span>
        ';
    }

    if (stripos($kondisi, 'Perlu Pemeriksaan') !== false) {
        return '
            <span class="badge kondisi-periksa">
                <i class="bi bi-exclamation-circle-fill"></i>
                Perlu Pemeriksaan
            </span>
        ';
    }

    return '
        <span class="badge kondisi-default">
            ' . e($kondisi ?: 'Belum Ditentukan') . '
        </span>
    ';
}

/* =========================================================
   AMBIL ID KOMPONEN
========================================================= */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   AMBIL DATA KOMPONEN
========================================================= */
$stmt = mysqli_prepare(
    $conn,
    "
    SELECT
        k.*
    FROM komponen k
    WHERE k.id = ?
    LIMIT 1
    "
);

if (!$stmt) {
    die("Query data komponen gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$komponen = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$komponen) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   DATA MASTER
========================================================= */

/* ---------------------------------------------------------
   LOKASI
--------------------------------------------------------- */
$lokasiList = [];

$qLokasi = mysqli_query(
    $conn,
    "
    SELECT DISTINCT lokasi
    FROM area_bagian
    WHERE lokasi IS NOT NULL
      AND TRIM(lokasi) <> ''
    ORDER BY lokasi ASC
    "
);

if ($qLokasi) {
    while ($row = mysqli_fetch_assoc($qLokasi)) {
        $lokasiList[] = $row['lokasi'];
    }
}

/* ---------------------------------------------------------
   AREA
--------------------------------------------------------- */
$areaList = [];

$qArea = mysqli_query(
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

if ($qArea) {
    while ($row = mysqli_fetch_assoc($qArea)) {
        $areaList[] = $row;
    }
}

/* ---------------------------------------------------------
   JENIS MESIN
--------------------------------------------------------- */
$jenisList = [];

$qJenis = mysqli_query(
    $conn,
    "
    SELECT
        id,
        id_area,
        nama_jenis_mesin
    FROM jenis_mesin
    ORDER BY nama_jenis_mesin ASC
    "
);

if ($qJenis) {
    while ($row = mysqli_fetch_assoc($qJenis)) {
        $jenisList[] = $row;
    }
}

/* ---------------------------------------------------------
   MESIN
--------------------------------------------------------- */
$mesinList = [];

$qMesin = mysqli_query(
    $conn,
    "
    SELECT
        id,
        id_area,
        id_jenis_mesin,
        nama_mesin
    FROM mesin
    ORDER BY nama_mesin ASC
    "
);

if ($qMesin) {
    while ($row = mysqli_fetch_assoc($qMesin)) {
        $mesinList[] = $row;
    }
}

/* ---------------------------------------------------------
   SUB MESIN
--------------------------------------------------------- */
$subMesinList = [];

$qSubMesin = mysqli_query(
    $conn,
    "
    SELECT
        id,
        id_mesin,
        nama_sub_mesin
    FROM sub_mesin
    ORDER BY nama_sub_mesin ASC
    "
);

if ($qSubMesin) {
    while ($row = mysqli_fetch_assoc($qSubMesin)) {
        $subMesinList[] = $row;
    }
}

/* =========================================================
   NILAI AWAL FORM
========================================================= */
$old = $komponen;
$error = '';

/* =========================================================
   LOKASI AWAL
========================================================= */
$lokasiAwal = trim(
    (string)($komponen['lokasi'] ?? '')
);

if ($lokasiAwal === '') {

    foreach ($areaList as $area) {

        if (
            (int)$area['id']
            ===
            (int)($komponen['id_area'] ?? 0)
        ) {
            $lokasiAwal = trim(
                (string)$area['lokasi']
            );

            break;
        }
    }
}

/* =========================================================
   PROSES UPDATE
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =====================================================
       AMBIL DATA FORM
    ===================================================== */

    $lokasi = trim(
        $_POST['lokasi'] ?? ''
    );

    $id_area = (int)(
        $_POST['id_area'] ?? 0
    );

    $id_jenis_mesin = (int)(
        $_POST['id_jenis_mesin'] ?? 0
    );

    $id_mesin = (int)(
        $_POST['id_mesin'] ?? 0
    );

    $id_sub_mesin = (int)(
        $_POST['id_sub_mesin'] ?? 0
    );

    $nama_bagian = trim(
        $_POST['nama_bagian'] ?? ''
    );

    $jenis_komponen = trim(
        $_POST['jenis_komponen'] ?? ''
    );

    $serial_number = trim(
        $_POST['serial_number'] ?? ''
    );

    $spesifikasi = trim(
        $_POST['spesifikasi'] ?? ''
    );

    $kategori = trim(
        $_POST['kategori'] ?? ''
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
        $_POST['kondisi'] ?? 'Baik'
    );

    $keterangan = trim(
        $_POST['keterangan'] ?? ''
    );

    /* =====================================================
       VALIDASI DASAR
    ===================================================== */

    if ($lokasi === '') {

        $error = 'Lokasi wajib dipilih.';

    } elseif ($id_area <= 0) {

        $error = 'Area wajib dipilih.';

    } elseif ($id_jenis_mesin <= 0) {

        $error = 'Jenis mesin wajib dipilih.';

    } elseif ($id_mesin <= 0) {

        $error = 'Mesin wajib dipilih.';

    } elseif ($id_sub_mesin <= 0) {

        $error = 'Sub mesin wajib dipilih.';

    } elseif ($nama_bagian === '') {

        $error = 'Nama bagian wajib diisi.';
    }

    /* =====================================================
       VALIDASI AREA
    ===================================================== */

    $areaData = null;

    if ($error === '') {

        $stmt = mysqli_prepare(
            $conn,
            "
            SELECT
                id,
                nama_area,
                lokasi
            FROM area_bagian
            WHERE id = ?
            LIMIT 1
            "
        );

        if (!$stmt) {

            $error =
                'Query validasi area gagal: '
                . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $id_area
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $areaData = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if (!$areaData) {

                $error =
                    'Area yang dipilih tidak ditemukan.';

            } elseif (
                strcasecmp(
                    trim($areaData['lokasi']),
                    trim($lokasi)
                ) !== 0
            ) {

                $error =
                    'Lokasi dan area yang dipilih tidak sesuai.';
            }
        }
    }

    /* =====================================================
       VALIDASI JENIS MESIN
    ===================================================== */

    $jenisData = null;

    if ($error === '') {

        $stmt = mysqli_prepare(
            $conn,
            "
            SELECT
                id,
                id_area,
                nama_jenis_mesin
            FROM jenis_mesin
            WHERE id = ?
            LIMIT 1
            "
        );

        if (!$stmt) {

            $error =
                'Query validasi jenis mesin gagal: '
                . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $id_jenis_mesin
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $jenisData = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if (!$jenisData) {

                $error =
                    'Jenis mesin yang dipilih tidak ditemukan.';

            } elseif (
                (int)$jenisData['id_area']
                !==
                $id_area
            ) {

                $error =
                    'Jenis mesin tidak sesuai dengan area yang dipilih.';
            }
        }
    }

    /* =====================================================
       VALIDASI MESIN
    ===================================================== */

    $mesinData = null;

    if ($error === '') {

        $stmt = mysqli_prepare(
            $conn,
            "
            SELECT
                id,
                id_area,
                id_jenis_mesin,
                nama_mesin
            FROM mesin
            WHERE id = ?
            LIMIT 1
            "
        );

        if (!$stmt) {

            $error =
                'Query validasi mesin gagal: '
                . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $id_mesin
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $mesinData = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if (!$mesinData) {

                $error =
                    'Mesin yang dipilih tidak ditemukan.';

            } elseif (
                (int)$mesinData['id_area']
                !==
                $id_area
            ) {

                $error =
                    'Mesin tidak sesuai dengan area yang dipilih.';

            } elseif (
                (int)$mesinData['id_jenis_mesin']
                !==
                $id_jenis_mesin
            ) {

                $error =
                    'Mesin tidak sesuai dengan jenis mesin yang dipilih.';
            }
        }
    }

    /* =====================================================
       VALIDASI SUB MESIN
    ===================================================== */

    $subData = null;

    if ($error === '') {

        $stmt = mysqli_prepare(
            $conn,
            "
            SELECT
                id,
                id_mesin,
                nama_sub_mesin
            FROM sub_mesin
            WHERE id = ?
            LIMIT 1
            "
        );

        if (!$stmt) {

            $error =
                'Query validasi sub mesin gagal: '
                . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $id_sub_mesin
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $subData = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if (!$subData) {

                $error =
                    'Sub mesin yang dipilih tidak ditemukan.';

            } elseif (
                (int)$subData['id_mesin']
                !==
                $id_mesin
            ) {

                $error =
                    'Sub mesin tidak sesuai dengan mesin yang dipilih.';
            }
        }
    }

    /* =====================================================
       SINKRONISASI DATA TEKS
    ===================================================== */

    $namaMesinFinal =
        $mesinData['nama_mesin']
        ??
        ($old['mesin'] ?? '');

    $namaSubMesinFinal =
        $subData['nama_sub_mesin']
        ??
        ($old['sub_mesin'] ?? '');

    $lokasiFinal =
        $areaData['lokasi']
        ??
        $lokasi;

    /* =====================================================
       UPLOAD GAMBAR
    ===================================================== */

    $gambarLama =
        trim((string)($old['gambar'] ?? ''));

    $gambarBaru = $gambarLama;

    $fileBaru = '';

    if (
        $error === ''
        &&
        isset($_FILES['gambar'])
        &&
        $_FILES['gambar']['error']
            !== UPLOAD_ERR_NO_FILE
    ) {

        if (
            $_FILES['gambar']['error']
            !== UPLOAD_ERR_OK
        ) {

            $error =
                'Gagal mengunggah gambar.';

        } elseif (
            $_FILES['gambar']['size']
            >
            2 * 1024 * 1024
        ) {

            $error =
                'Ukuran gambar maksimal 2 MB.';

        } else {

            $tmpName =
                $_FILES['gambar']['tmp_name'];

            $originalName =
                $_FILES['gambar']['name'];

            $extension = strtolower(
                pathinfo(
                    $originalName,
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

                $error =
                    'Format gambar harus JPG, JPEG, PNG, atau WEBP.';

            } elseif (
                !@getimagesize($tmpName)
            ) {

                $error =
                    'File yang diunggah bukan gambar yang valid.';

            } else {

                $uploadDir =
                    __DIR__
                    . '/../../uploads/komponen/';

                if (!is_dir($uploadDir)) {

                    if (
                        !mkdir(
                            $uploadDir,
                            0777,
                            true
                        )
                    ) {

                        $error =
                            'Folder upload gambar tidak dapat dibuat.';
                    }
                }

                if ($error === '') {

                    try {

                        $randomName =
                            bin2hex(
                                random_bytes(5)
                            );

                    } catch (Throwable $e) {

                        $randomName =
                            uniqid();
                    }

                    $fileBaru =
                        'komponen_'
                        . date('Ymd_His')
                        . '_'
                        . $randomName
                        . '.'
                        . $extension;

                    $destination =
                        $uploadDir
                        . $fileBaru;

                    if (
                        !move_uploaded_file(
                            $tmpName,
                            $destination
                        )
                    ) {

                        $error =
                            'Gambar gagal disimpan ke server.';

                        $fileBaru = '';

                    } else {

                        $gambarBaru =
                            $fileBaru;
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
            UPDATE komponen SET

                serial_number = ?,

                id_area = ?,

                id_jenis_mesin = ?,

                id_mesin = ?,

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

            if ($fileBaru !== '') {

                $pathBaru =
                    __DIR__
                    . '/../../uploads/komponen/'
                    . $fileBaru;

                if (file_exists($pathBaru)) {
                    @unlink($pathBaru);
                }
            }

            $error =
                'Query update gagal dipersiapkan: '
                . mysqli_error($conn);

        } else {

            /*
             * Parameter:
             *
             * 1  serial_number        s
             * 2  id_area              i
             * 3  id_jenis_mesin       i
             * 4  id_mesin             i
             * 5  id_sub_mesin         i
             *
             * 6  mesin                 s
             * 7  sub_mesin             s
             * 8  nama_bagian           s
             * 9  jenis_komponen        s
             * 10 spesifikasi           s
             * 11 kategori              s
             * 12 brand                 s
             * 13 tipe                  s
             * 14 part_number           s
             * 15 daya                  s
             * 16 io_address            s
             * 17 ip_address            s
             * 18 input_voltage         s
             * 19 frekuensi_input       s
             * 20 arus_input            s
             * 21 output                s
             * 22 frekuensi_output      s
             * 23 ip_rating             s
             * 24 lokasi                s
             * 25 kondisi               s
             * 26 keterangan            s
             * 27 gambar                s
             *
             * 28 id                    i
             */

            $types =
                's'
                . 'iiii'
                . str_repeat('s', 22)
                . 'i';

            mysqli_stmt_bind_param(
                $stmt,
                $types,

                $serial_number,

                $id_area,

                $id_jenis_mesin,

                $id_mesin,

                $id_sub_mesin,

                $namaMesinFinal,

                $namaSubMesinFinal,

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

                $lokasiFinal,

                $kondisi,

                $keterangan,

                $gambarBaru,

                $id
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                /* =========================================
                   HAPUS GAMBAR LAMA
                ========================================= */

                if (
                    $fileBaru !== ''
                    &&
                    $gambarLama !== ''
                    &&
                    $gambarLama !== $gambarBaru
                ) {

                    $oldPath =
                        __DIR__
                        . '/../../uploads/komponen/'
                        . basename($gambarLama);

                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                header(
                    "Location: detail.php?id="
                    . $id
                    . "&updated=1"
                );

                exit;

            } else {

                $error =
                    'Data gagal diperbarui: '
                    . mysqli_stmt_error($stmt);

                mysqli_stmt_close($stmt);

                /* Hapus file baru jika update gagal */
                if ($fileBaru !== '') {

                    $pathBaru =
                        __DIR__
                        . '/../../uploads/komponen/'
                        . $fileBaru;

                    if (file_exists($pathBaru)) {
                        @unlink($pathBaru);
                    }
                }
            }
        }
    }

    /* =====================================================
       PERTAHANKAN INPUT JIKA ERROR
    ===================================================== */

    $old = array_merge(
        $old,
        [
            'lokasi' =>
                $lokasi,

            'id_area' =>
                $id_area,

            'id_jenis_mesin' =>
                $id_jenis_mesin,

            'id_mesin' =>
                $id_mesin,

            'id_sub_mesin' =>
                $id_sub_mesin,

            'mesin' =>
                $namaMesinFinal,

            'sub_mesin' =>
                $namaSubMesinFinal,

            'nama_bagian' =>
                $nama_bagian,

            'jenis_komponen' =>
                $jenis_komponen,

            'serial_number' =>
                $serial_number,

            'spesifikasi' =>
                $spesifikasi,

            'kategori' =>
                $kategori,

            'brand' =>
                $brand,

            'tipe' =>
                $tipe,

            'part_number' =>
                $part_number,

            'daya' =>
                $daya,

            'io_address' =>
                $io_address,

            'ip_address' =>
                $ip_address,

            'input_voltage' =>
                $input_voltage,

            'frekuensi_input' =>
                $frekuensi_input,

            'arus_input' =>
                $arus_input,

            'output' =>
                $output,

            'frekuensi_output' =>
                $frekuensi_output,

            'ip_rating' =>
                $ip_rating,

            'kondisi' =>
                $kondisi,

            'keterangan' =>
                $keterangan
        ]
    );

    $lokasiAwal = $lokasi;
}

/* =========================================================
   NILAI TERPILIH
========================================================= */

$selectedLokasi = trim(
    (string)(
        $old['lokasi']
        ??
        $lokasiAwal
        ??
        ''
    )
);

$selectedArea =
    (int)($old['id_area'] ?? 0);

$selectedJenis =
    (int)($old['id_jenis_mesin'] ?? 0);

$selectedMesin =
    (int)($old['id_mesin'] ?? 0);

$selectedSubMesin =
    (int)($old['id_sub_mesin'] ?? 0);

$gambarSaatIni = trim(
    (string)($old['gambar'] ?? '')
);

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Komponen • Admin Inventory</title>

    <!-- GOOGLE FONT -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- BOOTSTRAP -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- BOOTSTRAP ICONS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        :root {
            --primary:#075eaa;
            --primary-dark:#064b89;
            --primary-light:#eaf4ff;

            --text:#172033;
            --muted:#7b8494;

            --bg:#f5f7fb;
            --white:#ffffff;
            --border:#e7ebf1;

            --danger:#dc3545;
        }

        * {
            box-sizing:border-box;
        }

        body {
            margin:0;
            background:var(--bg);
            color:var(--text);
            font-family:'Poppins',sans-serif;
            font-size:14px;
        }

        a {
            text-decoration:none;
        }

        /* =====================================================
           MAIN
           Lebar sidebar mengikuti admin/sidebar.php
        ===================================================== */

        .main {
            margin-left:240px;
            min-height:100vh;
        }

        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {
            height:76px;

            background:#fff;

            border-bottom:1px solid var(--border);

            display:flex;
            align-items:center;
            justify-content:space-between;

            padding:0 28px;

            position:sticky;
            top:0;

            z-index:100;
        }

        .topbar-left {
            display:flex;
            align-items:center;
            gap:12px;
        }

        .page-title {
            margin:0;

            font-size:20px;
            font-weight:700;
        }

        .page-subtitle {
            color:var(--muted);

            font-size:12px;

            margin-top:2px;
        }

        .admin-user {
            display:flex;
            align-items:center;
            gap:10px;
        }

        .admin-avatar {
            width:38px;
            height:38px;

            border-radius:50%;

            display:flex;
            align-items:center;
            justify-content:center;

            background:var(--primary-light);
            color:var(--primary);

            font-size:17px;
        }

        .admin-name {
            font-size:13px;
            font-weight:600;
        }

        .admin-role {
            font-size:11px;
            color:var(--muted);
        }

        /*
         * HANYA TOMBOL TOPBAR.
         * Bukan sidebar.
         */
        .mobile-menu {
            display:none;

            width:40px;
            height:40px;

            border:1px solid var(--border);
            border-radius:9px;

            background:#fff;
            color:var(--text);

            align-items:center;
            justify-content:center;

            font-size:21px;

            cursor:pointer;
        }

        .mobile-menu:hover {
            background:var(--primary-light);
            color:var(--primary);
        }

        /* =====================================================
           CONTENT
        ===================================================== */

        .content {
            padding:26px 28px 40px;

            max-width:1500px;
        }

        /* =====================================================
           BREADCRUMB
        ===================================================== */

        .breadcrumb-custom {
            display:flex;
            align-items:center;
            flex-wrap:wrap;

            gap:7px;

            margin-bottom:18px;

            color:var(--muted);

            font-size:12px;
        }

        .breadcrumb-custom a {
            color:var(--primary);
            font-weight:500;
        }

        /* =====================================================
           CARD
        ===================================================== */

        .card-custom {
            background:#fff;

            border:1px solid var(--border);

            border-radius:14px;

            box-shadow:
                0 3px 14px rgba(20,40,80,.035);
        }

        .card-header-custom {
            padding:19px 22px;

            border-bottom:1px solid var(--border);

            display:flex;
            align-items:center;
            justify-content:space-between;

            gap:15px;
        }

        .card-header-title {
            font-size:16px;
            font-weight:700;

            margin:0;
        }

        .card-header-subtitle {
            color:var(--muted);

            font-size:11px;

            margin-top:3px;
        }

        .card-body-custom {
            padding:22px;
        }

        /* =====================================================
           FORM
        ===================================================== */

        .section-title {
            display:flex;
            align-items:center;

            gap:9px;

            font-size:14px;
            font-weight:700;

            margin-bottom:17px;

            color:var(--text);
        }

        .section-title i {
            color:var(--primary);
            font-size:17px;
        }

        .section-divider {
            height:1px;

            background:var(--border);

            margin:26px 0;
        }

        .form-label {
            font-size:12px;
            font-weight:600;

            color:#414b5d;

            margin-bottom:7px;
        }

        .form-control,
        .form-select {
            min-height:43px;

            border-color:#dfe4ec;

            border-radius:8px;

            font-size:13px;

            color:var(--text);
        }

        textarea.form-control {
            min-height:105px;

            resize:vertical;
        }

        .form-control:focus,
        .form-select:focus {
            border-color:var(--primary);

            box-shadow:
                0 0 0 .2rem
                rgba(7,94,170,.10);
        }

        .form-control[readonly] {
            background:#f5f7fa;
            color:#687386;
        }

        .form-help {
            color:var(--muted);

            font-size:10px;

            margin-top:5px;
        }

        .required {
            color:#dc3545;
        }

        /* =====================================================
           LOCATION FLOW
        ===================================================== */

        .flow-box {
            border:1px solid #dce7f4;

            background:#f8fbff;

            border-radius:12px;

            padding:18px;
        }

        .flow-number {
            width:24px;
            height:24px;

            border-radius:50%;

            display:inline-flex;

            align-items:center;
            justify-content:center;

            background:var(--primary-light);
            color:var(--primary);

            font-size:11px;
            font-weight:700;

            margin-right:7px;
        }

        .flow-note {
            font-size:11px;

            color:var(--muted);

            margin-bottom:17px;
        }

        /* =====================================================
           IMAGE
        ===================================================== */

        .image-current {
            width:180px;
            height:135px;

            border-radius:10px;

            border:1px solid var(--border);

            overflow:hidden;

            background:#f5f7fb;

            display:flex;

            align-items:center;
            justify-content:center;

            margin-bottom:12px;
        }

        .image-current img {
            width:100%;
            height:100%;

            object-fit:contain;
        }

        .image-placeholder {
            color:#9aa4b3;

            text-align:center;

            font-size:11px;
        }

        .image-placeholder i {
            display:block;

            font-size:35px;

            margin-bottom:5px;
        }

        .upload-box {
            border:1px dashed #cbd5e1;

            border-radius:10px;

            padding:15px;

            background:#fafcff;
        }

        /* =====================================================
           BADGE
        ===================================================== */

        .badge {
            border-radius:7px;

            padding:6px 9px;

            font-size:10px;

            font-weight:600;

            display:inline-flex;
            align-items:center;
            gap:5px;
        }

        .kondisi-baik {
            background:#e8f8ee;
            color:#178344;
        }

        .kondisi-perbaikan {
            background:#fff0e5;
            color:#c35b12;
        }

        .kondisi-periksa {
            background:#fff8d9;
            color:#9b7a00;
        }

        .kondisi-default {
            background:#edf0f4;
            color:#667085;
        }

        /* =====================================================
           ALERT
        ===================================================== */

        .alert-custom {
            border:0;

            border-radius:10px;

            font-size:12px;

            padding:12px 14px;
        }

        /* =====================================================
           BUTTON
        ===================================================== */

        .btn {
            border-radius:8px;

            font-size:12px;

            font-weight:600;

            padding:10px 15px;
        }

        .btn-primary-custom {
            background:var(--primary);

            border-color:var(--primary);

            color:#fff;
        }

        .btn-primary-custom:hover {
            background:var(--primary-dark);

            border-color:var(--primary-dark);

            color:#fff;
        }

        .btn-light-custom {
            background:#fff;

            border:1px solid #dfe4ec;

            color:#596477;
        }

        .btn-light-custom:hover {
            background:#f5f7fb;

            color:var(--text);
        }

        /* =====================================================
           RESPONSIVE
           Sidebar mobile ditangani sepenuhnya oleh sidebar.php
        ===================================================== */

        @media (max-width:1100px) {

            .main {
                margin-left:0;
            }

            .mobile-menu {
                display:inline-flex;
            }

            .topbar {
                padding:0 18px;
            }

            .content {
                padding:20px 18px 35px;
            }

        }

        @media (max-width:576px) {

            .topbar {
                height:68px;
            }

            .page-title {
                font-size:17px;
            }

            .page-subtitle {
                display:none;
            }

            .admin-name,
            .admin-role {
                display:none;
            }

            .card-header-custom,
            .card-body-custom {
                padding:16px;
            }

            .flow-box {
                padding:14px;
            }

            .image-current {
                width:100%;
                height:180px;
            }

        }

    </style>

</head>

<body>


<!-- =========================================================
     MAIN
========================================================= -->

<div class="main">


    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <header class="topbar">

        <div class="topbar-left">

            <!--
                ID HARUS mobileToggle
                karena dibaca oleh admin/sidebar.php
            -->
            <button
                type="button"
                class="mobile-menu"
                id="mobileToggle"
                aria-label="Buka menu"
            >
                <i class="bi bi-list"></i>
            </button>

            <div>

                <h1 class="page-title">
                    Edit Komponen
                </h1>

                <div class="page-subtitle">
                    Perbarui data komponen mesin
                </div>

            </div>

        </div>


        <!-- ADMIN -->
        <div class="admin-user">

            <div class="admin-avatar">
                <i class="bi bi-person-fill"></i>
            </div>

            <div>

                <div class="admin-name">
                    <?= e(
                        $_SESSION['nama_lengkap']
                        ??
                        $_SESSION['username']
                        ??
                        'Admin'
                    ) ?>
                </div>

                <div class="admin-role">
                    Administrator
                </div>

            </div>

        </div>

    </header>


    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <main class="content">


        <!-- =================================================
             BREADCRUMB
        ================================================== -->

        <div class="breadcrumb-custom">

            <a href="../index.php">
                Admin
            </a>

            <i class="bi bi-chevron-right"></i>

            <a href="index.php">
                Data Komponen
            </a>

            <i class="bi bi-chevron-right"></i>

            <span>
                Edit Komponen
            </span>

        </div>


        <!-- =================================================
             ERROR
        ================================================== -->

        <?php if ($error !== ''): ?>

            <div
                class="alert alert-danger alert-custom d-flex align-items-center gap-2 mb-4"
                role="alert"
            >

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
            id="formKomponen"
        >


            <div class="card-custom">


                <!-- =========================================
                     HEADER
                ========================================== -->

                <div class="card-header-custom">

                    <div>

                        <h2 class="card-header-title">
                            Edit Data Komponen
                        </h2>

                        <div class="card-header-subtitle">
                            Perbarui informasi komponen sesuai kondisi terbaru
                        </div>

                    </div>

                    <?= badgeKondisi(
                        $old['kondisi'] ?? 'Baik'
                    ) ?>

                </div>


                <!-- =========================================
                     BODY
                ========================================== -->

                <div class="card-body-custom">


                    <!-- =====================================
                         POSISI KOMPONEN
                    ====================================== -->

                    <div class="section-title">

                        <i class="bi bi-diagram-3-fill"></i>

                        Posisi Komponen

                    </div>


                    <div class="flow-box">

                        <div class="flow-note">

                            Pilih lokasi terlebih dahulu.
                            Pilihan berikutnya akan menyesuaikan
                            dengan data yang tersedia.

                        </div>


                        <div class="row g-3">


                            <!-- =================================
                                 LOKASI
                            ================================== -->

                            <div class="col-lg-3 col-md-6">

                                <label class="form-label">

                                    <span class="flow-number">
                                        1
                                    </span>

                                    Lokasi

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <select
                                    name="lokasi"
                                    id="lokasi"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        -- Pilih Lokasi --
                                    </option>

                                    <?php foreach ($lokasiList as $lokasiItem): ?>

                                        <option
                                            value="<?= e($lokasiItem) ?>"
                                            <?= strcasecmp(
                                                $selectedLokasi,
                                                $lokasiItem
                                            ) === 0
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            <?= e($lokasiItem) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- =================================
                                 AREA
                            ================================== -->

                            <div class="col-lg-3 col-md-6">

                                <label class="form-label">

                                    <span class="flow-number">
                                        2
                                    </span>

                                    Area

                                    <span class="required">
                                        *
                                    </span>

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

                                    <?php foreach ($areaList as $area): ?>

                                        <option
                                            value="<?= (int)$area['id'] ?>"
                                            data-lokasi="<?= e(
                                                $area['lokasi']
                                            ) ?>"
                                            <?= $selectedArea
                                                ===
                                                (int)$area['id']
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            <?= e(
                                                $area['nama_area']
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- =================================
                                 JENIS MESIN
                            ================================== -->

                            <div class="col-lg-3 col-md-6">

                                <label class="form-label">

                                    <span class="flow-number">
                                        3
                                    </span>

                                    Jenis Mesin

                                    <span class="required">
                                        *
                                    </span>

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

                                    <?php foreach ($jenisList as $jenis): ?>

                                        <option
                                            value="<?= (int)$jenis['id'] ?>"
                                            data-area="<?= (int)$jenis['id_area'] ?>"
                                            <?= $selectedJenis
                                                ===
                                                (int)$jenis['id']
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            <?= e(
                                                $jenis['nama_jenis_mesin']
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- =================================
                                 MESIN
                            ================================== -->

                            <div class="col-lg-3 col-md-6">

                                <label class="form-label">

                                    <span class="flow-number">
                                        4
                                    </span>

                                    Mesin

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <select
                                    name="id_mesin"
                                    id="id_mesin"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        -- Pilih Mesin --
                                    </option>

                                    <?php foreach ($mesinList as $mesin): ?>

                                        <option
                                            value="<?= (int)$mesin['id'] ?>"
                                            data-area="<?= (int)$mesin['id_area'] ?>"
                                            data-jenis="<?= (int)$mesin['id_jenis_mesin'] ?>"
                                            <?= $selectedMesin
                                                ===
                                                (int)$mesin['id']
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            <?= e(
                                                $mesin['nama_mesin']
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- =================================
                                 SUB MESIN
                            ================================== -->

                            <div class="col-lg-3 col-md-6">

                                <label class="form-label">

                                    <span class="flow-number">
                                        5
                                    </span>

                                    Sub Mesin

                                    <span class="required">
                                        *
                                    </span>

                                </label>


                                <select
                                    name="id_sub_mesin"
                                    id="id_sub_mesin"
                                    class="form-select"
                                    required
                                >

                                    <option value="">
                                        -- Pilih Sub Mesin --
                                    </option>

                                    <?php foreach ($subMesinList as $sub): ?>

                                        <option
                                            value="<?= (int)$sub['id'] ?>"
                                            data-mesin="<?= (int)$sub['id_mesin'] ?>"
                                            <?= $selectedSubMesin
                                                ===
                                                (int)$sub['id']
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            <?= e(
                                                $sub['nama_sub_mesin']
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- =================================
                                 LOKASI TERPILIH
                            ================================== -->

                            <div class="col-lg-9 col-md-6">

                                <label class="form-label">
                                    Lokasi Terpilih
                                </label>


                                <input
                                    type="text"
                                    id="lokasi_display"
                                    class="form-control"
                                    value="<?= e($selectedLokasi) ?>"
                                    readonly
                                >


                                <div class="form-help">
                                    Lokasi otomatis mengikuti Area yang dipilih.
                                </div>

                            </div>


                        </div>

                    </div>


                    <div class="section-divider"></div>


                    <!-- =====================================
                         IDENTITAS KOMPONEN
                    ====================================== -->

                    <div class="section-title">

                        <i class="bi bi-pc-display-horizontal"></i>

                        Identitas Komponen

                    </div>


                    <div class="row g-3">


                        <div class="col-lg-6">

                            <label class="form-label">

                                Nama Bagian

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                name="nama_bagian"
                                class="form-control"
                                value="<?= e(
                                    $old['nama_bagian'] ?? ''
                                ) ?>"
                                placeholder="Contoh: Panel Kontrol Utama"
                                required
                            >

                        </div>


                        <div class="col-lg-6">

                            <label class="form-label">
                                Jenis Komponen
                            </label>


                            <input
                                type="text"
                                name="jenis_komponen"
                                class="form-control"
                                value="<?= e(
                                    $old['jenis_komponen'] ?? ''
                                ) ?>"
                                placeholder="Contoh: Inverter, PLC, Sensor"
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Serial Number
                            </label>


                            <input
                                type="text"
                                name="serial_number"
                                class="form-control"
                                value="<?= e(
                                    $old['serial_number'] ?? ''
                                ) ?>"
                                placeholder="Masukkan serial number"
                            >

                        </div>


                        <div class="col-md-3">

                            <label class="form-label">
                                Kategori
                            </label>


                            <input
                                type="text"
                                name="kategori"
                                class="form-control"
                                value="<?= e(
                                    $old['kategori'] ?? ''
                                ) ?>"
                                placeholder="Contoh: Elektrik"
                            >

                        </div>


                        <div class="col-md-3">

                            <label class="form-label">
                                Brand
                            </label>


                            <input
                                type="text"
                                name="brand"
                                class="form-control"
                                value="<?= e(
                                    $old['brand'] ?? ''
                                ) ?>"
                                placeholder="Contoh: Schneider"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Tipe
                            </label>


                            <input
                                type="text"
                                name="tipe"
                                class="form-control"
                                value="<?= e(
                                    $old['tipe'] ?? ''
                                ) ?>"
                                placeholder="Tipe komponen"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Part Number
                            </label>


                            <input
                                type="text"
                                name="part_number"
                                class="form-control"
                                value="<?= e(
                                    $old['part_number'] ?? ''
                                ) ?>"
                                placeholder="Part number"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Spesifikasi
                            </label>


                            <input
                                type="text"
                                name="spesifikasi"
                                class="form-control"
                                value="<?= e(
                                    $old['spesifikasi'] ?? ''
                                ) ?>"
                                placeholder="Spesifikasi singkat"
                            >

                        </div>


                    </div>


                    <div class="section-divider"></div>


                    <!-- =====================================
                         SPESIFIKASI TEKNIS
                    ====================================== -->

                    <div class="section-title">

                        <i class="bi bi-sliders"></i>

                        Spesifikasi Teknis

                    </div>


                    <div class="row g-3">


                        <div class="col-md-4">

                            <label class="form-label">
                                Daya
                            </label>

                            <input
                                type="text"
                                name="daya"
                                class="form-control"
                                value="<?= e(
                                    $old['daya'] ?? ''
                                ) ?>"
                                placeholder="Contoh: 5.5 kW"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                I/O Address
                            </label>

                            <input
                                type="text"
                                name="io_address"
                                class="form-control"
                                value="<?= e(
                                    $old['io_address'] ?? ''
                                ) ?>"
                                placeholder="Contoh: I0.0 / Q0.0"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                IP Address
                            </label>

                            <input
                                type="text"
                                name="ip_address"
                                class="form-control"
                                value="<?= e(
                                    $old['ip_address'] ?? ''
                                ) ?>"
                                placeholder="Contoh: 192.168.1.10"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Input Voltage
                            </label>

                            <input
                                type="text"
                                name="input_voltage"
                                class="form-control"
                                value="<?= e(
                                    $old['input_voltage'] ?? ''
                                ) ?>"
                                placeholder="Contoh: 220 VAC"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Frekuensi Input
                            </label>

                            <input
                                type="text"
                                name="frekuensi_input"
                                class="form-control"
                                value="<?= e(
                                    $old['frekuensi_input'] ?? ''
                                ) ?>"
                                placeholder="Contoh: 50 Hz"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Arus Input
                            </label>

                            <input
                                type="text"
                                name="arus_input"
                                class="form-control"
                                value="<?= e(
                                    $old['arus_input'] ?? ''
                                ) ?>"
                                placeholder="Contoh: 10 A"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Output
                            </label>

                            <input
                                type="text"
                                name="output"
                                class="form-control"
                                value="<?= e(
                                    $old['output'] ?? ''
                                ) ?>"
                                placeholder="Output komponen"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Frekuensi Output
                            </label>

                            <input
                                type="text"
                                name="frekuensi_output"
                                class="form-control"
                                value="<?= e(
                                    $old['frekuensi_output'] ?? ''
                                ) ?>"
                                placeholder="Contoh: 0-50 Hz"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                IP Rating
                            </label>

                            <input
                                type="text"
                                name="ip_rating"
                                class="form-control"
                                value="<?= e(
                                    $old['ip_rating'] ?? ''
                                ) ?>"
                                placeholder="Contoh: IP65"
                            >

                        </div>


                    </div>


                    <div class="section-divider"></div>


                    <!-- =====================================
                         KONDISI
                    ====================================== -->

                    <div class="section-title">

                        <i class="bi bi-clipboard-check"></i>

                        Kondisi Komponen

                    </div>


                    <div class="row g-3">


                        <div class="col-md-4">

                            <label class="form-label">
                                Kondisi
                            </label>


                            <select
                                name="kondisi"
                                class="form-select"
                            >

                                <option
                                    value="Baik"
                                    <?= (
                                        ($old['kondisi'] ?? '')
                                        ===
                                        'Baik'
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
                                        ($old['kondisi'] ?? '')
                                        ===
                                        'Perlu Pemeriksaan'
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Perlu Pemeriksaan
                                </option>


                                <option
                                    value="Dalam Perbaikan"
                                    <?= stripos(
                                        (string)(
                                            $old['kondisi'] ?? ''
                                        ),
                                        'Dalam Perbaikan'
                                    ) !== false
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    Dalam Perbaikan
                                </option>

                            </select>

                        </div>


                        <div class="col-md-8">

                            <label class="form-label">
                                Keterangan
                            </label>


                            <textarea
                                name="keterangan"
                                class="form-control"
                                placeholder="Tambahkan keterangan komponen..."
                            ><?= e(
                                $old['keterangan'] ?? ''
                            ) ?></textarea>

                        </div>


                    </div>


                    <div class="section-divider"></div>


                    <!-- =====================================
                         GAMBAR
                    ====================================== -->

                    <div class="section-title">

                        <i class="bi bi-image"></i>

                        Foto Komponen

                    </div>


                    <div class="row g-4 align-items-start">


                        <div class="col-md-4">

                            <label class="form-label">
                                Foto Saat Ini
                            </label>


                            <div class="image-current">

                                <?php

                                $gambarPath =
                                    '../../uploads/komponen/'
                                    . basename(
                                        $gambarSaatIni
                                    );

                                $gambarFile =
                                    __DIR__
                                    . '/../../uploads/komponen/'
                                    . basename(
                                        $gambarSaatIni
                                    );

                                ?>

                                <?php if (
                                    $gambarSaatIni !== ''
                                    &&
                                    file_exists($gambarFile)
                                ): ?>

                                    <img
                                        src="<?= e(
                                            $gambarPath
                                        ) ?>"
                                        alt="Foto Komponen"
                                    >

                                <?php else: ?>

                                    <div class="image-placeholder">

                                        <i class="bi bi-image"></i>

                                        Tidak ada foto

                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="col-md-8">

                            <label class="form-label">
                                Ganti Foto Komponen
                            </label>


                            <div class="upload-box">

                                <input
                                    type="file"
                                    name="gambar"
                                    id="gambar"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                >


                                <div class="form-help">

                                    Kosongkan jika tidak ingin mengganti foto.

                                    Format JPG, JPEG, PNG, WEBP.

                                    Maksimal 2 MB.

                                </div>

                            </div>

                        </div>


                    </div>


                    <!-- =====================================
                         ACTION
                    ====================================== -->

                    <div class="section-divider"></div>


                    <div
                        class="d-flex justify-content-between align-items-center flex-wrap gap-2"
                    >

                        <a
                            href="detail.php?id=<?= $id ?>"
                            class="btn btn-light-custom"
                        >

                            <i class="bi bi-arrow-left me-1"></i>

                            Batal

                        </a>


                        <button
                            type="submit"
                            name="update"
                            class="btn btn-primary-custom"
                        >

                            <i class="bi bi-check-lg me-1"></i>

                            Simpan Perubahan

                        </button>

                    </div>


                </div>

            </div>

        </form>


    </main>

</div>


<!-- =========================================================
     SIDEBAR ADMIN
     
     PENTING:
     Sidebar hanya berasal dari file pusat ini.
     
     admin/komponen/edit.php
             ↓
     ../sidebar.php
             ↓
     /inventory_mesin/admin/sidebar.php
========================================================= -->

<?php include "../sidebar.php"; ?>


<script>

/* =========================================================
   FORM HIERARKI
   Lokasi → Area → Jenis Mesin → Mesin → Sub Mesin
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const lokasi =
            document.getElementById('lokasi');

        const area =
            document.getElementById('id_area');

        const jenis =
            document.getElementById('id_jenis_mesin');

        const mesin =
            document.getElementById('id_mesin');

        const subMesin =
            document.getElementById('id_sub_mesin');

        const lokasiDisplay =
            document.getElementById('lokasi_display');


        if (
            !lokasi ||
            !area ||
            !jenis ||
            !mesin ||
            !subMesin ||
            !lokasiDisplay
        ) {
            return;
        }


        /* =====================================================
           FILTER AREA BERDASARKAN LOKASI
        ===================================================== */

        function filterArea(
            resetChild = true
        ) {

            const lokasiValue =
                lokasi.value;

            Array.from(
                area.options
            ).forEach(
                function (option, index) {

                    if (index === 0) {

                        option.hidden = false;

                        return;
                    }

                    const optionLokasi =
                        (
                            option.dataset.lokasi
                            ||
                            ''
                        ).trim();

                    const cocok =
                        lokasiValue !== ''
                        &&
                        optionLokasi
                            .toLowerCase()
                        ===
                        lokasiValue
                            .toLowerCase();

                    option.hidden =
                        !cocok;

                    if (
                        !cocok
                        &&
                        option.selected
                    ) {
                        option.selected =
                            false;
                    }

                }
            );


            if (resetChild) {

                area.value = '';

                jenis.value = '';

                mesin.value = '';

                subMesin.value = '';
            }


            lokasiDisplay.value =
                lokasiValue;
        }


        /* =====================================================
           FILTER JENIS MESIN BERDASARKAN AREA
        ===================================================== */

        function filterJenis(
            resetChild = true
        ) {

            const areaValue =
                area.value;


            Array.from(
                jenis.options
            ).forEach(
                function (option, index) {

                    if (index === 0) {

                        option.hidden = false;

                        return;
                    }


                    const optionArea =
                        option.dataset.area
                        ||
                        '';

                    const cocok =
                        areaValue !== ''
                        &&
                        optionArea
                        ===
                        areaValue;

                    option.hidden =
                        !cocok;


                    if (
                        !cocok
                        &&
                        option.selected
                    ) {

                        option.selected =
                            false;
                    }

                }
            );


            if (resetChild) {

                jenis.value = '';

                mesin.value = '';

                subMesin.value = '';
            }


            const selectedOption =
                area.options[
                    area.selectedIndex
                ];


            if (
                selectedOption
                &&
                selectedOption.dataset.lokasi
            ) {

                lokasiDisplay.value =
                    selectedOption.dataset.lokasi;
            }

        }


        /* =====================================================
           FILTER MESIN BERDASARKAN JENIS MESIN
        ===================================================== */

        function filterMesin(
            resetChild = true
        ) {

            const areaValue =
                area.value;

            const jenisValue =
                jenis.value;


            Array.from(
                mesin.options
            ).forEach(
                function (option, index) {

                    if (index === 0) {

                        option.hidden = false;

                        return;
                    }


                    const optionArea =
                        option.dataset.area
                        ||
                        '';

                    const optionJenis =
                        option.dataset.jenis
                        ||
                        '';


                    const cocok =
                        areaValue !== ''
                        &&
                        jenisValue !== ''
                        &&
                        optionArea
                        ===
                        areaValue
                        &&
                        optionJenis
                        ===
                        jenisValue;


                    option.hidden =
                        !cocok;


                    if (
                        !cocok
                        &&
                        option.selected
                    ) {

                        option.selected =
                            false;
                    }

                }
            );


            if (resetChild) {

                mesin.value = '';

                subMesin.value = '';
            }

        }


        /* =====================================================
           FILTER SUB MESIN BERDASARKAN MESIN
        ===================================================== */

        function filterSubMesin(
            resetChild = true
        ) {

            const mesinValue =
                mesin.value;


            Array.from(
                subMesin.options
            ).forEach(
                function (option, index) {

                    if (index === 0) {

                        option.hidden = false;

                        return;
                    }


                    const optionMesin =
                        option.dataset.mesin
                        ||
                        '';


                    const cocok =
                        mesinValue !== ''
                        &&
                        optionMesin
                        ===
                        mesinValue;


                    option.hidden =
                        !cocok;


                    if (
                        !cocok
                        &&
                        option.selected
                    ) {

                        option.selected =
                            false;
                    }

                }
            );


            if (resetChild) {

                subMesin.value = '';
            }

        }


        /* =====================================================
           EVENT LOKASI
        ===================================================== */

        lokasi.addEventListener(
            'change',
            function () {

                filterArea(true);

            }
        );


        /* =====================================================
           EVENT AREA
        ===================================================== */

        area.addEventListener(
            'change',
            function () {

                filterJenis(true);

            }
        );


        /* =====================================================
           EVENT JENIS MESIN
        ===================================================== */

        jenis.addEventListener(
            'change',
            function () {

                filterMesin(true);

            }
        );


        /* =====================================================
           EVENT MESIN
        ===================================================== */

        mesin.addEventListener(
            'change',
            function () {

                filterSubMesin(true);

            }
        );


        /* =====================================================
           INITIAL LOAD
           Pertahankan data lama
        ===================================================== */

        filterArea(false);

        filterJenis(false);

        filterMesin(false);

        filterSubMesin(false);

        lokasiDisplay.value =
            lokasi.value;

    }
);

</script>

</body>
</html>