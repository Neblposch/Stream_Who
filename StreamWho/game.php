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
    let stateUpdateInterval = null;

    function stopPreview() {
        if (currentAudio) {
            currentAudio.pause();
            currentAudio.currentTime = 0;
            currentAudio = null;
        }
    }

    function playPreview(track, force = false) {
        // Only play preview if game is active
        if (gameState.status !== 'active') {
            stopPreview();
            return;
        }
        
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
            const response = await fetch('game_logic.php?action=state&room=' + encodeURIComponent(roomCode));
            const data = await response.json();
            if (data && data.success !== false) {
                const previousStatus = gameState.status;
                gameState = { ...gameState, ...data };
                
                // Stop preview if game is no longer active
                if (gameState.status !== 'active') {
                    stopPreview();
                }
            }
            render();
        } catch (error) {
            console.error('Unable to refresh game state', error);
        }
    }

    async function startGame() {
        if (!gameState.is_host) {
            console.log('Not host, cannot start game');
            return;
        }
        
        if (gameState.status !== 'idle' && gameState.status !== 'ended') {
            console.log('Game already started or ended');
            return;
        }

        try {
            const response = await fetch('game_logic.php?action=start&room=' + encodeURIComponent(roomCode), { method: 'POST' });
            const data = await response.json();
            if (data && data.success !== false) {
                gameState = { ...gameState, ...data };
                render();
                // Start the first round after a short delay
                setTimeout(() => {
                    nextRound();
                }, 500);
            } else {
                console.error('Failed to start game:', data.message);
                document.getElementById('feedback').textContent = 'Failed to start game: ' + (data.message || 'Unknown error');
            }
        } catch (error) {
            console.error('Error starting game:', error);
            document.getElementById('feedback').textContent = 'Error starting game. Please try again.';
        }
    }

    async function nextRound() {
        if (!gameState.is_host) {
            return;
        }

        if (gameState.status === 'ended') {
            return;
        }

        try {
            const response = await fetch('game_logic.php?action=next_round&room=' + encodeURIComponent(roomCode), { method: 'POST' });
            const data = await response.json();
            if (data && data.success !== false) {
                gameState = { ...gameState, ...data };
                // Reset preview flag for new track
                previewFailed = false;
                // Clear any existing feedback message
                if (gameState.status === 'active') {
                    document.getElementById('feedback').style.color = '';
                }
            } else {
                console.error('Failed to start next round:', data.message);
                document.getElementById('feedback').textContent = 'Failed to start round: ' + (data.message || 'Unknown error');
            }
            render();
        } catch (error) {
            console.error('Error starting next round:', error);
            document.getElementById('feedback').textContent = 'Error starting next round. Please try again.';
        }
    }

    async function endGame() {
        if (!gameState.is_host || gameState.status === 'ended') {
            return;
        }

        try {
            const response = await fetch('game_logic.php?action=end_game&room=' + encodeURIComponent(roomCode), { method: 'POST' });
            const data = await response.json();
            if (data && data.success !== false) {
                gameState = { ...gameState, ...data };
                stopPreview();
                window.location.href = 'leaderboard.php?room=' + encodeURIComponent(roomCode);
                return;
            }
            render();
        } catch (error) {
            console.error('Error ending game:', error);
        }
    }

    async function submitGuess(playerId) {
        if (gameState.status !== 'active' || gameState.my_guess) {
            return;
        }

        const formData = new FormData();
        formData.append('guess', playerId);
        try {
            const response = await fetch('game_logic.php?action=guess&room=' + encodeURIComponent(roomCode), { method: 'POST', body: formData });
            const data = await response.json();
            if (data && data.success !== false) {
                gameState = { ...gameState, ...data };
                showFeedback(data.correct);
            }
            render();
        } catch (error) {
            console.error('Error submitting guess:', error);
        }
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
            
            if (!hasScrolledToGame && (gameState.status === 'active' || gameState.status === 'revealed')) {
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

        if (cover && gameState.status === 'active') {
            coverImg.src = cover;
            coverImg.alt = gameState.track?.title ? `${gameState.track.title} cover` : 'Track Cover';
            document.body.style.backgroundImage = `url(${cover})`;
        } else {
            coverImg.src = 'test.jpg';
            coverImg.alt = 'Track Cover';
            document.body.style.backgroundImage = 'none';
        }
        
        document.getElementById('trackTitle').textContent = gameState.track?.title || 'No track loaded';
        document.getElementById('trackArtist').textContent = gameState.track?.artist || '';

        const hasPreview = Boolean(gameState.track?.preview_url) && gameState.status === 'active';
        playTrackBtn.disabled = !hasPreview;
        playTrackBtn.textContent = hasPreview ? 'Play selected song' : (gameState.status === 'active' ? 'Preview unavailable' : 'Wait for game to start');

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
            btn.disabled = (gameState.status !== 'active' || gameState.my_guess !== null);
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

        const startBtn = document.getElementById('startBtn');
        const nextBtn = document.getElementById('nextBtn');
        const endGameBtn = document.getElementById('endGameBtn');
        const isHost = Boolean(gameState.is_host);

        // Show start button only when game is idle (not started)
        startBtn.style.display = isHost && gameState.status === 'idle' ? 'inline-block' : 'none';
        
        // Show next button when game is revealed or waiting to start
        nextBtn.style.display = isHost && (gameState.status === 'revealed' || gameState.status === 'waiting_to_start') ? 'inline-block' : 'none';
        
        // Enable next button text
        if (nextBtn.style.display === 'inline-block') {
            nextBtn.textContent = gameState.status === 'waiting_to_start' ? 'Start First Round' : 'Next Round';
        }
        
        // Show end game button during active gameplay
        endGameBtn.style.display = isHost && (gameState.status === 'active' || gameState.status === 'revealed') ? 'inline-block' : 'none';

        if (gameState.status === 'active') {
            document.getElementById('feedback').textContent = gameState.my_guess ? 'Guess submitted. Waiting for others...' : 'Guess which player listened to this track the most!';
            if (!gameState.my_guess) {
                document.getElementById('feedback').style.color = '';
            }
        } else if (gameState.status === 'revealed') {
            const correctPlayer = (gameState.players || []).find(player => player.id === gameState.correct_player_id);
            document.getElementById('feedback').textContent = correctPlayer ? `Round complete! ${correctPlayer.name} listened to this track the most.` : 'Round complete.';
            document.getElementById('feedback').style.color = '#7ABEFF';
        } else if (gameState.status === 'waiting_to_start') {
            document.getElementById('feedback').textContent = 'Game is ready! Click "Start First Round" to begin.';
            document.getElementById('feedback').style.color = '#7ABEFF';
        } else {
            document.getElementById('feedback').textContent = gameState.is_host ? 'Click "Start Game" to begin!' : 'Waiting for the host to start the game...';
            document.getElementById('feedback').style.color = '';
        }

        updateRoomDisplay();
    }

    function showFeedback(correct) {
        const feedback = document.getElementById('feedback');
        feedback.textContent = correct ? '✓ Correct! +1 point' : '✗ Wrong guess!';
        feedback.style.color = correct ? '#7ABEFF' : '#FF6B6B';
        setTimeout(() => {
            if (gameState.status === 'active') {
                feedback.textContent = 'Guess submitted. Waiting for others...';
                feedback.style.color = '';
            }
        }, 2000);
    }

    // Set up event listeners
    const startBtnElement = document.getElementById('startBtn');
    const nextBtnElement = document.getElementById('nextBtn');
    const endGameBtnElement = document.getElementById('endGameBtn');
    const playTrackBtnElement = document.getElementById('playTrackBtn');
    
    if (startBtnElement) {
        startBtnElement.onclick = startGame;
    }
    if (nextBtnElement) {
        nextBtnElement.onclick = nextRound;
    }
    if (endGameBtnElement) {
        endGameBtnElement.onclick = endGame;
    }
    if (playTrackBtnElement) {
        playTrackBtnElement.onclick = () => {
            previewFailed = false;
            playPreview(gameState.track, true);
        };
    }

    // Initial render and start polling
    render();
    fetchState();
    // Poll every 2 seconds for state updates
    stateUpdateInterval = setInterval(fetchState, 2000);
    
    // Clean up interval on page unload
    window.addEventListener('beforeunload', () => {
        if (stateUpdateInterval) {
            clearInterval(stateUpdateInterval);
        }
        stopPreview();
    });
</script>
    <?php endif; ?>
</body>
</html>