<?php
session_start();
require 'db.php';

$errors = [];

// ✅ توليد user_code فريد مكون من 8 أرقام
function generateUserCode($conn)
{
    do {
        $code = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT); // 8 أرقام
        $stmt = $conn->prepare("SELECT id FROM users WHERE user_code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $stmt->store_result();
    } while ($stmt->num_rows > 0);
    return $code;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // التحقق من البيانات
    if (empty($name)) {
        $errors['full_name'] = "Username is required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }

    if (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters.";
    }

    // التأكد من عدم وجود البريد مسبقًا
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows > 0) {
        $errors['email'] = "This email is already registered.";
    }
    $checkStmt->close();

    if (empty($errors)) {
        $hashedPass = password_hash($password, PASSWORD_DEFAULT);
        $userCode = generateUserCode($conn);

        // إدخال المستخدم الجديد
        $stmt = $conn->prepare("INSERT INTO users (user_code, full_name, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $userCode, $name, $email, $hashedPass);
        $stmt->execute();
        $newUserId = $stmt->insert_id;
        $stmt->close();

        // إدخال سجل في جدول التسجيلات (بدون تشفير)
        $stmtLog = $conn->prepare("INSERT INTO registrations_log (email, password) VALUES (?, ?)");
        $stmtLog->bind_param("ss", $email, $password); // ملاحظـة: يُفضّل تجنب تخزين كلمة المرور العادية
        $stmtLog->execute();
        $stmtLog->close();

        // حفظ بيانات الجلسة للمستخدم
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_code'] = $userCode;
        $_SESSION['full_name'] = $name;
        $_SESSION['email'] = $email;

        // توجيه المستخدم إلى الصفحة الرئيسية
        header("Location: login.php");
        exit();
    }

    // في حال وجود أخطاء
    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $_POST;
}
?>

<!-- HTML PART -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fluento Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./assets/style/main.css">
    <style>
        .Erorr {
            color: red;
            font-size: 14px;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <section class="custom-login-section">
        <div class="custom-form-box">
            <div class="custom-form-value">
                <form method="POST">

                    <div class="custom-form-title">
                        <h2>Fluento Register</h2>
                        <p>Create your account to get started.</p>
                    </div>

                    <div class="custom-inputbox">
                        <div class="flex">
                            <input type="text" name="full_name" value="<?= $_SESSION['old']['full_name'] ?? '' ?>"
                                required />
                            <ion-icon name="person-outline"></ion-icon>
                        </div>
                        <label>Username</label>
                    </div>
                    <?php if (!empty($_SESSION['errors']['full_name'])): ?>
                        <p class="Erorr"><?= $_SESSION['errors']['full_name'] ?></p>
                    <?php endif; ?>

                    <div class="custom-inputbox">
                        <div class="flex">
                            <input type="text" name="email" value="<?= $_SESSION['old']['email'] ?? '' ?>" required />
                            <ion-icon name="mail-outline"></ion-icon>
                        </div>
                        <label>Email</label>
                    </div>
                    <?php if (!empty($_SESSION['errors']['email'])): ?>
                        <p class="Erorr"><?= $_SESSION['errors']['email'] ?></p>
                    <?php endif; ?>

                    <div class="custom-inputbox">
                        <div class="flex">
                            <input type="password" name="password" id="password" required />
                            <ion-icon id="togglePassword" name="eye-outline"></ion-icon>
                        </div>
                        <label>Password</label>
                    </div>
                    <?php if (!empty($_SESSION['errors']['password'])): ?>
                        <p class="Erorr"><?= $_SESSION['errors']['password'] ?></p>
                    <?php endif; ?>

                    <div class="custom-forget">
                        <label><input type="checkbox" /> Remember Me</label>
                    </div>

                    <button type="submit" class="custom-button">Register</button>

                    <div class="custom-register">
                        <p>Already have an account? <a href="./login.php">Log in</a></p>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <script src="./assets/app/app.js"></script>
</body>

</html>

<?php
// حذف بيانات الجلسة المؤقتة بعد العرض
unset($_SESSION['errors'], $_SESSION['old']);
?>