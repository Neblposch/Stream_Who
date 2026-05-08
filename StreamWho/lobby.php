<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/spotify_helper.php';

startSession();
requireLogin();

$spotifyUser = $_SESSION['spotify_user'] ?? [];
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

<!-- Burger Menu -->
<div class="menu">
    <div class="burger">&#9776;</div>
    <div class="menu-content">
        <a href="logout.php">Logout</a>
        <a href="#">Impressum</a>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    <h1 class="lobbyHeading">Lobby</h1>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="user-info">
        <h2>Welcome, <?= htmlspecialchars($spotifyUser['display_name'] ?? 'Player') ?>!</h2>
        <?php if (!empty($spotifyUser['images'][0]['url'])): ?>
            <img src="<?= htmlspecialchars($spotifyUser['images'][0]['url']) ?>" alt="avatar" style="height: 60px; border-radius: 30px;">
        <?php endif; ?>
    </div>

    <form action="lobby.php" method="post" class="lobby-form">
        <div class="form-row">
            <button type="submit" name="action" value="create">Create Room</button>
        </div>
    </form>

    <form action="lobby.php" method="post" class="lobby-form">
        <label for="roomCode">Room Code</label>
        <input id="roomCode" type="text" name="roomCode" placeholder="Enter room code">

        <div class="form-row">
            <button type="submit" name="action" value="join">Join Room</button>
        </div>
    </form>
</div>

<script>
    const audio = document.getElementById("lobbyAudio")
    window.addEventListener("load", () => {
        if (!audio) return;
        audio.volume = 0.5;
        audio.play().catch(() => {
            document.addEventListener("click", () => {
                audio.play();
            }, { once: true });
        });
    });
</script>
</body>
</html>