<?php
require_once __DIR__ . '/spotify_helper_prod.php';
logout();
header('Location: index_prod.php');
exit;
?>
