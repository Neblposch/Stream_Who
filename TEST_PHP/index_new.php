<?php
session_start();

require __DIR__ . '/config.php';

// Zufälligen State-Parameter für CSRF-Schutz erzeugen
if (function_exists('random_bytes')) {
    $state = bin2hex(random_bytes(16));
} else {
    $state = md5(uniqid('spotify_state', true));
}

$_SESSION['spotify_state'] = $state;

// Authorize-URL für Spotify bauen
$queryParams = array(
    'client_id'     => SPOTIFY_CLIENT_ID,
    'response_type' => 'code',
    'redirect_uri'  => SPOTIFY_REDIRECT_URI,
    'scope'         => SPOTIFY_SCOPES,
    'state'         => $state,
);

$authorizeUrl = SPOTIFY_AUTH_URL . '?' . http_build_query($queryParams);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>StreamWho – Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #121212;
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            text-align: center;
        }
        h1 {
            margin-bottom: 0.5rem;
        }
        p {
            max-width: 480px;
            margin: 0 auto 1.5rem;
        }
        a.button {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #1DB954;
            color: #fff;
            text-decoration: none;
            border-radius: 24px;
            font-weight: bold;
        }
        a.button:hover {
            background-color: #1ed760;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>StreamWho</h1>
    <p>
        Melde dich mit deinem Spotify-Account an, damit wir deine Basisdaten laden
        und dich später im Spiel verwenden können.
    </p>
    <a class="button" href="<?php echo htmlspecialchars($authorizeUrl, ENT_QUOTES, 'UTF-8'); ?>">Mit Spotify einloggen</a>
</div>
</body>
</html>
