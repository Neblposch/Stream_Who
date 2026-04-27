<?php
// Dark minimal login redirect for production URL
require_once __DIR__ . '/spotify_helper.php';

// Build PKCE params and redirect to Spotify authorize
$verifier = generateCodeVerifier();
$challenge = generateCodeChallenge($verifier);
$_SESSION['code_verifier'] = $verifier;

$scope = 'user-read-email user-top-read';
$params = [
    'client_id' => SPOTIFY_CLIENT_ID,
    'response_type' => 'code',
    'redirect_uri' => SPOTIFY_REDIRECT_URI,
    'code_challenge_method' => 'S256',
    'code_challenge' => $challenge,
    'scope' => $scope
];

$authorize = 'https://accounts.spotify.com/authorize?' . http_build_query($params);
header('Location: ' . $authorize);
exit;
?>
