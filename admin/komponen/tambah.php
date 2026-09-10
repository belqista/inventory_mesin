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
   HELPER
========================================================= */

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   ACTIVE SIDEBAR
========================================================= */

$active_menu = 'komponen';


/* =========================================================
   DATA FORM
========================================================= */

$data = [
    'serial_number'    => '',
    'lokasi'           => '',
    'id_area'          => '',
    'id_jenis_mesin'   => '',
    'id_mesin'         => '',
    'id_sub_mesin'     => '',
    'mesin'            => '',
    'sub_mesin'        => '',
    'nama_bagian'      => '',
    'jenis_komponen'   => '',
    'spesifikasi'      => '',
    'kategori'         => '',
    'brand'             => '',
    'tipe'              => '',
    'part_number'      => '',
    'daya'              => '',
    'io_address'       => '',
    'ip_address'       => '',
    'input_voltage'    => '',
    'frekuensi_input' => '',
    'arus_input'       => '',
    'output'            => '',
    'frekuensi_output'=> '',
    'ip_rating'        => '',
    'kondisi'          => 'Baik',
    'keterangan'       => ''
];

$error = '';


/* =========================================================
   DATA MASTER
========================================================= */

$areas = [];
$jenisMesin = [];
$mesinList = [];
$subMesinList = [];
$lokasiList = [];


/* =========================================================
   AREA
========================================================= */

$qArea = mysqli_query(
    $conn,
    "SELECT
        id,
        nama_area,
        lokasi
     FROM area_bagian
     ORDER BY lokasi ASC, nama_area ASC"
);

if ($qArea) {

    while ($row = mysqli_fetch_assoc($qArea)) {

        $areas[] = $row;

        if (
            $row['lokasi'] !== null &&
            trim($row['lokasi']) !== ''
        ) {
            $lokasiList[] = trim($row['lokasi']);
        }
    }
}


/* =========================================================
   HAPUS LOKASI DUPLIKAT
========================================================= */

$lokasiList = array_values(
    array_unique($lokasiList)
);

sort($lokasiList);


/* =========================================================
   JENIS MESIN
========================================================= */

$qJenis = mysqli_query(
    $conn,
    "SELECT
        id,
        id_area,
        nama_jenis_mesin
     FROM jenis_mesin
     ORDER BY nama_jenis_mesin ASC"
);

if ($qJenis) {

    while ($row = mysqli_fetch_assoc($qJenis)) {
        $jenisMesin[] = $row;
    }
}


/* =========================================================
   MESIN
========================================================= */

$qMesin = mysqli_query(
    $conn,
    "SELECT
        id,
        id_area,
        id_jenis_mesin,
        nama_mesin
     FROM mesin
     ORDER BY nama_mesin ASC"
);

if ($qMesin) {

    while ($row = mysqli_fetch_assoc($qMesin)) {
        $mesinList[] = $row;
    }
}


/* =========================================================
   SUB MESIN
========================================================= */

$qSubMesin = mysqli_query(
    $conn,
    "SELECT
        id,
        id_mesin,
        nama_sub_mesin
     FROM sub_mesin
     ORDER BY nama_sub_mesin ASC"
);

if ($qSubMesin) {

    while ($row = mysqli_fetch_assoc($qSubMesin)) {
        $subMesinList[] = $row;
    }
}


/* =========================================================
   PROSES SIMPAN
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($data as $key => $value) {

        if (isset($_POST[$key])) {
            $data[$key] = trim($_POST[$key]);
        }
    }


    /* =====================================================
       VALIDASI LOKASI
    ===================================================== */

    if ($data['lokasi'] === '') {

        $error = 'Lokasi wajib dipilih.';
    }


    /* =====================================================
       VALIDASI AREA
    ===================================================== */

    if (
        $error === '' &&
        $data['id_area'] === ''
    ) {

        $error = 'Area wajib dipilih.';
    }


    /* =====================================================
       VALIDASI JENIS MESIN
    ===================================================== */

    if (
        $error === '' &&
        $data['id_jenis_mesin'] === ''
    ) {

        $error = 'Jenis mesin wajib dipilih.';
    }


    /* =====================================================
       VALIDASI MESIN
    ===================================================== */

    if (
        $error === '' &&
        $data['id_mesin'] === ''
    ) {

        $error = 'Mesin wajib dipilih.';
    }


    /* =====================================================
       VALIDASI SUB MESIN
    ===================================================== */

    if (
        $error === '' &&
        $data['id_sub_mesin'] === ''
    ) {

        $error = 'Sub mesin wajib dipilih.';
    }


    /* =====================================================
       VALIDASI NAMA BAGIAN
    ===================================================== */

    if (
        $error === '' &&
        $data['nama_bagian'] === ''
    ) {

        $error = 'Nama bagian wajib diisi.';
    }


    /* =====================================================
       VALIDASI HIRARKI
    ===================================================== */

    $selectedSub = null;

    if ($error === '') {

        $stmtSub = mysqli_prepare(
            $conn,
            "SELECT
                sm.id,
                sm.id_mesin,
                sm.nama_sub_mesin,

                m.id_area,
                m.id_jenis_mesin,
                m.nama_mesin,

                jm.nama_jenis_mesin,

                ab.nama_area,
                ab.lokasi

             FROM sub_mesin sm

             INNER JOIN mesin m
                ON m.id = sm.id_mesin

             LEFT JOIN jenis_mesin jm
                ON jm.id = m.id_jenis_mesin

             LEFT JOIN area_bagian ab
                ON ab.id = m.id_area

             WHERE sm.id = ?

             LIMIT 1"
        );

        if (!$stmtSub) {

            $error =
                'Query validasi sub mesin gagal: ' .
                mysqli_error($conn);

        } else {

            $idSubValidasi = (int)$data['id_sub_mesin'];

            mysqli_stmt_bind_param(
                $stmtSub,
                "i",
                $idSubValidasi
            );

            mysqli_stmt_execute($stmtSub);

            $resultSub =
                mysqli_stmt_get_result($stmtSub);

            $selectedSub =
                mysqli_fetch_assoc($resultSub);

            mysqli_stmt_close($stmtSub);


            if (!$selectedSub) {

                $error =
                    'Sub mesin yang dipilih tidak ditemukan.';
            }
        }
    }


    /* =====================================================
       PASTIKAN HIRARKI SESUAI PILIHAN
    ===================================================== */

    if (
        $error === '' &&
        $selectedSub
    ) {

        if (
            (int)$selectedSub['id_mesin'] !==
            (int)$data['id_mesin']
        ) {

            $error =
                'Sub mesin tidak sesuai dengan mesin yang dipilih.';

        } elseif (
            (int)$selectedSub['id_area'] !==
            (int)$data['id_area']
        ) {

            $error =
                'Mesin tidak sesuai dengan area yang dipilih.';

        } elseif (
            (int)$selectedSub['id_jenis_mesin'] !==
            (int)$data['id_jenis_mesin']
        ) {

            $error =
                'Mesin tidak sesuai dengan jenis mesin yang dipilih.';

        } elseif (
            trim((string)$selectedSub['lokasi']) !==
            trim((string)$data['lokasi'])
        ) {

            $error =
                'Mesin tidak sesuai dengan lokasi yang dipilih.';
        }
    }


    /* =====================================================
       SINKRONISASI DATA MASTER
    ===================================================== */

    if (
        $error === '' &&
        $selectedSub
    ) {

        $data['id_sub_mesin'] =
            $selectedSub['id'];

        $data['id_mesin'] =
            $selectedSub['id_mesin'];

        $data['id_area'] =
            $selectedSub['id_area'];

        $data['id_jenis_mesin'] =
            $selectedSub['id_jenis_mesin'];

        $data['lokasi'] =
            $selectedSub['lokasi'];

        $data['mesin'] =
            $selectedSub['nama_mesin'];

        $data['sub_mesin'] =
            $selectedSub['nama_sub_mesin'];
    }


    /* =====================================================
       VALIDASI KONDISI
    ===================================================== */

    $allowedKondisi = [
        'Baik',
        'Perlu Pemeriksaan',
        'Dalam Perbaikan'
    ];

    if (
        $error === '' &&
        !in_array(
            $data['kondisi'],
            $allowedKondisi,
            true
        )
    ) {

        $error =
            'Kondisi komponen tidak valid.';
    }


    /* =====================================================
       UPLOAD GAMBAR
    ===================================================== */

    $namaGambar = null;

    if (
        $error === '' &&
        isset($_FILES['gambar'])
    ) {

        if (
            $_FILES['gambar']['error'] !==
            UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES['gambar']['error'] !==
                UPLOAD_ERR_OK
            ) {

                $error =
                    'Upload gambar gagal.';

            } elseif (
                $_FILES['gambar']['size'] >
                2 * 1024 * 1024
            ) {

                $error =
                    'Ukuran gambar maksimal 2 MB.';

            } else {

                $tmpFile =
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

                } else {

                    $imageInfo =
                        @getimagesize($tmpFile);

                    if ($imageInfo === false) {

                        $error =
                            'File yang diupload bukan gambar yang valid.';

                    } else {

                        $uploadDir =
                            "../../uploads/komponen/";

                        if (!is_dir($uploadDir)) {

                            if (
                                !mkdir(
                                    $uploadDir,
                                    0775,
                                    true
                                ) &&
                                !is_dir($uploadDir)
                            ) {

                                $error =
                                    'Folder upload gambar tidak dapat dibuat.';
                            }
                        }


                        if ($error === '') {

                            $namaGambar =
                                'komponen_' .
                                date('Ymd_His') .
                                '_' .
                                bin2hex(
                                    random_bytes(5)
                                ) .
                                '.' .
                                $extension;

                            $targetFile =
                                $uploadDir .
                                $namaGambar;

                            if (
                                !move_uploaded_file(
                                    $tmpFile,
                                    $targetFile
                                )
                            ) {

                                $error =
                                    'Gambar gagal disimpan.';

                                $namaGambar = null;
                            }
                        }
                    }
                }
            }
        }
    }


    /* =====================================================
       INSERT DATABASE
    ===================================================== */

    if ($error === '') {

        $sql = "INSERT INTO komponen (
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
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?
                )";


        $stmt =
            mysqli_prepare(
                $conn,
                $sql
            );


        if (!$stmt) {

            if (
                $namaGambar &&
                file_exists(
                    "../../uploads/komponen/" .
                    $namaGambar
                )
            ) {

                unlink(
                    "../../uploads/komponen/" .
                    $namaGambar
                );
            }

            $error =
                'Query database gagal disiapkan: ' .
                mysqli_error($conn);

        } else {

            $idArea =
                (int)$data['id_area'];

            $idJenis =
                (int)$data['id_jenis_mesin'];

            $idMesin =
                (int)$data['id_mesin'];

            $idSubMesin =
                (int)$data['id_sub_mesin'];


            mysqli_stmt_bind_param(
                $stmt,
                "siiiisssssssssssssssssssssss",
                $data['serial_number'],
                $idArea,
                $idJenis,
                $idMesin,
                $idSubMesin,
                $data['mesin'],
                $data['sub_mesin'],
                $data['nama_bagian'],
                $data['jenis_komponen'],
                $data['spesifikasi'],
                $data['kategori'],
                $data['brand'],
                $data['tipe'],
                $data['part_number'],
                $data['daya'],
                $data['io_address'],
                $data['ip_address'],
                $data['input_voltage'],
                $data['frekuensi_input'],
                $data['arus_input'],
                $data['output'],
                $data['frekuensi_output'],
                $data['ip_rating'],
                $data['lokasi'],
                $data['kondisi'],
                $data['keterangan'],
                $namaGambar
            );


            if (
                mysqli_stmt_execute($stmt)
            ) {

                mysqli_stmt_close($stmt);

                header(
                    "Location: index.php?success=" .
                    urlencode(
                        'Komponen berhasil ditambahkan.'
                    )
                );

                exit;

            } else {

                $error =
                    'Komponen gagal disimpan: ' .
                    mysqli_stmt_error($stmt);

                mysqli_stmt_close($stmt);


                if (
                    $namaGambar &&
                    file_exists(
                        "../../uploads/komponen/" .
                        $namaGambar
                    )
                ) {

                    unlink(
                        "../../uploads/komponen/" .
                        $namaGambar
                    );
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Tambah Komponen • Admin
    </title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP ICONS
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         POPPINS
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
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

        }


        * {
            box-sizing:border-box;
        }


        body {

            margin:0;

            background:var(--bg);

            color:var(--text);

            font-family:'Poppins',sans-serif;

            font-size:13px;
        }


        /* =====================================================
           MAIN
        ====================================================== */

        .main {

            margin-left:240px;

            width:calc(100% - 240px);

            min-height:100vh;
        }


        /* =====================================================
           TOPBAR
        ====================================================== */

        .topbar {

            height:72px;

            background:#fff;

            border-bottom:1px solid var(--border);

            display:flex;

            align-items:center;

            justify-content:space-between;

            padding:0 28px;

            position:sticky;

            top:0;

            z-index:900;
        }


        .topbar-title {

            font-size:16px;

            font-weight:700;

            margin:0;
        }


        .topbar-subtitle {

            font-size:11px;

            color:var(--muted);

            margin-top:2px;
        }


        .user-box {

            display:flex;

            align-items:center;

            gap:10px;
        }


        .user-avatar {

            width:36px;

            height:36px;

            border-radius:50%;

            background:var(--primary-light);

            color:var(--primary);

            display:flex;

            align-items:center;

            justify-content:center;

            font-size:17px;
        }


        .user-name {

            font-size:12px;

            font-weight:600;
        }


        .user-role {

            color:var(--muted);

            font-size:10px;
        }


        /*
         * ID INI SENGAJA MENGIKUTI admin/sidebar.php
         */

        .sidebar-toggle {

            display:none;

            width:38px;

            height:38px;

            border:0;

            background:transparent;

            color:var(--primary);

            font-size:24px;

            align-items:center;

            justify-content:center;

            border-radius:8px;
        }


        .sidebar-toggle:hover {

            background:var(--primary-light);
        }


        /* =====================================================
           CONTENT
        ====================================================== */

        .content {

            padding:25px 28px 35px;
        }


        /* =====================================================
           PAGE HEADER
        ====================================================== */

        .page-header {

            display:flex;

            align-items:center;

            justify-content:space-between;

            gap:15px;

            margin-bottom:20px;
        }


        .page-title {

            font-size:20px;

            font-weight:700;

            margin:0;
        }


        .page-description {

            color:var(--muted);

            font-size:11px;

            margin-top:4px;
        }


        .btn-back {

            border:1px solid var(--border);

            background:#fff;

            color:#5f6878;

            border-radius:8px;

            padding:9px 14px;

            font-size:12px;

            font-weight:500;

            text-decoration:none;
        }


        .btn-back:hover {

            border-color:var(--primary);

            color:var(--primary);

            background:var(--primary-light);
        }


        /* =====================================================
           FORM CARD
        ====================================================== */

        .form-card {

            background:#fff;

            border:1px solid var(--border);

            border-radius:14px;

            overflow:hidden;

            box-shadow:
                0 2px 8px rgba(20,35,60,.03);
        }


        .section-header {

            padding:15px 20px;

            border-bottom:1px solid var(--border);

            display:flex;

            align-items:center;

            gap:10px;
        }


        .section-icon {

            width:34px;

            height:34px;

            border-radius:9px;

            background:var(--primary-light);

            color:var(--primary);

            display:flex;

            align-items:center;

            justify-content:center;

            font-size:16px;
        }


        .section-title {

            font-size:13px;

            font-weight:700;

            margin:0;
        }


        .section-subtitle {

            font-size:10px;

            color:var(--muted);

            margin-top:2px;
        }


        .section-body {

            padding:20px;
        }


        /* =====================================================
           FORM
        ====================================================== */

        .form-label {

            font-size:11px;

            font-weight:600;

            color:#4e5869;

            margin-bottom:6px;
        }


        .required {

            color:#dc3545;
        }


        .form-control,
        .form-select {

            border:1px solid #dfe4eb;

            border-radius:8px;

            min-height:39px;

            font-size:12px;

            color:var(--text);
        }


        .form-control:focus,
        .form-select:focus {

            border-color:var(--primary);

            box-shadow:
                0 0 0 3px rgba(7,94,170,.08);
        }


        textarea.form-control {

            min-height:90px;

            resize:vertical;
        }


        .form-hint {

            color:#929aaa;

            font-size:9px;

            margin-top:5px;
        }


        /* =====================================================
           LOCATION FLOW
        ====================================================== */

        .location-flow {

            background:#f8fbff;

            border:1px solid #dcecff;

            border-radius:10px;

            padding:14px;

            margin-bottom:18px;
        }


        .location-flow-title {

            display:flex;

            align-items:center;

            gap:7px;

            font-size:11px;

            font-weight:700;

            color:var(--primary);

            margin-bottom:4px;
        }


        .location-flow-description {

            color:var(--muted);

            font-size:9px;

            margin-bottom:14px;
        }


        .step-number {

            width:20px;

            height:20px;

            border-radius:50%;

            background:var(--primary);

            color:#fff;

            display:inline-flex;

            align-items:center;

            justify-content:center;

            font-size:9px;

            font-weight:600;

            margin-right:5px;
        }


        /* =====================================================
           UPLOAD
        ====================================================== */

        .upload-box {

            border:1px dashed #cfd6e1;

            border-radius:10px;

            padding:18px;

            background:#fafbfd;

            text-align:center;
        }


        .upload-icon {

            width:48px;

            height:48px;

            border-radius:12px;

            background:var(--primary-light);

            color:var(--primary);

            display:flex;

            align-items:center;

            justify-content:center;

            margin:0 auto 10px;

            font-size:21px;
        }


        .upload-title {

            font-size:12px;

            font-weight:600;

            margin-bottom:3px;
        }


        .upload-text {

            color:var(--muted);

            font-size:10px;

            margin-bottom:12px;
        }


        .upload-box .form-control {

            text-align:left;

            background:#fff;
        }


        /* =====================================================
           FOOTER
        ====================================================== */

        .form-footer {

            border-top:1px solid var(--border);

            padding:16px 20px;

            background:#fafbfd;

            display:flex;

            justify-content:flex-end;

            gap:8px;
        }


        .btn-cancel {

            border:1px solid var(--border);

            background:#fff;

            color:#667085;

            border-radius:8px;

            padding:9px 16px;

            font-size:12px;

            text-decoration:none;
        }


        .btn-cancel:hover {

            background:#f4f5f7;

            color:#333;
        }


        .btn-save {

            border:0;

            background:var(--primary);

            color:#fff;

            border-radius:8px;

            padding:9px 18px;

            font-size:12px;

            font-weight:600;
        }


        .btn-save:hover {

            background:var(--primary-dark);
        }


        .alert {

            border-radius:10px;

            font-size:11px;

            border:0;
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width:1100px) {

            .main {

                margin-left:0;

                width:100%;
            }


            .sidebar-toggle {

                display:flex;
            }


            .topbar {

                padding:0 18px;
            }


            .content {

                padding:20px 18px 30px;
            }
        }


        @media (max-width:900px) {

            .page-header {

                align-items:flex-start;
            }
        }


        @media (max-width:600px) {

            .topbar-title {

                font-size:14px;
            }


            .topbar-subtitle {

                display:none;
            }


            .user-name,
            .user-role {

                display:none;
            }


            .page-header {

                flex-direction:column;
            }


            .btn-back {

                width:100%;

                text-align:center;
            }


            .section-body {

                padding:16px;
            }


            .form-footer {

                padding:14px 16px;

                flex-direction:column-reverse;
            }


            .btn-cancel,
            .btn-save {

                width:100%;

                text-align:center;
            }
        }

    </style>

</head>


<body>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">


    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <header class="topbar">

        <div class="d-flex align-items-center gap-3">


            <!--
                PENTING:
                ID HARUS mobileToggle
                karena mengikuti admin/sidebar.php
            -->

            <button
                type="button"
                class="sidebar-toggle"
                id="mobileToggle"
                aria-label="Buka menu"
            >
                <i class="bi bi-list"></i>
            </button>


            <div>

                <h1 class="topbar-title">
                    Tambah Komponen
                </h1>

                <div class="topbar-subtitle">
                    Inventory Mesin • Master Data Komponen
                </div>

            </div>

        </div>


        <div class="user-box">

            <div class="user-avatar">

                <i class="bi bi-person"></i>

            </div>


            <div>

                <div class="user-name">

                    <?= e(
                        $_SESSION['nama_lengkap']
                        ?? $_SESSION['username']
                        ?? 'Administrator'
                    ) ?>

                </div>


                <div class="user-role">
                    Administrator
                </div>

            </div>

        </div>

    </header>


    <!-- =====================================================
         CONTENT
    ====================================================== -->

    <div class="content">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <div class="page-header">

            <div>

                <h2 class="page-title">
                    Tambah Komponen
                </h2>

                <div class="page-description">
                    Tambahkan data komponen baru ke inventory mesin.
                </div>

            </div>


            <a
                href="index.php"
                class="btn-back"
            >
                <i class="bi bi-arrow-left me-1"></i>
                Kembali ke Daftar Komponen
            </a>

        </div>


        <!-- =================================================
             ERROR
        ================================================== -->

        <?php if ($error !== ''): ?>

            <div
                class="alert alert-danger d-flex align-items-center gap-2 mb-3"
                role="alert"
            >

                <i class="bi bi-exclamation-circle-fill"></i>

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
            autocomplete="off"
        >


            <!-- =================================================
                 POSISI KOMPONEN
            ================================================== -->

            <div class="form-card mb-4">


                <div class="section-header">

                    <div class="section-icon">
                        <i class="bi bi-geo-alt"></i>
                    </div>


                    <div>

                        <h3 class="section-title">
                            Posisi Komponen
                        </h3>

                        <div class="section-subtitle">
                            Tentukan lokasi komponen terlebih dahulu.
                        </div>

                    </div>

                </div>


                <div class="section-body">


                    <div class="location-flow">


                        <div class="location-flow-title">

                            <i class="bi bi-info-circle"></i>

                            Urutan Pemilihan Lokasi

                        </div>


                        <div class="location-flow-description">

                            Pilih secara berurutan:
                            Lokasi → Area → Jenis Mesin → Mesin → Sub Mesin.

                        </div>


                        <div class="row g-3">


                            <!-- =================================================
                                 LOKASI
                            ================================================== -->

                            <div class="col-md-12">

                                <label class="form-label">

                                    <span class="step-number">
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
                                        Pilih Lokasi
                                    </option>


                                    <?php foreach ($lokasiList as $lokasi): ?>

                                        <option
                                            value="<?= e($lokasi) ?>"
                                            <?= $data['lokasi'] === $lokasi ? 'selected' : '' ?>
                                        >
                                            <?= e($lokasi) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>


                                <div class="form-hint">

                                    Lokasi diambil otomatis dari data Area.

                                </div>

                            </div>


                            <!-- =================================================
                                 AREA
                            ================================================== -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <span class="step-number">
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
                                    disabled
                                >

                                    <option value="">
                                        Pilih Area
                                    </option>


                                    <?php foreach ($areas as $area): ?>

                                        <option
                                            value="<?= e($area['id']) ?>"
                                            data-lokasi="<?= e($area['lokasi']) ?>"
                                            <?= (string)$data['id_area'] === (string)$area['id'] ? 'selected' : '' ?>
                                        >
                                            <?= e($area['nama_area']) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- =================================================
                                 JENIS MESIN
                            ================================================== -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <span class="step-number">
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
                                    disabled
                                >

                                    <option value="">
                                        Pilih Jenis Mesin
                                    </option>


                                    <?php foreach ($jenisMesin as $jenis): ?>

                                        <option
                                            value="<?= e($jenis['id']) ?>"
                                            data-area="<?= e($jenis['id_area']) ?>"
                                            <?= (string)$data['id_jenis_mesin'] === (string)$jenis['id'] ? 'selected' : '' ?>
                                        >
                                            <?= e($jenis['nama_jenis_mesin']) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- =================================================
                                 MESIN
                            ================================================== -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <span class="step-number">
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
                                    disabled
                                >

                                    <option value="">
                                        Pilih Mesin
                                    </option>


                                    <?php foreach ($mesinList as $mesin): ?>

                                        <option
                                            value="<?= e($mesin['id']) ?>"
                                            data-area="<?= e($mesin['id_area']) ?>"
                                            data-jenis="<?= e($mesin['id_jenis_mesin']) ?>"
                                            <?= (string)$data['id_mesin'] === (string)$mesin['id'] ? 'selected' : '' ?>
                                        >
                                            <?= e($mesin['nama_mesin']) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <!-- =================================================
                                 SUB MESIN
                            ================================================== -->

                            <div class="col-md-6">

                                <label class="form-label">

                                    <span class="step-number">
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
                                    disabled
                                >

                                    <option value="">
                                        Pilih Sub Mesin
                                    </option>


                                    <?php foreach ($subMesinList as $sub): ?>

                                        <option
                                            value="<?= e($sub['id']) ?>"
                                            data-mesin="<?= e($sub['id_mesin']) ?>"
                                            <?= (string)$data['id_sub_mesin'] === (string)$sub['id'] ? 'selected' : '' ?>
                                        >
                                            <?= e($sub['nama_sub_mesin']) ?>
                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         LOKASI DISPLAY
                    ================================================== -->

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Lokasi Komponen
                            </label>


                            <input
                                type="text"
                                id="lokasi_display"
                                class="form-control"
                                value="<?= e($data['lokasi']) ?>"
                                placeholder="Akan terisi otomatis"
                                readonly
                            >


                            <div class="form-hint">

                                Nilai ini mengikuti lokasi yang dipilih.

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 IDENTITAS KOMPONEN
            ================================================== -->

            <div class="form-card mb-4">


                <div class="section-header">

                    <div class="section-icon">
                        <i class="bi bi-puzzle"></i>
                    </div>


                    <div>

                        <h3 class="section-title">
                            Identitas Komponen
                        </h3>

                        <div class="section-subtitle">
                            Masukkan informasi utama komponen.
                        </div>

                    </div>

                </div>


                <div class="section-body">


                    <div class="row g-3">


                        <div class="col-md-6">

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
                                value="<?= e($data['nama_bagian']) ?>"
                                placeholder="Contoh: Motor Conveyor"
                                required
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Jenis Komponen
                            </label>


                            <input
                                type="text"
                                name="jenis_komponen"
                                class="form-control"
                                value="<?= e($data['jenis_komponen']) ?>"
                                placeholder="Contoh: Motor, Inverter, PLC"
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
                                value="<?= e($data['serial_number']) ?>"
                                placeholder="Masukkan serial number"
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Part Number
                            </label>


                            <input
                                type="text"
                                name="part_number"
                                class="form-control"
                                value="<?= e($data['part_number']) ?>"
                                placeholder="Masukkan part number"
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Kategori
                            </label>


                            <input
                                type="text"
                                name="kategori"
                                class="form-control"
                                value="<?= e($data['kategori']) ?>"
                                placeholder="Contoh: Electrical"
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Brand
                            </label>


                            <input
                                type="text"
                                name="brand"
                                class="form-control"
                                value="<?= e($data['brand']) ?>"
                                placeholder="Contoh: Schneider"
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Tipe
                            </label>


                            <input
                                type="text"
                                name="tipe"
                                class="form-control"
                                value="<?= e($data['tipe']) ?>"
                                placeholder="Masukkan tipe komponen"
                            >

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Spesifikasi
                            </label>


                            <input
                                type="text"
                                name="spesifikasi"
                                class="form-control"
                                value="<?= e($data['spesifikasi']) ?>"
                                placeholder="Masukkan spesifikasi"
                            >

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 SPESIFIKASI TEKNIS
            ================================================== -->

            <div class="form-card mb-4">


                <div class="section-header">

                    <div class="section-icon">
                        <i class="bi bi-cpu"></i>
                    </div>


                    <div>

                        <h3 class="section-title">
                            Spesifikasi Teknis
                        </h3>

                        <div class="section-subtitle">
                            Isi sesuai data teknis komponen jika tersedia.
                        </div>

                    </div>

                </div>


                <div class="section-body">


                    <div class="row g-3">


                        <div class="col-md-4">

                            <label class="form-label">
                                Daya
                            </label>


                            <input
                                type="text"
                                name="daya"
                                class="form-control"
                                value="<?= e($data['daya']) ?>"
                                placeholder="Contoh: 2.2 kW"
                            >

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                IO Address
                            </label>


                            <input
                                type="text"
                                name="io_address"
                                class="form-control"
                                value="<?= e($data['io_address']) ?>"
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
                                value="<?= e($data['ip_address']) ?>"
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
                                value="<?= e($data['input_voltage']) ?>"
                                placeholder="Contoh: 380 V"
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
                                value="<?= e($data['frekuensi_input']) ?>"
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
                                value="<?= e($data['arus_input']) ?>"
                                placeholder="Contoh: 5 A"
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
                                value="<?= e($data['output']) ?>"
                                placeholder="Masukkan output"
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
                                value="<?= e($data['frekuensi_output']) ?>"
                                placeholder="Contoh: 0–50 Hz"
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
                                value="<?= e($data['ip_rating']) ?>"
                                placeholder="Contoh: IP65"
                            >

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 KONDISI & FOTO
            ================================================== -->

            <div class="form-card mb-4">


                <div class="section-header">

                    <div class="section-icon">
                        <i class="bi bi-camera"></i>
                    </div>


                    <div>

                        <h3 class="section-title">
                            Kondisi & Dokumentasi
                        </h3>

                        <div class="section-subtitle">
                            Tentukan kondisi komponen dan tambahkan foto.
                        </div>

                    </div>

                </div>


                <div class="section-body">


                    <div class="row g-4">


                        <div class="col-lg-5">

                            <label class="form-label">
                                Kondisi
                            </label>


                            <select
                                name="kondisi"
                                class="form-select"
                            >

                                <option
                                    value="Baik"
                                    <?= $data['kondisi'] === 'Baik'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Baik
                                </option>


                                <option
                                    value="Perlu Pemeriksaan"
                                    <?= $data['kondisi'] === 'Perlu Pemeriksaan'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Perlu Pemeriksaan
                                </option>


                                <option
                                    value="Dalam Perbaikan"
                                    <?= $data['kondisi'] === 'Dalam Perbaikan'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Dalam Perbaikan
                                </option>

                            </select>

                        </div>


                        <div class="col-lg-7">

                            <div class="upload-box">


                                <div class="upload-icon">

                                    <i class="bi bi-image"></i>

                                </div>


                                <div class="upload-title">
                                    Foto Komponen
                                </div>


                                <div class="upload-text">

                                    JPG, JPEG, PNG atau WEBP • Maksimal 2 MB

                                </div>


                                <input
                                    type="file"
                                    name="gambar"
                                    id="gambar"
                                    class="form-control"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                >

                            </div>

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                Keterangan
                            </label>


                            <textarea
                                name="keterangan"
                                class="form-control"
                                placeholder="Tambahkan keterangan komponen jika diperlukan..."
                            ><?= e($data['keterangan']) ?></textarea>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 FOOTER
            ================================================== -->

            <div class="form-card">


                <div class="form-footer">


                    <a
                        href="index.php"
                        class="btn-cancel"
                    >

                        <i class="bi bi-x-lg me-1"></i>

                        Batal

                    </a>


                    <button
                        type="submit"
                        class="btn-save"
                    >

                        <i class="bi bi-check-lg me-1"></i>

                        Simpan Komponen

                    </button>

                </div>

            </div>


        </form>

    </div>

</main>


<!-- =========================================================
     SIDEBAR SHARED
     
     HARUS MENGGUNAKAN:
     admin/sidebar.php

     BUKAN SIDEBAR BUATAN HALAMAN INI.
========================================================= -->

<?php include "../sidebar.php"; ?>


<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script>

/* =========================================================
   ELEMENT SELECT
========================================================= */

const lokasiSelect =
    document.getElementById('lokasi');

const areaSelect =
    document.getElementById('id_area');

const jenisSelect =
    document.getElementById('id_jenis_mesin');

const mesinSelect =
    document.getElementById('id_mesin');

const subMesinSelect =
    document.getElementById('id_sub_mesin');

const lokasiDisplay =
    document.getElementById('lokasi_display');


/* =========================================================
   SIMPAN OPTION ASLI
========================================================= */

const areaOptions =
    Array.from(
        areaSelect.querySelectorAll(
            'option[data-lokasi]'
        )
    );


const jenisOptions =
    Array.from(
        jenisSelect.querySelectorAll(
            'option[data-area]'
        )
    );


const mesinOptions =
    Array.from(
        mesinSelect.querySelectorAll(
            'option[data-area]'
        )
    );


const subMesinOptions =
    Array.from(
        subMesinSelect.querySelectorAll(
            'option[data-mesin]'
        )
    );


/* =========================================================
   RESET SELECT
========================================================= */

function resetSelect(select, text)
{
    select.innerHTML = '';

    const option =
        document.createElement('option');

    option.value = '';

    option.textContent = text;

    select.appendChild(option);
}


/* =========================================================
   FILTER AREA BERDASARKAN LOKASI
========================================================= */

function filterArea()
{
    const lokasi =
        lokasiSelect.value;

    resetSelect(
        areaSelect,
        'Pilih Area'
    );


    areaOptions.forEach(option => {

        if (
            lokasi !== '' &&
            option.dataset.lokasi === lokasi
        ) {

            areaSelect.appendChild(
                option.cloneNode(true)
            );
        }

    });


    if (lokasi !== '') {

        areaSelect.disabled = false;

    } else {

        areaSelect.disabled = true;
    }


    filterJenisMesin();
}


/* =========================================================
   FILTER JENIS MESIN
========================================================= */

function filterJenisMesin()
{
    const areaId =
        areaSelect.value;


    resetSelect(
        jenisSelect,
        'Pilih Jenis Mesin'
    );


    if (areaId === '') {

        jenisSelect.disabled = true;

        filterMesin();

        return;
    }


    jenisOptions.forEach(option => {

        if (
            option.dataset.area === areaId
        ) {

            jenisSelect.appendChild(
                option.cloneNode(true)
            );
        }

    });


    jenisSelect.disabled = false;


    filterMesin();
}


/* =========================================================
   FILTER MESIN
========================================================= */

function filterMesin()
{
    const areaId =
        areaSelect.value;

    const jenisId =
        jenisSelect.value;


    resetSelect(
        mesinSelect,
        'Pilih Mesin'
    );


    if (
        areaId === '' ||
        jenisId === ''
    ) {

        mesinSelect.disabled = true;

        filterSubMesin();

        return;
    }


    mesinOptions.forEach(option => {

        const sesuaiArea =
            option.dataset.area === areaId;

        const sesuaiJenis =
            option.dataset.jenis === jenisId;


        if (
            sesuaiArea &&
            sesuaiJenis
        ) {

            mesinSelect.appendChild(
                option.cloneNode(true)
            );
        }

    });


    mesinSelect.disabled = false;


    filterSubMesin();
}


/* =========================================================
   FILTER SUB MESIN
========================================================= */

function filterSubMesin()
{
    const mesinId =
        mesinSelect.value;


    resetSelect(
        subMesinSelect,
        'Pilih Sub Mesin'
    );


    if (mesinId === '') {

        subMesinSelect.disabled = true;

        return;
    }


    subMesinOptions.forEach(option => {

        if (
            option.dataset.mesin === mesinId
        ) {

            subMesinSelect.appendChild(
                option.cloneNode(true)
            );
        }

    });


    subMesinSelect.disabled = false;
}


/* =========================================================
   LOKASI CHANGE
========================================================= */

lokasiSelect.addEventListener(
    'change',
    function () {

        lokasiDisplay.value =
            this.value;


        areaSelect.value = '';
        jenisSelect.value = '';
        mesinSelect.value = '';
        subMesinSelect.value = '';


        filterArea();

    }
);


/* =========================================================
   AREA CHANGE
========================================================= */

areaSelect.addEventListener(
    'change',
    function () {

        jenisSelect.value = '';
        mesinSelect.value = '';
        subMesinSelect.value = '';


        filterJenisMesin();

    }
);


/* =========================================================
   JENIS MESIN CHANGE
========================================================= */

jenisSelect.addEventListener(
    'change',
    function () {

        mesinSelect.value = '';
        subMesinSelect.value = '';


        filterMesin();

    }
);


/* =========================================================
   MESIN CHANGE
========================================================= */

mesinSelect.addEventListener(
    'change',
    function () {

        subMesinSelect.value = '';


        filterSubMesin();

    }
);


/* =========================================================
   INITIAL LOAD
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const initialLokasi =
            <?= json_encode($data['lokasi']) ?>;

        const initialArea =
            <?= json_encode($data['id_area']) ?>;

        const initialJenis =
            <?= json_encode($data['id_jenis_mesin']) ?>;

        const initialMesin =
            <?= json_encode($data['id_mesin']) ?>;

        const initialSub =
            <?= json_encode($data['id_sub_mesin']) ?>;


        /* -----------------------------------------------
           Lokasi
        ------------------------------------------------ */

        if (initialLokasi !== '') {

            lokasiSelect.value =
                initialLokasi;

            lokasiDisplay.value =
                initialLokasi;

            filterArea();
        }


        /* -----------------------------------------------
           Area
        ------------------------------------------------ */

        if (initialArea !== '') {

            areaSelect.value =
                initialArea;

            filterJenisMesin();
        }


        /* -----------------------------------------------
           Jenis Mesin
        ------------------------------------------------ */

        if (initialJenis !== '') {

            jenisSelect.value =
                initialJenis;

            filterMesin();
        }


        /* -----------------------------------------------
           Mesin
        ------------------------------------------------ */

        if (initialMesin !== '') {

            mesinSelect.value =
                initialMesin;

            filterSubMesin();
        }


        /* -----------------------------------------------
           Sub Mesin
        ------------------------------------------------ */

        if (initialSub !== '') {

            subMesinSelect.value =
                initialSub;
        }

    }
);


/* =========================================================
   VALIDASI GAMBAR
========================================================= */

const gambarInput =
    document.getElementById('gambar');


if (gambarInput) {

    gambarInput.addEventListener(
        'change',
        function () {

            const file =
                this.files[0];


            if (!file) {
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

                alert(
                    'Format gambar harus JPG, JPEG, PNG, atau WEBP.'
                );

                this.value = '';

                return;
            }


            if (
                file.size >
                2 * 1024 * 1024
            ) {

                alert(
                    'Ukuran gambar maksimal 2 MB.'
                );

                this.value = '';

                return;
            }

        }
    );
}


/* =========================================================
   VALIDASI FORM
========================================================= */

const form =
    document.querySelector('form');


if (form) {

    form.addEventListener(
        'submit',
        function (event) {


            if (
                lokasiSelect.value === ''
            ) {

                event.preventDefault();

                alert(
                    'Silakan pilih lokasi terlebih dahulu.'
                );

                lokasiSelect.focus();

                return;
            }


            if (
                areaSelect.value === ''
            ) {

                event.preventDefault();

                alert(
                    'Silakan pilih area.'
                );

                areaSelect.focus();

                return;
            }


            if (
                jenisSelect.value === ''
            ) {

                event.preventDefault();

                alert(
                    'Silakan pilih jenis mesin.'
                );

                jenisSelect.focus();

                return;
            }


            if (
                mesinSelect.value === ''
            ) {

                event.preventDefault();

                alert(
                    'Silakan pilih mesin.'
                );

                mesinSelect.focus();

                return;
            }


            if (
                subMesinSelect.value === ''
            ) {

                event.preventDefault();

                alert(
                    'Silakan pilih sub mesin.'
                );

                subMesinSelect.focus();

                return;
            }

        }
    );
}

</script>


</body>

</html>