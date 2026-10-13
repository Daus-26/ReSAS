
<?php
session_start();

if (file_exists('../config/db.php')) {
    require_once '../config/db.php';
} else {
    require_once 'config/db.php';
}

// Pengesahan sesi Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_id = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
    $status         = trim($_POST['status'] ?? '');
    $remarks        = trim($_POST['remarks'] ?? '');

    $allowed_statuses = ['Dalam Proses', 'Menunggu Bayaran', 'Diluluskan', 'Ditolak'];

    if ($application_id && in_array($status, $allowed_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE application SET status = ?, remarks = ? WHERE application_id = ?");
            $stmt->execute([$status, $remarks, $application_id]);

            $_SESSION['flash_success'] = "Status permohonan #{$application_id} berjaya dikemas kini kepada '{$status}'.";
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Ralat pangkalan data: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_error'] = "Maklumat kemaskini tidak sah.";
    }
}

// Kembalikan ke halaman permohonan
header("Location: applications.php");
exit();
?>