<?php
// =========================================================
// LOGIN
// Inventory Maintenance System
// PT Garudafood Putra Putri Jaya Tbk
// =========================================================

session_start();

include "koneksi.php";


// =========================================================
// JIKA SUDAH LOGIN
// =========================================================

if (isset($_SESSION['user_id'])) {

    // Jika admin
    if (($_SESSION['role'] ?? '') === 'admin') {

        header("Location: admin/index.php");
        exit;

    }

    // Jika user biasa
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


    // =====================================================
    // VALIDASI
    // =====================================================

    if ($username === '' || $password === '') {

        $error = "Username dan password wajib diisi.";

    } else {


        // =================================================
        // QUERY USER
        // =================================================

        $stmt = mysqli_prepare(
            $conn,
            "
            SELECT
                id,
                username,
                password,
                nama_lengkap,
                role
            FROM users
            WHERE username = ?
            LIMIT 1
            "
        );


        // =================================================
        // CEK PREPARE
        // =================================================

        if (!$stmt) {

            $error = "Terjadi kesalahan sistem.";

        } else {


            // =============================================
            // BIND PARAMETER
            // =============================================

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $username
            );


            // =============================================
            // EXECUTE
            // =============================================

            if (!mysqli_stmt_execute($stmt)) {

                $error = "Terjadi kesalahan saat memproses login.";

                mysqli_stmt_close($stmt);

            } else {


                // =========================================
                // AMBIL HASIL
                // =========================================

                $result = mysqli_stmt_get_result($stmt);

                $user = mysqli_fetch_assoc($result);

                mysqli_stmt_close($stmt);


                // =========================================
                // VERIFIKASI PASSWORD
                // =========================================

                if (
                    $user &&
                    password_verify(
                        $password,
                        $user['password']
                    )
                ) {


                    // =====================================
                    // REGENERATE SESSION
                    // =====================================

                    session_regenerate_id(true);


                    // =====================================
                    // SIMPAN SESSION
                    // =====================================

                    $_SESSION['user_id'] =
                        (int) $user['id'];

                    $_SESSION['username'] =
                        $user['username'];

                    $_SESSION['nama_lengkap'] =
                        $user['nama_lengkap'];

                    $_SESSION['role'] =
                        $user['role'];


                    // =====================================
                    // REDIRECT BERDASARKAN ROLE
                    // =====================================

                    if ($user['role'] === 'admin') {

                        header(
                            "Location: admin/index.php"
                        );

                        exit;
                    }


                    // =====================================
                    // USER BIASA
                    // =====================================

                    header(
                        "Location: dashboard/index.php"
                    );

                    exit;

                } else {

                    $error =
                        "Username atau password salah.";
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
        Login - Inventory Maintenance System
    </title>


    <!-- =================================================
         BOOTSTRAP
    ================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =================================================
         BOOTSTRAP ICONS
    ================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >


    <!-- =================================================
         GOOGLE FONT
    ================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <style>

        /* =================================================
           RESET
        ================================================= */

        * {
            box-sizing: border-box;
        }


        /* =================================================
           BODY
        ================================================= */

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


        /* =================================================
           WRAPPER
        ================================================= */

        .login-wrapper {

            width: 100%;

            max-width: 420px;
        }


        /* =================================================
           CARD
        ================================================= */

        .login-card {

            background: #ffffff;

            border: 1px solid #e5edf7;

            border-radius: 22px;

            padding: 35px 32px;

            box-shadow:
                0 15px 45px rgba(
                    15,
                    23,
                    42,
                    .09
                );
        }


        /* =================================================
           LOGO
        ================================================= */

        .login-logo {

            width: 75px;

            height: 75px;

            object-fit: contain;

            display: block;

            margin: 0 auto 12px;
        }


        /* =================================================
           TITLE
        ================================================= */

        .login-title {

            text-align: center;

            font-size: 22px;

            font-weight: 800;

            color: #075eaa;

            margin-bottom: 4px;
        }


        /* =================================================
           SUBTITLE
        ================================================= */

        .login-subtitle {

            text-align: center;

            font-size: 11px;

            color: #94a3b8;

            margin-bottom: 28px;
        }


        /* =================================================
           LABEL
        ================================================= */

        .form-label {

            font-size: 11px;

            font-weight: 700;

            color: #475569;

            margin-bottom: 7px;
        }


        /* =================================================
           INPUT
        ================================================= */

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
                0 0 0 3px
                rgba(
                    7,
                    94,
                    170,
                    .10
                );
        }


        /* =================================================
           INPUT ICON
        ================================================= */

        .input-group-text {

            background: #f8fafc;

            border-color: #dbe3ed;

            color: #64748b;
        }


        /* =================================================
           LOGIN BUTTON
        ================================================= */

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


        /* =================================================
           ERROR
        ================================================= */

        .login-error {

            background: #fff1f2;

            border: 1px solid #fecdd3;

            color: #be123c;

            border-radius: 9px;

            padding: 10px 12px;

            font-size: 11px;

            margin-bottom: 18px;
        }


        /* =================================================
           FOOTER
        ================================================= */

        .login-footer {

            text-align: center;

            margin-top: 24px;

            font-size: 9px;

            color: #94a3b8;

            line-height: 1.6;
        }


        /* =================================================
           PASSWORD TOGGLE
        ================================================= */

        .password-toggle {

            cursor: pointer;

            user-select: none;
        }


        /* =================================================
           MOBILE
        ================================================= */

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


        <!-- =================================================
             LOGO GARUDAFOOD
        ================================================== -->

        <img
            src="/inventory_mesin/assets/img/logo-garudafood.png"
            alt="GarudaFood"
            class="login-logo"
        >


        <!-- =================================================
             TITLE
        ================================================== -->

        <div class="login-title">

            Inventory Maintenance

        </div>


        <!-- =================================================
             SUBTITLE
        ================================================== -->

        <div class="login-subtitle">

            PT Garudafood Putra Putri Jaya Tbk

        </div>


        <!-- =================================================
             ERROR MESSAGE
        ================================================== -->

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


        <!-- =================================================
             LOGIN FORM
        ================================================== -->

        <form
            method="POST"
            autocomplete="off"
        >


            <!-- =============================================
                 USERNAME
            ============================================== -->

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


            <!-- =============================================
                 PASSWORD
            ============================================== -->

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
                        title="Tampilkan password"
                    >

                        <i
                            class="bi bi-eye"
                            id="passwordIcon"
                        ></i>

                    </span>

                </div>

            </div>


            <!-- =============================================
                 LOGIN BUTTON
            ============================================== -->

            <button
                type="submit"
                class="btn btn-login"
            >

                <i
                    class="bi bi-box-arrow-in-right me-1"
                ></i>

                Login

            </button>


        </form>


        <!-- =================================================
             FOOTER
        ================================================== -->

        <div class="login-footer">

            Inventory Maintenance System<br>

            D3 - Teknologi Informasi<br>

            Politeknik Semen Indonesia

        </div>


    </div>

</div>


<!-- =========================================================
     PASSWORD SCRIPT
========================================================= -->

<script>

function togglePassword() {

    const password =
        document.getElementById('password');

    const icon =
        document.getElementById('passwordIcon');


    if (!password || !icon) {
        return;
    }


    if (password.type === 'password') {

        password.type = 'text';

        icon.className =
            'bi bi-eye-slash';

    } else {

        password.type = 'password';

        icon.className =
            'bi bi-eye';
    }

}

</script>


</body>

</html>