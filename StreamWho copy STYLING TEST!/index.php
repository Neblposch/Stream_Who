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
            <div id="text" class="small-info-box">
                <p>You don’t guess songs — you guess the person behind the song.
                <br><br>
                Each round, a track is selected from one player’s listening history and shown to everyone. The cover, title, and artist appear on screen, but the real challenge isn’t identifying the music itself.
                <br>
                Instead, players try to figure out which person in the group has listened to it the most.
                <br>
                After everyone makes their guess, the answer is revealed and points are updated. Then the game moves on to the next round, using a different player’s data, keeping everything unpredictable and fast-paced.
                <br>
                Over time, patterns start to show — who loops songs, who forgets about tracks, and who has surprisingly specific taste.
                <br><br>
                It’s simple: observe, think, and read the room through music.</p>
            </div>
            <div id="log-in-start">
                <button><a href="?login=1" style="color:inherit; text-decoration:none;">Login</a></button>
            </div>
        </section>
        <section>
            <div class="slider">
                <div class="card">
                    <div class="flip">
                    <div class="front">Card 1</div>
                    <div class="back">Info 1</div>
                    </div>
                </div>

                <div class="card">
                    <div class="flip">
                    <div class="front">Card 2</div>
                    <div class="back">Info 2</div>
                    </div>
                </div>

                <div class="card">
                    <div class="flip">
                    <div class="front">Card 3</div>
                    <div class="back">Info 3</div>
                    </div>
                </div>

                <div class="card">
                    <div class="flip">
                    <div class="front">Card 4</div>
                    <div class="back">Info 4</div>
                    </div>
                </div>

                <div class="card">
                    <div class="flip">
                    <div class="front">Card 5</div>
                    <div class="back">Info 5</div>
                    </div>
                </div>

                <div class="card">
                    <div class="flip">
                    <div class="front">Card 6</div>
                    <div class="back">Info 6</div>
                    </div>
                </div>

            </div>
        </section>
    </main>

    <footer>
        <a href="#">Impressum</a> | <a href="?login=1">Log In</a>
    </footer>
</body>
</html>