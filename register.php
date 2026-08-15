<?php
session_start();
require_once 'db_connect.php';

// If already logged in, skip straight to the dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$old = ['username' => '', 'email' => '', 'full_name' => '', 'phone' => '', 'location' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $location  = trim($_POST['location'] ?? '');

    // keep entered values 
    $old = compact('username', 'email', 'full_name', 'phone', 'location');

    // --- Validation ---
    if ($username === '' || $email === '' || $password === '' || $full_name === '') {
        $errors[] = 'Username, email, full name and password are required.';
    }
    if (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // --- To check if the user account exists ---
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'That username or email is already registered.';
        }
    }

    // --- Add a new user to the user table ---
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, password, email, full_name, phone, location)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$username, $hashed, $email, $full_name, $phone, $location]);

        // Log the new user straight in
        $_SESSION['user_id']  = $pdo->lastInsertId();
        $_SESSION['username'] = $username;

        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Farmers Marketplace</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Create an Account</h1>
        <p style="font-size:14px;color:#555;">One account lets you sell your own produce and buy from others.</p>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($old['full_name']) ?>" required>

            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($old['username']) ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" required>

            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($old['phone']) ?>">

            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?= htmlspecialchars($old['location']) ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit">Register</button>
        </form>

        <div class="link-row">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>
</body>
</html>