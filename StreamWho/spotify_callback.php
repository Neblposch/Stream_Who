<?php
// File: `spotify_callback.php`
// Ensure session is started, exchange code, save tokens + profile, then redirect.

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/spotify_helper.php';

startSession();

if (isset($_GET['error'])) {
    // user denied or Spotify returned an error
    header('Location: index.php');
    exit;
}

if (!isset($_GET['code'])) {
    header('Location: index.php');
    exit;
}

$code = $_GET['code'];
$code_verifier = $_SESSION['code_verifier'] ?? '';

$tokenData = exchangeCodeForToken($code, $code_verifier);
if (isset($tokenData['error'])) {
    header('Location: index.php');
    exit;
}

// Store tokens in session
$_SESSION['access_token'] = $tokenData['access_token'];
if (isset($tokenData['refresh_token'])) {
    $_SESSION['refresh_token'] = $tokenData['refresh_token'];
}
$_SESSION['expires_at'] = time() + ($tokenData['expires_in'] ?? 3600);

// Clear PKCE verifier
unset($_SESSION['code_verifier']);

// Fetch and store Spotify profile (uses the just-saved access token)
$profile = fetchSpotify('https://api.spotify.com/v1/me');
if (isset($profile['error'])) {
    // If profile fetch fails, drop back to index (or handle as needed)
    header('Location: index.php');
    exit;
}

$_SESSION['spotify_user'] = $profile;

saveSpotifyUserAccount(
    $profile['id'] ?? 'unknown',
    $profile,
    $_SESSION['access_token'],
    $_SESSION['refresh_token'] ?? null,
    $_SESSION['expires_at'] ?? (time() + 3600)
);

// Redirect to lobby / main flow
header('Location: lobby.php');
exit;
?>
