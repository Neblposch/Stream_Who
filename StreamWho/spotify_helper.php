<?php
session_start();

// Spotify PKCE helper - works for both localhost and production
const SPOTIFY_CLIENT_ID = '76cdb795f3bf44b99361f82a0c16f9d0';

// Determine the redirect URI based on the current environment
function getSpotifyRedirectUri() {
    // For production (tpos.at)
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'tpos.at') !== false) {
        return 'https://tpos.at/streamwho/spotify_callback.php';
    }
    
    // For localhost development
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . $host . '/Stream_Who/v1/spotify_callback.php';
}

const SPOTIFY_REDIRECT_URI = '';  // Will be determined dynamically

function generateCodeVerifier($length = 128) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~';
    $verifier = '';
    for ($i = 0; $i < $length; $i++) {
        $verifier .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $verifier;
}

function generateCodeChallenge($verifier) {
    $hash = hash('sha256', $verifier, true);
    return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
}

function fetchSpotify($url, $allowRefresh = true) {
    if (empty($_SESSION['access_token'])) {
        return ['error' => 'no_token'];
    }

    // Refresh if expired
    if (!empty($_SESSION['expires_at']) && time() >= $_SESSION['expires_at']) {
        if ($allowRefresh && !empty($_SESSION['refresh_token'])) {
            $ref = refreshAccessToken($_SESSION['refresh_token']);
            if (isset($ref['access_token'])) {
                $_SESSION['access_token'] = $ref['access_token'];
                if (isset($ref['refresh_token'])) $_SESSION['refresh_token'] = $ref['refresh_token'];
                $_SESSION['expires_at'] = time() + ($ref['expires_in'] ?? 3600);
            } else {
                return ['error' => 'refresh_failed', 'detail' => $ref];
            }
        }
    }

    $access_token = $_SESSION['access_token'];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $retryAfter = null;
    if ($httpCode == 0) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['error' => 'curl_error', 'detail' => $err];
    }
    // Respect 429 Retry-After with exponential backoff
    if ($httpCode == 429) {
        $headers = curl_getinfo($ch);
        $retryAfter = 1;
    }
    curl_close($ch);

    if ($httpCode == 429) {
        // simple backoff
        sleep($retryAfter);
        return fetchSpotify($url, false);
    }

    $data = json_decode($res, true);
    if ($httpCode >= 400) {
        return ['error' => 'api_error', 'status' => $httpCode, 'body' => $data];
    }
    return $data;
}

function exchangeCodeForToken($code, $code_verifier) {
    $redirectUri = getSpotifyRedirectUri();
    $postData = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirectUri,
        'client_id' => SPOTIFY_CLIENT_ID,
        'code_verifier' => $code_verifier
    ];

    $ch = curl_init('https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $tokenData = json_decode($response, true);
    if ($httpCode >= 400) {
        return ['error' => 'token_error', 'status' => $httpCode, 'body' => $tokenData];
    }
    return $tokenData;
}

function refreshAccessToken($refresh_token) {
    $postData = [
        'grant_type' => 'refresh_token',
        'refresh_token' => $refresh_token,
        'client_id' => SPOTIFY_CLIENT_ID
    ];

    $ch = curl_init('https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $tokenData = json_decode($response, true);
    if ($httpCode >= 400) {
        return ['error' => 'refresh_error', 'status' => $httpCode, 'body' => $tokenData];
    }
    return $tokenData;
}

function spotifyLogout() {
    unset($_SESSION['access_token']);
    unset($_SESSION['refresh_token']);
    unset($_SESSION['expires_at']);
    unset($_SESSION['spotify_user']);
    unset($_SESSION['code_verifier']);
}

?>
