<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'مطلوب تسجيل الدخول']);
    exit();
}

$user_id = intval($_GET['user_id']);

$stmt = $conn->prepare("SELECT full_name, email, profile_image, is_online FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $user = $res->fetch_assoc();
    echo json_encode([
        'status' => 'success',
        'user' => $user
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'المستخدم غير موجود']);
}
?>