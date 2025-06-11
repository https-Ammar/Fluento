<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    $userId = intval($_SESSION['user_id']);

    $stmt = $conn->prepare("UPDATE users SET is_online = 0, last_seen = NOW() WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}

session_destroy();
header("Location: login.php");
exit();
