<?php
session_start();
require __DIR__ . '/config.php';

// CSRF-Schutz: zufälligen state generieren und in Session speichern
$state = bin2hex(random_bytes(16));
$_SESSION['spotify_oauth_state'] = $state;

// Login-URL für Spotify bauen
$queryParams = [
    'response_type' => 'code',
    'client_id'     => SPOTIFY_CLIENT_ID,
    'redirect_uri'  => SPOTIFY_REDIRECT_URI,
    'scope'         => SPOTIFY_SCOPES,
    'state'         => $state,
];

$authUrl = SPOTIFY_AUTH_URL . '?' . http_build_query($queryParams);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Spotify Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
</head>
<body class="dark-surface">
<main class="center-wrap">
    <section class="card">
        <div class="badge">
            <span class="dot"></span>
            Ready to connect
        </div>

        <div class="icon-wrap" aria-hidden="true">
            <!-- Simple music note icon -->
            <svg class="check" width="72" height="72" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="11" stroke="rgba(255,255,255,.12)" stroke-width="2" />
                <path d="M8 7v8.5a2.5 2.5 0 1 0 1.5 2.3V9.2l7-2v6.3a2.5 2.5 0 1 0 1.5 2.3V5.5L8 7z"
                      stroke="#1DB954" stroke-width="1.6" fill="rgba(29,185,84,.22)" />
            </svg>
            <div class="glow"></div>
        </div>

        <h1 class="title">Connect your Spotify</h1>
        <p class="subtitle">Log in to link your account and see your profile.</p>

        <div class="actions">
            <!-- Der Button ist hier ein normaler Link zu Spotify -->
            <a class="btn btn-primary"
               href="<?php echo htmlspecialchars($authUrl, ENT_QUOTES, 'UTF-8'); ?>">
                Login with Spotify
            </a>
        </div>

        <?php if (!empty($_SESSION['spotify_user'])): ?>
            <pre id="output" class="code-panel" aria-live="polite">
<?php echo htmlspecialchars(json_encode($_SESSION['spotify_user'], JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8'); ?>
            </pre>
        <?php endif; ?>
    </section>
</main>

<footer class="foot">
    <small>Dark mode • Background #121212</small>
</footer>
</body>
</html>
