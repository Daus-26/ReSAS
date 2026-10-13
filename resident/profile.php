
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
$cart_count = 0;

// Flash Messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

try {
    // 1. Dapatkan Maklumat Penduduk
    $stmtUser = $pdo->prepare("SELECT * FROM resident WHERE resident_id = ?");
    $stmtUser->execute([$resident_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: logout.php");
        exit();
    }

    // 2. Dapatkan Bilangan Permohonan Menunggu Bayaran / Dalam Troli
    $stmtCart = $pdo->prepare("SELECT COUNT(*) FROM application WHERE resident_id = ? AND status IN ('Dalam Troli', 'Menunggu Bayaran', 'Menunggu Pembayaran')");
    $stmtCart->execute([$resident_id]);
    $cart_count = $stmtCart->fetchColumn();

    // 3. Dapatkan Senarai Permohonan Bersama Maklumat Kenderaan
    $stmtApp = $pdo->prepare("
        SELECT a.application_id, a.submission_date, a.status, a.remarks,
               v.vehicle_id, v.plate_number, v.brand, v.model, v.color, v.vehicle_type
        FROM application a
        JOIN vehicle v ON a.vehicle_id = v.vehicle_id
        WHERE a.resident_id = ?
        ORDER BY a.submission_date DESC
    ");
    $stmtApp->execute([$resident_id]);
    $applications = $stmtApp->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Ralat Pangkalan Data: " . htmlspecialchars($e->getMessage()));
}

$resident_name  = $user['name'] ?? 'Penduduk';
$resident_email = $user['email'] ?? 'penduduk@example.com';
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permohonan Saya & Profil - ReSAS 2026</title>
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

        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            height: 100%;
        }

        .info-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 0.95rem;
            font-weight: 700;
            color: #0f172a;
            margin-top: 4px;
            word-break: break-word;
        }

        /* GAYA KHAS LOGOUT BUTTON, TROLI & BADGE EMAIL */
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
                <li>
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
                <li class="active">
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
                    <h5 class="fw-bold mb-1 text-dark">Permohonan Saya & Profil</h5>
                    <small class="text-muted">Semak maklumat profil dan status kelulusan pelekat kenderaan anda.</small>
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

        <!-- Kad Maklumat Profil -->
        <div class="custom-card mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3 flex-wrap gap-2">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-person-badge-fill text-primary me-2"></i>Maklumat Penduduk
                </h5>
                <div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold">
                        <i class="bi bi-check-circle-fill me-1"></i> Akaun Aktif
                    </span>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-label d-block">Nama Penuh</span>
                        <div class="info-value"><?= htmlspecialchars($user['name'] ?? '-'); ?></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-label d-block">Alamat E-mel</span>
                        <div class="info-value"><?= htmlspecialchars($user['email'] ?? '-'); ?></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-label d-block">No. Telefon</span>
                        <div class="info-value"><?= htmlspecialchars($user['phone_number'] ?? '-'); ?></div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-label d-block">Unit Kediaman</span>
                        <div class="info-value"><?= htmlspecialchars($user['address'] ?? '-'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kad Senarai Permohonan -->
        <div class="custom-card">
            <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3 flex-wrap gap-2">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-car-front-fill text-primary me-2"></i>Status Permohonan Pelekat Kenderaan
                </h5>
                <a href="apply.php" class="btn btn-sm btn-primary fw-bold px-3 py-2" style="background-color: #0f2c59; border-color: #0f2c59; border-radius: 8px;">
                    <i class="bi bi-plus-lg me-1"></i> Mohon Pelekat Baru
                </a>
            </div>

            <?php if (empty($applications)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted display-4 d-block mb-3"></i>
                    <h6 class="fw-bold text-secondary">Tiada Permohonan Ditemui</h6>
                    <p class="text-muted small mb-4">Anda belum membuat sebarang permohonan pelekat kenderaan lagi.</p>
                    <a href="apply.php" class="btn btn-primary fw-bold px-4 py-2" style="background-color: #0f2c59; border-color: #0f2c59; border-radius: 10px;">
                        <i class="bi bi-plus-circle me-1"></i> Mohon Pelekat Sekarang
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="text-secondary small text-uppercase">
                                <th style="width: 5%;">#</th>
                                <th>No. Plat</th>
                                <th>Kenderaan</th>
                                <th>Jenis</th>
                                <th>Tarikh Mohon</th>
                                <th>Status Permohonan</th>
                                <th class="text-center">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $index => $app): ?>
                                <tr>
                                    <td class="fw-bold text-secondary"><?= $index + 1; ?></td>
                                    <td>
                                        <span class="badge bg-dark text-light px-2 py-1 fs-6 font-monospace">
                                            <?= htmlspecialchars($app['plate_number']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">
                                            <?= htmlspecialchars($app['brand'] ?? ''); ?> <?= htmlspecialchars($app['model']); ?>
                                        </div>
                                        <small class="text-muted">Warna: <?= htmlspecialchars($app['color']); ?></small>
                                    </td>
                                    <td class="fw-semibold"><?= htmlspecialchars($app['vehicle_type']); ?></td>
                                    <td class="small text-muted">
                                        <?= date('d/m/Y h:i A', strtotime($app['submission_date'])); ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $st = strtolower(trim($app['status']));
                                            
                                            // 1. Sebelum Bayar: Status 'Menunggu Bayaran' / 'Menunggu Pembayaran' / 'Dalam Troli'
                                            if (in_array($st, ['menunggu pembayaran', 'menunggu bayaran', 'dalam troli'])) {
                                                echo '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 rounded-pill fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> Menunggu Bayaran</span>';
                                            
                                            // 2. Selepas Bayar: Status 'Dalam Proses' atau 'Sudah Dibayar'
                                            } elseif (in_array($st, ['dalam proses', 'sudah dibayar'])) {
                                                echo '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-bold"><i class="bi bi-clock-history me-1"></i> Dalam Proses</span>';
                                            
                                            // 3. Status Keputusan Pentadbir
                                            } elseif (in_array($st, ['diluluskan', 'lulus'])) {
                                                echo '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Diluluskan</span>';
                                            } elseif ($st == 'ditolak') {
                                                echo '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill fw-bold"><i class="bi bi-x-circle-fill me-1"></i> Ditolak</span>';
                                            } else {
                                                echo '<span class="badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill fw-bold"><i class="bi bi-clock-history me-1"></i> Dalam Proses</span>';
                                            }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
                                            <!-- Butang Bayar RM10 (Di KIRI ulasan jika belum dibayar) -->
                                            <?php if (in_array(strtolower(trim($app['status'])), ['menunggu pembayaran', 'menunggu bayaran', 'dalam troli'])): ?>
                                                <a href="cart.php" class="btn btn-sm btn-success fw-bold px-3 py-1 d-flex align-items-center gap-1" style="border-radius: 8px;">
                                                    <i class="bi bi-credit-card-fill"></i> Bayar RM10
                                                </a>
                                            <?php endif; ?>

                                            <!-- Butang Ulasan Pentadbir -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-info fw-bold px-2 py-1 d-flex align-items-center gap-1" 
                                                    style="border-radius: 8px;" 
                                                    onclick="viewRemarks('<?= htmlspecialchars(addslashes($app['remarks'] ?? ''), ENT_QUOTES); ?>')" 
                                                    title="Lihat Ulasan Pentadbir">
                                                <i class="bi bi-chat-left-text-fill"></i> Ulasan
                                            </button>

                                            <!-- Butang Padam -->
                                            <button type="button" 
                                                    onclick="confirmDelete(<?= $app['application_id']; ?>, '<?= htmlspecialchars($app['plate_number']); ?>')" 
                                                    class="btn btn-sm btn-outline-danger fw-bold px-2 py-1" 
                                                    style="border-radius: 8px;" 
                                                    title="Padam Permohonan">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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

function viewRemarks(remarks) {
    var message = (remarks && remarks.trim() !== '') ? remarks : 'Tiada sebarang ulasan atau catatan daripada pihak pentadbir.';
    Swal.fire({
        title: 'Ulasan Pentadbir',
        text: message,
        icon: 'info',
        confirmButtonColor: '#0f2c59',
        confirmButtonText: 'Tutup'
    });
}

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

function confirmDelete(appId, plateNum) {
    Swal.fire({
        title: 'Padam Permohonan?',
        text: 'Adakah anda pasti untuk memadam permohonan untuk plat ' + plateNum + '? Tindakan ini tidak boleh dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#0f2c59',
        confirmButtonText: 'Ya, Padam!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'delete-application.php?id=' + appId;
        }
    });
}
</script>

<?php if (!empty($flash_success)): ?>
<script>
    Swal.fire({
        title: 'Berjaya!',
        text: '<?= htmlspecialchars($flash_success); ?>',
        icon: 'success',
        confirmButtonColor: '#0f2c59'
    });
</script>
<?php endif; ?>

<?php if (!empty($flash_error)): ?>
<script>
    Swal.fire({
        title: 'Ralat!',
        text: '<?= htmlspecialchars($flash_error); ?>',
        icon: 'error',
        confirmButtonColor: '#dc3545'
    });
</script>
<?php endif; ?>

<?php if (isset($_GET['payment']) && $_GET['payment'] === 'success'): ?>
<script>
    Swal.fire({
        title: 'Pembayaran Berjaya!',
        text: 'Bayaran anda telah diterima. Permohonan pelekat anda kini sedang diproses oleh pihak pengurusan.',
        icon: 'success',
        confirmButtonColor: '#0f2c59'
    });
</script>
<?php endif; ?>

<?php if (isset($_GET['delete']) && $_GET['delete'] === 'success'): ?>
<script>
    Swal.fire({
        title: 'Dipadam!',
        text: 'Permohonan telah berjaya dipadamkan.',
        icon: 'success',
        confirmButtonColor: '#0f2c59'
    });
</script>
<?php endif; ?>

</body>
</html>