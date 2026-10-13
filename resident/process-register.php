
<?php
session_start();

if (file_exists('../config/db.php')) {
    require_once '../config/db.php';
} else {
    require_once 'config/db.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $name             = trim($_POST['name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone_number     = trim($_POST['phone_number'] ?? '');
    $address          = trim($_POST['address'] ?? '');
    $password         = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');

    if (empty($name) || empty($email) || empty($phone_number) || empty($address) || empty($password) || empty($password_confirm)) {
        $_SESSION['error'] = "Sila lengkapkan semua ruangan pendaftaran.";
        header("Location: register.php");
        exit();
    }

    if ($password !== $password_confirm) {
        $_SESSION['error'] = "Kata laluan dan pengesahan kata laluan tidak sepadan!";
        header("Location: register.php");
        exit();
    }

    // Semak format kata laluan (Min 6 aksara, Mesti ada huruf, nombor & simbol)
    $hasLetter = preg_match('/[a-zA-Z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    $hasSymbol = preg_match('/[^a-zA-Z0-9]/', $password);

    if (strlen($password) < 6 || !$hasLetter || !$hasNumber || !$hasSymbol) {
        $_SESSION['error'] = "Kata laluan hendaklah sekurang-kurangnya 6 aksara dan mengandungi gabungan huruf, nombor, serta simbol!";
        header("Location: register.php");
        exit();
    }

    try {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM resident WHERE email = ?");
        $checkStmt->execute([$email]);

        if ($checkStmt->fetchColumn() > 0) {
            $_SESSION['error'] = "E-mel ini telah pun didaftarkan!";
            header("Location: register.php");
            exit();
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $insertStmt = $pdo->prepare("INSERT INTO resident (name, email, phone_number, address, password) VALUES (?, ?, ?, ?, ?)");
        
        if ($insertStmt->execute([$name, $email, $phone_number, $address, $hashed_password])) {
            $_SESSION['success'] = "Pendaftaran anda berjaya";
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['error'] = "Pendaftaran gagal. Sila cuba lagi.";
            header("Location: register.php");
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Ralat pangkalan data: " . $e->getMessage();
        header("Location: register.php");
        exit();
    }

} else {
    header("Location: register.php");
    exit();
}