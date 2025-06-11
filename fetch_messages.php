<?php
session_start();
require 'db.php';

// التحقق من الجلسة والمعرف الآخر
if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    exit();
}

$me = intval($_SESSION['user_id']);
$other = intval($_GET['user_id']);

if ($me === $other || $other <= 0) {
    exit(); // لا يمكن عرض محادثة مع النفس أو معرف غير صالح
}

// ✅ حذف السجل من deleted_chats لو المستخدم بدأ محادثة جديدة
$clear_deleted = $conn->prepare("DELETE FROM deleted_chats WHERE user_id = ? AND chat_with_id = ?");
$clear_deleted->bind_param("ii", $me, $other);
$clear_deleted->execute();
$clear_deleted->close();

// التحقق إذا كانت المحادثة مخفية
$check = $conn->prepare("SELECT 1 FROM hidden_chats WHERE user_id = ? AND hidden_with_id = ?");
$check->bind_param("ii", $me, $other);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    exit(); // المحادثة مخفية
}

// جلب بيانات المستخدمين
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

// ✅ جلب الرسائل (مفيش داعي نفلتر بـ deleted_chats دلوقتي لأنه اتحذف فوق)
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
    $time = htmlspecialchars($row['created_at']);
    $msg_id = $row['id'];
    $type = $row['type'];

    echo "<div data-msg-id='$msg_id' 
                data-sender-name=\"{$sender['name']}\" 
                data-sender-img=\"{$sender['img']}\" 
                data-msg-content=\"$content\"
                style='margin-bottom:10px; display:flex; align-items:flex-start;'>";

    echo "<img src='{$sender['img']}' alt='{$sender['name']}' style='width:40px; height:40px; border-radius:50%; margin-right:10px;'>";

    echo "<div style='background:#f1f1f1; padding:10px; border-radius:10px; max-width:75%; position:relative;'>";

    echo "<strong>{$sender['name']}:</strong><br>";

    // عرض محتوى الرسالة حسب النوع
    switch ($type) {
        case 'image':
            echo "<img src='$content' alt='Image' style='max-width:200px; border-radius:8px;'><br>";
            break;
        case 'video':
            echo "<video src='$content' controls style='max-width:200px; border-radius:8px;'></video><br>";
            break;
        default:
            echo nl2br($content) . "<br>";
    }

    echo "<small style='color:gray;'>$time</small>";

    // زر الحذف إن كانت الرسالة من المستخدم نفسه
    if ($sender_id == $me) {
        echo "<form method='POST' action='delete_message.php' style='display:inline-block; margin-left:10px;'>
                <input type='hidden' name='id' value='$msg_id'>
                <button type='submit' style='background:none; border:none; color:red; cursor:pointer;'>🗑️</button>
              </form>";
    }

    // زر الرد
    echo "<button onclick='replyToMessage(\"" . addslashes($content) . "\")' 
                style='background:none; border:none; cursor:pointer; margin-left:5px;'>↩️</button>";

    echo "</div></div>";
}
?>