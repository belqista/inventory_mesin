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
   PROCESS TAMBAH
========================================================= */

$error = '';

$old_username = '';
$old_nama = '';
$old_role = 'user';
$old_status = 'Aktif';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $old_username = trim($_POST['username'] ?? '');
    $old_nama     = trim($_POST['nama_lengkap'] ?? '');
    $old_role     = trim($_POST['role'] ?? 'user');
    $old_status   = trim($_POST['status'] ?? 'Aktif');

    $password = $_POST['password'] ?? '';
    $password_confirmation =
        $_POST['password_confirmation'] ?? '';


    /* VALIDASI */

    if (
        $old_username === '' ||
        $old_nama === '' ||
        $password === ''
    ) {

        $error =
            "Username, nama lengkap, dan password wajib diisi.";

    } elseif (
        strlen($old_username) < 3
    ) {

        $error =
            "Username minimal 3 karakter.";

    } elseif (
        strlen($password) < 6
    ) {

        $error =
            "Password minimal 6 karakter.";

    } elseif (
        $password !== $password_confirmation
    ) {

        $error =
            "Konfirmasi password tidak sama.";

    } elseif (
        !in_array(
            $old_role,
            ['admin', 'user'],
            true
        )
    ) {

        $error =
            "Role tidak valid.";

    } elseif (
        !in_array(
            $old_status,
            ['Aktif', 'Nonaktif'],
            true
        )
    ) {

        $error =
            "Status tidak valid.";

    } else {


        /* CEK USERNAME */

        $check = mysqli_prepare(
            $conn,
            "
            SELECT id
            FROM users
            WHERE username = ?
            LIMIT 1
            "
        );

        mysqli_stmt_bind_param(
            $check,
            "s",
            $old_username
        );

        mysqli_stmt_execute($check);

        $check_result =
            mysqli_stmt_get_result($check);

        $existing =
            mysqli_fetch_assoc($check_result);

        mysqli_stmt_close($check);


        if ($existing) {

            $error =
                "Username tersebut sudah digunakan.";

        } else {


            /* HASH PASSWORD */

            $password_hash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            /* INSERT */

            $insert = mysqli_prepare(
                $conn,
                "
                INSERT INTO users
                (
                    username,
                    password,
                    nama_lengkap,
                    role,
                    status
                )
                VALUES
                (?, ?, ?, ?, ?)
                "
            );


            if (!$insert) {

                $error =
                    "Gagal menyiapkan data user.";

            } else {

                mysqli_stmt_bind_param(
                    $insert,
                    "sssss",
                    $old_username,
                    $password_hash,
                    $old_nama,
                    $old_role,
                    $old_status
                );


                if (
                    mysqli_stmt_execute($insert)
                ) {

                    $_SESSION['user_success'] =
                        "User berhasil ditambahkan.";

                    mysqli_stmt_close($insert);

                    header("Location: index.php");

                    exit;

                } else {

                    $error =
                        "User gagal ditambahkan.";

                    mysqli_stmt_close($insert);

                }

            }

        }

    }

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
Tambah User - Inventory Maintenance
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
    background:#fff;
    border:1px solid #e6ebf2;
    border-radius:16px;
    overflow:hidden;
}

.card-header-custom {
    background:#075eaa;
    color:#fff;
    padding:22px 25px;
}

.card-header-custom h1 {
    font-size:19px;
    margin:0;
    font-weight:700;
}

.card-header-custom p {
    font-size:10px;
    margin:5px 0 0;
    opacity:.9;
}

.card-body-custom {
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
    padding:10px 12px;
    border-radius:8px;
    border-color:#e6ebf2;
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

.info {
    background:#eaf4ff;
    color:#075eaa;
    padding:12px 14px;
    border-radius:9px;
    font-size:10px;
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

<div class="card-header-custom">

<h1>
<i class="bi bi-person-plus-fill me-2"></i>
Tambah User
</h1>

<p>
Buat akun pengguna baru untuk sistem Inventory Maintenance
</p>

</div>


<div class="card-body-custom">


<?php if ($error): ?>

<div class="alert alert-danger py-2" style="font-size:11px;">
<i class="bi bi-exclamation-circle me-1"></i>
<?= htmlspecialchars($error) ?>
</div>

<?php endif; ?>


<div class="info mb-4">

<i class="bi bi-info-circle-fill me-1"></i>

Password minimal 6 karakter dan akan disimpan dalam bentuk terenkripsi.

</div>


<form
    method="POST"
    autocomplete="off"
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
    value="<?= htmlspecialchars($old_username) ?>"
    placeholder="Contoh: teknik"
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
    value="<?= htmlspecialchars($old_nama) ?>"
    placeholder="Nama lengkap pengguna"
    required
>

</div>


<div class="col-md-6">

<label class="form-label">
Password *
</label>

<input
    type="password"
    name="password"
    class="form-control"
    placeholder="Minimal 6 karakter"
    required
>

</div>


<div class="col-md-6">

<label class="form-label">
Konfirmasi Password *
</label>

<input
    type="password"
    name="password_confirmation"
    class="form-control"
    placeholder="Ulangi password"
    required
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
    <?= $old_role === 'user'
        ? 'selected'
        : '' ?>
>
User
</option>

<option
    value="admin"
    <?= $old_role === 'admin'
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
    required
>

<option
    value="Aktif"
    <?= $old_status === 'Aktif'
        ? 'selected'
        : '' ?>
>
Aktif
</option>

<option
    value="Nonaktif"
    <?= $old_status === 'Nonaktif'
        ? 'selected'
        : '' ?>
>
Nonaktif
</option>

</select>

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
Simpan User
</button>

</div>


</form>


</div>

</div>

</div>

</div>

</body>

</html>