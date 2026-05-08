Setup steps to enable PKCE Spotify login in this v1 folder

Files provided in this folder:
- spotify_helper.php (helper, already present and used)
- login_impl.php (implementation to be swapped into login.php)
- callback_impl.php (intended implementation file - may be empty on disk due to tool limitations)
- index_impl.php (updated index that shows login/logout links)
- logout.php (already present)

Because some files in your repository already exist but are empty, my automation could not overwrite them directly in all cases. Perform the rename steps below in Windows cmd to activate the new implementation safely. Backups are created where appropriate.

Run these commands in Command Prompt (cmd.exe) inside the v1 folder (adjust path if needed):

cd "E:\tobias\Downloads\DOCKER_TEMPLATE(1)\docker_php_template\htdocs\streamWho\Stream_Who\v1"

:: Backup existing files (if any)
if exist login.php ren login.php login.php.bak
if exist callback.php ren callback.php callback.php.bak
if exist index.php ren index.php index.php.bak

:: Move new implementations into place
ren login_impl.php login.php
ren callback_impl.php callback.php
ren index_impl.php index.php

:: Start PHP built-in server (optional)
:: php -S 127.0.0.1:8080

Notes:
- Make sure that the redirect URI in spotify_helper.php matches the redirect set in your Spotify Developer Dashboard:
  http://127.0.0.1:8080/streamWho/Stream_Who/v1/callback.php

- To test: start the PHP built-in server with the command above, open http://127.0.0.1:8080/ in your browser, click Log In, authenticate with Spotify, and approve the scopes.

If anything fails, restore backups:
ren login.php.bak login.php
ren callback.php.bak callback.php
ren index.php.bak index.php

