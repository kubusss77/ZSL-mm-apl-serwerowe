<?php

require_once __DIR__ . '/../Domain/BotAi.php';

/** Uruchamia asynchronicznego workera bota, jeśli pokój tego wymaga */
function scheduleBotProcessing(Storage $storage, int $roomId): void {
  $lockKey = 'bot_lock_' . $roomId;
  if ($storage->get($lockKey)) {
    return;
  }

  $room = getRoom($storage, $roomId);
  if ($room === null || !roomNeedsBotProcessing($room)) {
    return;
  }

  $php = defined('PHP_BINARY') ? PHP_BINARY : 'php';
  $worker = __DIR__ . '/../bot_worker.php';

  if (!function_exists('exec') || !is_readable($worker)) {
    return;
  }

  $cmd = escapeshellarg($php) . ' ' . escapeshellarg($worker) . ' ' . $roomId;
  if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    pclose(popen('start /B ' . $cmd, 'r'));
  } else {
    exec($cmd . ' > /dev/null 2>&1 &');
  }
}
