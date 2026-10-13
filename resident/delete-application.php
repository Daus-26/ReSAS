
<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['resident_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $application_id = $_GET['id'];
    $resident_id    = $_SESSION['resident_id'];
    $from           = $_GET['from'] ?? 'profile';

    try {
        $stmt = $pdo->prepare("SELECT * FROM application WHERE application_id = ? AND resident_id = ?");
        $stmt->execute([$application_id, $resident_id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($app) {
            $vehicle_id = $app['vehicle_id'];

            $upload_dir = '../uploads/';
            $files_to_delete = [$app['grant_file'], $app['ic_file'], $app['residence_file']];

            foreach ($files_to_delete as $file) {
                if (!empty($file) && file_exists($upload_dir . $file)) {
                    unlink($upload_dir . $file);
                }
            }

            $stmtDelApp = $pdo->prepare("DELETE FROM application WHERE application_id = ?");
            $stmtDelApp->execute([$application_id]);

            if ($vehicle_id) {
                $stmtDelVehicle = $pdo->prepare("DELETE FROM vehicle WHERE vehicle_id = ?");
                $stmtDelVehicle->execute([$vehicle_id]);
            }

            if ($from === 'cart') {
                header("Location: cart.php?delete=success");
            } else {
                header("Location: profile.php?delete=success");
            }
            exit();
        }

    } catch (PDOException $e) {
        header("Location: profile.php?delete=error");
        exit();
    }
}

header("Location: profile.php");
exit();