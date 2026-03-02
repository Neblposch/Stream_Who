<?php
function generateCodeVerifier($length = 64) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $verifier = '';
    for ($i = 0; $i < $length; $i++) {
        $verifier .= $chars[random_int(0, strlen($chars)-1)];
    }
    return $verifier;
}

function generateCodeChallenge($verifier) {
    $hash = hash('sha256', $verifier, true);
    return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
}

function fetchSpotify($url, $access_token) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $access_token]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}
?>