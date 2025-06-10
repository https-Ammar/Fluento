<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'مطلوب تسجيل الدخول']);
    exit();
}

$user_id = $_SESSION['user_id'];
$name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$image_path = null;

if (isset($_FILES['profile_image']) && $_FILES['profile_image']['name'] !== '') {
    $file = $_FILES['profile_image'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed)) {
        echo json_encode(['status' => 'error', 'message' => 'نوع الصورة غير مدعوم']);
        exit();
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . "." . $ext;
    $target = 'uploads/' . $filename;
    move_uploaded_file($file['tmp_name'], $target);
    $image_path = $target;
}

if ($image_path) {
    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, profile_image=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $email, $image_path, $user_id);
} else {
    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $email, $user_id);
}

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'فشل التحديث']);
}
?>