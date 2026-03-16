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
        <a href="#">Leave</a>
        <a href="#">Impressum</a>
        <a href="#">More</a>
    </div>
</div>

<!-- Main Content -->
<div class="container">

    <img src="img/logoLight.png" class="logo" alt="Logo">

    <h1>Join Lobby</h1>

    <form action="joinLobby.php" method="post" class="lobby-form">

        <div class="join-row">
            <input type="text" name="lobbyCode" placeholder="Enter Lobby Code">

            <button type="submit">Join</button>
        </div>

    </form>

    <form action="lobby.php" method="post">
        <button type="submit">Create Lobby</button>
    </form>

</div>

</body>
</html>