<?php
require_once __DIR__ . '/functions.php';

startSession();
requireLogin();

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
    } elseif (($roomData['game']['status'] ?? 'idle') !== 'ended') {
        $roomError = 'The game has not ended yet.';
    } else {
        $_SESSION['current_room'] = $roomCode;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>StreamWho - Leaderboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="mobile.css">
</head>
<body class="leaderboard-page">
    <div class="container leaderboard-container">
        <?php if ($roomError): ?>
            <div class="leaderboard-header">
                <p class="leaderboard-subtitle">Final results</p>
                <h1>Leaderboard</h1>
            </div>
            <div class="card leaderboard-card">
                <p class="leaderboard-empty"><?= htmlspecialchars($roomError) ?></p>
                <div class="controls" style="justify-content: center; margin-top: 2vh;">
                    <a class="button control" href="joinLobby.php">Back to Join Lobby</a>
                </div>
            </div>
        <?php else: ?>
            <div class="leaderboard-header">
                <p class="leaderboard-subtitle">Final results for room</p>
                <h1><?= htmlspecialchars($roomCode) ?></h1>
            </div>

            <div class="card leaderboard-card">
                <h2 class="leaderboard-title">Final Leaderboard</h2>
                <?php
                $scores = is_array($roomData['game']['scores'] ?? null) ? $roomData['game']['scores'] : [];
                $rows = [];

                foreach ($roomData['players'] ?? [] as $player) {
                    $rows[] = [
                        'id' => $player['id'] ?? '',
                        'name' => $player['name'] ?? 'Unknown player',
                        'image' => $player['image'] ?? '',
                        'score' => (int) ($scores[$player['id']] ?? 0),
                    ];
                }

                usort($rows, static function ($left, $right): int {
                    if ($left['score'] === $right['score']) {
                        return strcmp($left['name'], $right['name']);
                    }

                    return $right['score'] <=> $left['score'];
                });
                ?>

                <?php if ($rows): ?>
                    <ol class="leaderboard-list">
                        <?php foreach ($rows as $index => $player): ?>
                            <li class="leaderboard-item">
                                <span class="leaderboard-rank"><?= $index + 1 ?></span>
                                <?php if (!empty($player['image'])): ?>
                                    <img class="leaderboard-avatar" src="<?= htmlspecialchars($player['image']) ?>" alt="<?= htmlspecialchars($player['name']) ?>">
                                <?php else: ?>
                                    <span class="leaderboard-avatar leaderboard-avatar-placeholder" aria-hidden="true"></span>
                                <?php endif; ?>
                                <div class="leaderboard-player">
                                    <strong><?= htmlspecialchars($player['name']) ?></strong>
                                    <span><?= $player['score'] ?> point<?= $player['score'] === 1 ? '' : 's' ?></span>
                                </div>
                                <span class="leaderboard-score"><?= $player['score'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <p class="leaderboard-empty">No scores were recorded for this room.</p>
                <?php endif; ?>

                <div class="controls" style="justify-content: center; margin-top: 2vh;">
                    <a class="button control" href="lobby.php">Back to Join Lobby</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
