<?php
require_once __DIR__ . '/../template/auth.php';
require_admin_role();

include "../koneksi.php";

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if (!empty($id)) {
    // Menghapus data riwayat maintenance berdasarkan ID
    $query = "DELETE FROM riwayat_maintenance WHERE id = '$id'";
    mysqli_query($conn, $query);
}

// Redirect kembali ke halaman utama maintenance
header("Location: index.php");
exit;
?>