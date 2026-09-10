<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

include "../koneksi.php";

$id_area = isset($_GET['id_area']) ? intval($_GET['id_area']) : 0;

echo '<option value="">-- Pilih Lokasi --</option>';

if ($id_area > 0) {
    // Sesuaikan query jika lokasi berupa kolom di tabel area_bagian atau tabel terpisah
    $query = mysqli_query($conn, "SELECT lokasi FROM area_bagian WHERE id = $id_area");
    while ($row = mysqli_fetch_assoc($query)) {
        if (!empty($row['lokasi'])) {
            // Jika lokasi bisa berupa banyak baris atau koma-komaan, sesuaikan. 
            // Berikut diasumsikan kolom lokasi menghasilkan nilai/teks lokasi yang bisa dipilih.
            echo '<option value="' . htmlspecialchars($row['lokasi']) . '">' . htmlspecialchars($row['lokasi']) . '</option>';
        }
    }
}
?>