<?php
session_start();

$jsonFile = 'data/spotifyAPI.json';

function loadPlayers() {
    global $jsonFile;
    $data = json_decode(file_get_contents($jsonFile), true);
    return $data['players'];
}

function getNextPlayer($players) {
    if (!isset($_SESSION['current_player_index'])) {
        $_SESSION['current_player_index'] = 0;
    }
    $player = $players[$_SESSION['current_player_index']];
    $_SESSION['current_player_index'] = ($_SESSION['current_player_index'] + 1) % count($players);
    return $player;
}

function getRandomTrack($player) {
    $available = array_filter($player['tracks'], function($track) {
        return !in_array($track['id'], $_SESSION['used_tracks'] ?? []);
    });
    if (empty($available)) {
        return null;
    }
    $random = $available[array_rand($available)];
    $_SESSION['used_tracks'][] = $random['id'];
    return $random;
}

function findTopListener($track, $players) {
    $maxPlaycount = 0;
    $topPlayer = null;
    foreach ($players as $player) {
        foreach ($player['tracks'] as $t) {
            if ($t['id'] === $track['id'] && $t['playcount'] > $maxPlaycount) {
                $maxPlaycount = $t['playcount'];
                $topPlayer = $player;
            }
        }
    }
    return $topPlayer;
}

function handleGuess($guess) {
    $correct = ($guess === $_SESSION['correct_player']['id']);
    if ($correct) {
        $_SESSION['scores'][$guess] = ($_SESSION['scores'][$guess] ?? 0) + 1;
    }
    return $correct;
}

$action = $_REQUEST['action'] ?? 'state';
$players = loadPlayers();

switch ($action) {
    case 'start':
        $_SESSION['used_tracks'] = [];
        $_SESSION['current_round'] = 0;
        $_SESSION['scores'] = array_fill_keys(array_column($players, 'id'), 0);
        $_SESSION['current_player_index'] = 0;
        // Fall through to next_round
    case 'next_round':
        $sourcePlayer = getNextPlayer($players);
        $track = getRandomTrack($sourcePlayer);
        if (!$track) {
            echo json_encode(['success' => false, 'message' => 'No more tracks available']);
            exit;
        }
        $correctPlayer = findTopListener($track, $players);
        $_SESSION['current_round']++;
        $_SESSION['current_track'] = $track;
        $_SESSION['correct_player'] = $correctPlayer;
        // Fall through to state
    case 'state':
        $response = [
            'success' => true,
            'round' => $_SESSION['current_round'] ?? 0,
            'track' => $_SESSION['current_track'] ?? null,
            'players' => array_map(function($p) { return ['id' => $p['id'], 'name' => $p['name']]; }, $players),
            'scores' => $_SESSION['scores'] ?? []
        ];
        echo json_encode($response);
        break;
    case 'guess':
        $guess = $_POST['guess'] ?? '';
        $correct = handleGuess($guess);
        $response = [
            'success' => true,
            'correct' => $correct,
            'scores' => $_SESSION['scores'] ?? []
        ];
        echo json_encode($response);
        break;
}
?>