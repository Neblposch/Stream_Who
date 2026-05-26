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
<div class="menu" id="menu">
    <button type="button" class="burger" id="menuToggle" aria-expanded="false" aria-controls="menuContent">
        &#9776;
    </button>
    <div class="menu-content" id="menuContent">
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
        <?php if (!empty($spotifyUser['images'][0]['url'])): ?>
            <img src="<?= htmlspecialchars($spotifyUser['images'][0]['url']) ?>" alt="avatar" style="height: 60px; border-radius: 30px;">
        <?php endif; ?>
        <h2>Welcome, <?= htmlspecialchars($spotifyUser['display_name'] ?? 'Player') ?>!</h2>
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
                <button type="submit" name="action" value="join">
                    Confirm Join
                </button>
            </div>
        </div>
    </form>

    <script>
        const showJoin = document.getElementById('showJoin')
        const joinSection = document.getElementById('joinSection')
        const createRoom = document.getElementById('createRoomDiv')
        showJoin.addEventListener('click', () => {
            joinSection.style.display = 'block'
            showJoin.style.display = 'none'
            createRoom.style.display = 'none'
        })

        const menu = document.getElementById('menu')
        const menuToggle = document.getElementById('menuToggle')

        if (menu && menuToggle) {
            menuToggle.addEventListener('click', () => {
                const isOpen = menu.classList.toggle('open')
                menuToggle.setAttribute('aria-expanded', String(isOpen))
            })
        }
    </script>
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