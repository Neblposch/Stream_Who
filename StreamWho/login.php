<?php
require_once __DIR__ . '/spotify_helper.php';

startSession();

// If already logged in, redirect to lobby
if (!empty($_SESSION['access_token'])) {
    header('Location: lobby.php');
    exit;
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
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #1DB954, #1aa34a);
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
        }
        .login-box h1 {
            color: #1DB954;
            margin-bottom: 20px;
        }
        .login-box p {
            color: #666;
            margin-bottom: 30px;
        }
        .spotify-btn {
            background-color: #1DB954;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 24px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .spotify-btn:hover {
            background-color: #1ed760;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>StreamWho</h1>
            <p>Guess the person behind the song using Spotify data</p>
            <a href="spotify_login.php" class="spotify-btn">Login with Spotify</a>
        </div>
    </div>
</body>
</html>