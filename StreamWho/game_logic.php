<?php
session_start();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/spotify_helper.php';

const ROUND_DURATION = 20;

function buildTrackPayload(array $track): array
{
    return [
        'id' => $track['id'] ?? '',
        'title' => $track['name'] ?? 'Unknown track',
        'artist' => $track['artist'] ?? 'Unknown artist',
        'cover' => $track['cover'] ?? '',
        'preview_url' => $track['preview_url'] ?? '',
        'playcount' => (int)($track['playcount'] ?? 0),
    ];
}

function normalizePlayerList(array $players): array
{
    return array_map(static function (array $player): array {
        return [
            'id' => $player['id'],
            'name' => $player['name'],
            'image' => $player['image'] ?? null,
        ];
    }, $players);
}

function getPlayerTopTracks(string $userId, int $limit = 50, string $timeRange = 'medium_term'): array
{
    $url = "https://api.spotify.com/v1/me/top/tracks?limit={$limit}&time_range={$timeRange}";
    $data = fetchSpotify($url, $userId);

    if (isset($data['error']) || !is_array($data['items'] ?? null)) {
        return [];
    }

    $tracks = [];
    foreach ($data['items'] as $track) {
        $tracks[] = [
            'id' => $track['id'] ?? '',
            'name' => $track['name'] ?? 'Unknown track',
            'artist' => $track['artists'][0]['name'] ?? 'Unknown artist',
            'cover' => $track['album']['images'][0]['url'] ?? '',
            'preview_url' => $track['preview_url'] ?? '',
            'playcount' => (int)($track['popularity'] ?? 0),
        ];
    }

    return $tracks;
}

function loadPlayersWithTracks(array $players): array
{
    $playersWithTracks = [];

    foreach ($players as $player) {
        $playersWithTracks[] = [
            'player' => $player,
            'tracks' => getPlayerTopTracks($player['id']),
        ];
    }

    return $playersWithTracks;
}

function chooseSourcePlayer(array $playersWithTracks, array $usedSourcePlayerIds): array
{
    $eligible = array_values(array_filter($playersWithTracks, static function (array $entry) use ($usedSourcePlayerIds): bool {
        $playerId = $entry['player']['id'] ?? null;
        return is_string($playerId) && !in_array($playerId, $usedSourcePlayerIds, true) && !empty($entry['tracks']);
    }));

    if (empty($eligible)) {
        $eligible = array_values(array_filter($playersWithTracks, static function (array $entry): bool {
            return !empty($entry['tracks']);
        }));
    }

    if (empty($eligible)) {
        return [];
    }

    shuffle($eligible);
    return $eligible[0];
}

function selectRoundTrack(array $sourcePlayer, array $usedTrackIds): ?array
{
    $eligible = array_values(array_filter($sourcePlayer['tracks'], static function (array $track) use ($usedTrackIds): bool {
        return !in_array($track['id'] ?? '', $usedTrackIds, true);
    }));

    if (!empty($eligible)) {
        $random = $eligible[array_rand($eligible)];
        return $random;
    }

    if (!empty($sourcePlayer['tracks'])) {
        return $sourcePlayer['tracks'][array_rand($sourcePlayer['tracks'])];
    }

    return null;
}

function determineCorrectPlayer(string $sourcePlayerId, array $playersWithTracks, array $roundTrack): string
{
    $bestPlayerId = $sourcePlayerId;
    $bestPlaycount = -1;

    foreach ($playersWithTracks as $entry) {
        $playerId = $entry['player']['id'] ?? null;
        if (!is_string($playerId)) {
            continue;
        }

        foreach ($entry['tracks'] as $track) {
            if (($track['id'] ?? null) !== ($roundTrack['id'] ?? null)) {
                continue;
            }

            $playcount = (int)($track['playcount'] ?? 0);
            if ($playcount > $bestPlaycount || ($playcount === $bestPlaycount && $playerId === $sourcePlayerId)) {
                $bestPlaycount = $playcount;
                $bestPlayerId = $playerId;
            }
        }
    }

    return $bestPlayerId;
}

function getRoomContext(): ?array
{
    $roomCode = $_SESSION['current_room'] ?? null;
    if (!is_string($roomCode) || $roomCode === '') {
        return null;
    }

    $room = getRoom($roomCode);
    if (!$room) {
        return null;
    }

    $userId = $_SESSION['spotify_user']['id'] ?? null;
    if (!is_string($userId) || $userId === '' || !isUserInRoom($roomCode)) {
        return null;
    }

    return [
        'room' => $room,
        'roomCode' => $roomCode,
        'userId' => $userId,
    ];
}

function saveRoom(array $room): void
{
    $rooms = getAllRooms();
    $rooms[$room['code'] ?? ''] = $room;
    saveAllRooms($rooms);
}

function persistRoomState(array $room, string $roomCode): void
{
    $rooms = getAllRooms();
    $rooms[$roomCode] = $room;
    saveAllRooms($rooms);
}

function finalizeRoundIfNeeded(array &$room): void
{
    $game = $room['game'] ?? [];
    if (($game['status'] ?? 'idle') !== 'active') {
        return;
    }

    $expiresAt = (int)($game['expires_at'] ?? 0);
    if ($expiresAt > time()) {
        return;
    }

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

function startNewRound(array $room): array
{
    $playersWithTracks = loadPlayersWithTracks($room['players']);
    $sourceCandidate = chooseSourcePlayer($playersWithTracks, $room['game']['used_source_player_ids'] ?? []);

    if (empty($sourceCandidate)) {
        throw new RuntimeException('No player has enough Spotify data to start a round.');
    }

    $track = selectRoundTrack($sourceCandidate, $room['game']['used_track_ids'] ?? []);
    if (!$track) {
        throw new RuntimeException('No playable tracks are available for the selected player.');
    }

    $correctPlayerId = determineCorrectPlayer($sourceCandidate['player']['id'], $playersWithTracks, $track);
    $game = $room['game'] ?? defaultGameState();

    $game['status'] = 'active';
    $game['round_number'] = ((int)($game['round_number'] ?? 0)) + 1;
    $game['round_id'] = bin2hex(random_bytes(8));
    $game['source_player_id'] = $sourceCandidate['player']['id'];
    $game['correct_player_id'] = $correctPlayerId;
    $game['track'] = buildTrackPayload($track);
    $game['guesses'] = [];
    $game['round_started_at'] = time();
    $game['expires_at'] = time() + ROUND_DURATION;
    $game['revealed_at'] = null;
    $game['used_track_ids'] = array_values(array_unique(array_merge($game['used_track_ids'] ?? [], [$track['id']])));
    $game['used_source_player_ids'] = array_values(array_unique(array_merge($game['used_source_player_ids'] ?? [], [$sourceCandidate['player']['id']])));

    $room['game'] = $game;
    return $room;
}

function buildGameResponse(array $room, string $userId): array
{
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

$context = getRoomContext();
if (!$context) {
    echo json_encode(['success' => false, 'message' => 'Room not found or not available.']);
    exit;
}

$room = $context['room'];
$roomCode = $context['roomCode'];
$userId = $context['userId'];
$action = $_REQUEST['action'] ?? 'state';

if ($action === 'start' || $action === 'next_round') {
    if ($room['host_id'] !== $userId) {
        echo json_encode(['success' => false, 'message' => 'Only the host can control the round.']);
        exit;
    }

    if (($room['game']['status'] ?? 'idle') !== 'active') {
        try {
            $room = startNewRound($room);
            persistRoomState($room, $roomCode);
        } catch (RuntimeException | InvalidArgumentException $exception) {
            echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
            exit;
        }
    }

    echo json_encode(buildGameResponse($room, $userId));
    exit;
}

if ($action === 'state') {
    finalizeRoundIfNeeded($room);
    persistRoomState($room, $roomCode);

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
    persistRoomState($room, $roomCode);

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