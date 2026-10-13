
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
    
    // Semakan jika muat naik melebihi post_max_size PHP (PHP akan kosongkan $_POST & $_FILES)
    if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        header("Location: apply.php?error=post_limit");
        exit();
    }

    $resident_id   = $_SESSION['resident_id'];
    $plate_number  = strtoupper(trim($_POST['plate_number'] ?? ''));
    $vehicle_type  = trim($_POST['vehicle_type'] ?? '');
    $brand         = trim($_POST['brand'] ?? '');
    $model         = trim($_POST['model'] ?? '');
    $color         = trim($_POST['color'] ?? '');
    $year          = intval($_POST['year'] ?? 0);

    if (empty($plate_number) || empty($vehicle_type) || empty($brand) || empty($model) || empty($color) || empty($year)) {
        header("Location: apply.php?error=empty");
        exit();
    }

    if (!isset($_FILES['grant_file']) || !isset($_FILES['ic_file']) || !isset($_FILES['residence_file'])) {
        header("Location: apply.php?error=empty");
        exit();
    }

    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];

    /**
     * Fungsi Muat Naik Fail yang Lebih Selamat & Menyokong Semakan Ralat
     */
    function uploadFile($file, $targetDir, $allowedExts) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            // Jika ralat saiz fail melebihi had php.ini
            if (isset($file['error']) && ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE)) {
                return 'ERR_SIZE';
            }
            return false;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            return false;
        }

        $newName = uniqid('doc_', true) . '.' . $ext;
        $targetFile = $targetDir . $newName;

        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return $newName;
        }

        return false;
    }

    $grantResult     = uploadFile($_FILES['grant_file'], $uploadDir, $allowedExtensions);
    $icResult        = uploadFile($_FILES['ic_file'], $uploadDir, $allowedExtensions);
    $residenceResult = uploadFile($_FILES['residence_file'], $uploadDir, $allowedExtensions);

    // Semak jika ada fail melebihi saiz
    if ($grantResult === 'ERR_SIZE' || $icResult === 'ERR_SIZE' || $residenceResult === 'ERR_SIZE') {
        header("Location: apply.php?error=file_size");
        exit();
    }

    // Semak jika muat naik gagal atau format salah
    if (!$grantResult || !$icResult || !$residenceResult) {
        // Padam fail yang mungkin sempat dimuat naik
        if (is_string($grantResult) && file_exists($uploadDir . $grantResult)) unlink($uploadDir . $grantResult);
        if (is_string($icResult) && file_exists($uploadDir . $icResult)) unlink($uploadDir . $icResult);
        if (is_string($residenceResult) && file_exists($uploadDir . $residenceResult)) unlink($uploadDir . $residenceResult);

        header("Location: apply.php?error=file_type");
        exit();
    }

    $grantFileName     = $grantResult;
    $icFileName        = $icResult;
    $residenceFileName = $residenceResult;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT vehicle_id FROM vehicle WHERE plate_number = ?");
        $stmt->execute([$plate_number]);
        $existingVehicle = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingVehicle) {
            $vehicle_id = $existingVehicle['vehicle_id'];
            
            $updateVehicle = $pdo->prepare("
                UPDATE vehicle 
                SET resident_id = ?, vehicle_type = ?, brand = ?, model = ?, color = ?, year = ? 
                WHERE vehicle_id = ?
            ");
            $updateVehicle->execute([$resident_id, $vehicle_type, $brand, $model, $color, $year, $vehicle_id]);
        } else {
            $insertVehicle = $pdo->prepare("
                INSERT INTO vehicle (resident_id, plate_number, vehicle_type, brand, model, color, year) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insertVehicle->execute([$resident_id, $plate_number, $vehicle_type, $brand, $model, $color, $year]);
            $vehicle_id = $pdo->lastInsertId();
        }

        // Simpan permohonan dengan status 'Dalam Troli'
        $insertApp = $pdo->prepare("
            INSERT INTO application (resident_id, vehicle_id, grant_file, ic_file, residence_file, status, submission_date) 
            VALUES (?, ?, ?, ?, ?, 'Dalam Troli', NOW())
        ");
        $insertApp->execute([$resident_id, $vehicle_id, $grantFileName, $icFileName, $residenceFileName]);

        $pdo->commit();

        // Bawa pengguna terus ke Troli Permohonan
        header("Location: cart.php?added=success");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();

        // Padam fail yang dimuat naik jika pangkalan data gagal
        if (file_exists($uploadDir . $grantFileName)) unlink($uploadDir . $grantFileName);
        if (file_exists($uploadDir . $icFileName)) unlink($uploadDir . $icFileName);
        if (file_exists($uploadDir . $residenceFileName)) unlink($uploadDir . $residenceFileName);

        die("Ralat Pangkalan Data: " . htmlspecialchars($e->getMessage()));
    }

} else {
    header("Location: apply.php");
    exit();
}