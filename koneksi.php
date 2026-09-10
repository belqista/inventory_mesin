<?php
/**
 * File Koneksi Database
 * Inventory Maintenance System
 * LOCAL XAMPP
 */

$host     = "localhost";
$username = "root";
$password = "";
$database = "inventory_mesin";


// =========================================================
// MEMBUAT KONEKSI
// =========================================================

$conn = mysqli_connect(
    $host,
    $username,
    $password,
    $database
);


// =========================================================
// CEK KONEKSI
// =========================================================

if (!$conn) {

    die(
        "Koneksi database gagal: " .
        mysqli_connect_error()
    );

}


// =========================================================
// ALIAS
// =========================================================

$koneksi = $conn;


// =========================================================
// TIMEZONE
// =========================================================

date_default_timezone_set('Asia/Jakarta');


// =========================================================
// SET CHARSET
// =========================================================

mysqli_set_charset($conn, "utf8mb4");

?>