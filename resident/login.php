
<?php
session_start();

if (isset($_SESSION['resident_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error   = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';

unset($_SESSION['error']);
unset($_SESSION['success']);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk - ReSAS 2026</title>
    <!-- Google Fonts & Bootstrap -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(rgba(2, 11, 24, 0.85), rgba(15, 44, 89, 0.85)), 
                        url('../assets/images/resasPicture1.webp') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
            position: relative;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            z-index: 1;
        }

        .brand-logo-img {
            max-width: 220px;
            width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 18px; /* Hujung bujur / tidak tajam */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 12px;
        }

        .form-control {
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            font-size: 0.95rem;
            background-color: #f8fafc;
        }

        .form-control:focus {
            background-color: #ffffff;
            border-color: #2563eb;
            box-shadow: none;
        }

        .input-group:focus-within {
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            border-radius: 12px;
        }

        .input-group-text, .btn-toggle-pw {
            background-color: #f8fafc;
            border-color: #cbd5e1;
        }

        .btn-toggle-pw {
            border-left: none;
            color: #64748b;
            cursor: pointer;
        }

        .btn-toggle-pw:hover {
            color: #1e293b;
        }

        .btn-submit {
            background: linear-gradient(135deg, #0f2c59 0%, #1d4ed8 100%);
            color: #ffffff;
            padding: 13px;
            border-radius: 12px;
            font-weight: 700;
            border: none;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(29, 78, 216, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(29, 78, 216, 0.45);
            color: #ffffff;
        }

        /* BAHAGIAN BUTANG NAVIGASI ATAS */
        .top-nav-wrapper {
            position: absolute;
            top: 24px;
            left: 24px;
            right: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .btn-nav-pill {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(8px);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-nav-pill:hover {
            background: #ffffff;
            color: #0f2c59;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 576px) {
            .top-nav-wrapper {
                top: 16px;
                left: 16px;
                right: 16px;
            }
            .btn-nav-pill {
                padding: 6px 12px;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>

<div class="top-nav-wrapper">
    <!-- Butang Ke Index / Laman Utama -->
    <a href="../index.php" class="btn-nav-pill">
        <i class="bi bi-house-door-fill"></i> Laman Utama
    </a>

    <!-- Butang Admin Portal -->
    <a href="../admin/login.php" class="btn-nav-pill">
        <i class="bi bi-shield-lock-fill"></i> Admin Portal
    </a>
</div>

<div class="login-card text-center">
    
    <!-- Logo ReSAS -->
    <div class="mb-4">
        <img src="../assets/images/resasLogo.png" alt="Logo ReSAS" class="brand-logo-img">
        <p class="text-muted small mb-0 fw-bold">Log Masuk Sistem Permohonan Pelekat Penduduk</p>
    </div>

    <!-- Mesej berjaya -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 px-3 small rounded-3 mb-3 text-start" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($success); ?>
            <button type="button" class="btn-close small py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Mesej Error -->
    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3 text-start">
            <i class="bi bi-exclamation-circle-fill me-1"></i> <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Ruang Log Masuk -->
    <form action="process-login.php" method="POST" class="text-start">
        
        <div class="mb-3">
            <label class="form-label fw-semibold small text-secondary">E-mel</label>
            <div class="input-group">
                <span class="input-group-text border-end-0 rounded-start-3 text-muted"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control border-start-0" placeholder="nama@email.com" required>
            </div>
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold small text-secondary">Kata Laluan</label>
            <div class="input-group">
                <span class="input-group-text border-end-0 rounded-start-3 text-muted"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" id="passwordInput" class="form-control border-start-0 border-end-0" placeholder="Sila isi kata laluan" required>
                <button class="btn btn-toggle-pw border-start-0 rounded-end-3" type="button" id="togglePassword">
                    <i class="bi bi-eye-slash" id="eyeIcon"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-submit w-100 mb-3">
            LOG MASUK
        </button>
    </form>

    <!-- Bawa Ke Page Mendaftar Akaun -->
    <div class="pt-3 border-top mt-2">
        <span class="text-muted small">Belum mendaftar akaun?</span>
        <div class="mt-1 mb-2">
            <a href="register.php" class="fw-bold text-primary text-decoration-none">
                Daftar Akaun Baru Di Sini <i class="bi bi-arrow-right-short"></i>
            </a>
        </div>
        <div>
        <!-- Bawa Ke Page Lupa Kata Laluan -->
            <a href="forgot-password.php" class="text-secondary small text-decoration-none hover-underline">
                <i class="bi bi-key me-1"></i>Lupa Kata Laluan?
            </a>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('passwordInput');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', function () {
        const isPassword = passwordInput.getAttribute('type') === 'password';
        passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
        eyeIcon.classList.toggle('bi-eye');
        eyeIcon.classList.toggle('bi-eye-slash');
    });
</script>

</body>
</html>