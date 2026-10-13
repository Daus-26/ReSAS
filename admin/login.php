
<?php
session_start();
require_once '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']       = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role']     = $admin['role'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Nama pengguna atau kata laluan tidak sah.";
        }
    } else {
        $error = "Sila isi semua ruangan.";
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Masuk Admin - ReSAS</title>
    
    <!-- Google Fonts & Bootstrap -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --primary-bg: #0b2244;
            --accent-blue: #00d2ff;
        }

        body {
            background: linear-gradient(135deg, rgba(11, 34, 68, 0.88), rgba(15, 44, 89, 0.88)), 
                        url('../assets/images/resasPicture2.JPG') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
            overflow: hidden;
        }

        .login-card .card-header {
            background-color: #0f2c59;
            color: #ffffff;
            padding: 30px 25px 25px;
            border-bottom: none;
            text-align: center;
        }

        .brand-logo {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--accent-blue);
            letter-spacing: 0.5px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 0.95rem;
            border: 1px solid #cbd5e1;
        }

        .form-control:focus {
            border-color: #0f2c59;
            box-shadow: 0 0 0 0.25rem rgba(15, 44, 89, 0.15);
        }

        .input-group-text {
            border-radius: 10px;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #0f2c59;
        }

        .btn-toggle-pw {
            border: 1px solid #cbd5e1;
            background-color: #f1f5f9;
            color: #0f2c59;
            transition: all 0.2s ease;
        }

        .btn-toggle-pw:hover {
            background-color: #e2e8f0;
            color: #0b2244;
        }

        .btn-submit {
            background-color: #0f2c59;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background-color: #0b2244;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(15, 44, 89, 0.3);
        }

        .btn-resident {
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            border: 1px solid #cbd5e1;
            color: #475569;
            background-color: #ffffff;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }

        .btn-resident:hover {
            background-color: #f8fafc;
            color: #0f2c59;
            border-color: #0f2c59;
        }

        .admin-badge {
            background: rgba(0, 210, 255, 0.15);
            color: var(--accent-blue);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

<div class="card login-card">
    <div class="card-header">
        <div class="brand-logo mb-1">
            <i class="bi bi-shield-lock-fill me-2"></i>ReSAS
        </div>
        <div>
            <span class="admin-badge">Portal Pentadbir</span>
        </div>
        <small class="text-light opacity-75 d-block mt-2">Sistem Pengurusan Pelekat Penduduk</small>
    </div>

    <div class="card-body p-4">
        <?php if ($error): ?>
            <div class="alert alert-danger py-2 px-3 small mb-3 rounded-3 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-6"></i>
                <div><?= htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary small">Nama Pengguna (Username)</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold text-secondary small">Kata Laluan</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                    <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Masukkan password" required>
                    <button class="btn btn-toggle-pw border-start-0 rounded-end-3" type="button" id="togglePassword">
                        <i class="bi bi-eye-slash" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-submit w-100 text-white mb-3">
                <i class="bi bi-box-arrow-in-right me-1"></i> Log Masuk
            </button>
        </form>

        <a href="../resident/login.php" class="btn btn-resident text-center">
            <i class="bi bi-arrow-left-circle me-1"></i> Kembali ke Portal Penduduk
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('passwordInput');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', function () {
        // Tukar jenis jenis input antara password dan text
        const isPassword = passwordInput.getAttribute('type') === 'password';
        passwordInput.setAttribute('type', isPassword ? 'text' : 'password');

        // Tukar ikon mata (bi-eye-slash <-> bi-eye)
        if (isPassword) {
            eyeIcon.classList.remove('bi-eye-slash');
            eyeIcon.classList.add('bi-eye');
        } else {
            eyeIcon.classList.remove('bi-eye');
            eyeIcon.classList.add('bi-eye-slash');
        }
    });
});
</script>

</body>
</html>