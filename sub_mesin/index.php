<?php
require_once __DIR__ . '/../template/auth.php';
require_authenticated();

include "../koneksi.php";

/* =========================================================
   SEARCH
========================================================= */

$keyword = isset($_GET['keyword'])
    ? trim($_GET['keyword'])
    : '';

/* =========================================================
   QUERY DATA SUB MESIN
========================================================= */

$sql = "
    SELECT
        sm.*,
        m.nama_mesin,
        m.serial_number AS sn_mesin_induk,

        (
            SELECT COUNT(*)
            FROM komponen k
            WHERE k.id_sub_mesin = sm.id
        ) AS total_komponen,

        (
            SELECT COUNT(*)
            FROM riwayat_maintenance rm
            INNER JOIN komponen k2
                ON rm.id_komponen = k2.id
            WHERE k2.id_sub_mesin = sm.id
        ) AS total_maintenance

    FROM sub_mesin sm

    LEFT JOIN mesin m
        ON sm.id_mesin = m.id
";

if ($keyword !== '') {

    $sql .= "
        WHERE
            sm.nama_sub_mesin LIKE ?
            OR sm.serial_number LIKE ?
            OR m.nama_mesin LIKE ?
            OR m.serial_number LIKE ?
    ";

    $sql .= " ORDER BY sm.id DESC";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die("Query gagal diproses.");
    }

    $kw = "%" . $keyword . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $kw,
        $kw,
        $kw,
        $kw
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

} else {

    $sql .= " ORDER BY sm.id DESC";

    $result = mysqli_query($conn, $sql);
}


/* =========================================================
   TOTAL DATA
========================================================= */

$total_sub_mesin = $result
    ? mysqli_num_rows($result)
    : 0;


/* =========================================================
   HEADER TEMPLATE
========================================================= */

include "../template/header.php";
?>


<style>

/* =========================================================
   PAGE
========================================================= */

.submachine-page {
    width: 100%;
    min-width: 0;
}


/* =========================================================
   TOP HEADER
========================================================= */

.submachine-topbar {
    position: relative;

    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 14px;

    padding: 18px 22px 18px 26px;

    box-shadow:
        0 3px 12px rgba(15, 23, 42, .04);

    margin-bottom: 20px;

    overflow: hidden;
}

.submachine-topbar::before {
    content: "";

    position: absolute;

    left: 0;
    top: 18px;
    bottom: 18px;

    width: 4px;

    background: linear-gradient(
        180deg,
        #005baa,
        #0076c8
    );

    border-radius: 0 5px 5px 0;
}


/* =========================================================
   PAGE TITLE
========================================================= */

.submachine-page-title {
    font-size: 24px;

    font-weight: 800;

    color: #172033;

    margin: 0;

    line-height: 1.3;
}

.submachine-page-subtitle {
    color: #64748b;

    font-size: 13px;

    margin: 4px 0 0;

    line-height: 1.5;
}


/* =========================================================
   ADD BUTTON
========================================================= */

.btn-add-submachine {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 4px;

    background: linear-gradient(
        135deg,
        #005baa,
        #0076c8
    );

    border: none;

    color: #fff;

    border-radius: 9px;

    padding: 9px 15px;

    font-size: 13px;

    font-weight: 600;

    white-space: nowrap;

    transition: all .2s ease;
}

.btn-add-submachine:hover {
    color: #fff;

    transform: translateY(-1px);

    box-shadow:
        0 7px 18px rgba(0, 91, 170, .20);
}


/* =========================================================
   MAIN CARD
========================================================= */

.submachine-card {
    background: #ffffff;

    border: 1px solid #e5e7eb;

    border-radius: 16px;

    overflow: hidden;

    box-shadow:
        0 3px 12px rgba(15, 23, 42, .04);
}


/* =========================================================
   CARD HEADER
========================================================= */

.submachine-card-header {
    padding: 17px 20px;

    border-bottom: 1px solid #e5e7eb;

    background: #ffffff;
}

.submachine-card-title {
    font-size: 15px;

    font-weight: 700;

    color: #172033;

    line-height: 1.4;
}

.submachine-card-subtitle {
    font-size: 11px;

    color: #94a3b8;

    margin-top: 3px;

    line-height: 1.5;
}


/* =========================================================
   SEARCH
========================================================= */

.submachine-search {
    width: 320px;

    max-width: 100%;

    position: relative;

    flex-shrink: 0;
}

.submachine-search input {
    width: 100%;

    height: 38px;

    border: 1px solid #dbe3ea;

    border-radius: 9px;

    padding: 8px 12px 8px 37px;

    font-size: 12px;

    color: #334155;

    outline: none;

    transition: all .2s ease;

    background: #fff;
}

.submachine-search input::placeholder {
    color: #94a3b8;
}

.submachine-search input:focus {
    border-color: #0076c8;

    box-shadow:
        0 0 0 3px rgba(0, 118, 200, .08);
}

.submachine-search i {
    position: absolute;

    left: 13px;

    top: 50%;

    transform: translateY(-50%);

    color: #94a3b8;

    font-size: 13px;

    pointer-events: none;
}


/* =========================================================
   TABLE WRAPPER
========================================================= */

.submachine-table-wrapper {
    width: 100%;

    overflow-x: auto;

    overflow-y: hidden;

    -webkit-overflow-scrolling: touch;
}

.submachine-table-wrapper::-webkit-scrollbar {
    height: 7px;
}

.submachine-table-wrapper::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.submachine-table-wrapper::-webkit-scrollbar-thumb {
    background: #cbd5e1;

    border-radius: 10px;
}

.submachine-table-wrapper::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}


/* =========================================================
   TABLE
========================================================= */

.submachine-table {
    width: 100%;

    min-width: 1120px;

    margin: 0;

    border-collapse: separate;

    border-spacing: 0;
}

.submachine-table thead th {
    background: #f8fafc;

    color: #475569;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .35px;

    padding: 12px 13px;

    border-bottom: 1px solid #e5e7eb;

    white-space: nowrap;

    vertical-align: middle;
}

.submachine-table tbody td {
    padding: 13px;

    border-bottom: 1px solid #f1f5f9;

    vertical-align: middle;

    font-size: 12px;

    color: #334155;

    background: #fff;
}

.submachine-table tbody tr {
    transition: background .15s ease;
}

.submachine-table tbody tr:hover td {
    background: #f8fbff;
}

.submachine-table tbody tr:last-child td {
    border-bottom: none;
}


/* =========================================================
   PHOTO
========================================================= */

.submachine-photo {
    width: 46px;

    height: 46px;

    object-fit: cover;

    border-radius: 9px;

    border: 1px solid #e2e8f0;

    background: #f8fafc;

    display: block;
}

.submachine-photo-placeholder {
    width: 46px;

    height: 46px;

    border-radius: 9px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eef5ff;

    border: 1px solid #d8eaff;

    color: #005baa;

    font-size: 18px;
}


/* =========================================================
   SUB MESIN NAME
========================================================= */

.submachine-name {
    font-weight: 700;

    color: #172033;

    font-size: 13px;

    line-height: 1.4;

    transition: color .15s;
}

.submachine-name:hover {
    color: #005baa;
}


/* =========================================================
   SERIAL NUMBER
========================================================= */

.submachine-sn {
    display: inline-block;

    background: #f1f5f9;

    border: 1px solid #e2e8f0;

    color: #334155;

    border-radius: 6px;

    padding: 4px 7px;

    font-size: 10px;

    font-weight: 600;

    font-family: monospace;

    white-space: nowrap;
}

.submachine-parent-sn {
    font-size: 10px;

    color: #94a3b8;

    margin-top: 5px;

    line-height: 1.4;
}


/* =========================================================
   PARENT MACHINE
========================================================= */

.parent-machine {
    display: inline-flex;

    align-items: flex-start;

    gap: 7px;

    color: #334155;

    text-decoration: none;

    font-weight: 600;

    line-height: 1.4;

    transition: color .15s;
}

.parent-machine:hover {
    color: #005baa;
}

.parent-machine i {
    color: #005baa;

    margin-top: 1px;

    flex-shrink: 0;
}


/* =========================================================
   STRUCTURE
========================================================= */

.submachine-stats {
    display: flex;

    align-items: center;

    gap: 5px;

    flex-wrap: wrap;
}

.submachine-stat {
    display: inline-flex;

    align-items: center;

    background: #f8fafc;

    border: 1px solid #e5e7eb;

    border-radius: 6px;

    padding: 4px 7px;

    font-size: 10px;

    color: #64748b;

    white-space: nowrap;
}

.submachine-stat strong {
    color: #334155;

    font-weight: 700;

    margin-left: 2px;
}


/* =========================================================
   DESCRIPTION
========================================================= */

.submachine-description {
    max-width: 220px;

    font-size: 11px;

    color: #64748b;

    line-height: 1.5;

    display: -webkit-box;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;

    overflow: hidden;

    word-break: break-word;
}


/* =========================================================
   ACTION BUTTONS
========================================================= */

.submachine-actions {
    display: flex;

    align-items: center;

    justify-content: center;

    gap: 5px;
}

.submachine-action {
    width: 33px;

    height: 33px;

    border-radius: 8px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    text-decoration: none;

    border: 1px solid transparent;

    transition: all .15s ease;

    font-size: 13px;

    flex-shrink: 0;
}


/* =========================================================
   DETAIL
========================================================= */

.submachine-action-detail {
    color: #005baa;

    background: #eef5ff;

    border-color: #d8eaff;
}

.submachine-action-detail:hover {
    background: #005baa;

    color: #fff;

    border-color: #005baa;
}


/* =========================================================
   EDIT
========================================================= */

.submachine-action-edit {
    color: #b76a00;

    background: #fff7e8;

    border-color: #ffe5b5;
}

.submachine-action-edit:hover {
    background: #f59e0b;

    color: #fff;

    border-color: #f59e0b;
}


/* =========================================================
   DELETE
========================================================= */

.submachine-action-delete {
    color: #dc2626;

    background: #fff1f2;

    border-color: #ffe0e3;
}

.submachine-action-delete:hover {
    background: #dc2626;

    color: #fff;

    border-color: #dc2626;
}


/* =========================================================
   FOOTER
========================================================= */

.submachine-card-footer {
    padding: 11px 20px;

    background: #f8fafc;

    border-top: 1px solid #e5e7eb;

    color: #64748b;

    font-size: 10px;

    line-height: 1.5;
}

.submachine-card-footer strong {
    color: #334155;
}


/* =========================================================
   RESET SEARCH
========================================================= */

.submachine-reset {
    color: #005baa;

    text-decoration: none;

    font-weight: 600;

    white-space: nowrap;
}

.submachine-reset:hover {
    color: #003f78;

    text-decoration: underline;
}


/* =========================================================
   EMPTY STATE
========================================================= */

.submachine-empty {
    padding: 60px 20px;

    text-align: center;

    color: #94a3b8;
}

.submachine-empty-icon {
    width: 70px;

    height: 70px;

    margin: 0 auto 15px;

    border-radius: 18px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #f1f5f9;

    color: #94a3b8;

    font-size: 29px;
}

.submachine-empty-title {
    font-size: 15px;

    font-weight: 700;

    color: #475569;

    line-height: 1.4;
}

.submachine-empty-text {
    font-size: 12px;

    margin-top: 5px;

    line-height: 1.6;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 768px) {

    .submachine-topbar {
        padding: 15px 15px 15px 20px;

        border-radius: 12px;

        margin-bottom: 15px;
    }

    .submachine-topbar::before {
        top: 15px;

        bottom: 15px;
    }

    .submachine-page-title {
        font-size: 20px;
    }

    .submachine-page-subtitle {
        font-size: 11px;

        max-width: 100%;
    }

    .btn-add-submachine {
        width: 100%;

        min-height: 40px;
    }

    .submachine-card {
        border-radius: 13px;
    }

    .submachine-card-header {
        padding: 15px;
    }

    .submachine-search {
        width: 100%;
    }

    .submachine-search input {
        height: 40px;

        font-size: 12px;
    }

    .submachine-card-footer {
        padding: 12px 15px;
    }

    .submachine-card-footer .d-flex {
        align-items: flex-start !important;

        flex-direction: column;
    }

    .submachine-reset {
        display: inline-block;

        margin-top: 2px;
    }

    .submachine-empty {
        padding: 50px 15px;
    }
}


/* =========================================================
   SMALL MOBILE
========================================================= */

@media (max-width: 480px) {

    .submachine-page-title {
        font-size: 18px;
    }

    .submachine-page-subtitle {
        font-size: 10px;
    }

    .submachine-card-title {
        font-size: 14px;
    }

    .submachine-card-subtitle {
        font-size: 10px;
    }

    .submachine-empty-icon {
        width: 60px;

        height: 60px;

        font-size: 25px;
    }

    .submachine-empty-title {
        font-size: 14px;
    }

    .submachine-empty-text {
        font-size: 11px;
    }
}

</style>


<div class="container-fluid p-0 submachine-page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="submachine-topbar">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

            <div class="flex-grow-1">

                <h2 class="submachine-page-title">
                    Data Sub Mesin
                </h2>

                <div class="submachine-page-subtitle">
                    Kelola bagian atau sub-sistem dari setiap mesin produksi.
                </div>

            </div>


            <?php if (has_role('user')): ?>
<a
                href="tambah.php"
                class="btn btn-add-submachine"
            >
                <i class="bi bi-plus-lg"></i>
                Tambah Sub Mesin
            </a>
<?php endif; ?>

        </div>

    </div>


    <!-- =====================================================
         MAIN CARD
    ====================================================== -->

    <div class="submachine-card">


        <!-- =================================================
             CARD HEADER
        ================================================== -->

        <div class="submachine-card-header">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div class="flex-grow-1">

                    <div class="submachine-card-title">

                        <i class="bi bi-diagram-3 text-primary me-2"></i>

                        Daftar Sub Mesin

                    </div>

                    <div class="submachine-card-subtitle">

                        Daftar seluruh sub mesin yang terhubung dengan mesin induk.

                    </div>

                </div>


                <!-- SEARCH -->

                <form
                    method="GET"
                    class="submachine-search"
                    autocomplete="off"
                >

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        name="keyword"
                        value="<?= htmlspecialchars(
                            $keyword,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        placeholder="Cari sub mesin, serial, mesin..."
                        aria-label="Cari sub mesin"
                    >

                </form>

            </div>

        </div>


        <!-- =================================================
             DATA
        ================================================== -->

        <?php if ($total_sub_mesin > 0): ?>

            <div class="submachine-table-wrapper">

                <table class="submachine-table">

                    <thead>

                        <tr>

                            <th
                                width="55"
                                class="text-center"
                            >
                                No
                            </th>

                            <th
                                width="75"
                                class="text-center"
                            >
                                Foto
                            </th>

                            <th width="190">
                                Sub Mesin
                            </th>

                            <th width="170">
                                Serial Number
                            </th>

                            <th width="200">
                                Mesin Induk
                            </th>

                            <th width="180">
                                Struktur
                            </th>

                            <th width="220">
                                Keterangan
                            </th>

                            <th
                                width="125"
                                class="text-center"
                            >
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    $no = 1;

                    while ($d = mysqli_fetch_assoc($result)):

                        $sub_id = intval($d['id']);

                        $id_mesin = intval(
                            $d['id_mesin'] ?? 0
                        );

                        $nama_sub_mesin = trim(
                            $d['nama_sub_mesin'] ?? ''
                        );

                        $serial_sub_mesin = trim(
                            $d['serial_number'] ?? ''
                        );

                        $nama_mesin = trim(
                            $d['nama_mesin'] ?? ''
                        );

                        $sn_mesin_induk = trim(
                            $d['sn_mesin_induk'] ?? ''
                        );

                        $foto = trim(
                            $d['gambar'] ?? ''
                        );

                        $foto_path =
                            "../uploads/sub_mesin/" . $foto;

                        $has_foto =
                            $foto !== '' &&
                            file_exists($foto_path);

                        $keterangan = trim(
                            $d['keterangan'] ?? ''
                        );

                        $total_komponen = intval(
                            $d['total_komponen'] ?? 0
                        );

                        $total_maintenance = intval(
                            $d['total_maintenance'] ?? 0
                        );

                    ?>

                        <tr>


                            <!-- =================================================
                                 NO
                            ================================================== -->

                            <td class="text-center">

                                <span class="text-muted fw-semibold">
                                    <?= $no++ ?>
                                </span>

                            </td>


                            <!-- =================================================
                                 FOTO
                            ================================================== -->

                            <td class="text-center">

                                <a
                                    href="detail.php?id=<?= $sub_id ?>"
                                    class="text-decoration-none"
                                    title="Lihat Detail"
                                >

                                    <?php if ($has_foto): ?>

                                        <img
                                            src="<?= htmlspecialchars(
                                                $foto_path,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                            class="submachine-photo mx-auto"
                                            alt="Foto <?= htmlspecialchars(
                                                $nama_sub_mesin ?: 'Sub Mesin',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                        >

                                    <?php else: ?>

                                        <div class="submachine-photo-placeholder mx-auto">

                                            <i class="bi bi-diagram-3"></i>

                                        </div>

                                    <?php endif; ?>

                                </a>

                            </td>


                            <!-- =================================================
                                 NAMA SUB MESIN
                                 ID SUB MESIN DIHAPUS
                            ================================================== -->

                            <td>

                                <a
                                    href="detail.php?id=<?= $sub_id ?>"
                                    class="text-decoration-none"
                                >

                                    <div class="submachine-name">

                                        <?= htmlspecialchars(
                                            $nama_sub_mesin ?: '-',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </div>

                                </a>

                            </td>


                            <!-- =================================================
                                 SERIAL NUMBER
                            ================================================== -->

                            <td>

                                <span class="submachine-sn">

                                    <?= htmlspecialchars(
                                        $serial_sub_mesin ?: '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>


                                <?php if ($sn_mesin_induk !== ''): ?>

                                    <div class="submachine-parent-sn">

                                        <i class="bi bi-arrow-return-right me-1"></i>

                                        SN Mesin:

                                        <?= htmlspecialchars(
                                            $sn_mesin_induk,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- =================================================
                                 MESIN INDUK
                            ================================================== -->

                            <td>

                                <?php if (
                                    $nama_mesin !== '' &&
                                    $id_mesin > 0
                                ): ?>

                                    <a
                                        href="../mesin/detail.php?id=<?= $id_mesin ?>"
                                        class="parent-machine"
                                        title="Lihat Detail Mesin"
                                    >

                                        <i class="bi bi-gear-wide-connected"></i>

                                        <span>

                                            <?= htmlspecialchars(
                                                $nama_mesin,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </span>

                                    </a>

                                <?php else: ?>

                                    <span class="text-muted">

                                        <i class="bi bi-dash-circle me-1"></i>

                                        Tidak terkait

                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- =================================================
                                 STRUKTUR
                            ================================================== -->

                            <td>

                                <div class="submachine-stats">

                                    <span class="submachine-stat">

                                        <i class="bi bi-cpu me-1"></i>

                                        Komp.

                                        <strong>
                                            <?= $total_komponen ?>
                                        </strong>

                                    </span>


                                    <span class="submachine-stat">

                                        <i class="bi bi-tools me-1"></i>

                                        Maint.

                                        <strong>
                                            <?= $total_maintenance ?>
                                        </strong>

                                    </span>

                                </div>

                            </td>


                            <!-- =================================================
                                 KETERANGAN
                            ================================================== -->

                            <td>

                                <div class="submachine-description">

                                    <?php if ($keterangan !== ''): ?>

                                        <?= htmlspecialchars(
                                            $keterangan,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    <?php else: ?>

                                        <span class="text-muted">
                                            Tidak ada keterangan
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>


                            <!-- =================================================
                                 AKSI
                            ================================================== -->

                            <td>

                                <div class="submachine-actions">


                                    <!-- DETAIL -->

                                    <a
                                        href="detail.php?id=<?= $sub_id ?>"
                                        class="submachine-action submachine-action-detail"
                                        title="Lihat Detail"
                                        aria-label="Lihat Detail"
                                    >

                                        <i class="bi bi-eye"></i>

                                    </a>


                                    <!-- EDIT -->

                                    <?php if (has_role('user')): ?>
<a
                                        href="edit.php?id=<?= $sub_id ?>"
                                        class="submachine-action submachine-action-edit"
                                        title="Edit Sub Mesin"
                                        aria-label="Edit Sub Mesin"
                                    >

                                        <i class="bi bi-pencil-square"></i>

                                    </a>
<?php endif; ?>


                                    <!-- HAPUS -->

                                    <?php if (has_role('admin')): ?>
<a
                                        href="hapus.php?id=<?= $sub_id ?>"
                                        class="submachine-action submachine-action-delete"
                                        title="Hapus Sub Mesin"
                                        aria-label="Hapus Sub Mesin"
                                        onclick="return confirm(
                                            'Apakah Anda yakin ingin menghapus sub mesin <?= htmlspecialchars(
                                                $nama_sub_mesin,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>?'
                                        );"
                                    >

                                        <i class="bi bi-trash"></i>

                                    </a>
<?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>


            <!-- =================================================
                 FOOTER
            ================================================== -->

            <div class="submachine-card-footer">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                    <div>

                        <i class="bi bi-database me-1"></i>

                        Menampilkan

                        <strong>
                            <?= $total_sub_mesin ?>
                        </strong>

                        sub mesin

                        <?php if ($keyword !== ''): ?>

                            untuk pencarian:

                            <strong>
                                "<?= htmlspecialchars(
                                    $keyword,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                            </strong>

                        <?php endif; ?>

                    </div>


                    <?php if ($keyword !== ''): ?>

                        <a
                            href="index.php"
                            class="submachine-reset"
                        >

                            <i class="bi bi-x-circle me-1"></i>

                            Reset pencarian

                        </a>

                    <?php endif; ?>

                </div>

            </div>


        <?php else: ?>


            <!-- =================================================
                 EMPTY STATE
            ================================================== -->

            <div class="submachine-empty">

                <div class="submachine-empty-icon">

                    <?php if ($keyword !== ''): ?>

                        <i class="bi bi-search"></i>

                    <?php else: ?>

                        <i class="bi bi-diagram-3"></i>

                    <?php endif; ?>

                </div>


                <div class="submachine-empty-title">

                    <?php if ($keyword !== ''): ?>

                        Sub mesin tidak ditemukan

                    <?php else: ?>

                        Belum ada data sub mesin

                    <?php endif; ?>

                </div>


                <div class="submachine-empty-text">

                    <?php if ($keyword !== ''): ?>

                        Tidak ada sub mesin yang sesuai dengan pencarian

                        <strong>
                            "<?= htmlspecialchars(
                                $keyword,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        </strong>.

                    <?php else: ?>

                        Silakan tambahkan sub mesin terlebih dahulu.

                    <?php endif; ?>

                </div>


                <div class="mt-3">

                    <?php if ($keyword !== ''): ?>

                        <a
                            href="index.php"
                            class="btn btn-outline-primary btn-sm"
                        >

                            <i class="bi bi-arrow-counterclockwise me-1"></i>

                            Reset Pencarian

                        </a>

                    <?php else: ?>

                        <?php if (has_role('user')): ?>
<a
                            href="tambah.php"
                            class="btn btn-primary btn-sm"
                        >

                            <i class="bi bi-plus-lg me-1"></i>

                            Tambah Sub Mesin

                        </a>
<?php endif; ?>

                    <?php endif; ?>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>


<?php

/* =========================================================
   TUTUP STATEMENT SEARCH
========================================================= */

if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    mysqli_stmt_close($stmt);
}

include "../template/footer.php";

?>