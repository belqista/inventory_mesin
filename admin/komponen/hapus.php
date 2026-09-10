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
   AMBIL ID KOMPONEN
========================================================= */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {

    header("Location: index.php?error=id_tidak_valid");
    exit;

}


/* =========================================================
   AMBIL DATA KOMPONEN
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        jenis_komponen,
        nama_bagian,
        serial_number,
        gambar
     FROM komponen
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {

    header("Location: index.php?error=query_gagal");
    exit;

}

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$komponen = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   KOMPONEN TIDAK DITEMUKAN
========================================================= */

if (!$komponen) {

    header("Location: index.php?error=komponen_tidak_ditemukan");
    exit;

}


/* =========================================================
   CEK RIWAYAT MAINTENANCE
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS jumlah
     FROM riwayat_maintenance
     WHERE id_komponen = ?"
);

if (!$stmt) {

    header("Location: index.php?error=query_gagal");
    exit;

}

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$resultMaintenance = mysqli_stmt_get_result($stmt);

$dataMaintenance = mysqli_fetch_assoc($resultMaintenance);

mysqli_stmt_close($stmt);

$jumlahMaintenance = (int)($dataMaintenance['jumlah'] ?? 0);


/* =========================================================
   JIKA MASIH MEMILIKI HISTORY MAINTENANCE
========================================================= */

if ($jumlahMaintenance > 0) {

    header(
        "Location: index.php?error=masih_digunakan&jumlah=" .
        $jumlahMaintenance
    );

    exit;
}


/* =========================================================
   SIMPAN NAMA GAMBAR
========================================================= */

$gambar = trim((string)($komponen['gambar'] ?? ''));


/* =========================================================
   HAPUS KOMPONEN
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM komponen
     WHERE id = ?"
);

if (!$stmt) {

    header("Location: index.php?error=query_gagal");
    exit;

}

mysqli_stmt_bind_param($stmt, "i", $id);

$berhasil = mysqli_stmt_execute($stmt);

$errorDelete = mysqli_stmt_error($stmt);

mysqli_stmt_close($stmt);


/* =========================================================
   JIKA DELETE GAGAL
========================================================= */

if (!$berhasil) {

    header(
        "Location: index.php?error=hapus_gagal"
    );

    exit;
}


/* =========================================================
   HAPUS FILE GAMBAR
========================================================= */

if ($gambar !== '') {

    $gambarPath =
        __DIR__ .
        '/../../uploads/komponen/' .
        basename($gambar);

    if (file_exists($gambarPath)) {

        @unlink($gambarPath);

    }
}


/* =========================================================
   BERHASIL
========================================================= */

header("Location: index.php?success=hapus");

exit;