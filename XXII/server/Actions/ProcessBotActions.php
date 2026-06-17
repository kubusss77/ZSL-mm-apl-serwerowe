<?php

require_once __DIR__ . '/../bootstrap.php';

/** Przyjmuje żądanie przetworzenia tury bota dla pokoju. */
function actionProcessBots($storage): void
{
    $roomId = (int)($_GET['room_id'] ?? 0);
    if ($roomId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Brak room_id']);
        return;
    }

    $lockKey = 'bot_lock_' . $roomId;
    if ($storage->get($lockKey)) {
        echo json_encode(['status' => 'busy']);
        return;
    }

    $room = getRoom($storage, $roomId);
    if ($room === null || !roomNeedsBotProcessing($room)) {
        echo json_encode(['status' => 'noop']);
        return;
    }

    echo json_encode(['status' => 'accepted']);
    scheduleBotProcessing($storage, $roomId);
}
