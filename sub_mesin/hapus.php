<?php
require_once __DIR__ . '/../template/auth.php';
require_admin_role();

include "../koneksi.php";

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if (!empty($id)) {
    mysqli_query($conn, "DELETE FROM komponen WHERE id_sub_mesin='$id'");
    mysqli_query($conn, "DELETE FROM sub_mesin WHERE id='$id'");
}

header("Location: index.php");
exit;
?>