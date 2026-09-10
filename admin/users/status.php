<?php

session_start();

require_once "../../koneksi.php";


/* =========================================================
   PROTEKSI LOGIN
========================================================= */

if (!isset($_SESSION['user_id'])) {

    header("Location: ../../login.php");
    exit;

}


$current_id = (int) $_SESSION['user_id'];


/* =========================================================
   CEK ADMIN
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT id, role
    FROM users
    WHERE id = ?
    LIMIT 1
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $current_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$current_user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (
    !$current_user ||
    strtolower($current_user['role']) !== 'admin'
) {

    header("Location: ../../dashboard/index.php");
    exit;

}


/* =========================================================
   ID TARGET
========================================================= */

$id = (int) ($_GET['id'] ?? 0);


/* =========================================================
   CEGAH ADMIN MENONAKTIFKAN DIRI SENDIRI
========================================================= */

if ($id <= 0 || $id === $current_id) {

    $_SESSION['user_error'] =
        "Akun yang sedang digunakan tidak dapat diubah statusnya.";

    header("Location: index.php");
    exit;

}


/* =========================================================
   AMBIL STATUS SAAT INI
========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT
        id,
        username,
        status
    FROM users
    WHERE id = ?
    LIMIT 1
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$user) {

    $_SESSION['user_error'] =
        "User tidak ditemukan.";

    header("Location: index.php");
    exit;

}


/* =========================================================
   TOGGLE STATUS
========================================================= */

$status_lama =
    strtolower(
        trim(
            $user['status'] ?? ''
        )
    );


if ($status_lama === 'aktif') {

    $status_baru = 'Nonaktif';

} else {

    $status_baru = 'Aktif';

}


$stmt = mysqli_prepare(
    $conn,
    "
    UPDATE users
    SET status = ?
    WHERE id = ?
    "
);

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $status_baru,
    $id
);


if (mysqli_stmt_execute($stmt)) {

    $_SESSION['user_success'] =
        "Status user "
        . $user['username']
        . " berhasil diubah menjadi "
        . $status_baru
        . ".";

} else {

    $_SESSION['user_error'] =
        "Status user gagal diubah.";

}


mysqli_stmt_close($stmt);

header("Location: index.php");
exit;