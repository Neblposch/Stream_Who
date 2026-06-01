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

    if ($roomData && (($roomData['game']['status'] ?? 'idle') === 'ended')) {
        header('Location: leaderboard.php?room=' . urlencode($roomCode));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>StreamWho - Game</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .room-info {
            transition: all 0.3s ease;
            background: transparent !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            border: none !important;
            box-shadow: none !important;
            padding: 2vh 2vw;
        }
        .room-info.hide-players .player-list-container {
            display: none;
        }
        .room-info.hide-players {
            text-align: center;
            padding: 1vh 2vw !important;
            margin: 0 auto 2vh auto !important;
            max-width: 300px;
            border-radius: 50px !important;
            position: sticky;
            top: 10px;
            z-index: 100;
            background: rgba(18, 32, 53, 0.95) !important;
            backdrop-filter: blur(5px) !important;
            border: 1px solid rgba(255, 255, 255, 0.14) !important;
        }
        .room-code-large {
            font-size: 2.5vh;
            letter-spacing: 0.3vw;
            background: rgba(255, 255, 255, 0.1);
            display: inline-block;
            padding: 0.8vh 2vw;
            border-radius: 50px;
            margin-top: 0;
        }
        .room-header {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .room-header h2 {
            margin-bottom: 0.5vh;
            font-size: 1.8vh;
        }
        #roomCodeDisplay {
            font-size: 1.8vh;
        }
        html {
            scroll-behavior: smooth;
        }
        #gameCard {
            transition: all 0.3s ease;
            margin: 0 auto;
            max-width: 90vw;
        }
        .overlay {
            background: transparent;
        }
        body.background {
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            background-attachment: fixed !important;
            transition: background-image 0.3s ease;
        }
    </style>
</head>
<body id="gameBody" class="background">
    <?php if ($roomError): ?>
        <div class="container">
            <h1>Error</h1>
            <p><?= htmlspecialchars($roomError) ?></p>
            <a href="lobby.php">Back to Lobby</a>
        </div>
    <?php else: ?>
        <div class="room-info" id="roomInfo">
            <div class="room-header">
                <h2>Room: <span id="roomCodeDisplay"><?= htmlspecialchars($roomCode) ?></span></h2>
                <div class="room-code-large" id="roomCodeLarge" style="display: none;"><?= htmlspecialchars($roomCode) ?></div>
            </div>
            <div id="playerListContainer" class="player-list-container">
                <p>Players:</p>
                <ul id="playerList">
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
        </div>

        <div id="gameCard" class="card">
            <img id="trackCover" class="cover" src="test.jpg" alt="Track Cover">
            <div id="trackTitle" class="title"></div>
            <div id="trackArtist" class="artist"></div>
            <div id="players" class="players"></div>
            <div id="gameInfo" class="info">
                <div id="round" class="round"></div>
                <div id="timer" class="timer"></div>
                <div id="scores" class="scores"></div>
            </div>
            <div id="controls" class="controls">
                <button id="startBtn" class="button control" style="display:none;">Start Game</button>
                <button id="playTrackBtn" class="button control">Play selected song</button>
                <button id="nextBtn" class="button control">Next Round</button>
                <button id="endGameBtn" class="button control">End Game</button>
            </div>
            <div id="feedback" class="feedback"></div>
        </div>

        <script>
            let gameState = {
                players: <?= json_encode($roomData['players']) ?>,
                scores: <?= json_encode($roomData['game']['scores'] ?? []) ?>,
                status: '<?= $roomData['game']['status'] ?? 'idle' ?>',
                track: <?= json_encode($roomData['game']['track'] ?? null) ?>,
                my_guess: null,
                is_host: <?= (($spotifyUser['id'] ?? '') === ($roomData['host_id'] ?? '')) ? 'true' : 'false' ?>,
                round: <?= $roomData['game']['round_number'] ?? 0 ?>
            };
            const roomCode = <?= json_encode($roomCode) ?>;
            
            let currentAudio = null;
            let currentTrackId = null;
            let previewFailed = false;
            let hasScrolledToGame = false;

            function stopPreview() {
                if (currentAudio) {
                    currentAudio.pause();
                    currentAudio.currentTime = 0;
                    currentAudio = null;
                }
            }

            function playPreview(track, force = false) {
                if (!track || !track.preview_url) {
                    stopPreview();
                    return;
                }

                if (previewFailed && !force) {
                    return;
                }

                if (currentTrackId === track.id && currentAudio && !currentAudio.paused) {
                    return;
                }

                stopPreview();
                currentTrackId = track.id;
                currentAudio = new Audio(track.preview_url);
                currentAudio.volume = 0.4;
                currentAudio.play().catch(() => {
                    previewFailed = true;
                    stopPreview();
                });
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

            async function endGame() {
                if (!gameState.is_host || gameState.status === 'ended') {
                    return;
                }

                const response = await fetch('game_logic.php?action=end_game', { method: 'POST' });
                const data = await response.json();
                if (data && data.success !== false) {
                    gameState = { ...gameState, ...data };
                    stopPreview();
                    window.location.href = 'leaderboard.php?room=' + encodeURIComponent(roomCode);
                    return;
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

            function scrollToGameCard() {
                const gameCard = document.getElementById('gameCard');
                if (gameCard) {
                    gameCard.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start',
                        inline: 'nearest'
                    });
                }
            }

            function updateRoomDisplay() {
                const roomInfoDiv = document.getElementById('roomInfo');
                const playerListContainer = document.getElementById('playerListContainer');
                const roomCodeLarge = document.getElementById('roomCodeLarge');
                const roomCodeDisplay = document.getElementById('roomCodeDisplay');
                
                if (gameState.status === 'active' || gameState.status === 'revealed') {
                    if (playerListContainer) {
                        playerListContainer.style.display = 'none';
                    }
                    if (roomCodeLarge) {
                        roomCodeLarge.style.display = 'inline-block';
                    }
                    if (roomCodeDisplay) {
                        roomCodeDisplay.style.display = 'none';
                    }
                    roomInfoDiv.classList.add('hide-players');
                    
                    if (!hasScrolledToGame) {
                        setTimeout(() => {
                            scrollToGameCard();
                            hasScrolledToGame = true;
                        }, 200);
                    }
                } else {
                    if (playerListContainer) {
                        playerListContainer.style.display = 'block';
                    }
                    if (roomCodeLarge) {
                        roomCodeLarge.style.display = 'none';
                    }
                    if (roomCodeDisplay) {
                        roomCodeDisplay.style.display = 'inline';
                    }
                    roomInfoDiv.classList.remove('hide-players');
                    hasScrolledToGame = false;
                }
            }

            function render() {
                const isGameEnded = gameState.status === 'ended';
                const cover = gameState.track?.cover || '';
                const coverImg = document.getElementById('trackCover');
                const playTrackBtn = document.getElementById('playTrackBtn');
                const gameCard = document.getElementById('gameCard');
                
                if (isGameEnded) {
                    stopPreview();
                    document.body.style.backgroundImage = 'none';
                    gameCard.style.display = 'none';
                    window.location.href = 'leaderboard.php?room=' + encodeURIComponent(roomCode);
                    return;
                }

                gameCard.style.display = 'block';

                if (gameState.status === 'active' && cover) {
                    coverImg.src = cover;
                    coverImg.alt = gameState.track?.title ? `${gameState.track.title} cover` : 'Track Cover';
                    coverImg.style.display = 'block';
                    document.body.style.backgroundImage = `url(${cover})`;
                } else {
                    coverImg.style.display = 'none';
                    document.body.style.backgroundImage = 'none';
                }
                
                if (gameState.status === 'active') {
                    document.getElementById('trackTitle').textContent = gameState.track?.title || 'No track loaded';
                    document.getElementById('trackArtist').textContent = gameState.track?.artist || '';
                } else {
                    document.getElementById('trackTitle').textContent = 'Waiting for the host to start the game';
                    document.getElementById('trackArtist').textContent = '';
                }

                const hasPreview = gameState.status === 'active' && Boolean(gameState.track?.preview_url);
                playTrackBtn.disabled = !hasPreview;
                playTrackBtn.textContent = hasPreview ? 'Play selected song' : 'Preview unavailable';

                if (gameState.track && currentTrackId !== gameState.track.id) {
                    previewFailed = false;
                }

                if (gameState.status === 'active' && gameState.track && !previewFailed) {
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
                scoresDiv.innerHTML = 'Scores: ';
                Object.entries(gameState.scores || {}).forEach(([id, score]) => {
                    const player = (gameState.players || []).find(p => p.id === id);
                    if (player) {
                        scoresDiv.innerHTML += `${player.name}: ${score} `;
                    }
                });

                const canGuess = gameState.status === 'active' && !gameState.my_guess;
                document.querySelectorAll('.player-btn').forEach(btn => {
                    btn.disabled = !canGuess;
                });

                const startBtn = document.getElementById('startBtn');
                const nextBtn = document.getElementById('nextBtn');
                const endGameBtn = document.getElementById('endGameBtn');
                const isHost = Boolean(gameState.is_host);

                startBtn.style.display = isHost && gameState.status === 'idle' ? 'inline-block' : 'none';
                startBtn.disabled = !isHost || gameState.status !== 'idle';

                nextBtn.style.display = isHost && gameState.status === 'revealed' ? 'inline-block' : 'none';
                nextBtn.disabled = !isHost || gameState.status !== 'revealed';

                endGameBtn.style.display = isHost && ['active', 'revealed'].includes(gameState.status) ? 'inline-block' : 'none';
                endGameBtn.disabled = !isHost || !['active', 'revealed'].includes(gameState.status);

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

                updateRoomDisplay();
            }

            function showFeedback(correct) {
                const feedback = document.getElementById('feedback');
                feedback.textContent = correct ? 'Correct!' : 'Wrong!';
                feedback.style.color = correct ? '#7ABEFF' : '#FF6B6B';
            }

            document.getElementById('startBtn').onclick = startGame;
            document.getElementById('nextBtn').onclick = nextRound;
            document.getElementById('endGameBtn').onclick = endGame;
            document.getElementById('playTrackBtn').onclick = () => {
                previewFailed = false;
                playPreview(gameState.track, true);
            };

            render();
            fetchState();
            setInterval(fetchState, 3000);
        </script>
    <?php endif; ?>
</body>
</html>