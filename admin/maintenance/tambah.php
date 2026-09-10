<?php
session_start();
require_once "../../koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

if (strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') {
    header("Location: ../../dashboard/index.php");
    exit;
}

date_default_timezone_set('Asia/Jakarta');
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function tampil($value, $default = '-')
{
    $value = trim((string)$value);
    return $value !== '' ? $value : $default;
}

function tanggalInput($value = '')
{
    if (!$value) {
        return date('Y-m-d\TH:i');
    }

    $timestamp = strtotime($value);

    if (!$timestamp) {
        return date('Y-m-d\TH:i');
    }

    return date('Y-m-d\TH:i', $timestamp);
}

function parseEnumValues($conn, $table, $column)
{
    $values = [];

    $table  = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

    $sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {

        $type = $row['Type'] ?? '';

        if (preg_match('/^enum\((.*)\)$/i', $type, $match)) {

            preg_match_all(
                "/'((?:[^'\\\\]|\\\\.)*)'/",
                $match[1],
                $matches
            );

            if (!empty($matches[1])) {
                foreach ($matches[1] as $value) {
                    $values[] = stripcslashes($value);
                }
            }
        }
    }

    return $values;
}

$conditionOptions = parseEnumValues(
    $conn,
    'komponen',
    'kondisi'
);

if (empty($conditionOptions)) {
    $conditionOptions = [
        'Baik',
        'Perlu Pemeriksaan',
        'Dalam Perbaikan'
    ];
}

function labelKondisi($value)
{
    $value = trim((string)$value);

    if ($value === '') {
        return '-';
    }

    return rtrim($value, '.');
}

$lokasiList = [];

$sqlLokasi = "
    SELECT DISTINCT lokasi
    FROM area_bagian
    WHERE lokasi IS NOT NULL
      AND TRIM(lokasi) <> ''
    ORDER BY lokasi ASC
";

$resultLokasi = $conn->query($sqlLokasi);

if ($resultLokasi) {
    while ($row = $resultLokasi->fetch_assoc()) {
        $lokasiList[] = $row['lokasi'];
    }
}

$areaList = [];

$sqlArea = "
    SELECT
        id,
        nama_area,
        lokasi,
        keterangan
    FROM area_bagian
    ORDER BY nama_area ASC
";

$resultArea = $conn->query($sqlArea);

if ($resultArea) {
    while ($row = $resultArea->fetch_assoc()) {
        $areaList[] = $row;
    }
}

$jenisList = [];

$sqlJenis = "
    SELECT
        jm.id,
        jm.id_area,
        jm.nama_jenis_mesin,
        ab.nama_area,
        ab.lokasi
    FROM jenis_mesin jm
    LEFT JOIN area_bagian ab
        ON ab.id = jm.id_area
    ORDER BY jm.nama_jenis_mesin ASC
";

$resultJenis = $conn->query($sqlJenis);

if ($resultJenis) {
    while ($row = $resultJenis->fetch_assoc()) {
        $jenisList[] = $row;
    }
}

$mesinList = [];

$sqlMesin = "
    SELECT
        m.id,
        m.id_jenis_mesin,
        m.id_area,
        m.nama_mesin,
        m.serial_number,
        m.lokasi,
        m.keterangan,
        jm.nama_jenis_mesin,
        ab.nama_area,
        ab.lokasi AS lokasi_area
    FROM mesin m
    LEFT JOIN jenis_mesin jm
        ON jm.id = m.id_jenis_mesin
    LEFT JOIN area_bagian ab
        ON ab.id = m.id_area
    ORDER BY m.nama_mesin ASC
";

$resultMesin = $conn->query($sqlMesin);

if ($resultMesin) {
    while ($row = $resultMesin->fetch_assoc()) {
        $mesinList[] = $row;
    }
}

$subMesinList = [];

$sqlSubMesin = "
    SELECT
        sm.id,
        sm.id_mesin,
        sm.nama_sub_mesin,
        sm.serial_number,
        sm.keterangan,
        m.nama_mesin,
        m.id_area,
        m.id_jenis_mesin,
        ab.nama_area,
        ab.lokasi,
        jm.nama_jenis_mesin
    FROM sub_mesin sm
    LEFT JOIN mesin m
        ON m.id = sm.id_mesin
    LEFT JOIN area_bagian ab
        ON ab.id = m.id_area
    LEFT JOIN jenis_mesin jm
        ON jm.id = m.id_jenis_mesin
    ORDER BY sm.nama_sub_mesin ASC
";

$resultSubMesin = $conn->query($sqlSubMesin);

if ($resultSubMesin) {
    while ($row = $resultSubMesin->fetch_assoc()) {
        $subMesinList[] = $row;
    }
}

$komponenList = [];

$sqlKomponen = "
    SELECT
        k.id,
        k.serial_number,
        COALESCE(NULLIF(k.id_area, 0), m.id_area, jm.id_area, sm_area.id) AS id_area,
        COALESCE(NULLIF(k.id_jenis_mesin, 0), m.id_jenis_mesin, jm.id) AS id_jenis_mesin,
        COALESCE(NULLIF(k.id_mesin, 0), sm.id_mesin, m.id) AS id_mesin,
        COALESCE(NULLIF(k.id_sub_mesin, 0), sm.id) AS id_sub_mesin,

        k.mesin,
        k.sub_mesin,
        k.nama_bagian,
        k.jenis_komponen,
        k.spesifikasi,
        k.kategori,
        k.brand,
        k.tipe,
        k.part_number,
        k.daya,
        k.io_address,
        k.ip_address,
        k.input_voltage,
        k.frekuensi_input,
        k.arus_input,
        k.output,
        k.frekuensi_output,
        k.ip_rating,
        k.lokasi,
        k.kondisi,
        k.keterangan,
        k.gambar,

        ab.nama_area,
        ab.lokasi AS area_lokasi,
        jm.nama_jenis_mesin,
        m.nama_mesin,
        sm.nama_sub_mesin

    FROM komponen k

    LEFT JOIN sub_mesin sm
        ON sm.id = NULLIF(k.id_sub_mesin, 0)

    LEFT JOIN mesin m
        ON m.id = COALESCE(NULLIF(k.id_mesin, 0), sm.id_mesin)

    LEFT JOIN jenis_mesin jm
        ON jm.id = COALESCE(NULLIF(k.id_jenis_mesin, 0), m.id_jenis_mesin)

    LEFT JOIN area_bagian ab
        ON ab.id = COALESCE(NULLIF(k.id_area, 0), m.id_area, jm.id_area)

    LEFT JOIN area_bagian sm_area
        ON sm_area.id = m.id_area

    ORDER BY
        k.id DESC
";

$resultKomponen = $conn->query($sqlKomponen);

if ($resultKomponen) {
    while ($row = $resultKomponen->fetch_assoc()) {
        $komponenList[] = $row;
    }
}

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

$form = [
    'tanggal'              => date('Y-m-d\TH:i'),
    'status'               => 'Pending',
    'teknisi'              => '',
    'jenis'                => '',
    'sparepart'            => '',
    'tindakan'             => '',
    'catatan'              => '',

    'id_area'              => '',
    'id_jenis_mesin'      => '',
    'id_mesin'             => '',
    'id_sub_mesin'         => '',
    'id_komponen'          => '',

    'perubahan'            => 'Tidak',

    'brand_baru'           => '',
    'tipe_baru'            => '',
    'part_number_baru'     => '',
    'daya_baru'            => '',
    'io_address_baru'      => '',
    'ip_address_baru'      => '',
    'input_voltage_baru'   => '',
    'frekuensi_input_baru' => '',
    'arus_input_baru'      => '',
    'output_baru'          => '',
    'frekuensi_output_baru'=> '',
    'ip_rating_baru'       => '',
    'kondisi_baru'         => '',
    'spesifikasi_baru'     => ''
];

$error = '';
$success = '';

$idKomponenUrl = (int)($_GET['id_komponen'] ?? 0);

if ($idKomponenUrl > 0) {

    $stmt = $conn->prepare("
        SELECT
            COALESCE(NULLIF(k.id_area, 0), m.id_area, jm.id_area, sm_area.id) AS id_area,
            COALESCE(NULLIF(k.id_jenis_mesin, 0), m.id_jenis_mesin, jm.id) AS id_jenis_mesin,
            COALESCE(NULLIF(k.id_mesin, 0), sm.id_mesin, m.id) AS id_mesin,
            COALESCE(NULLIF(k.id_sub_mesin, 0), sm.id) AS id_sub_mesin
        FROM komponen k
        LEFT JOIN sub_mesin sm
            ON sm.id = NULLIF(k.id_sub_mesin, 0)
        LEFT JOIN mesin m
            ON m.id = COALESCE(NULLIF(k.id_mesin, 0), sm.id_mesin)
        LEFT JOIN jenis_mesin jm
            ON jm.id = COALESCE(NULLIF(k.id_jenis_mesin, 0), m.id_jenis_mesin)
        LEFT JOIN area_bagian sm_area
            ON sm_area.id = m.id_area
        WHERE k.id = ?
        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param('i', $idKomponenUrl);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            $form['id_komponen']  = $idKomponenUrl;
            $form['id_area']      = $row['id_area'];
            $form['id_jenis_mesin'] = $row['id_jenis_mesin'];
            $form['id_mesin']     = $row['id_mesin'];
            $form['id_sub_mesin'] = $row['id_sub_mesin'];
        }

        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $form['tanggal'] = trim($_POST['tanggal'] ?? '');
    $form['status'] = trim($_POST['status'] ?? 'Pending');
    $teknisiId = (int)($_POST['teknisi_id'] ?? 0);
    $form['teknisi'] = '';
    $form['jenis'] = trim($_POST['jenis'] ?? '');
    $form['sparepart'] = trim($_POST['sparepart'] ?? '');
    $form['tindakan'] = trim($_POST['tindakan'] ?? '');
    $form['catatan'] = trim($_POST['catatan'] ?? '');

    $form['id_area'] = (int)($_POST['id_area'] ?? 0);
    $form['id_jenis_mesin'] = (int)($_POST['id_jenis_mesin'] ?? 0);
    $form['id_mesin'] = (int)($_POST['id_mesin'] ?? 0);
    $form['id_sub_mesin'] = (int)($_POST['id_sub_mesin'] ?? 0);
    $form['id_komponen'] = (int)($_POST['id_komponen'] ?? 0);

    $form['perubahan'] = trim($_POST['perubahan'] ?? 'Tidak');

    $form['brand_baru'] = trim($_POST['brand_baru'] ?? '');
    $form['tipe_baru'] = trim($_POST['tipe_baru'] ?? '');
    $form['part_number_baru'] = trim($_POST['part_number_baru'] ?? '');
    $form['daya_baru'] = trim($_POST['daya_baru'] ?? '');
    $form['io_address_baru'] = trim($_POST['io_address_baru'] ?? '');
    $form['ip_address_baru'] = trim($_POST['ip_address_baru'] ?? '');
    $form['input_voltage_baru'] = trim($_POST['input_voltage_baru'] ?? '');
    $form['frekuensi_input_baru'] = trim($_POST['frekuensi_input_baru'] ?? '');
    $form['arus_input_baru'] = trim($_POST['arus_input_baru'] ?? '');
    $form['output_baru'] = trim($_POST['output_baru'] ?? '');
    $form['frekuensi_output_baru'] = trim($_POST['frekuensi_output_baru'] ?? '');
    $form['ip_rating_baru'] = trim($_POST['ip_rating_baru'] ?? '');
    $form['kondisi_baru'] = trim($_POST['kondisi_baru'] ?? '');
    $form['spesifikasi_baru'] = trim($_POST['spesifikasi_baru'] ?? '');

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
                $form['teknisi'] = trim((string)$dataTeknisi['nama_lengkap']);
            }
        }
    }

    if ($form['tanggal'] === '') {
        $error = 'Tanggal maintenance wajib diisi.';
    } elseif ($form['tindakan'] === '') {
        $error = 'Tindakan maintenance wajib diisi.';
    } elseif ($form['id_area'] <= 0) {
        $error = 'Lokasi / area wajib dipilih.';
    } elseif ($form['id_jenis_mesin'] <= 0) {
        $error = 'Jenis mesin wajib dipilih.';
    } elseif ($form['id_mesin'] <= 0) {
        $error = 'Mesin wajib dipilih.';
    } elseif ($form['id_sub_mesin'] <= 0) {
        $error = 'Sub mesin wajib dipilih.';
    } elseif ($form['id_komponen'] <= 0) {
        $error = 'Komponen wajib dipilih.';
    }

    $statusAllowed = [
        'Pending',
        'Proses',
        'Selesai'
    ];

    if (
        $error === '' &&
        !in_array($form['status'], $statusAllowed, true)
    ) {
        $error = 'Status maintenance tidak valid.';
    }

    if (
        $error === '' &&
        !in_array($form['perubahan'], ['Ya', 'Tidak'], true)
    ) {
        $form['perubahan'] = 'Tidak';
    }

    $komponen = null;

    if ($error === '') {

        $stmt = $conn->prepare("
            SELECT
                k.*,
                COALESCE(NULLIF(k.id_area, 0), m.id_area, jm.id_area, sm_area.id) AS resolved_id_area,
                COALESCE(NULLIF(k.id_jenis_mesin, 0), m.id_jenis_mesin, jm.id) AS resolved_id_jenis_mesin,
                COALESCE(NULLIF(k.id_mesin, 0), sm.id_mesin, m.id) AS resolved_id_mesin,
                COALESCE(NULLIF(k.id_sub_mesin, 0), sm.id) AS resolved_id_sub_mesin,
                ab.nama_area,
                ab.lokasi AS area_lokasi,
                jm.nama_jenis_mesin,
                m.nama_mesin,
                sm.nama_sub_mesin
            FROM komponen k
            LEFT JOIN sub_mesin sm
                ON sm.id = NULLIF(k.id_sub_mesin, 0)
            LEFT JOIN mesin m
                ON m.id = COALESCE(NULLIF(k.id_mesin, 0), sm.id_mesin)
            LEFT JOIN jenis_mesin jm
                ON jm.id = COALESCE(NULLIF(k.id_jenis_mesin, 0), m.id_jenis_mesin)
            LEFT JOIN area_bagian ab
                ON ab.id = COALESCE(NULLIF(k.id_area, 0), m.id_area, jm.id_area)
            LEFT JOIN area_bagian sm_area
                ON sm_area.id = m.id_area
            WHERE k.id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            $error = 'Gagal menyiapkan data komponen: ' . $conn->error;
        } else {

            $stmt->bind_param(
                'i',
                $form['id_komponen']
            );

            $stmt->execute();

            $result = $stmt->get_result();

            $komponen = $result->fetch_assoc();

            $stmt->close();

            if (!$komponen) {
                $error = 'Komponen yang dipilih tidak ditemukan.';
            }
        }
    }

    /*
     * PENTING:
     * Hierarki Area -> Jenis Mesin -> Mesin -> Sub Mesin
     * ditentukan dari komponen yang dipilih.
     *
     * Select Jenis/Mesin/Sub Mesin pada halaman ini dibuat disabled
     * sebelum pilihan sebelumnya dipilih. Field HTML yang disabled
     * tidak ikut dikirim saat POST. Karena itu jangan menjadikan
     * nilai POST dari field tersebut sebagai sumber kebenaran.
     * Komponen adalah sumber kebenaran karena relasinya sudah tersimpan
     * di database.
     */
    if ($error === '' && $komponen) {

        $componentArea = (int)($komponen['resolved_id_area'] ?? $komponen['id_area'] ?? 0);
        $componentJenis = (int)($komponen['resolved_id_jenis_mesin'] ?? $komponen['id_jenis_mesin'] ?? 0);
        $componentMesin = (int)($komponen['resolved_id_mesin'] ?? $komponen['id_mesin'] ?? 0);
        $componentSubMesin = (int)($komponen['resolved_id_sub_mesin'] ?? $komponen['id_sub_mesin'] ?? 0);

        if ($componentArea <= 0) {
            $error = 'Komponen belum memiliki area yang valid.';
        } elseif ($componentJenis <= 0) {
            $error = 'Komponen belum memiliki jenis mesin yang valid.';
        } elseif ($componentMesin <= 0) {
            $error = 'Komponen belum memiliki mesin yang valid.';
        } elseif ($componentSubMesin <= 0) {
            $error = 'Komponen belum memiliki sub mesin yang valid.';
        } else {
            /*
             * Sinkronkan seluruh hierarchy berdasarkan komponen.
             * Ini menghilangkan error:
             * "Area tidak sesuai dengan komponen yang dipilih."
             * akibat select disabled tidak terkirim melalui POST.
             */
            $form['id_area'] = $componentArea;
            $form['id_jenis_mesin'] = $componentJenis;
            $form['id_mesin'] = $componentMesin;
            $form['id_sub_mesin'] = $componentSubMesin;
        }
    }

    if ($error === '' && $komponen) {

        $normalBrand = trim((string)($komponen['brand'] ?? ''));
        $normalTipe = trim((string)($komponen['tipe'] ?? ''));
        $normalPartNumber = trim((string)($komponen['part_number'] ?? ''));
        $normalDaya = trim((string)($komponen['daya'] ?? ''));
        $normalIoAddress = trim((string)($komponen['io_address'] ?? ''));
        $normalIpAddress = trim((string)($komponen['ip_address'] ?? ''));
        $normalInputVoltage = trim((string)($komponen['input_voltage'] ?? ''));
        $normalFrekuensiInput = trim((string)($komponen['frekuensi_input'] ?? ''));
        $normalArusInput = trim((string)($komponen['arus_input'] ?? ''));
        $normalOutput = trim((string)($komponen['output'] ?? ''));
        $normalFrekuensiOutput = trim((string)($komponen['frekuensi_output'] ?? ''));
        $normalIpRating = trim((string)($komponen['ip_rating'] ?? ''));
        $normalKondisi = trim((string)($komponen['kondisi'] ?? ''));
        $normalSpesifikasi = trim((string)($komponen['spesifikasi'] ?? ''));

        if ($form['perubahan'] !== 'Ya') {

            $form['brand_baru'] = $normalBrand;
            $form['tipe_baru'] = $normalTipe;
            $form['part_number_baru'] = $normalPartNumber;
            $form['daya_baru'] = $normalDaya;
            $form['io_address_baru'] = $normalIoAddress;
            $form['ip_address_baru'] = $normalIpAddress;
            $form['input_voltage_baru'] = $normalInputVoltage;
            $form['frekuensi_input_baru'] = $normalFrekuensiInput;
            $form['arus_input_baru'] = $normalArusInput;
            $form['output_baru'] = $normalOutput;
            $form['frekuensi_output_baru'] = $normalFrekuensiOutput;
            $form['ip_rating_baru'] = $normalIpRating;
            $form['kondisi_baru'] = $normalKondisi;
            $form['spesifikasi_baru'] = $normalSpesifikasi;
        }

        if (
            $form['perubahan'] === 'Ya' &&
            $form['kondisi_baru'] === ''
        ) {
            $error = 'Kondisi terbaru wajib dipilih jika ada perubahan.';
        }
    }

    if (
        $error === '' &&
        $form['kondisi_baru'] !== '' &&
        !in_array($form['kondisi_baru'], $conditionOptions, true)
    ) {
        $error = 'Kondisi terbaru tidak valid.';
    }

    $fotoMaintenance = null;

    if ($error === '') {

        if (
            isset($_FILES['foto']) &&
            $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE
        ) {

            if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {

                $error = 'Foto maintenance gagal diupload.';

            } elseif (
                (int)$_FILES['foto']['size'] >
                2 * 1024 * 1024
            ) {

                $error = 'Ukuran foto maksimal 2 MB.';

            } else {

                $tmpName = $_FILES['foto']['tmp_name'];

                $imageInfo = @getimagesize($tmpName);

                if ($imageInfo === false) {

                    $error = 'File foto tidak valid.';

                } else {

                    $allowedMime = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp'
                    ];

                    $mime = $imageInfo['mime'] ?? '';

                    if (!isset($allowedMime[$mime])) {

                        $error = 'Format foto harus JPG, PNG, atau WEBP.';

                    } else {

                        $uploadDir = "../../uploads/maintenance/";

                        if (!is_dir($uploadDir)) {
                            @mkdir(
                                $uploadDir,
                                0777,
                                true
                            );
                        }

                        $extension = $allowedMime[$mime];

                        $fileName =
                            'maintenance_' .
                            date('YmdHis') .
                            '_' .
                            bin2hex(random_bytes(5)) .
                            '.' .
                            $extension;

                        $destination =
                            $uploadDir .
                            $fileName;

                        if (
                            !move_uploaded_file(
                                $tmpName,
                                $destination
                            )
                        ) {
                            $error = 'Foto maintenance gagal disimpan.';
                        } else {
                            $fotoMaintenance = $fileName;
                        }
                    }
                }
            }
        }
    }

    if ($error === '' && $komponen) {

        try {

            $conn->begin_transaction();

            $serialNumber =
                trim((string)($komponen['serial_number'] ?? ''));

            $namaBagian =
                trim((string)($komponen['nama_bagian'] ?? ''));

            $kategori =
                trim((string)($komponen['kategori'] ?? ''));

            $namaMesin =
                trim((string)(
                    $komponen['nama_mesin']
                    ?? $komponen['mesin']
                    ?? ''
                ));

            $namaSubMesin =
                trim((string)(
                    $komponen['nama_sub_mesin']
                    ?? $komponen['sub_mesin']
                    ?? ''
                ));

            $lokasiPenempatan =
                trim((string)(
                    $komponen['lokasi']
                    ?? $komponen['area_lokasi']
                    ?? ''
                ));

            $normalBrand =
                trim((string)($komponen['brand'] ?? ''));

            $normalTipe =
                trim((string)($komponen['tipe'] ?? ''));

            $normalPartNumber =
                trim((string)($komponen['part_number'] ?? ''));

            $normalDaya =
                trim((string)($komponen['daya'] ?? ''));

            $normalIoAddress =
                trim((string)($komponen['io_address'] ?? ''));

            $normalIpAddress =
                trim((string)($komponen['ip_address'] ?? ''));

            $normalInputVoltage =
                trim((string)($komponen['input_voltage'] ?? ''));

            $normalFrekuensiInput =
                trim((string)($komponen['frekuensi_input'] ?? ''));

            $normalArusInput =
                trim((string)($komponen['arus_input'] ?? ''));

            $normalOutput =
                trim((string)($komponen['output'] ?? ''));

            $normalFrekuensiOutput =
                trim((string)($komponen['frekuensi_output'] ?? ''));

            $normalIpRating =
                trim((string)($komponen['ip_rating'] ?? ''));

            $normalKondisi =
                trim((string)($komponen['kondisi'] ?? ''));

            $normalSpesifikasi =
                trim((string)($komponen['spesifikasi'] ?? ''));

            $terbaruBrand = $form['brand_baru'];
            $terbaruTipe = $form['tipe_baru'];
            $terbaruPartNumber = $form['part_number_baru'];
            $terbaruDaya = $form['daya_baru'];
            $terbaruIoAddress = $form['io_address_baru'];
            $terbaruIpAddress = $form['ip_address_baru'];
            $terbaruInputVoltage = $form['input_voltage_baru'];
            $terbaruFrekuensiInput = $form['frekuensi_input_baru'];
            $terbaruArusInput = $form['arus_input_baru'];
            $terbaruOutput = $form['output_baru'];
            $terbaruFrekuensiOutput = $form['frekuensi_output_baru'];
            $terbaruIpRating = $form['ip_rating_baru'];
            $terbaruKondisi = $form['kondisi_baru'];
            $terbaruSpesifikasi = $form['spesifikasi_baru'];

            $brandBaruDb = null;
            $tipeBaruDb = null;
            $partNumberBaruDb = null;
            $dayaBaruDb = null;
            $ioAddressBaruDb = null;
            $inputVoltageBaruDb = null;
            $frekuensiInputBaruDb = null;
            $arusInputBaruDb = null;
            $outputBaruDb = null;
            $frekuensiOutputBaruDb = null;
            $ipRatingBaruDb = null;
            $kondisiBaruDb = null;

            if ($form['perubahan'] === 'Ya') {

                $brandBaruDb = $terbaruBrand;
                $tipeBaruDb = $terbaruTipe;
                $partNumberBaruDb = $terbaruPartNumber;
                $dayaBaruDb = $terbaruDaya;
                $ioAddressBaruDb = $terbaruIoAddress;
                $inputVoltageBaruDb = $terbaruInputVoltage;
                $frekuensiInputBaruDb = $terbaruFrekuensiInput;
                $arusInputBaruDb = $terbaruArusInput;
                $outputBaruDb = $terbaruOutput;
                $frekuensiOutputBaruDb = $terbaruFrekuensiOutput;
                $ipRatingBaruDb = $terbaruIpRating;
                $kondisiBaruDb = $terbaruKondisi;
            }

            $catatanFinal = $form['catatan'];

            if (
                $form['perubahan'] === 'Ya' &&
                $terbaruSpesifikasi !== $normalSpesifikasi
            ) {

                $tambahan =
                    "Spesifikasi setelah maintenance: " .
                    ($terbaruSpesifikasi !== ''
                        ? $terbaruSpesifikasi
                        : '-');

                if ($catatanFinal !== '') {
                    $catatanFinal .= "\n\n";
                }

                $catatanFinal .= $tambahan;
            }

            $sqlInsert = "
                INSERT INTO riwayat_maintenance (

                    id_komponen,
                    tanggal,
                    tindakan,
                    status,
                    teknisi,
                    jenis,
                    sparepart,

                    brand_baru,
                    tipe_baru,
                    part_number_baru,
                    daya_baru,
                    io_address_baru,
                    input_voltage_baru,
                    frekuensi_input_baru,
                    arus_input_baru,
                    output_baru,
                    frekuensi_output_baru,
                    ip_rating_baru,
                    kondisi_baru,

                    normal_brand,
                    normal_tipe,
                    normal_part_number,
                    normal_daya,
                    normal_io_address,
                    normal_ip_address,
                    normal_input_voltage,
                    normal_frekuensi_input,
                    normal_arus_input,
                    normal_output,
                    normal_frekuensi_output,
                    normal_ip_rating,
                    normal_kondisi,

                    terbaru_brand,
                    terbaru_tipe,
                    terbaru_part_number,
                    terbaru_daya,
                    terbaru_io_address,
                    terbaru_ip_address,
                    terbaru_input_voltage,
                    terbaru_frekuensi_input,
                    terbaru_arus_input,
                    terbaru_output,
                    terbaru_frekuensi_output,
                    terbaru_ip_rating,
                    terbaru_kondisi,

                    catatan,
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

                    gambar,
                    foto
                )

                VALUES (
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

            $stmt = $conn->prepare($sqlInsert);

            if (!$stmt) {
                throw new Exception(
                    'Gagal menyiapkan INSERT maintenance: ' .
                    $conn->error
                );
            }

            /*
             * 73 parameter.
             */

            $types =
                'issssss' .
                str_repeat('s', 12) .
                str_repeat('s', 13) .
                str_repeat('s', 13) .
                str_repeat('s', 7) .
                str_repeat('s', 11) .
                'ss';

            /*
             * Pastikan jumlah parameter sesuai.
             */
            $params = [
                $form['id_komponen'],
                date(
                    'Y-m-d H:i:s',
                    strtotime($form['tanggal'])
                ),
                $form['tindakan'],
                $form['status'],
                $form['teknisi'],
                $form['jenis'],
                $form['sparepart'],

                $brandBaruDb,
                $tipeBaruDb,
                $partNumberBaruDb,
                $dayaBaruDb,
                $ioAddressBaruDb,
                $inputVoltageBaruDb,
                $frekuensiInputBaruDb,
                $arusInputBaruDb,
                $outputBaruDb,
                $frekuensiOutputBaruDb,
                $ipRatingBaruDb,
                $kondisiBaruDb,

                $normalBrand,
                $normalTipe,
                $normalPartNumber,
                $normalDaya,
                $normalIoAddress,
                $normalIpAddress,
                $normalInputVoltage,
                $normalFrekuensiInput,
                $normalArusInput,
                $normalOutput,
                $normalFrekuensiOutput,
                $normalIpRating,
                $normalKondisi,

                $normalBrand === $terbaruBrand
                    ? $normalBrand
                    : $terbaruBrand,

                $normalTipe === $terbaruTipe
                    ? $normalTipe
                    : $terbaruTipe,

                $normalPartNumber === $terbaruPartNumber
                    ? $normalPartNumber
                    : $terbaruPartNumber,

                $normalDaya === $terbaruDaya
                    ? $normalDaya
                    : $terbaruDaya,

                $normalIoAddress === $terbaruIoAddress
                    ? $normalIoAddress
                    : $terbaruIoAddress,

                $normalIpAddress === $terbaruIpAddress
                    ? $normalIpAddress
                    : $terbaruIpAddress,

                $normalInputVoltage === $terbaruInputVoltage
                    ? $normalInputVoltage
                    : $terbaruInputVoltage,

                $normalFrekuensiInput === $terbaruFrekuensiInput
                    ? $normalFrekuensiInput
                    : $terbaruFrekuensiInput,

                $normalArusInput === $terbaruArusInput
                    ? $normalArusInput
                    : $terbaruArusInput,

                $normalOutput === $terbaruOutput
                    ? $normalOutput
                    : $terbaruOutput,

                $normalFrekuensiOutput === $terbaruFrekuensiOutput
                    ? $normalFrekuensiOutput
                    : $terbaruFrekuensiOutput,

                $normalIpRating === $terbaruIpRating
                    ? $normalIpRating
                    : $terbaruIpRating,

                $normalKondisi === $terbaruKondisi
                    ? $normalKondisi
                    : $terbaruKondisi,

                $catatanFinal,
                $serialNumber,
                $namaBagian,
                $kategori,
                $namaMesin,
                $namaSubMesin,
                $lokasiPenempatan,

                $normalBrand,
                $normalTipe,
                $normalPartNumber,
                $normalDaya,
                $normalIoAddress,
                $normalInputVoltage,
                $normalFrekuensiInput,
                $normalArusInput,
                $normalOutput,
                $normalFrekuensiOutput,
                $normalIpRating,

                $komponen['gambar'] ?? null,
                $fotoMaintenance
            ];

            /*
             * Koreksi types secara aman berdasarkan
             * jumlah parameter.
             */
            $types = '';

            foreach ($params as $index => $value) {

                if ($index === 0) {
                    $types .= 'i';
                } else {
                    $types .= 's';
                }
            }

            $stmt->bind_param(
                $types,
                ...$params
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Gagal menyimpan riwayat maintenance: ' .
                    $stmt->error
                );
            }

            $stmt->close();

            if ($form['perubahan'] === 'Ya') {

                $newBrand =
                    $terbaruBrand;

                $newTipe =
                    $terbaruTipe;

                $newPartNumber =
                    $terbaruPartNumber;

                $newDaya =
                    $terbaruDaya;

                $newIoAddress =
                    $terbaruIoAddress;

                $newIpAddress =
                    $terbaruIpAddress;

                $newInputVoltage =
                    $terbaruInputVoltage;

                $newFrekuensiInput =
                    $terbaruFrekuensiInput;

                $newArusInput =
                    $terbaruArusInput;

                $newOutput =
                    $terbaruOutput;

                $newFrekuensiOutput =
                    $terbaruFrekuensiOutput;

                $newIpRating =
                    $terbaruIpRating;

                $newKondisi =
                    $terbaruKondisi;

                $newSpesifikasi =
                    $terbaruSpesifikasi;

                $stmtUpdate = $conn->prepare("
                    UPDATE komponen
                    SET
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
                        kondisi = ?,
                        spesifikasi = ?
                    WHERE id = ?
                ");

                if (!$stmtUpdate) {
                    throw new Exception(
                        'Gagal menyiapkan update komponen: ' .
                        $conn->error
                    );
                }

                $stmtUpdate->bind_param(
                    'ssssssssssssssi',
                    $newBrand,
                    $newTipe,
                    $newPartNumber,
                    $newDaya,
                    $newIoAddress,
                    $newIpAddress,
                    $newInputVoltage,
                    $newFrekuensiInput,
                    $newArusInput,
                    $newOutput,
                    $newFrekuensiOutput,
                    $newIpRating,
                    $newKondisi,
                    $newSpesifikasi,
                    $form['id_komponen']
                );

                if (!$stmtUpdate->execute()) {
                    throw new Exception(
                        'Gagal memperbarui data komponen: ' .
                        $stmtUpdate->error
                    );
                }

                $stmtUpdate->close();
            }

            $conn->commit();

            header(
                "Location: detail.php?id=" .
                $form['id_komponen'] .
                "&success=tambah"
            );
            exit;

        } catch (Throwable $e) {

            $conn->rollback();

            if (
                $fotoMaintenance &&
                file_exists(
                    "../../uploads/maintenance/" .
                    $fotoMaintenance
                )
            ) {
                @unlink(
                    "../../uploads/maintenance/" .
                    $fotoMaintenance
                );
            }

            $error = $e->getMessage();
        }
    }
}

$komponenJson = json_encode(
    $komponenList,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
);

$areaJson = json_encode(
    $areaList,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
);

$jenisJson = json_encode(
    $jenisList,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
);

$mesinJson = json_encode(
    $mesinList,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
);

$subMesinJson = json_encode(
    $subMesinList,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
);

$conditionJson = json_encode(
    $conditionOptions,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT
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

    <title>Tambah Maintenance • Admin</title>

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
            --primary: #075eaa;
            --primary-dark: #064b89;
            --primary-light: #eaf4ff;
            --text: #172033;
            --muted: #7b8494;
            --bg: #f5f7fb;
            --white: #ffffff;
            --border: #e7ebf1;
            --success: #198754;
            --warning: #f59e0b;
            --danger: #dc3545;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
        }

        a {
            text-decoration: none;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            height: 100vh;
            background: var(--white);
            border-right: 1px solid var(--border);
            z-index: 1050;
            display: flex;
            flex-direction: column;
        }

        .brand {
            height: 76px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 20px;
            border-bottom: 1px solid var(--border);
        }

        .brand img {
            max-width: 145px;
            max-height: 43px;
            object-fit: contain;
        }

        .brand-text {
            font-size: 11px;
            color: var(--muted);
            line-height: 1.3;
        }

        .menu {
            padding: 18px 12px;
            overflow-y: auto;
            flex: 1;
        }

        .menu-title {
            font-size: 10px;
            font-weight: 700;
            color: #a1a8b4;
            text-transform: uppercase;
            letter-spacing: .08em;
            padding: 8px 12px;
            margin-top: 4px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 11px;
            color: #566071;
            padding: 10px 12px;
            border-radius: 9px;
            margin-bottom: 3px;
            font-size: 12px;
            font-weight: 500;
            transition: .2s;
        }

        .menu a i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .menu a:hover {
            background: var(--primary-light);
            color: var(--primary);
        }

        .menu a.active {
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 600;
        }

        .sidebar-bottom {
            padding: 12px;
            border-top: 1px solid var(--border);
        }

        .logout {
            color: #dc3545 !important;
        }

        .logout:hover {
            background: #fff1f2 !important;
            color: #dc3545 !important;
        }


        .main {
            margin-left: 240px;
            min-height: 100vh;
        }

        .topbar {
            height: 76px;
            background: var(--white);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .page-title {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .page-subtitle {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: 11px;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .admin-name {
            font-weight: 600;
            font-size: 12px;
        }

        .admin-role {
            color: var(--muted);
            font-size: 10px;
        }

        .content {
            padding: 24px 28px 40px;
        }


        .card-box {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            margin-bottom: 18px;
            box-shadow: 0 4px 16px rgba(23, 32, 51, .035);
        }

        .card-header-custom {
            padding: 17px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .section-title {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
        }

        .section-subtitle {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: 11px;
        }

        .card-body-custom {
            padding: 20px;
        }


        .form-label {
            font-size: 11px;
            font-weight: 600;
            color: #4c5565;
            margin-bottom: 6px;
        }

        .form-control,
        .form-select {
            min-height: 40px;
            border-color: var(--border);
            border-radius: 9px;
            font-size: 12px;
            color: var(--text);
            box-shadow: none !important;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
        }

        .form-control:disabled,
        .form-select:disabled {
            background: #f4f6f9;
            color: #9aa2af;
        }

        .required {
            color: #dc3545;
        }

        .help-text {
            color: var(--muted);
            font-size: 10px;
            margin-top: 5px;
        }


        .step-box {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 14px;
            background: #fafcff;
            border: 1px solid var(--border);
            border-radius: 11px;
            margin-bottom: 14px;
        }

        .step-number {
            width: 28px;
            height: 28px;
            min-width: 28px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        .step-content {
            flex: 1;
        }


        .component-preview {
            display: none;
            border: 1px solid #cfe2ff;
            background: #f7fbff;
            border-radius: 12px;
            padding: 16px;
            margin-top: 15px;
        }

        .component-preview.show {
            display: block;
        }

        .component-preview-inner {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .component-image {
            width: 76px;
            height: 76px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #fff;
            object-fit: cover;
        }

        .component-image-placeholder {
            width: 76px;
            height: 76px;
            border-radius: 10px;
            background: #eef3f8;
            color: #8a94a3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }

        .component-name {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .component-meta {
            color: var(--muted);
            font-size: 10px;
            line-height: 1.8;
        }

        .component-meta strong {
            color: var(--text);
        }


        .status-option {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .status-option label {
            cursor: pointer;
        }

        .status-option input {
            display: none;
        }

        .status-option span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 13px;
            background: #fff;
            font-size: 11px;
            color: #596273;
        }

        .status-option input:checked + span {
            background: var(--primary-light);
            border-color: #a9d2ff;
            color: var(--primary);
            font-weight: 600;
        }


        .change-box {
            border: 1px solid var(--border);
            background: #fafbfc;
            border-radius: 12px;
            padding: 15px;
        }

        .change-title {
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 3px;
        }

        .change-desc {
            color: var(--muted);
            font-size: 10px;
            margin-bottom: 12px;
        }

        .change-buttons {
            display: flex;
            gap: 8px;
        }

        .change-buttons label {
            cursor: pointer;
        }

        .change-buttons input {
            display: none;
        }

        .change-buttons span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            border: 1px solid var(--border);
            padding: 8px 15px;
            border-radius: 8px;
            background: white;
            font-size: 11px;
        }

        .change-buttons input:checked + span {
            background: var(--primary-light);
            border-color: #a9d2ff;
            color: var(--primary);
            font-weight: 600;
        }


        .comparison-wrapper {
            margin-top: 18px;
        }

        .comparison-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
        }

        .comparison-header {
            padding: 13px 15px;
            font-size: 12px;
            font-weight: 700;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .normal-header {
            background: #f5f7fa;
            color: #596273;
        }

        .latest-header {
            background: var(--primary-light);
            color: var(--primary);
        }

        .comparison-body {
            padding: 15px;
        }

        .spec-row {
            margin-bottom: 11px;
        }

        .spec-label {
            display: block;
            color: var(--muted);
            font-size: 9px;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        .spec-value {
            font-size: 11px;
            font-weight: 600;
            word-break: break-word;
        }

        .normal-value {
            padding: 8px 9px;
            background: #f8f9fb;
            border: 1px solid #edf0f4;
            border-radius: 7px;
            min-height: 34px;
        }

        .latest-fields {
            display: none;
        }

        .latest-fields.show {
            display: block;
        }

        .normal-info {
            margin-top: 12px;
            padding: 9px 11px;
            border-radius: 8px;
            background: #f7f8fa;
            color: var(--muted);
            font-size: 10px;
        }


        .btn-primary-custom {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            border-radius: 8px;
            padding: 9px 15px;
            font-size: 11px;
            font-weight: 600;
        }

        .btn-primary-custom:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: white;
        }

        .btn-light-custom {
            background: white;
            border: 1px solid var(--border);
            color: #5e6878;
            border-radius: 8px;
            padding: 9px 15px;
            font-size: 11px;
            font-weight: 600;
        }

        .btn-light-custom:hover {
            background: #f5f7fa;
            color: var(--text);
        }

        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 9px;
            margin-top: 22px;
        }


        .alert-custom {
            border-radius: 10px;
            border: 1px solid;
            font-size: 11px;
        }

        .mobile-menu-btn {
            display: none;
            border: 0;
            background: transparent;
            font-size: 22px;
            color: var(--text);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.35);
            z-index: 1040;
        }

        @media (max-width: 991px) {

            .sidebar {
                transform: translateX(-100%);
                transition: transform .25s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .sidebar-overlay.show {
                display: block;
            }

            .main {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: inline-block;
            }

            .topbar {
                padding: 0 18px;
            }

            .content {
                padding: 18px;
            }

            .admin-user {
                display: none;
            }
        }

        @media (max-width: 575px) {

            .topbar {
                height: 68px;
            }

            .page-title {
                font-size: 17px;
            }

            .content {
                padding: 14px;
            }

            .card-body-custom {
                padding: 15px;
            }

            .card-header-custom {
                padding: 14px 15px;
            }

            .component-preview-inner {
                align-items: flex-start;
            }

            .form-footer {
                flex-direction: column-reverse;
            }

            .form-footer .btn {
                width: 100%;
            }

            .change-buttons {
                flex-direction: column;
            }

            .change-buttons label,
            .change-buttons span {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
    onclick="toggleSidebar()"
></div>

<aside class="sidebar" id="sidebar">

    <div class="brand">

        <img
            src="../../assets/img/logo-garudafood.png"
            alt="Garudafood"
            onerror="this.style.display='none'"
        >

        <div class="brand-text">
            ADMIN
        </div>

    </div>

    <nav class="menu">

        <div class="menu-title">
            Menu Utama
        </div>

        <a href="../index.php">
            <i class="bi bi-grid-1x2"></i>
            <span>Dashboard Admin</span>
        </a>

        <div class="menu-title">
            Master Data
        </div>

        <a href="../area/index.php">
            <i class="bi bi-geo-alt"></i>
            <span>Data Area</span>
        </a>

        <a href="../jenis_mesin/index.php">
            <i class="bi bi-diagram-3"></i>
            <span>Jenis Mesin</span>
        </a>

        <a href="../mesin/index.php">
            <i class="bi bi-cpu"></i>
            <span>Daftar Mesin</span>
        </a>

        <a href="../sub_mesin/index.php">
            <i class="bi bi-boxes"></i>
            <span>Sub Mesin</span>
        </a>

        <a href="../komponen/index.php">
            <i class="bi bi-nut"></i>
            <span>Data Komponen</span>
        </a>

        <div class="menu-title">
            Maintenance
        </div>

        <a
            href="index.php"
            class="active"
        >
            <i class="bi bi-tools"></i>
            <span>Riwayat Maintenance</span>
        </a>

        <div class="menu-title">
            Sistem
        </div>

        <a href="../users/index.php">
            <i class="bi bi-people"></i>
            <span>Manajemen User</span>
        </a>

    </nav>

    <div class="sidebar-bottom">

        <a
            href="../../logout.php"
            class="menu a logout"
        >
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>

    </div>

</aside>

<main class="main">

    <header class="topbar">

        <div class="d-flex align-items-center gap-2">

            <button
                type="button"
                class="mobile-menu-btn"
                onclick="toggleSidebar()"
            >
                <i class="bi bi-list"></i>
            </button>

            <div>

                <h1 class="page-title">
                    Tambah Maintenance
                </h1>

                <p class="page-subtitle">
                    Catat aktivitas maintenance dan perubahan komponen
                </p>

            </div>

        </div>

        <div class="admin-user">

            <div class="avatar">
                <i class="bi bi-person"></i>
            </div>

            <div>

                <div class="admin-name">
                    <?= e($_SESSION['nama_lengkap'] ?? 'Administrator') ?>
                </div>

                <div class="admin-role">
                    Administrator
                </div>

            </div>

        </div>

    </header>

    <section class="content">

        <?php if ($error !== ''): ?>

            <div
                class="alert alert-danger alert-custom d-flex align-items-start gap-2"
            >
                <i class="bi bi-exclamation-circle-fill"></i>

                <div>
                    <?= e($error) ?>
                </div>

            </div>

        <?php endif; ?>

        <form
            method="POST"
            enctype="multipart/form-data"
            id="maintenanceForm"
        >

            <div class="card-box">

                <div class="card-header-custom">

                    <div>

                        <h2 class="section-title">
                            <i class="bi bi-geo-alt me-1"></i>
                            Pilih Komponen
                        </h2>

                        <p class="section-subtitle">
                            Tentukan lokasi komponen dari struktur mesin.
                        </p>

                    </div>

                </div>

                <div class="card-body-custom">

                    <!-- LOKASI -->
                    <div class="step-box">

                        <div class="step-number">
                            1
                        </div>

                        <div class="step-content">

                            <label class="form-label">
                                Lokasi
                                <span class="required">*</span>
                            </label>

                            <select
                                class="form-select"
                                id="lokasi"
                            >
                                <option value="">
                                    Pilih lokasi
                                </option>

                                <?php foreach ($lokasiList as $lokasi): ?>

                                    <option
                                        value="<?= e($lokasi) ?>"
                                        <?= (
                                            isset($_POST['lokasi']) &&
                                            $_POST['lokasi'] === $lokasi
                                        ) ? 'selected' : '' ?>
                                    >
                                        <?= e($lokasi) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <div class="help-text">
                                Pilih lokasi terlebih dahulu.
                            </div>

                        </div>

                    </div>

                    <!-- AREA -->
                    <div class="step-box">

                        <div class="step-number">
                            2
                        </div>

                        <div class="step-content">

                            <label class="form-label">
                                Area
                                <span class="required">*</span>
                            </label>

                            <select
                                class="form-select"
                                name="id_area"
                                id="id_area"
                                required
                            >

                                <option value="">
                                    Pilih area
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- JENIS MESIN -->
                    <div class="step-box">

                        <div class="step-number">
                            3
                        </div>

                        <div class="step-content">

                            <label class="form-label">
                                Jenis Mesin
                                <span class="required">*</span>
                            </label>

                            <select
                                class="form-select"
                                name="id_jenis_mesin"
                                id="id_jenis_mesin"
                                required
                                disabled
                            >

                                <option value="">
                                    Pilih jenis mesin
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- MESIN -->
                    <div class="step-box">

                        <div class="step-number">
                            4
                        </div>

                        <div class="step-content">

                            <label class="form-label">
                                Mesin
                                <span class="required">*</span>
                            </label>

                            <select
                                class="form-select"
                                name="id_mesin"
                                id="id_mesin"
                                required
                                disabled
                            >

                                <option value="">
                                    Pilih mesin
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- SUB MESIN -->
                    <div class="step-box">

                        <div class="step-number">
                            5
                        </div>

                        <div class="step-content">

                            <label class="form-label">
                                Sub Mesin
                                <span class="required">*</span>
                            </label>

                            <select
                                class="form-select"
                                name="id_sub_mesin"
                                id="id_sub_mesin"
                                required
                                disabled
                            >

                                <option value="">
                                    Pilih sub mesin
                                </option>

                            </select>

                        </div>

                    </div>

                    <!-- KOMPONEN -->
                    <div class="step-box">

                        <div class="step-number">
                            6
                        </div>

                        <div class="step-content">

                            <label class="form-label">
                                Komponen
                                <span class="required">*</span>
                            </label>

                            <select
                                class="form-select"
                                id="komponen_picker"
                                required
                                disabled
                            >

                                <option value="">
                                    Pilih komponen
                                </option>

                            </select>

                            <input
                                type="hidden"
                                name="id_komponen"
                                id="id_komponen"
                                value="<?= e($form['id_komponen']) ?>"
                            >

                            <div class="help-text">
                                Setelah sub mesin dipilih,
                                komponen yang berada di dalamnya akan muncul.
                            </div>

                        </div>

                    </div>

                    <!-- COMPONENT PREVIEW -->
                    <div
                        class="component-preview"
                        id="componentPreview"
                    >

                        <div class="component-preview-inner">

                            <div id="componentImageContainer">
                                <div class="component-image-placeholder">
                                    <i class="bi bi-nut"></i>
                                </div>
                            </div>

                            <div>

                                <div
                                    class="component-name"
                                    id="previewName"
                                >
                                    -
                                </div>

                                <div
                                    class="component-meta"
                                    id="previewMeta"
                                >
                                    -
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <div class="card-box">

                <div class="card-header-custom">

                    <div>

                        <h2 class="section-title">
                            <i class="bi bi-tools me-1"></i>
                            Detail Maintenance
                        </h2>

                        <p class="section-subtitle">
                            Isi informasi pelaksanaan maintenance.
                        </p>

                    </div>

                </div>

                <div class="card-body-custom">

                    <div class="row g-3">

                        <!-- TANGGAL -->
                        <div class="col-md-4">

                            <label class="form-label">
                                Tanggal Maintenance
                                <span class="required">*</span>
                            </label>

                            <input
                                type="datetime-local"
                                name="tanggal"
                                class="form-control"
                                value="<?= e(
                                    tanggalInput($form['tanggal'])
                                ) ?>"
                                required
                            >

                        </div>

                        <!-- TEKNISI -->
                        <div class="col-md-4">

                            <label class="form-label">
                                Teknisi <span class="required">*</span>
                            </label>

                            <select
                                name="teknisi_id"
                                class="form-select"
                                required
                            >
                                <option value="">-- Pilih Teknisi --</option>
                                <?php foreach ($teknisiList as $teknisi): ?>
                                    <option
                                        value="<?= (int)$teknisi['id'] ?>"
                                        <?= ((int)($teknisiId ?? 0) === (int)$teknisi['id']) ? 'selected' : '' ?>
                                    >
                                        <?= e($teknisi['nama_lengkap']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <div class="help-text">
                                Pilih teknisi dari User aktif di Manajemen User.
                            </div>

                        </div>

                        <!-- JENIS -->
                        <div class="col-md-4">

                            <label class="form-label">
                                Jenis Maintenance
                            </label>

                            <select
                                name="jenis"
                                class="form-select"
                            >

                                <option value="">
                                    Pilih jenis
                                </option>

                                <?php
                                $jenisMaintenance = [
                                    'Preventive',
                                    'Corrective',
                                    'Inspection',
                                    'Replacement',
                                    'Lainnya'
                                ];
                                ?>

                                <?php foreach ($jenisMaintenance as $jenis): ?>

                                    <option
                                        value="<?= e($jenis) ?>"
                                        <?= (
                                            $form['jenis'] === $jenis
                                        ) ? 'selected' : '' ?>
                                    >
                                        <?= e($jenis) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <!-- STATUS -->
                        <div class="col-12">

                            <label class="form-label">
                                Status
                                <span class="required">*</span>
                            </label>

                            <div class="status-option">

                                <?php foreach (
                                    ['Pending', 'Proses', 'Selesai']
                                    as $status
                                ): ?>

                                    <label>

                                        <input
                                            type="radio"
                                            name="status"
                                            value="<?= e($status) ?>"
                                            <?= (
                                                $form['status'] === $status
                                            ) ? 'checked' : '' ?>
                                        >

                                        <span>

                                            <?php if ($status === 'Pending'): ?>
                                                <i class="bi bi-clock"></i>
                                            <?php elseif ($status === 'Proses'): ?>
                                                <i class="bi bi-arrow-repeat"></i>
                                            <?php else: ?>
                                                <i class="bi bi-check-circle"></i>
                                            <?php endif; ?>

                                            <?= e($status) ?>

                                        </span>

                                    </label>

                                <?php endforeach; ?>

                            </div>

                        </div>

                        <!-- SPAREPART -->
                        <div class="col-md-6">

                            <label class="form-label">
                                Sparepart
                            </label>

                            <textarea
                                name="sparepart"
                                class="form-control"
                                placeholder="Sparepart yang digunakan..."
                            ><?= e($form['sparepart']) ?></textarea>

                        </div>

                        <!-- TINDAKAN -->
                        <div class="col-md-6">

                            <label class="form-label">
                                Tindakan Maintenance
                                <span class="required">*</span>
                            </label>

                            <textarea
                                name="tindakan"
                                class="form-control"
                                placeholder="Jelaskan tindakan maintenance yang dilakukan..."
                                required
                            ><?= e($form['tindakan']) ?></textarea>

                        </div>

                        <!-- FOTO -->
                        <div class="col-md-6">

                            <label class="form-label">
                                Foto Maintenance
                            </label>

                            <input
                                type="file"
                                name="foto"
                                class="form-control"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            >

                            <div class="help-text">
                                Maksimal 2 MB. Format JPG, PNG, WEBP.
                            </div>

                        </div>

                        <!-- CATATAN -->
                        <div class="col-md-6">

                            <label class="form-label">
                                Catatan
                            </label>

                            <textarea
                                name="catatan"
                                class="form-control"
                                placeholder="Catatan tambahan..."
                            ><?= e($form['catatan']) ?></textarea>

                        </div>

                    </div>

                </div>

            </div>

            <div class="card-box">

                <div class="card-header-custom">

                    <div>

                        <h2 class="section-title">
                            <i class="bi bi-arrow-left-right me-1"></i>
                            Kondisi & Spesifikasi Komponen
                        </h2>

                        <p class="section-subtitle">
                            Data Normal otomatis diambil dari master komponen.
                        </p>

                    </div>

                </div>

                <div class="card-body-custom">

                    <div class="change-box">

                        <div class="change-title">
                            Apakah ada perubahan spesifikasi / komponen?
                        </div>

                        <div class="change-desc">
                            Jika ada penggantian atau perubahan,
                            pilih "Ya" lalu isi data terbaru.
                        </div>

                        <div class="change-buttons">

                            <label>

                                <input
                                    type="radio"
                                    name="perubahan"
                                    value="Tidak"
                                    <?= (
                                        $form['perubahan'] !== 'Ya'
                                    ) ? 'checked' : '' ?>
                                >

                                <span>
                                    <i class="bi bi-check-circle"></i>
                                    Tidak Ada Perubahan
                                </span>

                            </label>

                            <label>

                                <input
                                    type="radio"
                                    name="perubahan"
                                    value="Ya"
                                    <?= (
                                        $form['perubahan'] === 'Ya'
                                    ) ? 'checked' : '' ?>
                                >

                                <span>
                                    <i class="bi bi-pencil-square"></i>
                                    Ada Perubahan
                                </span>

                            </label>

                        </div>

                    </div>

                    <div class="comparison-wrapper">

                        <div class="row g-3">

                            <!-- NORMAL -->
                            <div class="col-lg-6">

                                <div class="comparison-card">

                                    <div class="comparison-header normal-header">

                                        <i class="bi bi-database"></i>

                                        Data Normal

                                        <span
                                            class="badge bg-secondary-subtle text-secondary ms-auto"
                                        >
                                            MASTER
                                        </span>

                                    </div>

                                    <div class="comparison-body">

                                        <!-- BRAND -->
                                        <div class="spec-row">

                                            <span class="spec-label">
                                                Brand
                                            </span>

                                            <div
                                                class="normal-value spec-value"
                                                id="normal_brand_view"
                                            >
                                                -
                                            </div>

                                        </div>

                                        <!-- TIPE -->
                                        <div class="spec-row">

                                            <span class="spec-label">
                                                Tipe
                                            </span>

                                            <div
                                                class="normal-value spec-value"
                                                id="normal_tipe_view"
                                            >
                                                -
                                            </div>

                                        </div>

                                        <!-- PART NUMBER -->
                                        <div class="spec-row">

                                            <span class="spec-label">
                                                Part Number
                                            </span>

                                            <div
                                                class="normal-value spec-value"
                                                id="normal_part_number_view"
                                            >
                                                -
                                            </div>

                                        </div>

                                        <!-- DAYA -->
                                        <div class="spec-row">

                                            <span class="spec-label">
                                                Daya
                                            </span>

                                            <div
                                                class="normal-value spec-value"
                                                id="normal_daya_view"
                                            >
                                                -
                                            </div>

                                        </div>

                                        <!-- IO -->
                                        <div class="spec-row">

                                            <span class="spec-label">
                                                IO Address
                                            </span>

                                            <div
                                                class="normal-value spec-value"
                                                id="normal_io_address_view"
                                            >
                                                -
                                            </div>

                                        </div>

                                        <!-- IP -->
                                        <div class="spec-row">

                                            <span class="spec-label">
                                                IP Address
                                            </span>

                                            <div
                                                class="normal-value spec-value"
                                                id="normal_ip_address_view"
                                            >
                                                -
                                            </div>

                                        </div>

                                        <!-- VOLTAGE -->
                                        <div class="spec-row">

                                            <span class="spec-label">
                                                Input Voltage
                                            </span>

                                            <div
                                                class="normal-value spec-value"
                                                id="normal_input_voltage_view"
                                            >
                                                -
                                            </div>

                                        </div>

                                        <!-- FREKUENSI INPUT -->
                                        <div class="spec-row">

                                            <span class="spec-label">
                                                Frekuensi Input
                                            </span>

                                            <div
                                                class="normal-value spec-value"
                                                id="normal_frekuensi_input_view"
                                            >
                                                -
                                            </div>

                                        </div>

                                        <!-- ARUS -->
                                        <div class="spec-row">

                                            <span class="spec-label">
                                                Arus Input
                                            </span>

                                            <div
                                                class="normal-value spec-value"
                                                id="normal_arus_input_view"
                                            >
                                                -
                                            </div>

                                        </div>

                                        <!-- OUTPUT -->
                                        <div class="spec-row">

                                            <span class="spec-label">
                                                Output
                                            </span>

                                            <div
                                                class="normal-value spec-value"
                                                id="normal_output_view"
                                            >
                                                -
                                            </div>

                                        </div>

                                        <!-- FREKUENSI OUTPUT -->
                                        <div class="spec-row">

                                            <span class="spec-label">
                                                Frekuensi Output
                                            </span>

                                            <div
                                                class="normal-value spec-value"
                                                id="normal_frekuensi_output_view"
                                            >
                                                -
                                            </div>

                                        </div>

                                        <!-- IP RATING -->
                                        <div class="spec-row">

                                            <span class="spec-label">
                                                IP Rating
                                            </span>

                                            <div
                                                class="normal-value spec-value"
                                                id="normal_ip_rating_view"
                                            >
                                                -
                                            </div>

                                        </div>

                                        <!-- KONDISI -->
                                        <div class="spec-row">

                                            <span class="spec-label">
                                                Kondisi
                                            </span>

                                            <div
                                                class="normal-value spec-value"
                                                id="normal_kondisi_view"
                                            >
                                                -
                                            </div>

                                        </div>

                                        <!-- SPESIFIKASI -->
                                        <div class="spec-row">

                                            <span class="spec-label">
                                                Spesifikasi
                                            </span>

                                            <div
                                                class="normal-value spec-value"
                                                id="normal_spesifikasi_view"
                                            >
                                                -
                                            </div>

                                        </div>

                                        <div class="normal-info">

                                            <i class="bi bi-info-circle me-1"></i>

                                            Data ini diambil langsung dari
                                            master komponen saat komponen dipilih.

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <!-- TERBARU -->
                            <div class="col-lg-6">

                                <div class="comparison-card">

                                    <div class="comparison-header latest-header">

                                        <i class="bi bi-pencil-square"></i>

                                        Data Terbaru

                                        <span
                                            class="badge bg-primary-subtle text-primary ms-auto"
                                            id="latestBadge"
                                        >
                                            OTOMATIS
                                        </span>

                                    </div>

                                    <div class="comparison-body">

                                        <div
                                            id="latestDisabledMessage"
                                            class="normal-info mb-3"
                                        >
                                            <i class="bi bi-arrow-right-circle me-1"></i>

                                            Data terbaru otomatis mengikuti
                                            Data Normal karena tidak ada perubahan.
                                        </div>

                                        <div
                                            id="latestFields"
                                            class="latest-fields"
                                        >

                                            <!-- BRAND -->
                                            <div class="spec-row">

                                                <label class="spec-label">
                                                    Brand
                                                </label>

                                                <input
                                                    type="text"
                                                    name="brand_baru"
                                                    id="brand_baru"
                                                    class="form-control"
                                                    maxlength="255"
                                                    value="<?= e($form['brand_baru']) ?>"
                                                >

                                            </div>

                                            <!-- TIPE -->
                                            <div class="spec-row">

                                                <label class="spec-label">
                                                    Tipe
                                                </label>

                                                <input
                                                    type="text"
                                                    name="tipe_baru"
                                                    id="tipe_baru"
                                                    class="form-control"
                                                    maxlength="255"
                                                    value="<?= e($form['tipe_baru']) ?>"
                                                >

                                            </div>

                                            <!-- PART NUMBER -->
                                            <div class="spec-row">

                                                <label class="spec-label">
                                                    Part Number
                                                </label>

                                                <input
                                                    type="text"
                                                    name="part_number_baru"
                                                    id="part_number_baru"
                                                    class="form-control"
                                                    maxlength="255"
                                                    value="<?= e($form['part_number_baru']) ?>"
                                                >

                                            </div>

                                            <!-- DAYA -->
                                            <div class="spec-row">

                                                <label class="spec-label">
                                                    Daya
                                                </label>

                                                <input
                                                    type="text"
                                                    name="daya_baru"
                                                    id="daya_baru"
                                                    class="form-control"
                                                    maxlength="255"
                                                    value="<?= e($form['daya_baru']) ?>"
                                                >

                                            </div>

                                            <!-- IO -->
                                            <div class="spec-row">

                                                <label class="spec-label">
                                                    IO Address
                                                </label>

                                                <input
                                                    type="text"
                                                    name="io_address_baru"
                                                    id="io_address_baru"
                                                    class="form-control"
                                                    maxlength="255"
                                                    value="<?= e($form['io_address_baru']) ?>"
                                                >

                                            </div>

                                            <!-- IP -->
                                            <div class="spec-row">

                                                <label class="spec-label">
                                                    IP Address
                                                </label>

                                                <input
                                                    type="text"
                                                    name="ip_address_baru"
                                                    id="ip_address_baru"
                                                    class="form-control"
                                                    maxlength="255"
                                                    value="<?= e($form['ip_address_baru']) ?>"
                                                >

                                            </div>

                                            <!-- VOLTAGE -->
                                            <div class="spec-row">

                                                <label class="spec-label">
                                                    Input Voltage
                                                </label>

                                                <input
                                                    type="text"
                                                    name="input_voltage_baru"
                                                    id="input_voltage_baru"
                                                    class="form-control"
                                                    maxlength="255"
                                                    value="<?= e($form['input_voltage_baru']) ?>"
                                                >

                                            </div>

                                            <!-- FREKUENSI INPUT -->
                                            <div class="spec-row">

                                                <label class="spec-label">
                                                    Frekuensi Input
                                                </label>

                                                <input
                                                    type="text"
                                                    name="frekuensi_input_baru"
                                                    id="frekuensi_input_baru"
                                                    class="form-control"
                                                    maxlength="255"
                                                    value="<?= e($form['frekuensi_input_baru']) ?>"
                                                >

                                            </div>

                                            <!-- ARUS -->
                                            <div class="spec-row">

                                                <label class="spec-label">
                                                    Arus Input
                                                </label>

                                                <input
                                                    type="text"
                                                    name="arus_input_baru"
                                                    id="arus_input_baru"
                                                    class="form-control"
                                                    maxlength="255"
                                                    value="<?= e($form['arus_input_baru']) ?>"
                                                >

                                            </div>

                                            <!-- OUTPUT -->
                                            <div class="spec-row">

                                                <label class="spec-label">
                                                    Output
                                                </label>

                                                <input
                                                    type="text"
                                                    name="output_baru"
                                                    id="output_baru"
                                                    class="form-control"
                                                    maxlength="255"
                                                    value="<?= e($form['output_baru']) ?>"
                                                >

                                            </div>

                                            <!-- FREKUENSI OUTPUT -->
                                            <div class="spec-row">

                                                <label class="spec-label">
                                                    Frekuensi Output
                                                </label>

                                                <input
                                                    type="text"
                                                    name="frekuensi_output_baru"
                                                    id="frekuensi_output_baru"
                                                    class="form-control"
                                                    maxlength="255"
                                                    value="<?= e($form['frekuensi_output_baru']) ?>"
                                                >

                                            </div>

                                            <!-- IP RATING -->
                                            <div class="spec-row">

                                                <label class="spec-label">
                                                    IP Rating
                                                </label>

                                                <input
                                                    type="text"
                                                    name="ip_rating_baru"
                                                    id="ip_rating_baru"
                                                    class="form-control"
                                                    maxlength="255"
                                                    value="<?= e($form['ip_rating_baru']) ?>"
                                                >

                                            </div>

                                            <!-- KONDISI -->
                                            <div class="spec-row">

                                                <label class="spec-label">
                                                    Kondisi
                                                    <span class="required">*</span>
                                                </label>

                                                <select
                                                    name="kondisi_baru"
                                                    id="kondisi_baru"
                                                    class="form-select"
                                                >

                                                    <option value="">
                                                        Pilih kondisi
                                                    </option>

                                                    <?php foreach (
                                                        $conditionOptions
                                                        as $condition
                                                    ): ?>

                                                        <option
                                                            value="<?= e($condition) ?>"
                                                            <?= (
                                                                $form['kondisi_baru'] === $condition
                                                            ) ? 'selected' : '' ?>
                                                        >
                                                            <?= e(
                                                                labelKondisi($condition)
                                                            ) ?>
                                                        </option>

                                                    <?php endforeach; ?>

                                                </select>

                                            </div>

                                            <!-- SPESIFIKASI -->
                                            <div class="spec-row">

                                                <label class="spec-label">
                                                    Spesifikasi
                                                </label>

                                                <textarea
                                                    name="spesifikasi_baru"
                                                    id="spesifikasi_baru"
                                                    class="form-control"
                                                    rows="4"
                                                    maxlength="255"
                                                ><?= e($form['spesifikasi_baru']) ?></textarea>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <div class="form-footer">

                <a
                    href="index.php"
                    class="btn btn-light-custom"
                >
                    <i class="bi bi-arrow-left me-1"></i>
                    Batal
                </a>

                <button
                    type="submit"
                    class="btn btn-primary-custom"
                    id="submitButton"
                >
                    <i class="bi bi-save me-1"></i>
                    Simpan Maintenance
                </button>

            </div>

        </form>

    </section>

</main>

<script>

    const AREA_DATA = <?= $areaJson ?: '[]' ?>;

    const JENIS_DATA = <?= $jenisJson ?: '[]' ?>;

    const MESIN_DATA = <?= $mesinJson ?: '[]' ?>;

    const SUB_MESIN_DATA = <?= $subMesinJson ?: '[]' ?>;

    const KOMPONEN_DATA = <?= $komponenJson ?: '[]' ?>;

    const CONDITION_DATA = <?= $conditionJson ?: '[]' ?>;

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

    const komponenPicker =
        document.getElementById('komponen_picker');

    const hiddenKomponen =
        document.getElementById('id_komponen');

    const OLD_AREA =
        <?= (int)$form['id_area'] ?>;

    const OLD_JENIS =
        <?= (int)$form['id_jenis_mesin'] ?>;

    const OLD_MESIN =
        <?= (int)$form['id_mesin'] ?>;

    const OLD_SUB_MESIN =
        <?= (int)$form['id_sub_mesin'] ?>;

    const OLD_KOMPONEN =
        <?= (int)$form['id_komponen'] ?>;

    function escapeHtml(value)
    {
        if (value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function valueOrDash(value)
    {
        const text = String(value ?? '').trim();

        return text !== ''
            ? escapeHtml(text)
            : '-';
    }

    function setSelectDisabled(select, disabled)
    {
        select.disabled = disabled;
    }

    function resetSelect(select, placeholder)
    {
        select.innerHTML = '';

        const option =
            document.createElement('option');

        option.value = '';
        option.textContent = placeholder;

        select.appendChild(option);

        select.value = '';
    }

    function addOption(
        select,
        value,
        text,
        selected = false
    )
    {
        const option =
            document.createElement('option');

        option.value = value;
        option.textContent = text;

        if (selected) {
            option.selected = true;
        }

        select.appendChild(option);
    }

    function loadArea()
    {
        const selectedLocation =
            lokasiSelect.value.trim();

        resetSelect(
            areaSelect,
            selectedLocation
                ? 'Pilih area'
                : 'Pilih lokasi terlebih dahulu'
        );

        resetSelect(
            jenisSelect,
            'Pilih jenis mesin'
        );

        resetSelect(
            mesinSelect,
            'Pilih mesin'
        );

        resetSelect(
            subMesinSelect,
            'Pilih sub mesin'
        );

        resetSelect(
            komponenPicker,
            'Pilih komponen'
        );

        setSelectDisabled(
            areaSelect,
            !selectedLocation
        );

        setSelectDisabled(
            jenisSelect,
            true
        );

        setSelectDisabled(
            mesinSelect,
            true
        );

        setSelectDisabled(
            subMesinSelect,
            true
        );

        setSelectDisabled(
            komponenPicker,
            true
        );

        hiddenKomponen.value = '';

        hideComponentPreview();

        if (!selectedLocation) {
            return;
        }

        AREA_DATA.forEach(area => {

            const areaLocation =
                String(area.lokasi ?? '').trim();

            if (
                areaLocation ===
                selectedLocation
            ) {

                addOption(
                    areaSelect,
                    area.id,
                    area.nama_area,
                    Number(area.id) === OLD_AREA
                );
            }
        });

        setSelectDisabled(
            areaSelect,
            false
        );

        if (OLD_AREA > 0) {
            loadJenis();
        }
    }

    function loadJenis()
    {
        const areaId =
            Number(areaSelect.value);

        resetSelect(
            jenisSelect,
            areaId
                ? 'Pilih jenis mesin'
                : 'Pilih area terlebih dahulu'
        );

        resetSelect(
            mesinSelect,
            'Pilih mesin'
        );

        resetSelect(
            subMesinSelect,
            'Pilih sub mesin'
        );

        resetSelect(
            komponenPicker,
            'Pilih komponen'
        );

        setSelectDisabled(
            jenisSelect,
            !areaId
        );

        setSelectDisabled(
            mesinSelect,
            true
        );

        setSelectDisabled(
            subMesinSelect,
            true
        );

        setSelectDisabled(
            komponenPicker,
            true
        );

        hiddenKomponen.value = '';

        hideComponentPreview();

        if (!areaId) {
            return;
        }

        JENIS_DATA.forEach(jenis => {

            if (
                Number(jenis.id_area) ===
                areaId
            ) {

                addOption(
                    jenisSelect,
                    jenis.id,
                    jenis.nama_jenis_mesin,
                    Number(jenis.id) === OLD_JENIS
                );
            }
        });

        setSelectDisabled(
            jenisSelect,
            false
        );

        if (OLD_JENIS > 0) {
            loadMesin();
        }
    }

    function loadMesin()
    {
        const areaId =
            Number(areaSelect.value);

        const jenisId =
            Number(jenisSelect.value);

        resetSelect(
            mesinSelect,
            jenisId
                ? 'Pilih mesin'
                : 'Pilih jenis mesin terlebih dahulu'
        );

        resetSelect(
            subMesinSelect,
            'Pilih sub mesin'
        );

        resetSelect(
            komponenPicker,
            'Pilih komponen'
        );

        setSelectDisabled(
            mesinSelect,
            !areaId || !jenisId
        );

        setSelectDisabled(
            subMesinSelect,
            true
        );

        setSelectDisabled(
            komponenPicker,
            true
        );

        hiddenKomponen.value = '';

        hideComponentPreview();

        if (!areaId || !jenisId) {
            return;
        }

        MESIN_DATA.forEach(mesin => {

            if (
                Number(mesin.id_area) === areaId &&
                Number(mesin.id_jenis_mesin) === jenisId
            ) {

                let label =
                    mesin.nama_mesin || '';

                if (
                    mesin.serial_number &&
                    String(mesin.serial_number).trim() !== ''
                ) {
                    label +=
                        ' • SN: ' +
                        mesin.serial_number;
                }

                addOption(
                    mesinSelect,
                    mesin.id,
                    label,
                    Number(mesin.id) === OLD_MESIN
                );
            }

        });

        setSelectDisabled(
            mesinSelect,
            false
        );

        if (OLD_MESIN > 0) {
            loadSubMesin();
        }
    }

    function loadSubMesin()
    {
        const mesinId =
            Number(mesinSelect.value);

        resetSelect(
            subMesinSelect,
            mesinId
                ? 'Pilih sub mesin'
                : 'Pilih mesin terlebih dahulu'
        );

        resetSelect(
            komponenPicker,
            'Pilih komponen'
        );

        setSelectDisabled(
            subMesinSelect,
            !mesinId
        );

        setSelectDisabled(
            komponenPicker,
            true
        );

        hiddenKomponen.value = '';

        hideComponentPreview();

        if (!mesinId) {
            return;
        }

        SUB_MESIN_DATA.forEach(sub => {

            if (
                Number(sub.id_mesin) === mesinId
            ) {

                let label =
                    sub.nama_sub_mesin || '';

                if (
                    sub.serial_number &&
                    String(sub.serial_number).trim() !== ''
                ) {
                    label +=
                        ' • SN: ' +
                        sub.serial_number;
                }

                addOption(
                    subMesinSelect,
                    sub.id,
                    label,
                    Number(sub.id) === OLD_SUB_MESIN
                );
            }

        });

        setSelectDisabled(
            subMesinSelect,
            false
        );

        if (OLD_SUB_MESIN > 0) {
            loadKomponen();
        }
    }

    function loadKomponen()
    {
        const subMesinId =
            Number(subMesinSelect.value);

        resetSelect(
            komponenPicker,
            subMesinId
                ? 'Pilih komponen'
                : 'Pilih sub mesin terlebih dahulu'
        );

        setSelectDisabled(
            komponenPicker,
            !subMesinId
        );

        hiddenKomponen.value = '';

        hideComponentPreview();

        if (!subMesinId) {
            return;
        }

        let jumlah = 0;

        KOMPONEN_DATA.forEach(component => {

            if (
                Number(component.id_sub_mesin) ===
                subMesinId
            ) {

                let nama =
                    String(
                        component.jenis_komponen ||
                        component.nama_bagian ||
                        'Komponen'
                    ).trim();

                let label =
                    nama;

                if (
                    component.nama_bagian &&
                    String(component.nama_bagian).trim() !== '' &&
                    String(component.nama_bagian).trim() !== nama
                ) {
                    label +=
                        ' • ' +
                        component.nama_bagian;
                }

                if (
                    component.serial_number &&
                    String(component.serial_number).trim() !== ''
                ) {
                    label +=
                        ' • SN: ' +
                        component.serial_number;
                }

                addOption(
                    komponenPicker,
                    component.id,
                    label,
                    Number(component.id) === OLD_KOMPONEN
                );

                jumlah++;
            }

        });

        if (jumlah === 0) {

            resetSelect(
                komponenPicker,
                'Tidak ada komponen'
            );

            setSelectDisabled(
                komponenPicker,
                true
            );

            return;
        }

        setSelectDisabled(
            komponenPicker,
            false
        );

        if (OLD_KOMPONEN > 0) {

            hiddenKomponen.value =
                OLD_KOMPONEN;

            populateComponent(
                OLD_KOMPONEN,
                false
            );
        }
    }

    function findComponent(id)
    {
        return KOMPONEN_DATA.find(
            component =>
                Number(component.id) ===
                Number(id)
        );
    }

    function populateComponent(
        componentId,
        resetLatest = true
    )
    {
        const component =
            findComponent(componentId);

        if (!component) {

            hiddenKomponen.value = '';

            hideComponentPreview();

            clearNormalData();

            return;
        }

        hiddenKomponen.value =
            component.id;

        const nama =
            String(
                component.jenis_komponen ||
                component.nama_bagian ||
                'Komponen'
            ).trim();

        document.getElementById(
            'previewName'
        ).textContent = nama;

        document.getElementById(
            'previewMeta'
        ).innerHTML =
            '<strong>Serial:</strong> ' +
            valueOrDash(component.serial_number) +
            ' &nbsp;•&nbsp; ' +

            '<strong>Brand:</strong> ' +
            valueOrDash(component.brand) +
            ' &nbsp;•&nbsp; ' +

            '<strong>Tipe:</strong> ' +
            valueOrDash(component.tipe) +
            '<br>' +

            '<strong>Mesin:</strong> ' +
            valueOrDash(component.nama_mesin) +
            ' &nbsp;•&nbsp; ' +

            '<strong>Sub Mesin:</strong> ' +
            valueOrDash(component.nama_sub_mesin);

        const imageContainer =
            document.getElementById(
                'componentImageContainer'
            );

        if (
            component.gambar &&
            String(component.gambar).trim() !== ''
        ) {

            const img =
                document.createElement('img');

            img.src =
                '../../uploads/komponen/' +
                encodeURIComponent(
                    component.gambar
                );

            img.alt = nama;

            img.className =
                'component-image';

            img.onerror = function() {

                imageContainer.innerHTML =
                    '<div class="component-image-placeholder">' +
                    '<i class="bi bi-nut"></i>' +
                    '</div>';
            };

            imageContainer.innerHTML = '';

            imageContainer.appendChild(img);

        } else {

            imageContainer.innerHTML =
                '<div class="component-image-placeholder">' +
                '<i class="bi bi-nut"></i>' +
                '</div>';
        }

        document
            .getElementById('componentPreview')
            .classList.add('show');

        setNormalView(
            'normal_brand_view',
            component.brand
        );

        setNormalView(
            'normal_tipe_view',
            component.tipe
        );

        setNormalView(
            'normal_part_number_view',
            component.part_number
        );

        setNormalView(
            'normal_daya_view',
            component.daya
        );

        setNormalView(
            'normal_io_address_view',
            component.io_address
        );

        setNormalView(
            'normal_ip_address_view',
            component.ip_address
        );

        setNormalView(
            'normal_input_voltage_view',
            component.input_voltage
        );

        setNormalView(
            'normal_frekuensi_input_view',
            component.frekuensi_input
        );

        setNormalView(
            'normal_arus_input_view',
            component.arus_input
        );

        setNormalView(
            'normal_output_view',
            component.output
        );

        setNormalView(
            'normal_frekuensi_output_view',
            component.frekuensi_output
        );

        setNormalView(
            'normal_ip_rating_view',
            component.ip_rating
        );

        setNormalView(
            'normal_kondisi_view',
            component.kondisi
        );

        setNormalView(
            'normal_spesifikasi_view',
            component.spesifikasi
        );

        if (resetLatest) {

            copyNormalToLatest(
                component
            );

            const noChange =
                document.querySelector(
                    'input[name="perubahan"][value="Tidak"]'
                );

            if (noChange) {
                noChange.checked = true;
            }

            updateLatestMode();
        }
    }

    function setNormalView(
        elementId,
        value
    )
    {
        const element =
            document.getElementById(elementId);

        if (!element) {
            return;
        }

        let text =
            String(value ?? '').trim();

        if (text === '') {
            text = '-';
        }

        element.textContent = text;
    }

    function clearNormalData()
    {
        const ids = [
            'normal_brand_view',
            'normal_tipe_view',
            'normal_part_number_view',
            'normal_daya_view',
            'normal_io_address_view',
            'normal_ip_address_view',
            'normal_input_voltage_view',
            'normal_frekuensi_input_view',
            'normal_arus_input_view',
            'normal_output_view',
            'normal_frekuensi_output_view',
            'normal_ip_rating_view',
            'normal_kondisi_view',
            'normal_spesifikasi_view'
        ];

        ids.forEach(id => {

            const element =
                document.getElementById(id);

            if (element) {
                element.textContent = '-';
            }

        });
    }

    function copyNormalToLatest(component)
    {
        document.getElementById(
            'brand_baru'
        ).value =
            component.brand ?? '';

        document.getElementById(
            'tipe_baru'
        ).value =
            component.tipe ?? '';

        document.getElementById(
            'part_number_baru'
        ).value =
            component.part_number ?? '';

        document.getElementById(
            'daya_baru'
        ).value =
            component.daya ?? '';

        document.getElementById(
            'io_address_baru'
        ).value =
            component.io_address ?? '';

        document.getElementById(
            'ip_address_baru'
        ).value =
            component.ip_address ?? '';

        document.getElementById(
            'input_voltage_baru'
        ).value =
            component.input_voltage ?? '';

        document.getElementById(
            'frekuensi_input_baru'
        ).value =
            component.frekuensi_input ?? '';

        document.getElementById(
            'arus_input_baru'
        ).value =
            component.arus_input ?? '';

        document.getElementById(
            'output_baru'
        ).value =
            component.output ?? '';

        document.getElementById(
            'frekuensi_output_baru'
        ).value =
            component.frekuensi_output ?? '';

        document.getElementById(
            'ip_rating_baru'
        ).value =
            component.ip_rating ?? '';

        document.getElementById(
            'spesifikasi_baru'
        ).value =
            component.spesifikasi ?? '';

        setConditionValue(
            component.kondisi ?? ''
        );
    }

    function setConditionValue(value)
    {
        const select =
            document.getElementById(
                'kondisi_baru'
            );

        if (!select) {
            return;
        }

        const target =
            String(value ?? '').trim();

        select.value = target;

        if (select.value !== target) {

            for (
                const option
                of select.options
            ) {

                const normalized =
                    option.value
                        .replace(/\.+$/, '')
                        .trim();

                const normalizedTarget =
                    target
                        .replace(/\.+$/, '')
                        .trim();

                if (
                    normalized ===
                    normalizedTarget
                ) {

                    select.value =
                        option.value;

                    break;
                }
            }
        }
    }

    function updateLatestMode()
    {
        const selected =
            document.querySelector(
                'input[name="perubahan"]:checked'
            );

        const isChange =
            selected &&
            selected.value === 'Ya';

        const latestFields =
            document.getElementById(
                'latestFields'
            );

        const latestDisabledMessage =
            document.getElementById(
                'latestDisabledMessage'
            );

        const latestBadge =
            document.getElementById(
                'latestBadge'
            );

        const component =
            findComponent(
                hiddenKomponen.value
            );

        if (isChange) {

            latestFields.classList.add(
                'show'
            );

            latestDisabledMessage.style.display =
                'none';

            latestBadge.textContent =
                'DAPAT DIEDIT';

            latestBadge.className =
                'badge bg-warning-subtle text-warning ms-auto';

            if (component) {

                const currentComponent =
                    findComponent(
                        hiddenKomponen.value
                    );

                if (currentComponent) {

                    const anyValue =
                        [
                            'brand_baru',
                            'tipe_baru',
                            'part_number_baru',
                            'daya_baru',
                            'io_address_baru',
                            'ip_address_baru',
                            'input_voltage_baru',
                            'frekuensi_input_baru',
                            'arus_input_baru',
                            'output_baru',
                            'frekuensi_output_baru',
                            'ip_rating_baru',
                            'spesifikasi_baru'
                        ].some(id => {

                            const el =
                                document.getElementById(id);

                            return (
                                el &&
                                el.value.trim() !== ''
                            );
                        });

                    if (!anyValue) {
                        copyNormalToLatest(
                            currentComponent
                        );
                    }
                }
            }

            setLatestDisabled(
                false
            );

        } else {

            latestFields.classList.remove(
                'show'
            );

            latestDisabledMessage.style.display =
                'block';

            latestBadge.textContent =
                'OTOMATIS';

            latestBadge.className =
                'badge bg-primary-subtle text-primary ms-auto';

            setLatestDisabled(
                true
            );

            if (component) {
                copyNormalToLatest(
                    component
                );
            }
        }
    }

    function setLatestDisabled(
        disabled
    )
    {
        const ids = [
            'brand_baru',
            'tipe_baru',
            'part_number_baru',
            'daya_baru',
            'io_address_baru',
            'ip_address_baru',
            'input_voltage_baru',
            'frekuensi_input_baru',
            'arus_input_baru',
            'output_baru',
            'frekuensi_output_baru',
            'ip_rating_baru',
            'kondisi_baru',
            'spesifikasi_baru'
        ];

        ids.forEach(id => {

            const element =
                document.getElementById(id);

            if (element) {
                element.disabled =
                    disabled;
            }

        });
    }

    function hideComponentPreview()
    {
        document
            .getElementById('componentPreview')
            .classList.remove('show');

        document.getElementById(
            'previewName'
        ).textContent = '-';

        document.getElementById(
            'previewMeta'
        ).textContent = '-';

        document.getElementById(
            'componentImageContainer'
        ).innerHTML =
            '<div class="component-image-placeholder">' +
            '<i class="bi bi-nut"></i>' +
            '</div>';

        clearNormalData();
    }

    lokasiSelect.addEventListener(
        'change',
        function() {

            loadArea();

        }
    );

    areaSelect.addEventListener(
        'change',
        function() {

            loadJenis();

        }
    );

    jenisSelect.addEventListener(
        'change',
        function() {

            loadMesin();

        }
    );

    mesinSelect.addEventListener(
        'change',
        function() {

            loadSubMesin();

        }
    );

    subMesinSelect.addEventListener(
        'change',
        function() {

            loadKomponen();

        }
    );

    komponenPicker.addEventListener(
        'change',
        function() {

            const componentId =
                Number(this.value);

            hiddenKomponen.value =
                componentId || '';

            if (componentId > 0) {

                populateComponent(
                    componentId,
                    true
                );

            } else {

                hideComponentPreview();

            }

        }
    );

    document
        .querySelectorAll(
            'input[name="perubahan"]'
        )
        .forEach(radio => {

            radio.addEventListener(
                'change',
                function() {

                    updateLatestMode();

                }
            );

        });

    document
        .getElementById('maintenanceForm')
        .addEventListener(
            'submit',
            function(event) {

                if (
                    !hiddenKomponen.value ||
                    Number(hiddenKomponen.value) <= 0
                ) {

                    event.preventDefault();

                    alert(
                        'Silakan pilih komponen terlebih dahulu.'
                    );

                    komponenPicker.focus();

                    return false;
                }

                const selectedChange =
                    document.querySelector(
                        'input[name="perubahan"]:checked'
                    );

                if (
                    selectedChange &&
                    selectedChange.value === 'Ya'
                ) {

                    const kondisi =
                        document.getElementById(
                            'kondisi_baru'
                        );

                    if (
                        !kondisi.value
                    ) {

                        event.preventDefault();

                        alert(
                            'Kondisi terbaru wajib dipilih.'
                        );

                        kondisi.focus();

                        return false;
                    }
                }

                /*
                 * Select yang disabled tidak dikirim browser saat submit.
                 * Aktifkan sementara seluruh hierarchy agar POST tetap lengkap.
                 * Server tetap menggunakan hierarchy dari komponen sebagai
                 * sumber kebenaran.
                 */
                areaSelect.disabled = false;
                jenisSelect.disabled = false;
                mesinSelect.disabled = false;
                subMesinSelect.disabled = false;
                komponenPicker.disabled = false;

                const button =
                    document.getElementById(
                        'submitButton'
                    );

                button.disabled = true;

                button.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-1"></span>' +
                    'Menyimpan...';

            }
        );

    function initializeForm()
    {
        if (OLD_AREA > 0) {

            const area =
                AREA_DATA.find(
                    item =>
                        Number(item.id) ===
                        Number(OLD_AREA)
                );

            if (area) {

                lokasiSelect.value =
                    area.lokasi || '';

            }
        }

        if (
            !lokasiSelect.value &&
            OLD_AREA > 0
        ) {

            const area =
                AREA_DATA.find(
                    item =>
                        Number(item.id) ===
                        Number(OLD_AREA)
                );

            if (area) {
                lokasiSelect.value =
                    area.lokasi || '';
            }
        }

        if (lokasiSelect.value) {
            loadArea();
        }

        if (!hiddenKomponen.value) {

            setLatestDisabled(
                true
            );

        } else {

            updateLatestMode();
        }
    }

    initializeForm();

    function toggleSidebar()
    {
        const sidebar =
            document.getElementById(
                'sidebar'
            );

        const overlay =
            document.getElementById(
                'sidebarOverlay'
            );

        sidebar.classList.toggle(
            'show'
        );

        overlay.classList.toggle(
            'show'
        );
    }

</script>

</body>
</html>