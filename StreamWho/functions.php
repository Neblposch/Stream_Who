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

function normalizePlayerData(mixed $player): array
{
    if (is_array($player)) {
        return [
            'id' => (string)($player['id'] ?? $player['name'] ?? 'unknown'),
            'name' => (string)($player['name'] ?? $player['id'] ?? 'Unknown Player'),
            'image' => $player['image'] ?? null,
        ];
    }

    return [
        'id' => (string)$player,
        'name' => (string)$player,
        'image' => null,
    ];
}

function defaultGameState(): array
{
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

function normalizeRoomData(array $room): array
{
    $players = [];
    foreach ($room['players'] ?? [] as $player) {
        $players[] = normalizePlayerData($player);
    }

    $hostId = (string)($room['host_id'] ?? $room['host'] ?? ($players[0]['id'] ?? 'unknown'));

    $game = is_array($room['game'] ?? null) ? $room['game'] : [];
    $game = array_replace(defaultGameState(), $game);

    if (!is_array($game['scores'] ?? null)) {
        $game['scores'] = [];
    }

    if (!is_array($game['guesses'] ?? null)) {
        $game['guesses'] = [];
    }

    if (!is_array($game['used_track_ids'] ?? null)) {
        $game['used_track_ids'] = [];
    }

    if (!is_array($game['used_source_player_ids'] ?? null)) {
        $game['used_source_player_ids'] = [];
    }

    foreach ($players as $player) {
        if (!array_key_exists($player['id'], $game['scores'])) {
            $game['scores'][$player['id']] = 0;
        }
    }

    $game['used_track_ids'] = array_values(array_unique(array_filter(array_map('strval', $game['used_track_ids']), static fn($value) => $value !== '')));
    $game['used_source_player_ids'] = array_values(array_unique(array_filter(array_map('strval', $game['used_source_player_ids']), static fn($value) => $value !== '')));

    return array_merge($room, [
        'host_id' => $hostId,
        'players' => $players,
        'created_at' => $room['created_at'] ?? time(),
        'game' => $game,
    ]);
}

function normalizeRooms(array $rooms): array
{
    foreach ($rooms as $code => $room) {
        if (!is_array($room)) {
            continue;
        }

        $rooms[$code] = normalizeRoomData($room);
    }

    return $rooms;
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
        return [];
    }

    if (!is_array($data)) {
        return [];
    }

    $normalized = normalizeRooms($data);
    if ($normalized !== $data) {
        saveAllRooms($normalized);
    }

    return $normalized;
}

function saveAllRooms(array $rooms): void
{
    $file = getRoomsFilePath();
    $dir = dirname($file);

    $json = json_encode($rooms, JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new RuntimeException('Failed to encode rooms data: ' . json_last_error_msg());
    }

    $tmp = tempnam($dir, 'rooms_');
    if ($tmp === false) {
        throw new RuntimeException('Unable to create temporary file for rooms in ' . $dir);
    }

    $written = file_put_contents($tmp, $json, LOCK_EX);
    if ($written === false) {
        @unlink($tmp);
        throw new RuntimeException('Unable to write temporary rooms file: ' . $tmp);
    }

    if (!@rename($tmp, $file)) {
        if (!@copy($tmp, $file)) {
            @unlink($tmp);
            throw new RuntimeException('Unable to replace rooms file (rename and copy failed). Check permissions for ' . $file);
        }
        @unlink($tmp);
    }

    clearstatcache(true, $file);
    if (filesize($file) === 0) {
        throw new RuntimeException('Rooms file is empty after write. Check filesystem/permissions for ' . $file);
    }
}

function getSpotifyAccountsFilePath(): string
{
    return __DIR__ . '/data/spotify_accounts.json';
}

function getSpotifyAccounts(): array
{
    $file = getSpotifyAccountsFilePath();
    if (!file_exists($file)) {
        return [];
    }

    $contents = file_get_contents($file);
    if ($contents === false) {
        throw new RuntimeException('Unable to read Spotify accounts file: ' . $file);
    }

    $data = json_decode($contents, true);
    if (!is_array($data)) {
        return [];
    }

    return $data;
}

function saveSpotifyAccounts(array $accounts): void
{
    $file = getSpotifyAccountsFilePath();
    $dir = dirname($file);

    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create directory for Spotify accounts: ' . $dir);
    }

    $json = json_encode($accounts, JSON_PRETTY_PRINT);
    if ($json === false) {
        throw new RuntimeException('Failed to encode Spotify accounts data: ' . json_last_error_msg());
    }

    $bytes = file_put_contents($file, $json, LOCK_EX);
    if ($bytes === false) {
        throw new RuntimeException('Unable to write Spotify accounts file: ' . $file);
    }
}

function saveSpotifyUserAccount(string $userId, array $profile, string $accessToken, ?string $refreshToken = null, int $expiresAt = 0): void
{
    $accounts = getSpotifyAccounts();
    $accounts[$userId] = [
        'profile' => $profile,
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'expires_at' => $expiresAt,
        'updated_at' => time(),
    ];

    saveSpotifyAccounts($accounts);
}

function getStoredSpotifyUserAccount(string $userId): ?array
{
    $accounts = getSpotifyAccounts();
    return $accounts[$userId] ?? null;
}

function removeStoredSpotifyUserAccount(string $userId): void
{
    $accounts = getSpotifyAccounts();
    unset($accounts[$userId]);
    saveSpotifyAccounts($accounts);
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
        'game' => defaultGameState(),
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

    $rooms[$roomCode] = normalizeRoomData($rooms[$roomCode]);
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
