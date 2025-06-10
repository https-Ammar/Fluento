<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id']))
    exit("غير مسموح");

$id = intval($_POST['id']);
$stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();

header("Location: " . $_SERVER['HTTP_REFERER']);
?>