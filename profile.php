<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;

    if (!empty($_FILES['profile_image']['name'])) {
        $image_name = time() . '_' . basename($_FILES['profile_image']['name']);
        $target = 'uploads/' . $image_name;
        move_uploaded_file($_FILES['profile_image']['tmp_name'], $target);

        $stmt = $conn->prepare("UPDATE users SET full_name = ?, profile_image = ?, notifications_enabled = ? WHERE id = ?");
        $stmt->bind_param("ssii", $name, $target, $notifications_enabled, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, notifications_enabled = ? WHERE id = ?");
        $stmt->bind_param("sii", $name, $notifications_enabled, $user_id);
    }

    $stmt->execute();
    $success = "تم تحديث البيانات بنجاح";
}

// جلب بيانات المستخدم
$stmt = $conn->prepare("SELECT full_name, email, profile_image, notifications_enabled, user_code FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <title>صفحتي الشخصية</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            direction: rtl;
            background-color: #f8f8f8;
        }

        .profile-container {
            max-width: 500px;
            margin: auto;
            padding: 25px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-top: 40px;
        }

        .profile-container img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
        }

        label {
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        input[type="file"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
        }

        button {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background-color: #218838;
        }

        .danger {
            color: red;
            display: inline-block;
            margin-top: 15px;
        }

        .info {
            font-size: 14px;
            margin-top: -10px;
            margin-bottom: 20px;
            color: #555;
        }
    </style>
</head>

<body>

    <div class="profile-container">
        <h2>صفحتي الشخصية</h2>

        <?php if (isset($success)): ?>
            <p style="color: green;"><?= $success ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div>
                <label>الصورة الحالية:</label><br>
                <img src="<?= htmlspecialchars($user['profile_image']) ?>" alt="الصورة">
            </div>

            <div>
                <label>تغيير الصورة:</label><br>
                <input type="file" name="profile_image" accept="image/*">
            </div>

            <div>
                <label>الاسم الكامل:</label><br>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
            </div>

            <div>
                <label>البريد الإلكتروني:</label><br>
                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
            </div>

            <div class="info">
                <label>الرقم التعريفي (ID):</label><br>
                <span
                    style="direction: ltr; display:inline-block; background:#eee; padding:5px 10px; border-radius:6px;">
                    <?= htmlspecialchars($user['user_code']) ?>
                </span>
            </div>

            <div>
                <label>الإشعارات:</label><br>
                <input type="checkbox" name="notifications_enabled" value="1" <?= $user['notifications_enabled'] ? 'checked' : '' ?>>
                تفعيل الإشعارات على البريد الإلكتروني
            </div>

            <br>
            <button type="submit">💾 حفظ التعديلات</button>
        </form>

        <br>
        <a href="delete_account.php" onclick="return confirm('هل أنت متأكد أنك تريد حذف الحساب نهائيًا؟');"
            class="danger">
            🗑️ حذف الحساب نهائيًا
        </a>
    </div>

</body>

</html>