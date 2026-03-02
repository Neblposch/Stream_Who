<?php
session_start();
require_once __DIR__ . './../src/spotify.php';

$CLIENT_ID = "ce0a01ea1d7845c1841c4ce345514573";
$REDIRECT_URI = "http://127.0.0.1:8080/streamWho/Stream_Who/spotify-php/public/logged-in.php";
$SCOPES = "user-read-email playlist-read-private";

if(isset($_GET['login'])) {
    $code_verifier = generateCodeVerifier();
    $code_challenge = generateCodeChallenge($code_verifier);

    // Save code verifier for token exchange
    $_SESSION['code_verifier'] = $code_verifier;

    $params = [
        'client_id' => $CLIENT_ID,
        'response_type' => 'code',
        'redirect_uri' => $REDIRECT_URI,
        'scope' => $SCOPES,
        'code_challenge_method' => 'S256',
        'code_challenge' => $code_challenge
    ];

    header('Location: https://accounts.spotify.com/authorize?' . http_build_query($params));
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Spotify Test App</title>
</head>
<body>
<h1>Spotify Test App</h1>
<a href="?login=1">Login with Spotify</a>
</body>
</html>