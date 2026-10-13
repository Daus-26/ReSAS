
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

$resident_id    =$_SESSION['resident_id'];
$resident_name  =$_SESSION['resident_name'] ?? 'Penduduk';
$resident_email =$_SESSION['resident_email'] ?? 'penduduk@example.com';
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mohon Pelekat - ReSAS 2026</title>
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

        .section-header {
            border-bottom: 2px dashed #e2e8f0;
            padding-bottom: 12px;
            margin-bottom: 24px;
        }

        .upload-card {
            background-color: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 20px;
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
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
    <!-- Sidebar -->
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
                <li class="active"><a href="cart.php"><i class="bi bi-cart-fill me-3"></i>Troli Permohonan</a></li>
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
                    <h5 class="fw-bold mb-1 text-dark">Borang Permohonan Pelekat Kenderaan</h5>
                    <small class="text-muted">
                        Isi maklumat kenderaan. Anda boleh menambah beberapa permohonan ke dalam troli sebelum membuat pembayaran.
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

                <a href="cart.php" class="btn btn-outline-primary fw-bold btn-cart-custom">
                    <i class="bi bi-cart-check-fill me-1"></i> Lihat Troli
                </a>
            </div>
        </div>

        <!-- Custom Card / Borang Permohonan -->
        <div class="custom-card mb-4">
            <form action="process-apply.php" method="POST" enctype="multipart/form-data" id="applyForm">
                
                <!-- SEKSYEN 1: MAKLUMAT KENDERAAN -->
                <div class="section-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-car-front-fill text-primary me-2"></i>1. Maklumat Kenderaan
                    </h5>
                    <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill">
                        Kadar: RM 10.00 / Pelekat
                    </span>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label for="plate_number" class="form-label fw-semibold text-dark">
                            Nombor Pendaftaran Kenderaan (Plat) <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="plate_number" id="plate_number" class="form-control text-uppercase fw-bold" placeholder="Cth: VAB 1234" required>
                    </div>

                    <div class="col-md-6">
                        <label for="vehicle_type" class="form-label fw-semibold text-dark">
                            Jenis Kenderaan <span class="text-danger">*</span>
                        </label>
                        <select name="vehicle_type" id="vehicle_type" class="form-select" required>
                            <option value="" selected disabled>-- Pilih Jenis Kenderaan --</option>
                            <option value="Kereta">Kereta</option>
                            <option value="Motosikal">Motosikal</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="brand" class="form-label fw-semibold text-dark">
                            Jenama Kenderaan <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="brand" id="brand" class="form-control" placeholder="Cth: Perodua / Honda / Yamaha" required>
                    </div>

                    <div class="col-md-6">
                        <label for="model" class="form-label fw-semibold text-dark">
                            Model Kenderaan <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="model" id="model" class="form-control" placeholder="Cth: Myvi / City / Y15ZR" required>
                    </div>

                    <div class="col-md-6">
                        <label for="color" class="form-label fw-semibold text-dark">
                            Warna Kenderaan <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="color" id="color" class="form-control" placeholder="Cth: Hitam / Perak / Putih" required>
                    </div>

                    <div class="col-md-6">
                        <label for="year" class="form-label fw-semibold text-dark">
                            Tahun Kenderaan <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="year" id="year" class="form-control" placeholder="Cth: 2022" min="1980" max="2026" required>
                    </div>
                </div>

                <!-- SEKSYEN 2: DOKUMEN SOKONGAN -->
                <div class="section-header mt-5">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-file-earmark-arrow-up-fill text-primary me-2"></i>2. Muat Naik Dokumen Sokongan
                    </h5>
                    <small class="text-muted">Format: PDF, JPG, JPEG, PNG (Maksimal 10MB per fail)</small>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="upload-card">
                            <label for="grant_file" class="form-label fw-semibold text-dark mb-1">
                                Geran Kenderaan <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="grant_file" id="grant_file" class="form-control form-control-sm mt-2" accept="image/jpeg,image/png,image/jpg,application/pdf" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="upload-card">
                            <label for="ic_file" class="form-label fw-semibold text-dark mb-1">
                                Kad Pengenalan (IC) <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="ic_file" id="ic_file" class="form-control form-control-sm mt-2" accept="image/jpeg,image/png,image/jpg,application/pdf" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="upload-card">
                            <label for="residence_file" class="form-label fw-semibold text-dark mb-1">
                                Bukti Kediaman <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="residence_file" id="residence_file" class="form-control form-control-sm mt-2" accept="image/jpeg,image/png,image/jpg,application/pdf" required>
                        </div>
                    </div>
                </div>

                <!-- NOTIS ADD TO CART -->
                <div class="p-3 mb-4 rounded-3 bg-light border d-flex align-items-center gap-3">
                    <i class="bi bi-cart-plus-fill text-primary fs-3 flex-shrink-0"></i>
                    <div class="small text-muted">
                        Borang ini akan disimpan ke dalam <strong>Troli Permohonan</strong>. Selepas ini anda boleh memilih untuk <strong>Memohon Pelekat Kenderaan Lain</strong> atau terus ke pembayaran <strong>QR Code (RM10/Pelekat)</strong>.
                    </div>
                </div>

                <!-- BUTANG SUBMIT -->
                <div class="d-flex justify-content-end gap-3 pt-2">
                    <a href="dashboard.php" class="btn btn-light fw-bold px-4 py-2 border" style="border-radius: 10px;">
                        Batal
                    </a>
                    <button type="submit" id="submitBtn" class="btn btn-primary fw-bold px-4 py-2" style="background-color: #0f2c59; border-color: #0f2c59; border-radius: 10px;">
                        <i class="bi bi-cart-plus me-2"></i> Simpan & Tambah Ke Troli
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Mobile Sidebar Menu
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

// Semakan Saiz Fail dan Animasi Loading Muat Naik
document.getElementById('applyForm').addEventListener('submit', function(e) {
    const maxSizeBytes = 10 * 1024 * 1024; // Limit 10MB per fail
    const fileInputs = [
        { id: 'grant_file', name: 'Geran Kenderaan' },
        { id: 'ic_file', name: 'Kad Pengenalan' },
        { id: 'residence_file', name: 'Bukti Kediaman' }
    ];

    for (let inputObj of fileInputs) {
        const input = document.getElementById(inputObj.id);
        if (input && input.files.length > 0) {
            const file = input.files[0];
            if (file.size > maxSizeBytes) {
                e.preventDefault();
                Swal.fire({
                    title: 'Fail Terlalu Besar!',
                    text: `Fail ${inputObj.name} melebihi had 10MB (${(file.size / (1024 * 1024)).toFixed(1)}MB). Sila kecilkan saiz gambar/fail anda sebelum memuat naik.`,
                    icon: 'warning',
                    confirmButtonColor: '#0f2c59'
                });
                return false;
            }
        }
    }

    // Paparkan Popup Loading semasa muat naik berlangsung
    Swal.fire({
        title: 'Memproses Permohonan...',
        text: 'Sila tunggu sebentar sementara fail dokumen anda dimuat naik ke sistem.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
});

// Fungsi Log Keluar
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

<?php if (isset($_GET['error'])): ?>
<script>
    let errorTitle = 'Ralat!';
    let errorMsg = 'Sila pastikan semua borang diisi dengan betul.';
    
    <?php if($_GET['error'] == 'empty'): ?>
        errorMsg = 'Sila isi semua maklumat bertanda * dan muat naik dokumen yang diperlukan.';
    <?php elseif($_GET['error'] == 'file_type'): ?>
        errorMsg = 'Format fail tidak sah! Sila muat naik fail PDF, JPG, JPEG, atau PNG sahaja.';
    <?php elseif($_GET['error'] == 'file_size'): ?>
        errorMsg = 'Salah satu fail dokumen melebihi had saiz maksimum (10MB). Sila kecilkan saiz fail anda.';
    <?php elseif($_GET['error'] == 'post_limit'): ?>
        errorTitle = 'Jumlah Saiz Fail Terlalu Besar!';
        errorMsg = 'Jumlah keseluruhan fail yang dimuat naik melebihi had server. Sila mampatkan/kecilkan saiz gambar anda.';
    <?php endif; ?>

    Swal.fire({
        title: errorTitle,
        text: errorMsg,
        icon: 'error',
        confirmButtonColor: '#0f2c59',
        confirmButtonText: 'Cuba Lagi'
    });
</script>
<?php endif; ?>

</body>
</html>