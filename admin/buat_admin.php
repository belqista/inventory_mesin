<?php

session_start();

require_once "../koneksi.php";


// =========================================================
// PROSES BUAT AKUN ADMIN
// =========================================================

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username     = trim($_POST['username'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $password     = $_POST['password'] ?? '';
    $konfirmasi   = $_POST['konfirmasi_password'] ?? '';


    // =====================================================
    // VALIDASI
    // =====================================================

    if (
        $username === '' ||
        $nama_lengkap === '' ||
        $password === '' ||
        $konfirmasi === ''
    ) {

        $error = "Semua field wajib diisi.";

    } elseif (strlen($username) < 3) {

        $error = "Username minimal 3 karakter.";

    } elseif (strlen($password) < 6) {

        $error = "Password minimal 6 karakter.";

    } elseif ($password !== $konfirmasi) {

        $error = "Konfirmasi password tidak cocok.";

    } else {


        // =================================================
        // CEK USERNAME
        // =================================================

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE username = ? LIMIT 1"
        );

        if (!$stmt) {

            $error = "Terjadi kesalahan sistem.";

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $username
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $existing = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);


            if ($existing) {

                $error = "Username tersebut sudah digunakan.";

            } else {


                // =============================================
                // HASH PASSWORD
                // =============================================

                $password_hash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                // =============================================
                // SIMPAN ADMIN
                // =============================================

                $stmt = mysqli_prepare(
                    $conn,
                    "
                    INSERT INTO users
                    (
                        username,
                        password,
                        nama_lengkap,
                        role
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        'admin'
                    )
                    "
                );


                if (!$stmt) {

                    $error = "Gagal menyiapkan proses pembuatan akun.";

                } else {

                    mysqli_stmt_bind_param(
                        $stmt,
                        "sss",
                        $username,
                        $password_hash,
                        $nama_lengkap
                    );


                    if (mysqli_stmt_execute($stmt)) {

                        $success = "Akun admin berhasil dibuat.";

                        $_POST = [];

                    } else {

                        $error = "Akun admin gagal dibuat.";
                    }


                    mysqli_stmt_close($stmt);
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

    <title>Buat Admin - Inventory Maintenance</title>


    <!-- BOOTSTRAP -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- BOOTSTRAP ICONS -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- GOOGLE FONT -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            min-height: 100vh;

            font-family: 'Poppins', sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #eef6ff 0%,
                    #f8fbff 50%,
                    #ffffff 100%
                );

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;
        }


        .admin-wrapper {

            width: 100%;

            max-width: 460px;
        }


        .admin-card {

            background: #ffffff;

            border: 1px solid #e5edf7;

            border-radius: 22px;

            padding: 34px;

            box-shadow:
                0 15px 45px rgba(15, 23, 42, .09);
        }


        .icon-admin {

            width: 64px;

            height: 64px;

            border-radius: 16px;

            background: #eaf2ff;

            color: #075eaa;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 28px;

            margin: 0 auto 16px;
        }


        .title {

            text-align: center;

            font-size: 21px;

            font-weight: 800;

            color: #075eaa;

            margin-bottom: 5px;
        }


        .subtitle {

            text-align: center;

            font-size: 11px;

            color: #94a3b8;

            margin-bottom: 25px;
        }


        .form-label {

            font-size: 11px;

            font-weight: 700;

            color: #475569;

            margin-bottom: 7px;
        }


        .form-control {

            height: 45px;

            border-radius: 10px;

            border: 1px solid #dbe3ed;

            font-size: 13px;
        }


        .form-control:focus {

            border-color: #075eaa;

            box-shadow:
                0 0 0 3px rgba(7, 94, 170, .10);
        }


        .btn-admin {

            width: 100%;

            height: 45px;

            border: 0;

            border-radius: 10px;

            background: #075eaa;

            color: white;

            font-size: 13px;

            font-weight: 700;
        }


        .btn-admin:hover {

            background: #064f8f;

            color: white;
        }


        .alert {

            font-size: 11px;

            border-radius: 9px;
        }


        .note {

            margin-top: 18px;

            padding: 12px;

            background: #f8fafc;

            border-radius: 10px;

            color: #64748b;

            font-size: 10px;

            line-height: 1.6;
        }


        .back-link {

            display: block;

            text-align: center;

            margin-top: 18px;

            color: #075eaa;

            text-decoration: none;

            font-size: 11px;

            font-weight: 600;
        }


        .back-link:hover {

            text-decoration: underline;
        }


    </style>

</head>


<body>


<div class="admin-wrapper">

    <div class="admin-card">


        <div class="icon-admin">

            <i class="bi bi-shield-lock"></i>

        </div>


        <div class="title">
            Buat Akun Admin
        </div>


        <div class="subtitle">
            Inventory Maintenance System
        </div>


        <?php if ($success !== ''): ?>

            <div class="alert alert-success">

                <i class="bi bi-check-circle me-1"></i>

                <?= htmlspecialchars(
                    $success,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <?php if ($error !== ''): ?>

            <div class="alert alert-danger">

                <i class="bi bi-exclamation-circle me-1"></i>

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <form method="POST" autocomplete="off">


            <!-- USERNAME -->

            <div class="mb-3">

                <label class="form-label">
                    Username Admin
                </label>

                <input
                    type="text"
                    name="username"
                    class="form-control"
                    placeholder="Contoh: admin"
                    value="<?= htmlspecialchars(
                        $_POST['username'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

            </div>


            <!-- NAMA -->

            <div class="mb-3">

                <label class="form-label">
                    Nama Lengkap
                </label>

                <input
                    type="text"
                    name="nama_lengkap"
                    class="form-control"
                    placeholder="Contoh: Administrator"
                    value="<?= htmlspecialchars(
                        $_POST['nama_lengkap'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

            </div>


            <!-- PASSWORD -->

            <div class="mb-3">

                <label class="form-label">
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Minimal 6 karakter"
                    required
                >

            </div>


            <!-- KONFIRMASI -->

            <div class="mb-4">

                <label class="form-label">
                    Konfirmasi Password
                </label>

                <input
                    type="password"
                    name="konfirmasi_password"
                    class="form-control"
                    placeholder="Ulangi password"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn btn-admin"
            >

                <i class="bi bi-person-plus me-1"></i>

                Buat Akun Admin

            </button>


        </form>


        <div class="note">

            <i class="bi bi-info-circle me-1"></i>

            Password akan disimpan menggunakan
            <strong>password hash</strong>.
            Password tidak disimpan dalam bentuk teks biasa.

        </div>


        <a
            href="../login.php"
            class="back-link"
        >

            <i class="bi bi-arrow-left me-1"></i>

            Kembali ke Login

        </a>


    </div>

</div>


</body>

</html>