<?php
// =========================================================
// LOGOUT
// Inventory Maintenance System
// =========================================================

session_start();


// =========================================================
// HAPUS SEMUA SESSION
// =========================================================

$_SESSION = [];


// =========================================================
// HAPUS COOKIE SESSION
// =========================================================

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}


// =========================================================
// HANCURKAN SESSION
// =========================================================

session_destroy();


// =========================================================
// KEMBALI KE LOGIN
// =========================================================

header("Location: /inventory_mesin/login.php");

exit;