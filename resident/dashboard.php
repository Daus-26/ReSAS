
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

$total_apps   = 0;
$pending_apps = 0;
$cart_count   = 0;

try {
    // Statistik Permohonan
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status IN ('Dalam Semakan', 'Dalam Proses') THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status IN ('Dalam Troli', 'Menunggu Bayaran', 'Menunggu Pembayaran') THEN 1 ELSE 0 END) as in_cart
        FROM application WHERE resident_id = ?");
    $stmt->execute([$resident_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($stats) {
        $total_apps   = $stats['total'] ?? 0;
        $pending_apps = $stats['pending'] ?? 0;
        $cart_count   = $stats['in_cart'] ?? 0;
    }
} catch (PDOException $e) {
    // Sekiranya berlaku ralat pangkalan data
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penduduk - ReSAS 2026</title>
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
            --card-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
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
            flex-shrink: 0; 
            transition: all 0.3s ease-in-out;
            z-index: 1050;
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
            padding: 20px 28px; 
            border-radius: 16px; 
            box-shadow: var(--card-shadow); 
            margin-bottom: 30px; 
            border: 1px solid #e2e8f0;
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }

        /* REKA BENTUK KAD BEWERNA BERKONTRAS TINGGI */
        .color-card-1 {
            background: linear-gradient(135deg, #0b2244 0%, #1e3c72 100%);
            color: #ffffff;
            border-radius: 18px;
            border: none;
            box-shadow: 0 10px 20px rgba(11, 34, 68, 0.25);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            height: 100%;
        }

        .color-card-2 {
            background: linear-gradient(135deg, #0d5c3a 0%, #198754 100%);
            color: #ffffff;
            border-radius: 18px;
            border: none;
            box-shadow: 0 10px 20px rgba(13, 92, 58, 0.25);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            height: 100%;
        }

        .color-card-3 {
            background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 100%);
            color: #ffffff;
            border-radius: 18px;
            border: none;
            box-shadow: 0 10px 20px rgba(76, 29, 149, 0.25);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            height: 100%;
        }

        .color-card-1:hover, .color-card-2:hover, .color-card-3:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .icon-circle-dark {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            backdrop-filter: blur(5px);
        }

        .btn-card-light {
            background-color: #ffffff;
            color: #0b2244;
            font-weight: 700;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            transition: background-color 0.2s ease;
        }

        .btn-card-light:hover {
            background-color: #f1f5f9;
            color: #0b2244;
        }

        /* AMBIENT & MOBILE RESPONSIVE */
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

        /* GAYA LENCANA EMAIL & BUTTON LOG KELUAR */
        .user-email-badge {
            font-size: 0.82rem;
            max-width: 200px;
            height: 36px;
            background-color: #f8fafc;
        }

        .btn-logout-custom {
            border-radius: 8px;
            font-size: 0.8rem;
            height: 36px;
            white-space: nowrap !important;
            flex-shrink: 0 !important;
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

            .top-bar-user-area {
                width: 100%;
                justify-content: space-between;
                border-top: 1px solid #f1f5f9;
                padding-top: 12px;
            }

            .user-email-badge {
                max-width: 180px;
            }

            .mobile-nav-toggle {
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .hero-card {
                padding: 20px !important;
            }

            .hero-card h3 {
                font-size: 1.25rem;
            }

            .hero-card .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<!-- Overlay Gelap untuk Mobile Menu -->
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
                <li class="active">
                    <a href="dashboard.php"><i class="bi bi-grid-1x2-fill me-3"></i>Paparan Utama</a>
                </li>
                <li>
                    <a href="cart.php">
                        <i class="bi bi-cart-fill me-3"></i>Troli Permohonan
                        <?php if ($cart_count > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?= $cart_count; ?></span>
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
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar Header -->
        <div class="top-bar">
            <div class="d-flex align-items-center justify-content-between w-100 w-lg-auto mb-2 mb-lg-0">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">Selamat Datang, <?= htmlspecialchars($resident_name); ?></h5>
                    <small class="text-muted">
                        ke Sistem Permohonan Pelekat Penduduk <b> (ReSAS) </b> <b class="text-primary fs-6">2026</b>
                    </small>
                </div>
                <!-- Butang Menu Mobile -->
                <button type="button" class="mobile-nav-toggle d-lg-none" id="mobileSidebarToggle">
                    <i class="bi bi-list"></i>
                </button>
            </div>

            <!-- Bahagian Pengguna & Butang Log Keluar -->
            <div class="d-flex align-items-center gap-2 top-bar-user-area flex-shrink-0">
                <span class="badge text-dark border px-2.5 py-1.5 rounded-3 d-flex align-items-center gap-2 user-email-badge" title="<?= htmlspecialchars($resident_email); ?>">
                    <i class="bi bi-person-circle text-primary fs-6 flex-shrink-0"></i> 
                    <span class="text-truncate"><?= htmlspecialchars($resident_email); ?></span>
                </span>
                <button type="button" onclick="confirmLogout()" class="btn btn-outline-danger fw-semibold px-3 d-flex align-items-center gap-1.5 btn-logout-custom text-nowrap flex-shrink-0">
                    <i class="bi bi-box-arrow-right"></i> <span>Log Keluar</span>
                </button>
            </div>
        </div>

        <!-- Hero Card -->
        <div class="p-4 rounded-4 text-white mb-4 hero-card" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 1px solid rgba(255,255,255,0.1);">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="fw-extrabold mb-2 text-white">Portal Penduduk Rumah Pangsa Kajang Impian</h3>
                    <p class="text-white-50 mb-4">Urus permohonan pelekat kenderaan kediaman anda.
                         Anda boleh memohon lebih daripada satu pelekat sebelum membuat pembayaran serentak</p>
                    <a href="apply.php" class="btn btn-info fw-bold text-dark px-4 py-2 d-inline-flex align-items-center" style="border-radius: 10px;">
                        <i class="bi bi-plus-circle-fill me-2"></i> Mohon Pelekat Baru (RM10/Pelekat)
                    </a>
                </div>
                <div class="col-md-4 text-center d-none d-md-block opacity-75">
                    
                </div>
            </div>
        </div>

        <!-- Kad Bewarna Kontras Tinggi -->
        <div class="row g-3 g-md-4">
            
            <!-- Kad 1: Status Permohonan -->
            <div class="col-12 col-md-4">
                <div class="color-card-1 p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="icon-circle-dark">
                            <i class="bi bi-card-checklist"></i>
                        </div>
                        <span class="badge bg-white text-dark fw-bold px-3 py-2 rounded-pill">Status</span>
                    </div>
                    <h5 class="fw-bold mb-1 text-white">Status Permohonan</h5>
                    <p class="text-white-50 small mb-4">Ringkasan permohonan pelekat anda yang sedang diproses:</p>
                    
                    <div class="p-2 mb-2 rounded-3" style="background: rgba(255,255,255,0.15); font-weight: 600;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-white">Jumlah Permohonan</span>
                            <span class="badge bg-light text-dark fw-bold rounded-pill"><?= $total_apps; ?></span>
                        </div>
                    </div>
                    <div class="p-2 mb-4 rounded-3" style="background: rgba(255,255,255,0.15); font-weight: 600;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-white">Dalam Semakan</span>
                            <span class="badge bg-warning text-dark fw-bold rounded-pill"><?= $pending_apps; ?></span>
                        </div>
                    </div>

                    <a href="profile.php" class="btn-card-light w-100 justify-content-center">
                        Lihat Permohonan Saya <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>

            <!-- Kad 2: Troli Permohonan -->
            <div class="col-12 col-md-4">
                <div class="color-card-2 p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="icon-circle-dark">
                            <i class="bi bi-cart-check-fill"></i>
                        </div>
                        <span class="badge bg-white text-success fw-bold px-3 py-2 rounded-pill">Cart</span>
                    </div>
                    <h5 class="fw-bold mb-1 text-white">Troli Permohonan</h5>
                    <p class="text-white-50 small mb-4">Permohonan disimpan yang belum dibayar:</p>

                    <div class="p-3 mb-4 rounded-3" style="background: rgba(255,255,255,0.15);">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small fw-semibold text-white">Item Dalam Troli:</span>
                            <span class="fs-5 fw-bold text-white"><?= $cart_count; ?> Permohonan</span>
                        </div>
                        <small class="text-white-50 d-block mt-1">Jumlah: RM <?= number_format($cart_count * 10, 2); ?></small>
                    </div>
                    
                    <a href="cart.php" class="btn-card-light w-100 justify-content-center text-success">
                        Buka Troli & Bayar <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>

            <!-- Kad 3: Panduan & Informasi -->
            <div class="col-12 col-md-4">
                <div class="color-card-3 p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="icon-circle-dark">
                            <i class="bi bi-info-circle-fill"></i>
                        </div>
                        <span class="badge bg-white text-primary fw-bold px-3 py-2 rounded-pill">Info</span>
                    </div>
                    <h5 class="fw-bold mb-1 text-white">Panduan & Syarat</h5>
                    <p class="text-white-50 small mb-4">Terma permohonan pelekat dan kaedah pembayaran QR DuitNow.</p>

                    <div class="p-3 mb-4 rounded-3" style="background: rgba(255,255,255,0.15);">
                        <small class="text-white d-block mb-1"><i class="bi bi-check-circle-fill me-1"></i> RM10.00 / Pelekat Kenderaan</small>
                        <small class="text-white d-block"><i class="bi bi-qr-code-scan me-1"></i> Pembayaran QR Code DuitNow</small>
                    </div>
                    
                    <a href="info.php" class="btn-card-light w-100 justify-content-center text-primary">
                        Lihat Syarat & Info <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>

        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Pengendalian Sidebar (Mobile Navigation Toggle)
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebarPanel');
    const toggleBtn = document.getElementById('mobileSidebarToggle');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const overlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        sidebar.classList.add('show');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        document.body.style.overflow = '';
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
</script>

</body>
</html>