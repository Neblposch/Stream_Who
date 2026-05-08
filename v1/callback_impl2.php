
<?php
//session_start();
require_once __DIR__ . '/spotify_helper.php';




if (!isset($_GET['code'])) {
    if (isset($_GET['error'])) {
        echo "Spotify authorization error: " . htmlspecialchars($_GET['error']);
    } else {
        echo "No code returned by Spotify.";
    }
    exit;
}


$code = $_GET['code'];
$code_verifier = $_SESSION['code_verifier'] ?? '';

$tokenData = exchangeCodeForToken($code, $code_verifier);
if (isset($tokenData['error'])) {
    echo "Token exchange failed: " . htmlspecialchars(json_encode($tokenData));
    exit;
}

// Store tokens in session securely
$_SESSION['access_token'] = $tokenData['access_token'];
if (isset($tokenData['refresh_token'])) $_SESSION['refresh_token'] = $tokenData['refresh_token'];
$_SESSION['expires_at'] = time() + ($tokenData['expires_in'] ?? 3600);

// Redirect back to index where UI will show logged-in state
header('Location: index.php');
exit;
?>
