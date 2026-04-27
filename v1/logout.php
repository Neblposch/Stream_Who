<?php
require_once __DIR__ . '/spotify_helper.php';
require_once __DIR__ . '/functions.php';

startSession();

// Logout from both Spotify and local session
spotifyLogout();
session_unset();
session_destroy();

header('Location: index.php');
exit;
?>
