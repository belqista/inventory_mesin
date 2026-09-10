<?php
session_start();
require_once "../../koneksi.php";

date_default_timezone_set('Asia/Jakarta');

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

/* =========================================================
   PARAMETER
========================================================= */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header(
        "Location: index.php?error=" .
        urlencode("ID maintenance tidak valid.")
    );
    exit;
}

/* =========================================================
   CEK DATA MAINTENANCE
========================================================= */
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, foto
     FROM riwayat_maintenance
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {
    header(
        "Location: index.php?error=" .
        urlencode("Gagal menyiapkan proses hapus.")
    );
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data) {
    header(
        "Location: index.php?error=" .
        urlencode("Data maintenance tidak ditemukan.")
    );
    exit;
}

/* =========================================================
   SIMPAN NAMA FOTO SEBELUM DATA DIHAPUS
========================================================= */
$foto = trim((string)($data['foto'] ?? ''));

/* =========================================================
   HAPUS DATA MAINTENANCE
========================================================= */
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM riwayat_maintenance
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {
    header(
        "Location: index.php?error=" .
        urlencode("Gagal menyiapkan penghapusan data.")
    );
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id);

$berhasil = mysqli_stmt_execute($stmt);

$errorDb = mysqli_error($conn);

mysqli_stmt_close($stmt);

/* =========================================================
   CEK HASIL DELETE
========================================================= */
if (!$berhasil) {
    header(
        "Location: index.php?error=" .
        urlencode("Data maintenance gagal dihapus: " . $errorDb)
    );
    exit;
}

/* =========================================================
   HAPUS FOTO MAINTENANCE
   Foto komponen TIDAK disentuh.
========================================================= */

if ($foto !== '') {

    /*
     * Struktur:
     * inventory_mesin/
     * ├── uploads/
     * │   └── maintenance/
     * └── admin/
     *     └── maintenance/
     *         └── hapus.php
     *
     * Dari admin/maintenance/hapus.php
     * menuju uploads/maintenance = ../../uploads/maintenance/
     */
    $fotoPath = __DIR__ . "/../../uploads/maintenance/" . basename($foto);

    if (is_file($fotoPath)) {
        @unlink($fotoPath);
    }
}

/* =========================================================
   KEMBALI KE INDEX
========================================================= */
header(
    "Location: index.php?success=" .
    urlencode("Riwayat maintenance berhasil dihapus.")
);
exit;
?>