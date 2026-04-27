<?php
require_once __DIR__ . '/spotify_helper.php';

startSession();

// If logged in, redirect to lobby
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
    <title>Stream Who</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <img height="60vh" src="img/logo.png" alt="logo">
        <h2>StreamWho</h2>
        <p>
            <?php if (!empty($_SESSION['access_token'])): ?>
                <a href="logout.php">Log Out</a>
            <?php else: ?>
                <a href="spotify_login.php">Log In</a>
            <?php endif; ?>
        </p>
    </header>

    <main>
        <section id="info">
            <div id="vid" class="small-info-box">
                <img src="img/placeholderVid.png" alt="placeholder">
            </div>
            <div id="text" class="small-info-box">
                <p>You don’t guess songs — you guess the person behind the song.

                Each round, a track is selected from one player’s listening history and shown to everyone. The cover, title, and artist appear on screen, but the real challenge isn’t identifying the music itself.

                Instead, players try to figure out which person in the group has listened to it the most.

                After everyone makes their guess, the answer is revealed and points are updated. Then the game moves on to the next round, using a different player’s data, keeping everything unpredictable and fast-paced.

                Over time, patterns start to show — who loops songs, who forgets about tracks, and who has surprisingly specific taste.

                It’s simple: observe, think, and read the room through music.</p>
            </div>
            <div id="log-in-start">
                <button><a href="spotify_login.php">Log In with Spotify!</a></button>
            </div>
        </section>
    </main>

    <footer>
        <a href="?">Impressum</a> | 
        <?php if (!empty($_SESSION['access_token'])): ?>
            <a href="logout.php">Log Out</a>
        <?php else: ?>
            <a href="spotify_login.php">Log In</a>
        <?php endif; ?>
    </footer>
</body>
</html>