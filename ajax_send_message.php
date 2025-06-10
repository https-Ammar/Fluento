<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'مطلوب تسجيل الدخول']);
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = intval($_POST['receiver_id']);
$type = $_POST['type'] ?? 'text';
$content = trim($_POST['content'] ?? '');

if ($receiver_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'مستلم غير صالح']);
    exit();
}

// التعامل مع الملفات
if (in_array($type, ['image', 'video']) && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm'];

    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['status' => 'error', 'message' => 'نوع الملف غير مدعوم']);
        exit();
    }

    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . "." . $ext;
    $target = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        $content = $target;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'فشل في رفع الملف']);
        exit();
    }
}

// تحقق من الرسالة النصية
if ($type === 'text' && $content === '') {
    echo json_encode(['status' => 'error', 'message' => 'الرسالة فارغة']);
    exit();
}

// حفظ الرسالة
$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content, type, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("iiss", $sender_id, $receiver_id, $content, $type);

if ($stmt->execute()) {
    // جلب معلومات المرسل لإظهارها فورًا
    $user_stmt = $conn->prepare("SELECT full_name, profile_image FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $sender_id);
    $user_stmt->execute();
    $user_res = $user_stmt->get_result()->fetch_assoc();

    echo json_encode([
        'status' => 'success',
        'message' => [
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'content' => htmlspecialchars($content),
            'type' => $type,
            'time' => date("Y-m-d H:i:s"),
            'sender_name' => htmlspecialchars($user_res['full_name']),
            'sender_image' => htmlspecialchars($user_res['profile_image'])
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'فشل الحفظ في قاعدة البيانات']);
}
?>