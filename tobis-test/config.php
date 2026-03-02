<?php
// Spotify OAuth configuration for StreamWho (tobis-test)

// Für dieses Projekt nutzen wir direkt die festen Werte.
// Später kannst du sie über ENV-Variablen aus Docker setzen.

define('SPOTIFY_CLIENT_ID', 'ce0a01ea1d7845c1841c4ce345514573');
define('SPOTIFY_CLIENT_SECRET', 'a04db91505e54b8d9ccadbdfce19f1e3');

// Diese URL muss EXAKT so auch im Spotify Developer Dashboard als Redirect-URI eingetragen sein.
define(
    'SPOTIFY_REDIRECT_URI',
    'http://localhost/streamWho/Stream_Who/tobis-test/loggedIn.php'
);

// Spotify OAuth / API Endpoints
define('SPOTIFY_AUTH_URL', 'https://accounts.spotify.com/authorize');
define('SPOTIFY_TOKEN_URL', 'https://accounts.spotify.com/api/token');
define('SPOTIFY_API_BASE', 'https://api.spotify.com/v1');

// Für den ersten Schritt reicht: Basis-Profil + E-Mail
define('SPOTIFY_SCOPES', 'user-read-email');
