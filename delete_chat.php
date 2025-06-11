<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user = intval($_SESSION['user_id']);
$other_user = intval($_GET['user_id']);

if ($current_user === $other_user || $other_user <= 0) {
    header("Location: index.php");
    exit();
}

// حذف جميع الرسائل بين الطرفين
$stmt = $conn->prepare("
    DELETE FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) 
       OR (sender_id = ? AND receiver_id = ?)
");
$stmt->bind_param("iiii", $current_user, $other_user, $other_user, $current_user);
$stmt->execute();
$stmt->close();

header("Location: chat.php?user_id=$other_user"); // أو رجّعه للصفحة اللي تحب
exit();
?>