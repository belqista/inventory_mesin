<?php
require_once __DIR__ . '/../template/auth.php';
require_admin_role();

include "../koneksi.php";

// Cek apakah ada parameter id di URL
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Query untuk menghapus data berdasarkan tabel area_bagian
    $query = "DELETE FROM area_bagian WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil terhapus, kembalikan ke halaman area.php
        header("Location: area.php?pesan=berhasil_hapus");
        exit();
    } else {
        // Jika gagal
        echo "Gagal menghapus data: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
} else {
    // Jika tidak ada id, kembalikan ke halaman area.php
    header("Location: area.php");
    exit();
}
?>