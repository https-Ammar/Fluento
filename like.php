<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id'])) {
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id']);

// تحقق إذا كان عامل لايك
$stmt = $conn->prepare("SELECT 1 FROM likes WHERE post_id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$exists = $stmt->get_result()->num_rows > 0;

if ($exists) {
    $del = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
    $del->bind_param("ii", $post_id, $user_id);
    $del->execute();
    echo 'unliked';
} else {
    $add = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
    $add->bind_param("ii", $post_id, $user_id);
    $add->execute();
    echo 'liked';
}
?>