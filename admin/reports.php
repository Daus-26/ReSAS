
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

$admin_id      = $_SESSION['admin_id'];
$type_filter   = $_GET['vehicle_type'] ?? 'ALL';
$status_filter = $_GET['status'] ?? 'ALL';

// Ambil data laporan penduduk & kenderaan
$sql = "
    SELECT a.application_id, a.submission_date, a.status, a.remarks,
           r.resident_id, r.name AS resident_name, r.email, r.phone_number, r.address,
           v.vehicle_id, v.plate_number, v.brand, v.model, v.color, v.vehicle_type
    FROM application a
    JOIN resident r ON a.resident_id = r.resident_id
    JOIN vehicle v ON a.vehicle_id = v.vehicle_id
    WHERE 1=1
";

$params = [];
if ($type_filter !== 'ALL') {
    $sql .= " AND v.vehicle_type = ?";
    $params[] = $type_filter;
}
if ($status_filter !== 'ALL') {
    $sql .= " AND a.status = ?";
    $params[] = $status_filter;
}
$sql .= " ORDER BY r.address ASC, a.submission_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pengiraan statistik ringkas berdasarkan data tapisan
$stat_total      = count($report_data);
$stat_lulus      = 0;
$stat_proses     = 0;
$stat_tolak      = 0;

foreach ($report_data as $row) {
    $st = strtolower($row['status']);
    if ($st === 'diluluskan') {
        $stat_lulus++;
    } elseif ($st === 'ditolak') {
        $stat_tolak++;
    } else {
        $stat_proses++;
    }
}

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kenderaan & Permohonan - ReSAS Admin</title>
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
        .custom-card { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 25px; }
        .stat-box { background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 18px 20px; }
        
        @media print {
            .sidebar-panel, .top-bar, .no-print, form { display: none !important; }
            .main-content { padding: 0 !important; }
            body { background-color: #ffffff !important; }
            .custom-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
            .print-header { display: block !important; margin-bottom: 20px; }
        }
        .print-header { display: none; }
    </style>
</head>
<body>

<div class="dashboard-wrapper">
    <!-- Sidebar Navigasi -->
    <div class="sidebar-panel">
        <div>
            <div class="sidebar-header d-flex align-items-center gap-2">
                <a href="dashboard.php" class="sidebar-brand"><i class="bi bi-shield-check"></i> ReSAS Admin</a>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="bi bi-grid-1x2-fill me-3"></i>Dashboard</a></li>
                <li><a href="applications.php"><i class="bi bi-file-earmark-text-fill me-3"></i>Permohonan</a></li>
                <li><a href="registrations.php"><i class="bi bi-people-fill me-3"></i>Pendaftaran</a></li>
                <li class="active"><a href="reports.php"><i class="bi bi-bar-chart-line-fill me-3"></i>Laporan & Log</a></li>
            </ul>
        </div>
    </div>

    <!-- Utama -->
    <div class="main-content">
        <!-- Cetakan (Hanya Keluar Bila Print) -->
        <div class="print-header text-center border-bottom pb-3">
            <h3 class="fw-bold m-0">SISTEM PERMOHONAN PELEKAT PENDUDUK (ReSAS)</h3>
            <p class="m-0 text-muted">Laporan Ringkasan Kenderaan & Permohonan Pelekat</p>
            <small class="text-secondary">Tarikh Dicetak: <?= date('d/m/Y h:i A'); ?></small>
        </div>

        <!-- Top Bar dengan Butang Tindakan & Log Keluar -->
        <div class="top-bar">
            <div>
                <h5 class="fw-bold mb-1">Pengurusan Laporan Permohonan</h5>
                <small class="text-muted">Paparan data terkini mengikut tapisan jenis kenderaan dan status</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button onclick="exportTableToCSV('Laporan_ReSAS.csv')" class="btn btn-outline-success fw-bold px-3 py-2 no-print">
                    <i class="bi bi-file-earmark-excel-fill me-1"></i> Eksport CSV
                </button>
                <button onclick="window.print()" class="btn btn-primary fw-bold px-3 py-2 no-print" style="background-color: #0f2c59;">
                    <i class="bi bi-printer-fill me-1"></i> Cetak Laporan
                </button>
                <button type="button" onclick="confirmLogout()" class="btn btn-outline-danger fw-bold px-3 py-2 no-print d-flex align-items-center gap-1" style="border-radius: 10px;">
                    <i class="bi bi-box-arrow-right"></i> Log Keluar
                </button>
            </div>
        </div>

        <!-- Kad Ringkasan Statistik -->
        <div class="row g-3 mb-4 no-print">
            <div class="col-md-3">
                <div class="stat-box d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted fw-bold d-block mb-1">JUMLAH REKOD</small>
                        <h4 class="fw-bold text-dark m-0"><?= $stat_total; ?></h4>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-3"><i class="bi bi-files fs-4"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted fw-bold d-block mb-1">DILULUSKAN</small>
                        <h4 class="fw-bold text-success m-0"><?= $stat_lulus; ?></h4>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-3"><i class="bi bi-check-circle-fill fs-4"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted fw-bold d-block mb-1">DALAM PROSES</small>
                        <h4 class="fw-bold text-warning m-0"><?= $stat_proses; ?></h4>
                    </div>
                    <div class="bg-warning-subtle text-warning p-3 rounded-3"><i class="bi bi-hourglass-split fs-4"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted fw-bold d-block mb-1">DITOLAK</small>
                        <h4 class="fw-bold text-danger m-0"><?= $stat_tolak; ?></h4>
                    </div>
                    <div class="bg-danger-subtle text-danger p-3 rounded-3"><i class="bi bi-x-circle-fill fs-4"></i></div>
                </div>
            </div>
        </div>

        <!-- Borang Penapis -->
        <div class="custom-card no-print">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Jenis Kenderaan</label>
                    <select name="vehicle_type" class="form-select">
                        <option value="ALL" <?= $type_filter === 'ALL' ? 'selected' : ''; ?>>-- Semua Jenis --</option>
                        <option value="Kereta" <?= $type_filter === 'Kereta' ? 'selected' : ''; ?>>Kereta</option>
                        <option value="Motosikal" <?= $type_filter === 'Motosikal' ? 'selected' : ''; ?>>Motosikal</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">Status Permohonan</label>
                    <select name="status" class="form-select">
                        <option value="ALL" <?= $status_filter === 'ALL' ? 'selected' : ''; ?>>-- Semua Status --</option>
                        <option value="Diluluskan" <?= $status_filter === 'Diluluskan' ? 'selected' : ''; ?>>Diluluskan</option>
                        <option value="Dalam Proses" <?= $status_filter === 'Dalam Proses' ? 'selected' : ''; ?>>Dalam Proses</option>
                        <option value="Ditolak" <?= $status_filter === 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100" style="background-color: #0f2c59;">
                        <i class="bi bi-funnel-fill me-1"></i> Tapis Data
                    </button>
                    <a href="reports.php" class="btn btn-outline-secondary fw-bold">Reset</a>
                </div>
            </form>
        </div>

        <!-- Senarai Data Laporan -->
        <div class="custom-card">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-file-text-fill me-2 text-primary"></i>Ringkasan Data Permohonan Pelekat</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0" id="reportTable">
                    <thead class="table-dark small text-uppercase">
                        <tr>
                            <th>#</th>
                            <th>Unit Kediaman</th>
                            <th>Pemohon</th>
                            <th>No. Telefon / E-mel</th>
                            <th>No. Plat</th>
                            <th>Jenis / Model</th>
                            <th>Tarikh Hantar</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($report_data)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">Tiada rekod permohonan ditemui.</td></tr>
                        <?php else: ?>
                            <?php foreach ($report_data as $idx => $row): ?>
                                <tr>
                                    <td class="fw-bold"><?= $idx + 1; ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['address']); ?></td>
                                    <td><?= htmlspecialchars($row['resident_name']); ?></td>
                                    <td class="small">
                                        <div><?= htmlspecialchars($row['phone_number']); ?></div>
                                        <div class="text-muted"><?= htmlspecialchars($row['email']); ?></div>
                                    </td>
                                    <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($row['plate_number']); ?></span></td>
                                    <td class="small"><strong><?= htmlspecialchars($row['vehicle_type']); ?></strong> - <?= htmlspecialchars($row['brand'] . ' ' . $row['model']); ?></td>
                                    <td class="small text-muted"><?= date('d/m/Y', strtotime($row['submission_date'])); ?></td>
                                    <td>
                                        <?php 
                                            $st = strtolower($row['status']);
                                            if ($st == 'diluluskan') echo '<span class="badge bg-success-subtle text-success border px-2 py-1">Diluluskan</span>';
                                            elseif ($st == 'ditolak') echo '<span class="badge bg-danger-subtle text-danger border px-2 py-1">Ditolak</span>';
                                            elseif ($st == 'sudah dibayar') echo '<span class="badge bg-info-subtle text-info border px-2 py-1">Sudah Dibayar</span>';
                                            else echo '<span class="badge bg-warning-subtle text-warning-emphasis border px-2 py-1">'.htmlspecialchars($row['status']).'</span>';
                                        ?>
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

function exportTableToCSV(filename) {
    let csv = [];
    let rows = document.querySelectorAll("#reportTable tr");
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/,/g, " ");
            row.push('"' + data.trim() + '"');
        }
        csv.push(row.join(","));
    }

    let csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
    let downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}
</script>
</body>
</html>