
<?php
session_start();

if (file_exists('../config/db.php')) {
    require_once '../config/db.php';
} else {
    require_once 'config/db.php';
}

if (!isset($_SESSION['resident_id'])) {
    header("Location: login.php");
    exit();
}

$resident_id    = $_SESSION['resident_id'];
$resident_name  = $_SESSION['resident_name'] ?? 'Penduduk';
$resident_email = $_SESSION['resident_email'] ?? 'penduduk@example.com';

try {
    // Ambil semua permohonan yang berstatus 'Dalam Troli' atau 'Menunggu Bayaran'
    $stmt = $pdo->prepare("
        SELECT a.application_id, a.submission_date, a.status,
               v.plate_number, v.brand, v.model, v.vehicle_type, v.color
        FROM application a
        JOIN vehicle v ON a.vehicle_id = v.vehicle_id
        WHERE a.resident_id = ? AND a.status IN ('Dalam Troli', 'Menunggu Bayaran', 'Menunggu Pembayaran')
        ORDER BY a.submission_date DESC
    ");
    $stmt->execute([$resident_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Ralat Pangkalan Data: " . htmlspecialchars($e->getMessage()));
}

$price_per_item = 10.00;
$total_items    = count($cart_items);
$total_amount   = $total_items * $price_per_item;
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Troli Permohonan - ReSAS 2026</title>
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --primary-bg: #0b2244;
            --accent-blue: #00d2ff;
            --body-bg: #f8fafc;
            --card-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05);
        }

        body { 
            background-image: url('../assets/images/bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #334155;
        }

        .dashboard-wrapper { display: flex; min-height: 100vh; position: relative; }

        .sidebar-panel { 
            width: 270px; 
            background-color: var(--primary-bg); 
            color: #ffffff; 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between; 
            flex-shrink: 0; 
            z-index: 1050;
            transition: all 0.3s ease-in-out;
        }

        .sidebar-header { 
            padding: 24px 20px; 
            background-color: #0f2c59; 
            border-bottom: 1px solid rgba(255, 255, 255, 0.08); 
        }

        .sidebar-brand { 
            font-size: 1.4rem; 
            font-weight: 800; 
            color: var(--accent-blue); 
            text-decoration: none; 
            letter-spacing: 0.5px;
        }

        .sidebar-menu { list-style: none; padding: 15px 10px; margin: 0; }
        .sidebar-menu li a { 
            display: flex; 
            align-items: center; 
            padding: 12px 18px; 
            color: #94a3b8; 
            text-decoration: none; 
            font-weight: 600; 
            border-radius: 10px;
            margin-bottom: 4px;
            transition: all 0.2s ease;
        }

        .sidebar-menu li a:hover { 
            background-color: rgba(255, 255, 255, 0.06); 
            color: #ffffff; 
        }

        .sidebar-menu li.active a { 
            background-color: #0f2c59; 
            color: var(--accent-blue); 
            box-shadow: 0 4px 12px rgba(0, 210, 255, 0.15);
            font-weight: 700; 
        }

        .main-content { flex-grow: 1; padding: 35px; overflow-y: auto; width: 100%; }

        .top-bar { 
            background-color: #ffffff; 
            padding: 18px 24px; 
            border-radius: 16px; 
            box-shadow: var(--card-shadow); 
            margin-bottom: 30px; 
            border: 1px solid #e2e8f0;
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            gap: 15px;
        }

        .custom-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: var(--card-shadow);
            padding: 32px;
        }

        .user-email-badge {
            max-width: 180px;
            font-size: 0.8rem;
            height: 36px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            padding: 0 10px;
            border-radius: 8px;
            flex-shrink: 1;
            min-width: 0;
        }

        .user-email-text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .btn-logout-custom {
            height: 36px;
            font-size: 0.8rem;
            padding: 0 12px;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-cart-custom {
            height: 36px;
            font-size: 0.82rem;
            padding: 0 14px;
            border-radius: 8px;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
            display: inline-flex;
            align-items: center;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1040;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .mobile-nav-toggle {
            display: none;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #0b2244;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 1.25rem;
        }

        @media (max-width: 991.98px) {
            .sidebar-panel {
                position: fixed;
                top: 0;
                left: -280px;
                height: 100vh;
                box-shadow: 10px 0 30px rgba(0, 0, 0, 0.25);
            }

            .sidebar-panel.show {
                left: 0;
            }

            .sidebar-overlay.show {
                display: block;
                opacity: 1;
            }

            .main-content {
                padding: 16px;
            }

            .top-bar {
                padding: 16px;
                margin-bottom: 20px;
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
            }

            .top-bar-actions {
                width: 100%;
                justify-content: space-between;
                border-top: 1px solid #f1f5f9;
                padding-top: 12px;
            }

            .user-email-badge {
                max-width: 140px;
            }

            .mobile-nav-toggle {
                display: inline-flex;
                align-items: center;
            }

            .custom-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<!-- Mobile Overlay Layer -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="dashboard-wrapper">
    <!-- Sidebar Navigation -->
    <div class="sidebar-panel" id="sidebarPanel">
        <div>
            <div class="sidebar-header d-flex align-items-center justify-content-between">
                <a href="dashboard.php" class="sidebar-brand d-flex align-items-center gap-2">
                    Resident Sticker Application System
                </a>
                <button type="button" class="btn-close btn-close-white d-lg-none" id="sidebarCloseBtn" aria-label="Close"></button>
            </div>

            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php"><i class="bi bi-grid-1x2-fill me-3"></i>Paparan Utama</a>
                </li>
                <li class="active">
                    <a href="cart.php">
                        <i class="bi bi-cart-fill me-3"></i>Troli Permohonan
                        <?php if ($total_items > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?= $total_items; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="profile.php"><i class="bi bi-clock-history me-3"></i>Permohonan Saya</a>
                </li>
                <li>
                    <a href="info.php"><i class="bi bi-info-circle-fill me-3"></i>Informasi</a>
                </li>
            </ul>
        </div>

        <div class="p-3"></div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar Header -->
        <div class="top-bar">
            <div class="d-flex align-items-center justify-content-between w-100 w-lg-auto mb-1 mb-lg-0 gap-2">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">Troli Permohonan Pelekat</h5>
                    <small class="text-muted">
                        Semak permohonan yang disimpan sebelum meneruskan pembayaran.
                    </small>
                </div>
                <!-- Butang Menu Mobile -->
                <button type="button" class="mobile-nav-toggle d-lg-none" id="mobileSidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>

            <!-- Bahagian Pengguna: Email, Log Keluar & Troli -->
            <div class="d-flex align-items-center gap-2 top-bar-actions">
                <span class="user-email-badge" title="<?= htmlspecialchars($resident_email); ?>">
                    <i class="bi bi-person-circle text-primary fs-6 me-1 flex-shrink-0"></i> 
                    <span class="user-email-text text-muted"><?= htmlspecialchars($resident_email); ?></span>
                </span>
                
                <button type="button" onclick="confirmLogout()" class="btn btn-outline-danger fw-semibold btn-logout-custom">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Log Keluar</span>
                </button>
            </div>
        </div>

        <!-- Custom Card / Kad Kandungan Utama -->
        <div class="row justify-content-center">
            <div class="col-lg-12">
                
                <div class="custom-card mb-4">
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4 flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-cart-check-fill text-primary me-2"></i>Senarai Permohonan Dalam Troli</h5>
                            <small class="text-muted">Setiap permohonan pelekat berharga RM 10.00.</small>
                        </div>
                        <a href="apply.php" class="btn btn-outline-primary fw-bold px-3 py-2 rounded-3">
                            <i class="bi bi-plus-lg me-1"></i> Mohon Pelekat Lain
                        </a>
                    </div>

                    <?php if (empty($cart_items)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-cart-x text-muted display-3 d-block mb-3"></i>
                            <h5 class="fw-bold text-secondary">Troli Anda Kosong</h5>
                            <p class="text-muted small mb-4">Tiada permohonan pelekat yang belum dibayar dalam troli anda.</p>
                            <a href="apply.php" class="btn btn-primary fw-bold px-4 py-2" style="background-color: #0f2c59; border-color: #0f2c59; border-radius: 10px;">
                                <i class="bi bi-plus-circle me-1"></i> Mohon Pelekat Sekarang
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr class="text-secondary small text-uppercase">
                                        <th>#</th>
                                        <th>Nombor Plat</th>
                                        <th>Kenderaan</th>
                                        <th>Jenis</th>
                                        <th>Kadar Bayaran</th>
                                        <th class="text-center">Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $idx => $item): ?>
                                        <tr>
                                            <td class="fw-bold text-muted"><?= $idx + 1; ?></td>
                                            <td>
                                                <span class="badge bg-dark text-light px-2 py-1 fs-6 font-monospace">
                                                    <?= htmlspecialchars($item['plate_number']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($item['brand'] . ' ' . $item['model']); ?></div>
                                                <small class="text-muted">Warna: <?= htmlspecialchars($item['color']); ?></small>
                                            </td>
                                            <td class="fw-semibold"><?= htmlspecialchars($item['vehicle_type']); ?></td>
                                            <td class="fw-bold text-success">RM 10.00</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-2" onclick="confirmDelete(<?= $item['application_id']; ?>)" title="Padam Permohonan">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Ringkasan Bayaran -->
                        <div class="bg-light p-4 rounded-3 border mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Jumlah Permohonan Pelekat:</span>
                                <span class="fw-bold text-dark"><?= $total_items; ?> Keping</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Harga Per Pelekat:</span>
                                <span class="fw-bold text-dark">RM 10.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span class="fw-bold fs-5 text-dark">Jumlah Keseluruhan Perlu Dibayar:</span>
                                <span class="fw-extrabold fs-4 text-success">RM <?= number_format($total_amount, 2); ?></span>
                            </div>
                        </div>

                        <!-- Butang Tindakan -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <a href="apply.php" class="btn btn-outline-secondary fw-bold px-4 py-2" style="border-radius: 10px;">
                                <i class="bi bi-plus-lg me-1"></i> Mohon Pelekat Lagi
                            </a>
                            <a href="payment.php" class="btn btn-success fw-bold px-4 py-2 fs-6" style="border-radius: 10px;">
                                Teruskan Ke Pembayaran QR (RM <?= number_format($total_amount, 2); ?>) <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    <?php endif; ?>

                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Menu Navigation Mobile Overlay
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebarPanel');
    const toggleBtn = document.getElementById('mobileSidebarToggle');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const overlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        if(sidebar && overlay) {
            sidebar.classList.add('show');
            overlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSidebar() {
        if(sidebar && overlay) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);
});

// Fungsi Pengesahan Log Keluar
function confirmLogout() {
    Swal.fire({
        title: 'Log Keluar?',
        text: 'Adakah anda pasti untuk keluar dari akaun anda?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#0f2c59',
        confirmButtonText: 'Ya, Log Keluar',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'logout.php';
        }
    });
}

// Fungsi Pengesahan Padam Permohonan
function confirmDelete(id) {
    Swal.fire({
        title: 'Padam Permohonan?',
        text: 'Adakah anda pasti mahu memadam permohonan ini dari troli?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#0f2c59',
        confirmButtonText: 'Ya, Padam',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete-application.php?id=' + id + '&from=cart';
        }
    });
}
</script>

<?php if (isset($_GET['added']) && $_GET['added'] === 'success'): ?>
<script>
    Swal.fire({
        title: 'Berjaya Ditambah!',
        text: 'Permohonan pelekat anda telah dimasukkan ke dalam troli.',
        icon: 'success',
        confirmButtonColor: '#0f2c59',
        confirmButtonText: 'OK'
    });
</script>
<?php endif; ?>

<?php if (isset($_GET['deleted']) && $_GET['deleted'] === 'success'): ?>
<script>
    Swal.fire({
        title: 'Berjaya Dipadam!',
        text: 'Permohonan telah dikeluarkan dari troli anda.',
        icon: 'success',
        confirmButtonColor: '#0f2c59',
        confirmButtonText: 'OK'
    });
</script>
<?php endif; ?>

</body>
</html>