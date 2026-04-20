<?php
require_once __DIR__ . '/functions.php';

startSession();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    if ($username === '') {
        $error = 'Please enter a username.';
    } else {
        try {
            loginUser($username);
            header('Location: lobby.php');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StreamWho</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="lobby">
    <div class="container">
        <h1>Login to StreamWho</h1>
        <?php if ($error): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form action="login.php" method="post">
            <input type="text" name="username" placeholder="Enter your username" required>
            <button type="submit">Login</button>
        </form>
        <p>Don't have a username? Just enter one and it'll be created for you!</p>
        <p>Dummy users: Alice, Bob, Charlie, Diana</p>
    </div>
</body>
</html>