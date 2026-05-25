<?php
function startSession(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function normalizeRoomCode(string $roomCode): string {
    $roomCode = strtoupper(trim($roomCode));
    return preg_replace('/[^A-Z0-9]/', '', $roomCode);
}

function generateRoomCode(int $length = 6): string {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return normalizeRoomCode($code);
}

function getRoomsFilePath(): string {
    return __DIR__ . '/rooms.json';
}

function getAllRooms(): array {
    $file = getRoomsFilePath();
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([]));
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function saveAllRooms(array $rooms): void {
    file_put_contents(getRoomsFilePath(), json_encode($rooms, JSON_PRETTY_PRINT));
}

function defaultGameState(): array {
    return [
        'status' => 'idle',
        'round_number' => 0,
        'round_id' => null,
        'source_player_id' => null,
        'correct_player_id' => null,
        'track' => null,
        'guesses' => [],
        'scores' => [],
        'used_track_ids' => [],
        'used_source_player_ids' => [],
        'round_started_at' => null,
        'expires_at' => null,
        'revealed_at' => null,
    ];
}

function getRoom(string $roomCode): ?array {
    $rooms = getAllRooms();
    return $rooms[$roomCode] ?? null;
}

function saveRoom(array $room, string $roomCode): void {
    $rooms = getAllRooms();
    $rooms[$roomCode] = $room;
    saveAllRooms($rooms);
}

function isUserInRoom(string $roomCode): bool {
    $room = getRoom($roomCode);
    if (!$room) return false;
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) return false;
    foreach ($room['players'] as $player) {
        if (($player['id'] ?? null) === $userId) return true;
    }
    return false;
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function createRoom(): string {
    $rooms = getAllRooms();
    $roomCode = generateRoomCode();
    while (isset($rooms[$roomCode])) {
        $roomCode = generateRoomCode();
    }
    
    $userId = $_SESSION['user_id'] ?? 'dummy_' . rand(1000, 9999);
    $userName = $_SESSION['user_name'] ?? 'Player_' . rand(100, 999);
    
    $rooms[$roomCode] = [
        'host_id' => $userId,
        'players' => [[
            'id' => $userId,
            'name' => $userName,
            'image' => null
        ]],
        'created_at' => time(),
        'game' => defaultGameState(),
    ];
    
    saveAllRooms($rooms);
    $_SESSION['current_room'] = $roomCode;
    return $roomCode;
}

function joinRoom(string $roomCode): bool {
    $roomCode = normalizeRoomCode($roomCode);
    $rooms = getAllRooms();
    
    if (!isset($rooms[$roomCode])) {
        throw new InvalidArgumentException('Room not found.');
    }
    
    $userId = $_SESSION['user_id'] ?? 'dummy_' . rand(1000, 9999);
    $userName = $_SESSION['user_name'] ?? 'Player_' . rand(100, 999);
    
    foreach ($rooms[$roomCode]['players'] as $player) {
        if (($player['id'] ?? null) === $userId) {
            return true;
        }
    }
    
    $rooms[$roomCode]['players'][] = [
        'id' => $userId,
        'name' => $userName,
        'image' => null,
    ];
    
    saveAllRooms($rooms);
    $_SESSION['current_room'] = $roomCode;
    return true;
}

// DUMMY TRACKS FUNCTION
function getDummyTracksForPlayer(string $playerId): array {
    $allTracks = [
        ['id' => 'track_001', 'name' => 'Blinding Lights', 'artist' => 'The Weeknd', 'cover' => '', 'preview_url' => '', 'playcount' => rand(0, 100)],
        ['id' => 'track_002', 'name' => 'Flowers', 'artist' => 'Miley Cyrus', 'cover' => '', 'preview_url' => '', 'playcount' => rand(0, 100)],
        ['id' => 'track_003', 'name' => 'As It Was', 'artist' => 'Harry Styles', 'cover' => '', 'preview_url' => '', 'playcount' => rand(0, 100)],
        ['id' => 'track_004', 'name' => 'Cruel Summer', 'artist' => 'Taylor Swift', 'cover' => '', 'preview_url' => '', 'playcount' => rand(0, 100)],
        ['id' => 'track_005', 'name' => 'Anti-Hero', 'artist' => 'Taylor Swift', 'cover' => '', 'preview_url' => '', 'playcount' => rand(0, 100)],
    ];
    return $allTracks;
}
?>