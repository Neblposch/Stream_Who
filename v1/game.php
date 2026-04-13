<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StreamWho</title>
    <link rel="stylesheet" href="style.css">
</head>
<body id="gameBody" class="background">
    <div id="overlay" class="overlay">
        <div id="gameCard" class="card">
            <img id="trackCover" class="cover" src="" alt="Track Cover">
            <div id="trackTitle" class="title"></div>
            <div id="trackArtist" class="artist"></div>
            <div id="players" class="players"></div>
            <div id="gameInfo" class="info">
                <div id="round" class="round"></div>
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
        let gameState = {};

        async function fetchState() {
            const response = await fetch('game_logic.php?action=state');
            const data = await response.json();
            gameState = data;
            render();
        }

        async function startGame() {
            const response = await fetch('game_logic.php?action=start', { method: 'POST' });
            const data = await response.json();
            gameState = data;
            render();
        }

        async function nextRound() {
            const response = await fetch('game_logic.php?action=next_round', { method: 'POST' });
            const data = await response.json();
            gameState = data;
            render();
        }

        async function submitGuess(playerId) {
            const formData = new FormData();
            formData.append('guess', playerId);
            const response = await fetch('game_logic.php?action=guess', { method: 'POST', body: formData });
            const data = await response.json();
            gameState.scores = data.scores;
            showFeedback(data.correct);
            disableButtons();
        }

        function render() {
            if (gameState.track) {
                document.body.style.backgroundImage = `url(${gameState.track.cover_url})`;
                document.getElementById('trackCover').src = gameState.track.cover_url;
                document.getElementById('trackTitle').textContent = gameState.track.title;
                document.getElementById('trackArtist').textContent = gameState.track.artist;
            } else {
                document.body.style.backgroundImage = 'none';
                document.getElementById('trackCover').src = '';
                document.getElementById('trackTitle').textContent = 'No track loaded';
                document.getElementById('trackArtist').textContent = '';
            }

            const playersDiv = document.getElementById('players');
            playersDiv.innerHTML = '';
            gameState.players.forEach(player => {
                const btn = document.createElement('button');
                btn.className = 'button player-btn';
                btn.textContent = player.name;
                btn.onclick = () => submitGuess(player.id);
                playersDiv.appendChild(btn);
            });

            document.getElementById('round').textContent = `Round: ${gameState.round}`;

            const scoresDiv = document.getElementById('scores');
            scoresDiv.innerHTML = 'Scores: ';
            Object.entries(gameState.scores).forEach(([id, score]) => {
                const player = gameState.players.find(p => p.id === id);
                scoresDiv.innerHTML += `${player.name}: ${score} `;
            });

            document.getElementById('feedback').textContent = '';
            enableButtons();
        }

        function showFeedback(correct) {
            const feedback = document.getElementById('feedback');
            feedback.textContent = correct ? 'Correct!' : 'Wrong!';
            feedback.style.color = correct ? 'green' : 'red';
        }

        function disableButtons() {
            document.querySelectorAll('.player-btn').forEach(btn => btn.disabled = true);
        }

        function enableButtons() {
            document.querySelectorAll('.player-btn').forEach(btn => btn.disabled = false);
        }

        document.getElementById('startBtn').onclick = startGame;
        document.getElementById('nextBtn').onclick = nextRound;

        // Load initial state on page load
        fetchState();
    </script>
</body>
</html>