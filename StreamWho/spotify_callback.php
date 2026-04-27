<?php
require_once __DIR__ . '/spotify_helper.php';
require_once __DIR__ . '/functions.php';

startSession();

if (isset($_GET['error'])) {
    echo '<h2>Spotify authorization error</h2>';
    echo '<p>' . htmlspecialchars($_GET['error']) . '</p>';
    echo '<p><a href="index.php">Return to home</a></p>';
    exit;
}

if (!isset($_GET['code'])) {
    echo '<h2>No code returned</h2>';
    echo '<p>Did you click cancel or was the redirect configured incorrectly?</p>';
    echo '<p><a href="index.php">Return to home</a></p>';
    exit;
}

$code = $_GET['code'];
$code_verifier = $_SESSION['code_verifier'] ?? '';

if (!$code_verifier) {
    echo '<h2>Session error</h2>';
    echo '<p>Code verifier not found in session. Please try logging in again.</p>';
    echo '<p><a href="index.php">Return to home</a></p>';
    exit;
}

$tokenData = exchangeCodeForToken($code, $code_verifier);
if (isset($tokenData['error'])) {
    echo '<h2>Token exchange failed</h2>';
    echo '<pre>' . htmlspecialchars(json_encode($tokenData, JSON_PRETTY_PRINT)) . '</pre>';
    echo '<p><a href="index.php">Return to home</a></p>';
    exit;
}

// Store tokens in session
$_SESSION['access_token'] = $tokenData['access_token'];
if (isset($tokenData['refresh_token'])) $_SESSION['refresh_token'] = $tokenData['refresh_token'];
$_SESSION['expires_at'] = time() + ($tokenData['expires_in'] ?? 3600);

// Fetch profile
$profile = fetchSpotify('https://api.spotify.com/v1/me');

// Store user profile in session
if (!isset($profile['error'])) {
    $_SESSION['spotify_user'] = $profile;
    
    // Create or log in with a username based on Spotify profile
    $spotifyUsername = $profile['display_name'] ?? $profile['id'] ?? 'SpotifyUser';
    $spotifyUsername = sanitizeUsername($spotifyUsername);
    
    try {
        loginUser($spotifyUsername);
    } catch (Exception $e) {
        // Handle error but continue anyway
        error_log('Error logging in Spotify user: ' . $e->getMessage());
    }
}

// Redirect to lobby
header('Location: lobby.php');
exit;
?>

