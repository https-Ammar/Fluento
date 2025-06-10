<?php
session_start();
require 'db.php';

// التحقق من الجلسة والمعرف الآخر
if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    exit();
}

$me = $_SESSION['user_id'];
$other = intval($_GET['user_id']);

// التحقق إذا كانت المحادثة مخفية
$check = $conn->prepare("SELECT 1 FROM hidden_chats WHERE user_id = ? AND hidden_with_id = ?");
$check->bind_param("ii", $me, $other);
$check->execute();
$check_res = $check->get_result();

if ($check_res->num_rows > 0) {
    exit(); // المستخدم أخفى هذه المحادثة، لا تعرض أي رسائل
}

// جلب بيانات المستخدمين
$users = [];
$user_stmt = $conn->prepare("SELECT id, full_name, profile_image FROM users WHERE id IN (?, ?)");
$user_stmt->bind_param("ii", $me, $other);
$user_stmt->execute();
$user_res = $user_stmt->get_result();
while ($user = $user_res->fetch_assoc()) {
    $users[$user['id']] = $user;
}

// جلب الرسائل
$stmt = $conn->prepare("SELECT * FROM messages WHERE 
    (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?) 
    ORDER BY created_at ASC");
$stmt->bind_param("iiii", $me, $other, $other, $me);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $sender = $users[$row['sender_id']];
    $img = htmlspecialchars($sender['profile_image']);
    $name = htmlspecialchars($sender['full_name']);
    $content = htmlspecialchars($row['content']);
    $time = $row['created_at'];
    $msg_id = $row['id'];

    echo "<div 
            data-msg-id='$msg_id' 
            data-sender-name=\"$name\" 
            data-sender-img=\"$img\" 
            data-msg-content=\"$content\"
            style='margin-bottom: 10px; display: flex; align-items: flex-start;'>";

    echo "<img src='$img' alt='$name' style='width:40px; height:40px; border-radius:50%; margin-right:10px;'>";

    echo "<div style='background:#f1f1f1; padding:10px; border-radius:10px; max-width:75%; position:relative;'>";

    echo "<strong>$name:</strong><br>";

    // عرض حسب نوع الرسالة
    if ($row['type'] === 'image') {
        echo "<img src='$content' style='max-width:200px; border-radius:8px;'><br>";
    } elseif ($row['type'] === 'video') {
        echo "<video src='$content' controls style='max-width:200px; border-radius:8px;'></video><br>";
    } else {
        echo $content . "<br>";
    }

    echo "<small style='color:gray;'>$time</small>";

    // لو الرسالة من المستخدم الحالي، أضف زر الحذف
    if ($row['sender_id'] == $me) {
        echo "<form method='POST' action='delete_message.php' style='display:inline-block; margin-right:10px;'>
                <input type='hidden' name='id' value='$msg_id'>
                <button type='submit' style='background:none; border:none; color:red; cursor:pointer;'>🗑️</button>
              </form>";
    }

    // زر رد
    echo "<button onclick='replyToMessage(\"" . addslashes($content) . "\")' style='background:none; border:none; cursor:pointer;'>↩️</button>";

    echo "</div></div>";
}
?>