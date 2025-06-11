<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['user_id'];

// جلب بيانات المستخدم الحالي
$stmt = $conn->prepare("SELECT user_code, full_name, profile_image, is_online FROM users WHERE id = ?");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$current_user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current_user_data) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// جلب جميع المستخدمين باستثناء الحالي
$stmt = $conn->prepare("SELECT id, full_name, profile_image, is_online FROM users WHERE id != ?");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$users_result = $stmt->get_result();
$stmt->close();

// البحث عن مستخدم عبر user_code
$search_result = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_code'])) {
    $search_code = trim($_POST['user_code']);
    if (strlen($search_code) !== 8 || !ctype_digit($search_code)) {
        $error = "الرجاء إدخال رقم مكوّن من 8 أرقام.";
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, profile_image, is_online FROM users WHERE user_code = ?");
        $stmt->bind_param("s", $search_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $search_result = $result->num_rows > 0 ? $result->fetch_assoc() : null;
        $stmt->close();
    }
}

// التحقق من وجود مستخدم للمحادثة
$other_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$chat_user = null;

if ($other_id > 0 && $other_id !== $current_user) {
    $stmt = $conn->prepare("SELECT full_name, profile_image, is_online FROM users WHERE id = ?");
    $stmt->bind_param("i", $other_id);
    $stmt->execute();
    $chat_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <title>لوحة المستخدم</title>
    <link rel="stylesheet" href="./assets/style/main.css">
    <style>
        body {
            font-family: Arial;
            direction: rtl;
            background-color: #f9f9f9;
            padding: 20px;
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            background: white;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .user-card img {
            border-radius: 50%;
            width: 60px;
            height: 60px;
            object-fit: cover;
        }

        .section-box {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.05);
        }

        input[type="text"] {
            padding: 8px;
            width: 200px;
        }

        button {
            padding: 8px 15px;
        }
    </style>
</head>

<body>

    <!-- ملفي الشخصي -->
    <section class="section-box">
        <h2>👤 ملفي الشخصي</h2>
        <div class="user-card">
            <img src="<?= htmlspecialchars($current_user_data['profile_image']) ?>" alt="الصورة">
            <div>
                <strong><?= htmlspecialchars($current_user_data['full_name']) ?></strong><br>
                كود المستخدم: <strong><?= htmlspecialchars($current_user_data['user_code']) ?></strong><br>
                الحالة: <?= $current_user_data['is_online'] ? '🟢 متصل' : '⚪ غير متصل' ?>
            </div>
        </div>
    </section>

    <!-- البحث -->
    <section class="section-box">
        <h2>🔍 بحث عن مستخدم بالكود</h2>
        <form method="POST">
            <input type="text" name="user_code" placeholder="أدخل الكود (8 أرقام)" maxlength="8" required />
            <button type="submit">بحث</button>
        </form>

        <?php if ($error): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($search_result): ?>
            <div class="user-card">
                <img src="<?= htmlspecialchars($search_result['profile_image']) ?>" alt="الصورة">
                <div>
                    <strong><?= htmlspecialchars($search_result['full_name']) ?></strong><br>
                    <?= $search_result['is_online'] ? '🟢 متصل' : '⚪ غير متصل' ?><br>
                    <a href="?user_id=<?= $search_result['id'] ?>">💬 بدء محادثة</a>
                </div>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <p style="color:gray;">لا يوجد مستخدم بهذا الكود.</p>
        <?php endif; ?>
    </section>

    <!-- جميع المستخدمين -->
    <section class="section-box">
        <h2>📋 جميع المستخدمين</h2>
        <?php while ($row = $users_result->fetch_assoc()): ?>
            <div class="user-card">
                <img src="<?= htmlspecialchars($row['profile_image']) ?>" alt="الصورة">
                <div>
                    <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                    <?= $row['is_online'] ? '🟢 متصل' : '⚪ غير متصل' ?><br>
                    <a href="?user_id=<?= $row['id'] ?>">💬 محادثة</a>
                </div>
            </div>
        <?php endwhile; ?>
    </section>

    <!-- واجهة المحادثة -->
    <?php if ($chat_user): ?>
        <section class="section-box">
            <h2>💬 محادثة مع <?= htmlspecialchars($chat_user['full_name']) ?></h2>

            <div onclick="showUserInfo(<?= $other_id ?>)"
                style="cursor:pointer; display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                <img src="<?= htmlspecialchars($chat_user['profile_image']) ?>" width="50" height="50"
                    style="border-radius:50%;">
                <div>
                    <strong><?= htmlspecialchars($chat_user['full_name']) ?></strong><br>
                    <?= $chat_user['is_online'] ? '🟢 متصل' : '⚪ غير متصل' ?>
                </div>
            </div>


            <a href="delete_chat.php?user_id=<?= $other_id ?>" onclick="return confirm('هل أنت متأكد من حذف المحادثة؟')"
                style="color: red;">🗑️ حذف المحادثة</a>



            <div id="messagesBox"
                style="height:300px; overflow-y:auto; border:1px solid #ccc; margin-bottom:15px; padding:10px; background:#f9f9f9;">
            </div>

            <form id="messageForm" onsubmit="sendMessage(event)" enctype="multipart/form-data">
                <input type="text" id="messageInput" placeholder="اكتب رسالة..." style="width: 50%;" />
                <input type="file" id="fileInput" accept="image/*,video/*" />
                <button type="submit">📤 إرسال</button>
            </form>
        </section>
    <?php endif; ?>

    <!-- مودال معلومات المستخدم -->
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
    <div id="modalOverlay" onclick="closeModal()" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
 background:rgba(0,0,0,0.5); z-index:999;"></div>

    <script>
        <?php if ($chat_user): ?>
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
                const fileInput = document.getElementById("fileInput");
                const file = fileInput.files[0];

                if (!input.value.trim() && !file) {
                    alert("يرجى كتابة رسالة أو اختيار ملف.");
                    return;
                }

                const formData = new FormData();
                formData.append("receiver_id", otherId);
                formData.append("content", input.value.trim());
                if (file) {
                    formData.append("file", file);
                    formData.append("type", file.type.startsWith("image/") ? "image" : "video");
                } else {
                    formData.append("type", "text");
                }

                fetch('ajax_send_message.php', { method: 'POST', body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            input.value = "";
                            fileInput.value = "";
                            fetchMessages();
                        } else {
                            alert(data.message || "حدث خطأ أثناء إرسال الرسالة.");
                        }
                    });
            }
        <?php endif; ?>

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
                });
        }

        function closeModal() {
            document.getElementById('userInfoModal').style.display = 'none';
            document.getElementById('modalOverlay').style.display = 'none';
        }
    </script>

</body>

</html>