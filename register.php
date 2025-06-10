<?php
session_start();
require 'db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validation
    if (empty($name)) {
        $errors['full_name'] = "Username is required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }

    if (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters.";
    }

    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows > 0) {
        $errors['email'] = "This email is already registered.";
    }
    $checkStmt->close();

    // If no errors, insert the new user
    if (empty($errors)) {
        $hashedPass = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $hashedPass);
        $stmt->execute();
        header("Location: login.php");
        exit();
    }

    $_SESSION['errors'] = $errors;
    $_SESSION['old'] = $_POST;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fluento Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./assets/style/main.css">
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
                        <p class="Erorr">
                            <?= $_SESSION['errors']['full_name'] ?>
                        </p>
                    <?php endif; ?>


                    <div class="custom-inputbox">
                        <div class="flex">
                            <input type="text" name="email" value="<?= $_SESSION['old']['email'] ?? '' ?>" required />
                            <ion-icon name="mail-outline"></ion-icon>
                        </div>
                        <label>Email</label>





                    </div>

                    <?php if (!empty($_SESSION['errors']['email'])): ?>

                        <p class="Erorr">
                            <?= $_SESSION['errors']['email'] ?>
                        </p>

                    <?php endif; ?>
                    <div class="custom-inputbox">
                        <div class="flex">
                            <input type="password" name="password" id="password" required />
                            <ion-icon id="togglePassword" name="eye-outline"></ion-icon>
                        </div>
                        <label>Password</label>

                    </div>
                    <?php if (!empty($_SESSION['errors']['password'])): ?>
                        <p class="Erorr"><?= $_SESSION['errors']['password'] ?> </p>
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

    <!-- Show/Hide Password Script -->
    <script src="./assets/app/app.js"></script>
</body>

</html>

<?php
// Clear session errors and old values after rendering
unset($_SESSION['errors'], $_SESSION['old']);
?>