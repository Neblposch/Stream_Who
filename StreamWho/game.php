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
$isHost = false;

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
        $isHost = ($roomData['host_id'] ?? '') === $currentUserId;
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
                <div id="roleBadge" class="role-badge">
                    <?= $isHost ? 'Host view' : 'Player view' ?>
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
                status: 'loading',
                track: null,
                my_guess: null,
                is_host: <?= $isHost ? 'true' : 'false' ?>,
            };
            let currentAudio = null;
            let currentTrackId = null;

            async function parseApiResponse(response) {
                const text = await response.text();

                if (!text) {
                    throw new Error('Empty response from server.');
                }

                try {
                    const data = JSON.parse(text);

                    if (!response.ok) {
                        throw new Error(data?.message || text);
                    }

                    return data;
                } catch (error) {
                    if (error instanceof SyntaxError) {
                        if (!response.ok) {
                            throw new Error(text);
                        }

                        throw new Error('Invalid JSON response from server.');
                    }

                    throw error;
                }
            }

            function showStatus(message) {
                const feedback = document.getElementById('feedback');
                feedback.textContent = message;
                feedback.style.color = '';
            }

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
                    const data = await parseApiResponse(response);

                    if (data && data.success !== false) {
                        gameState = { ...gameState, ...data };
                    } else if (data && data.message) {
                        showStatus(data.message);
                    }

                    render();
                } catch (error) {
                    console.error('Unable to refresh game state', error);
                    showStatus('Unable to refresh game state. Please reload the page.');
                }
            }

            async function startGame() {
                if (!gameState.is_host) {
                    return;
                }

                try {
                    const response = await fetch('game_logic.php?action=start', { method: 'POST' });
                    const data = await parseApiResponse(response);

                    if (data && data.success !== false) {
                        gameState = { ...gameState, ...data };
                    } else if (data && data.message) {
                        showStatus(data.message);
                    }

                    render();
                } catch (error) {
                    console.error('Unable to start game', error);
                    showStatus('Unable to start the game right now.');
                }
            }

            async function nextRound() {
                if (!gameState.is_host) {
                    return;
                }

                try {
                    const response = await fetch('game_logic.php?action=next_round', { method: 'POST' });
                    const data = await parseApiResponse(response);

                    if (data && data.success !== false) {
                        gameState = { ...gameState, ...data };
                    } else if (data && data.message) {
                        showStatus(data.message);
                    }

                    render();
                } catch (error) {
                    console.error('Unable to start next round', error);
                    showStatus('Unable to start the next round right now.');
                }
            }

            async function submitGuess(playerId) {
                if (gameState.status !== 'active' || gameState.my_guess) {
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('guess', playerId);
                    const response = await fetch('game_logic.php?action=guess', { method: 'POST', body: formData });
                    const data = await parseApiResponse(response);

                    if (data && data.success !== false) {
                        gameState = { ...gameState, ...data };
                        showFeedback(data.correct);
                    } else if (data && data.message) {
                        showStatus(data.message);
                    }

                    render();
                } catch (error) {
                    console.error('Unable to submit guess', error);
                    showStatus('Unable to submit your guess right now.');
                }
            }

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
                document.getElementById('timer').textContent = gameState.status === 'active' ? `Time left: ${gameState.time_left ?? 0}s` : 'Time left: 0s';

                const scoresDiv = document.getElementById('scores');
                scoresDiv.textContent = 'Scores: ';
                Object.entries(gameState.scores || {}).forEach(([id, score]) => {
                    const player = (gameState.players || []).find(p => p.id === id);
                    if (player) {
                        scoresDiv.textContent += `${player.name}: ${score} `;
                    }
                });

                const canGuess = gameState.status === 'active' && !gameState.my_guess && gameState.can_guess !== false;
                document.querySelectorAll('.player-btn').forEach(btn => {
                    btn.disabled = !canGuess;
                });

                const controls = document.getElementById('controls');
                const startBtn = document.getElementById('startBtn');
                const nextBtn = document.getElementById('nextBtn');
                controls.style.display = gameState.is_host ? 'flex' : 'none';
                startBtn.disabled = !gameState.is_host || gameState.status === 'active' || gameState.status === 'starting';
                nextBtn.disabled = !gameState.is_host || gameState.status !== 'revealed';

                if (gameState.status === 'starting') {
                    document.getElementById('feedback').textContent = 'Starting the next round...';
                    document.getElementById('feedback').style.color = '';
                } else if (gameState.status === 'active') {
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

            render();
            fetchState();
            setInterval(fetchState, 1500);
        </script>
    <?php endif; ?>
</body>
</html>
