<?php
session_start();
require_once 'spotify.php';

$CLIENT_ID = "ce0a01ea1d7845c1841c4ce345514573";
$REDIRECT_URI = "http://127.0.0.1:3000/callback.php";

if(!isset($_GET['code'])) die("No code from Spotify.");

$code = $_GET['code'];
$code_verifier = $_SESSION['code_verifier'] ?? '';

$postData = [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $REDIRECT_URI,
    'client_id' => $CLIENT_ID,
    'code_verifier' => $code_verifier
];

$ch = curl_init('https://accounts.spotify.com/api/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response, true);
$access_token = $tokenData['access_token'] ?? '';
if(!$access_token) die("Token request failed");

echo "<h2>Logged in!</h2>";
echo "<pre>" . print_r($tokenData, true) . "</pre>";

// Profil abrufen
$profile = fetchSpotify("https://api.spotify.com/v1/me", $access_token);
echo "<h3>User Profile</h3>";
echo "<pre>" . print_r($profile, true) . "</pre>";

// Lieblingssong abrufen (Top Track)
$topTracks = fetchSpotify("https://api.spotify.com/v1/me/top/tracks?limit=1", $access_token);
if(!empty($topTracks['items'])) {
    $track = $topTracks['items'][0];
    $artists = implode(", ", array_map(fn($a) => $a['name'], $track['artists']));
    echo "<p>Your favorite track: <strong>{$track['name']}</strong> by <strong>{$artists}</strong></p>";
}
?>