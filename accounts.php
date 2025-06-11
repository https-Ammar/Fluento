<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['user_id'];

// جلب جميع المستخدمين باستثناء المستخدم الحالي
$stmt = $conn->prepare("SELECT id, full_name, profile_image, is_online FROM users WHERE id != ?");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <title>الحسابات</title>
</head>

<body>
    <h2>قائمة المستخدمين</h2>

    <?php while ($user = $result->fetch_assoc()): ?>
        <div class="user-card">
            <img src="<?= htmlspecialchars($user['profile_image']) ?>" alt="الصورة">
            <div class="user-info">
                <strong><?= htmlspecialchars($user['full_name']) ?></strong><br>
                <?= $user['is_online'] ? '🟢 متصل' : '⚪ غير متصل' ?>
            </div>
            <a href="chat.php?user_id=<?= $user['id'] ?>">📩 مراسلة</a>
        </div>
    <?php endwhile; ?>
</body>

</html>