<?php
/* =========================================================
   AUTH & ROLE HELPER
   Inventory Maintenance System
   ========================================================= */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Pastikan pengguna sudah login.
 */
function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /inventory_mesin/login.php');
        exit;
    }
}

/**
 * Role pengguna yang sedang login.
 */
function current_role(): string
{
    return strtolower(trim((string)($_SESSION['role'] ?? '')));
}

/**
 * Cek apakah role termasuk daftar yang diizinkan.
 */
function has_role(...$roles): bool
{
    $role = current_role();
    foreach ($roles as $allowed) {
        if ($role === strtolower(trim((string)$allowed))) {
            return true;
        }
    }
    return false;
}

/**
 * Halaman Teknik/User: hanya user operasional.
 */
function require_user_role(): void
{
    require_login();

    if (!has_role('user')) {
        header('Location: /inventory_mesin/admin/index.php');
        exit;
    }
}

/**
 * Halaman yang hanya boleh diakses admin.
 */
function require_admin_role(): void
{
    require_login();

    if (!has_role('admin')) {
        header('Location: /inventory_mesin/dashboard/index.php');
        exit;
    }
}

/**
 * Halaman baca yang boleh diakses Admin maupun Teknik/User.
 */
function require_authenticated(): void
{
    require_login();
}
