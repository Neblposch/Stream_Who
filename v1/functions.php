<?php


function startSession(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function generateTemporaryUsername(): string
{
    return 'Player' . random_int(1000, 9999);
}

function sanitizeUsername(string $username): string
{
    $username = trim($username);
    $username = preg_replace('/[^A-Za-z0-9_-]/', '', $username);
    return $username === '' ? generateTemporaryUsername() : $username;
}

function normalizeRoomCode(string $roomCode): string
{
    $roomCode = strtoupper(trim($roomCode));
    return preg_replace('/[^A-Z0-9]/', '', $roomCode);
}

function roomsFilePath(): string
{
    return __DIR__ . '/rooms.json';
}

function loadRooms(): array
{
    $path = roomsFilePath();

    if (!file_exists($path)) {
        file_put_contents($path, json_encode([], JSON_PRETTY_PRINT));
    }

    $fp = fopen($path, 'r');
    if (!$fp) {
        return [];
    }

    flock($fp, LOCK_SH);
    $contents = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    $rooms = json_decode($contents, true);
    return is_array($rooms) ? $rooms : [];
}

function saveRooms(array $rooms): bool
{
    $path = roomsFilePath();
    $fp = fopen($path, 'c+');
    if (!$fp) {
        return false;
    }

    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    $written = fwrite($fp, json_encode($rooms, JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    return $written !== false;
}

function roomExists(string $roomCode): bool
{
    $roomCode = normalizeRoomCode($roomCode);
    $rooms = loadRooms();
    return isset($rooms[$roomCode]);
}

function generateRoomCode(int $length = 6): string
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        $code = normalizeRoomCode($code);
    } while (roomExists($code));

    return $code;
}

function createRoom(string $username): string
{
    $username = sanitizeUsername($username);
    $rooms = loadRooms();

    $roomCode = generateRoomCode();
    $rooms[$roomCode] = [
        'host' => $username,
        'players' => [$username],
        'created_at' => time(),
    ];

    if (!saveRooms($rooms)) {
        throw new RuntimeException('Unable to save room.');
    }

    return $roomCode;
}

function joinRoom(string $roomCode, string $username): void
{
    $roomCode = normalizeRoomCode($roomCode);
    $username = sanitizeUsername($username);

    $rooms = loadRooms();
    if (!isset($rooms[$roomCode])) {
        throw new InvalidArgumentException('Room not found.');
    }

    if (in_array($username, $rooms[$roomCode]['players'], true)) {
        throw new InvalidArgumentException('Username already exists in that room.');
    }

    $rooms[$roomCode]['players'][] = $username;

    if (!saveRooms($rooms)) {
        throw new RuntimeException('Unable to save room.');
    }
}

function getRoom(string $roomCode): ?array
{
    $roomCode = normalizeRoomCode($roomCode);
    $rooms = loadRooms();
    return $rooms[$roomCode] ?? null;
}
?>