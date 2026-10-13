
<?php
session_start();

if (isset($_SESSION['resident_id'])) {
    header("Location: dashboard.php");
    exit();
}

header("Location: login.php");
exit();
?>