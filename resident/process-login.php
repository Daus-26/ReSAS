
<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Sila isi semua ruangan log masuk.";
        header("Location: login.php");
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM resident WHERE email = ?");
        $stmt->execute([$email]);
        $resident = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resident && password_verify($password, $resident['password'])) {
            
            $_SESSION['resident_id']    = $resident['resident_id'];
            $_SESSION['resident_name']  = $resident['name'];
            $_SESSION['resident_email'] = $resident['email'];

            header("Location: dashboard.php");
            exit();

        } else {
            $_SESSION['error'] = "E-mel atau kata laluan tidak sah.";
            header("Location: login.php");
            exit();
        }

    } catch (PDOException $e) {
        $_SESSION['error'] = "Pangkalan Data Error: " . $e->getMessage();
        header("Location: login.php");
        exit();
    }

} else {
    header("Location: login.php");
    exit();
}