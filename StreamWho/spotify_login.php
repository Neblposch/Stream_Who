<?php
require_once __DIR__ . '/spotify_helper.php';

// Build PKCE params and redirect to Spotify authorize
$verifier = generateCodeVerifier();
$challenge = generateCodeChallenge($verifier);
$_SESSION['code_verifier'] = $verifier;

$scope = 'user-read-email user-top-read user-read-private';
$redirectUri = getSpotifyRedirectUri();
$params = [
    'client_id' => SPOTIFY_CLIENT_ID,
    'response_type' => 'code',
    'redirect_uri' => $redirectUri,
    'code_challenge_method' => 'S256',
    'code_challenge' => $challenge,
    'scope' => $scope
];

$authorize = 'https://accounts.spotify.com/authorize?' . http_build_query($params);
header('Location: ' . $authorize);
exit;
?>
