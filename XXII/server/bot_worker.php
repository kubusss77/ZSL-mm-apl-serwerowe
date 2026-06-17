#!/usr/bin/env php
<?php

$roomId = (int)($argv[1] ?? 0);
if ($roomId <= 0) {
    fwrite(STDERR, "Usage: php bot_worker.php <room_id>\n");
    exit(1);
}

require_once __DIR__ . '/bootstrap.php';

$storage = new Storage();
$lockKey = 'bot_lock_' . $roomId;

if ($storage->get($lockKey)) {
    exit(0);
}

$storage->set($lockKey, 1, 45);

try {
    runBotTurnForRoom($storage, $roomId);
} finally {
    $storage->delete($lockKey);
}
