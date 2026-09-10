<?php
require_once __DIR__ . '/../template/auth.php';
require_user_role();

// =========================================================
// TAMBAH AREA / BAGIAN
// Inventory Maintenance System
// PT Garudafood Putra Putri Jaya Tbk
// =========================================================

include "../koneksi.php";

$error   = "";
$success = "";


/* =========================================================
   PROSES SIMPAN DATA
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {

    $nama_area = trim($_POST['nama_area'] ?? '');
    $lokasi    = trim($_POST['lokasi'] ?? '');


    /* =====================================================
       VALIDASI
    ===================================================== */

    if ($nama_area === '') {

        $error = "Nama Area / Bagian wajib diisi!";

    } else {

        /* =================================================
           CEK DUPLIKASI
           
           NAMA AREA BOLEH SAMA JIKA LOKASI/GEDUNG BERBEDA.

           Contoh:
           Baking - PGA    ✅
           Baking - PSPD   ✅
           Baking - PGMJ   ✅
           Baking - PDP    ✅

           Tetapi:
           Baking - PGA
           Baking - PGA    ❌
        ================================================= */

        $stmt_cek = mysqli_prepare(
            $conn,
            "
            SELECT id
            FROM area_bagian
            WHERE LOWER(TRIM(nama_area)) = LOWER(TRIM(?))
              AND LOWER(TRIM(COALESCE(lokasi, ''))) = LOWER(TRIM(?))
            LIMIT 1
            "
        );


        if ($stmt_cek) {

            mysqli_stmt_bind_param(
                $stmt_cek,
                "ss",
                $nama_area,
                $lokasi
            );

            mysqli_stmt_execute($stmt_cek);

            $result_cek = mysqli_stmt_get_result($stmt_cek);


            if ($result_cek && mysqli_num_rows($result_cek) > 0) {

                if ($lokasi !== '') {

                    $error =
                        "Area / Bagian <strong>" .
                        htmlspecialchars(
                            $nama_area,
                            ENT_QUOTES,
                            'UTF-8'
                        ) .
                        "</strong> pada lokasi / gedung <strong>" .
                        htmlspecialchars(
                            $lokasi,
                            ENT_QUOTES,
                            'UTF-8'
                        ) .
                        "</strong> sudah terdaftar.";

                } else {

                    $error =
                        "Area / Bagian <strong>" .
                        htmlspecialchars(
                            $nama_area,
                            ENT_QUOTES,
                            'UTF-8'
                        ) .
                        "</strong> sudah terdaftar.";

                }

            }


            mysqli_stmt_close($stmt_cek);


        } else {

            $error =
                "Terjadi kesalahan saat memeriksa data area.";

        }
    }


    /* =====================================================
       SIMPAN KE DATABASE
    ===================================================== */

    if ($error === '') {

        $stmt = mysqli_prepare(
            $conn,
            "
            INSERT INTO area_bagian
                (nama_area, lokasi)
            VALUES
                (?, ?)
            "
        );


        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "ss",
                $nama_area,
                $lokasi
            );


            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                header("Location: area.php");
                exit;


            } else {

                $error =
                    "Gagal menyimpan data: " .
                    mysqli_error($conn);

                mysqli_stmt_close($stmt);
            }


        } else {

            $error =
                "Gagal menyiapkan query database.";

        }
    }
}


/* =========================================================
   HEADER
========================================================= */

include "../template/header.php";

?>

<style>

/* =========================================================
   RESPONSIVE FORM AREA
========================================================= */

.area-page {
    width: 100%;
}


/* =========================================================
   HEADER
========================================================= */

.area-page .dashboard-header {
    border-radius: 12px;
}


/* =========================================================
   CARD
========================================================= */

.area-page .content-card {
    width: 100%;
    overflow: hidden;
}


/* =========================================================
   INPUT
========================================================= */

.area-page .form-control,
.area-page .form-select {
    min-height: 40px;
}


/* =========================================================
   TOMBOL
========================================================= */

.area-action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}


/* =========================================================
   DESKRIPSI INPUT
========================================================= */

.style-subtext {
    font-size: 11px;
    color: #64748b;
    line-height: 1.5;
}


/* =========================================================
   JUDUL
========================================================= */

.area-title-wrapper {
    min-width: 0;
}


.area-title-wrapper h3 {
    line-height: 1.3;
}


.area-title-wrapper p {
    line-height: 1.5;
}


/* =========================================================
   TABLET
========================================================= */

@media (max-width: 768px) {

    .area-page .dashboard-header {
        padding: 16px !important;
    }


    .area-page .dashboard-title {
        font-size: 20px !important;
    }


    .area-page .dashboard-subtitle {
        font-size: 12px !important;
    }


    .area-page .card-header-custom {
        padding: 12px 14px !important;
    }


    .area-page .card-body-custom {
        padding: 14px !important;
    }

}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 576px) {

    .area-page {
        padding-left: 0;
        padding-right: 0;
    }


    /* Header */

    .area-page .dashboard-header {
        margin-bottom: 12px !important;
        padding: 14px !important;
    }


    .area-page .dashboard-header > div {
        align-items: flex-start !important;
    }


    .area-page .dashboard-header a {
        width: 36px !important;
        height: 36px !important;
    }


    .area-page .dashboard-header a i {
        font-size: 16px !important;
    }


    .area-page .dashboard-title {
        font-size: 18px !important;
    }


    .area-page .dashboard-subtitle {
        font-size: 11px !important;
        margin-top: 3px !important;
    }


    /* Card */

    .area-page .content-card {
        border-radius: 10px;
        margin-bottom: 12px !important;
    }


    .area-page .card-header-custom {
        padding: 11px 13px !important;
    }


    .area-page .card-title-custom {
        font-size: 13px !important;
    }


    .area-page .card-body-custom {
        padding: 13px !important;
    }


    /* Label */

    .area-page .form-label {
        font-size: 12px !important;
    }


    /* Input */

    .area-page .form-control {
        min-height: 40px;
        font-size: 13px;
    }


    .area-page .style-subtext {
        font-size: 10px;
    }


    /* Tombol */

    .area-action-buttons {
        width: 100%;
        flex-direction: column;
    }


    .area-action-buttons .btn {
        width: 100%;
        min-height: 40px;
    }

}


/* =========================================================
   EXTRA SMALL MOBILE
========================================================= */

@media (max-width: 380px) {

    .area-page .dashboard-title {
        font-size: 16px !important;
    }


    .area-page .dashboard-subtitle {
        font-size: 10px !important;
    }


    .area-page .card-title-custom {
        font-size: 12px !important;
    }


    .area-page .form-control {
        font-size: 12px;
    }

}

</style>


<div class="container-fluid p-0 area-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="dashboard-header mb-3 py-3 px-4">

        <div class="d-flex align-items-center gap-3">


            <!-- =================================================
                 TOMBOL KEMBALI
            ================================================== -->

            <a
                href="area.php"
                class="btn btn-outline-secondary btn-sm rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0"
                style="width:38px;height:38px;"
                title="Kembali ke Data Area"
            >

                <i class="bi bi-arrow-left fs-5"></i>

            </a>


            <!-- =================================================
                 JUDUL
            ================================================== -->

            <div class="area-title-wrapper">

                <h3 class="dashboard-title m-0 fs-4 fw-bold">

                    Tambah Area Baru

                </h3>


                <p class="dashboard-subtitle m-0 small text-muted">

                    Isi formulir berikut untuk mendaftarkan area atau bagian pabrik baru

                </p>

            </div>

        </div>

    </div>


    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <div class="content-card mb-3">


        <!-- =================================================
             CARD HEADER
        ================================================== -->

        <div class="card-header-custom py-2 px-3">

            <h6 class="card-title-custom m-0 fw-bold">

                <i class="bi bi-plus-circle me-2"></i>

                Form Tambah Area

            </h6>

        </div>


        <!-- =================================================
             CARD BODY
        ================================================== -->

        <div class="card-body-custom p-3">


            <!-- =================================================
                 ALERT ERROR
            ================================================== -->

            <?php if ($error !== '') : ?>

                <div
                    class="alert alert-danger border-0 d-flex align-items-start py-2 px-3 mb-3"
                    role="alert"
                >

                    <i class="bi bi-exclamation-triangle-fill fs-6 me-2 mt-1"></i>


                    <div class="small">

                        <?= $error; ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
                method="POST"
                action=""
                autocomplete="off"
            >


                <div class="row g-3">


                    <!-- =================================================
                         NAMA AREA
                    ================================================== -->

                    <div class="col-12">

                        <label
                            for="nama_area"
                            class="form-label fw-semibold text-dark small mb-1"
                        >

                            Nama Area / Bagian

                            <span class="text-danger">*</span>

                        </label>


                        <input
                            type="text"
                            name="nama_area"
                            id="nama_area"
                            class="form-control form-control-sm"
                            placeholder="Contoh: Gedung A - Area Produksi"
                            value="<?= htmlspecialchars(
                                $_POST['nama_area'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                            maxlength="150"
                            required
                            autofocus
                        >


                        <div class="form-text mt-1 style-subtext">

                            Nama lokasi atau bagian pabrik tempat mesin ditempatkan.

                        </div>

                    </div>


                    <!-- =================================================
                         LOKASI
                    ================================================== -->

                    <div class="col-12">

                        <label
                            for="lokasi"
                            class="form-label fw-semibold text-dark small mb-1"
                        >

                            Lokasi Area

                        </label>


                        <input
                            type="text"
                            name="lokasi"
                            id="lokasi"
                            class="form-control form-control-sm"
                            placeholder="Contoh: Lantai 2, Zona Barat / Sektor B"
                            value="<?= htmlspecialchars(
                                $_POST['lokasi'] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                            maxlength="255"
                        >


                        <div class="form-text mt-1 style-subtext">

                            Keterangan detail posisi atau gedung spesifik area tersebut.

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     ACTION BUTTON
                ================================================== -->

                <div class="border-top mt-3 pt-3">

                    <div class="area-action-buttons">


                        <!-- =================================================
                             SIMPAN
                        ================================================== -->

                        <button
                            type="submit"
                            name="simpan"
                            value="1"
                            class="btn btn-primary px-4 btn-sm fw-semibold"
                        >

                            <i class="bi bi-check-lg me-1"></i>

                            Simpan Data

                        </button>


                        <!-- =================================================
                             BATAL
                        ================================================== -->

                        <a
                            href="area.php"
                            class="btn btn-light border px-4 btn-sm fw-semibold text-secondary"
                        >

                            <i class="bi bi-x-lg me-1"></i>

                            Batal

                        </a>


                    </div>

                </div>


            </form>

        </div>

    </div>

</div>


<?php include "../template/footer.php"; ?>