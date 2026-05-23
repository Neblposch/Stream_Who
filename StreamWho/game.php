<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/spotify_helper.php';

startSession();
requireLogin();

$spotifyUser = $_SESSION['spotify_user'] ?? [];
$currentUserId = $spotifyUser['id'] ?? 'unknown';
$roomCode = isset($_GET['room']) ? normalizeRoomCode($_GET['room']) : '';
$roomData = null;
$roomError = '';

if ($roomCode === '') {
    $roomError = 'Room code missing.';
} else {
    $roomData = getRoom($roomCode);
    if (!$roomData) {
        $roomError = 'Room not found.';
    } elseif (!isUserInRoom($roomCode)) {
        $roomError = 'You are not a member of this room.';
    } else {
        $_SESSION['current_room'] = $roomCode;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StreamWho</title>
    <link rel="stylesheet" href="style.css">
</head>
<body id="gameBody" class="background">
    <?php if ($roomError): ?>
        <div class="container">
            <h1>Error</h1>
            <p><?= htmlspecialchars($roomError) ?></p>
            <a href="lobby.php">Back to Lobby</a>
        </div>
    <?php else: ?>
        <div class="container room-info">
            <h2>Room: <?= htmlspecialchars($roomCode) ?></h2>
            <p>Players:</p>
            <ul>
                <?php foreach ($roomData['players'] as $player): ?>
                    <li>
                        <?php if (!empty($player['image'])): ?>
                            <img src="<?= htmlspecialchars($player['image']) ?>" alt="<?= htmlspecialchars($player['name']) ?>" style="height: 30px; border-radius: 15px; margin-right: 10px;">
                        <?php endif; ?>
                        <?= htmlspecialchars($player['name']) ?>
                        <?php if ($player['id'] === $roomData['host_id']): ?>
                            (host)
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div id="overlay" class="overlay">
            <div id="gameCard" class="card">
                <img id="trackCover" class="cover" src="" alt="Track Cover">
                <div id="trackTitle" class="title"></div>
                <div id="trackArtist" class="artist"></div>
                <div id="players" class="players"></div>
                <div id="gameInfo" class="info">
                    <div id="round" class="round"></div>
                    <div id="timer" class="timer"></div>
                    <div id="scores" class="scores"></div>
                </div>
                <div id="controls" class="controls">
                    <button id="startBtn" class="button control">Start Game</button>
                    <button id="nextBtn" class="button control">Next Round</button>
                </div>
                <div id="feedback" class="feedback"></div>
            </div>
        </div>

        <script>
            let gameState = {
                players: [],
                scores: {},
                status: 'idle',
                track: null,
                my_guess: null,
                is_host: false,
            };
            let currentAudio = null;
            let currentTrackId = null;

            function stopPreview() {
                if (currentAudio) {
                    currentAudio.pause();
                    currentAudio.currentTime = 0;
                    currentAudio = null;
                }
            }

            function playPreview(track) {
                if (!track || !track.preview_url) {
                    stopPreview();
                    return;
                }

                if (currentTrackId === track.id && currentAudio && !currentAudio.paused) {
                    return;
                }

                stopPreview();
                currentTrackId = track.id;
                currentAudio = new Audio(track.preview_url);
                currentAudio.volume = 0.4;
                currentAudio.play().catch(() => {});
            }

            async function fetchState() {
                try {
                    const response = await fetch('game_logic.php?action=state');
                    const data = await response.json();
                    if (data && data.success !== false) {
                        gameState = { ...gameState, ...data };
                    }
                    render();
                } catch (error) {
                    console.error('Unable to refresh game state', error);
                }
            }

            async function startGame() {
                if (!gameState.is_host) {
                    return;
                }

                const response = await fetch('game_logic.php?action=start', { method: 'POST' });
                const data = await response.json();
                if (data && data.success !== false) {
                    gameState = { ...gameState, ...data };
                }
                render();
            }

            async function nextRound() {
                if (!gameState.is_host) {
                    return;
                }

                const response = await fetch('game_logic.php?action=next_round', { method: 'POST' });
                const data = await response.json();
                if (data && data.success !== false) {
                    gameState = { ...gameState, ...data };
                }
                render();
            }

            async function submitGuess(playerId) {
                if (gameState.status !== 'active' || gameState.my_guess) {
                    return;
                }

                const formData = new FormData();
                formData.append('guess', playerId);
                const response = await fetch('game_logic.php?action=guess', { method: 'POST', body: formData });
                const data = await response.json();
                if (data && data.success !== false) {
                    gameState = { ...gameState, ...data };
                    showFeedback(data.correct);
                }
                render();
            }

            // Updated render() in `game.php`
            function render() {
                const cover = gameState.track?.cover || '';
                document.body.style.backgroundImage = cover ? `url(${cover})` : 'none';
                document.getElementById('trackCover').src = cover;
                document.getElementById('trackCover').alt = gameState.track?.title ? `${gameState.track.title} cover` : 'Track Cover';
                document.getElementById('trackTitle').textContent = gameState.track?.title || 'No track loaded';
                document.getElementById('trackArtist').textContent = gameState.track?.artist || '';

                if (gameState.status === 'active' && gameState.track) {
                    playPreview(gameState.track);
                } else {
                    stopPreview();
                }



                const playersDiv = document.getElementById('players');
                playersDiv.innerHTML = '';
                (gameState.players || []).forEach(player => {
                    const btn = document.createElement('button');
                    btn.className = 'button player-btn';
                    btn.textContent = player.name;
                    btn.onclick = () => submitGuess(player.id);
                    playersDiv.appendChild(btn);
                });

                document.getElementById('round').textContent = `Round: ${gameState.round ?? 0}`;
                document.getElementById('timer').textContent = `Time left: ${gameState.time_left ?? 0}s`;

                const scoresDiv = document.getElementById('scores');
                scoresDiv.textContent = 'Scores: ';
                Object.entries(gameState.scores || {}).forEach(([id, score]) => {
                    const player = (gameState.players || []).find(p => p.id === id);
                    if (player) {
                        scoresDiv.textContent += `${player.name}: ${score} `;
                    }
                });

                const canGuess = gameState.status === 'active' && !gameState.my_guess;
                document.querySelectorAll('.player-btn').forEach(btn => {
                    btn.disabled = !canGuess;
                });

                const startBtn = document.getElementById('startBtn');
                const nextBtn = document.getElementById('nextBtn');
                startBtn.disabled = !gameState.is_host || gameState.status === 'active';
                nextBtn.disabled = !gameState.is_host || gameState.status !== 'revealed';

                if (gameState.status === 'active') {
                    document.getElementById('feedback').textContent = gameState.my_guess ? 'Guess submitted.' : 'Guess the player who listened to this track the most.';
                    document.getElementById('feedback').style.color = '';
                } else if (gameState.status === 'revealed') {
                    const correctPlayer = (gameState.players || []).find(player => player.id === gameState.correct_player_id);
                    document.getElementById('feedback').textContent = correctPlayer ? `${correctPlayer.name} was the source player.` : 'Round complete.';
                    document.getElementById('feedback').style.color = 'white';
                } else {
                    document.getElementById('feedback').textContent = 'Waiting for the host to start the next round.';
                    document.getElementById('feedback').style.color = '';
                }
            }

            function showFeedback(correct) {
                const feedback = document.getElementById('feedback');
                feedback.textContent = correct ? 'Correct!' : 'Wrong!';
                feedback.style.color = correct ? 'green' : 'red';
            }

            document.getElementById('startBtn').onclick = startGame;
            document.getElementById('nextBtn').onclick = nextRound;

            fetchState();
            setInterval(fetchState, 3000);
        </script>
    <?php endif; ?>
</body>
</html>