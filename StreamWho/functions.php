<?php
// File: `functions.php`
// Improved file-backed room storage with robust writes (rename fallback for Windows)
// Throws clear exceptions on failure.

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

function generateRoomCode(int $length = 6): string
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return normalizeRoomCode($code);
}

function getRoomsFilePath(): string
{
    return __DIR__ . '/rooms.json';
}

function getAllRooms(): array
{
    $file = getRoomsFilePath();

    if (!file_exists($file)) {
        $init = json_encode([], JSON_PRETTY_PRINT);
        if ($init === false) {
            throw new RuntimeException('Failed to encode initial rooms data.');
        }
        $bytes = file_put_contents($file, $init, LOCK_EX);
        if ($bytes === false) {
            throw new RuntimeException('Unable to initialize rooms file. Check permissions for ' . $file);
        }
        return [];
    }

    $contents = file_get_contents($file);
    if ($contents === false) {
        throw new RuntimeException('Unable to read rooms file: ' . $file);
    }

    $data = json_decode($contents, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        // invalid json -> treat as empty to avoid fatal errors
        return [];
    }

    if (!is_array($data)) {
        return [];
    }

    return $data;
}

function saveAllRooms(array $rooms): void
{
    $file = getRoomsFilePath();
    $dir = dirname($file);

    $json = json_encode($rooms, JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new RuntimeException('Failed to encode rooms data: ' . json_last_error_msg());
    }

    // create a temporary file in the same directory
    $tmp = tempnam($dir, 'rooms_');
    if ($tmp === false) {
        throw new RuntimeException('Unable to create temporary file for rooms in ' . $dir);
    }

    // write to temp with exclusive lock
    $written = file_put_contents($tmp, $json, LOCK_EX);
    if ($written === false) {
        @unlink($tmp);
        throw new RuntimeException('Unable to write temporary rooms file: ' . $tmp);
    }

    // Try atomic replace
    if (!@rename($tmp, $file)) {
        // rename failed (common on Windows if target is locked) -> try copy fallback
        if (!@copy($tmp, $file)) {
            @unlink($tmp);
            throw new RuntimeException('Unable to replace rooms file (rename and copy failed). Check permissions for ' . $file);
        }
        // copy succeeded -> remove temp
        @unlink($tmp);
    }

    // Optional: ensure file is non-empty
    clearstatcache(true, $file);
    if (filesize($file) === 0) {
        throw new RuntimeException('Rooms file is empty after write. Check filesystem/permissions for ' . $file);
    }
}

function createRoom(): string
{
    if (empty($_SESSION['access_token'])) {
        throw new RuntimeException('Spotify authentication required.');
    }

    $rooms = getAllRooms();
    $roomCode = generateRoomCode();

    while (isset($rooms[$roomCode])) {
        $roomCode = generateRoomCode();
    }

    $spotifyUser = $_SESSION['spotify_user'] ?? [];
    $userId = $spotifyUser['id'] ?? 'unknown';
    $userName = $spotifyUser['display_name'] ?? 'Unknown Player';
    $userImage = $spotifyUser['images'][0]['url'] ?? null;

    $rooms[$roomCode] = [
        'host_id' => $userId,
        'players' => [
            [
                'id' => $userId,
                'name' => $userName,
                'image' => $userImage,
            ]
        ],
        'created_at' => time(),
    ];

    saveAllRooms($rooms);
    return $roomCode;
}

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
    $userImage = $spotifyUser['images'][0]['url'] ?? null;

    foreach ($rooms[$roomCode]['players'] as $player) {
        if (($player['id'] ?? null) === $userId) {
            return true;
        }
    }

    $rooms[$roomCode]['players'][] = [
        'id' => $userId,
        'name' => $userName,
        'image' => $userImage,
    ];

    saveAllRooms($rooms);
    return true;
}

function getRoom(string $roomCode): ?array
{
    $roomCode = normalizeRoomCode($roomCode);
    $rooms = getAllRooms();
    return $rooms[$roomCode] ?? null;
}

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
        if (($player['id'] ?? null) === $userId) {
            return true;
        }
    }

    return false;
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['access_token']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: spotify_login.php');
        exit;
    }
}
?>
