
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resident_id    = $_SESSION['resident_id'];
    $payment_method = 'DuitNow QR';
    $proofName      = null;

    // Semak fail resit
    if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/proofs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['proof_of_payment']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];

        if (in_array($ext, $allowedExts)) {
            $proofName = 'proof_' . $resident_id . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $uploadDir . $proofName);
        } else {
            $_SESSION['flash_error'] = "Format resit tidak sah! Format yang dibenarkan: PDF, JPG, JPEG, PNG.";
            header("Location: cart.php");
            exit();
        }
    } else {
        $_SESSION['flash_error'] = "Sila muat naik resit pembayaran anda!";
        header("Location: cart.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Ambil permohonan dalam troli / menunggu bayaran
        $stmtGet = $pdo->prepare("
            SELECT application_id FROM application 
            WHERE resident_id = ? AND status IN ('Dalam Troli', 'Menunggu Bayaran', 'Menunggu Pembayaran')
        ");
        $stmtGet->execute([$resident_id]);
        $apps = $stmtGet->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($apps)) {
            foreach ($apps as $app) {
                $app_id = $app['application_id'];

                // 2. Rekodkan ke jadual payment
                $stmtPay = $pdo->prepare("
                    INSERT INTO payment (application_id, amount, payment_date, payment_method, transaction_status, proof_of_payment)
                    VALUES (?, 10.00, NOW(), ?, 'Selesai', ?)
                ");
                $stmtPay->execute([$app_id, $payment_method, $proofName]);

                // 3. Kemaskini status permohonan kepada 'Dalam Proses'
                $stmtApp = $pdo->prepare("
                    UPDATE application 
                    SET status = 'Dalam Proses'
                    WHERE application_id = ? AND resident_id = ?
                ");
                $stmtApp->execute([$app_id, $resident_id]);
            }
        }

        $pdo->commit();

        header("Location: profile.php?payment=success");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Ralat Pangkalan Data: " . htmlspecialchars($e->getMessage()));
    }
} else {
    header("Location: profile.php");
    exit();
}