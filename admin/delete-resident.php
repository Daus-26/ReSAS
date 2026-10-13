
<?php
session_start();

if (file_exists('../config/db.php')) {
    require_once '../config/db.php';
} else {
    require_once 'config/db.php';
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$resident_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($resident_id) {
    try {
        $pdo->beginTransaction();

        // 1. Padam permohonan berkaitan penduduk
        $stmtApp = $pdo->prepare("DELETE FROM application WHERE resident_id = ?");
        $stmtApp->execute([$resident_id]);

        // 2. Padam kenderaan berkaitan penduduk
        $stmtVeh = $pdo->prepare("DELETE FROM vehicle WHERE resident_id = ?");
        $stmtVeh->execute([$resident_id]);

        // 3. Padam rekod penduduk
        $stmtRes = $pdo->prepare("DELETE FROM resident WHERE resident_id = ?");
        $stmtRes->execute([$resident_id]);

        $pdo->commit();
        $_SESSION['flash_success'] = "Rekod penduduk dan data berkaitan berjaya dipadam.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = "Gagal memadam rekod: " . $e->getMessage();
    }
} else {
    $_SESSION['flash_error'] = "ID penduduk tidak sah.";
}

header("Location: registrations.php");
exit();
?>