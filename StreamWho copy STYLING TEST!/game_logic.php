<?php
session_start();
require_once __DIR__ . '/functions.php';

const ROUND_DURATION = 20;

function buildTrackPayload(array $track): array {
    return [
        'id' => $track['id'] ?? '',
        'title' => $track['name'] ?? 'Unknown track',
        'artist' => $track['artist'] ?? 'Unknown artist',
        'cover' => $track['cover'] ?? '',
        'preview_url' => $track['preview_url'] ?? '',
        'playcount' => (int)($track['playcount'] ?? 0),
    ];
}

function normalizePlayerList(array $players): array {
    return array_map(static function (array $player): array {
        return [
            'id' => $player['id'],
            'name' => $player['name'],
            'image' => $player['image'] ?? null,
        ];
    }, $players);
}

function startNewRound(array $room): array {
    $players = $room['players'];
    $game = $room['game'];
    
    // Zufälligen Spieler als Source auswählen
    $sourcePlayer = $players[array_rand($players)];
    
    // Dummy-Tracks für den Spieler
    $playerTracks = getDummyTracksForPlayer($sourcePlayer['id']);
    $track = $playerTracks[array_rand($playerTracks)];
    
    $correctPlayerId = $sourcePlayer['id'];
    
    $game['status'] = 'active';
    $game['round_number'] = ((int)($game['round_number'] ?? 0)) + 1;
    $game['round_id'] = bin2hex(random_bytes(8));
    $game['source_player_id'] = $sourcePlayer['id'];
    $game['correct_player_id'] = $correctPlayerId;
    $game['track'] = buildTrackPayload($track);
    $game['guesses'] = [];
    $game['round_started_at'] = time();
    $game['expires_at'] = time() + ROUND_DURATION;
    $game['revealed_at'] = null;
    $game['used_track_ids'] = array_merge($game['used_track_ids'] ?? [], [$track['id']]);
    $game['used_source_player_ids'] = array_merge($game['used_source_player_ids'] ?? [], [$sourcePlayer['id']]);
    
    $room['game'] = $game;
    return $room;
}

function finalizeRoundIfNeeded(array &$room): void {
    $game = $room['game'] ?? [];
    if (($game['status'] ?? 'idle') !== 'active') return;
    
    $expiresAt = (int)($game['expires_at'] ?? 0);
    if ($expiresAt > time()) return;
    
    $game['status'] = 'revealed';
    $game['revealed_at'] = time();
    
    foreach ($room['players'] as $player) {
        if (!isset($game['scores'][$player['id']])) {
            $game['scores'][$player['id']] = 0;
        }
    }
    
    $correctPlayerId = $game['correct_player_id'] ?? null;
    foreach ($game['guesses'] as $playerId => $guess) {
        if ($guess === $correctPlayerId && isset($game['scores'][$playerId])) {
            $game['scores'][$playerId] = (int)$game['scores'][$playerId] + 1;
        }
    }
    
    $room['game'] = $game;
}

function buildGameResponse(array $room, string $userId): array {
    $game = $room['game'] ?? defaultGameState();
    $track = is_array($game['track'] ?? null) ? buildTrackPayload($game['track']) : null;
    
    return [
        'success' => true,
        'round' => (int)($game['round_number'] ?? 0),
        'status' => $game['status'] ?? 'idle',
        'round_id' => $game['round_id'] ?? null,
        'track' => $track,
        'players' => normalizePlayerList($room['players'] ?? []),
        'scores' => is_array($game['scores'] ?? null) ? $game['scores'] : [],
        'my_guess' => $game['guesses'][$userId] ?? null,
        'time_left' => $game['expires_at'] ? max(0, (int)$game['expires_at'] - time()) : 0,
        'expires_at' => $game['expires_at'] ?? null,
        'correct_player_id' => $game['correct_player_id'] ?? null,
        'source_player_id' => $game['source_player_id'] ?? null,
        'is_host' => ($room['host_id'] ?? null) === $userId,
    ];
}

header('Content-Type: application/json');

$roomCode = $_SESSION['current_room'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

if (!$roomCode || !$userId) {
    echo json_encode(['success' => false, 'message' => 'No active room']);
    exit;
}

$room = getRoom($roomCode);
if (!$room || !isUserInRoom($roomCode)) {
    echo json_encode(['success' => false, 'message' => 'Room not found']);
    exit;
}

$action = $_REQUEST['action'] ?? 'state';

if ($action === 'start' || $action === 'next_round') {
    if (($room['host_id'] ?? null) !== $userId) {
        echo json_encode(['success' => false, 'message' => 'Only the host can control the round.']);
        exit;
    }
    
    if (($room['game']['status'] ?? 'idle') !== 'active') {
        try {
            $room = startNewRound($room);
            saveRoom($room, $roomCode);
        } catch (Exception $exception) {
            echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
            exit;
        }
    }
    
    echo json_encode(buildGameResponse($room, $userId));
    exit;
}

if ($action === 'state') {
    finalizeRoundIfNeeded($room);
    saveRoom($room, $roomCode);
    echo json_encode(buildGameResponse($room, $userId));
    exit;
}

if ($action === 'guess') {
    $guess = $_POST['guess'] ?? '';
    $game = $room['game'] ?? defaultGameState();
    
    if (($game['status'] ?? 'idle') !== 'active') {
        echo json_encode(array_merge(buildGameResponse($room, $userId), [
            'accepted' => false,
            'duplicate' => false,
            'correct' => false,
            'message' => 'Round is not accepting guesses right now.',
        ]));
        exit;
    }
    
    $validPlayers = array_column($room['players'] ?? [], 'id');
    if (!in_array($guess, $validPlayers, true)) {
        echo json_encode(array_merge(buildGameResponse($room, $userId), [
            'accepted' => false,
            'duplicate' => false,
            'correct' => false,
            'message' => 'That player is not in the room.',
        ]));
        exit;
    }
    
    $duplicate = isset($game['guesses'][$userId]);
    if (!$duplicate) {
        $game['guesses'][$userId] = $guess;
        if (count($game['guesses']) >= count($room['players'] ?? [])) {
            $game['expires_at'] = time();
        }
    }
    
    $room['game'] = $game;
    finalizeRoundIfNeeded($room);
    saveRoom($room, $roomCode);
    
    $response = buildGameResponse($room, $userId);
    $response['accepted'] = true;
    $response['duplicate'] = $duplicate;
    $response['correct'] = ($game['guesses'][$userId] ?? null) === ($game['correct_player_id'] ?? null);
    $response['message'] = $duplicate ? 'Your guess was already recorded.' : 'Guess recorded.';
    
    echo json_encode($response);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
?>