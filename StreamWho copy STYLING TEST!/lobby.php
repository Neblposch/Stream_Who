<?php
require_once __DIR__ . '/functions.php';
startSession();
requireLogin();

$spotifyUser = $_SESSION['spotify_user'] ?? ['display_name' => $_SESSION['user_name'] ?? 'Player', 'images' => []];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        try {
            $roomCode = createRoom();
            header("Location: game.php?room={$roomCode}");
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($action === 'join') {
        $roomCode = normalizeRoomCode($_POST['roomCode'] ?? '');
        if ($roomCode === '') {
            $error = 'Enter a valid room code.';
        } else {
            try {
                joinRoom($roomCode);
                header("Location: game.php?room={$roomCode}");
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lobby</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="lobby">

<div class="menu">
    <div class="burger">&#9776;</div>
    <div class="menu-content">
        <a href="logout.php">Logout</a>
        <a href="#">Impressum</a>
    </div>
</div>

<div class="container">
    <h1 class="lobbyHeading">Lobby</h1>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="user-info">
        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Ccircle cx='30' cy='30' r='28' fill='%237ABEFF'/%3E%3Ctext x='30' y='40' text-anchor='middle' fill='white' font-size='30'%3E🎵%3C/text%3E%3C/svg%3E" alt="avatar">
        <h2>Welcome, <?= htmlspecialchars($spotifyUser['display_name'] ?? $_SESSION['user_name'] ?? 'Player') ?>!</h2>
    </div>

    <form action="lobby.php" method="post" class="lobby-form">
        <div class="form-row" id="createRoomDiv">
            <button type="submit" name="action" value="create">Create Room</button>
        </div>
    </form>

    <form action="lobby.php" method="post" class="lobby-form" id="lobbyForm">
        <div class="form-row">
            <button type="button" id="showJoin">Join Room</button>
        </div>
        <div id="joinSection" style="display: none;">
            <input id="roomCode" type="text" name="roomCode" placeholder="Enter room code">
            <div class="form-row">
                <button type="submit" name="action" value="join">Confirm Join</button>
            </div>
        </div>
    </form>
</div>

<script>
    const showJoin = document.getElementById('showJoin')
    const joinSection = document.getElementById('joinSection')
    const createRoom = document.getElementById('createRoomDiv')
    showJoin.addEventListener('click', () => {
        joinSection.style.display = 'block'
        showJoin.style.display = 'none'
        createRoom.style.display = 'none'
    })
</script>
</body>
</html>