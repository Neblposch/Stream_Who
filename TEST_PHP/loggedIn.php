<?php
session_start();

require __DIR__ . '/config.php';

// Prüfen, ob Spotify einen Fehler zurückgegeben hat
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
    echo "<h1>Spotify Login fehlgeschlagen</h1>";
    echo "<p>Fehler: {$error}</p>";
    echo '<p><a href="index.php">Zurück zur Startseite</a></p>';
    exit;
}

// Prüfe, ob wir einen Code und State bekommen haben
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    echo '<h1>Ungültiger Aufruf</h1>';
    echo '<p>Es wurden nicht alle benötigten Parameter von Spotify übergeben.</p>';
    echo '<p><a href="index.php">Zurück zur Startseite</a></p>';
    exit;
}

$code  = $_GET['code'];
$state = $_GET['state'];

// State-Token aus der Session prüfen (CSRF-Schutz)
if (!isset($_SESSION['spotify_state']) || $state !== $_SESSION['spotify_state']) {
    echo '<h1>Ungültiger State</h1>';
    echo '<p>Die Sicherheitsprüfung ist fehlgeschlagen. Bitte versuche es erneut.</p>';
    echo '<p><a href="index.php">Zurück zur Startseite</a></p>';
    exit;
}

// State nur einmalig verwenden
unset($_SESSION['spotify_state']);

// 1) Authorization Code gegen Access Token tauschen
$tokenResponse = getSpotifyAccessToken($code);

if ($tokenResponse === null || !isset($tokenResponse['access_token'])) {
    echo '<h1>Fehler beim Abrufen des Zugriffstokens</h1>';
    echo '<p>Bitte versuche es erneut.</p>';
    echo '<p><a href="index.php">Zurück zur Startseite</a></p>';
    exit;
}

$accessToken = $tokenResponse['access_token'];

// 2) User-Infos mit dem Access Token abrufen
$user = getSpotifyCurrentUser($accessToken);

if ($user === null) {
    echo '<h1>Fehler beim Abrufen der Benutzerdaten</h1>';
    echo '<p>Bitte versuche es erneut.</p>';
    echo '<p><a href="index.php">Zurück zur Startseite</a></p>';
    exit;
}

// Daten aus dem User-Array extrahieren
$displayName = $user['display_name'] ?? 'Unbekannt';
$userId      = $user['id'] ?? 'Unbekannt';
$email       = $user['email'] ?? 'Keine E-Mail verfügbar (Scope user-read-email?)';
$images      = $user['images'] ?? [];
$profileImg  = (!empty($images) && isset($images[0]['url'])) ? $images[0]['url'] : null;

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>StreamWho – Eingeloggt</title>
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
        .card {
            background-color: #181818;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .card img {
            border-radius: 50%;
            width: 120px;
            height: 120px;
            object-fit: cover;
            margin-bottom: 1rem;
        }
        h1 {
            margin-top: 0;
            margin-bottom: 1rem;
        }
        .meta {
            text-align: left;
            margin-top: 1rem;
        }
        a.button {
            display: inline-block;
            margin-top: 1.5rem;
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
<div class="card">
    <h1>Du bist eingeloggt 🎵</h1>
    <?php if ($profileImg): ?>
        <img src="<?php echo htmlspecialchars($profileImg, ENT_QUOTES, 'UTF-8'); ?>" alt="Spotify Profilbild">
    <?php endif; ?>

    <div class="meta">
        <p><strong>Display-Name:</strong> <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Spotify ID:</strong> <?php echo htmlspecialchars($userId, ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>E-Mail:</strong> <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <a class="button" href="index.php">Zurück zur Startseite</a>
</div>
</body>
</html>
<?php

/**
 * Tauscht den Authorization Code gegen ein Access Token bei Spotify.
 *
 * @param string $code
 * @return array|null
 */
function getSpotifyAccessToken(string $code): ?array
{
    $postFields = [
        'grant_type'   => 'authorization_code',
        'code'         => $code,
        'redirect_uri' => SPOTIFY_REDIRECT_URI,
    ];

    $ch = curl_init(SPOTIFY_TOKEN_URL);

    $headers = [
        'Authorization: Basic ' . base64_encode(SPOTIFY_CLIENT_ID . ':' . SPOTIFY_CLIENT_SECRET),
        'Content-Type: application/x-www-form-urlencoded',
    ];

    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($postFields),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        // Für Debug-Zwecke lokal anzeigen oder loggen
        error_log('Spotify Token Request Error: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode < 200 || $statusCode >= 300) {
        error_log('Spotify Token Request HTTP Status: ' . $statusCode . ' Response: ' . $response);
        return null;
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {
        error_log('Spotify Token Response JSON Decode Error: ' . $response);
        return null;
    }

    return $data;
}

/**
 * Ruft die aktuellen Benutzerdaten von Spotify (/me) mit einem Access Token ab.
 *
 * @param string $accessToken
 * @return array|null
 */
function getSpotifyCurrentUser(string $accessToken): ?array
{
    $ch = curl_init(SPOTIFY_API_BASE . '/me');

    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Accept: application/json',
    ];

    curl_setopt_array($ch, [
        CURLOPT_HTTPGET        => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        error_log('Spotify Me Request Error: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode < 200 || $statusCode >= 300) {
        error_log('Spotify Me Request HTTP Status: ' . $statusCode . ' Response: ' . $response);
        return null;
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {
        error_log('Spotify Me Response JSON Decode Error: ' . $response);
        return null;
    }

    return $data;
}
