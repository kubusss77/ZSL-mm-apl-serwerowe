<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

$storage = new Storage();
$gameId = (int)($_POST['game_id'] ?? 0);
$user = trim($_POST['user'] ?? '');
$text = $_POST['text'] ?? '';

if ($gameId <= 0 || $user === '') {
    echo json_encode(['status' => 'error', 'message' => 'Brak danych']);
    exit;
}

$message = appendChatMessage($storage, $gameId, $user, $text);
if ($message === null) {
    echo json_encode(['status' => 'error', 'message' => 'Pusta wiadomość']);
    exit;
}

echo json_encode(['status' => 'success', 'message' => $message]);
