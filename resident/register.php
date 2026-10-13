
<?php
session_start();

if (isset($_SESSION['resident_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akaun - ReSAS 2026</title>
    <!-- Google Fonts & Bootstrap Icons -->
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
            padding: 30px 20px;
            margin: 0;
        }

        .register-card {
            width: 100%;
            max-width: 480px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .brand-icon-box {
            width: 56px;
            height: 56px;
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
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
    </style>
</head>
<body>

<div class="register-card text-center">
    
    <div class="mb-3">
        <div class="brand-icon-box">
            <i class="bi bi-person-plus-fill"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1">Daftar Akaun Penduduk</h4>
        <p class="text-muted small mb-0"><span class="text-danger">*</span> Ruangan wajib diisi</p>
    </div>

    <!-- Mesej Ralat -->
    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 small rounded-3 mb-3 text-start">
            <i class="bi bi-exclamation-circle-fill me-1"></i> <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Borang Pendaftaran -->
    <form action="process-register.php" method="POST" class="text-start">
        
        <!-- Nama Penuh -->
        <div class="mb-3">
            <label class="form-label fw-semibold small text-secondary">
                Nama Penuh <span class="text-danger">*</span>
            </label>
            <div class="input-group">
                <span class="input-group-text border-end-0 rounded-start-3 text-muted"><i class="bi bi-person"></i></span>
                <input type="text" name="name" class="form-control border-start-0" placeholder="Masukkan nama penuh" required>
            </div>
        </div>

        <!-- E-mel -->
        <div class="mb-3">
            <label class="form-label fw-semibold small text-secondary">
                Alamat E-mel <span class="text-danger">*</span>
            </label>
            <div class="input-group">
                <span class="input-group-text border-end-0 rounded-start-3 text-muted"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control border-start-0" placeholder="nama@email.com" required>
            </div>
        </div>

        <!-- No. Telefon -->
        <div class="mb-3">
            <label class="form-label fw-semibold small text-secondary">
                No. Telefon <span class="text-danger">*</span>
            </label>
            <div class="input-group">
                <span class="input-group-text border-end-0 rounded-start-3 text-muted"><i class="bi bi-telephone"></i></span>
                <input type="text" name="phone_number" class="form-control border-start-0" placeholder="Contoh: 0123456789" required>
            </div>
        </div>

        <!-- Alamat Rumah -->
        <div class="mb-3">
            <label class="form-label fw-semibold small text-secondary">
                Alamat Rumah (No. Unit / Blok) <span class="text-danger">*</span>
            </label>
            <div class="input-group">
                <span class="input-group-text border-end-0 rounded-start-3 text-muted"><i class="bi bi-geo-alt"></i></span>
                <textarea name="address" class="form-control border-start-0" rows="2" placeholder="Contoh: B-03-11, Blok B" required></textarea>
            </div>
        </div>

        <!-- Kata Laluan -->
        <div class="mb-3">
            <label class="form-label fw-semibold small text-secondary">
                Kata Laluan <span class="text-danger">*</span> (Pastikan mengandungi huruf,nombor & simbol)
            </label>
            <div class="input-group">
                <span class="input-group-text border-end-0 rounded-start-3 text-muted"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" id="passwordInput" class="form-control border-start-0 border-end-0" placeholder="Sila masukkan kata laluan (min 6 aksara)" required>
                <button class="btn btn-toggle-pw border-start-0 rounded-end-3" type="button" id="togglePassword">
                    <i class="bi bi-eye-slash" id="eyeIcon"></i>
                </button>
            </div>
            
            <!-- Meter Kekuatan Kata Laluan -->
            <div class="progress mt-2" style="height: 5px; display: none;" id="strengthProgress">
                <div id="strengthBar" class="progress-bar" role="progressbar" style="width: 0%;"></div>
            </div>
            <small id="strengthText" class="d-block mt-1 fw-bold" style="font-size: 0.75rem;"></small>
        </div>

        <!-- Pengesahan Kata Laluan -->
        <div class="mb-4">
            <label class="form-label fw-semibold small text-secondary">
                Sahkan Kata Laluan <span class="text-danger">*</span>
            </label>
            <div class="input-group">
                <span class="input-group-text border-end-0 rounded-start-3 text-muted"><i class="bi bi-lock-fill"></i></span>
                <input type="password" name="password_confirm" id="confirmPasswordInput" class="form-control border-start-0 border-end-0" placeholder="Ulang kata laluan" required>
                <button class="btn btn-toggle-pw border-start-0 rounded-end-3" type="button" id="toggleConfirmPassword">
                    <i class="bi bi-eye-slash" id="eyeIconConfirm"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-submit w-100 mb-3">
            DAFTAR AKAUN
        </button>
    </form>

    <div class="pt-3 border-top mt-2">
        <span class="text-muted small">Sudah mempunyai akaun?</span>
        <div class="mt-1">
            <a href="login.php" class="fw-bold text-primary text-decoration-none">
                Log Masuk Di Sini <i class="bi bi-arrow-right-short"></i>
            </a>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function setupPasswordToggle(buttonId, inputId, iconId) {
        const toggleBtn = document.getElementById(buttonId);
        const inputField = document.getElementById(inputId);
        const iconField = document.getElementById(iconId);

        toggleBtn.addEventListener('click', function () {
            const isPassword = inputField.getAttribute('type') === 'password';
            inputField.setAttribute('type', isPassword ? 'text' : 'password');
            iconField.classList.toggle('bi-eye');
            iconField.classList.toggle('bi-eye-slash');
        });
    }

    setupPasswordToggle('togglePassword', 'passwordInput', 'eyeIcon');
    setupPasswordToggle('toggleConfirmPassword', 'confirmPasswordInput', 'eyeIconConfirm');

    // Skrip Semakan Kekuatan Kata Laluan
    const passwordInput = document.getElementById('passwordInput');
    const strengthProgress = document.getElementById('strengthProgress');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');

    passwordInput.addEventListener('input', function () {
        const val = passwordInput.value;
        if (val.length === 0) {
            strengthProgress.style.display = 'none';
            strengthText.textContent = '';
            return;
        }

        strengthProgress.style.display = 'flex';
        
        const hasLetter = /[a-zA-Z]/.test(val);
        const hasNumber = /[0-9]/.test(val);
        const hasSymbol = /[^a-zA-Z0-9]/.test(val);

        if (!hasLetter || !hasNumber || !hasSymbol || val.length < 6) {
            strengthBar.style.width = '33%';
            strengthBar.className = 'progress-bar bg-danger';
            strengthText.textContent = 'Lemah (Perlu gabungan huruf, nombor & simbol - Min 6 aksara)';
            strengthText.className = 'd-block mt-1 text-danger fw-bold';
        } else if (val.length >= 6 && val.length < 10) {
            strengthBar.style.width = '66%';
            strengthBar.className = 'progress-bar bg-warning';
            strengthText.textContent = 'Sederhana (Memenuhi syarat asas)';
            strengthText.className = 'd-block mt-1 text-warning fw-bold';
        } else {
            strengthBar.style.width = '100%';
            strengthBar.className = 'progress-bar bg-success';
            strengthText.textContent = 'Sangat Kuat!';
            strengthText.className = 'd-block mt-1 text-success fw-bold';
        }
    });
</script>

</body>
</html>