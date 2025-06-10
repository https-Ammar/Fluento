<?php
session_start();
require 'db.php';

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, password FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['full_name'];
            $conn->query("UPDATE users SET is_online=1 WHERE id={$row['id']}");
            header("Location: users_list.php");
            exit();
        }
    }

    $errorMessage = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fluento Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="./assets/style/main.css">
</head>

<body>
    <section class="custom-login-section">
        <div class="custom-form-box">
            <div class="custom-form-value">
                <form method="POST">
                    <div class="custom-form-title">
                        <h2>Fluento Login</h2>
                        <p>Welcome back! Please log in to your account.</p>
                    </div>

                    <div class="custom-inputbox">
                        <div class="flex">
                            <input type="text" name="email" required />
                            <ion-icon name="mail-outline"></ion-icon>
                        </div>
                        <label>Email</label>
                    </div>

                    <div class="custom-inputbox">
                        <div class="flex">
                            <input type="password" name="password" id="password" required />
                            <ion-icon id="togglePassword" name="eye-outline"></ion-icon>
                        </div>
                        <label>Password</label>


                    </div>

                    <?php if (!empty($errorMessage)): ?>


                        <p class="Erorr">
                            <?php echo $errorMessage; ?>
                        </p>

                    <?php endif; ?>

                    <div class="custom-forget">
                        <label><input type="checkbox" /> Remember Me</label>
                        <a href="#">Forgot password?</a>
                    </div>

                    <button type="submit" class="custom-button">Log In</button>

                    <div class="custom-register">
                        <p>Don't have an account? <a href="./register.php">Register</a></p>
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