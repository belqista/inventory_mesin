<?php
include "../koneksi.php";

$id_jenis_mesin = isset($_GET['id_jenis_mesin']) ? intval($_GET['id_jenis_mesin']) : 0;

echo '<option value="">-- Pilih Mesin --</option>';

if ($id_jenis_mesin > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM mesin WHERE id_jenis_mesin = ? ORDER BY nama_mesin ASC");
    mysqli_stmt_bind_param($stmt, "i", $id_jenis_mesin);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nama_mesin']) . ' (' . htmlspecialchars($row['serial_number']) . ')</option>';
    }
    mysqli_stmt_close($stmt);
}
?>