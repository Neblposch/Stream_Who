<?php
require_once __DIR__ . '/functions.php';
startSession();

// Dummy Login
if (isset($_GET['login'])) {
    $_SESSION['user_id'] = 'dummy_' . rand(1000, 9999);
    $_SESSION['user_name'] = 'Player_' . rand(100, 999);
    $_SESSION['spotify_user'] = [
        'id' => $_SESSION['user_id'],
        'display_name' => $_SESSION['user_name'],
        'images' => []
    ];
    $_SESSION['access_token'] = 'dummy_token_' . rand(100000, 999999);
    header('Location: lobby.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stream Who</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <img height="60vh" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='45' fill='%231DB954'/%3E%3Ctext x='50' y='67' text-anchor='middle' fill='white' font-size='40'%3E♪%3C/text%3E%3C/svg%3E" alt="logo">
        <h2>StreamWho</h2>
        <p><a href="?login=1">Log In</a></p>
    </header>

    <main>
        <section id="info">
            <div id="vid" class="small-info-box">
                <div style="background:#1C2A45; height:150px; display:flex; align-items:center; justify-content:center; border-radius:12px;">🎬 Video Platzhalter</div>
            </div>
            <div id="text" class="small-info-box">
                <p>You don't guess songs — you guess the person behind the song.<br><br>
                Each round, a track is selected from one player's listening history and shown to everyone.<br><br>
                Instead, players try to figure out which person in the group has listened to it the most.<br><br>
                After everyone makes their guess, the answer is revealed and points are updated.</p>
            </div>
            <div id="log-in-start">
                <button><a href="?login=1" style="color:inherit; text-decoration:none;">Login</a></button>
            </div>
        </section>
    </main>

    <footer>
        <a href="#">Impressum</a> | <a href="?login=1">Log In</a>
    </footer>
</body>
</html>