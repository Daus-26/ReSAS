
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
$resident_email = $_SESSION['resident_email'] ?? 'penduduk@example.com';

try {
    // Dapatkan maklumat e-mel terkini penduduk daripada pangkalan data
    $stmtUser = $pdo->prepare("SELECT email FROM resident WHERE resident_id = ?");
    $stmtUser->execute([$resident_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if ($user && !empty($user['email'])) {
        $resident_email = $user['email'];
    }

    // Dapatkan bilangan permohonan dalam troli / menunggu bayaran
    $stmtCart = $pdo->prepare("SELECT COUNT(*) FROM application WHERE resident_id = ? AND status IN ('Dalam Troli', 'Menunggu Bayaran', 'Menunggu Pembayaran')");
    $stmtCart->execute([$resident_id]);
    $cart_count = $stmtCart->fetchColumn();
} catch (PDOException $e) {
    // Pengendalian ralat sambungan pangkalan data
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informasi & Syarat - ReSAS 2026</title>
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

        .info-card { 
            background-color: #ffffff; 
            border-radius: 16px; 
            box-shadow: var(--card-shadow); 
            border: 1px solid #e2e8f0; 
            overflow: hidden;
        }

        .info-card-header { 
            background-color: #0f2c59; 
            color: #ffffff; 
            padding: 18px 25px; 
        }

        .rule-box {
            background-color: #f8fafc;
            border-left: 4px solid #0f2c59;
            border-radius: 8px;
            padding: 20px;
            height: 100%;
        }

        .rule-box.warning-rule {
            border-left-color: #f59e0b;
            background-color: #fffbeb;
        }

        .rule-box.danger-rule {
            border-left-color: #ef4444;
            background-color: #fef2f2;
        }

        .step-number {
            width: 42px;
            height: 42px;
            min-width: 42px;
            background-color: #0f2c59;
            color: var(--accent-blue);
            font-weight: 800;
            font-size: 1.1rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .contact-box {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .contact-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .accordion-button:not(.collapsed) {
            background-color: #f1f5f9;
            color: #0f2c59;
            font-weight: 700;
        }

        .accordion-button:focus {
            box-shadow: none;
            border-color: rgba(15, 44, 89, 0.125);
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
                <li>
                    <a href="cart.php">
                        <i class="bi bi-cart-fill me-3"></i>Troli Permohonan
                        <?php if ($cart_count > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-auto"><?= $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="profile.php"><i class="bi bi-clock-history me-3"></i>Permohonan Saya</a></li>
                <li class="active"><a href="info.php"><i class="bi bi-info-circle-fill me-3"></i>Informasi</a></li>
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
                    <h5 class="fw-bold mb-1 text-dark">Informasi & Syarat Permohonan</h5>
                    <small class="text-muted">Panduan lengkap, syarat pendaftaran serta info yang perlu dibaca sebelum memohon</small>
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

        <!-- SEKSYEN 1: SYARAT & PERATURAN UTAMA -->
        <div class="card info-card mb-4">
            <div class="info-card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-bold"><i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>Syarat & Had Pendaftaran Kenderaan</h6>
                <span class="badge bg-warning text-dark fw-bold">Wajib Dibaca</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <!-- Peraturan 1: Had Limit 2 Kereta & 2 Motosikal -->
                    <div class="col-md-4">
                        <div class="rule-box warning-rule">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="bg-warning text-dark rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-p-square-fill fs-5"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-0">Had Kuota Kediaman</h6>
                            </div>
                            <p class="text-secondary small mb-2">
                                Setiap unit kediaman/rumah ditetapkan had maksimum pendaftaran pelekat kenderaan:
                            </p>
                            <ul class="text-dark small mb-0 ps-3 fw-semibold">
                                <li>Maksimum <strong>2 pendaftaran bagi Kereta</strong></li>
                                <li>Maksimum <strong>2 pendaftaran bagi Motosikal</strong></li>
                            </ul>
                            <small class="text-muted d-block mt-2">Permohonan yang melebihi kuota tidak akan diproses oleh pihak pengurusan.</small>
                        </div>
                    </div>

                    <!-- Peraturan 2: Limit Masa Pembayaran (3 Hari Bekerja) -->
                    <div class="col-md-4">
                        <div class="rule-box danger-rule">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="bg-danger text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-hourglass-split fs-5"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-0">Had Masa Pembayaran</h6>
                            </div>
                            <p class="text-secondary small mb-2">
                                Untuk setiap permohonan pendaftaran yang <strong>tidak dijelaskan pembayaran sehingga limit masa 3 hari bekerja</strong>:
                            </p>
                            <ul class="text-danger small mb-0 ps-3 fw-bold">
                                <li>Permohonan akan automatik ditolak oleh pihak pengurusan</li>
                                <li>Pemohon perlu membuat pendaftaran baharu</li>
                            </ul>
                            <small class="text-muted d-block mt-2">Sila selesaikan pembayaran dalam troli dengan kadar segera.</small>
                        </div>
                    </div>

                    <!-- Peraturan 3: Tempoh Sah & Pembaharuan Tahunan -->
                    <div class="col-md-4">
                        <div class="rule-box">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="bi bi-calendar-check-fill fs-5"></i>
                                </div>
                                <h6 class="fw-bold text-dark mb-0">Pembaharuan Tahunan</h6>
                            </div>
                            <p class="text-secondary small mb-2">
                                Setiap pelekat kenderaan yang dikeluarkan <strong>hanya sah selama 1 tahun sahaja</strong>.
                            </p>
                            <p class="text-secondary small mb-0">
                                Semua penduduk wajib mendaftar semula dan memperbaharui permohonan pelekat pada <strong>setiap awal tahun</strong> menerusi portal ReSAS ini.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEKSYEN 2: PANDUAN PERMOHONAN -->
        <div class="card info-card mb-4">
            <div class="info-card-header">
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-check me-2 text-info"></i>Langkah Demi Langkah Permohonan Pelekat</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="step-number">1</div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Isi Maklumat Kenderaan</h6>
                                <p class="text-muted small mb-0">Lengkapkan nombor plat, jenis kenderaan, jenama, model, dan muat naik dokumen sokongan yang sah (Geran Kenderaan / Insurans / Bukti Kediaman).</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="step-number">2</div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Simpan Ke Troli & Bayar QR</h6>
                                <p class="text-muted small mb-0">Permohonan disimpan dalam <b>Troli Permohonan</b>. Buat pembayaran <b>RM 10.00</b> bagi setiap pelekat menerusi imbuhan kod DuitNow QR dalam tempoh 3 hari bekerja.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="step-number">3</div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Semakan Status Permohonan</h6>
                                <p class="text-muted small mb-0">Status semakan dan pengesahan borang boleh dipantau secara langsung melalui menu <b>Permohonan Saya</b>.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="step-number">4</div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Tuntut Pelekat Fizikal</h6>
                                <p class="text-muted small mb-0">Setelah status ditukar kepada <b>Diluluskan</b>, sila hadir mengambil pelekat fizikal di Pejabat Pengurusan Kediaman bersama bukti resit.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEKSYEN 3: SOALAN LAZIM (FAQ) -->
        <div class="card info-card mb-4">
            <div class="info-card-header">
                <h6 class="mb-0 fw-bold"><i class="bi bi-question-circle-fill me-2 text-info"></i>Soalan Lazim (FAQ)</h6>
            </div>
            <div class="card-body p-4">
                <div class="accordion accordion-flush" id="faqAccordion">
                    <div class="accordion-item border rounded-3 mb-2 overflow-hidden">
                        <h2 class="accordion-header" id="faqHeadingOne">
                            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseOne" aria-expanded="false" aria-controls="faqCollapseOne">
                                Berapa lamakah tempoh masa kelulusan permohonan pelekat?
                            </button>
                        </h2>
                        <div id="faqCollapseOne" class="accordion-collapse collapse" aria-labelledby="faqHeadingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary small">
                                Proses semakan dokumen dan pengesahan pembayaran biasanya mengambil masa 1 hingga 3 hari bekerja. Pengguna boleh menyemak status permohonan terkini di menu <strong>Permohonan Saya</strong>.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border rounded-3 mb-2 overflow-hidden">
                        <h2 class="accordion-header" id="faqHeadingTwo">
                            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseTwo" aria-expanded="false" aria-controls="faqCollapseTwo">
                                Apakah yang terjadi jika saya tidak membuat pembayaran dalam masa 3 hari bekerja?
                            </button>
                        </h2>
                        <div id="faqCollapseTwo" class="accordion-collapse collapse" aria-labelledby="faqHeadingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary small">
                                Permohonan anda akan <strong>automatik ditolak</strong> oleh pihak pengurusan dan dipadam dari rekod proses. Anda perlu membuat permohonan pendaftaran baharu sekiranya masih memerlukan pelekat.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border rounded-3 overflow-hidden">
                        <h2 class="accordion-header" id="faqHeadingThree">
                            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapseThree" aria-expanded="false" aria-controls="faqCollapseThree">
                                Bolehkah saya mendaftar lebih daripada 2 buah kereta?
                            </button>
                        </h2>
                        <div id="faqCollapseThree" class="accordion-collapse collapse" aria-labelledby="faqHeadingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-secondary small">
                                Tidak boleh. Setiap unit rumah terhad kepada 2 buah kereta dan 2 buah motosikal sahaja demi menampung kuota ruang tempat letak kenderaan kawasan kediaman.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEKSYEN 4: HUBUNGI PENGURUSAN -->
        <div class="card info-card">
            <div class="info-card-header">
                <h6 class="mb-0 fw-bold"><i class="bi bi-headset me-2 text-info"></i>Hubungi Pihak Pengurusan</h6>
            </div>
            <div class="card-body p-4">
                <p class="text-muted mb-3">Sekiranya anda mempunyai sebarang pertanyaan berkenaan kuota pendaftaran atau masalah teknikal sistem, sila hubungi:</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light contact-box">
                            <small class="text-muted d-block mb-1"><i class="bi bi-envelope-fill me-2 text-primary"></i> E-mel Rasmi</small>
                            <span class="fw-bold text-dark small">adminrumahpangsa@kajangimpian.com</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light contact-box">
                            <small class="text-muted d-block mb-1"><i class="bi bi-telephone-fill me-2 text-success"></i> Talian Pejabat</small>
                            <span class="fw-bold text-dark small">016-3907113</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded-3 bg-light contact-box">
                            <small class="text-muted d-block mb-1"><i class="bi bi-clock-fill me-2 text-warning"></i> Waktu Operasi</small>
                            <span class="fw-bold text-dark small">Isnin - Jumaat (8:00 AM - 5:00 PM)</span>
                        </div>
                    </div>
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