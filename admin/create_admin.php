
<?php
require_once '../config/db.php';

$username = 'ResasAdmin';
$password = 'resasadmin321';
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

try {
    // Padam akaun lama jika ada
    $pdo->prepare("DELETE FROM admin WHERE username = ?")->execute([$username]);
    
    // Buat Akaun Baru Admin
    $stmt = $pdo->prepare("INSERT INTO admin (username, password, role) VALUES (?, ?, 'Admin')");
    $stmt->execute([$username, $hashed_password]);

    echo "<h3 style='color: green;'>Akaun Admin Berjaya Dicipta!</h3>";
    echo "Username: <b>ResasAdmin</b><br>";
    echo "Password: <b>resasadmin321</b><br><br>";
    echo "<a href='login.php'>Klik Sini Untuk Log Masuk</a>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>