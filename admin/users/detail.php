<?php

session_start();

require_once "../../koneksi.php";

if (!isset($_SESSION['user_id'])) {

    header("Location: ../../login.php");
    exit;

}

$current_id = (int) $_SESSION['user_id'];

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT id, username, nama_lengkap, role, status
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


$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {

    header("Location: index.php");
    exit;

}


$stmt = mysqli_prepare(
    $conn,
    "
    SELECT
        id,
        username,
        nama_lengkap,
        role,
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


function e($value)
{

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );

}

?>
<!DOCTYPE html>

<html lang="id">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
Detail User - Inventory Maintenance
</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    rel="stylesheet"
>

<link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
>

<style>

body {
    margin:0;
    background:#f5f8fc;
    font-family:'Poppins',sans-serif;
    color:#172033;
}

.page {
    min-height:100vh;
    padding:35px 20px;
}

.container-custom {
    max-width:800px;
    margin:auto;
}

.card-custom {
    background:#fff;
    border:1px solid #e6ebf2;
    border-radius:16px;
    overflow:hidden;
}

.header {
    background:#075eaa;
    color:white;
    padding:25px;
    display:flex;
    align-items:center;
    gap:15px;
}

.avatar {
    width:55px;
    height:55px;
    border-radius:50%;
    background:rgba(255,255,255,.15);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
}

.header h1 {
    font-size:19px;
    margin:0;
    font-weight:700;
}

.header p {
    margin:4px 0 0;
    font-size:10px;
    opacity:.9;
}

.body {
    padding:25px;
}

.info-row {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:14px 0;
    border-bottom:1px solid #eef1f5;
    gap:20px;
}

.info-row:last-child {
    border-bottom:0;
}

.label {
    font-size:10px;
    color:#7a8495;
}

.value {
    font-size:11px;
    font-weight:600;
    text-align:right;
}

.badge-custom {
    display:inline-flex;
    align-items:center;
    gap:4px;
    padding:5px 9px;
    border-radius:20px;
    font-size:9px;
    font-weight:600;
}

.admin {
    background:#f1edff;
    color:#6d4bc1;
}

.user {
    background:#eaf4ff;
    color:#075eaa;
}

.active {
    background:#e9f8f1;
    color:#11875a;
}

.inactive {
    background:#fff0f0;
    color:#c7353b;
}

.btn-back {
    background:#f0f2f5;
    color:#596579;
    border:0;
    border-radius:8px;
    padding:10px 17px;
    font-size:11px;
    text-decoration:none;
    font-weight:600;
}

.btn-edit {
    background:#075eaa;
    color:#fff;
    border-radius:8px;
    padding:10px 17px;
    font-size:11px;
    text-decoration:none;
    font-weight:600;
}

.btn-edit:hover {
    background:#064d8c;
    color:white;
}

</style>

</head>

<body>

<div class="page">

<div class="container-custom">


<div class="mb-3">

<a
    href="index.php"
    class="btn-back"
>

<i class="bi bi-arrow-left"></i>

Kembali

</a>

</div>


<div class="card-custom">


<div class="header">

<div class="avatar">

<i class="bi bi-person-fill"></i>

</div>


<div>

<h1>
Detail Pengguna
</h1>

<p>
Informasi akun pengguna sistem
</p>

</div>

</div>


<div class="body">


<div class="info-row">

<div class="label">
ID User
</div>

<div class="value">
#<?= e($user['id']) ?>
</div>

</div>


<div class="info-row">

<div class="label">
Username
</div>

<div class="value">
<?= e($user['username']) ?>
</div>

</div>


<div class="info-row">

<div class="label">
Nama Lengkap
</div>

<div class="value">
<?= e($user['nama_lengkap'] ?: '-') ?>
</div>

</div>


<div class="info-row">

<div class="label">
Role
</div>

<div class="value">

<?php if (
    strtolower($user['role']) === 'admin'
): ?>

<span class="badge-custom admin">

<i class="bi bi-shield-fill-check"></i>

Admin

</span>

<?php else: ?>

<span class="badge-custom user">

<i class="bi bi-person-fill"></i>

User

</span>

<?php endif; ?>

</div>

</div>


<div class="info-row">

<div class="label">
Status Akun
</div>

<div class="value">

<?php if (
    strtolower($user['status']) === 'aktif'
): ?>

<span class="badge-custom active">

<i class="bi bi-check-circle-fill"></i>

Aktif

</span>

<?php else: ?>

<span class="badge-custom inactive">

<i class="bi bi-x-circle-fill"></i>

Nonaktif

</span>

<?php endif; ?>

</div>

</div>


<div class="d-flex justify-content-end mt-4">

<a
    href="edit.php?id=<?= (int) $user['id'] ?>"
    class="btn-edit"
>

<i class="bi bi-pencil-fill me-1"></i>

Edit User

</a>

</div>


</div>

</div>

</div>

</div>

</body>

</html>