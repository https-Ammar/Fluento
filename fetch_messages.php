<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    exit();
}

$me = intval($_SESSION['user_id']);
$other = intval($_GET['user_id']);

if ($me === $other || $other <= 0) {
    exit();
}

$clear_deleted = $conn->prepare("DELETE FROM deleted_chats WHERE user_id = ? AND chat_with_id = ?");
$clear_deleted->bind_param("ii", $me, $other);
$clear_deleted->execute();
$clear_deleted->close();

$check = $conn->prepare("SELECT 1 FROM hidden_chats WHERE user_id = ? AND hidden_with_id = ?");
$check->bind_param("ii", $me, $other);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    exit();
}

$users = [];
$user_stmt = $conn->prepare("SELECT id, full_name, profile_image FROM users WHERE id IN (?, ?)");
$user_stmt->bind_param("ii", $me, $other);
$user_stmt->execute();
$user_res = $user_stmt->get_result();

while ($user = $user_res->fetch_assoc()) {
    $users[$user['id']] = [
        'name' => htmlspecialchars($user['full_name']),
        'img' => htmlspecialchars($user['profile_image'])
    ];
}

$msg_stmt = $conn->prepare("SELECT * FROM messages 
    WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)
    ORDER BY created_at ASC");
$msg_stmt->bind_param("iiii", $me, $other, $other, $me);
$msg_stmt->execute();
$msg_res = $msg_stmt->get_result();

while ($row = $msg_res->fetch_assoc()) {
    $sender_id = $row['sender_id'];
    $sender = $users[$sender_id];
    $content = htmlspecialchars($row['content'], ENT_QUOTES);
    $time = date('g:i A', strtotime($row['created_at']));
    $msg_id = $row['id'];
    $type = $row['type'];

    if ($sender_id == $me) {
        echo "<div class='my-message' style='display:flex; justify-content:flex-end; align-items:flex-start; margin-bottom:15px;'>";
        echo "<div style='width:45px; height:45px; border-radius:50%; background-image:url(\"{$sender['img']}\"); background-size:cover; background-position:center; margin-left:10px;'></div>";
        echo "<div style='background:#2d2416; color:#fff; padding:15px 20px; border-radius:20px 20px 0 20px; max-width:70%; font-family:sans-serif; font-size:18px; position:relative;'>";
        if ($type == 'image') {
            echo "<img src='$content' style='max-width:200px; border-radius:10px;'><br>";
        } elseif ($type == 'video') {
            echo "<video src='$content' controls style='max-width:200px; border-radius:10px;'></video><br>";
        } else {
            echo nl2br($content);
        }
        echo "<div style='display:flex; justify-content:space-between; align-items:center; margin-top:5px;'>";
        echo "<small style='color:#bbb;'>$time</small>";
        echo "<div>";
        echo "<form method='POST' action='delete_message.php' style='display:inline-block; margin-left:5px;'>
                <input type='hidden' name='id' value='$msg_id'>
                <button type='submit' style='background:none; border:none; color:red; cursor:pointer;'>🗑️</button>
              </form>";
        echo "</div></div>";
        echo "</div></div>";
    } else {
        echo "<div class='other-message' style='display:flex; align-items:flex-start; margin-bottom:15px;'>";
        echo "<div style='width:45px; height:45px; border-radius:50%; background-image:url(\"{$sender['img']}\"); background-size:cover; background-position:center; margin-right:10px;'></div>";
        echo "<div style='background:#1e1e1e; color:#fff; padding:15px 20px; border-radius:20px 20px 20px 0; max-width:70%; font-family:sans-serif; font-size:18px; position:relative;'>";
        if ($type == 'image') {
            echo "<img src='$content' style='max-width:200px; border-radius:10px;'><br>";
        } elseif ($type == 'video') {
            echo "<video src='$content' controls style='max-width:200px; border-radius:10px;'></video><br>";
        } else {
            echo nl2br($content);
        }
        echo "<div style='display:flex; justify-content:space-between; align-items:center; margin-top:5px;'>";
        echo "<small style='color:#bbb;'>$time</small>";
        echo "</div></div></div>";
    }
}
?>