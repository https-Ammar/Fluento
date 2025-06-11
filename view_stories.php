<?php
// view_stories.php

require 'db.php';
session_start();

// تأكد من أن المستخدم مسجّل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['user_id'];

// جلب الستوري النشطة
$stmt = $conn->prepare("SELECT s.*, u.full_name, u.profile_image FROM stories s
    JOIN users u ON s.user_id = u.id
    WHERE s.expires_at > NOW()
    ORDER BY s.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <title>عرض الستوري</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            direction: rtl;
            background-color: #f5f5f5;
            margin: 20px;
        }

        .stories-container {
            display: flex;
            gap: 15px;
            overflow-x: auto;
            padding: 10px;
        }

        .story-box {
            text-align: center;
            background: #fff;
            padding: 10px;
            border-radius: 10px;
            min-width: 150px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .story-box img.profile {
            border-radius: 50%;
            border: 2px solid #4CAF50;
            width: 60px;
            height: 60px;
        }

        .story-box p {
            font-size: 14px;
            margin: 5px 0;
        }

        .story-box img.media,
        .story-box video {
            width: 120px;
            max-height: 200px;
            margin-top: 5px;
            border-radius: 10px;
        }
    </style>
</head>

<body>

    <h2>القصص الحالية</h2>

    <div class="stories-container">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="story-box">
                <img src="<?= htmlspecialchars($row['profile_image']) ?>" class="profile" alt="Profile">
                <p><?= htmlspecialchars($row['full_name']) ?></p>
                <?php if ($row['media_type'] === 'image'): ?>
                    <img src="<?= htmlspecialchars($row['media_path']) ?>" class="media" alt="Story Image">
                <?php elseif ($row['media_type'] === 'video'): ?>
                    <video class="media" controls>
                        <source src="<?= htmlspecialchars($row['media_path']) ?>" type="video/mp4">
                        المتصفح لا يدعم تشغيل الفيديو.
                    </video>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>

</body>

</html>


