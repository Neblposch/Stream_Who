Ready-to-host setup for StreamWho (v1)

Goal:
Make the production-ready files active so the site works at https://tpos.at/streamwho/ with the callback https://tpos.at/streamwho/loggedIn.php

Files you already have in this folder (prod versions created by me):
- spotify_helper_prod.php   (uses redirect https://tpos.at/streamwho/loggedIn.php)
- login_prod.php            (starts PKCE flow, depends on spotify_helper_prod.php)
- loggedIn_prod.php        (callback page that exchanges code and shows profile)
- index_prod.php           (simple index that shows Log In/Out)
- logout_prod.php          (logs out using spotify_helper_prod.php)

Recommended activation steps (Windows cmd.exe)
Open Command Prompt and run these commands from the v1 folder:

cd "E:\tobias\Downloads\DOCKER_TEMPLATE(1)\docker_php_template\htdocs\streamWho\Stream_Who\v1"

:: Backup existing files first (creates .bak files if originals exist)
if exist spotify_helper.php ren spotify_helper.php spotify_helper.php.bak
if exist login.php ren login.php login.php.bak
if exist loggedIn.php ren loggedIn.php loggedIn.php.bak
if exist index.php ren index.php index.php.bak
if exist logout.php ren logout.php logout.php.bak

:: Move (rename) prod files into place
ren spotify_helper_prod.php spotify_helper.php
ren login_prod.php login.php
ren loggedIn_prod.php loggedIn.php
ren index_prod.php index.php
ren logout_prod.php logout.php

Notes:
- The Spotify redirect URI in the helper is set to exactly:
  https://tpos.at/streamwho/loggedIn.php
  Make sure this same URI is configured in your Spotify Developer Dashboard for the app whose client ID is in the helper.

- If your site is hosted at a different path or domain, update the constant SPOTIFY_REDIRECT_URI in spotify_helper.php to match the exact redirect URI registered at Spotify.

Quick local test (optional):
If you want to sanity-check locally before uploading, you can use the PHP built-in server (for local dev only):

php -S 127.0.0.1:8080

Then open http://127.0.0.1:8080/ and try Log In. Note: when testing locally, redirect URI in Spotify Dashboard must match the callback used (the prod helper points to https; for local debugging you'd need to use a different helper or change redirect in both Spotify and helper).

What to do if something fails:
- "error=invalid_request" or "redirect_uri_mismatch": double-check redirect entry in Spotify Dashboard exactly equals https://tpos.at/streamwho/loggedIn.php
- Token exchange failures: loggedIn.php will display the raw token error; copy it and inspect for invalid_client or mismatched redirect.
- If you need me to perform the rename/activation for you in the repo, reply and I will rename the files automatically here.

Security note:
- This uses PKCE (no client secret required). Keep the client secret out of repository files.

Done. If you'd like, I can now perform the renames automatically in the repository (I will create backups first). Reply "Perform activation" to have me do that, or follow the commands above yourself.
