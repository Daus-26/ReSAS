
<?php
session_start();

if (file_exists('../config/db.php')) {
    require_once '../config/db.php';
} else {
    require_once 'config/db.php';
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

function getFilePath($filename, $isProof = false) {
    if (empty($filename)) return '#';
    
    $cleanPath = ltrim($filename, '/');
    
    if (strpos($cleanPath, 'uploads/') === 0) {
        return '../' . $cleanPath;
    }
    
    if ($isProof) {
        if (strpos($cleanPath, 'proofs/') === 0) {
            return '../uploads/' . $cleanPath;
        }
        return '../uploads/proofs/' . $cleanPath;
    }
    
    return '../uploads/' . $cleanPath;
}

$status_filter = $_GET['status'] ?? 'ALL';
$search_query  = trim($_GET['search'] ?? '');

// Menyambungkan application, resident, vehicle, dan payment
$sql = "
    SELECT a.application_id, a.submission_date, a.status, a.remarks,
           a.grant_file, a.ic_file, a.residence_file,
           p.proof_of_payment, p.payment_id, p.payment_date, p.amount,
           r.resident_id, r.name AS resident_name, r.email, r.phone_number, r.address,
           v.vehicle_id, v.plate_number, v.brand, v.model, v.color, v.vehicle_type
    FROM application a
    JOIN resident r ON a.resident_id = r.resident_id
    JOIN vehicle v ON a.vehicle_id = v.vehicle_id
    LEFT JOIN payment p ON a.application_id = p.application_id
    WHERE 1=1
";

$params = [];
if ($status_filter !== 'ALL') {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
}
if (!empty($search_query)) {
    $sql .= " AND (v.plate_number LIKE ? OR r.name LIKE ? OR r.address LIKE ?)";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
}
$sql .= " ORDER BY a.submission_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengurusan Permohonan & Dokumen - ReSAS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; }
        .dashboard-wrapper { display: flex; min-height: 100vh; }
        .sidebar-panel { width: 270px; background-color: #0b2244; color: #ffffff; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-header { padding: 24px 20px; background-color: #0f2c59; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        .sidebar-brand { font-size: 1.4rem; font-weight: 800; color: #00d2ff; text-decoration: none; }
        .sidebar-menu { list-style: none; padding: 15px 10px; margin: 0; }
        .sidebar-menu li a { display: flex; align-items: center; padding: 12px 18px; color: #94a3b8; text-decoration: none; font-weight: 600; border-radius: 10px; margin-bottom: 4px; transition: all 0.2s ease; }
        .sidebar-menu li a:hover { background-color: rgba(255, 255, 255, 0.06); color: #ffffff; }
        .sidebar-menu li.active a { background-color: #0f2c59; color: #00d2ff; font-weight: 700; }
        .main-content { flex-grow: 1; padding: 35px; overflow-y: auto; }
        .top-bar { background-color: #ffffff; padding: 20px 28px; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .custom-card { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 25px; }
        .doc-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; height: 100%; display: flex; flex-direction: column; justify-content: space-between; }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <!-- Navigasi Sidebar -->
    <div class="sidebar-panel">
        <div>
            <div class="sidebar-header d-flex align-items-center gap-2">
                <a href="dashboard.php" class="sidebar-brand"><i class="bi bi-shield-check"></i> ReSAS Admin</a>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="bi bi-grid-1x2-fill me-3"></i>Dashboard</a></li>
                <li class="active"><a href="applications.php"><i class="bi bi-file-earmark-text-fill me-3"></i>Permohonan</a></li>
                <li><a href="registrations.php"><i class="bi bi-people-fill me-3"></i>Pendaftaran</a></li>
                <li><a href="reports.php"><i class="bi bi-bar-chart-line-fill me-3"></i>Laporan & Log</a></li>
            </ul>
        </div>
    </div>

    <!-- Kandungan Utama -->
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h5 class="fw-bold mb-1">Pengurusan Permohonan & Dokumen</h5>
                <small class="text-muted">Semakan lampiran fail permohonan dan resit bukti pembayaran penduduk</small>
            </div>
            <button type="button" onclick="confirmLogout()" class="btn btn-outline-danger fw-bold px-3 py-2 d-flex align-items-center gap-2" style="border-radius: 10px;">
                <i class="bi bi-box-arrow-right"></i> Log Keluar
            </button>
        </div>

        <div class="custom-card">
            <!-- Borang Tapis & Carian -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Cari No Plat / Nama Pemohon / Alamat..." value="<?= htmlspecialchars($search_query); ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="ALL" <?= $status_filter === 'ALL' ? 'selected' : ''; ?>>-- Semua Status --</option>
                        <option value="Dalam Proses" <?= $status_filter === 'Dalam Proses' ? 'selected' : ''; ?>>Dalam Proses</option>
                        <option value="Sudah Dibayar" <?= $status_filter === 'Sudah Dibayar' ? 'selected' : ''; ?>>Sudah Dibayar</option>
                        <option value="Diluluskan" <?= $status_filter === 'Diluluskan' ? 'selected' : ''; ?>>Diluluskan</option>
                        <option value="Ditolak" <?= $status_filter === 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold" style="background-color: #0f2c59;">
                        <i class="bi bi-funnel-fill me-1"></i> Tapis
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="applications.php" class="btn btn-outline-secondary w-100 fw-bold">Reset</a>
                </div>
            </form>

            <!-- Jadual Permohonan -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>#ID</th>
                            <th>Pemohon</th>
                            <th>Kenderaan</th>
                            <th>Lampiran Dokumen</th>
                            <th>Status</th>
                            <th>Tarikh Hantar</th>
                            <th class="text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">Tiada permohonan ditemui.</td></tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app): ?>
                                <tr>
                                    <td class="fw-bold">#<?= $app['application_id']; ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($app['resident_name']); ?></div>
                                        <small class="text-muted"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($app['address']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark font-monospace mb-1"><?= htmlspecialchars($app['plate_number']); ?></span>
                                        <div class="small fw-semibold text-secondary"><?= htmlspecialchars($app['brand'] . ' ' . $app['model']); ?></div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info fw-bold position-relative" data-bs-toggle="modal" data-bs-target="#filesModal<?= $app['application_id']; ?>">
                                            <i class="bi bi-folder-symlink me-1"></i> Lihat Dokumen
                                            <?php if (!empty($app['proof_of_payment'])): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-success border border-light rounded-circle" title="Resit Bayaran Ada"></span>
                                            <?php endif; ?>
                                        </button>
                                    </td>
                                    <td>
                                        <?php 
                                            $st = strtolower($app['status']);
                                            if ($st == 'diluluskan') echo '<span class="badge bg-success-subtle text-success border px-2 py-1">Diluluskan</span>';
                                            elseif ($st == 'ditolak') echo '<span class="badge bg-danger-subtle text-danger border px-2 py-1">Ditolak</span>';
                                            elseif ($st == 'sudah dibayar') echo '<span class="badge bg-info-subtle text-info border px-2 py-1">Sudah Dibayar</span>';
                                            else echo '<span class="badge bg-warning-subtle text-warning-emphasis border px-2 py-1">'.$app['status'].'</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <div class="small fw-bold text-dark"><?= date('d/m/Y', strtotime($app['submission_date'])); ?></div>
                                        <small class="text-muted"><?= date('h:i A', strtotime($app['submission_date'])); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary fw-bold" style="background-color: #0f2c59;" data-bs-toggle="modal" data-bs-target="#updateModal<?= $app['application_id']; ?>">
                                            <i class="bi bi-pencil-square me-1"></i> Kemaskini
                                        </button>
                                    </td>
                                </tr>

                                <!-- Modal Lampiran Fail -->
                                <div class="modal fade" id="filesModal<?= $app['application_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered modal-lg">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold"><i class="bi bi-folder2-open me-2 text-primary"></i>Fail Lampiran Permohonan #<?= $app['application_id']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="row g-3">
                                                    <!-- 1. Geran Kenderaan -->
                                                    <div class="col-md-6">
                                                        <div class="doc-box">
                                                            <div>
                                                                <div class="fw-bold text-dark mb-1"><i class="bi bi-file-earmark-pdf text-danger me-2 fs-5"></i>Geran Kenderaan</div>
                                                                <small class="text-muted d-block mb-3">Dokumen Hak Milik Kenderaan</small>
                                                            </div>
                                                            <?php if (!empty($app['grant_file'])): ?>
                                                                <a href="<?= htmlspecialchars(getFilePath($app['grant_file'])); ?>" target="_blank" class="btn btn-sm btn-primary w-100 fw-bold">
                                                                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Geran
                                                                </a>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-secondary w-100" disabled>Tiada Fail</button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <!-- 2. Kad Pengenalan -->
                                                    <div class="col-md-6">
                                                        <div class="doc-box">
                                                            <div>
                                                                <div class="fw-bold text-dark mb-1"><i class="bi bi-card-heading text-primary me-2 fs-5"></i>Kad Pengenalan</div>
                                                                <small class="text-muted d-block mb-3">Salinan Kad Pengenalan Pemohon</small>
                                                            </div>
                                                            <?php if (!empty($app['ic_file'])): ?>
                                                                <a href="<?= htmlspecialchars(getFilePath($app['ic_file'])); ?>" target="_blank" class="btn btn-sm btn-primary w-100 fw-bold">
                                                                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka IC
                                                                </a>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-secondary w-100" disabled>Tiada Fail</button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <!-- 3. Bukti Kediaman -->
                                                    <div class="col-md-6">
                                                        <div class="doc-box">
                                                            <div>
                                                                <div class="fw-bold text-dark mb-1"><i class="bi bi-house-door text-success me-2 fs-5"></i>Bukti Kediaman</div>
                                                                <small class="text-muted d-block mb-3">Bil Utiliti / Perjanjian Rumah</small>
                                                            </div>
                                                            <?php if (!empty($app['residence_file'])): ?>
                                                                <a href="<?= htmlspecialchars(getFilePath($app['residence_file'])); ?>" target="_blank" class="btn btn-sm btn-primary w-100 fw-bold">
                                                                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Bukti Kediaman
                                                                </a>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-secondary w-100" disabled>Tiada Fail</button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <!-- 4. Bukti Pembayaran -->
                                                    <div class="col-md-6">
                                                        <div class="doc-box" style="border-color: #cbd5e1; background-color: #f1f5f9;">
                                                            <div>
                                                                <div class="fw-bold text-dark mb-1"><i class="bi bi-receipt-cutoff text-warning-emphasis me-2 fs-5"></i>Bukti Pembayaran</div>
                                                                <small class="text-muted d-block mb-1">Bukti Resit Pembayaran Pelekat</small>
                                                                <?php if (!empty($app['amount'])): ?>
                                                                    <small class="fw-bold text-success d-block mb-2">Jumlah Bayaran: RM <?= number_format($app['amount'], 2); ?></small>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php if (!empty($app['proof_of_payment'])): ?>
                                                                <a href="<?= htmlspecialchars(getFilePath($app['proof_of_payment'], true)); ?>" target="_blank" class="btn btn-sm btn-success w-100 fw-bold">
                                                                    <i class="bi bi-file-earmark-check-fill me-1"></i> Buka Resit Bayaran
                                                                </a>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-warning w-100 fw-bold text-dark" disabled>Belum Ada Bayaran</button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Status Permohonan -->
                                <div class="modal fade" id="updateModal<?= $app['application_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Kemaskini Status #<?= $app['application_id']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="update-status.php" method="POST">
                                                <input type="hidden" name="application_id" value="<?= $app['application_id']; ?>">
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Status Baru</label>
                                                        <select name="status" class="form-select fw-bold" required>
                                                            <option value="Dalam Proses" <?= $app['status'] == 'Dalam Proses' ? 'selected' : ''; ?>>Dalam Proses</option>
                                                            <option value="Diluluskan" <?= $app['status'] == 'Diluluskan' ? 'selected' : ''; ?>>Diluluskan</option>
                                                            <option value="Ditolak" <?= $app['status'] == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold small">Catatan / Mesej Pentadbir</label>
                                                        <textarea name="remarks" class="form-control" rows="3" placeholder="Masukkan sebarang catatan jika perlu..."><?= htmlspecialchars($app['remarks'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-primary fw-bold" style="background-color: #0f2c59;">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmLogout() {
    Swal.fire({
        title: 'Log Keluar Admin?',
        text: 'Adakah anda pasti mahu keluar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#0f2c59',
        confirmButtonText: 'Ya, Log Keluar',
        cancelButtonText: 'Batal'
    }).then((res) => {
        if (res.isConfirmed) { window.location.href = 'logout.php'; }
    });
}
</script>

<?php if ($flash_success): ?>
<script>Swal.fire({ title: 'Berjaya!', text: '<?= htmlspecialchars($flash_success); ?>', icon: 'success' });</script>
<?php endif; ?>
<?php if ($flash_error): ?>
<script>Swal.fire({ title: 'Ralat!', text: '<?= htmlspecialchars($flash_error); ?>', icon: 'error' });</script>
<?php endif; ?>

</body>
</html>