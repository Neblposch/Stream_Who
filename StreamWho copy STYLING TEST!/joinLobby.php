<?php
require_once __DIR__ . '/functions.php';
startSession();
requireLogin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lobbyCode'])) {
    $roomCode = normalizeRoomCode($_POST['lobbyCode']);
    if ($roomCode === '') {
        $error = 'Please enter a valid lobby code.';
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
    <div class="logo-wrapper">
        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ccircle cx='50' cy='50' r='45' fill='%231DB954'/%3E%3Ctext x='50' y='67' text-anchor='middle' fill='white' font-size='40'%3E♪%3C/text%3E%3C/svg%3E" class="logo" alt="Logo">
        <svg class="wave-ring" viewBox="0 0 240 240">
            <path id="wavePath" fill="none" stroke="var(--text)" stroke-width="4"/>
        </svg>
    </div>

    <h1>Join Lobby</h1>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="joinLobby.php" method="post" class="lobby-form">
        <div class="join-row">
            <input type="text" name="lobbyCode" placeholder="Enter Lobby Code" required>
            <button type="submit">Join</button>
        </div>
    </form>

    <form action="lobby.php" method="post">
        <button type="submit">Create Lobby</button>
    </form>
</div>

<script>
    (function() {
        const path = document.getElementById("wavePath");
        if (!path) return;  
        const center = 120, baseRadius = 105, amplitude = 12, waves = 12, points = 180;
        let t = 0, rotation = 0;
        const rotationSpeed = 0.01;

        function drawWave() {
            let d = "";
            const pulse = Math.sin(t);         
            for (let i = 0; i <= points; i++) {
                const angle = (i / points) * Math.PI * 2;
                const r = baseRadius + amplitude * pulse * Math.sin(waves * angle);
                const x = center + r * Math.cos(angle + rotation);
                const y = center + r * Math.sin(angle + rotation);
                d += (i === 0) ? `M ${x} ${y}` : ` L ${x} ${y}`;
            }
            d += " Z";   
            path.setAttribute("d", d);
            t += 0.05;
            rotation += rotationSpeed;
            requestAnimationFrame(drawWave);
        }
        drawWave();
    })();
</script>
</body>
</html>