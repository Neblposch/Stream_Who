<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/spotify_helper.php';

startSession();


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stream Who</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="mobile.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="header">
        <img height="60vh" src="img/logo.png" alt="logo">
        <h2>StreamWho</h2>
        <p>
            <?php if (!empty($_SESSION['access_token'])): ?>
                <?php header("Location: spotify_login.php");?>
                <a href="logout.php">Log Out</a>
            <?php else: ?>
                <a href="spotify_login.php">Log In</a>
            <?php endif; ?>
        </p>
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
        </section>
        
        <section id="reviews">
            <h3>Read our reviews:</h3>
            <div class="slider">
                <div class="cardflip">
                    <div class="flip">
                    <div class="front">Mia (17)</div>
                    <div class="back">"actually way more fun than i expected 😭 we played it at a sleepover and it got so competitive so fast. like i was SO sure i knew my friends taste in music but i was wrong every round"</div>
                    </div>
                </div>

                <div class="cardflip">
                    <div class="flip">
                    <div class="front">Jonas (19)</div>
                    <div class="back">“pretty solid party game tbh. connecting spotify was super easy and the whole guessing thing makes it kinda chaotic in a good way. would 100% play again with friends”</div>
                    </div>
                </div>

                <div class="cardflip">
                    <div class="flip">
                    <div class="front">Lena (16)</div>
                    <div class="back">“this game exposed my friends SO hard 😭 i thought i knew who listens to what but nahh. also it’s really fast paced so no one gets bored”</div>
                    </div>
                </div>

                <div class="cardflip">
                    <div class="flip">
                    <div class="front">Alex (23)</div>
                    <div class="back">“fun concept and actually works really well in a group. we tried it at a small hangout and it kept everyone involved the whole time. simple but addictive”</div>
                    </div>
                </div>

                <div class="cardflip">
                    <div class="flip">
                    <div class="front">Sofie (19)</div>
                    <div class="back">“i love how it uses your real spotify stats, it makes everything feel personal. also the reactions when someone gets exposed are priceless 😭”</div>
                    </div>
                </div>

                <div class="cardflip">
                    <div class="flip">
                    <div class="front">David (20)</div>
                    <div class="back">“didn’t expect much but it’s actually really entertaining. good mix of guessing and laughing at your friends’ questionable music taste”</div>
                    </div>
                </div>

                <div class="cardflip">
                    <div class="flip">
                    <div class="front">Emma (15)</div>
                    <div class="back">“we played like 10 rounds and didn’t even notice the time passing. it’s kinda addicting and super easy to understand even if you’ve never played before”</div>
                    </div>
                </div>
                <div class="cardflip">
                    <div class="flip">
                    <div class="front">Steven (22)</div>
                    <div class="back">“great for group calls too, we tried it online and it still worked really well. definitely one of the better party games I’ve seen recently”</div>
                    </div>
                </div>
                <div class="cardflip">
                    <div class="flip">
                    <div class="front">Carol (18)</div>
                    <div class="back">“THIS GAME IS SO FUN OMG i kept guessing wrong but it didn’t even matter, we were all just laughing the whole time”</div>
                    </div>
                </div>
                <div class="cardflip">
                    <div class="flip">
                    <div class="front">Tom (18)</div>
                    <div class="back">“simple idea but really effective. it turns music taste into a game which is honestly genius for friend groups.”</div>
                    </div>
                </div>
                <div class="cardflip">
                    <div class="flip">
                    <div class="front">Want to leave your own review?</div>
                    <div class="back"><a href="mailto:annalenasee9@gmail.com?subject=Review%20Request">Text us and send a request!</a><br>Make sure to add your name, and optional age</div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <script>
        document.querySelectorAll('.cardflip').forEach(card => {
        card.addEventListener('click', () => {
            card.classList.toggle('tapped');
        });
        });
    </script>
    <footer>
        <a href="impressum.html">Impressum</a> | 
        <a href="datenschutz.html">Datenschutz</a>
    </footer>
</body>
</html>