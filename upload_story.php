<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['story_media'])) {
    $user_id = $_SESSION['user_id'];
    $caption = trim($_POST['caption']);
    $media = $_FILES['story_media'];

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
    $media_type = null;

    if (!in_array($media['type'], $allowed_types)) {
        $message = "الملف يجب أن يكون صورة أو فيديو (MP4 فقط).";
    } elseif ($media['error'] !== 0) {
        $message = "حدث خطأ أثناء رفع الملف.";
    } else {
        // تحديد نوع الوسائط
        if (str_starts_with($media['type'], 'image')) {
            $media_type = 'image';
        } elseif ($media['type'] === 'video/mp4') {
            $media_type = 'video';
        }

        $ext = pathinfo($media['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('story_', true) . '.' . $ext;
        $upload_path = 'uploads/stories/' . $new_filename;

        // إنشاء المجلد إذا لم يكن موجودًا
        if (!is_dir('uploads/stories')) {
            mkdir('uploads/stories', 0777, true);
        }

        if (move_uploaded_file($media['tmp_name'], $upload_path)) {
            // إدخال البيانات في قاعدة البيانات
            $stmt = $conn->prepare("INSERT INTO stories (user_id, media_path, caption, media_type, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isss", $user_id, $upload_path, $caption, $media_type);
            $stmt->execute();
            $stmt->close();

            $message = "✅ تم رفع الستوري بنجاح!";
        } else {
            $message = "❌ فشل في حفظ الملف.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <title>رفع ستوري</title>
    <link rel="stylesheet" href="assets/style/main.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            direction: rtl;
            background: #f9f9f9;
            padding: 20px;
        }

        .form-container {
            max-width: 500px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .form-container input,
        .form-container textarea {
            width: 100%;
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .form-container button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 6px;
        }

        .form-container button:hover {
            background-color: #45a049;
        }

        .message {
            margin-bottom: 15px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>رفع ستوري جديدة</h2>

        <?php if ($message): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label for="story_media">اختر صورة أو فيديو (MP4 فقط):</label>
            <input type="file" name="story_media" accept="image/*,video/mp4" required>

            <label for="caption">نص اختياري للستوري:</label>
            <textarea name="caption" rows="3" placeholder="أدخل وصفاً (اختياري)"></textarea>

            <button type="submit">رفع الستوري</button>
        </form>
    </div>
</body>

</html>