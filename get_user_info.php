<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

// التحقق من صلاحية الطلب
if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح']);
    exit();
}

$user_id = intval($_GET['user_id']);

// الاستعلام عن بيانات المستخدم
$stmt = $conn->prepare("SELECT full_name, email, profile_image, is_online, 
                        DATE_FORMAT(last_seen, '%Y-%m-%d %H:%i:%s') as last_seen 
                        FROM users WHERE id = ?");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'فشل إعداد الاستعلام: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'user' => $user]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'المستخدم غير موجود']);
}
?>