<?php
require_once __DIR__ . '/../template/auth.php';
require_user_role();

include "../koneksi.php";

$error = "";

/* =========================================================
   PROSES SIMPAN DATA
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {

    $id_area          = isset($_POST['id_area']) ? intval($_POST['id_area']) : 0;
    $nama_jenis_mesin = trim($_POST['nama_jenis_mesin'] ?? '');

    /* ---------------------------------------------------------
       VALIDASI
    --------------------------------------------------------- */
    if ($id_area <= 0) {
        $error = "Area Bagian wajib dipilih!";
    } elseif ($nama_jenis_mesin === '') {
        $error = "Nama Jenis Mesin wajib diisi!";
    }

    /* ---------------------------------------------------------
       CEK AREA
    --------------------------------------------------------- */
    if (empty($error)) {

        $stmt_check = mysqli_prepare(
            $conn,
            "SELECT id FROM area_bagian WHERE id = ? LIMIT 1"
        );

        if ($stmt_check) {

            mysqli_stmt_bind_param(
                $stmt_check,
                "i",
                $id_area
            );

            mysqli_stmt_execute($stmt_check);

            $result_check = mysqli_stmt_get_result($stmt_check);

            if (!$result_check || mysqli_num_rows($result_check) === 0) {
                $error = "Area Bagian yang dipilih tidak ditemukan.";
            }

            mysqli_stmt_close($stmt_check);

        } else {
            $error = "Terjadi kesalahan saat memeriksa Area Bagian.";
        }
    }

    /* ---------------------------------------------------------
       SIMPAN DATA
    --------------------------------------------------------- */
    if (empty($error)) {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO jenis_mesin
                (id_area, nama_jenis_mesin)
             VALUES
                (?, ?)"
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "is",
                $id_area,
                $nama_jenis_mesin
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                header("Location: jenis_mesin.php");
                exit;

            } else {

                $error = "Gagal menyimpan data: " . mysqli_error($conn);
            }

            mysqli_stmt_close($stmt);

        } else {

            $error = "Query penyimpanan data gagal diproses.";
        }
    }
}

/* =========================================================
   AMBIL DATA AREA
========================================================= */
$q_area = mysqli_query(
    $conn,
    "SELECT id, nama_area, lokasi
     FROM area_bagian
     ORDER BY nama_area ASC"
);

include "../template/header.php";
?>

<style>
/* =========================================================
   TAMBAH JENIS MESIN - RESPONSIVE
========================================================= */

.jenis-mesin-wrapper {
    width: 100%;
}

.jenis-mesin-header {
    padding: 18px 22px;
    border-radius: 12px;
}

.jenis-mesin-header-content {
    min-width: 0;
}

.jenis-mesin-back {
    width: 40px;
    height: 40px;
    min-width: 40px;
    flex-shrink: 0;
}

.jenis-mesin-title {
    font-size: 22px;
    line-height: 1.3;
}

.jenis-mesin-subtitle {
    font-size: 13px;
    line-height: 1.5;
}

.jenis-mesin-card {
    overflow: hidden;
}

.jenis-mesin-card-header {
    padding: 14px 18px;
}

.jenis-mesin-card-body {
    padding: 20px;
}

.jenis-mesin-label {
    font-size: 13px;
    margin-bottom: 6px;
}

.jenis-mesin-input,
.jenis-mesin-select {
    min-height: 40px;
    font-size: 14px;
}

.jenis-mesin-help {
    font-size: 11px;
    line-height: 1.5;
    color: #64748b;
}

.jenis-mesin-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.jenis-mesin-actions .btn {
    min-height: 38px;
}

/* Tablet */
@media (max-width: 768px) {

    .jenis-mesin-header {
        padding: 16px;
    }

    .jenis-mesin-title {
        font-size: 19px;
    }

    .jenis-mesin-subtitle {
        font-size: 12px;
    }

    .jenis-mesin-card-body {
        padding: 16px;
    }

    .jenis-mesin-actions {
        width: 100%;
    }

    .jenis-mesin-actions .btn {
        flex: 1 1 auto;
    }
}

/* Mobile */
@media (max-width: 576px) {

    .jenis-mesin-header {
        padding: 14px;
        margin-bottom: 12px !important;
    }

    .jenis-mesin-header-row {
        align-items: flex-start !important;
    }

    .jenis-mesin-back {
        width: 36px;
        height: 36px;
        min-width: 36px;
    }

    .jenis-mesin-back i {
        font-size: 16px !important;
    }

    .jenis-mesin-title {
        font-size: 17px;
    }

    .jenis-mesin-subtitle {
        font-size: 11px;
        margin-top: 2px !important;
    }

    .jenis-mesin-card-header {
        padding: 12px 14px;
    }

    .jenis-mesin-card-body {
        padding: 14px;
    }

    .jenis-mesin-label {
        font-size: 12px;
    }

    .jenis-mesin-input,
    .jenis-mesin-select {
        min-height: 42px;
        font-size: 13px;
    }

    .jenis-mesin-help {
        font-size: 10px;
    }

    .jenis-mesin-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .jenis-mesin-actions .btn {
        width: 100%;
        flex: none;
    }

    .jenis-mesin-alert {
        font-size: 12px;
    }
}
</style>

<div class="container-fluid p-0 jenis-mesin-wrapper">

    <!-- =====================================================
         HEADER
    ====================================================== -->
    <div class="dashboard-header jenis-mesin-header mb-3">

        <div class="d-flex align-items-center gap-3 jenis-mesin-header-row">

            <!-- Tombol Kembali -->
            <a
                href="jenis_mesin.php"
                class="btn btn-outline-secondary btn-sm rounded-circle d-inline-flex align-items-center justify-content-center jenis-mesin-back"
                title="Kembali"
            >
                <i class="bi bi-arrow-left fs-5"></i>
            </a>

            <!-- Judul -->
            <div class="jenis-mesin-header-content">

                <h3 class="dashboard-title m-0 fw-bold jenis-mesin-title">
                    Tambah Jenis Mesin Baru
                </h3>

                <p class="dashboard-subtitle m-0 mt-1 jenis-mesin-subtitle text-muted">
                    Isi formulir untuk mendaftarkan kategori atau jenis mesin baru
                </p>

            </div>

        </div>

    </div>


    <!-- =====================================================
         FORM CARD
    ====================================================== -->
    <div class="content-card jenis-mesin-card mb-3">

        <!-- CARD HEADER -->
        <div class="card-header-custom jenis-mesin-card-header">

            <h6 class="card-title-custom m-0 fw-bold">

                <i class="bi bi-plus-circle me-2"></i>

                Form Tambah Jenis Mesin

            </h6>

        </div>


        <!-- CARD BODY -->
        <div class="card-body-custom jenis-mesin-card-body">

            <!-- =================================================
                 ERROR MESSAGE
            ================================================== -->
            <?php if (!empty($error)) : ?>

                <div
                    class="alert alert-danger border-0 d-flex align-items-start py-2 px-3 mb-4 jenis-mesin-alert"
                    role="alert"
                >

                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>

                    <div>
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 FORM
            ================================================== -->
            <form method="POST" action="" autocomplete="off">

                <div class="row g-3">

                    <!-- =========================================
                         AREA BAGIAN
                    ========================================== -->
                    <div class="col-12 col-md-6">

                        <label
                            for="id_area"
                            class="form-label fw-semibold text-dark jenis-mesin-label"
                        >
                            Area Bagian

                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="id_area"
                            id="id_area"
                            class="form-select jenis-mesin-select"
                            required
                        >

                            <option value="">
                                -- Pilih Area Bagian --
                            </option>

                            <?php if ($q_area && mysqli_num_rows($q_area) > 0) : ?>

                                <?php while ($a = mysqli_fetch_assoc($q_area)) : ?>

                                    <?php
                                    $selected = (
                                        isset($_POST['id_area']) &&
                                        (int)$_POST['id_area'] === (int)$a['id']
                                    ) ? 'selected' : '';
                                    ?>

                                    <option
                                        value="<?= (int)$a['id']; ?>"
                                        <?= $selected; ?>
                                    >
                                        <?= htmlspecialchars(
                                            $a['nama_area'] ?? '-',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                        <?php if (!empty($a['lokasi'])) : ?>
                                            — <?= htmlspecialchars(
                                                $a['lokasi'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?>
                                        <?php endif; ?>

                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>

                        <div class="form-text mt-1 jenis-mesin-help">
                            Pilih area atau bagian pabrik tempat jenis mesin berada.
                        </div>

                    </div>


                    <!-- =========================================
                         NAMA JENIS MESIN
                    ========================================== -->
                    <div class="col-12 col-md-6">

                        <label
                            for="nama_jenis_mesin"
                            class="form-label fw-semibold text-dark jenis-mesin-label"
                        >
                            Nama Jenis Mesin

                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="nama_jenis_mesin"
                            id="nama_jenis_mesin"
                            class="form-control jenis-mesin-input"
                            placeholder="Contoh: Conveyor, Mixer, Filling Machine"
                            value="<?= isset($_POST['nama_jenis_mesin'])
                                ? htmlspecialchars(
                                    $_POST['nama_jenis_mesin'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                                : ''; ?>"
                            maxlength="150"
                            required
                        >

                        <div class="form-text mt-1 jenis-mesin-help">
                            Masukkan nama kategori atau kelompok jenis unit mesin.
                        </div>

                    </div>

                </div>


                <!-- =================================================
                     ACTION
                ================================================== -->
                <div class="border-top mt-4 pt-3 jenis-mesin-actions">

                    <button
                        type="submit"
                        name="simpan"
                        class="btn btn-primary px-4 fw-semibold"
                    >

                        <i class="bi bi-check-lg me-1"></i>

                        Simpan Data

                    </button>


                    <a
                        href="jenis_mesin.php"
                        class="btn btn-light border px-4 fw-semibold text-secondary"
                    >

                        <i class="bi bi-x-lg me-1"></i>

                        Batal

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

<?php include "../template/footer.php"; ?> 