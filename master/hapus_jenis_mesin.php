<?php
require_once __DIR__ . '/../template/auth.php';
require_admin_role();


/*
|--------------------------------------------------------------------------
| HAPUS JENIS MESIN
|--------------------------------------------------------------------------
| File : jenis_mesin/hapus_jenis_mesin.php
|--------------------------------------------------------------------------
*/

mysqli_report(MYSQLI_REPORT_OFF);

require_once "../koneksi.php";


/* =========================================================
   CEK KONEKSI
========================================================= */

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    die("Koneksi database tidak tersedia.");
}


/* =========================================================
   AMBIL ID
========================================================= */

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;


/* =========================================================
   VALIDASI ID
========================================================= */

if ($id <= 0) {
    header("Location: jenis_mesin.php?status=error&msg=" . urlencode(
        "ID Jenis Mesin tidak valid."
    ));
    exit;
}


/* =========================================================
   CEK DATA JENIS MESIN
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT
        id,
        nama_jenis_mesin
    FROM jenis_mesin
    WHERE id = ?
    LIMIT 1
    "
);

if (!$stmt) {
    header("Location: jenis_mesin.php?status=error&msg=" . urlencode(
        "Gagal menyiapkan pemeriksaan data: " . mysqli_error($conn)
    ));
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

if (!mysqli_stmt_execute($stmt)) {

    $error = mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);

    header("Location: jenis_mesin.php?status=error&msg=" . urlencode(
        "Gagal memeriksa data: " . $error
    ));

    exit;
}

mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) < 1) {

    mysqli_stmt_close($stmt);

    header("Location: jenis_mesin.php?status=error&msg=" . urlencode(
        "Data Jenis Mesin tidak ditemukan."
    ));

    exit;
}

mysqli_stmt_bind_result(
    $stmt,
    $db_id,
    $namaJenisMesin
);

mysqli_stmt_fetch($stmt);

mysqli_stmt_close($stmt);


/* =========================================================
   CEK APAKAH MASIH DIPAKAI MESIN
========================================================= */

$stmtCheckMesin = mysqli_prepare(
    $conn,
    "
    SELECT COUNT(*)
    FROM mesin
    WHERE id_jenis_mesin = ?
    "
);

if (!$stmtCheckMesin) {

    header("Location: jenis_mesin.php?status=error&msg=" . urlencode(
        "Gagal memeriksa penggunaan Jenis Mesin: " .
        mysqli_error($conn)
    ));

    exit;
}

mysqli_stmt_bind_param(
    $stmtCheckMesin,
    "i",
    $id
);

if (!mysqli_stmt_execute($stmtCheckMesin)) {

    $error = mysqli_stmt_error($stmtCheckMesin);

    mysqli_stmt_close($stmtCheckMesin);

    header("Location: jenis_mesin.php?status=error&msg=" . urlencode(
        "Gagal memeriksa mesin terkait: " . $error
    ));

    exit;
}

mysqli_stmt_bind_result(
    $stmtCheckMesin,
    $jumlahMesin
);

mysqli_stmt_fetch($stmtCheckMesin);

mysqli_stmt_close($stmtCheckMesin);

$jumlahMesin = (int)$jumlahMesin;


/* =========================================================
   JIKA MASIH DIPAKAI
========================================================= */

if ($jumlahMesin > 0) {

    header("Location: jenis_mesin.php?status=error&msg=" . urlencode(
        "Jenis Mesin \"" .
        $namaJenisMesin .
        "\" tidak dapat dihapus karena masih digunakan oleh " .
        $jumlahMesin .
        " mesin. Hapus atau pindahkan data mesin terlebih dahulu."
    ));

    exit;
}


/* =========================================================
   HAPUS DATA
========================================================= */

$stmtDelete = mysqli_prepare(
    $conn,
    "
    DELETE FROM jenis_mesin
    WHERE id = ?
    LIMIT 1
    "
);

if (!$stmtDelete) {

    header("Location: jenis_mesin.php?status=error&msg=" . urlencode(
        "Gagal menyiapkan proses hapus: " .
        mysqli_error($conn)
    ));

    exit;
}

mysqli_stmt_bind_param(
    $stmtDelete,
    "i",
    $id
);


/* =========================================================
   EXECUTE DELETE
========================================================= */

if (!mysqli_stmt_execute($stmtDelete)) {

    $errorNumber = mysqli_stmt_errno($stmtDelete);
    $errorMessage = mysqli_stmt_error($stmtDelete);

    mysqli_stmt_close($stmtDelete);

    header("Location: jenis_mesin.php?status=error&msg=" . urlencode(
        "GAGAL MENGHAPUS JENIS MESIN [MYSQL " .
        $errorNumber .
        "]: " .
        $errorMessage
    ));

    exit;
}


/* =========================================================
   CEK BENAR-BENAR TERHAPUS
========================================================= */

$affectedRows = mysqli_stmt_affected_rows(
    $stmtDelete
);

mysqli_stmt_close($stmtDelete);


/* =========================================================
   HASIL
========================================================= */

if ($affectedRows > 0) {

    header("Location: jenis_mesin.php?status=success&msg=" . urlencode(
        "Jenis Mesin \"" .
        $namaJenisMesin .
        "\" berhasil dihapus."
    ));

    exit;
}


header("Location: jenis_mesin.php?status=error&msg=" . urlencode(
    "Data Jenis Mesin gagal dihapus atau sudah tidak tersedia."
));

exit;