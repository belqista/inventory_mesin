<?php
require_once __DIR__ . '/../template/auth.php';
require_user_role();

include "../koneksi.php";

$error = "";
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

/* =========================================================
   VALIDASI ID
========================================================= */

if ($id <= 0) {
    header("Location: area.php");
    exit;
}


/* =========================================================
   AMBIL DATA AREA
========================================================= */

$stmt_get = mysqli_prepare(
    $conn,
    "SELECT id, nama_area, lokasi
     FROM area_bagian
     WHERE id = ?"
);

if (!$stmt_get) {
    die("Query gagal dipersiapkan: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt_get,
    "i",
    $id
);

mysqli_stmt_execute($stmt_get);

$result = mysqli_stmt_get_result($stmt_get);

$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt_get);


/* =========================================================
   DATA TIDAK DITEMUKAN
========================================================= */

if (!$data) {
    header("Location: area.php");
    exit;
}


/* =========================================================
   PROSES UPDATE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $nama_area = trim($_POST['nama_area'] ?? '');
    $lokasi    = trim($_POST['lokasi'] ?? '');


    /* =====================================================
       VALIDASI
    ===================================================== */

    if ($nama_area === '') {

        $error = "Nama Area / Bagian wajib diisi.";

    } elseif (mb_strlen($nama_area) > 255) {

        $error = "Nama Area / Bagian terlalu panjang.";

    } elseif (mb_strlen($lokasi) > 255) {

        $error = "Lokasi area terlalu panjang.";

    }


    /* =====================================================
       UPDATE DATABASE
    ===================================================== */

    if ($error === '') {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE area_bagian
             SET nama_area = ?, lokasi = ?
             WHERE id = ?"
        );

        if (!$stmt) {

            $error =
                "Query update gagal dipersiapkan: "
                . mysqli_error($conn);

        } else {

            mysqli_stmt_bind_param(
                $stmt,
                "ssi",
                $nama_area,
                $lokasi,
                $id
            );


            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                header("Location: area.php");
                exit;

            } else {

                $error =
                    "Gagal memperbarui data: "
                    . mysqli_stmt_error($stmt);

                mysqli_stmt_close($stmt);
            }
        }
    }
}


/* =========================================================
   NILAI FORM
========================================================= */

$form_nama_area = isset($_POST['nama_area'])
    ? $_POST['nama_area']
    : ($data['nama_area'] ?? '');

$form_lokasi = isset($_POST['lokasi'])
    ? $_POST['lokasi']
    : ($data['lokasi'] ?? '');


/* =========================================================
   HEADER
========================================================= */

include "../template/header.php";

?>


<style>

/* =========================================================
   PAGE
========================================================= */

.area-edit-page {
    width: 100%;
    max-width: 100%;
}


/* =========================================================
   HEADER
========================================================= */

.area-edit-header {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 18px 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
}


.area-edit-header-inner {
    display: flex;
    align-items: center;
    gap: 14px;
}


.area-back-button {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 11px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    background: #f8fafc;
    border: 1px solid #e2e8f0;

    color: #475569;
    text-decoration: none;

    transition: all .2s ease;
}


.area-back-button:hover {
    background: #005baa;
    border-color: #005baa;
    color: #ffffff;
    transform: translateX(-2px);
}


.area-edit-title {
    font-size: 21px;
    font-weight: 800;
    color: #172033;
    margin: 0;
    line-height: 1.3;
}


.area-edit-subtitle {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 4px;
}


/* =========================================================
   FORM CARD
========================================================= */

.area-form-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    overflow: hidden;

    box-shadow:
        0 2px 8px rgba(15, 23, 42, .035);
}


.area-form-header {
    padding: 17px 20px;

    border-bottom: 1px solid #e5e7eb;

    display: flex;
    align-items: center;
    gap: 10px;
}


.area-form-header-icon {
    width: 38px;
    height: 38px;

    border-radius: 10px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #eef5ff;
    color: #005baa;

    font-size: 17px;
}


.area-form-title {
    font-size: 15px;
    font-weight: 800;
    color: #172033;
    margin: 0;
}


.area-form-subtitle {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 2px;
}


/* =========================================================
   FORM BODY
========================================================= */

.area-form-body {
    padding: 22px;
}


/* =========================================================
   ALERT
========================================================= */

.area-error {
    border: 0;
    border-left: 4px solid #dc3545;

    background: #fff5f5;
    color: #842029;

    border-radius: 10px;

    padding: 12px 14px;

    font-size: 13px;

    margin-bottom: 20px;
}


/* =========================================================
   FORM LABEL
========================================================= */

.area-form-label {
    display: block;

    font-size: 12px;
    font-weight: 700;

    color: #172033;

    margin-bottom: 7px;
}


.area-required {
    color: #dc3545;
}


.area-form-control {
    min-height: 42px;

    border-radius: 10px;

    border: 1px solid #dbe2ea;

    color: #172033;

    font-size: 13px;

    transition: all .2s ease;
}


.area-form-control:focus {
    border-color: #005baa;

    box-shadow:
        0 0 0 3px rgba(0, 91, 170, .10);
}


.area-form-help {
    margin-top: 6px;

    font-size: 11px;

    color: #94a3b8;

    line-height: 1.5;
}


/* =========================================================
   FORM SECTION
========================================================= */

.area-form-section {
    margin-bottom: 22px;
}


.area-form-section:last-child {
    margin-bottom: 0;
}


/* =========================================================
   BUTTON AREA
========================================================= */

.area-form-actions {
    margin-top: 24px;

    padding-top: 18px;

    border-top: 1px solid #e5e7eb;

    display: flex;

    align-items: center;

    justify-content: flex-end;

    gap: 9px;
}


.area-btn {
    min-height: 40px;

    padding: 8px 18px;

    border-radius: 9px;

    font-size: 13px;

    font-weight: 700;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 6px;
}


.area-btn-primary {
    background: #005baa;

    border-color: #005baa;

    color: #ffffff;
}


.area-btn-primary:hover {
    background: #004b8d;

    border-color: #004b8d;

    color: #ffffff;
}


.area-btn-cancel {
    background: #ffffff;

    border: 1px solid #dbe2ea;

    color: #64748b;
}


.area-btn-cancel:hover {
    background: #f8fafc;

    border-color: #cbd5e1;

    color: #334155;
}


/* =========================================================
   INFO BOX
========================================================= */

.area-info-box {
    background: #f8fafc;

    border: 1px solid #e5e7eb;

    border-radius: 11px;

    padding: 13px 15px;

    margin-bottom: 20px;

    display: flex;

    align-items: flex-start;

    gap: 10px;
}


.area-info-icon {
    color: #005baa;

    font-size: 16px;

    margin-top: 1px;
}


.area-info-text {
    color: #64748b;

    font-size: 11px;

    line-height: 1.6;
}


.area-info-text strong {
    color: #334155;
}


/* =========================================================
   RESPONSIVE TABLET
========================================================= */

@media (max-width: 768px) {

    .area-edit-header {
        padding: 15px;
        border-radius: 13px;
    }


    .area-edit-title {
        font-size: 18px;
    }


    .area-edit-subtitle {
        font-size: 11px;
    }


    .area-form-header {
        padding: 15px;
    }


    .area-form-body {
        padding: 18px;
    }


    .area-form-actions {
        justify-content: stretch;
    }


    .area-btn {
        flex: 1;
    }

}


/* =========================================================
   RESPONSIVE MOBILE
========================================================= */

@media (max-width: 576px) {

    .area-edit-header {
        margin-bottom: 14px;
        padding: 13px;
    }


    .area-edit-header-inner {
        gap: 10px;
    }


    .area-back-button {
        width: 38px;
        height: 38px;
        min-width: 38px;
    }


    .area-edit-title {
        font-size: 16px;
    }


    .area-edit-subtitle {
        font-size: 10px;

        line-height: 1.5;
    }


    .area-form-card {
        border-radius: 13px;
    }


    .area-form-header {
        padding: 13px;
    }


    .area-form-header-icon {
        width: 34px;
        height: 34px;

        font-size: 15px;
    }


    .area-form-title {
        font-size: 14px;
    }


    .area-form-subtitle {
        font-size: 10px;
    }


    .area-form-body {
        padding: 15px;
    }


    .area-form-label {
        font-size: 11px;
    }


    .area-form-control {
        min-height: 40px;

        font-size: 12px;
    }


    .area-form-help {
        font-size: 10px;
    }


    .area-form-actions {
        flex-direction: column;

        gap: 8px;
    }


    .area-btn {
        width: 100%;
        flex: none;
    }


    .area-info-box {
        padding: 11px 12px;
    }

}


/* =========================================================
   EXTRA SMALL
========================================================= */

@media (max-width: 380px) {

    .area-edit-title {
        font-size: 15px;
    }


    .area-edit-subtitle {
        display: none;
    }


    .area-form-body {
        padding: 13px;
    }

}

</style>


<div class="container-fluid p-0 area-edit-page">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div class="area-edit-header">

        <div class="area-edit-header-inner">

            <a
                href="area.php"
                class="area-back-button"
                title="Kembali ke Data Area"
            >

                <i class="bi bi-arrow-left"></i>

            </a>


            <div>

                <h2 class="area-edit-title">

                    Edit Area Bagian

                </h2>


                <div class="area-edit-subtitle">

                    Perbarui informasi nama area atau lokasi
                    penempatan mesin.

                </div>

            </div>

        </div>

    </div>



    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <div class="area-form-card">


        <!-- FORM HEADER -->

        <div class="area-form-header">

            <div class="area-form-header-icon">

                <i class="bi bi-pencil-square"></i>

            </div>


            <div>

                <div class="area-form-title">

                    Form Edit Area

                </div>


                <div class="area-form-subtitle">

                    Ubah informasi area atau lokasi pabrik

                </div>

            </div>

        </div>



        <!-- FORM BODY -->

        <div class="area-form-body">


            <!-- ERROR -->

            <?php if (!empty($error)): ?>

                <div
                    class="area-error"
                    role="alert"
                >

                    <i class="bi bi-exclamation-triangle-fill me-2"></i>

                    <?= htmlspecialchars($error) ?>

                </div>

            <?php endif; ?>



            <!-- INFO -->

            <div class="area-info-box">

                <div class="area-info-icon">

                    <i class="bi bi-info-circle-fill"></i>

                </div>


                <div class="area-info-text">

                    <strong>Informasi:</strong>

                    Perubahan nama area atau lokasi akan
                    digunakan sebagai informasi penempatan
                    mesin pada sistem maintenance.

                </div>

            </div>



            <!-- FORM -->

            <form
                method="POST"
                action=""
                autocomplete="off"
            >


                <!-- =================================================
                     NAMA AREA
                ================================================== -->

                <div class="area-form-section">

                    <label
                        for="nama_area"
                        class="area-form-label"
                    >

                        Nama Area / Bagian

                        <span class="area-required">*</span>

                    </label>


                    <input
                        type="text"
                        name="nama_area"
                        id="nama_area"
                        class="form-control area-form-control"
                        placeholder="Contoh: Gedung A - Area Produksi"
                        value="<?= htmlspecialchars($form_nama_area) ?>"
                        maxlength="255"
                        required
                        autofocus
                    >


                    <div class="area-form-help">

                        Masukkan nama lokasi atau bagian pabrik
                        tempat mesin ditempatkan.

                    </div>

                </div>



                <!-- =================================================
                     LOKASI
                ================================================== -->

                <div class="area-form-section">

                    <label
                        for="lokasi"
                        class="area-form-label"
                    >

                        Lokasi Area

                    </label>


                    <input
                        type="text"
                        name="lokasi"
                        id="lokasi"
                        class="form-control area-form-control"
                        placeholder="Contoh: Lantai 2, Zona Barat / Sektor B"
                        value="<?= htmlspecialchars($form_lokasi) ?>"
                        maxlength="255"
                    >


                    <div class="area-form-help">

                        Tambahkan keterangan detail seperti
                        lantai, zona, sektor, gedung, atau posisi
                        spesifik area.

                    </div>

                </div>



                <!-- =================================================
                     ACTION
                ================================================== -->

                <div class="area-form-actions">


                    <a
                        href="area.php"
                        class="btn area-btn area-btn-cancel"
                    >

                        <i class="bi bi-x-lg"></i>

                        Batal

                    </a>


                    <button
                        type="submit"
                        name="update"
                        value="1"
                        class="btn area-btn area-btn-primary"
                    >

                        <i class="bi bi-check-lg"></i>

                        Perbarui Data

                    </button>


                </div>


            </form>

        </div>

    </div>

</div>


<?php include "../template/footer.php"; ?>