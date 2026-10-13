
<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email            = trim($_POST['email'] ?? '');
    $phone_number     = trim($_POST['phone_number'] ?? '');
    $new_password     = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($email) || empty($phone_number) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = "Sila lengkapkan semua ruangan yang disediakan.";
        header("Location: forgot-password.php");
        exit();
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Kata laluan baharu dan pengesahan tidak sepadan!";
        header("Location: forgot-password.php");
        exit();
    }

    // Semak format kata laluan (Min 6 aksara, Mesti ada huruf, nombor & simbol)
    $hasLetter = preg_match('/[a-zA-Z]/', $new_password);
    $hasNumber = preg_match('/[0-9]/', $new_password);
    $hasSymbol = preg_match('/[^a-zA-Z0-9]/', $new_password);

    if (strlen($new_password) < 6 || !$hasLetter || !$hasNumber || !$hasSymbol) {
        $_SESSION['error'] = "Kata laluan baharu hendaklah sekurang-kurangnya 6 aksara dan mengandungi gabungan huruf, nombor, serta simbol!";
        header("Location: forgot-password.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT resident_id FROM resident WHERE email = ? AND phone_number = ?");
        $stmt->execute([$email, $phone_number]);
        $resident = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resident) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            
            $updateStmt = $pdo->prepare("UPDATE resident SET password = ? WHERE resident_id = ?");
            $updateStmt->execute([$hashed_password, $resident['resident_id']]);

            $_SESSION['success'] = "Kata laluan berjaya dikemas kini! Sila log masuk.";
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['error'] = "E-mel atau nombor telefon tidak berpadanan dengan rekod kami.";
            header("Location: forgot-password.php");
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Ralat pangkalan data: " . $e->getMessage();
        header("Location: forgot-password.php");
        exit();
    }

} else {
    header("Location: forgot-password.php");
    exit();
}