<?php
session_start();
require 'db.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    exit("غير مسموح");
}

$current_user = $_SESSION['user_id'];
$other_user = intval($_GET['user_id']);

// تحقق من عدم وجود سجل مكرر في جدول hidden_chats
$check = $conn->prepare("SELECT * FROM hidden_chats WHERE user_id = ? AND hidden_with_id = ?");
$check->bind_param("ii", $current_user, $other_user);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    // إذا لم تكن المحادثة مخفية، قم بإضافتها
    $stmt = $conn->prepare("INSERT INTO hidden_chats (user_id, hidden_with_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $current_user, $other_user);
    $stmt->execute();
}

// إعادة التوجيه إلى الصفحة السابقة
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>