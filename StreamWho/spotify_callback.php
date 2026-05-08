<?php
require_once __DIR__ . '/spotify_helper.php';
require_once __DIR__ . '/functions.php';


if (isset($_GET['error'])) {
    echo '<h2>Spotify authorization error</h2>';
    echo '<p>' . htmlspecialchars($_GET['error']) . '</p>';
    exit;
}

if (!isset($_GET['code'])) {
    echo '<h2>No code returned</h2>';
    echo '<p>Did you click cancel or was the redirect configured incorrectly?</p>';
    exit;
}

$code = $_GET['code'];
$code_verifier = $_SESSION['code_verifier'] ?? '';

$tokenData = exchangeCodeForToken($code, $code_verifier);
if (isset($tokenData['error'])) {
    echo '<h2>Token exchange failed</h2>';
    echo '<pre>' . htmlspecialchars(json_encode($tokenData, JSON_PRETTY_PRINT)) . '</pre>';
    exit;
}

// Store tokens in session
$_SESSION['access_token'] = $tokenData['access_token'];
if (isset($tokenData['refresh_token'])) $_SESSION['refresh_token'] = $tokenData['refresh_token'];
$_SESSION['expires_at'] = time() + ($tokenData['expires_in'] ?? 3600);

// Fetch profile
$profile = fetchSpotify('https://api.spotify.com/v1/me');

// Simple post-login page
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Logged in - StreamWho</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <img height="60vh" src="Img/logo.png" alt="logo">
        <h2>StreamWho</h2>
        <p><a href="logout.php">Log Out</a></p>
    </header>

    <main>
        <section id="logged-in">
            <h1>You're logged in with Spotify</h1>
            <?php if (isset($profile['display_name'])): ?>
                <p>Welcome, <?php echo htmlspecialchars($profile['display_name']); ?>!</p>
            <?php else: ?>
                <p>Welcome — profile could not be fetched.</p>
            <?php endif; ?>

            <?php if (!empty($profile['images'][0]['url'])): ?>
                <img src="<?php echo htmlspecialchars($profile['images'][0]['url']); ?>" alt="avatar" style="height:80px;border-radius:40px">
            <?php endif; ?>

            <p>Email: <?php echo htmlspecialchars($profile['email'] ?? '—'); ?></p>

            <p><a href="index.php">Return to site</a></p>
        </section>
    </main>

    <footer>
        <a href="#">Impressum</a>
    </footer>
</body>
</html>
