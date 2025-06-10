<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// حذف المستخدم
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();

// إنهاء الجلسة
session_destroy();

// إعادة التوجيه
header("Location: goodbye.php");
exit();
?>