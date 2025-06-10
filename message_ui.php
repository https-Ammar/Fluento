<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['user_id'];
$other_id = intval($_GET['user_id']);

// جلب بيانات المستخدم الآخر
$stmt = $conn->prepare("SELECT full_name, profile_image, is_online FROM users WHERE id = ?");
$stmt->bind_param("i", $other_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!-- معلومات المستخدم الآخر - عند الضغط يظهر البوب أب -->
<div onclick="showUserInfo(<?= $other_id ?>)"
    style="cursor: pointer; display: flex; align-items: center; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
    <img src="<?= htmlspecialchars($user['profile_image']) ?>" alt="صورة" width="50" height="50"
        style="border-radius: 50%; margin-right: 10px;">
    <div>
        <strong><?= htmlspecialchars($user['full_name']) ?></strong><br>
        <?= $user['is_online'] ? '🟢 متصل' : '⚪ غير متصل' ?>
    </div>
</div>

<!-- زر حذف المحادثة -->
<div style="margin-bottom: 10px;">
    <a href="delete_chat.php?user_id=<?= $other_id ?>" onclick="return confirm('هل أنت متأكد من حذف المحادثة؟')"
        style="color: red; text-decoration: none;">🗑️ حذف المحادثة</a>
</div>

<!-- عرض الرسائل -->
<div id="messagesBox" style="height:300px; overflow-y:auto; border:1px solid #ccc; margin-bottom: 15px; padding:10px;">
    <!-- الرسائل تظهر هنا -->
</div>

<!-- نموذج إرسال الرسائل -->
<form id="messageForm" onsubmit="sendMessage(event)" enctype="multipart/form-data">
    <input type="text" id="messageInput" placeholder="اكتب رسالة..." style="width: 50%;">
    <input type="file" id="fileInput" accept="image/*,video/*">
    <button type="submit">📤 إرسال</button>
</form>

<!-- مودال عرض بيانات المستخدم -->
<div id="userInfoModal" style="display:none; position:fixed; top:20%; left:50%; transform:translateX(-50%);
background:white; padding:20px; border-radius:10px; box-shadow:0 0 15px rgba(0,0,0,0.3); z-index:1000;">
    <button onclick="closeModal()" style="float:right; background:none; border:none; font-size:20px;">❌</button>
    <h3>معلومات المستخدم</h3>
    <img id="userModalImage" src="" width="80" height="80" style="border-radius:50%;"><br><br>
    <p><strong>الاسم:</strong> <span id="userModalName"></span></p>
    <p><strong>البريد:</strong> <span id="userModalEmail"></span></p>
    <p><strong>الحالة:</strong> <span id="userModalStatus"></span></p>
    <p><strong>آخر ظهور:</strong> <span id="userModalLastSeen"></span></p>
</div>

<!-- خلفية داكنة للمودال -->
<div id="modalOverlay" onclick="closeModal()" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
background:rgba(0,0,0,0.5); z-index:999;"></div>

<!-- جافاسكريبت -->
<script>
    let otherId = <?= $other_id ?>;

    function fetchMessages() {
        fetch('fetch_messages.php?user_id=' + otherId)
            .then(res => res.text())
            .then(html => {
                const box = document.getElementById("messagesBox");
                box.innerHTML = html;
                box.scrollTop = box.scrollHeight;
            });
    }

    fetchMessages();
    setInterval(fetchMessages, 5000);

    function sendMessage(e) {
        e.preventDefault();
        const input = document.getElementById("messageInput");
        const file = document.getElementById("fileInput").files[0];

        if (!input.value.trim() && !file) {
            alert("يرجى كتابة رسالة أو اختيار ملف.");
            return;
        }

        const formData = new FormData();
        formData.append("receiver_id", otherId);
        formData.append("content", input.value);

        if (file) {
            formData.append("file", file);
            formData.append("type", file.type.startsWith("image/") ? "image" : "video");
        } else {
            formData.append("type", "text");
        }

        fetch('ajax_send_message.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    input.value = "";
                    document.getElementById("fileInput").value = "";
                    fetchMessages();
                } else {
                    alert(data.message || "حدث خطأ أثناء إرسال الرسالة.");
                }
            })
            .catch(err => {
                alert("فشل الاتصال بالخادم.");
                console.error(err);
            });
    }

    function showUserInfo(userId) {
        fetch('get_user_info.php?user_id=' + userId)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('userModalImage').src = data.user.profile_image;
                    document.getElementById('userModalName').innerText = data.user.full_name;
                    document.getElementById('userModalEmail').innerText = data.user.email;
                    document.getElementById('userModalStatus').innerText = data.user.is_online ? '🟢 متصل' : '⚪ غير متصل';
                    document.getElementById('userModalLastSeen').innerText = data.user.last_seen;
                    document.getElementById('userInfoModal').style.display = 'block';
                    document.getElementById('modalOverlay').style.display = 'block';
                } else {
                    alert(data.message);
                }
            }).catch(error => {
                alert("فشل جلب بيانات المستخدم.");
                console.error(error);
            });
    }

    function closeModal() {
        document.getElementById('userInfoModal').style.display = 'none';
        document.getElementById('modalOverlay').style.display = 'none';
    }
</script>