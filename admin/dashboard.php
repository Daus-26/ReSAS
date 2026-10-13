
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

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

try {
    // Stat Cards Data (Termasuk 'Sudah Dibayar' dalam pengiraan Menunggu Semakan)
    $total_apps      = $pdo->query("SELECT COUNT(*) FROM application")->fetchColumn();
    $pending_apps    = $pdo->query("SELECT COUNT(*) FROM application WHERE status IN ('Dalam Proses', 'Menunggu Bayaran', 'Sudah Dibayar')")->fetchColumn();
    $approved_apps   = $pdo->query("SELECT COUNT(*) FROM application WHERE status = 'Diluluskan'")->fetchColumn();
    $total_residents = $pdo->query("SELECT COUNT(*) FROM resident")->fetchColumn();

    // Chart 1 Data: Status Breakdown (Gabung 'Baru' -> 'Dalam Troli' & 'Sudah Dibayar' -> 'Dalam Proses')
    $status_stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN status = 'Baru' THEN 'Dalam Troli'
                WHEN status = 'Sudah Dibayar' THEN 'Dalam Proses'
                ELSE status 
            END AS status_group, 
            COUNT(*) as count 
        FROM application 
        GROUP BY status_group
    ");
    $status_data = $status_stmt->fetchAll(PDO::FETCH_ASSOC);

    $status_labels = [];
    $status_counts = [];
    foreach ($status_data as $row) {
        $status_labels[] = $row['status_group'];
        $status_counts[] = $row['count'];
    }

    // Chart 2 Data: Vehicle Breakdown
    $vehicle_stmt = $pdo->query("
        SELECT v.vehicle_type, COUNT(a.application_id) as count 
        FROM vehicle v 
        LEFT JOIN application a ON v.vehicle_id = a.vehicle_id 
        GROUP BY v.vehicle_type
    ");
    $vehicle_data = $vehicle_stmt->fetchAll(PDO::FETCH_ASSOC);

    $vehicle_labels = [];
    $vehicle_counts = [];
    foreach ($vehicle_data as $row) {
        $vehicle_labels[] = $row['vehicle_type'] ?: 'Lain-lain';
        $vehicle_counts[] = $row['count'];
    }

} catch (PDOException $e) {
    die("Ralat Pangkalan Data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pentadbir - ReSAS</title>
    
    <!-- Google Fonts & Library CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- CSS Asal Pentadbir (Sebelum Gambar Latar Belakang) -->
    <style>
        body { background-color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; color: #334155; }
        .dashboard-wrapper { display: flex; min-height: 100vh; }
        .sidebar-panel { width: 270px; background-color: #0b2244; color: #ffffff; flex-shrink: 0; }
        .sidebar-header { padding: 24px 20px; background-color: #0f2c59; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        .sidebar-brand { font-size: 1.4rem; font-weight: 800; color: #00d2ff; text-decoration: none; }
        .sidebar-menu { list-style: none; padding: 15px 10px; margin: 0; }
        .sidebar-menu li a { display: flex; align-items: center; padding: 12px 18px; color: #94a3b8; text-decoration: none; font-weight: 600; border-radius: 10px; margin-bottom: 4px; transition: all 0.2s ease; }
        .sidebar-menu li a:hover { background-color: rgba(255, 255, 255, 0.06); color: #ffffff; }
        .sidebar-menu li.active a { background-color: #0f2c59; color: #00d2ff; font-weight: 700; }
        .main-content { flex-grow: 1; min-width: 0; padding: 35px; overflow-y: auto; }
        .top-bar { background-color: #ffffff; padding: 20px 28px; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .stat-card { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 22px; transition: transform 0.2s ease; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <!-- Sidebar -->
    <div class="sidebar-panel">
        <div class="sidebar-header d-flex align-items-center gap-2">
            <a href="dashboard.php" class="sidebar-brand"><i class="bi bi-shield-check"></i> ReSAS Admin</a>
        </div>
        <ul class="sidebar-menu">
            <li class="active"><a href="dashboard.php"><i class="bi bi-grid-1x2-fill me-3"></i>Dashboard</a></li>
            <li><a href="applications.php"><i class="bi bi-file-earmark-text-fill me-3"></i>Permohonan</a></li>
            <li><a href="registrations.php"><i class="bi bi-people-fill me-3"></i>Pendaftaran</a></li>
            <li><a href="reports.php"><i class="bi bi-bar-chart-line-fill me-3"></i>Laporan & Log</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <div>
                <h5 class="fw-bold mb-1">Panel Kawalan Pentadbir</h5>
                <small class="text-muted">Ringkasan permohonan pelekat kenderaan, Visualisasi Data & Graf Permohonan ReSAS</small>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-light text-dark border px-3 py-2 rounded-3">
                    <i class="bi bi-person-badge-fill text-primary me-1"></i> Admin: <strong><?= htmlspecialchars($admin_username); ?></strong>
                </span>
                <button type="button" onclick="confirmLogout()" class="btn btn-outline-danger btn-sm fw-bold px-3 py-2 d-flex align-items-center gap-2" style="border-radius: 10px;">
                    <i class="bi bi-box-arrow-right"></i> Log Keluar
                </button>
            </div>
        </div>

        <!-- Kad Statistik -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase">Jumlah Permohonan</span>
                        <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-folder-fill"></i></div>
                    </div>
                    <h3 class="fw-800 text-dark mb-0"><?= $total_apps; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase">Menunggu Semakan</span>
                        <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-hourglass-split"></i></div>
                    </div>
                    <h3 class="fw-800 text-dark mb-0"><?= $pending_apps; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase">Diluluskan</span>
                        <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-check-circle-fill"></i></div>
                    </div>
                    <h3 class="fw-800 text-dark mb-0"><?= $approved_apps; ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase">Penduduk Berdaftar</span>
                        <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-people-fill"></i></div>
                    </div>
                    <h3 class="fw-800 text-dark mb-0"><?= $total_residents; ?></h3>
                </div>
            </div>
        </div>

        <!-- Visualisasi Data -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <h6 class="fw-bold text-dark m-0"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Visualisasi Data & Graf Permohonan</h6>
            </div>
            <div class="row g-4 align-items-center">
                <div class="col-md-6 border-end">
                    <h6 class="text-center text-muted fw-bold mb-3">Status Keseluruhan Permohonan</h6>
                    <div style="max-height: 300px;" class="d-flex justify-content-center">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="text-center text-muted fw-bold mb-3">Permohonan Mengikut Jenis Kenderaan</h6>
                    <div style="max-height: 300px;" class="d-flex justify-content-center">
                        <canvas id="vehicleChart"></canvas>
                    </div>
                </div>
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

// Pemetaan warna tepat mengikut status
const statusColorMap = {
    'Dalam Troli': '#ffc107',
    'Menunggu Bayaran': '#fd7e14',
    'Dalam Proses': '#0f2c59',
    'Diluluskan': '#198754',
    'Ditolak': '#dc3545'
};

const statusLabels = <?= json_encode($status_labels); ?>;
const statusPalette = statusLabels.map(label => statusColorMap[label] || '#00d2ff');

// Palet warna kenderaan mengikut bilangan data
const vehicleLabels = <?= json_encode($vehicle_labels); ?>;
const vehiclePalette = ['#00d2ff', '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b'].slice(0, vehicleLabels.length);

// Render Chart 1: Doughnut Chart (Status Permohonan)
const ctxStatus = document.getElementById('statusChart').getContext('2d');
new Chart(ctxStatus, {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: <?= json_encode($status_counts); ?>,
            backgroundColor: statusPalette,
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Render Chart 2: Bar Chart (Jenis Kenderaan)
const ctxVehicle = document.getElementById('vehicleChart').getContext('2d');
new Chart(ctxVehicle, {
    type: 'bar',
    data: {
        labels: vehicleLabels,
        datasets: [{
            label: 'Jumlah Kenderaan',
            data: <?= json_encode($vehicle_counts); ?>,
            backgroundColor: vehiclePalette,
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});
</script>
</body>
</html>