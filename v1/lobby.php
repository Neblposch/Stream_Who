<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lobby</title>
<link rel="stylesheet" href="style.css">
</head>

<body class="lobby">

<!-- Burger Menu -->
<div class="menu">
    <div class="burger">&#9776;</div>
    <div class="menu-content">
        <a href="index.php">Leave</a>
        <a href="#">Impressum</a>
        <a href="#">More</a>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    <h1 class="lobbyHeading">Lobby</h1>

<!-- Lobby Code -->
<div class="lobby-code">
    CODE: ABCD12
</div>

<h2>Players</h2>

<!-- Player List -->
<div class="player-grid">

    <div class="player">
        <div class="avatar"></div>
        <span class="username">PlayerOne</span>
    </div>

    <div class="player">
        <div class="avatar"></div>
        <span class="username">ShadowCat</span>
    </div>

    <div class="player">
        <div class="avatar"></div>
        <span class="username">PixelGhost</span>
    </div>

</div>

<!-- only if admin -->
<form action="game.php" method="post">
    <button type="submit">Start Game</button>
</form>

<audio id="lobbyAudio" loop>
  <source src="media/lobby.mp3" type="audio/mpeg">
</audio>

</div>


<script>
    const audio = document.getElementById("lobbyAudio")

    window.addEventListener("load", () => {
        audio.volume = 0.5 
        audio.play().catch(() => {
        document.addEventListener("click", () => {
            audio.play()
        }, { once: true })
        })
    })
</script>

</body>
</html>