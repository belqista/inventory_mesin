<?php

session_start();

require_once "../../koneksi.php";

date_default_timezone_set('Asia/Jakarta');


/* =========================================================
   PROTEKSI
========================================================= */

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


/* =========================================================
   ID USER
========================================================= */

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {

    header("Location: index.php");
    exit;

}


/* =========================================================
   AMBIL USER
========================================================= */

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


/* =========================================================
   PROCESS
========================================================= */

$error = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username =
        trim($_POST['username'] ?? '');

    $nama =
        trim($_POST['nama_lengkap'] ?? '');

    $role =
        trim($_POST['role'] ?? 'user');

    $status =
        trim($_POST['status'] ?? 'Aktif');

    $password =
        $_POST['password'] ?? '';

    $password_confirmation =
        $_POST['password_confirmation'] ?? '';


    /* VALIDASI */

    if (
        $username === '' ||
        $nama === ''
    ) {

        $error =
            "Username dan nama lengkap wajib diisi.";

    } elseif (
        strlen($username) < 3
    ) {

        $error =
            "Username minimal 3 karakter.";

    } elseif (
        !in_array(
            $role,
            ['admin', 'user'],
            true
        )
    ) {

        $error =
            "Role tidak valid.";

    } elseif (
        !in_array(
            $status,
            ['Aktif', 'Nonaktif'],
            true
        )
    ) {

        $error =
            "Status tidak valid.";

    } elseif (
        $id === $current_id &&
        $status === 'Nonaktif'
    ) {

        $error =
            "Anda tidak dapat menonaktifkan akun sendiri.";

    } elseif (
        $password !== '' &&
        strlen($password) < 6
    ) {

        $error =
            "Password baru minimal 6 karakter.";

    } elseif (
        $password !== '' &&
        $password !== $password_confirmation
    ) {

        $error =
            "Konfirmasi password tidak sama.";

    } else {


        /* CEK USERNAME */

        $check = mysqli_prepare(
            $conn,
            "
            SELECT id
            FROM users
            WHERE username = ?
              AND id <> ?
            LIMIT 1
            "
        );

        mysqli_stmt_bind_param(
            $check,
            "si",
            $username,
            $id
        );

        mysqli_stmt_execute($check);

        $check_result =
            mysqli_stmt_get_result($check);

        $duplicate =
            mysqli_fetch_assoc($check_result);

        mysqli_stmt_close($check);


        if ($duplicate) {

            $error =
                "Username tersebut sudah digunakan.";

        } else {


            /* UPDATE TANPA PASSWORD */

            if ($password === '') {

                $update = mysqli_prepare(
                    $conn,
                    "
                    UPDATE users
                    SET
                        username = ?,
                        nama_lengkap = ?,
                        role = ?,
                        status = ?
                    WHERE id = ?
                    "
                );

                mysqli_stmt_bind_param(
                    $update,
                    "ssssi",
                    $username,
                    $nama,
                    $role,
                    $status,
                    $id
                );

            } else {


                $password_hash =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                $update = mysqli_prepare(
                    $conn,
                    "
                    UPDATE users
                    SET
                        username = ?,
                        nama_lengkap = ?,
                        role = ?,
                        status = ?,
                        password = ?
                    WHERE id = ?
                    "
                );

                mysqli_stmt_bind_param(
                    $update,
                    "sssssi",
                    $username,
                    $nama,
                    $role,
                    $status,
                    $password_hash,
                    $id
                );

            }


            if (
                $update &&
                mysqli_stmt_execute($update)
            ) {

                mysqli_stmt_close($update);

                $_SESSION['user_success'] =
                    "Data user berhasil diperbarui.";

                header("Location: index.php");

                exit;

            } else {

                $error =
                    "Data user gagal diperbarui.";

                if ($update) {

                    mysqli_stmt_close($update);

                }

            }

        }

    }


    /* UPDATE DATA TAMPILAN */

    $user['username'] =
        $username;

    $user['nama_lengkap'] =
        $nama;

    $user['role'] =
        $role;

    $user['status'] =
        $status;

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
Edit User - Inventory Maintenance
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
    max-width:850px;
    margin:auto;
}

.card-custom {
    background:white;
    border:1px solid #e6ebf2;
    border-radius:16px;
    overflow:hidden;
}

.header {
    background:#075eaa;
    color:white;
    padding:22px 25px;
}

.header h1 {
    margin:0;
    font-size:19px;
    font-weight:700;
}

.header p {
    margin:5px 0 0;
    font-size:10px;
    opacity:.9;
}

.body {
    padding:25px;
}

.form-label {
    font-size:11px;
    font-weight:600;
}

.form-control,
.form-select {
    font-family:'Poppins',sans-serif;
    font-size:11px;
    border-radius:8px;
    padding:10px 12px;
}

.btn-main {
    background:#075eaa;
    color:white;
    border:0;
    border-radius:8px;
    padding:10px 18px;
    font-size:11px;
    font-weight:600;
}

.btn-main:hover {
    background:#064d8c;
    color:white;
}

.btn-back {
    background:#f0f2f5;
    color:#596579;
    border:0;
    border-radius:8px;
    padding:10px 18px;
    font-size:11px;
    text-decoration:none;
    font-weight:600;
}

.password-info {
    font-size:9px;
    color:#7a8495;
    margin-top:5px;
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

<h1>

<i class="bi bi-pencil-square me-2"></i>

Edit User

</h1>

<p>
Perbarui data akun pengguna
</p>

</div>


<div class="body">


<?php if ($error): ?>

<div
    class="alert alert-danger"
    style="font-size:11px;"
>

<i class="bi bi-exclamation-circle me-1"></i>

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>


<form
    method="POST"
    autocomplete="off"
>


<input
    type="hidden"
    name="id"
    value="<?= (int) $user['id'] ?>"
>


<div class="row g-3">


<div class="col-md-6">

<label class="form-label">
Username *
</label>

<input
    type="text"
    name="username"
    class="form-control"
    value="<?= htmlspecialchars($user['username']) ?>"
    required
>

</div>


<div class="col-md-6">

<label class="form-label">
Nama Lengkap *
</label>

<input
    type="text"
    name="nama_lengkap"
    class="form-control"
    value="<?= htmlspecialchars($user['nama_lengkap']) ?>"
    required
>

</div>


<div class="col-md-6">

<label class="form-label">
Password Baru
</label>

<input
    type="password"
    name="password"
    class="form-control"
    placeholder="Kosongkan jika tidak diubah"
>

<div class="password-info">
Kosongkan apabila password tetap.
</div>

</div>


<div class="col-md-6">

<label class="form-label">
Konfirmasi Password
</label>

<input
    type="password"
    name="password_confirmation"
    class="form-control"
    placeholder="Ulangi password baru"
>

</div>


<div class="col-md-6">

<label class="form-label">
Role *
</label>

<select
    name="role"
    class="form-select"
    required
>

<option
    value="user"
    <?= $user['role'] === 'user'
        ? 'selected'
        : '' ?>
>
User
</option>

<option
    value="admin"
    <?= $user['role'] === 'admin'
        ? 'selected'
        : '' ?>
>
Admin
</option>

</select>

</div>


<div class="col-md-6">

<label class="form-label">
Status *
</label>

<select
    name="status"
    class="form-select"
    <?= $id === $current_id
        ? 'disabled'
        : '' ?>
>

<option
    value="Aktif"
    <?= $user['status'] === 'Aktif'
        ? 'selected'
        : '' ?>
>
Aktif
</option>

<option
    value="Nonaktif"
    <?= $user['status'] === 'Nonaktif'
        ? 'selected'
        : '' ?>
>
Nonaktif
</option>

</select>


<?php if ($id === $current_id): ?>

<input
    type="hidden"
    name="status"
    value="Aktif"
>

<div class="password-info">
Akun admin yang sedang digunakan tidak dapat dinonaktifkan.
</div>

<?php endif; ?>

</div>


</div>


<div class="d-flex justify-content-end gap-2 mt-4">

<a
    href="index.php"
    class="btn-back"
>
Batal
</a>

<button
    type="submit"
    class="btn-main"
>
<i class="bi bi-save me-1"></i>
Simpan Perubahan
</button>

</div>


</form>


</div>

</div>

</div>

</div>

</body>

</html>