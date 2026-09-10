<?php
include "koneksi.php";
include "template/header.php";

// 1. Query Statistik Card
$q_total_mesin = mysqli_query($conn, "SELECT COUNT(*) AS total FROM mesin");
$d_total_mesin = mysqli_fetch_assoc($q_total_mesin)['total'];

$q_total_komponen = mysqli_query($conn, "SELECT COUNT(*) AS total FROM komponen");
$d_total_komponen = mysqli_fetch_assoc($q_total_komponen)['total'];

// Komponen Rusak / Perlu Perhatian
$q_komponen_perhatian = mysqli_query($conn, "SELECT COUNT(*) AS total FROM komponen WHERE kondisi != 'Baik'");
$d_komponen_perhatian = mysqli_fetch_assoc($q_komponen_perhatian)['total'];

// Maintenance Bulan Ini
$bulan_ini = date('Y-m');
$q_maint_bulan_ini = mysqli_query($conn, "SELECT COUNT(*) AS total FROM riwayat_maintenance WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'");
$d_maint_bulan_ini = mysqli_fetch_assoc($q_maint_bulan_ini)['total'];

// 2. Query Riwayat Maintenance Terbaru (Top 5)
$q_maintenance = mysqli_query($conn, "
    SELECT rm.*, k.nama_bagian, m.nama_mesin 
    FROM riwayat_maintenance rm
    LEFT JOIN komponen k ON rm.id_komponen = k.id
    LEFT JOIN mesin m ON k.id_mesin = m.id
    ORDER BY rm.tanggal DESC, rm.id DESC
    LIMIT 5
");

// 3. Query Komponen Bermasalah (Perlu Pemeriksaan / Dalam Perbaikan)
$q_komponen_krusial = mysqli_query($conn, "
    SELECT k.*, m.nama_mesin 
    FROM komponen k
    LEFT JOIN mesin m ON k.id_mesin = m.id
    WHERE k.kondisi != 'Baik'
    ORDER BY k.id DESC
    LIMIT 5
");
?>

<div class="container-fluid p-0">

  <!-- Header Section -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="fw-bold text-dark m-0">Dashboard System</h3>
      <p class="text-muted small m-0">Ringkasan inventaris mesin dan pemeliharaan sistem pabrik</p>
    </div>
    <div class="text-end">
      <span class="badge bg-light text-dark border px-3 py-2 fw-normal">
        <i class="bi bi-calendar3 me-1"></i> <?= date('d F Y') ?>
      </span>
    </div>
  </div>

  <!-- Stat Cards Section -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="content-card p-3 border-start border-4 border-primary">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <span class="text-muted small fw-semibold d-block">TOTAL MESIN</span>
            <h3 class="fw-bold text-dark m-0 mt-1"><?= number_format($d_total_mesin) ?></h3>
          </div>
          <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
            <i class="bi bi-gear-wide-connected fs-4"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="content-card p-3 border-start border-4 border-info">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <span class="text-muted small fw-semibold d-block">TOTAL KOMPONEN</span>
            <h3 class="fw-bold text-dark m-0 mt-1"><?= number_format($d_total_komponen) ?></h3>
          </div>
          <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
            <i class="bi bi-cpu fs-4"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="content-card p-3 border-start border-4 border-warning">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <span class="text-muted small fw-semibold d-block">PERLU PERHATIAN</span>
            <h3 class="fw-bold text-dark m-0 mt-1"><?= number_format($d_komponen_perhatian) ?></h3>
          </div>
          <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
            <i class="bi bi-exclamation-triangle fs-4"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="content-card p-3 border-start border-4 border-success">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <span class="text-muted small fw-semibold d-block">MAINTENANCE BULAN INI</span>
            <h3 class="fw-bold text-dark m-0 mt-1"><?= number_format($d_maint_bulan_ini) ?></h3>
          </div>
          <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
            <i class="bi bi-tools fs-4"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Action / Shortcut Buttons -->
  <div class="content-card p-3 mb-4 bg-light border-0">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div class="fw-bold text-dark">
        <i class="bi bi-lightning-charge text-warning me-1"></i> Aksi Cepat:
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="mesin/tambah.php" class="btn btn-sm btn-outline-primary bg-white">
          <i class="bi bi-plus-lg me-1"></i> Tambah Mesin
        </a>
        <a href="komponen/tambah.php" class="btn btn-sm btn-outline-primary bg-white">
          <i class="bi bi-plus-lg me-1"></i> Tambah Komponen
        </a>
        <a href="maintenance/tambah.php" class="btn btn-sm btn-primary" style="background-color: var(--primary-blue); border: none;">
          <i class="bi bi-tools me-1"></i> Catat Maintenance Baru
        </a>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Maintenance Terbaru -->
    <div class="col-lg-7">
      <div class="content-card h-100">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
          <h2 class="card-title-custom"><i class="bi bi-clock-history"></i> Maintenance Terbaru</h2>
          <a href="maintenance/index.php" class="btn btn-sm btn-link text-decoration-none p-0">Lihat Semua →</a>
        </div>
        <div class="p-3">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Tanggal</th>
                  <th>Komponen</th>
                  <th>Tindakan</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($q_maintenance) > 0) : ?>
                  <?php while ($m = mysqli_fetch_assoc($q_maintenance)) : 
                    $badgeStatus = 'bg-success';
                    if ($m['status'] == 'Proses') $badgeStatus = 'bg-warning text-dark';
                    if ($m['status'] == 'Pending') $badgeStatus = 'bg-danger';
                  ?>
                    <tr>
                      <td><small class="fw-semibold text-dark"><?= date('d/m/Y', strtotime($m['tanggal'])) ?></small></td>
                      <td>
                        <strong class="text-dark d-block"><?= htmlspecialchars($m['nama_bagian'] ?: '-') ?></strong>
                        <small class="text-muted"><?= htmlspecialchars($m['nama_mesin'] ?: '-') ?></small>
                      </td>
                      <td><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($m['tindakan'], 0, 35, "...")) ?></small></td>
                      <td><span class="badge <?= $badgeStatus ?>"><?= htmlspecialchars($m['status']) ?></span></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else : ?>
                  <tr>
                    <td colspan="4" class="text-center text-muted py-3">Belum ada riwayat maintenance.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Komponen Perlu Perhatian -->
    <div class="col-lg-5">
      <div class="content-card h-100">
        <div class="card-header-custom d-flex justify-content-between align-items-center">
          <h2 class="card-title-custom"><i class="bi bi-exclamation-octagon text-danger"></i> Komponen Non-Baik</h2>
          <a href="komponen/index.php?kondisi=Dalam+Perbaikan" class="btn btn-sm btn-link text-decoration-none p-0">Detail →</a>
        </div>
        <div class="p-3">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Komponen</th>
                  <th>Lokasi / Mesin</th>
                  <th>Kondisi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (mysqli_num_rows($q_komponen_krusial) > 0) : ?>
                  <?php while ($k = mysqli_fetch_assoc($q_komponen_krusial)) : 
                    $badgeKondisi = ($k['kondisi'] == 'Dalam Perbaikan') ? 'bg-danger' : 'bg-warning text-dark';
                  ?>
                    <tr>
                      <td>
                        <strong class="text-dark d-block"><?= htmlspecialchars($k['nama_bagian']) ?></strong>
                        <small class="text-muted">PN: <?= htmlspecialchars($k['part_number'] ?: '-') ?></small>
                      </td>
                      <td><small class="text-dark"><?= htmlspecialchars($k['nama_mesin'] ?: '-') ?></small></td>
                      <td><span class="badge <?= $badgeKondisi ?>"><?= htmlspecialchars($k['kondisi']) ?></span></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else : ?>
                  <tr>
                    <td colspan="3" class="text-center text-success py-3">
                      <i class="bi bi-check-circle fs-4 d-block mb-1"></i>
                      Semua komponen dalam kondisi baik.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<?php include "template/footer.php"; ?>