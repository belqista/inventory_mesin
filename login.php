<?php
// =========================================================
// LOGIN
// Inventory Maintenance System
// =========================================================

session_start();

include "koneksi.php";


// =========================================================
// JIKA SUDAH LOGIN
// =========================================================

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/index.php");
    exit;
}


// =========================================================
// PROSES LOGIN
// =========================================================

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {

        $error = "Username dan password wajib diisi.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "
            SELECT
                id,
                username,
                password,
                nama_lengkap
            FROM users
            WHERE username = ?
            LIMIT 1
            "
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

            $user = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);


            if ($user && password_verify($password, $user['password'])) {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

                header("Location: dashboard/index.php");
                exit;

            } else {

                $error = "Username atau password salah.";
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

    <title>Login - Inventory Maintenance System</title>


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
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
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


        .login-wrapper {

            width: 100%;

            max-width: 420px;
        }


        .login-card {

            background: #ffffff;

            border: 1px solid #e5edf7;

            border-radius: 22px;

            padding: 35px 32px;

            box-shadow:
                0 15px 45px rgba(15, 23, 42, .09);
        }


        .login-logo {

            width: 75px;

            height: 75px;

            object-fit: contain;

            display: block;

            margin: 0 auto 12px;
        }


        .login-title {

            text-align: center;

            font-size: 22px;

            font-weight: 800;

            color: #075eaa;

            margin-bottom: 4px;
        }


        .login-subtitle {

            text-align: center;

            font-size: 11px;

            color: #94a3b8;

            margin-bottom: 28px;
        }


        .form-label {

            font-size: 11px;

            font-weight: 700;

            color: #475569;

            margin-bottom: 7px;
        }


        .form-control {

            height: 46px;

            border-radius: 10px;

            border: 1px solid #dbe3ed;

            font-size: 13px;

            padding-left: 14px;
        }


        .form-control:focus {

            border-color: #075eaa;

            box-shadow:
                0 0 0 3px rgba(7, 94, 170, .10);
        }


        .input-group-text {

            background: #f8fafc;

            border-color: #dbe3ed;

            color: #64748b;
        }


        .btn-login {

            width: 100%;

            height: 46px;

            border: 0;

            border-radius: 10px;

            background: #075eaa;

            color: #ffffff;

            font-size: 13px;

            font-weight: 700;

            transition: .2s ease;
        }


        .btn-login:hover {

            background: #064f8f;

            color: #ffffff;

            transform: translateY(-1px);
        }


        .login-error {

            background: #fff1f2;

            border: 1px solid #fecdd3;

            color: #be123c;

            border-radius: 9px;

            padding: 10px 12px;

            font-size: 11px;

            margin-bottom: 18px;
        }


        .login-footer {

            text-align: center;

            margin-top: 24px;

            font-size: 9px;

            color: #94a3b8;

            line-height: 1.6;
        }


        .password-toggle {

            cursor: pointer;

            user-select: none;
        }


        @media (max-width: 480px) {

            .login-card {

                padding: 28px 22px;

                border-radius: 18px;
            }

            .login-title {

                font-size: 20px;
            }
        }

    </style>

</head>


<body>


<div class="login-wrapper">

    <div class="login-card">


        <!-- LOGO GARUDAFOOD -->

        <img
            src="/inventory_mesin/assets/img/logo-garudafood.png"
            alt="GarudaFood"
            class="login-logo"
        >


        <div class="login-title">
            Inventory Maintenance
        </div>


        <div class="login-subtitle">
            PT Garudafood Putra Putri Jaya Tbk
        </div>


        <?php if ($error !== ''): ?>

            <div class="login-error">

                <i class="bi bi-exclamation-circle me-1"></i>

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            autocomplete="off"
        >


            <!-- USERNAME -->

            <div class="mb-3">

                <label class="form-label">
                    Username
                </label>

                <div class="input-group">

                    <span class="input-group-text">
                        <i class="bi bi-person"></i>
                    </span>

                    <input
                        type="text"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan username"
                        value="<?= htmlspecialchars(
                            $_POST['username'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                        autofocus
                    >

                </div>

            </div>


            <!-- PASSWORD -->

            <div class="mb-4">

                <label class="form-label">
                    Password
                </label>

                <div class="input-group">

                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        required
                    >

                    <span
                        class="input-group-text password-toggle"
                        onclick="togglePassword()"
                    >
                        <i
                            class="bi bi-eye"
                            id="passwordIcon"
                        ></i>
                    </span>

                </div>

            </div>


            <!-- LOGIN -->

            <button
                type="submit"
                class="btn btn-login"
            >

                <i class="bi bi-box-arrow-in-right me-1"></i>

                Login

            </button>


        </form>


        <div class="login-footer">

            Inventory Maintenance System<br>

            D3 - Teknologi Informasi<br>

            Politeknik Semen Indonesia

        </div>


    </div>

</div>


<script>

function togglePassword() {

    const password =
        document.getElementById('password');

    const icon =
        document.getElementById('passwordIcon');


    if (password.type === 'password') {

        password.type = 'text';

        icon.className = 'bi bi-eye-slash';

    } else {

        password.type = 'password';

        icon.className = 'bi bi-eye';
    }
}

</script>


</body>

</html>