<?php
// get_stories.php

require 'db.php';
header('Content-Type: application/json');
session_start();

// التحقق من أن المستخدم مسجّل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// التحقق من وجود معرف المستخدم المطلوب
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = intval($_GET['user_id']);

// جلب الستوري الخاصة بالمستخدم مع معلومات المستخدم نفسه
$stmt = $conn->prepare("
    SELECT s.media_path, s.media_type, s.created_at,
           u.full_name, u.profile_image
    FROM stories s
    JOIN users u ON s.user_id = u.id
    WHERE s.user_id = ? AND s.expires_at > NOW()
    ORDER BY s.created_at ASC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$stories = [];
while ($row = $result->fetch_assoc()) {
    $stories[] = [
        'media_path' => $row['media_path'],
        'media_type' => $row['media_type'],
        'created_at' => date('Y-m-d H:i:s', strtotime($row['created_at'])),
        'full_name' => $row['full_name'],
        'profile_image' => $row['profile_image']
    ];
}

echo json_encode($stories);
exit;
