<?php
require_once __DIR__ . '/../template/auth.php';
require_user_role();


include "../koneksi.php";

/* =========================================================
   KONFIGURASI
========================================================= */

$error   = "";
$success = "";

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;


/* =========================================================
   VALIDASI ID
========================================================= */

if ($id <= 0) {
    header("Location: jenis_mesin.php");
    exit;
}


/* =========================================================
   AMBIL DATA JENIS MESIN
========================================================= */

$stmt_get = mysqli_prepare(
    $conn,
    "SELECT
        id,
        id_area,
        nama_jenis_mesin
     FROM jenis_mesin
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt_get) {
    die(
        "Gagal menyiapkan query data jenis mesin: "
        . htmlspecialchars(mysqli_error($conn))
    );
}

mysqli_stmt_bind_param(
    $stmt_get,
    "i",
    $id
);

if (!mysqli_stmt_execute($stmt_get)) {
    die(
        "Gagal mengambil data jenis mesin: "
        . htmlspecialchars(mysqli_stmt_error($stmt_get))
    );
}

$result = mysqli_stmt_get_result($stmt_get);

$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt_get);


/* =========================================================
   DATA TIDAK DITEMUKAN
========================================================= */

if (!$data) {
    header("Location: jenis_mesin.php");
    exit;
}


/* =========================================================
   NILAI AWAL FORM
========================================================= */

$form_id_area = (int) ($data['id_area'] ?? 0);

$form_nama_jenis_mesin =
    $data['nama_jenis_mesin'] ?? '';



/* =========================================================
   PROSES UPDATE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * Ambil nilai dari POST
     */
    $form_id_area = isset($_POST['id_area'])
        ? (int) $_POST['id_area']
        : 0;

    $form_nama_jenis_mesin = trim(
        $_POST['nama_jenis_mesin'] ?? ''
    );


    /* -----------------------------------------------------
       VALIDASI AREA
    ----------------------------------------------------- */

    if ($form_id_area <= 0) {

        $error = "Area Bagian wajib dipilih.";

    }


    /* -----------------------------------------------------
       VALIDASI NAMA
    ----------------------------------------------------- */

    elseif ($form_nama_jenis_mesin === '') {

        $error = "Nama Jenis Mesin wajib diisi.";

    }

    elseif (strlen($form_nama_jenis_mesin) < 2) {

        $error = "Nama Jenis Mesin minimal 2 karakter.";

    }


    /* -----------------------------------------------------
       CEK AREA
    ----------------------------------------------------- */

    if ($error === "") {

        $stmt_area = mysqli_prepare(
            $conn,
            "SELECT id
             FROM area_bagian
             WHERE id = ?
             LIMIT 1"
        );

        if (!$stmt_area) {

            $error =
                "Gagal memeriksa Area Bagian: "
                . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt_area,
                "i",
                $form_id_area
            );

            if (!mysqli_stmt_execute($stmt_area)) {

                $error =
                    "Gagal memeriksa Area Bagian: "
                    . mysqli_stmt_error($stmt_area);

            } else {

                $result_area =
                    mysqli_stmt_get_result($stmt_area);

                if (
                    !$result_area ||
                    mysqli_num_rows($result_area) <= 0
                ) {

                    $error =
                        "Area Bagian yang dipilih tidak ditemukan.";

                }
            }

            mysqli_stmt_close($stmt_area);
        }
    }


    /* -----------------------------------------------------
       CEK DUPLIKASI
    ----------------------------------------------------- */

    if ($error === "") {

        $stmt_check = mysqli_prepare(
            $conn,
            "SELECT id
             FROM jenis_mesin
             WHERE id_area = ?
             AND LOWER(TRIM(nama_jenis_mesin))
                 = LOWER(TRIM(?))
             AND id <> ?
             LIMIT 1"
        );

        if (!$stmt_check) {

            $error =
                "Gagal melakukan pengecekan data: "
                . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt_check,
                "isi",
                $form_id_area,
                $form_nama_jenis_mesin,
                $id
            );

            if (!mysqli_stmt_execute($stmt_check)) {

                $error =
                    "Gagal melakukan pengecekan data: "
                    . mysqli_stmt_error($stmt_check);

            } else {

                $result_check =
                    mysqli_stmt_get_result($stmt_check);

                if (
                    $result_check &&
                    mysqli_num_rows($result_check) > 0
                ) {

                    $error =
                        "Jenis mesin dengan nama tersebut sudah terdaftar pada area yang dipilih.";

                }
            }

            mysqli_stmt_close($stmt_check);
        }
    }


    /* -----------------------------------------------------
       UPDATE DATABASE
    ----------------------------------------------------- */

    if ($error === "") {

        $stmt_update = mysqli_prepare(
            $conn,
            "UPDATE jenis_mesin
             SET
                id_area = ?,
                nama_jenis_mesin = ?
             WHERE id = ?"
        );


        if (!$stmt_update) {

            $error =
                "UPDATE gagal dipersiapkan: "
                . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt_update,
                "isi",
                $form_id_area,
                $form_nama_jenis_mesin,
                $id
            );


            /* -------------------------------------------------
               JALANKAN UPDATE
            ------------------------------------------------- */

            if (mysqli_stmt_execute($stmt_update)) {

                /*
                 * affected_rows bisa 0 jika user menyimpan
                 * data yang sama persis.
                 *
                 * Jadi yang kita cek adalah execute berhasil.
                 */

                mysqli_stmt_close($stmt_update);

                /*
                 * Redirect setelah berhasil
                 */
                header(
                    "Location: jenis_mesin.php?updated=1"
                );

                exit;

            } else {

                $error =
                    "Data gagal diperbarui. Error database: "
                    . mysqli_stmt_error($stmt_update);

                mysqli_stmt_close($stmt_update);
            }
        }
    }
}


/* =========================================================
   AMBIL DATA AREA
========================================================= */

$q_area = mysqli_query(
    $conn,
    "SELECT
        id,
        nama_area,
        lokasi
     FROM area_bagian
     ORDER BY nama_area ASC"
);

if (!$q_area) {

    $error_area =
        "Data Area Bagian gagal dimuat: "
        . mysqli_error($conn);

} else {

    $error_area = "";
}


/* =========================================================
   HEADER
========================================================= */

include "../template/header.php";

?>

<style>

/* =========================================================
   PAGE
========================================================= */

.edit-jenis-page {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}


/* =========================================================
   HEADER
========================================================= */

.edit-page-header {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 18px 20px;
    margin-bottom: 18px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
}

.edit-page-header-inner {
    display: flex;
    align-items: center;
    gap: 14px;
}

.edit-back-btn {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 11px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #dbe2ea;
    background: #f8fafc;
    color: #475569;
    text-decoration: none;
    transition: .2s ease;
}

.edit-back-btn:hover {
    background: #005baa;
    border-color: #005baa;
    color: #ffffff;
    transform: translateX(-2px);
}

.edit-page-title {
    font-size: 21px;
    font-weight: 800;
    color: #172033;
    margin: 0;
}

.edit-page-subtitle {
    color: #94a3b8;
    font-size: 12px;
    margin-top: 3px;
}


/* =========================================================
   FORM CARD
========================================================= */

.edit-form-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .035);
}

.edit-form-header {
    padding: 17px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 12px;
}

.edit-form-header-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #eef5ff;
    color: #005baa;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.edit-form-title {
    font-size: 15px;
    font-weight: 800;
    color: #172033;
    margin: 0;
}

.edit-form-subtitle {
    color: #94a3b8;
    font-size: 11px;
    margin-top: 2px;
}


/* =========================================================
   BODY
========================================================= */

.edit-form-body {
    padding: 22px;
}


/* =========================================================
   ALERT ERROR
========================================================= */

.edit-error {
    border: 0;
    border-left: 4px solid #dc3545;
    background: #fff5f5;
    color: #842029;
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 13px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 9px;
}


/* =========================================================
   FORM GROUP
========================================================= */

.form-section {
    margin-bottom: 20px;
}

.form-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 800;
    color: #005baa;
    text-transform: uppercase;
    letter-spacing: .4px;
    margin-bottom: 14px;
}

.form-section-title i {
    font-size: 15px;
}

.form-label-custom {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #334155;
    margin-bottom: 6px;
}

.required {
    color: #dc3545;
}

.form-control-custom,
.form-select-custom {
    width: 100%;
    min-height: 42px;
    border: 1px solid #dbe2ea;
    border-radius: 9px;
    padding: 9px 12px;
    font-size: 13px;
    color: #172033;
    background: #ffffff;
    outline: none;
    transition: .2s ease;
}

.form-control-custom:focus,
.form-select-custom:focus {
    border-color: #005baa;
    box-shadow: 0 0 0 3px rgba(0, 91, 170, .10);
}

.form-help {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 6px;
    line-height: 1.5;
}


/* =========================================================
   INFO AREA
========================================================= */

.selected-area-info {
    margin-top: 10px;
    padding: 10px 12px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 9px;
    font-size: 11px;
    color: #64748b;
}

.selected-area-info strong {
    color: #334155;
}


/* =========================================================
   ACTION
========================================================= */

.form-actions {
    border-top: 1px solid #e5e7eb;
    padding-top: 18px;
    margin-top: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.form-actions-left {
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-update {
    min-height: 42px;
    padding: 9px 18px;
    border: 0;
    border-radius: 9px;
    background: #005baa;
    color: #ffffff;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    transition: .2s ease;
    cursor: pointer;
}

.btn-update:hover {
    background: #004987;
    color: #ffffff;
    transform: translateY(-1px);
}

.btn-update:disabled {
    opacity: .7;
    cursor: not-allowed;
    transform: none;
}

.btn-cancel {
    min-height: 42px;
    padding: 9px 18px;
    border: 1px solid #dbe2ea;
    border-radius: 9px;
    background: #ffffff;
    color: #64748b;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: .2s ease;
}

.btn-cancel:hover {
    background: #f8fafc;
    color: #334155;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 768px) {

    .edit-page-header {
        padding: 15px;
        border-radius: 13px;
    }

    .edit-page-title {
        font-size: 18px;
    }

    .edit-page-subtitle {
        font-size: 11px;
    }

    .edit-form-body {
        padding: 16px;
    }

    .edit-form-header {
        padding: 14px 16px;
    }

    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .form-actions-left {
        width: 100%;
    }

    .btn-update,
    .btn-cancel {
        flex: 1;
        width: 100%;
    }
}


@media (max-width: 576px) {

    .edit-jenis-page {
        padding-bottom: 10px;
    }

    .edit-page-header {
        margin-bottom: 12px;
        padding: 13px;
        border-radius: 12px;
    }

    .edit-page-header-inner {
        gap: 10px;
    }

    .edit-back-btn {
        width: 38px;
        height: 38px;
        min-width: 38px;
    }

    .edit-page-title {
        font-size: 16px;
    }

    .edit-page-subtitle {
        font-size: 10px;
    }

    .edit-form-card {
        border-radius: 12px;
    }

    .edit-form-header {
        padding: 13px;
        gap: 9px;
    }

    .edit-form-header-icon {
        width: 36px;
        height: 36px;
        min-width: 36px;
        font-size: 16px;
    }

    .edit-form-title {
        font-size: 13px;
    }

    .edit-form-subtitle {
        font-size: 10px;
    }

    .edit-form-body {
        padding: 13px;
    }

    .form-section {
        margin-bottom: 17px;
    }

    .form-section-title {
        font-size: 11px;
        margin-bottom: 12px;
    }

    .form-label-custom {
        font-size: 11px;
    }

    .form-control-custom,
    .form-select-custom {
        min-height: 40px;
        font-size: 12px;
        padding: 8px 10px;
    }

    .form-help {
        font-size: 10px;
    }

    .selected-area-info {
        font-size: 10px;
    }

    .form-actions-left {
        flex-direction: column;
        width: 100%;
    }

    .btn-update,
    .btn-cancel {
        min-height: 40px;
        font-size: 12px;
        width: 100%;
    }
}

</style>


<div class="container-fluid p-0 edit-jenis-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="edit-page-header">

        <div class="edit-page-header-inner">

            <a
                href="jenis_mesin.php"
                class="edit-back-btn"
                title="Kembali"
            >
                <i class="bi bi-arrow-left"></i>
            </a>

            <div>

                <h2 class="edit-page-title">
                    Edit Jenis Mesin
                </h2>

                <div class="edit-page-subtitle">
                    Perbarui informasi kategori atau jenis mesin
                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <div class="edit-form-card">


        <!-- HEADER -->

        <div class="edit-form-header">

            <div class="edit-form-header-icon">
                <i class="bi bi-pencil-square"></i>
            </div>

            <div>

                <h5 class="edit-form-title">
                    Form Edit Jenis Mesin
                </h5>

                <div class="edit-form-subtitle">
                    Silakan perbarui data jenis mesin di bawah ini.
                </div>

            </div>

        </div>


        <!-- BODY -->

        <div class="edit-form-body">


            <!-- =================================================
                 ERROR
            ================================================== -->

            <?php if ($error !== ""): ?>

                <div class="edit-error">

                    <i class="bi bi-exclamation-triangle-fill"></i>

                    <div>
                        <?= htmlspecialchars($error) ?>
                    </div>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 ERROR AREA
            ================================================== -->

            <?php if (!empty($error_area)): ?>

                <div class="edit-error">

                    <i class="bi bi-exclamation-triangle-fill"></i>

                    <div>
                        <?= htmlspecialchars($error_area) ?>
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
                id="editJenisForm"
            >


                <!-- =================================================
                     INFORMASI JENIS MESIN
                ================================================== -->

                <div class="form-section">

                    <div class="form-section-title">

                        <i class="bi bi-diagram-3"></i>

                        Informasi Jenis Mesin

                    </div>


                    <div class="row g-3">


                        <!-- AREA -->

                        <div class="col-12 col-md-6">

                            <label
                                for="id_area"
                                class="form-label-custom"
                            >

                                Area Bagian

                                <span class="required">*</span>

                            </label>


                            <select
                                name="id_area"
                                id="id_area"
                                class="form-select-custom"
                                required
                            >

                                <option value="">
                                    -- Pilih Area Bagian --
                                </option>


                                <?php if ($q_area && mysqli_num_rows($q_area) > 0): ?>

                                    <?php while ($a = mysqli_fetch_assoc($q_area)): ?>

                                        <option
                                            value="<?= (int) $a['id'] ?>"
                                            <?= (
                                                $form_id_area == (int) $a['id']
                                            )
                                                ? 'selected'
                                                : ''
                                            ?>
                                        >

                                            <?= htmlspecialchars(
                                                $a['nama_area']
                                            ) ?>

                                            <?php if (!empty($a['lokasi'])): ?>

                                                —
                                                <?= htmlspecialchars(
                                                    $a['lokasi']
                                                ) ?>

                                            <?php endif; ?>

                                        </option>

                                    <?php endwhile; ?>

                                <?php endif; ?>

                            </select>


                            <div class="form-help">

                                Pilih area atau bagian pabrik tempat
                                jenis mesin berada.

                            </div>


                            <div
                                id="area-info"
                                class="selected-area-info"
                            >

                                <i class="bi bi-info-circle me-1"></i>

                                Pastikan area yang dipilih sudah sesuai.

                            </div>

                        </div>


                        <!-- NAMA JENIS MESIN -->

                        <div class="col-12 col-md-6">

                            <label
                                for="nama_jenis_mesin"
                                class="form-label-custom"
                            >

                                Nama Jenis Mesin

                                <span class="required">*</span>

                            </label>


                            <input
                                type="text"
                                name="nama_jenis_mesin"
                                id="nama_jenis_mesin"
                                class="form-control-custom"
                                placeholder="Contoh: Conveyor, Mixer, Filling Machine"
                                value="<?= htmlspecialchars(
                                    $form_nama_jenis_mesin
                                ) ?>"
                                maxlength="150"
                                required
                            >


                            <div class="form-help">

                                Masukkan nama kategori atau kelompok
                                jenis mesin.

                            </div>

                        </div>


                    </div>

                </div>


                <!-- =================================================
                     ACTION
                ================================================== -->

                <div class="form-actions">


                    <div class="form-actions-left">


                        <button
                            type="submit"
                            name="update"
                            value="1"
                            class="btn-update"
                            id="btn-update"
                        >

                            <i class="bi bi-check-lg"></i>

                            <span>
                                Perbarui Data
                            </span>

                        </button>


                        <a
                            href="jenis_mesin.php"
                            class="btn-cancel"
                        >

                            <i class="bi bi-x-lg me-1"></i>

                            Batal

                        </a>


                    </div>


                    <div class="text-muted small d-none d-md-block">

                        <i class="bi bi-shield-check me-1"></i>

                        Data akan disimpan ke database

                    </div>


                </div>


            </form>

        </div>

    </div>

</div>


<script>

/* =========================================================
   AREA INFO
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const areaSelect =
            document.getElementById("id_area");

        const areaInfo =
            document.getElementById("area-info");

        if (!areaSelect || !areaInfo) {
            return;
        }


        function updateAreaInfo() {

            const option =
                areaSelect.options[
                    areaSelect.selectedIndex
                ];


            if (!option || !option.value) {

                areaInfo.innerHTML =
                    '<i class="bi bi-info-circle me-1"></i>' +
                    'Pastikan area yang dipilih sudah sesuai.';

                return;
            }


            const text =
                option.textContent.trim();


            areaInfo.innerHTML =
                '<i class="bi bi-geo-alt me-1"></i>' +
                '<strong>Area dipilih:</strong> ' +
                escapeHtml(text);
        }


        function escapeHtml(text) {

            const div =
                document.createElement("div");

            div.textContent = text;

            return div.innerHTML;
        }


        areaSelect.addEventListener(
            "change",
            updateAreaInfo
        );


        updateAreaInfo();

    }
);


/* =========================================================
   PREVENT DOUBLE SUBMIT
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const form =
            document.getElementById(
                "editJenisForm"
            );

        const button =
            document.getElementById(
                "btn-update"
            );


        if (!form || !button) {
            return;
        }


        form.addEventListener(
            "submit",
            function () {

                /*
                 * Jangan mencegah submit pertama.
                 */

                if (
                    form.dataset.submitted === "1"
                ) {

                    return;

                }


                form.dataset.submitted = "1";

                button.disabled = true;

                button.innerHTML =
                    '<span class="spinner-border spinner-border-sm"></span>' +
                    '<span>Menyimpan...</span>';

            }
        );

    }
);

</script>


<?php

include "../template/footer.php";

?>