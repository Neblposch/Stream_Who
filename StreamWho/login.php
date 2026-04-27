<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/spotify_helper.php';

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
    <style>
        .login-options {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .login-option {
            flex: 1;
            min-width: 250px;
            padding: 20px;
            border: 2px solid #ccc;
            border-radius: 8px;
            text-align: center;
        }
        .login-option h3 {
            margin-top: 0;
        }
        .login-option form {
            margin-top: 15px;
        }
        .spotify-btn {
            background-color: #1DB954;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 24px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        .spotify-btn:hover {
            background-color: #1ed760;
        }
    </style>
</head>
<body class="lobby">
    <div class="container">
        <h1>Login to StreamWho</h1>
        <?php if ($error): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        
        <div class="login-options">
            <!-- Username Login -->
            <div class="login-option">
                <h3>Classic Login</h3>
                <form action="login.php" method="post">
                    <input type="text" name="username" placeholder="Enter your username" required>
                    <button type="submit">Login</button>
                </form>
                <p>Don't have a username? Just enter one!</p>
                <p><strong>Demo:</strong> Alice, Bob, Charlie, Diana</p>
            </div>
            
            <!-- Spotify Login -->
            <div class="login-option">
                <h3>Login with Spotify</h3>
                <p>Get personalized data from your Spotify account</p>
                <a href="spotify_login.php" class="spotify-btn">Connect with Spotify</a>
            </div>
        </div>
    </div>
</body>
</html>