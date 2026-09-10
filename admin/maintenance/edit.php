<?php
session_start();
require_once "../../koneksi.php";

/* =========================================================
   PROTEKSI LOGIN & ADMIN
========================================================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$role = strtolower(trim($_SESSION['role'] ?? ''));
if ($role !== 'admin') {
    header("Location: ../../dashboard/index.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');

function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function old(array $data, string $key, string $default = ''): string
{
    return e($data[$key] ?? $default);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   AMBIL DATA MAINTENANCE
========================================================= */
$stmt = mysqli_prepare($conn, "
    SELECT
        rm.*,
        k.nama_bagian AS komponen_nama,
        k.serial_number AS komponen_serial,
        k.id_area AS komponen_id_area,
        k.id_jenis_mesin AS komponen_id_jenis_mesin,
        k.id_mesin AS komponen_id_mesin,
        k.id_sub_mesin AS komponen_id_sub_mesin,
        ab.lokasi AS area_lokasi,
        ab.nama_area,
        jm.nama_jenis_mesin,
        m.nama_mesin AS mesin_master,
        sm.nama_sub_mesin AS sub_mesin_master
    FROM riwayat_maintenance rm
    LEFT JOIN komponen k ON k.id = rm.id_komponen
    LEFT JOIN area_bagian ab ON ab.id = k.id_area
    LEFT JOIN jenis_mesin jm ON jm.id = k.id_jenis_mesin
    LEFT JOIN mesin m ON m.id = k.id_mesin
    LEFT JOIN sub_mesin sm ON sm.id = k.id_sub_mesin
    WHERE rm.id = ?
    LIMIT 1
");

if (!$stmt) {
    die('Gagal menyiapkan query: ' . e(mysqli_error($conn)));
}

mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$d = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$d) {
    header("Location: index.php");
    exit;
}

/* =========================================================
   DATA MASTER KOMPONEN
========================================================= */
$komponen = [];

$qKomponen = mysqli_query($conn, "
    SELECT
        k.id,
        k.nama_bagian,
        k.serial_number,
        k.id_area,
        k.id_jenis_mesin,
        k.id_mesin,
        k.id_sub_mesin,
        ab.lokasi,
        ab.nama_area,
        jm.nama_jenis_mesin,
        m.nama_mesin,
        sm.nama_sub_mesin
    FROM komponen k
    LEFT JOIN area_bagian ab ON ab.id = k.id_area
    LEFT JOIN jenis_mesin jm ON jm.id = k.id_jenis_mesin
    LEFT JOIN mesin m ON m.id = k.id_mesin
    LEFT JOIN sub_mesin sm ON sm.id = k.id_sub_mesin
    ORDER BY
        ab.lokasi,
        ab.nama_area,
        jm.nama_jenis_mesin,
        m.nama_mesin,
        sm.nama_sub_mesin,
        k.nama_bagian
");

if ($qKomponen) {
    while ($row = mysqli_fetch_assoc($qKomponen)) {
        $komponen[] = $row;
    }
}

$error = '';
$form = $d;

/* =========================================================
   DATA TEKNISI AKTIF
   Teknisi berasal dari Manajemen User.
   Tidak menggunakan no_reg.
========================================================= */
$teknisiList = [];

$qTeknisi = $conn->query("
    SELECT id, nama_lengkap
    FROM users
    WHERE LOWER(TRIM(role)) = 'user'
      AND LOWER(TRIM(status)) = 'aktif'
      AND nama_lengkap IS NOT NULL
      AND TRIM(nama_lengkap) <> ''
    ORDER BY nama_lengkap ASC
");

if ($qTeknisi) {
    while ($row = $qTeknisi->fetch_assoc()) {
        $teknisiList[] = $row;
    }
}

$teknisiId = 0;
$currentTeknisi = trim((string)($d['teknisi'] ?? ''));

foreach ($teknisiList as $teknisi) {
    if (strcasecmp(trim((string)$teknisi['nama_lengkap']), $currentTeknisi) === 0) {
        $teknisiId = (int)$teknisi['id'];
        break;
    }
}


/* =========================================================
   PROSES UPDATE
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $id_komponen = isset($_POST['id_komponen'])
        ? (int)$_POST['id_komponen']
        : 0;

    $tanggal = trim($_POST['tanggal'] ?? '');
    $status = trim($_POST['status'] ?? 'Pending');
    $teknisiId = (int)($_POST['teknisi_id'] ?? 0);
    $teknisi = '';
    $jenis = trim($_POST['jenis'] ?? '');
    $tindakan = trim($_POST['tindakan'] ?? '');
    $sparepart = trim($_POST['sparepart'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    /* Spesifikasi setelah maintenance */
    $brand_baru = trim($_POST['brand_baru'] ?? '');
    $tipe_baru = trim($_POST['tipe_baru'] ?? '');
    $part_number_baru = trim($_POST['part_number_baru'] ?? '');
    $daya_baru = trim($_POST['daya_baru'] ?? '');
    $io_address_baru = trim($_POST['io_address_baru'] ?? '');
    $input_voltage_baru = trim($_POST['input_voltage_baru'] ?? '');
    $frekuensi_input_baru = trim($_POST['frekuensi_input_baru'] ?? '');
    $arus_input_baru = trim($_POST['arus_input_baru'] ?? '');
    $output_baru = trim($_POST['output_baru'] ?? '');
    $frekuensi_output_baru = trim($_POST['frekuensi_output_baru'] ?? '');
    $ip_rating_baru = trim($_POST['ip_rating_baru'] ?? '');
    $kondisi_baru = trim($_POST['kondisi_baru'] ?? '');

    /* =====================================================
       VALIDASI
    ===================================================== */
    $allowedStatus = [
        'Pending',
        'Proses',
        'Selesai'
    ];

    if ($teknisiId <= 0) {
        $error = 'Teknisi wajib dipilih.';
    }

    if ($error === '' && $teknisiId > 0) {
        $stmtTeknisi = $conn->prepare("
            SELECT nama_lengkap
            FROM users
            WHERE id = ?
              AND LOWER(TRIM(role)) = 'user'
              AND LOWER(TRIM(status)) = 'aktif'
              AND nama_lengkap IS NOT NULL
              AND TRIM(nama_lengkap) <> ''
            LIMIT 1
        " );

        if (!$stmtTeknisi) {
            $error = 'Gagal memeriksa data teknisi: ' . $conn->error;
        } else {
            $stmtTeknisi->bind_param('i', $teknisiId);
            $stmtTeknisi->execute();
            $resultTeknisi = $stmtTeknisi->get_result();
            $dataTeknisi = $resultTeknisi->fetch_assoc();
            $stmtTeknisi->close();

            if (!$dataTeknisi) {
                $error = 'Teknisi yang dipilih tidak valid atau sudah tidak aktif.';
            } else {
                $teknisi = trim((string)$dataTeknisi['nama_lengkap']);
            }
        }
    }

    if ($id_komponen <= 0) {
        $error = 'Komponen maintenance wajib dipilih.';
    } elseif ($tanggal === '') {
        $error = 'Tanggal maintenance wajib diisi.';
    } elseif ($tindakan === '') {
        $error = 'Tindakan maintenance wajib diisi.';
    } elseif (!in_array($status, $allowedStatus, true)) {
        $error = 'Status maintenance tidak valid.';
    }

    /* =====================================================
       NORMALISASI TANGGAL
    ===================================================== */
    $tanggalDb = '';

    if ($error === '') {

        $tanggalDb = str_replace(
            'T',
            ' ',
            $tanggal
        );

        if (strlen($tanggalDb) === 16) {
            $tanggalDb .= ':00';
        }

        $dt = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $tanggalDb
        );

        if (!$dt) {
            $error = 'Format tanggal maintenance tidak valid.';
        }
    }

    /* =====================================================
       AMBIL SNAPSHOT KOMPONEN
    ===================================================== */
    $komp = null;

    if ($error === '') {

        $stmtK = mysqli_prepare($conn, "
            SELECT
                k.*,
                ab.lokasi,
                ab.nama_area,
                jm.nama_jenis_mesin,
                m.nama_mesin,
                sm.nama_sub_mesin
            FROM komponen k
            LEFT JOIN area_bagian ab
                ON ab.id = k.id_area
            LEFT JOIN jenis_mesin jm
                ON jm.id = k.id_jenis_mesin
            LEFT JOIN mesin m
                ON m.id = k.id_mesin
            LEFT JOIN sub_mesin sm
                ON sm.id = k.id_sub_mesin
            WHERE k.id = ?
            LIMIT 1
        ");

        if ($stmtK) {

            mysqli_stmt_bind_param(
                $stmtK,
                'i',
                $id_komponen
            );

            mysqli_stmt_execute($stmtK);

            $komp = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmtK)
            );

            mysqli_stmt_close($stmtK);
        }

        if (!$komp) {
            $error = 'Komponen yang dipilih tidak ditemukan.';
        }
    }

    /* =====================================================
       FOTO
    ===================================================== */
    $fotoBaru = $d['gambar'] ?? '';
    $uploadedPath = '';

    if (
        $error === '' &&
        isset($_FILES['gambar']) &&
        $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $file = $_FILES['gambar'];

        if ($file['error'] !== UPLOAD_ERR_OK) {

            $error = 'Upload foto gagal.';

        } elseif ($file['size'] > 2 * 1024 * 1024) {

            $error = 'Ukuran foto maksimal 2 MB.';

        } elseif (!is_uploaded_file($file['tmp_name'])) {

            $error = 'File foto tidak valid.';

        } else {

            $allowedMime = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            $finfo = finfo_open(
                FILEINFO_MIME_TYPE
            );

            $mime = finfo_file(
                $finfo,
                $file['tmp_name']
            );

            finfo_close($finfo);

            if (!isset($allowedMime[$mime])) {

                $error =
                    'Format foto harus JPG, PNG, atau WEBP.';

            } else {

                $targetDir =
                    '../uploads/maintenance/';

                if (
                    !is_dir($targetDir) &&
                    !mkdir(
                        $targetDir,
                        0777,
                        true
                    )
                ) {

                    $error =
                        'Folder upload foto tidak dapat dibuat.';

                } else {

                    try {

                        $random =
                            bin2hex(
                                random_bytes(5)
                            );

                    } catch (Exception $ex) {

                        $random = uniqid();
                    }

                    $fotoBaru =
                        'maintenance_' .
                        $id_komponen .
                        '_' .
                        time() .
                        '_' .
                        $random .
                        '.' .
                        $allowedMime[$mime];

                    $uploadedPath =
                        $targetDir .
                        $fotoBaru;

                    if (
                        !move_uploaded_file(
                            $file['tmp_name'],
                            $uploadedPath
                        )
                    ) {

                        $error =
                            'Gagal menyimpan foto ke server.';

                        $fotoBaru =
                            $d['gambar'] ?? '';

                        $uploadedPath = '';
                    }
                }
            }
        }
    }

    /* =====================================================
       UPDATE DATABASE
    ===================================================== */
    if ($error === '') {

        $namaBagian =
            $komp['nama_bagian'] ?? '';

        $serialNumber =
            $komp['serial_number'] ?? '';

        $kategori =
            $komp['kategori'] ?? '';

        $namaMesin =
            $komp['nama_mesin'] ?? '';

        $namaSubMesin =
            $komp['nama_sub_mesin'] ?? '';

        $lokasiPenempatan =
            $komp['lokasi'] ?? '';

        /* Jika kosong, pertahankan data lama */
        $brand_baru =
            $brand_baru !== ''
                ? $brand_baru
                : ($d['brand_baru'] ?? '');

        $tipe_baru =
            $tipe_baru !== ''
                ? $tipe_baru
                : ($d['tipe_baru'] ?? '');

        $part_number_baru =
            $part_number_baru !== ''
                ? $part_number_baru
                : ($d['part_number_baru'] ?? '');

        $daya_baru =
            $daya_baru !== ''
                ? $daya_baru
                : ($d['daya_baru'] ?? '');

        $io_address_baru =
            $io_address_baru !== ''
                ? $io_address_baru
                : ($d['io_address_baru'] ?? '');

        $input_voltage_baru =
            $input_voltage_baru !== ''
                ? $input_voltage_baru
                : ($d['input_voltage_baru'] ?? '');

        $frekuensi_input_baru =
            $frekuensi_input_baru !== ''
                ? $frekuensi_input_baru
                : ($d['frekuensi_input_baru'] ?? '');

        $arus_input_baru =
            $arus_input_baru !== ''
                ? $arus_input_baru
                : ($d['arus_input_baru'] ?? '');

        $output_baru =
            $output_baru !== ''
                ? $output_baru
                : ($d['output_baru'] ?? '');

        $frekuensi_output_baru =
            $frekuensi_output_baru !== ''
                ? $frekuensi_output_baru
                : ($d['frekuensi_output_baru'] ?? '');

        $ip_rating_baru =
            $ip_rating_baru !== ''
                ? $ip_rating_baru
                : ($d['ip_rating_baru'] ?? '');

        $kondisi_baru =
            $kondisi_baru !== ''
                ? $kondisi_baru
                : ($d['kondisi_baru'] ?? '');

        $sql = "
            UPDATE riwayat_maintenance SET

                id_komponen = ?,
                tanggal = ?,
                tindakan = ?,
                status = ?,
                teknisi = ?,
                jenis = ?,
                sparepart = ?,

                brand_baru = ?,
                tipe_baru = ?,
                part_number_baru = ?,
                daya_baru = ?,
                io_address_baru = ?,
                input_voltage_baru = ?,
                frekuensi_input_baru = ?,
                arus_input_baru = ?,
                output_baru = ?,
                frekuensi_output_baru = ?,
                ip_rating_baru = ?,
                kondisi_baru = ?,

                catatan = ?,

                serial_number = ?,
                nama_bagian = ?,
                kategori = ?,
                nama_mesin = ?,
                nama_sub_mesin = ?,
                lokasi_penempatan = ?,
                gambar = ?

            WHERE id = ?
        ";

        $stmtU =
            mysqli_prepare(
                $conn,
                $sql
            );

        if (!$stmtU) {

            $error =
                'Gagal menyiapkan update: ' .
                mysqli_error($conn);

        } else {

            $types =
                'isssssssssssssssssssssssssssi';

            mysqli_stmt_bind_param(
                $stmtU,
                $types,

                $id_komponen,
                $tanggalDb,
                $tindakan,
                $status,
                $teknisi,
                $jenis,
                $sparepart,

                $brand_baru,
                $tipe_baru,
                $part_number_baru,
                $daya_baru,
                $io_address_baru,
                $input_voltage_baru,
                $frekuensi_input_baru,
                $arus_input_baru,
                $output_baru,
                $frekuensi_output_baru,
                $ip_rating_baru,
                $kondisi_baru,

                $catatan,

                $serialNumber,
                $namaBagian,
                $kategori,
                $namaMesin,
                $namaSubMesin,
                $lokasiPenempatan,
                $fotoBaru,

                $id
            );

            if (
                mysqli_stmt_execute($stmtU)
            ) {

                mysqli_stmt_close($stmtU);

                /* =================================================
                   SINKRONISASI KONDISI KOMPONEN
                ================================================= */
                $kondisiKomponen = '';

                if ($status === 'Selesai') {

                    $kondisiKomponen =
                        'Baik';

                } elseif ($status === 'Proses') {

                    $kondisiKomponen =
                        'Dalam Perbaikan';

                } elseif ($status === 'Pending') {

                    $kondisiKomponen =
                        'Perlu Pemeriksaan';
                }

                if ($kondisiKomponen !== '') {

                    $stmtC =
                        mysqli_prepare(
                            $conn,
                            "
                            UPDATE komponen
                            SET kondisi = ?
                            WHERE id = ?
                            "
                        );

                    if ($stmtC) {

                        mysqli_stmt_bind_param(
                            $stmtC,
                            'si',
                            $kondisiKomponen,
                            $id_komponen
                        );

                        mysqli_stmt_execute(
                            $stmtC
                        );

                        mysqli_stmt_close(
                            $stmtC
                        );
                    }
                }

                /* =================================================
                   HAPUS FOTO LAMA
                ================================================= */
                if (
                    $uploadedPath !== '' &&
                    !empty($d['gambar']) &&
                    $d['gambar'] !== $fotoBaru
                ) {

                    $oldPath =
                        '../uploads/maintenance/' .
                        basename($d['gambar']);

                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                header(
                    'Location: detail.php?id=' .
                    $id .
                    '&update=berhasil'
                );

                exit;

            } else {

                $error =
                    'Gagal memperbarui data maintenance: ' .
                    mysqli_stmt_error($stmtU);

                mysqli_stmt_close(
                    $stmtU
                );

                if (
                    $uploadedPath !== '' &&
                    is_file($uploadedPath)
                ) {
                    @unlink($uploadedPath);
                }
            }
        }
    }

    $form =
        array_merge(
            $d,
            $_POST
        );

    $form['gambar'] =
        $fotoBaru;
}

/* =========================================================
   FORMAT TANGGAL
========================================================= */
$tanggalValue = '';

if (!empty($form['tanggal'])) {

    $timestamp =
        strtotime(
            $form['tanggal']
        );

    if ($timestamp !== false) {

        $tanggalValue =
            date(
                'Y-m-d\TH:i',
                $timestamp
            );
    }
}

/* =========================================================
   SIDEBAR
========================================================= */
$active_menu = 'maintenance';

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit Maintenance • Admin</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <style>

        :root {
            --blue: #092f63;
            --blue2: #123f7a;
            --light: #eaf2ff;
            --text: #172033;
            --muted: #788396;
            --border: #e7ebf1;
            --bg: #f5f7fb;
            --white: #fff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .page-content {
            margin-left: 265px;
            padding: 28px 30px 50px;
            min-height: 100vh;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 24px;
        }

        .mobile-menu {
            display: none;
            border: 1px solid var(--border);
            background: #fff;
            border-radius: 10px;
            width: 42px;
            height: 42px;
            color: var(--blue);
            font-size: 20px;
        }

        .eyebrow {
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
            margin-bottom: 4px;
        }

        h1 {
            font-size: 25px;
            font-weight: 700;
            margin: 0;
        }

        .subtitle {
            color: var(--muted);
            margin: 5px 0 0;
        }

        .back-btn {
            border: 1px solid var(--border);
            background: #fff;
            color: var(--text);
            padding: 10px 15px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
        }

        .card-box {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(20,42,75,.04);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-head {
            padding: 18px 21px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .card-head i {
            width: 35px;
            height: 35px;
            display: grid;
            place-items: center;
            background: var(--light);
            color: var(--blue2);
            border-radius: 10px;
            font-size: 17px;
        }

        .card-head h2 {
            font-size: 15px;
            font-weight: 700;
            margin: 0;
        }

        .card-body {
            padding: 21px;
        }

        .form-label {
            font-weight: 600;
            font-size: 13px;
            margin-bottom: 7px;
        }

        .form-control,
        .form-select {
            border-color: var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 13px;
            min-height: 43px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #9db8dc;
            box-shadow: 0 0 0 .2rem rgba(18,63,122,.08);
        }

        textarea.form-control {
            min-height: 110px;
            resize: vertical;
        }

        .readonly {
            background: #f8fafc;
        }

        .info-strip {
            background: #f7f9fc;
            border: 1px dashed #dbe2eb;
            border-radius: 12px;
            padding: 13px 15px;
            color: #596579;
            font-size: 12px;
        }

        .info-strip strong {
            color: var(--text);
        }

        .spec-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .section-note {
            font-size: 12px;
            color: var(--muted);
            margin: -8px 0 18px;
        }

        .current-photo {
            width: 150px;
            height: 105px;
            object-fit: cover;
            border-radius: 11px;
            border: 1px solid var(--border);
            background: #f8fafc;
        }

        .no-photo {
            width: 150px;
            height: 105px;
            border-radius: 11px;
            border: 1px dashed #cbd4df;
            display: grid;
            place-items: center;
            color: #9aa5b5;
            font-size: 12px;
            text-align: center;
            padding: 10px;
        }

        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-main {
            background: var(--blue2);
            color: #fff;
            border: 0;
            border-radius: 10px;
            padding: 11px 18px;
            font-weight: 600;
        }

        .btn-main:hover {
            background: var(--blue);
            color: #fff;
        }

        .btn-cancel {
            background: #fff;
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 10px;
            padding: 10px 17px;
            text-decoration: none;
            font-weight: 600;
        }

        .alert {
            border-radius: 11px;
            border: 0;
        }

        .small-muted {
            font-size: 11px;
            color: var(--muted);
        }

        @media (max-width: 992px) {

            .page-content {
                margin-left: 0;
                padding: 22px 18px 40px;
            }

            .mobile-menu {
                display: inline-grid;
                place-items: center;
            }

            .topbar .back-btn {
                display: none;
            }

            .spec-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {

            .page-content {
                padding: 18px 13px 35px;
            }

            h1 {
                font-size: 21px;
            }

            .card-body {
                padding: 16px;
            }

            .spec-grid {
                grid-template-columns: 1fr;
            }

            .actions > * {
                width: 100%;
                text-align: center;
            }
        }

    </style>

</head>

<body>

<?php include "../sidebar.php"; ?>

<main class="page-content">

    <div class="topbar">

        <div class="d-flex align-items-start gap-3">

            <button
                type="button"
                class="mobile-menu"
                id="mobileToggle"
                aria-label="Buka menu"
            >
                <i class="bi bi-list"></i>
            </button>

            <div>

                <div class="eyebrow">
                    RIWAYAT MAINTENANCE / EDIT
                </div>

                <h1>
                    Edit Maintenance
                </h1>

                <p class="subtitle">
                    Perbarui data pekerjaan, teknisi, status,
                    dan spesifikasi setelah maintenance.
                </p>

            </div>

        </div>

        <a
            href="detail.php?id=<?= (int)$id ?>"
            class="back-btn"
        >
            <i class="bi bi-arrow-left me-1"></i>
            Kembali
        </a>

    </div>

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <i class="bi bi-exclamation-circle me-2"></i>

            <?= e($error) ?>

        </div>

    <?php endif; ?>

    <form
        method="post"
        enctype="multipart/form-data"
        autocomplete="off"
    >

        <input
            type="hidden"
            name="update"
            value="1"
        >

        <!-- =====================================================
             INFORMASI MAINTENANCE
        ====================================================== -->

        <div class="card-box">

            <div class="card-head">

                <i class="bi bi-tools"></i>

                <h2>
                    Informasi Maintenance
                </h2>

            </div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-lg-7">

                        <label class="form-label">
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
                                -- Pilih Komponen --
                            </option>

                            <?php foreach ($komponen as $k): ?>

                                <option
                                    value="<?= (int)$k['id'] ?>"
                                    <?= (
                                        (int)($form['id_komponen'] ?? 0)
                                        ===
                                        (int)$k['id']
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >

                                    <?= e($k['nama_bagian']) ?>

                                    —

                                    <?= e(
                                        $k['nama_mesin'] ?: '-'
                                    ) ?>

                                    /

                                    <?= e(
                                        $k['nama_sub_mesin'] ?: '-'
                                    ) ?>

                                    <?php if (!empty($k['serial_number'])): ?>

                                        —
                                        SN:
                                        <?= e($k['serial_number']) ?>

                                    <?php endif; ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <div class="small-muted mt-1">
                            Komponen menjadi dasar identitas
                            mesin, sub mesin, dan lokasi.
                        </div>

                    </div>

                    <div class="col-lg-5">

                        <label class="form-label">
                            Tanggal & Waktu
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="datetime-local"
                            name="tanggal"
                            class="form-control"
                            value="<?= e($tanggalValue) ?>"
                            required
                        >

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Jenis Maintenance
                        </label>

                        <?php
                        $jenisNow =
                            $form['jenis'] ?? '';
                        ?>

                        <select
                            name="jenis"
                            class="form-select"
                        >

                            <option value="">
                                -- Pilih Jenis --
                            </option>

                            <?php
                            $jenisList = [
                                'Preventive',
                                'Corrective',
                                'Predictive',
                                'Inspection',
                                'Overhaul',
                                'Lainnya'
                            ];
                            ?>

                            <?php foreach ($jenisList as $j): ?>

                                <option
                                    value="<?= e($j) ?>"
                                    <?= (
                                        $jenisNow === $j
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    <?= e($j) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Teknisi
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="teknisi_id"
                            class="form-select"
                            required
                        >
                            <option value="">-- Pilih Teknisi --</option>

                            <?php foreach ($teknisiList as $teknisiOption): ?>
                                <option
                                    value="<?= (int)$teknisiOption['id'] ?>"
                                    <?= ((int)$teknisiId === (int)$teknisiOption['id']) ? 'selected' : '' ?>
                                >
                                    <?= e($teknisiOption['nama_lengkap']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="small-muted mt-1">
                            Pilih teknisi dari User aktif di Manajemen User.
                        </div>

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Status
                            <span class="text-danger">*</span>
                        </label>

                        <?php
                        $statusNow =
                            $form['status'] ?? 'Pending';
                        ?>

                        <select
                            name="status"
                            class="form-select"
                            required
                        >

                            <?php foreach (
                                [
                                    'Pending',
                                    'Proses',
                                    'Selesai'
                                ] as $s
                            ): ?>

                                <option
                                    value="<?= e($s) ?>"
                                    <?= (
                                        $statusNow === $s
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    <?= e($s) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="col-12">

                        <label class="form-label">
                            Tindakan Maintenance
                            <span class="text-danger">*</span>
                        </label>

                        <textarea
                            name="tindakan"
                            class="form-control"
                            required
                            placeholder="Contoh: cek kondisi drive, bersihkan panel, kencangkan terminal..."
                        ><?= old(
                            $form,
                            'tindakan'
                        ) ?></textarea>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Sparepart yang Diganti
                        </label>

                        <textarea
                            name="sparepart"
                            class="form-control"
                            style="min-height:90px"
                            placeholder="Isi jika ada sparepart yang diganti."
                        ><?= old(
                            $form,
                            'sparepart'
                        ) ?></textarea>

                    </div>

                    <div class="col-md-6">

                        <label class="form-label">
                            Catatan / Hasil Pekerjaan
                        </label>

                        <textarea
                            name="catatan"
                            class="form-control"
                            style="min-height:90px"
                            placeholder="Catatan hasil pemeriksaan atau pekerjaan."
                        ><?= old(
                            $form,
                            'catatan'
                        ) ?></textarea>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================================
             IDENTITAS KOMPONEN
        ====================================================== -->

        <div class="card-box">

            <div class="card-head">

                <i class="bi bi-diagram-3"></i>

                <h2>
                    Identitas Komponen
                </h2>

            </div>

            <div class="card-body">

                <div class="info-strip mb-3">

                    <strong>
                        Snapshot data:
                    </strong>

                    identitas diambil dari master komponen
                    yang dipilih dan disimpan bersama
                    riwayat maintenance.

                </div>

                <div class="row g-3">

                    <div class="col-md-4">

                        <label class="form-label">
                            Lokasi
                        </label>

                        <input
                            class="form-control readonly"
                            value="<?= old(
                                $form,
                                'area_lokasi',
                                $d['area_lokasi'] ?? '-'
                            ) ?>"
                            readonly
                        >

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Area
                        </label>

                        <input
                            class="form-control readonly"
                            value="<?= old(
                                $form,
                                'nama_area',
                                $d['nama_area'] ?? '-'
                            ) ?>"
                            readonly
                        >

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Jenis Mesin
                        </label>

                        <input
                            class="form-control readonly"
                            value="<?= old(
                                $form,
                                'nama_jenis_mesin',
                                $d['nama_jenis_mesin'] ?? '-'
                            ) ?>"
                            readonly
                        >

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Mesin
                        </label>

                        <input
                            class="form-control readonly"
                            value="<?= old(
                                $form,
                                'mesin_master',
                                $d['mesin_master'] ?? '-'
                            ) ?>"
                            readonly
                        >

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Sub Mesin
                        </label>

                        <input
                            class="form-control readonly"
                            value="<?= old(
                                $form,
                                'sub_mesin_master',
                                $d['sub_mesin_master'] ?? '-'
                            ) ?>"
                            readonly
                        >

                    </div>

                    <div class="col-md-4">

                        <label class="form-label">
                            Nama Komponen
                        </label>

                        <input
                            class="form-control readonly"
                            value="<?= old(
                                $form,
                                'komponen_nama',
                                $d['komponen_nama'] ?? '-'
                            ) ?>"
                            readonly
                        >

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================================
             SPESIFIKASI SETELAH MAINTENANCE
        ====================================================== -->

        <div class="card-box">

            <div class="card-head">

                <i class="bi bi-sliders"></i>

                <h2>
                    Spesifikasi Setelah Maintenance
                </h2>

            </div>

            <div class="card-body">

                <p class="section-note">

                    Isi bagian yang berubah.
                    Data lama akan tetap dipertahankan
                    apabila kolom dibiarkan kosong.

                </p>

                <div class="spec-grid">

                    <div>

                        <label class="form-label">
                            Brand Baru
                        </label>

                        <input
                            name="brand_baru"
                            class="form-control"
                            value="<?= old(
                                $form,
                                'brand_baru'
                            ) ?>"
                            placeholder="Contoh: Danfoss"
                        >

                    </div>

                    <div>

                        <label class="form-label">
                            Tipe Baru
                        </label>

                        <input
                            name="tipe_baru"
                            class="form-control"
                            value="<?= old(
                                $form,
                                'tipe_baru'
                            ) ?>"
                            placeholder="Tipe / model setelah maintenance"
                        >

                    </div>

                    <div>

                        <label class="form-label">
                            Part Number Baru
                        </label>

                        <input
                            name="part_number_baru"
                            class="form-control"
                            value="<?= old(
                                $form,
                                'part_number_baru'
                            ) ?>"
                        >

                    </div>

                    <div>

                        <label class="form-label">
                            Daya Baru
                        </label>

                        <input
                            name="daya_baru"
                            class="form-control"
                            value="<?= old(
                                $form,
                                'daya_baru'
                            ) ?>"
                            placeholder="Contoh: 1.5 kW"
                        >

                    </div>

                    <div>

                        <label class="form-label">
                            IO Address Baru
                        </label>

                        <input
                            name="io_address_baru"
                            class="form-control"
                            value="<?= old(
                                $form,
                                'io_address_baru'
                            ) ?>"
                        >

                    </div>

                    <div>

                        <label class="form-label">
                            Input Voltage Baru
                        </label>

                        <input
                            name="input_voltage_baru"
                            class="form-control"
                            value="<?= old(
                                $form,
                                'input_voltage_baru'
                            ) ?>"
                        >

                    </div>

                    <div>

                        <label class="form-label">
                            Frekuensi Input Baru
                        </label>

                        <input
                            name="frekuensi_input_baru"
                            class="form-control"
                            value="<?= old(
                                $form,
                                'frekuensi_input_baru'
                            ) ?>"
                        >

                    </div>

                    <div>

                        <label class="form-label">
                            Arus Input Baru
                        </label>

                        <input
                            name="arus_input_baru"
                            class="form-control"
                            value="<?= old(
                                $form,
                                'arus_input_baru'
                            ) ?>"
                        >

                    </div>

                    <div>

                        <label class="form-label">
                            Output Baru
                        </label>

                        <input
                            name="output_baru"
                            class="form-control"
                            value="<?= old(
                                $form,
                                'output_baru'
                            ) ?>"
                        >

                    </div>

                    <div>

                        <label class="form-label">
                            Frekuensi Output Baru
                        </label>

                        <input
                            name="frekuensi_output_baru"
                            class="form-control"
                            value="<?= old(
                                $form,
                                'frekuensi_output_baru'
                            ) ?>"
                        >

                    </div>

                    <div>

                        <label class="form-label">
                            IP Rating Baru
                        </label>

                        <input
                            name="ip_rating_baru"
                            class="form-control"
                            value="<?= old(
                                $form,
                                'ip_rating_baru'
                            ) ?>"
                        >

                    </div>

                    <div>

                        <label class="form-label">
                            Kondisi Setelah Maintenance
                        </label>

                        <?php
                        $kondisiNow =
                            $form['kondisi_baru'] ?? '';
                        ?>

                        <select
                            name="kondisi_baru"
                            class="form-select"
                        >

                            <option value="">
                                -- Pilih Kondisi --
                            </option>

                            <?php
                            $kondisiList = [
                                'Baik',
                                'Dalam Perbaikan',
                                'Perlu Pemeriksaan'
                            ];
                            ?>

                            <?php foreach (
                                $kondisiList as $kondisi
                            ): ?>

                                <option
                                    value="<?= e($kondisi) ?>"
                                    <?= (
                                        $kondisiNow === $kondisi
                                    )
                                        ? 'selected'
                                        : ''
                                    ?>
                                >
                                    <?= e($kondisi) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================================
             DOKUMENTASI
        ====================================================== -->

        <div class="card-box">

            <div class="card-head">

                <i class="bi bi-camera"></i>

                <h2>
                    Dokumentasi Maintenance
                </h2>

            </div>

            <div class="card-body">

                <div class="row g-4 align-items-start">

                    <div class="col-md-4">

                        <div class="form-label">
                            Foto Saat Ini
                        </div>

                        <?php if (!empty($form['gambar'])): ?>

                            <img
                                src="../uploads/maintenance/<?= e(
                                    basename(
                                        $form['gambar']
                                    )
                                ) ?>"
                                class="current-photo"
                                alt="Foto maintenance"
                            >

                        <?php else: ?>

                            <div class="no-photo">

                                <div>

                                    <i class="bi bi-image fs-4 mb-1"></i>

                                    <br>

                                    Tidak ada foto

                                </div>

                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="col-md-8">

                        <label class="form-label">
                            Ganti Foto
                        </label>

                        <input
                            type="file"
                            name="gambar"
                            class="form-control"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <div class="small-muted mt-2">

                            Opsional.
                            JPG, PNG, atau WEBP,
                            maksimal 2 MB.

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- =====================================================
             ACTION
        ====================================================== -->

        <div class="actions">

            <a
                href="detail.php?id=<?= (int)$id ?>"
                class="btn-cancel"
            >
                Batal
            </a>

            <button
                type="submit"
                class="btn-main"
            >

                <i class="bi bi-check2-circle me-1"></i>

                Simpan Perubahan

            </button>

        </div>

    </form>

</main>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>