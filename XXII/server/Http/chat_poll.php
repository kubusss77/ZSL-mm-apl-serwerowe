<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

$storage = new Storage();
$gameId = (int)($_GET['game_id'] ?? 0);
$lastId = (int)($_GET['last_id'] ?? 0);
$timeout = min((int)($_GET['timeout'] ?? 30), 30);

if ($gameId <= 0) {
    echo json_encode([]);
    exit;
}

$deadline = time() + $timeout;

while (true) {
    $messages = $storage->get('chat_messages_' . $gameId) ?? [];
    $newMessages = array_values(array_filter($messages, static fn($m) => ($m['id'] ?? 0) > $lastId));

    if (count($newMessages) > 0) {
        echo json_encode($newMessages);
        exit;
    }

    if (time() >= $deadline) {
        break;
    }

    usleep(500_000);
}

echo json_encode([]);
