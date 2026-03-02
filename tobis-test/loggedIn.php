<?php
session_start();
require __DIR__ . '/config.php';

function abortWithMessage(string $message): void
{
    http_response_code(400);
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport"
              content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
        <title>Spotify Login Error</title>
        <link rel="stylesheet" href="style.css" />
    </head>
    <body class="spotify-surface">
    <main class="center-wrap">
        <section class="card">
            <h1 class="title">Login error</h1>
            <p class="subtitle">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <div class="actions">
                <a class="btn btn-primary" href="index.php">Back to start</a>
            </div>
        </section>
    </main>
    </body>
    </html>
    <?php
    exit;
}

// Fehler von Spotify?
if (isset($_GET['error'])) {
    abortWithMessage('Spotify returned an error: ' . $_GET['error']);
}

// Code + State aus Query lesen
$code  = $_GET['code']  ?? null;
$state = $_GET['state'] ?? null;

if (!$code) {
    abortWithMessage('Missing authorization code. Please start the login again.');
}

// CSRF-Schutz: state prüfen
if (!$state || !isset($_SESSION['spotify_oauth_state']) || $state !== $_SESSION['spotify_oauth_state']) {
    abortWithMessage('Invalid state parameter. Please try logging in again.');
}

// State nach erfolgreicher Prüfung entfernen
unset($_SESSION['spotify_oauth_state']);

// Token-Tausch mit Spotify
$tokenData = [
    'grant_type'   => 'authorization_code',
    'code'         => $code,
    'redirect_uri' => SPOTIFY_REDIRECT_URI,
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => SPOTIFY_TOKEN_URL,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($tokenData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Basic ' . base64_encode(SPOTIFY_CLIENT_ID . ':' . SPOTIFY_CLIENT_SECRET),
        'Content-Type: application/x-www-form-urlencoded',
    ],
]);

$response = curl_exec($ch);
if ($response === false) {
    $err = curl_error($ch);
    curl_close($ch);
    abortWithMessage('Error while requesting token: ' . $err);
}

$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$tokenJson = json_decode($response, true);
if ($statusCode !== 200 || !is_array($tokenJson) || empty($tokenJson['access_token'])) {
    $msg = 'Token endpoint returned status ' . $statusCode;
    if (isset($tokenJson['error_description'])) {
        $msg .= ' – ' . $tokenJson['error_description'];
    }
    abortWithMessage($msg);
}

$accessToken  = $tokenJson['access_token'];
$refreshToken = $tokenJson['refresh_token'] ?? null;
$expiresIn    = $tokenJson['expires_in'] ?? 0;

// Token in der Session speichern
$_SESSION['spotify_access_token']      = $accessToken;
$_SESSION['spotify_refresh_token']     = $refreshToken;
$_SESSION['spotify_token_expires_at']  = time() + (int)$expiresIn;

// Userprofil abrufen
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => SPOTIFY_API_BASE . '/me',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
    ],
]);
$userResponse = curl_exec($ch);
if ($userResponse === false) {
    $err = curl_error($ch);
    curl_close($ch);
    abortWithMessage('Error while fetching profile: ' . $err);
}

$userStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$userJson = json_decode($userResponse, true);
if ($userStatus !== 200 || !is_array($userJson)) {
    abortWithMessage('Failed to fetch user profile. Status ' . $userStatus);
}

// Für später in Session merken
$_SESSION['spotify_user'] = $userJson;

// Daten fürs Template
$displayName = $userJson['display_name'] ?? ($userJson['id'] ?? 'Unknown user');
$email       = $userJson['email'] ?? null;
$imageUrl    = null;
if (!empty($userJson['images']) && is_array($userJson['images'])) {
    $firstImage = $userJson['images'][0] ?? null;
    if (is_array($firstImage) && !empty($firstImage['url'])) {
        $imageUrl = $firstImage['url'];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Logged In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style.css" />
</head>
<body class="spotify-surface">
<main class="center-wrap">
    <section class="card">
        <div class="badge">
            <span class="dot"></span>
            Connected
        </div>

        <div class="icon-wrap" aria-hidden="true">
            <svg class="check" width="72" height="72" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="11" stroke="rgba(255,255,255,.12)" stroke-width="2" />
                <path d="M7 12.5l3 3 7-7" stroke="#1DB954" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div class="glow"></div>
        </div>

        <h1 class="title">You are now logged in</h1>
        <p class="subtitle">Your Spotify account is linked. Enjoy the music.</p>

        <div style="margin: 16px 0; text-align: left;">
            <p><strong>Name:</strong>
                <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
            </p>
            <p><strong>E-Mail:</strong>
                <?php echo $email
                    ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8')
                    : '(no e-mail provided)'; ?>
            </p>

            <?php if ($imageUrl): ?>
                <div style="margin-top: 8px;">
                    <img src="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                         alt="Profile image"
                         style="max-width: 96px; border-radius: 50%;">
                </div>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a class="btn btn-primary" href="index.php">Back to start</a>
            <a class="btn btn-ghost" href="player.html">Open player</a>
        </div>

        <div class="eq" aria-hidden="true">
            <span style="--d:0ms"></span>
            <span style="--d:120ms"></span>
            <span style="--d:240ms"></span>
            <span style="--d:360ms"></span>
            <span style="--d:480ms"></span>
        </div>
    </section>
</main>

<footer class="foot">
    <small>Designed with Spotify‑inspired colors.</small>
</footer>
</body>
</html>
