<?php

// Spotify API configuration (lokal, nur zu Testzwecken hart codiert)

// HINWEIS: In einem echten Projekt diese Werte in eine .env oder Server-Konfiguration auslagern
// und NIEMALS in ein öffentliches Repository pushen.

const SPOTIFY_CLIENT_ID     = 'ce0a01ea1d7845c1841c4ce345514573';
const SPOTIFY_CLIENT_SECRET = 'a04db91505e54b8d9ccadbdfce19f1e3';

// Passe diese URL genau so an, wie sie im Spotify Developer Dashboard eingetragen ist
const SPOTIFY_REDIRECT_URI  = 'http://localhost/streamWho/Stream_Who/TEST_PHP/loggedIn.php';

const SPOTIFY_AUTH_URL      = 'https://accounts.spotify.com/authorize';
const SPOTIFY_TOKEN_URL     = 'https://accounts.spotify.com/api/token';
const SPOTIFY_API_BASE      = 'https://api.spotify.com/v1';

// Minimale Scopes für Basis-Userdaten inkl. E-Mail
const SPOTIFY_SCOPES = 'user-read-email';
