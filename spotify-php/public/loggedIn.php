<?php
session_start();
require_once __DIR__ . '/../src/spotify.php';

$CLIENT_ID = "ce0a01ea1d7845c1841c4ce345514573";
$REDIRECT_URI = "http://127.0.0.1:8080/streamWho/Stream_Who/spotify-php/public/logged-in.php";

if(!isset($_GET['code'])) {
    die("No code from Spotify. Please <a href='index.php'>login</a> first.");
}

$code = $_GET['code'];
$code_verifier = $_SESSION['code_verifier'] ?? '';

$postData = [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $REDIRECT_URI,
    'client_id' => $CLIENT_ID,
    'code_verifier' => $code_verifier
];

// Exchange code for access token
$ch = curl_init('https://accounts.spotify.com/api/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response, true);
$access_token = $tokenData['access_token'] ?? '';
if(!$access_token) die("Token request failed");

// Fetch profile
$profile = fetchSpotify("https://api.spotify.com/v1/me", $access_token);

// Fetch top 5 tracks
$topTracksData = fetchSpotify("https://api.spotify.com/v1/me/top/tracks?limit=5", $access_token);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Logged In</title>
</head>
<body>
<h1>Welcome, <?= htmlspecialchars($profile['display_name'] ?? 'User') ?>!</h1>

<h2>Profile Info</h2>
<p>Email: <?= htmlspecialchars($profile['email'] ?? '-') ?></p>
<p>Country: <?= htmlspecialchars($profile['country'] ?? '-') ?></p>
<?php if(!empty($profile['images'][0]['url'])): ?>
    <img src="<?= htmlspecialchars($profile['images'][0]['url']) ?>" width="150"/>
<?php endif; ?>

<h2>Your Top 5 Tracks</h2>
<ol>
    <?php
    if(!empty($topTracksData['items'])) {
        foreach($topTracksData['items'] as $track) {
            $artists = implode(", ", array_map(fn($a) => $a['name'], $track['artists']));
            echo "<li>" . htmlspecialchars($track['name']) . " by " . htmlspecialchars($artists) . "</li>";
        }
    } else {
        echo "<li>No top tracks found.</li>";
    }
    ?>
</ol>

</body>
</html>