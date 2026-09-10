<?php
require_once __DIR__ . '/../template/auth.php';
require_admin_role();

include "../koneksi.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Ambil data gambar lama untuk dihapus dari folder jika ada
    $q_cek = mysqli_query($conn, "SELECT gambar FROM mesin WHERE id = $id");
    if ($q_cek && mysqli_num_rows($q_cek) > 0) {
        $data = mysqli_fetch_assoc($q_cek);
        if (!empty($data['gambar'])) {
            $filePath = "../uploads/mesin/" . $data['gambar'];
            if (file_exists($filePath)) {
                unlink($filePath); // Hapus file fisik gambar
            }
        }
    }

    // Eksekusi hapus data dari database
    $query = "DELETE FROM mesin WHERE id = $id";
    if (mysqli_query($conn, $query)) {
        header("Location: index.php");
        exit;
    } else {
        echo "Gagal menghapus data: " . mysqli_error($conn);
    }
} else {
    header("Location: index.php");
    exit;
}
?>