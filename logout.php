<?php
session_start();
require 'db.php';
if (isset($_SESSION['user_id'])) {
    $conn->query("UPDATE users SET is_online=0 WHERE id={$_SESSION['user_id']}");
}
session_destroy();
header("Location: login.php");
?>