<?php

session_start();

require_once "../../koneksi.php";

/* =========================================================
   TIMEZONE
========================================================= */
date_default_timezone_set('Asia/Jakarta');


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
$role = strtolower(trim($_SESSION['role'] ?? ''));

if ($role !== 'admin') {
    header("Location: ../../dashboard/index.php");
    exit;
}


/* =========================================================
   DATA USER
========================================================= */
$nama_lengkap = $_SESSION['nama_lengkap']
    ?? $_SESSION['username']
    ?? 'Administrator';


/* =========================================================
   HELPER
========================================================= */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


/* =========================================================
   FILTER
========================================================= */
$filter_lokasi = trim($_GET['lokasi'] ?? '');

$filter_area = isset($_GET['id_area'])
    ? (int)$_GET['id_area']
    : 0;

$filter_jenis = isset($_GET['id_jenis'])
    ? (int)$_GET['id_jenis']
    : 0;

$filter_mesin = isset($_GET['id_mesin'])
    ? (int)$_GET['id_mesin']
    : 0;

$filter_sub = isset($_GET['id_sub_mesin'])
    ? (int)$_GET['id_sub_mesin']
    : 0;

$keyword = trim($_GET['keyword'] ?? '');


/* =========================================================
   QUERY DATA STRUKTUR
========================================================= */

$sql = "
    SELECT
        ab.id AS id_area,
        ab.lokasi,
        ab.nama_area,

        jm.id AS id_jenis_mesin,
        jm.nama_jenis_mesin,

        m.id AS id_mesin,
        m.nama_mesin,
        m.serial_number AS serial_mesin,
        m.lokasi AS lokasi_mesin,
        m.keterangan AS keterangan_mesin,
        m.gambar AS gambar_mesin,

        sm.id AS id_sub_mesin,
        sm.nama_sub_mesin,
        sm.serial_number AS serial_sub_mesin,

        k.id AS id_komponen,
        k.nama_bagian AS nama_komponen,
        k.jenis_komponen,
        k.brand AS brand_komponen,
        k.tipe AS tipe_komponen,
        k.kondisi AS kondisi_komponen,
        k.serial_number AS serial_komponen

    FROM area_bagian ab

    LEFT JOIN jenis_mesin jm
        ON jm.id_area = ab.id

    LEFT JOIN mesin m
        ON m.id_jenis_mesin = jm.id

    LEFT JOIN sub_mesin sm
        ON sm.id_mesin = m.id

    LEFT JOIN komponen k
        ON k.id_sub_mesin = sm.id

    WHERE 1=1
";


$params = [];
$types = "";


/* =========================================================
   FILTER LOKASI
========================================================= */
if ($filter_lokasi !== '') {

    $sql .= " AND ab.lokasi = ? ";

    $params[] = $filter_lokasi;
    $types .= "s";
}


/* =========================================================
   FILTER AREA
========================================================= */
if ($filter_area > 0) {

    $sql .= " AND ab.id = ? ";

    $params[] = $filter_area;
    $types .= "i";
}


/* =========================================================
   FILTER JENIS MESIN
========================================================= */
if ($filter_jenis > 0) {

    $sql .= " AND jm.id = ? ";

    $params[] = $filter_jenis;
    $types .= "i";
}


/* =========================================================
   FILTER MESIN
========================================================= */
if ($filter_mesin > 0) {

    $sql .= " AND m.id = ? ";

    $params[] = $filter_mesin;
    $types .= "i";
}


/* =========================================================
   FILTER SUB MESIN
========================================================= */
if ($filter_sub > 0) {

    $sql .= " AND sm.id = ? ";

    $params[] = $filter_sub;
    $types .= "i";
}


/* =========================================================
   SEARCH
========================================================= */
if ($keyword !== '') {

    $sql .= "
        AND (
            ab.lokasi LIKE ?
            OR ab.nama_area LIKE ?
            OR jm.nama_jenis_mesin LIKE ?
            OR m.nama_mesin LIKE ?
            OR m.serial_number LIKE ?
            OR sm.nama_sub_mesin LIKE ?
            OR sm.serial_number LIKE ?
            OR k.nama_bagian LIKE ?
            OR k.brand LIKE ?
            OR k.tipe LIKE ?
            OR k.serial_number LIKE ?
        )
    ";

    $search = "%{$keyword}%";

    for ($i = 0; $i < 11; $i++) {
        $params[] = $search;
        $types .= "s";
    }
}


/* =========================================================
   ORDER
========================================================= */
$sql .= "
    ORDER BY
        ab.lokasi ASC,
        ab.nama_area ASC,
        jm.nama_jenis_mesin ASC,
        m.nama_mesin ASC,
        sm.nama_sub_mesin ASC,
        k.nama_bagian ASC
";


/* =========================================================
   EXECUTE QUERY
========================================================= */
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query error: " . mysqli_error($conn));
}


if (!empty($params)) {

    mysqli_stmt_bind_param(
        $stmt,
        $types,
        ...$params
    );
}


mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


/* =========================================================
   SIMPAN DATA
========================================================= */
$data = [];

while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

mysqli_stmt_close($stmt);


/* =========================================================
   STATISTIK
========================================================= */

$uniqueLokasi = [];
$uniqueArea = [];
$uniqueJenis = [];
$uniqueMesin = [];
$uniqueSub = [];
$uniqueKomponen = [];

foreach ($data as $row) {

    if (!empty($row['lokasi'])) {
        $uniqueLokasi[$row['lokasi']] = true;
    }

    if (!empty($row['id_area'])) {
        $uniqueArea[$row['id_area']] = true;
    }

    if (!empty($row['id_jenis_mesin'])) {
        $uniqueJenis[$row['id_jenis_mesin']] = true;
    }

    if (!empty($row['id_mesin'])) {
        $uniqueMesin[$row['id_mesin']] = true;
    }

    if (!empty($row['id_sub_mesin'])) {
        $uniqueSub[$row['id_sub_mesin']] = true;
    }

    if (!empty($row['id_komponen'])) {
        $uniqueKomponen[$row['id_komponen']] = true;
    }
}


$totalLokasi = count($uniqueLokasi);
$totalArea = count($uniqueArea);
$totalJenis = count($uniqueJenis);
$totalMesin = count($uniqueMesin);
$totalSub = count($uniqueSub);
$totalKomponen = count($uniqueKomponen);


/* =========================================================
   DROPDOWN LOKASI
========================================================= */
$locations = [];

$qLokasi = mysqli_query(
    $conn,
    "
    SELECT DISTINCT lokasi
    FROM area_bagian
    WHERE lokasi IS NOT NULL
      AND lokasi <> ''
    ORDER BY lokasi ASC
    "
);

if ($qLokasi) {

    while ($r = mysqli_fetch_assoc($qLokasi)) {
        $locations[] = $r['lokasi'];
    }
}


/* =========================================================
   DROPDOWN AREA
========================================================= */
$areas = [];

$qArea = mysqli_query(
    $conn,
    "
    SELECT
        id,
        lokasi,
        nama_area
    FROM area_bagian
    ORDER BY lokasi ASC, nama_area ASC
    "
);

if ($qArea) {

    while ($r = mysqli_fetch_assoc($qArea)) {
        $areas[] = $r;
    }
}


/* =========================================================
   DROPDOWN JENIS MESIN
========================================================= */
$jenisMesin = [];

$qJenis = mysqli_query(
    $conn,
    "
    SELECT
        jm.id,
        jm.id_area,
        jm.nama_jenis_mesin,
        ab.lokasi,
        ab.nama_area
    FROM jenis_mesin jm
    LEFT JOIN area_bagian ab
        ON ab.id = jm.id_area
    ORDER BY
        ab.lokasi ASC,
        ab.nama_area ASC,
        jm.nama_jenis_mesin ASC
    "
);

if ($qJenis) {

    while ($r = mysqli_fetch_assoc($qJenis)) {
        $jenisMesin[] = $r;
    }
}


/* =========================================================
   DROPDOWN MESIN
========================================================= */
$mesinList = [];

$qMesin = mysqli_query(
    $conn,
    "
    SELECT
        m.id,
        m.id_jenis_mesin,
        m.nama_mesin,
        m.serial_number,
        jm.nama_jenis_mesin,
        ab.id AS id_area,
        ab.lokasi,
        ab.nama_area
    FROM mesin m

    LEFT JOIN jenis_mesin jm
        ON jm.id = m.id_jenis_mesin

    LEFT JOIN area_bagian ab
        ON ab.id = jm.id_area

    ORDER BY
        ab.lokasi ASC,
        ab.nama_area ASC,
        jm.nama_jenis_mesin ASC,
        m.nama_mesin ASC
    "
);

if ($qMesin) {

    while ($r = mysqli_fetch_assoc($qMesin)) {
        $mesinList[] = $r;
    }
}


/* =========================================================
   NAMA PATH / BREADCRUMB
========================================================= */

$currentLocation = '';

$currentAreaName = '';

$currentJenisName = '';

$currentMesinName = '';

$currentSubName = '';


if ($filter_lokasi !== '') {
    $currentLocation = $filter_lokasi;
}


/* =========================================================
   AMBIL NAMA AREA AKTIF
========================================================= */
if ($filter_area > 0) {

    $stmtArea = mysqli_prepare(
        $conn,
        "
        SELECT
            nama_area,
            lokasi
        FROM area_bagian
        WHERE id = ?
        LIMIT 1
        "
    );

    if ($stmtArea) {

        mysqli_stmt_bind_param(
            $stmtArea,
            "i",
            $filter_area
        );

        mysqli_stmt_execute($stmtArea);

        $resArea = mysqli_stmt_get_result($stmtArea);

        if ($r = mysqli_fetch_assoc($resArea)) {

            $currentAreaName = $r['nama_area'];

            if ($currentLocation === '') {
                $currentLocation = $r['lokasi'];
            }
        }

        mysqli_stmt_close($stmtArea);
    }
}


/* =========================================================
   AMBIL NAMA JENIS AKTIF
========================================================= */
if ($filter_jenis > 0) {

    $stmtJenis = mysqli_prepare(
        $conn,
        "
        SELECT
            nama_jenis_mesin
        FROM jenis_mesin
        WHERE id = ?
        LIMIT 1
        "
    );

    if ($stmtJenis) {

        mysqli_stmt_bind_param(
            $stmtJenis,
            "i",
            $filter_jenis
        );

        mysqli_stmt_execute($stmtJenis);

        $resJenis = mysqli_stmt_get_result($stmtJenis);

        if ($r = mysqli_fetch_assoc($resJenis)) {
            $currentJenisName = $r['nama_jenis_mesin'];
        }

        mysqli_stmt_close($stmtJenis);
    }
}


/* =========================================================
   AMBIL NAMA MESIN AKTIF
========================================================= */
if ($filter_mesin > 0) {

    $stmtMesin = mysqli_prepare(
        $conn,
        "
        SELECT
            nama_mesin
        FROM mesin
        WHERE id = ?
        LIMIT 1
        "
    );

    if ($stmtMesin) {

        mysqli_stmt_bind_param(
            $stmtMesin,
            "i",
            $filter_mesin
        );

        mysqli_stmt_execute($stmtMesin);

        $resMesin = mysqli_stmt_get_result($stmtMesin);

        if ($r = mysqli_fetch_assoc($resMesin)) {
            $currentMesinName = $r['nama_mesin'];
        }

        mysqli_stmt_close($stmtMesin);
    }
}


/* =========================================================
   AMBIL NAMA SUB MESIN AKTIF
========================================================= */
if ($filter_sub > 0) {

    $stmtSub = mysqli_prepare(
        $conn,
        "
        SELECT
            nama_sub_mesin
        FROM sub_mesin
        WHERE id = ?
        LIMIT 1
        "
    );

    if ($stmtSub) {

        mysqli_stmt_bind_param(
            $stmtSub,
            "i",
            $filter_sub
        );

        mysqli_stmt_execute($stmtSub);

        $resSub = mysqli_stmt_get_result($stmtSub);

        if ($r = mysqli_fetch_assoc($resSub)) {
            $currentSubName = $r['nama_sub_mesin'];
        }

        mysqli_stmt_close($stmtSub);
    }
}


/* =========================================================
   LEVEL FOLDER
========================================================= */

if ($filter_sub > 0) {

    $currentLevel = 'komponen';

} elseif ($filter_mesin > 0) {

    $currentLevel = 'sub';

} elseif ($filter_jenis > 0) {

    $currentLevel = 'mesin';

} elseif ($filter_area > 0) {

    $currentLevel = 'jenis';

} elseif ($filter_lokasi !== '') {

    $currentLevel = 'area';

} else {

    $currentLevel = 'lokasi';
}


/* =========================================================
   BANGUN FOLDER DARI DATA
========================================================= */

$folders = [];


/* =========================================================
   LEVEL LOKASI
========================================================= */

if ($currentLevel === 'lokasi') {

    foreach ($data as $row) {

        $lokasi = trim($row['lokasi'] ?? '');

        if ($lokasi === '') {
            continue;
        }

        if (!isset($folders[$lokasi])) {

            $folders[$lokasi] = [
                'name' => $lokasi,
                'id' => $lokasi,
                'areas' => [],
                'jenis' => [],
                'mesin' => [],
                'sub' => [],
                'komponen' => []
            ];
        }

        if (!empty($row['id_area'])) {
            $folders[$lokasi]['areas'][$row['id_area']] = true;
        }

        if (!empty($row['id_jenis_mesin'])) {
            $folders[$lokasi]['jenis'][$row['id_jenis_mesin']] = true;
        }

        if (!empty($row['id_mesin'])) {
            $folders[$lokasi]['mesin'][$row['id_mesin']] = true;
        }

        if (!empty($row['id_sub_mesin'])) {
            $folders[$lokasi]['sub'][$row['id_sub_mesin']] = true;
        }

        if (!empty($row['id_komponen'])) {
            $folders[$lokasi]['komponen'][$row['id_komponen']] = true;
        }
    }
}


/* =========================================================
   LEVEL AREA
========================================================= */

elseif ($currentLevel === 'area') {

    foreach ($data as $row) {

        if (empty($row['id_area'])) {
            continue;
        }

        $id = (int)$row['id_area'];

        if (!isset($folders[$id])) {

            $folders[$id] = [
                'name' => $row['nama_area'],
                'id' => $id,
                'lokasi' => $row['lokasi'],
                'jenis' => [],
                'mesin' => [],
                'sub' => [],
                'komponen' => []
            ];
        }

        if (!empty($row['id_jenis_mesin'])) {
            $folders[$id]['jenis'][$row['id_jenis_mesin']] = true;
        }

        if (!empty($row['id_mesin'])) {
            $folders[$id]['mesin'][$row['id_mesin']] = true;
        }

        if (!empty($row['id_sub_mesin'])) {
            $folders[$id]['sub'][$row['id_sub_mesin']] = true;
        }

        if (!empty($row['id_komponen'])) {
            $folders[$id]['komponen'][$row['id_komponen']] = true;
        }
    }
}


/* =========================================================
   LEVEL JENIS MESIN
========================================================= */

elseif ($currentLevel === 'jenis') {

    foreach ($data as $row) {

        if (empty($row['id_jenis_mesin'])) {
            continue;
        }

        $id = (int)$row['id_jenis_mesin'];

        if (!isset($folders[$id])) {

            $folders[$id] = [
                'name' => $row['nama_jenis_mesin'],
                'id' => $id,
                'mesin' => [],
                'sub' => [],
                'komponen' => []
            ];
        }

        if (!empty($row['id_mesin'])) {
            $folders[$id]['mesin'][$row['id_mesin']] = true;
        }

        if (!empty($row['id_sub_mesin'])) {
            $folders[$id]['sub'][$row['id_sub_mesin']] = true;
        }

        if (!empty($row['id_komponen'])) {
            $folders[$id]['komponen'][$row['id_komponen']] = true;
        }
    }
}


/* =========================================================
   LEVEL MESIN
========================================================= */

elseif ($currentLevel === 'mesin') {

    foreach ($data as $row) {

        if (empty($row['id_mesin'])) {
            continue;
        }

        $id = (int)$row['id_mesin'];

        if (!isset($folders[$id])) {

            $folders[$id] = [
                'name' => $row['nama_mesin'],
                'id' => $id,
                'serial' => $row['serial_mesin'],
                'sub' => [],
                'komponen' => []
            ];
        }

        if (!empty($row['id_sub_mesin'])) {
            $folders[$id]['sub'][$row['id_sub_mesin']] = true;
        }

        if (!empty($row['id_komponen'])) {
            $folders[$id]['komponen'][$row['id_komponen']] = true;
        }
    }
}


/* =========================================================
   LEVEL SUB MESIN
========================================================= */

elseif ($currentLevel === 'sub') {

    foreach ($data as $row) {

        if (empty($row['id_sub_mesin'])) {
            continue;
        }

        $id = (int)$row['id_sub_mesin'];

        if (!isset($folders[$id])) {

            $folders[$id] = [
                'name' => $row['nama_sub_mesin'],
                'id' => $id,
                'serial' => $row['serial_sub_mesin'],
                'komponen' => []
            ];
        }

        if (!empty($row['id_komponen'])) {
            $folders[$id]['komponen'][$row['id_komponen']] = true;
        }
    }
}


/* =========================================================
   LEVEL KOMPONEN
========================================================= */

$components = [];

if ($currentLevel === 'komponen') {

    $componentIds = [];

    foreach ($data as $row) {

        if (
            !empty($row['id_komponen'])
            && !isset($componentIds[$row['id_komponen']])
        ) {

            $componentIds[$row['id_komponen']] = true;

            $components[] = $row;
        }
    }
}


/* =========================================================
   BREADCRUMB
========================================================= */

$breadcrumb = [];

if ($currentLocation !== '') {

    $breadcrumb[] = [
        'label' => $currentLocation,
        'url' => 'struktur.php'
    ];
}

if ($filter_area > 0) {

    $url = 'struktur.php?lokasi=' . urlencode($currentLocation);

    $breadcrumb[] = [
        'label' => $currentAreaName,
        'url' => $url
    ];
}

if ($filter_jenis > 0) {

    $url =
        'struktur.php'
        . '?lokasi=' . urlencode($currentLocation)
        . '&id_area=' . $filter_area;

    $breadcrumb[] = [
        'label' => $currentJenisName,
        'url' => $url
    ];
}

if ($filter_mesin > 0) {

    $url =
        'struktur.php'
        . '?lokasi=' . urlencode($currentLocation)
        . '&id_area=' . $filter_area
        . '&id_jenis=' . $filter_jenis;

    $breadcrumb[] = [
        'label' => $currentMesinName,
        'url' => $url
    ];
}

if ($filter_sub > 0) {

    $url =
        'struktur.php'
        . '?lokasi=' . urlencode($currentLocation)
        . '&id_area=' . $filter_area
        . '&id_jenis=' . $filter_jenis
        . '&id_mesin=' . $filter_mesin;

    $breadcrumb[] = [
        'label' => $currentSubName,
        'url' => $url
    ];
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
        Struktur Mesin | Inventory Maintenance
    </title>


    <!-- =====================================================
         GOOGLE FONT
    ====================================================== -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


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
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <style>

        /* =====================================================
           ROOT
        ====================================================== */

        :root {

            --primary: #123f7a;
            --primary-dark: #092f63;
            --primary-light: #eaf2ff;

            --text: #172033;
            --muted: #788396;

            --border: #e7ebf1;

            --white: #ffffff;

            --bg: #f5f7fb;

            --danger: #dc3545;
            --danger-bg: #fff0f0;

            --success: #198754;
            --warning: #d99b00;

            --sidebar-width: 240px;
        }


        /* =====================================================
           GLOBAL
        ====================================================== */

        * {
            box-sizing: border-box;
        }


        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }


        body {

            font-family: 'Poppins', sans-serif;

            font-size: 13px;

            color: var(--text);

            background: var(--bg);
        }


        a {
            text-decoration: none;
        }


        /* =====================================================
           SIDEBAR
        ====================================================== */

        .sidebar {

            position: fixed;

            top: 0;
            left: 0;

            width: var(--sidebar-width);

            height: 100vh;

            background: var(--white);

            border-right: 1px solid var(--border);

            z-index: 1050;

            display: flex;

            flex-direction: column;
        }


        .sidebar-header {

            height: 76px;

            padding: 0 18px;

            display: flex;

            align-items: center;

            gap: 10px;

            border-bottom: 1px solid var(--border);

            flex-shrink: 0;
        }


        .sidebar-logo {

            width: 42px;
            height: 42px;

            object-fit: contain;

            flex-shrink: 0;
        }


        .sidebar-brand {
            min-width: 0;
        }


        .sidebar-brand-title {

            font-size: 12px;

            font-weight: 700;

            color: var(--text);

            line-height: 1.2;
        }


        .sidebar-brand-subtitle {

            margin-top: 3px;

            font-size: 8px;

            line-height: 1.3;

            color: var(--muted);

            text-transform: uppercase;
        }


        .sidebar-content {

            flex: 1;

            overflow-y: auto;

            padding: 10px 12px 12px;
        }


        .sidebar-content::-webkit-scrollbar {
            width: 5px;
        }


        .sidebar-content::-webkit-scrollbar-track {
            background: transparent;
        }


        .sidebar-content::-webkit-scrollbar-thumb {

            background: #d5dce6;

            border-radius: 10px;
        }


        .menu-label {

            padding: 14px 9px 7px;

            font-size: 9px;

            font-weight: 700;

            letter-spacing: .9px;

            color: #a1a9b8;

            text-transform: uppercase;
        }


        .menu-label:first-child {
            padding-top: 5px;
        }


        .menu-link {

            display: flex;

            align-items: center;

            gap: 11px;

            width: 100%;

            padding: 10px 10px;

            margin-bottom: 3px;

            border-radius: 9px;

            color: #566174;

            font-size: 11px;

            font-weight: 500;

            text-decoration: none;

            transition: .2s ease;
        }


        .menu-link i {

            width: 18px;

            text-align: center;

            font-size: 16px;

            flex-shrink: 0;
        }


        .menu-link:hover {

            background: var(--primary-light);

            color: var(--primary);
        }


        .menu-link.active {

            background: var(--primary);

            color: #fff;
        }


        .menu-link.logout {

            color: var(--danger);
        }


        .menu-link.logout:hover {

            background: var(--danger-bg);

            color: var(--danger);
        }


        .sidebar-footer {

            padding: 14px 15px;

            border-top: 1px solid var(--border);

            display: flex;

            align-items: center;

            gap: 10px;

            background: #fff;

            flex-shrink: 0;
        }


        .sidebar-footer img {

            width: 42px;
            height: 42px;

            object-fit: contain;

            object-position: center;

            border-radius: 8px;

            border: 1px solid var(--border);

            background: #fff;

            padding: 2px;

            flex-shrink: 0;
        }


        .sidebar-footer-text {

            font-size: 8px;

            line-height: 1.5;

            color: var(--muted);

            min-width: 0;
        }


        .sidebar-footer-text strong {

            display: block;

            color: var(--text);

            font-size: 9px;

            font-weight: 700;
        }


        /* =====================================================
           OVERLAY
        ====================================================== */

        .overlay {

            position: fixed;

            inset: 0;

            background: rgba(0, 0, 0, .35);

            z-index: 1040;

            opacity: 0;

            visibility: hidden;

            transition: .25s ease;
        }


        .overlay.show {

            opacity: 1;

            visibility: visible;
        }


        /* =====================================================
           MAIN
        ====================================================== */

        .main-wrapper {

            margin-left: var(--sidebar-width);

            min-height: 100vh;
        }


        /* =====================================================
           TOPBAR
        ====================================================== */

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

            z-index: 900;
        }


        .topbar-left {

            display: flex;

            align-items: center;

            gap: 13px;

            min-width: 0;
        }


        .mobile-toggle {

            display: none;

            border: 0;

            background: transparent;

            color: var(--text);

            font-size: 24px;

            padding: 4px;

            line-height: 1;
        }


        .mobile-toggle:hover {

            color: var(--primary);
        }


        .page-title {

            margin: 0;

            font-size: 19px;

            font-weight: 700;

            color: var(--text);
        }


        .page-subtitle {

            margin: 2px 0 0;

            font-size: 11px;

            color: var(--muted);
        }


        .topbar-user {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .user-avatar {

            width: 34px;

            height: 34px;

            border-radius: 50%;

            background: var(--primary-light);

            color: var(--primary);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 15px;
        }


        .user-info {

            line-height: 1.2;
        }


        .user-name {

            font-size: 11px;

            font-weight: 600;

            color: var(--text);
        }


        .user-role {

            margin-top: 3px;

            font-size: 8px;

            color: var(--muted);

            text-transform: uppercase;
        }


        /* =====================================================
           PAGE CONTENT
        ====================================================== */

        .page-content {

            padding: 24px 28px 35px;
        }


        /* =====================================================
           PAGE HEADER
        ====================================================== */

        .content-header {

            display: flex;

            align-items: flex-start;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 20px;
        }


        .content-heading h2 {

            margin: 0;

            font-size: 17px;

            font-weight: 700;

            color: var(--text);
        }


        .content-heading p {

            margin: 5px 0 0;

            font-size: 10px;

            color: var(--muted);
        }


        /* =====================================================
           STATISTICS
        ====================================================== */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(6, minmax(0, 1fr));

            gap: 10px;

            margin-bottom: 18px;
        }


        .stat-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 12px;

            padding: 13px;

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .stat-icon {

            width: 38px;

            height: 38px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: var(--primary-light);

            color: var(--primary);

            font-size: 17px;

            flex-shrink: 0;
        }


        .stat-number {

            font-size: 17px;

            font-weight: 700;

            line-height: 1.1;

            color: var(--text);
        }


        .stat-label {

            margin-top: 3px;

            font-size: 8px;

            color: var(--muted);

            text-transform: uppercase;

            letter-spacing: .3px;
        }


        /* =====================================================
           FILTER
        ====================================================== */

        .filter-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 13px;

            padding: 16px;

            margin-bottom: 18px;
        }


        .filter-title {

            display: flex;

            align-items: center;

            gap: 8px;

            margin-bottom: 13px;

            font-size: 11px;

            font-weight: 700;

            color: var(--text);
        }


        .filter-title i {

            color: var(--primary);

            font-size: 15px;
        }


        .filter-grid {

            display: grid;

            grid-template-columns:
                1.1fr
                1.1fr
                1.1fr
                1.1fr
                1.5fr
                auto;

            gap: 9px;

            align-items: end;
        }


        .form-label-small {

            display: block;

            margin-bottom: 5px;

            font-size: 9px;

            font-weight: 600;

            color: #5e697b;
        }


        .form-control,
        .form-select {

            min-height: 37px;

            border-color: var(--border);

            border-radius: 8px;

            font-family: 'Poppins', sans-serif;

            font-size: 10px;

            color: var(--text);
        }


        .form-control:focus,
        .form-select:focus {

            border-color: #a7c1e3;

            box-shadow: 0 0 0 .18rem rgba(18, 63, 122, .08);
        }


        .filter-buttons {

            display: flex;

            gap: 6px;
        }


        .btn-filter {

            min-height: 37px;

            border: 0;

            border-radius: 8px;

            padding: 0 13px;

            background: var(--primary);

            color: #fff;

            font-family: 'Poppins', sans-serif;

            font-size: 10px;

            font-weight: 600;

            white-space: nowrap;
        }


        .btn-filter:hover {

            background: var(--primary-dark);

            color: #fff;
        }


        .btn-reset {

            min-height: 37px;

            border: 1px solid var(--border);

            border-radius: 8px;

            padding: 0 11px;

            background: #fff;

            color: #687386;

            font-size: 10px;

            font-weight: 500;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            white-space: nowrap;
        }


        .btn-reset:hover {

            background: #f7f9fc;

            color: var(--text);
        }


        /* =====================================================
           BROWSER
        ====================================================== */

        .structure-card {

            background: var(--white);

            border: 1px solid var(--border);

            border-radius: 14px;

            overflow: hidden;
        }


        .browser-toolbar {

            min-height: 57px;

            padding: 10px 16px;

            border-bottom: 1px solid var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            background: #fff;
        }


        .breadcrumb-area {

            display: flex;

            align-items: center;

            gap: 5px;

            min-width: 0;

            flex-wrap: wrap;
        }


        .breadcrumb-home {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            width: 27px;

            height: 27px;

            border-radius: 7px;

            background: var(--primary-light);

            color: var(--primary);

            font-size: 13px;
        }


        .breadcrumb-item-custom {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            font-size: 10px;

            font-weight: 500;

            color: #687386;

            min-width: 0;
        }


        .breadcrumb-item-custom a {

            color: var(--primary);

            font-weight: 600;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

            max-width: 220px;
        }


        .breadcrumb-separator {

            color: #b4bcc8;

            font-size: 11px;
        }


        .browser-info {

            display: flex;

            align-items: center;

            gap: 7px;

            color: var(--muted);

            font-size: 9px;

            white-space: nowrap;
        }


        .back-button {

            display: inline-flex;

            align-items: center;

            gap: 5px;

            padding: 7px 10px;

            border: 1px solid var(--border);

            border-radius: 7px;

            background: #fff;

            color: #687386;

            font-size: 9px;

            font-weight: 500;
        }


        .back-button:hover {

            background: #f7f9fc;

            color: var(--primary);
        }


        /* =====================================================
           FOLDER AREA
        ====================================================== */

        .folder-content {

            padding: 18px;
        }


        .folder-heading {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 10px;

            margin-bottom: 13px;
        }


        .folder-heading-left {

            display: flex;

            align-items: center;

            gap: 9px;
        }


        .folder-heading-icon {

            width: 34px;

            height: 34px;

            border-radius: 8px;

            background: #fff5d8;

            color: #d89b00;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 16px;
        }


        .folder-heading-title {

            font-size: 12px;

            font-weight: 700;

            color: var(--text);
        }


        .folder-heading-sub {

            margin-top: 2px;

            font-size: 9px;

            color: var(--muted);
        }


        .folder-count {

            padding: 5px 8px;

            border-radius: 6px;

            background: #f5f7fb;

            color: var(--muted);

            font-size: 8px;

            font-weight: 600;
        }


        .folder-grid {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 10px;
        }


        /* =====================================================
           FOLDER
        ====================================================== */

        .folder-item {

            display: flex;

            align-items: center;

            gap: 11px;

            min-width: 0;

            padding: 12px;

            background: #fff;

            border: 1px solid var(--border);

            border-radius: 10px;

            transition: .18s ease;

            cursor: pointer;
        }


        .folder-item:hover {

            border-color: #c5d6eb;

            background: #f9fbff;

            transform: translateY(-1px);

            box-shadow: 0 4px 12px rgba(20, 50, 90, .05);
        }


        .folder-icon {

            width: 42px;

            height: 38px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            justify-content: center;

            flex-shrink: 0;

            font-size: 19px;

            color: #d79a00;

            background: #fff5d8;
        }


        .folder-icon.location {

            background: #eaf4ff;

            color: #2670b7;
        }


        .folder-icon.area {

            background: #eaf8f1;

            color: #15915d;
        }


        .folder-icon.jenis {

            background: #fff4df;

            color: #bf7c00;
        }


        .folder-icon.mesin {

            background: #f0edff;

            color: #6851c8;
        }


        .folder-icon.sub {

            background: #edf4ff;

            color: #3976bd;
        }


        .folder-info {

            min-width: 0;

            flex: 1;
        }


        .folder-name {

            font-size: 10px;

            font-weight: 600;

            color: var(--text);

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .folder-meta {

            margin-top: 4px;

            font-size: 8px;

            color: var(--muted);

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .folder-arrow {

            color: #b0b8c5;

            font-size: 13px;

            flex-shrink: 0;
        }


        .folder-item:hover .folder-arrow {

            color: var(--primary);
        }


        /* =====================================================
           COMPONENT FILE
        ====================================================== */

        .component-grid {

            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 10px;
        }


        .component-item {

            border: 1px solid var(--border);

            border-radius: 10px;

            padding: 13px;

            background: #fff;

            transition: .18s ease;
        }


        .component-item:hover {

            border-color: #c5d6eb;

            background: #f9fbff;
        }


        .component-top {

            display: flex;

            align-items: flex-start;

            gap: 10px;
        }


        .component-icon {

            width: 38px;

            height: 38px;

            border-radius: 8px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #edf4ff;

            color: var(--primary);

            font-size: 17px;

            flex-shrink: 0;
        }


        .component-name {

            font-size: 10px;

            font-weight: 700;

            color: var(--text);

            line-height: 1.35;
        }


        .component-type {

            margin-top: 3px;

            font-size: 8px;

            color: var(--muted);
        }


        .component-detail {

            margin-top: 11px;

            padding-top: 9px;

            border-top: 1px solid #f0f2f5;

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 7px;
        }


        .component-detail-item {

            min-width: 0;
        }


        .component-detail-label {

            font-size: 7px;

            color: #9aa3b2;

            text-transform: uppercase;
        }


        .component-detail-value {

            margin-top: 2px;

            font-size: 8px;

            font-weight: 500;

            color: #596476;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .condition-badge {

            display: inline-flex;

            align-items: center;

            padding: 3px 6px;

            border-radius: 5px;

            font-size: 7px;

            font-weight: 600;
        }


        .condition-baik {

            background: #eaf8f1;

            color: #168653;
        }


        .condition-periksa {

            background: #fff5dd;

            color: #a96e00;
        }


        .condition-rusak {

            background: #fff0f0;

            color: #c73535;
        }


        .condition-default {

            background: #f2f4f7;

            color: #687386;
        }


        /* =====================================================
           EMPTY STATE
        ====================================================== */

        .empty-state {

            padding: 55px 20px;

            text-align: center;
        }


        .empty-icon {

            width: 58px;

            height: 58px;

            margin: 0 auto 12px;

            border-radius: 14px;

            background: #f1f4f8;

            color: #9ca6b5;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 26px;
        }


        .empty-title {

            font-size: 12px;

            font-weight: 700;

            color: var(--text);
        }


        .empty-text {

            margin-top: 4px;

            font-size: 9px;

            color: var(--muted);
        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1200px) {

            .stats-grid {

                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }


            .filter-grid {

                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }


            .folder-grid {

                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }


            .component-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }


        @media (max-width: 991.98px) {

            .sidebar {

                transform: translateX(-100%);

                transition: transform .25s ease;
            }


            .sidebar.show {

                transform: translateX(0);
            }


            .main-wrapper {

                margin-left: 0;
            }


            .mobile-toggle {

                display: inline-flex;

                align-items: center;

                justify-content: center;
            }


            .topbar {

                padding: 0 18px;
            }


            .page-content {

                padding: 20px 18px 30px;
            }


            .folder-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .content-header {

                flex-direction: column;
            }
        }


        @media (max-width: 767.98px) {

            .topbar-user {

                display: none;
            }


            .stats-grid {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }


            .filter-grid {

                grid-template-columns:
                    1fr 1fr;
            }


            .filter-buttons {

                grid-column: 1 / -1;
            }


            .folder-grid,
            .component-grid {

                grid-template-columns: 1fr;
            }


            .browser-toolbar {

                align-items: flex-start;

                flex-direction: column;
            }


            .browser-info {

                width: 100%;

                justify-content: space-between;
            }
        }


        @media (max-width: 480px) {

            .page-content {

                padding: 15px 12px 25px;
            }


            .topbar {

                padding: 0 13px;
            }


            .page-title {

                font-size: 16px;
            }


            .page-subtitle {

                font-size: 9px;
            }


            .stats-grid {

                grid-template-columns: 1fr 1fr;

                gap: 7px;
            }


            .stat-card {

                padding: 10px;

                gap: 7px;
            }


            .stat-icon {

                width: 32px;

                height: 32px;

                font-size: 14px;
            }


            .stat-number {

                font-size: 14px;
            }


            .stat-label {

                font-size: 7px;
            }


            .filter-grid {

                grid-template-columns: 1fr;
            }


            .filter-buttons {

                grid-column: auto;
            }
        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar" id="sidebar">


    <!-- SIDEBAR HEADER -->

    <div class="sidebar-header">

        <img
            src="../../assets/img/logo-garudafood.png"
            alt="Garudafood"
            class="sidebar-logo"
        >

        <div class="sidebar-brand">

            <div class="sidebar-brand-title">
                Inventory Maintenance
            </div>

            <div class="sidebar-brand-subtitle">
                PT GARUDAFOOD PUTRA PUTRI JAYA TBK
            </div>

        </div>

    </div>


    <!-- SIDEBAR CONTENT -->

    <div class="sidebar-content">


        <!-- =================================================
             ADMIN
        ================================================== -->

        <div class="menu-label">
            Admin
        </div>


        <!-- Dashboard -->

        <a
            href="../index.php"
            class="menu-link"
        >

            <i class="bi bi-grid-1x2"></i>

            <span>
                Dashboard
            </span>

        </a>


        <!-- Struktur -->

        <a
            href="struktur.php"
            class="menu-link active"
        >

            <i class="bi bi-folder2-open"></i>

            <span>
                Struktur
            </span>

        </a>


        <!-- =================================================
             DATA UTAMA
        ================================================== -->

        <div class="menu-label">
            Data Utama
        </div>


        <!-- Data Area -->

        <a
            href="../area/index.php"
            class="menu-link"
        >

            <i class="bi bi-geo-alt"></i>

            <span>
                Data Area
            </span>

        </a>


        <!-- Jenis Mesin -->

        <a
            href="../jenis_mesin/index.php"
            class="menu-link"
        >

            <i class="bi bi-cpu"></i>

            <span>
                Jenis Mesin
            </span>

        </a>


        <!-- Sub Mesin -->

        <a
            href="../sub_mesin/index.php"
            class="menu-link"
        >

            <i class="bi bi-diagram-3"></i>

            <span>
                Sub Mesin
            </span>

        </a>


        <!-- Data Komponen -->

        <a
            href="../komponen/index.php"
            class="menu-link"
        >

            <i class="bi bi-box-seam"></i>

            <span>
                Data Komponen
            </span>

        </a>


        <!-- =================================================
             TRANSAKSI
        ================================================== -->

        <div class="menu-label">
            Transaksi
        </div>


        <!-- Riwayat Maintenance -->

        <a
            href="../maintenance/index.php"
            class="menu-link"
        >

            <i class="bi bi-wrench-adjustable-circle"></i>

            <span>
                Riwayat Maintenance
            </span>

        </a>


        <!-- =================================================
             PENGGUNA
        ================================================== -->

        <div class="menu-label">
            Pengguna
        </div>


        <!-- Manajemen User -->

        <a
            href="../users/index.php"
            class="menu-link"
        >

            <i class="bi bi-people"></i>

            <span>
                Manajemen User
            </span>

        </a>


        <!-- =================================================
             AKUN
        ================================================== -->

        <div class="menu-label">
            Akun
        </div>


        <!-- Logout -->

        <a
            href="../../logout.php"
            class="menu-link logout"
            onclick="return confirm('Apakah Anda yakin ingin keluar?');"
        >

            <i class="bi bi-box-arrow-right"></i>

            <span>
                Logout
            </span>

        </a>


    </div>


    <!-- =====================================================
         SIDEBAR FOOTER
    ====================================================== -->

    <div class="sidebar-footer">

        <img
            src="../../assets/img/POLTEKSI_LG.jpeg"
            alt="Politeknik Semen Indonesia"
        >

        <div class="sidebar-footer-text">

            <strong>
                D-3 Teknologi Informasi
            </strong>

            Politeknik Semen Indonesia

        </div>

    </div>


</aside>


<!-- MOBILE OVERLAY -->

<div
    class="overlay"
    id="sidebarOverlay"
></div>



<!-- =========================================================
     MAIN WRAPPER
========================================================= -->

<div class="main-wrapper">


    <!-- =====================================================
         TOPBAR
    ====================================================== -->

    <div class="topbar">


        <div class="topbar-left">

            <button
                type="button"
                class="mobile-toggle"
                id="mobileToggle"
                aria-label="Buka menu"
            >

                <i class="bi bi-list"></i>

            </button>


            <div>

                <h1 class="page-title">
                    Struktur
                </h1>

                <p class="page-subtitle">
                    Struktur folder data mesin
                </p>

            </div>

        </div>


        <!-- USER -->

        <div class="topbar-user">

            <div class="user-avatar">

                <i class="bi bi-person"></i>

            </div>

            <div class="user-info">

                <div class="user-name">
                    <?= e($nama_lengkap); ?>
                </div>

                <div class="user-role">
                    Administrator
                </div>

            </div>

        </div>


    </div>



    <!-- =====================================================
         PAGE CONTENT
    ====================================================== -->

    <main class="page-content">


        <!-- PAGE HEADER -->

        <div class="content-header">

            <div class="content-heading">

                <h2>
                    Struktur Mesin
                </h2>

                <p>
                    Jelajahi data mesin berdasarkan folder lokasi, area,
                    jenis mesin, mesin, sub mesin, hingga komponen.
                </p>

            </div>

        </div>



        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="stats-grid">


            <!-- Lokasi -->

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="bi bi-geo-alt"></i>

                </div>

                <div>

                    <div class="stat-number">
                        <?= number_format($totalLokasi); ?>
                    </div>

                    <div class="stat-label">
                        Lokasi
                    </div>

                </div>

            </div>


            <!-- Area -->

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="bi bi-grid"></i>

                </div>

                <div>

                    <div class="stat-number">
                        <?= number_format($totalArea); ?>
                    </div>

                    <div class="stat-label">
                        Area
                    </div>

                </div>

            </div>


            <!-- Jenis -->

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="bi bi-cpu"></i>

                </div>

                <div>

                    <div class="stat-number">
                        <?= number_format($totalJenis); ?>
                    </div>

                    <div class="stat-label">
                        Jenis Mesin
                    </div>

                </div>

            </div>


            <!-- Mesin -->

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="bi bi-hdd-stack"></i>

                </div>

                <div>

                    <div class="stat-number">
                        <?= number_format($totalMesin); ?>
                    </div>

                    <div class="stat-label">
                        Mesin / Merk
                    </div>

                </div>

            </div>


            <!-- Sub Mesin -->

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="bi bi-diagram-3"></i>

                </div>

                <div>

                    <div class="stat-number">
                        <?= number_format($totalSub); ?>
                    </div>

                    <div class="stat-label">
                        Sub Mesin
                    </div>

                </div>

            </div>


            <!-- Komponen -->

            <div class="stat-card">

                <div class="stat-icon">

                    <i class="bi bi-box-seam"></i>

                </div>

                <div>

                    <div class="stat-number">
                        <?= number_format($totalKomponen); ?>
                    </div>

                    <div class="stat-label">
                        Komponen
                    </div>

                </div>

            </div>


        </div>



        <!-- =================================================
             FILTER
        ================================================== -->

        <div class="filter-card">


            <div class="filter-title">

                <i class="bi bi-funnel"></i>

                Filter Struktur

            </div>


            <form
                method="GET"
                action="struktur.php"
            >

                <div class="filter-grid">


                    <!-- LOKASI -->

                    <div>

                        <label class="form-label-small">
                            Lokasi
                        </label>

                        <select
                            name="lokasi"
                            id="filterLokasi"
                            class="form-select"
                        >

                            <option value="">
                                Semua Lokasi
                            </option>

                            <?php foreach ($locations as $lokasi): ?>

                                <option
                                    value="<?= e($lokasi); ?>"
                                    <?= $filter_lokasi === $lokasi ? 'selected' : ''; ?>
                                >
                                    <?= e($lokasi); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- AREA -->

                    <div>

                        <label class="form-label-small">
                            Area
                        </label>

                        <select
                            name="id_area"
                            id="filterArea"
                            class="form-select"
                        >

                            <option value="0">
                                Semua Area
                            </option>

                            <?php foreach ($areas as $area): ?>

                                <option
                                    value="<?= (int)$area['id']; ?>"
                                    data-lokasi="<?= e($area['lokasi']); ?>"
                                    <?= $filter_area === (int)$area['id'] ? 'selected' : ''; ?>
                                >
                                    <?= e($area['nama_area']); ?>
                                    — <?= e($area['lokasi']); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- JENIS -->

                    <div>

                        <label class="form-label-small">
                            Jenis Mesin
                        </label>

                        <select
                            name="id_jenis"
                            id="filterJenis"
                            class="form-select"
                        >

                            <option value="0">
                                Semua Jenis
                            </option>

                            <?php foreach ($jenisMesin as $jenis): ?>

                                <option
                                    value="<?= (int)$jenis['id']; ?>"
                                    data-area="<?= (int)$jenis['id_area']; ?>"
                                    <?= $filter_jenis === (int)$jenis['id'] ? 'selected' : ''; ?>
                                >
                                    <?= e($jenis['nama_jenis_mesin']); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- MESIN -->

                    <div>

                        <label class="form-label-small">
                            Mesin / Merk
                        </label>

                        <select
                            name="id_mesin"
                            id="filterMesin"
                            class="form-select"
                        >

                            <option value="0">
                                Semua Mesin
                            </option>

                            <?php foreach ($mesinList as $mesin): ?>

                                <option
                                    value="<?= (int)$mesin['id']; ?>"
                                    data-jenis="<?= (int)$mesin['id_jenis_mesin']; ?>"
                                    <?= $filter_mesin === (int)$mesin['id'] ? 'selected' : ''; ?>
                                >
                                    <?= e($mesin['nama_mesin']); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- SEARCH -->

                    <div>

                        <label class="form-label-small">
                            Cari
                        </label>

                        <input
                            type="text"
                            name="keyword"
                            class="form-control"
                            value="<?= e($keyword); ?>"
                            placeholder="Cari mesin, sub mesin, komponen..."
                        >

                    </div>


                    <!-- BUTTON -->

                    <div class="filter-buttons">

                        <button
                            type="submit"
                            class="btn-filter"
                        >

                            <i class="bi bi-search"></i>

                            Terapkan

                        </button>


                        <a
                            href="struktur.php"
                            class="btn-reset"
                        >

                            Reset

                        </a>

                    </div>


                </div>

            </form>

        </div>



        <!-- =================================================
             STRUCTURE BROWSER
        ================================================== -->

        <div class="structure-card">


            <!-- BROWSER TOOLBAR -->

            <div class="browser-toolbar">


                <div class="breadcrumb-area">


                    <a
                        href="struktur.php"
                        class="breadcrumb-home"
                        title="Root Struktur"
                    >

                        <i class="bi bi-house"></i>

                    </a>


                    <?php if (empty($breadcrumb)): ?>

                        <div class="breadcrumb-item-custom">

                            <span>
                                Struktur Mesin
                            </span>

                        </div>

                    <?php else: ?>

                        <?php foreach ($breadcrumb as $index => $crumb): ?>

                            <span class="breadcrumb-separator">
                                /
                            </span>

                            <?php if ($index < count($breadcrumb) - 1): ?>

                                <div class="breadcrumb-item-custom">

                                    <a href="<?= e($crumb['url']); ?>">

                                        <?= e($crumb['label']); ?>

                                    </a>

                                </div>

                            <?php else: ?>

                                <div class="breadcrumb-item-custom">

                                    <span>
                                        <?= e($crumb['label']); ?>
                                    </span>

                                </div>

                            <?php endif; ?>

                        <?php endforeach; ?>

                    <?php endif; ?>


                </div>


                <div class="browser-info">


                    <?php if ($currentLevel !== 'lokasi'): ?>

                        <?php

                        $backUrl = 'struktur.php';

                        if ($currentLevel === 'area') {
                            $backUrl = 'struktur.php';
                        }

                        elseif ($currentLevel === 'jenis') {

                            $backUrl =
                                'struktur.php'
                                . '?lokasi=' . urlencode($currentLocation);
                        }

                        elseif ($currentLevel === 'mesin') {

                            $backUrl =
                                'struktur.php'
                                . '?lokasi=' . urlencode($currentLocation)
                                . '&id_area=' . $filter_area;
                        }

                        elseif ($currentLevel === 'sub') {

                            $backUrl =
                                'struktur.php'
                                . '?lokasi=' . urlencode($currentLocation)
                                . '&id_area=' . $filter_area
                                . '&id_jenis=' . $filter_jenis;
                        }

                        elseif ($currentLevel === 'komponen') {

                            $backUrl =
                                'struktur.php'
                                . '?lokasi=' . urlencode($currentLocation)
                                . '&id_area=' . $filter_area
                                . '&id_jenis=' . $filter_jenis
                                . '&id_mesin=' . $filter_mesin;
                        }

                        ?>

                        <a
                            href="<?= e($backUrl); ?>"
                            class="back-button"
                        >

                            <i class="bi bi-arrow-left"></i>

                            Kembali

                        </a>

                    <?php endif; ?>


                    <span>

                        <?php if ($currentLevel === 'lokasi'): ?>
                            Lokasi

                        <?php elseif ($currentLevel === 'area'): ?>
                            Area

                        <?php elseif ($currentLevel === 'jenis'): ?>
                            Jenis Mesin

                        <?php elseif ($currentLevel === 'mesin'): ?>
                            Mesin / Merk

                        <?php elseif ($currentLevel === 'sub'): ?>
                            Sub Mesin

                        <?php else: ?>
                            Komponen
                        <?php endif; ?>

                    </span>


                </div>


            </div>



            <!-- FOLDER CONTENT -->

            <div class="folder-content">


                <?php if ($currentLevel !== 'komponen'): ?>


                    <div class="folder-heading">


                        <div class="folder-heading-left">


                            <div class="folder-heading-icon">

                                <?php if ($currentLevel === 'lokasi'): ?>

                                    <i class="bi bi-folder2-open"></i>

                                <?php elseif ($currentLevel === 'area'): ?>

                                    <i class="bi bi-folder2-open"></i>

                                <?php elseif ($currentLevel === 'jenis'): ?>

                                    <i class="bi bi-folder2-open"></i>

                                <?php elseif ($currentLevel === 'mesin'): ?>

                                    <i class="bi bi-folder2-open"></i>

                                <?php else: ?>

                                    <i class="bi bi-folder2-open"></i>

                                <?php endif; ?>

                            </div>


                            <div>

                                <div class="folder-heading-title">

                                    <?php if ($currentLevel === 'lokasi'): ?>

                                        Folder Lokasi

                                    <?php elseif ($currentLevel === 'area'): ?>

                                        Folder Area

                                    <?php elseif ($currentLevel === 'jenis'): ?>

                                        Folder Jenis Mesin

                                    <?php elseif ($currentLevel === 'mesin'): ?>

                                        Folder Mesin / Merk

                                    <?php else: ?>

                                        Folder Sub Mesin

                                    <?php endif; ?>

                                </div>


                                <div class="folder-heading-sub">

                                    Pilih folder untuk melihat isi berikutnya.

                                </div>

                            </div>


                        </div>


                        <div class="folder-count">

                            <?= count($folders); ?> folder

                        </div>


                    </div>



                    <?php if (!empty($folders)): ?>


                        <div class="folder-grid">


                            <?php foreach ($folders as $folder): ?>


                                <?php

                                /* =================================================
                                   BUILD URL
                                ================================================== */

                                $folderUrl = 'struktur.php';

                                $folderIconClass = 'location';

                                $meta = '';

                                if ($currentLevel === 'lokasi') {

                                    $folderUrl =
                                        'struktur.php'
                                        . '?lokasi='
                                        . urlencode($folder['name']);

                                    $folderIconClass = 'location';

                                    $meta =
                                        count($folder['areas'])
                                        . ' area • '
                                        . count($folder['mesin'])
                                        . ' mesin';
                                }


                                elseif ($currentLevel === 'area') {

                                    $folderUrl =
                                        'struktur.php'
                                        . '?lokasi='
                                        . urlencode($currentLocation)
                                        . '&id_area='
                                        . $folder['id'];

                                    $folderIconClass = 'area';

                                    $meta =
                                        count($folder['jenis'])
                                        . ' jenis • '
                                        . count($folder['mesin'])
                                        . ' mesin';
                                }


                                elseif ($currentLevel === 'jenis') {

                                    $folderUrl =
                                        'struktur.php'
                                        . '?lokasi='
                                        . urlencode($currentLocation)
                                        . '&id_area='
                                        . $filter_area
                                        . '&id_jenis='
                                        . $folder['id'];

                                    $folderIconClass = 'jenis';

                                    $meta =
                                        count($folder['mesin'])
                                        . ' mesin • '
                                        . count($folder['sub'])
                                        . ' sub mesin';
                                }


                                elseif ($currentLevel === 'mesin') {

                                    $folderUrl =
                                        'struktur.php'
                                        . '?lokasi='
                                        . urlencode($currentLocation)
                                        . '&id_area='
                                        . $filter_area
                                        . '&id_jenis='
                                        . $filter_jenis
                                        . '&id_mesin='
                                        . $folder['id'];

                                    $folderIconClass = 'mesin';

                                    $serialText = '';

                                    if (!empty($folder['serial'])) {
                                        $serialText =
                                            'SN: '
                                            . $folder['serial']
                                            . ' • ';
                                    }

                                    $meta =
                                        $serialText
                                        . count($folder['sub'])
                                        . ' sub mesin';
                                }


                                elseif ($currentLevel === 'sub') {

                                    $folderUrl =
                                        'struktur.php'
                                        . '?lokasi='
                                        . urlencode($currentLocation)
                                        . '&id_area='
                                        . $filter_area
                                        . '&id_jenis='
                                        . $filter_jenis
                                        . '&id_mesin='
                                        . $filter_mesin
                                        . '&id_sub_mesin='
                                        . $folder['id'];

                                    $folderIconClass = 'sub';

                                    $serialText = '';

                                    if (!empty($folder['serial'])) {
                                        $serialText =
                                            'SN: '
                                            . $folder['serial']
                                            . ' • ';
                                    }

                                    $meta =
                                        $serialText
                                        . count($folder['komponen'])
                                        . ' komponen';
                                }

                                ?>


                                <a
                                    href="<?= e($folderUrl); ?>"
                                    class="folder-item"
                                    title="<?= e($folder['name']); ?>"
                                >


                                    <div class="folder-icon <?= e($folderIconClass); ?>">

                                        <i class="bi bi-folder-fill"></i>

                                    </div>


                                    <div class="folder-info">

                                        <div class="folder-name">

                                            <?= e($folder['name']); ?>

                                        </div>


                                        <div class="folder-meta">

                                            <?= e($meta); ?>

                                        </div>

                                    </div>


                                    <div class="folder-arrow">

                                        <i class="bi bi-chevron-right"></i>

                                    </div>


                                </a>


                            <?php endforeach; ?>


                        </div>


                    <?php else: ?>


                        <div class="empty-state">


                            <div class="empty-icon">

                                <i class="bi bi-folder-x"></i>

                            </div>


                            <div class="empty-title">

                                Folder tidak ditemukan

                            </div>


                            <div class="empty-text">

                                Tidak ada data yang sesuai dengan filter yang dipilih.

                            </div>


                        </div>


                    <?php endif; ?>


                <?php else: ?>


                    <!-- =================================================
                         COMPONENTS
                    ================================================== -->


                    <div class="folder-heading">


                        <div class="folder-heading-left">


                            <div class="folder-heading-icon">

                                <i class="bi bi-folder2-open"></i>

                            </div>


                            <div>

                                <div class="folder-heading-title">

                                    Komponen

                                </div>


                                <div class="folder-heading-sub">

                                    Komponen yang berada di dalam sub mesin ini.

                                </div>

                            </div>


                        </div>


                        <div class="folder-count">

                            <?= count($components); ?> komponen

                        </div>


                    </div>



                    <?php if (!empty($components)): ?>


                        <div class="component-grid">


                            <?php foreach ($components as $component): ?>


                                <?php

                                $kondisi = strtolower(
                                    trim($component['kondisi_komponen'] ?? '')
                                );


                                if ($kondisi === 'baik') {

                                    $conditionClass = 'condition-baik';

                                }

                                elseif (
                                    $kondisi === 'perlu pemeriksaan'
                                    || $kondisi === 'perlu diperiksa'
                                ) {

                                    $conditionClass = 'condition-periksa';

                                }

                                elseif (
                                    $kondisi === 'dalam perbaikan'
                                    || $kondisi === 'rusak'
                                ) {

                                    $conditionClass = 'condition-rusak';

                                }

                                else {

                                    $conditionClass = 'condition-default';
                                }

                                ?>


                                <div class="component-item">


                                    <div class="component-top">


                                        <div class="component-icon">

                                            <i class="bi bi-file-earmark-text"></i>

                                        </div>


                                        <div>

                                            <div class="component-name">

                                                <?= e(
                                                    $component['nama_komponen']
                                                    ?: 'Komponen'
                                                ); ?>

                                            </div>


                                            <div class="component-type">

                                                <?= e(
                                                    $component['jenis_komponen']
                                                    ?: 'Jenis komponen tidak tersedia'
                                                ); ?>

                                            </div>

                                        </div>


                                    </div>



                                    <div class="component-detail">


                                        <!-- BRAND -->

                                        <div class="component-detail-item">

                                            <div class="component-detail-label">
                                                Brand
                                            </div>

                                            <div class="component-detail-value">

                                                <?= e(
                                                    $component['brand_komponen']
                                                    ?: '-'
                                                ); ?>

                                            </div>

                                        </div>


                                        <!-- TIPE -->

                                        <div class="component-detail-item">

                                            <div class="component-detail-label">
                                                Tipe
                                            </div>

                                            <div class="component-detail-value">

                                                <?= e(
                                                    $component['tipe_komponen']
                                                    ?: '-'
                                                ); ?>

                                            </div>

                                        </div>


                                        <!-- SERIAL -->

                                        <div class="component-detail-item">

                                            <div class="component-detail-label">
                                                Serial Number
                                            </div>

                                            <div class="component-detail-value">

                                                <?= e(
                                                    $component['serial_komponen']
                                                    ?: '-'
                                                ); ?>

                                            </div>

                                        </div>


                                        <!-- KONDISI -->

                                        <div class="component-detail-item">

                                            <div class="component-detail-label">
                                                Kondisi
                                            </div>

                                            <div class="component-detail-value">

                                                <span
                                                    class="condition-badge <?= e($conditionClass); ?>"
                                                >

                                                    <?= e(
                                                        $component['kondisi_komponen']
                                                        ?: 'Tidak diketahui'
                                                    ); ?>

                                                </span>

                                            </div>

                                        </div>


                                    </div>


                                </div>


                            <?php endforeach; ?>


                        </div>


                    <?php else: ?>


                        <div class="empty-state">


                            <div class="empty-icon">

                                <i class="bi bi-file-earmark-x"></i>

                            </div>


                            <div class="empty-title">

                                Belum ada komponen

                            </div>


                            <div class="empty-text">

                                Sub mesin ini belum memiliki data komponen.

                            </div>


                        </div>


                    <?php endif; ?>


                <?php endif; ?>


            </div>


        </div>


    </main>


</div>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

document.addEventListener('DOMContentLoaded', function () {


    /* =====================================================
       MOBILE SIDEBAR
    ====================================================== */

    const sidebar =
        document.getElementById('sidebar');

    const mobileToggle =
        document.getElementById('mobileToggle');

    const sidebarOverlay =
        document.getElementById('sidebarOverlay');


    if (mobileToggle) {

        mobileToggle.addEventListener(
            'click',
            function () {

                sidebar.classList.add('show');

                sidebarOverlay.classList.add('show');

            }
        );
    }


    if (sidebarOverlay) {

        sidebarOverlay.addEventListener(
            'click',
            function () {

                sidebar.classList.remove('show');

                sidebarOverlay.classList.remove('show');

            }
        );
    }


    /* =====================================================
       FILTER CASCADING
    ====================================================== */

    const lokasiSelect =
        document.getElementById('filterLokasi');

    const areaSelect =
        document.getElementById('filterArea');

    const jenisSelect =
        document.getElementById('filterJenis');

    const mesinSelect =
        document.getElementById('filterMesin');


    /* =====================================================
       UPDATE AREA
    ====================================================== */

    function updateArea() {

        if (!lokasiSelect || !areaSelect) {
            return;
        }


        const lokasi =
            lokasiSelect.value;


        Array.from(areaSelect.options).forEach(
            function (option, index) {

                if (index === 0) {
                    option.hidden = false;
                    return;
                }


                const optionLokasi =
                    option.getAttribute('data-lokasi');


                if (
                    lokasi === ''
                    || optionLokasi === lokasi
                ) {

                    option.hidden = false;

                } else {

                    option.hidden = true;
                }

            }
        );


        const selected =
            areaSelect.options[
                areaSelect.selectedIndex
            ];


        if (
            selected
            && selected.hidden
        ) {

            areaSelect.value = '0';
        }

    }


    /* =====================================================
       UPDATE JENIS
    ====================================================== */

    function updateJenis() {

        if (!jenisSelect || !areaSelect) {
            return;
        }


        const area =
            areaSelect.value;


        Array.from(jenisSelect.options).forEach(
            function (option, index) {

                if (index === 0) {
                    option.hidden = false;
                    return;
                }


                const optionArea =
                    option.getAttribute('data-area');


                if (
                    area === '0'
                    || optionArea === area
                ) {

                    option.hidden = false;

                } else {

                    option.hidden = true;
                }

            }
        );


        const selected =
            jenisSelect.options[
                jenisSelect.selectedIndex
            ];


        if (
            selected
            && selected.hidden
        ) {

            jenisSelect.value = '0';
        }

    }


    /* =====================================================
       UPDATE MESIN
    ====================================================== */

    function updateMesin() {

        if (!mesinSelect || !jenisSelect) {
            return;
        }


        const jenis =
            jenisSelect.value;


        Array.from(mesinSelect.options).forEach(
            function (option, index) {

                if (index === 0) {
                    option.hidden = false;
                    return;
                }


                const optionJenis =
                    option.getAttribute('data-jenis');


                if (
                    jenis === '0'
                    || optionJenis === jenis
                ) {

                    option.hidden = false;

                } else {

                    option.hidden = true;
                }

            }
        );


        const selected =
            mesinSelect.options[
                mesinSelect.selectedIndex
            ];


        if (
            selected
            && selected.hidden
        ) {

            mesinSelect.value = '0';
        }

    }


    /* =====================================================
       EVENTS
    ====================================================== */

    if (lokasiSelect) {

        lokasiSelect.addEventListener(
            'change',
            function () {

                updateArea();

                updateJenis();

                updateMesin();

            }
        );
    }


    if (areaSelect) {

        areaSelect.addEventListener(
            'change',
            function () {

                updateJenis();

                updateMesin();

            }
        );
    }


    if (jenisSelect) {

        jenisSelect.addEventListener(
            'change',
            function () {

                updateMesin();

            }
        );
    }


    /* =====================================================
       INITIALIZE
    ====================================================== */

    updateArea();

    updateJenis();

    updateMesin();


});

</script>


</body>

</html>