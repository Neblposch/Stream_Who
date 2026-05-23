<?php
// php
// File: `spotify_helper.php`
// Minimal helper file — no callback logic here.

require_once __DIR__ . '/functions.php';
startSession();

const SPOTIFY_CLIENT_ID = '76cdb795f3bf44b99361f82a0c16f9d0';
const SPOTIFY_REDIRECT_URI = 'https://tpos.at/streamwho/spotify_callback.php'; // keep callback separate

function generateCodeVerifier(int $length = 64): string
{
    return rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');
}

function generateCodeChallenge(string $verifier): string
{
    return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
}

function exchangeCodeForToken(string $code, string $code_verifier): array
{
    $post = http_build_query([
        'client_id' => SPOTIFY_CLIENT_ID,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => SPOTIFY_REDIRECT_URI,
        'code_verifier' => $code_verifier,
    ]);

    $ch = curl_init('https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return ['error' => $err ?: 'curl_error'];
    }

    $data = json_decode($resp, true);
    return $data ?: ['error' => 'invalid_response'];
}

function refreshSpotifyToken(string $refreshToken): array
{
    $post = http_build_query([
        'client_id' => SPOTIFY_CLIENT_ID,
        'grant_type' => 'refresh_token',
        'refresh_token' => $refreshToken,
    ]);

    $ch = curl_init('https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($resp === false) {
        return ['error' => $err ?: 'curl_error'];
    }

    $data = json_decode($resp, true);
    if (!is_array($data)) {
        return ['error' => 'invalid_response'];
    }

    return $data;
}

function getSpotifyAccessToken(?string $userId = null): ?string
{
    if ($userId === null) {
        return $_SESSION['access_token'] ?? null;
    }

    $account = getStoredSpotifyUserAccount($userId);
    if (!$account) {
        return null;
    }

    $token = $account['access_token'] ?? null;
    $expiresAt = (int)($account['expires_at'] ?? 0);
    if (!empty($token) && ($expiresAt === 0 || $expiresAt > time())) {
        return $token;
    }

    $refreshToken = $account['refresh_token'] ?? null;
    if (empty($refreshToken)) {
        return null;
    }

    $refreshed = refreshSpotifyToken($refreshToken);
    if (isset($refreshed['error']) || empty($refreshed['access_token'])) {
        return null;
    }

    saveSpotifyUserAccount(
        $userId,
        $account['profile'] ?? [],
        $refreshed['access_token'],
        $refreshToken,
        time() + (int)($refreshed['expires_in'] ?? 3600)
    );

    return $refreshed['access_token'];
}

function fetchSpotify(string $url, ?string $userId = null): array
{
    $token = getSpotifyAccessToken($userId);
    if (empty($token)) {
        return ['error' => 'no_access_token'];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false) {
        return ['error' => $err ?: 'curl_error'];
    }

    $data = json_decode($resp, true);
    if ($httpCode >= 400) {
        return ['error' => $data ?? 'http_error', 'status' => $httpCode];
    }

    return $data ?: ['error' => 'invalid_response'];
}

function logout(): void
{
    $userId = $_SESSION['spotify_user']['id'] ?? null;
    if ($userId) {
        removeStoredSpotifyUserAccount($userId);
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}
?>
