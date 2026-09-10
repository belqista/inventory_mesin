<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

include "../koneksi.php";

$lokasi = isset($_GET['lokasi']) ? trim($_GET['lokasi']) : '';

echo '<option value="">-- Pilih Area --</option>';

if (!empty($lokasi)) {
    $stmt = mysqli_prepare($conn, "SELECT id, nama_area FROM area_bagian WHERE lokasi = ? ORDER BY nama_area ASC");
    mysqli_stmt_bind_param($stmt, "s", $lokasi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nama_area']) . '</option>';
    }
    mysqli_stmt_close($stmt);
}
?>