<?php
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$old_username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $old_username = $username;

    if ($username === '' || $password === '') {
        $errors[] = 'Please enter both username and password.';
    } else {
        // Allow login with either username or email
        $stmt = $pdo->prepare('SELECT user_id, username, password FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];

            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = 'Incorrect username/email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Farmers Marketplace</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Welcome Back</h1>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="username">Username or Email</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($old_username) ?>" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Log In</button>
        </form>

        <div class="link-row">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </div>
</body>
</html>