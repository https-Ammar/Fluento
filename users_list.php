<?php
session_start();
require 'db.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$current_user = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, full_name, profile_image, is_online FROM users WHERE id != ?");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$result = $stmt->get_result();
?>
<h2>المستخدمون</h2>
<?php while ($row = $result->fetch_assoc()): ?>
    <div>
        <img src="<?= htmlspecialchars($row['profile_image']) ?>" width="50">
        <?= htmlspecialchars($row['full_name']) ?>
        <?= $row['is_online'] ? '🟢' : '⚪' ?>
        <a href="message_ui.php?user_id=<?= $row['id'] ?>">مراسلة</a>
    </div>
<?php endwhile; ?>