
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

$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT r.resident_id, r.name, r.email, r.phone_number, r.address,
           COUNT(DISTINCT v.vehicle_id) AS total_vehicles,
           COUNT(DISTINCT a.application_id) AS total_applications
    FROM resident r
    LEFT JOIN vehicle v ON r.resident_id = v.resident_id
    LEFT JOIN application a ON r.resident_id = a.resident_id
    WHERE 1=1
";

$params = [];
if (!empty($search)) {
    $sql .= " AND (r.name LIKE ? OR r.address LIKE ? OR r.email LIKE ? OR r.phone_number LIKE ?)";
    $params = ["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"];
}
$sql .= " GROUP BY r.resident_id ORDER BY r.resident_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$residents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senarai Pendaftaran Penduduk - ReSAS Admin</title>
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
                <li><a href="applications.php"><i class="bi bi-file-earmark-text-fill me-3"></i>Permohonan</a></li>
                <li class="active"><a href="registrations.php"><i class="bi bi-people-fill me-3"></i>Pendaftaran</a></li>
                <li><a href="reports.php"><i class="bi bi-bar-chart-line-fill me-3"></i>Laporan & Log</a></li>
            </ul>
        </div>
    </div>

    <!-- Kandungan Utama -->
    <div class="main-content">
        <!-- Top Bar dengan Butang Log Keluar di Kanan -->
        <div class="top-bar">
            <div>
                <h5 class="fw-bold mb-1">Pengurusan Pendaftaran Penduduk</h5>
                <small class="text-muted">Paparan pengurusan profil bagi penduduk yang berdaftar</small>
            </div>
            <button type="button" onclick="confirmLogout()" class="btn btn-outline-danger fw-bold px-3 py-2 d-flex align-items-center gap-2" style="border-radius: 10px;">
                <i class="bi bi-box-arrow-right"></i> Log Keluar
            </button>
        </div>

        <div class="custom-card">
            <!-- Borang Carian -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama penduduk, e-mel, telefon, alamat..." value="<?= htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100 fw-bold" style="background-color: #0f2c59;">Cari Penduduk</button>
                </div>
                <div class="col-md-3">
                    <a href="registrations.php" class="btn btn-outline-secondary w-100 fw-bold">Reset</a>
                </div>
            </form>

            <!-- Jadual Penduduk -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>#ID</th>
                            <th>Nama Penduduk</th>
                            <th>Alamat Rumah</th>
                            <th>No. Telefon / E-mel</th>
                            <th class="text-center">Bil. Kenderaan</th>
                            <th class="text-center">Bil. Permohonan</th>
                            <th class="text-center">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($residents)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">Tiada data penduduk berdaftar.</td></tr>
                        <?php else: ?>
                            <?php foreach ($residents as $res): ?>
                                <tr>
                                    <td class="fw-bold">#<?= $res['resident_id']; ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($res['name']); ?></td>
                                    <td><small class="text-muted"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($res['address']); ?></small></td>
                                    <td class="small">
                                        <div><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($res['phone_number']); ?></div>
                                        <div class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($res['email']); ?></div>
                                    </td>
                                    <td class="text-center"><span class="badge bg-info-subtle text-info border px-2 py-1"><?= $res['total_vehicles']; ?> Unit</span></td>
                                    <td class="text-center"><span class="badge bg-primary-subtle text-primary border px-2 py-1"><?= $res['total_applications']; ?> Rekod</span></td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-danger fw-bold px-3" onclick="confirmDelete(<?= $res['resident_id']; ?>, '<?= htmlspecialchars(addslashes($res['name'])); ?>')">
                                            <i class="bi bi-trash-fill me-1"></i> Padam
                                        </button>
                                    </td>
                                </tr>
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
function confirmDelete(id, name) {
    Swal.fire({
        title: 'Padam Penduduk?',
        text: `Adakah anda pasti mahu memadam rekod "${name}"? Semua permohonan & kenderaan berkaitan akan turut dipadam!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Ya, Padam',
        cancelButtonText: 'Batal'
    }).then((res) => {
        if (res.isConfirmed) {
            window.location.href = `delete-resident.php?id=${id}`;
        }
    });
}
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