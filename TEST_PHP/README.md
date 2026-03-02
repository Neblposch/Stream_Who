# StreamWho – Spotify Login Test (PHP)

Dies ist ein Minimal-Setup, um den Spotify-Login (OAuth2) in PHP zu testen.

## Dateien

- `config.php`
  - Enthält Spotify Client ID, Client Secret, Redirect URI und Basis-URLs.
  - Hinweis: **Nur für lokale Tests** so verwenden, Secrets später in `.env` auslagern.

- `index.php`
  - Startseite mit Button „Mit Spotify einloggen“.
  - Baut die Spotify-Authorize-URL und leitet zum Login weiter.

- `loggedIn.php`
  - Redirect-Ziel nach erfolgreichem Login.
  - Tauscht den Authorization Code gegen ein Access Token.
  - Ruft die Userdaten über `https://api.spotify.com/v1/me` ab und zeigt Basisinfos an.

## Spotify Developer Dashboard

Stelle sicher, dass in deiner Spotify-App bei den Redirect-URIs **genau** diese URL eingetragen ist:

```text
http://localhost/streamWho/Stream_Who/TEST_PHP/loggedIn.php
```

und dass die Client-ID und das Client-Secret mit deiner App übereinstimmen.

## Lokal testen

1. Stelle sicher, dass dein Webserver (z. B. Apache mit XAMPP) läuft und `htdocs` dein Document Root ist.
2. Rufe im Browser auf:

```text
http://localhost/streamWho/Stream_Who/TEST_PHP/index.php
```

3. Klicke auf „Mit Spotify einloggen“ und folge dem Spotify-Login.
4. Nach erfolgreichem Login solltest du auf `loggedIn.php` geleitet werden, wo deine Spotify-Basisdaten angezeigt werden.
