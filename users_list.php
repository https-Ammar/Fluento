<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['user_id'];
$success = null;
$error = null;
$message = null;
$search_result = null;
$chat_user = null;

// حذف الحساب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $current_user);
    $stmt->execute();
    session_destroy();
    header("Location: goodbye.php");
    exit();
}

// تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'])) {
    $name = trim($_POST['full_name']);
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;

    if (!empty($_FILES['profile_image']['name'])) {
        $image_name = time() . '_' . basename($_FILES['profile_image']['name']);
        $target = 'uploads/' . $image_name;
        move_uploaded_file($_FILES['profile_image']['tmp_name'], $target);
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, profile_image = ?, notifications_enabled = ? WHERE id = ?");
        $stmt->bind_param("ssii", $name, $target, $notifications_enabled, $current_user);
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, notifications_enabled = ? WHERE id = ?");
        $stmt->bind_param("sii", $name, $notifications_enabled, $current_user);
    }
    $stmt->execute();
    $success = "Data updated successfully.";
}

// رفع ستوري
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['story_media'])) {
    $caption = trim($_POST['caption']);
    $media = $_FILES['story_media'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];

    if (!in_array($media['type'], $allowed_types)) {
        $message = "\u0627\u0644\u0645\u0644\u0641 \u064a\u062c\u0628 \u0623\u0646 \u064a\u0643\u0648\u0646 \u0635\u0648\u0631\u0629 \u0623\u0648 \u0641\u064a\u062f\u064a\u0648 (MP4 \u0641\u0642\u0637).";
    } elseif ($media['error'] !== 0) {
        $message = "\u062d\u062f\u062b \u062e\u0637\u0623 \u0623\u062b\u0646\u0627\u0621 \u0631\u0641\u0639 \u0627\u0644\u0645\u0644\u0641.";
    } else {
        $media_type = str_starts_with($media['type'], 'image') ? 'image' : 'video';
        $ext = pathinfo($media['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('story_', true) . '.' . $ext;
        $upload_path = 'uploads/stories/' . $new_filename;

        if (!is_dir('uploads/stories')) {
            mkdir('uploads/stories', 0777, true);
        }

        if (move_uploaded_file($media['tmp_name'], $upload_path)) {
            $stmt = $conn->prepare("INSERT INTO stories (user_id, media_path, caption, media_type, created_at, expires_at) VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR))");
            $stmt->bind_param("isss", $current_user, $upload_path, $caption, $media_type);
            $stmt->execute();
            $stmt->close();
            $message = "\u2705 \u062a\u0645 \u0631\u0641\u0639 \u0627\u0644\u0633\u062a\u0648\u0631\u064a \u0628\u0646\u062c\u0627\u062d!";
        } else {
            $message = "\u274c \u0641\u0634\u0644 \u0641\u064a \u062d\u0641\u0638 \u0627\u0644\u0645\u0644\u0641.";
        }
    }
}

// إنشاء بوست
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_text'])) {
    $post_text = trim($_POST['post_text']);
    $media_type = null;
    $media_path = null;

    if (!empty($_FILES['post_media']['name'])) {
        $media = $_FILES['post_media'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];

        if (!in_array($media['type'], $allowed_types)) {
            $message = "\u0627\u0644\u0645\u0644\u0641 \u064a\u062c\u0628 \u0623\u0646 \u064a\u0643\u0648\u0646 \u0635\u0648\u0631\u0629 \u0623\u0648 \u0641\u064a\u062f\u064a\u0648 (MP4 \u0641\u0642\u0637).";
        } elseif ($media['error'] !== 0) {
            $message = "\u062d\u062f\u062b \u062e\u0637\u0623 \u0623\u062b\u0646\u0627\u0621 \u0631\u0641\u0639 \u0627\u0644\u0645\u0644\u0641.";
        } else {
            $media_type = str_starts_with($media['type'], 'image') ? 'image' : 'video';
            $ext = pathinfo($media['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('post_', true) . '.' . $ext;
            $upload_path = 'uploads/posts/' . $new_filename;

            if (!is_dir('uploads/posts')) {
                mkdir('uploads/posts', 0777, true);
            }

            if (move_uploaded_file($media['tmp_name'], $upload_path)) {
                $media_path = $upload_path;
            } else {
                $message = "\u274c \u0641\u0634\u0644 \u0641\u064a \u062d\u0641\u0638 \u0645\u0644\u0641 \u0627\u0644\u0628\u0648\u0633\u062a.";
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO posts (user_id, media_path, post_text, media_type, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $current_user, $media_path, $post_text, $media_type);
    $stmt->execute();
    $stmt->close();
    $message = "\u2705 \u062a\u0645 \u0625\u0646\u0634\u0627\u0621 \u0627\u0644\u0628\u0648\u0633\u062a \u0628\u0646\u062c\u0627\u062d!";
}

// حذف بوست
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $delete_id = intval($_POST['delete_post_id']);
    $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $current_user);
    if ($stmt->execute()) {
        echo "<script>location.reload();</script>";
    }
}

// معلومات المستخدم الحالي
$stmt = $conn->prepare("SELECT user_code, full_name, email, profile_image, notifications_enabled, is_online FROM users WHERE id = ?");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$current_user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current_user_data) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// جلب المستخدمين الآخرين
$stmt = $conn->prepare("SELECT id, full_name, profile_image, is_online FROM users WHERE id != ?");
$stmt->bind_param("i", $current_user);
$stmt->execute();
$users_result = $stmt->get_result();
$stmt->close();

// البحث بكود مستخدم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_code']) && !isset($_POST['full_name'])) {
    $search_code = trim($_POST['user_code']);

    if (strlen($search_code) !== 8 || !ctype_digit($search_code)) {
        $error = "Please enter an 8-digit code.";
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, profile_image, is_online FROM users WHERE user_code = ?");
        $stmt->bind_param("s", $search_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $search_result = $result->num_rows > 0 ? $result->fetch_assoc() : null;
        $stmt->close();
    }
}

// معلومات مستخدم الدردشة
$other_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($other_id > 0 && $other_id !== $current_user) {
    $stmt = $conn->prepare("SELECT full_name, profile_image, is_online FROM users WHERE id = ?");
    $stmt->bind_param("i", $other_id);
    $stmt->execute();
    $chat_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// جلب الستوريهات
$stmt = $conn->prepare("SELECT s.*, u.full_name, u.profile_image FROM stories s JOIN users u ON s.user_id = u.id WHERE s.expires_at > NOW() ORDER BY s.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
?>




<!-- فورم إنشاء بوست -->
<form method="POST" enctype="multipart/form-data">
    <textarea name="post_text" placeholder="اكتب البوست هنا..." required style="width:100%;height:80px;"></textarea>
    <input type="file" name="post_media" accept="image/*,video/*">
    <button type="submit" name="create_post">نشر البوست</button>
</form>

<?php
$stmt = $conn->prepare("SELECT p.*, u.full_name, u.profile_image FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
$stmt->execute();
$posts_result = $stmt->get_result();

while ($post = $posts_result->fetch_assoc()):
    $post_id = $post['id'];
    $created_at = date('Y-m-d H:i', strtotime($post['created_at']));

    $likes_stmt = $conn->prepare("SELECT COUNT(*) AS total_likes FROM likes WHERE post_id = ?");
    $likes_stmt->bind_param("i", $post_id);
    $likes_stmt->execute();
    $likes_result = $likes_stmt->get_result()->fetch_assoc();
    $total_likes = $likes_result['total_likes'];

    $like_stmt = $conn->prepare("SELECT 1 FROM likes WHERE post_id = ? AND user_id = ?");
    $like_stmt->bind_param("ii", $post_id, $current_user);
    $like_stmt->execute();
    $liked = $like_stmt->get_result()->num_rows > 0;
    ?>
    <div class="post">
        <div class="user-info">
            <img src="<?= htmlspecialchars($post['profile_image']) ?>" class="profile"
                style="width:40px;height:40px;border-radius:50%;">
            <strong><?= htmlspecialchars($post['full_name']) ?></strong>
            <small style="display:block; color:gray;">⏱ <?= $created_at ?></small>
        </div>

        <p><?= nl2br(htmlspecialchars($post['post_text'])) ?></p>

        <?php if ($post['media_type'] === 'image'): ?>
            <img src="<?= htmlspecialchars($post['media_path']) ?>" class="post-media" style="max-width:100%;">
        <?php elseif ($post['media_type'] === 'video'): ?>
            <video controls class="post-media" style="max-width:100%;">
                <source src="<?= htmlspecialchars($post['media_path']) ?>" type="video/mp4">
            </video>
        <?php endif; ?>

        <form method="POST" style="display:inline-block;">
            <input type="hidden" name="post_id" value="<?= $post_id ?>">
            <button type="submit" name="like_post">
                <?= $liked ? '❤️ إلغاء الإعجاب' : '🤍 إعجاب' ?>
            </button>
        </form>

        <span>الإعجابات: <?= $total_likes ?></span>

        <?php if ($post['user_id'] == $current_user): ?>
            <form method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا البوست؟');" style="display:inline-block;">
                <input type="hidden" name="delete_post_id" value="<?= $post_id ?>">
                <button type="submit" name="delete_post" style="color:red;">🗑 حذف</button>
            </form>
        <?php endif; ?>
    </div>
<?php endwhile; ?>

<!--  -->


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



<!-- Modal Trigger Button (optional if you want to open modal manually) -->
<!-- <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#profileModal">Edit Profile</button> -->
<style>
    img.post-media {
        width: 200px;
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

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content p-4">
            <div class="modal-header">
                <h2 class="modal-title" id="profileModalLabel">My Profile</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <?php if (isset($success)): ?>
                    <p style="color: green;"><?= $success ?></p>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label>Current Image:</label><br>
                        <img src="<?= htmlspecialchars($current_user_data['profile_image']) ?>" alt="Profile Image"
                            style="max-width: 100px; border-radius: 8px;">
                    </div>

                    <div class="mb-3">
                        <label>Change Image:</label>
                        <input type="file" name="profile_image" accept="image/*" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Full Name:</label>
                        <input type="text" name="full_name"
                            value="<?= htmlspecialchars($current_user_data['full_name']) ?>" class="form-control"
                            required>
                    </div>

                    <div class="mb-3">
                        <label>Email:</label>
                        <input type="email" value="<?= htmlspecialchars($current_user_data['email']) ?>"
                            class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label>User ID:</label><br>
                        <span
                            class="badge bg-secondary p-2"><?= htmlspecialchars($current_user_data['user_code']) ?></span>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="notifications_enabled" value="1"
                            <?= $current_user_data['notifications_enabled'] ? 'checked' : '' ?>>
                        <label class="form-check-label">
                            Enable email notifications
                        </label>
                    </div>

                    <button type="submit" class="btn btn-success w-100">💾 Save Changes</button>
                </form>

                <hr>

                <a href="delete_account.php"
                    onclick="return confirm('Are you sure you want to permanently delete your account?');"
                    class="btn btn-danger w-100 mt-2">
                    🗑️ Delete Account Permanently
                </a>
            </div>
        </div>
    </div>
</div>
<script>
    var myModal = new bootstrap.Modal(document.getElementById('profileModal'));
    window.onload = function () {
        myModal.show();
    };
</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat App | M.A Developer</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Chat list styling */
        .chat {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
            text-decoration: none;
        }

        .profile-image {
            width: 50px;
            height: 50px;
            background-size: cover;
            background-position: center;
            border-radius: 50%;
            position: relative;
        }

        .status-indicator {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid white;
        }

        .status-indicator.online {
            background-color: #00c853;
        }

        .status-indicator.offline {
            background-color: #ccc;
        }

        .user-header {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-main {
            width: 50px;
            height: 50px;
            background-size: cover;
            background-position: center;
            border-radius: 50%;
            position: relative;
            display: inline-block;
        }

        /* Modal styling */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .modal-content {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            width: 300px;
            text-align: center;
            box-shadow: 0 0 25px rgba(0, 0, 0, 0.2);
            position: relative;
            font-family: 'Cairo', sans-serif;
        }


        .status-dot {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .status-dot.online {
            background-color: #28a745;
        }

        .status-dot.offline {
            background-color: #ccc;
        }

        .user-info {
            font-size: 16px;
            margin-bottom: 15px;
        }

        #closeModal {
            background-color: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>

<body>


    <div class="main-container">
        <!-- Navigation Bar -->
        <div class="navbar">
            <div class="nav-left">
                <button class="toggle-btn" id="toggleBtn">
                    <img src="images/icon-menu.svg" alt="Menu" />
                </button>
                <img src="images/logo.svg" alt="Logo" class="main-logo" />
            </div>
            <div class="nav-right">
                <form class="search-bar" method="POST">
                    <img src="images/icon-search.svg" alt="Search" />
                    <input type="text" name="user_code" placeholder="Search here..." maxlength="8" required>
                </form>
                <img src="images/icon-setting.svg" alt="Settings" class="setting" />
                <a href="#" class="user">
                    <img src="images/img-04.jpg" alt="User" />
                </a>
            </div>
        </div>


        <!-- Main Content Area -->
        <div class="bottom-sec">

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

            <!-- Side Menu -->
            <div class="side-menu" id="SideMenu">
                <form class="search-bar">
                    <img src="images/icon-search.svg" alt="Search" />
                    <input type="text" name="" placeholder="Search here...">
                </form>
                <ul>
                    <li>
                        <img src="images/home-icon.png" alt="Home" />
                        <a href="#" class="active">Home</a>
                    </li>
                    <li>
                        <img src="images/group-icon.png" alt="Groups" />
                        <a href="#">Groups</a>
                    </li>
                    <li id="openModal" style="cursor: pointer;">
                        <img src="images/profile-icon.png" alt="Profile" />
                        <a href="#">Profile</a>
                    </li>

                    <!-- Button to open the profile modal -->
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#profileModal">
                        👤 Edit My Profile
                    </button>


                    <!-- User Profile Modal -->
                    <div id="userModal" class="modal-overlay" style="display: none;">
                        <div class="modal-content">
                            <div class="profile-image"
                                style="background-image: url('<?= htmlspecialchars($current_user_data['profile_image']) ?>');">
                                <span
                                    class="status-dot <?= $current_user_data['is_online'] ? 'online' : 'offline' ?>"></span>
                            </div>
                            <div class="user-info">
                                <strong><?= htmlspecialchars($current_user_data['full_name']) ?></strong><br>
                                User Code: <strong><?= htmlspecialchars($current_user_data['user_code']) ?></strong><br>
                            </div>
                            <button id="closeModal">Close</button>
                        </div>
                    </div>

                    <li>
                        <img src="images/notification-icon.png" alt="Notifications" />
                        <a href="#">Notifications</a>
                    </li>
                    <li>
                        <img src="images/logout-icon.png" alt="Logout" />
                        <a href="logout.php">Logout</a>
                    </li>
                </ul>

                <!-- Chat List Section -->
                <div class="chat-sec">
                    <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
                        <?php while ($row = $users_result->fetch_assoc()): ?>
                            <a href="?user_id=<?= $row['id'] ?>">
                                <div class="chat">
                                    <div class="profile-image"
                                        style="background-image: url('<?= htmlspecialchars($row['profile_image']) ?>');">
                                        <span class="status-indicator <?= $row['is_online'] ? 'online' : 'offline' ?>"></span>
                                    </div>
                                    <p><?= htmlspecialchars($row['full_name']) ?></p>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
                    <?php endif; ?>

                    <?php if ($search_result): ?>
                        <a href="?user_id=<?= $search_result['id'] ?>">
                            <div class="chat">
                                <img src="<?= htmlspecialchars($search_result['profile_image']) ?>" alt="" />
                                <p><?= htmlspecialchars($search_result['full_name']) ?></p>
                                <span
                                    class="status-indicator <?= $search_result['is_online'] ? 'online' : 'offline' ?>"></span>
                            </div>
                        </a>
                    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                        <p style="color:gray;">No user found with this code.</p>
                    <?php endif; ?>




                </div>
            </div>

            <!-- Chat Section (shown when a user is selected) -->
            <?php if ($chat_user): ?>
                <div class="chat-section">
                    <div class="chat-header">
                        <div class="user-header">
                            <span class="user-main"
                                style="background-image: url('<?= htmlspecialchars($chat_user['profile_image']) ?>');">
                                <span class="status-indicator <?= $chat_user['is_online'] ? 'online' : 'offline' ?>"></span>
                            </span>
                            <h2><?= htmlspecialchars($chat_user['full_name']) ?></h2>
                        </div>
                        <img src="images/icon-threedot.png" alt="Options" class="threedot" />
                    </div>
                    <a href="delete_chat.php?user_id=<?= $other_id ?>"
                        onclick="return confirm('Are you sure you want to delete this conversation?')"
                        style="color: red;">Delete Conversation</a>

                    <!-- Messages Container -->
                    <div class="all-chat" id="messagesBox">


                    </div>

                    <!-- Message Input Area -->
                    <div class="chat-type">
                        <img src="images/icon-plus.png" alt="Add" />
                        <form class="message-type" id="message-form" enctype="multipart/form-data">
                            <input type="text" name="text" id="messageInput" placeholder="Enter Message...">
                            <img src="images/icon-emoji.png" alt="Emoji" />
                            <img src="images/icon-attach.png" alt="Attach" />
                            <input type="file" id="fileInput" accept="image/*,video/*" />
                            <button type="submit"> <img src="images/icon-send.png" alt="Send" class="send-icon" /></button>
                        </form>

                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        document.getElementById('openModal').addEventListener('click', function () {
            document.getElementById('userModal').style.display = 'flex';
        });

        document.getElementById('closeModal').addEventListener('click', function () {
            document.getElementById('userModal').style.display = 'none';
        });

        document.getElementById('userModal').addEventListener('click', function (e) {
            if (e.target.id === 'userModal') {
                document.getElementById('userModal').style.display = 'none';
            }
        });

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
            setInterval(fetchMessages, 3000);

            document.getElementById('message-form').addEventListener('submit', function (e) {
                e.preventDefault();

                const input = document.getElementById("messageInput");
                const fileInput = document.getElementById("fileInput");
                const file = fileInput.files[0];
                const formData = new FormData();

                if (!input.value.trim() && !file) {
                    alert("Please enter a message or select a file.");
                    return;
                }

                formData.append("receiver_id", otherId);
                formData.append("content", input.value.trim());

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
                            fileInput.value = "";
                            fetchMessages();
                        } else {
                            alert(data.message || "Error sending message.");
                        }
                    });
            });
        <?php endif; ?>

        function showUserInfo(userId) {
            fetch('get_user_info.php?user_id=' + userId)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('userModalImage').src = data.user.profile_image;
                        document.getElementById('userModalName').innerText = data.user.full_name;
                        document.getElementById('userModalEmail').innerText = data.user.email;
                        document.getElementById('userModalStatus').innerText = data.user.is_online ? '🟢 Online' : '⚪ Offline';
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