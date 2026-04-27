<?php

function startSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function normalizeRoomCode(string $roomCode): string
{
    $roomCode = strtoupper(trim($roomCode));
    return preg_replace('/[^A-Z0-9]/', '', $roomCode);
}

/**
 * Generate a unique room code
 */
function generateRoomCode(int $length = 6): string
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return normalizeRoomCode($code);
}

/**
 * Get all active rooms from session storage
 */
function getAllRooms(): array
{
    if (!isset($_SESSION['all_rooms'])) {
        $_SESSION['all_rooms'] = [];
    }
    return $_SESSION['all_rooms'];
}

/**
 * Create a new room (requires Spotify authentication)
 */
function createRoom(): string
{
    if (empty($_SESSION['access_token'])) {
        throw new RuntimeException('Spotify authentication required.');
    }

    $roomCode = generateRoomCode();
    $rooms = getAllRooms();
    
    // Ensure room code is unique
    while (isset($rooms[$roomCode])) {
        $roomCode = generateRoomCode();
    }
    
    $spotifyUser = $_SESSION['spotify_user'] ?? [];
    $rooms[$roomCode] = [
        'host_id' => $spotifyUser['id'] ?? 'unknown',
        'players' => [
            [
                'id' => $spotifyUser['id'] ?? 'unknown',
                'name' => $spotifyUser['display_name'] ?? 'Unknown Player',
                'image' => $spotifyUser['images'][0]['url'] ?? null,
            ]
        ],
        'created_at' => time(),
    ];
    
    $_SESSION['all_rooms'] = $rooms;
    return $roomCode;
}

/**
 * Join a room (requires Spotify authentication)
 */
function joinRoom(string $roomCode): bool
{
    if (empty($_SESSION['access_token'])) {
        throw new RuntimeException('Spotify authentication required.');
    }

    $roomCode = normalizeRoomCode($roomCode);
    $rooms = getAllRooms();
    
    if (!isset($rooms[$roomCode])) {
        throw new InvalidArgumentException('Room not found.');
    }
    
    $spotifyUser = $_SESSION['spotify_user'] ?? [];
    $userId = $spotifyUser['id'] ?? 'unknown';
    $userName = $spotifyUser['display_name'] ?? 'Unknown Player';
    
    // Check if player already in room
    foreach ($rooms[$roomCode]['players'] as $player) {
        if ($player['id'] === $userId) {
            return true; // Already in room
        }
    }
    
    // Add player to room
    $rooms[$roomCode]['players'][] = [
        'id' => $userId,
        'name' => $userName,
        'image' => $spotifyUser['images'][0]['url'] ?? null,
    ];
    
    $_SESSION['all_rooms'] = $rooms;
    return true;
}

/**
 * Get room details
 */
function getRoom(string $roomCode): ?array
{
    $roomCode = normalizeRoomCode($roomCode);
    $rooms = getAllRooms();
    return $rooms[$roomCode] ?? null;
}

/**
 * Check if user is in a room
 */
function isUserInRoom(string $roomCode): bool
{
    $roomCode = normalizeRoomCode($roomCode);
    $room = getRoom($roomCode);
    
    if (!$room) {
        return false;
    }
    
    $spotifyUser = $_SESSION['spotify_user'] ?? [];
    $userId = $spotifyUser['id'] ?? null;
    
    if (!$userId) {
        return false;
    }
    
    foreach ($room['players'] as $player) {
        if ($player['id'] === $userId) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if user is logged in (requires Spotify auth)
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['access_token']);
}

/**
 * Require Spotify login
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: spotify_login.php');
        exit;
    }
}

?>
