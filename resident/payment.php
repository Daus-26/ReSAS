
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

$resident_id = $_SESSION['resident_id'];
$resident_email = $_SESSION['resident_email'] ?? 'penduduk@example.com';

try {
    // Dapatkan maklumat e-mel penduduk terkini
    $stmtUser = $pdo->prepare("SELECT email FROM resident WHERE resident_id = ?");
    $stmtUser->execute([$resident_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if ($user && !empty($user['email'])) {
        $resident_email = $user['email'];
    }

    // Dapatkan SEMUA permohonan dalam troli yang belum dibayar
    $stmt = $pdo->prepare("
        SELECT a.application_id, a.status, a.submission_date, 
               v.plate_number, v.brand, v.model, v.vehicle_type
        FROM application a
        JOIN vehicle v ON a.vehicle_id = v.vehicle_id
        WHERE a.resident_id = ? AND a.status IN ('Dalam Troli', 'Menunggu Bayaran', 'Menunggu Pembayaran')
    ");
    $stmt->execute([$resident_id]);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($applications)) {
        header("Location: profile.php");
        exit();
    }

    $total_items  = count($applications);
    $total_amount = $total_items * 10.00;
    $cart_count   = $total_items;

} catch (PDOException $e) {
    die("Ralat Pangkalan Data: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran QR DuitNow - ReSAS 2026</title>
    <!-- Google Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        :root {
            --primary-bg: #0b2244;
            --accent-blue: #00d2ff;
            --body-bg: #f8fafc;
            --card-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05), 0 8px 10px -6px rgba(15, 23, 42, 0.05);
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
            padding: 28px;
        }

        /* GAYA KHAS BADGE EMAIL & BUTTON LOGOUT */
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

        /* REKA BENTUK RESPONSIP MOBILE */
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
                <li><a href="dashboard.php"><i class="bi bi-grid-1x2-fill me-3"></i>Paparan Utama</a></li>
                <li class="active">
                    <a href="cart.php">
                        <i class="bi bi-cart-fill me-3"></i>Troli Permohonan
                        <?php if ($cart_count > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?= $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="profile.php"><i class="bi bi-clock-history me-3"></i>Permohonan Saya</a></li>
                <li><a href="info.php"><i class="bi bi-info-circle-fill me-3"></i>Informasi</a></li>
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
                    <h5 class="fw-bold mb-1 text-dark">Pembayaran QR DuitNow</h5>
                    <small class="text-muted">Lakukan pembayaran menerusi imbahan kod QR DuitNow dan muat naik bukti resit</small>
                </div>
                <!-- Butang Menu Mobile -->
                <button type="button" class="mobile-nav-toggle d-lg-none" id="mobileSidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>

            <!-- Bahagian Pengguna: Email & Log Keluar Sahaja -->
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

        <!-- Kad Pembayaran -->
        <div class="row justify-content-center">
            <div class="col-lg-9 col-xl-8">
                
                <div class="custom-card mb-4">
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-4 flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-qr-code-scan text-primary me-2"></i>Pembayaran QR Code DuitNow</h5>
                            <small class="text-muted">Jumlah Permohonan: <?= $total_items; ?> Permohonan Pelekat</small>
                        </div>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 rounded-pill fw-bold">
                            Menunggu Bayaran
                        </span>
                    </div>

                    <!-- Senarai Permohonan Yang Dibayar -->
                    <div class="bg-light p-3 rounded-3 mb-4 border">
                        <h6 class="fw-bold text-dark mb-3">Ringkasan Item Permohonan:</h6>
                        <ul class="list-group mb-3 shadow-sm">
                            <?php foreach ($applications as $app): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="badge bg-dark font-monospace"><?= htmlspecialchars($app['plate_number']); ?></span>
                                        <small class="fw-bold text-dark"><?= htmlspecialchars($app['brand'] . ' ' . $app['model']); ?></small>
                                        <small class="text-muted">(<?= htmlspecialchars($app['vehicle_type']); ?>)</small>
                                    </div>
                                    <span class="fw-bold text-secondary ms-2">RM 10.00</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="fw-bold text-dark">Jumlah Perlu Dibayar:</span>
                            <span class="fw-extrabold fs-4 text-success">RM <?= number_format($total_amount, 2); ?></span>
                        </div>
                    </div>

                    <!-- Borang Pembayaran QR SAHAJA -->
                    <form action="process-payment.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="payment_method" value="DuitNow QR">

                        <!-- Ruangan Kod QR -->
                        <div class="p-4 border border-primary rounded-3 bg-primary-subtle mb-4 text-center">
                            <p class="fw-bold mb-1 text-dark fs-5">Imbas Kod QR DuitNow Di Bawah:</p>
                            <small class="text-muted d-block mb-3">Sila bayar jumlah tepat <strong>RM <?= number_format($total_amount, 2); ?></strong> menerusi mana-mana aplikasi Bank / E-Wallet.</small>
                            
                            <img src="../assets/images/resasQr1.jpeg" alt="DuitNow QR" class="img-fluid rounded border my-2 bg-white p-3 shadow-sm" style="max-width: 220px; height: auto;">
                            
                            <div class="mt-2">
                                <span class="badge bg-dark px-3 py-2 text-wrap lh-base">
                                    Akaun: Pengurusan Rumah Pangsa Kajang Impian (Maybank: 1623 8518 7156)
                                </span>
                            </div>
                        </div>

                        <!-- Upload Resit Bayaran -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">
                                <i class="bi bi-file-earmark-arrow-up text-primary me-1"></i> Muat Naik Resit Bukti Pembayaran <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="proof_of_payment" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                            <small class="text-muted mt-1 d-block">Sila muat naik fail resit pembayaran (Format: JPG, PNG, PDF).</small>
                        </div>

                        <!-- Butang Tindakan -->
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center pt-3 border-top gap-2">
                            <a href="cart.php" class="btn btn-outline-secondary fw-bold px-4 py-2 w-100 w-sm-auto" style="border-radius: 10px;">
                                Kembali Ke Troli
                            </a>
                            <button type="submit" class="btn btn-success fw-bold px-4 py-2 w-100 w-sm-auto" style="border-radius: 10px;">
                                <i class="bi bi-shield-check me-1"></i> Sahkan & Hantar Bayaran (RM <?= number_format($total_amount, 2); ?>)
                            </button>
                        </div>

                    </form>
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

// Pengesahan Log Keluar
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
</script>

</body>
</html>