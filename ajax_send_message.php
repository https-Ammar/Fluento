<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Login required']);
    exit();
}

$sender_id = intval($_SESSION['user_id']);
$receiver_id = intval($_POST['receiver_id'] ?? 0);
$type = $_POST['type'] ?? 'text';
$content = trim($_POST['content'] ?? '');

if ($receiver_id <= 0 || $receiver_id === $sender_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid receiver']);
    exit();
}

if (in_array($type, ['image', 'video']) && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $allowed_types = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm'
    ];

    if (!array_key_exists($file['type'], $allowed_types)) {
        echo json_encode(['status' => 'error', 'message' => 'Unsupported file type']);
        exit();
    }

    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $ext = $allowed_types[$file['type']];
    $filename = uniqid('msg_', true) . '.' . $ext;
    $target = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        echo json_encode(['status' => 'error', 'message' => 'File upload failed']);
        exit();
    }

    $content = $target;
}

if ($type === 'text') {
    if ($content === '') {
        echo json_encode(['status' => 'error', 'message' => 'Empty message']);
        exit();
    }
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
}

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content, type, created_at) VALUES (?, ?, ?, ?, NOW())");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit();
}
$stmt->bind_param("iiss", $sender_id, $receiver_id, $content, $type);

if ($stmt->execute()) {
    $user_stmt = $conn->prepare("SELECT full_name, profile_image FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $sender_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();

    echo json_encode([
        'status' => 'success',
        'message' => [
            'sender_id' => $sender_id,
            'receiver_id' => $receiver_id,
            'content' => $content,
            'type' => $type,
            'time' => date("Y-m-d H:i:s"),
            'sender_name' => htmlspecialchars($user['full_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'),
            'sender_image' => htmlspecialchars($user['profile_image'] ?? '', ENT_QUOTES, 'UTF-8')
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save message']);
}
?>