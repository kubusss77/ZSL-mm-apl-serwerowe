<?php

require_once __DIR__ . '/../bootstrap.php';

/** Zwraca stan pokoju przefiltrowany dla konkretnego gracza. */
function actionGetState($storage): void {
  $roomId = (int) ($_GET['room_id'] ?? 0);
  $playerId = (int) ($_GET['player_id'] ?? 0);
  $token = $_GET['token'] ?? '';

  $room = getRoom($storage, $roomId);
  if ($room === null) {
    echo json_encode(['status' => 'error', 'message' => 'Brak pokoju']);
    exit();
  }

  $isPlayerInRoom = isset($room['players'][$playerId]) && $room['players'][$playerId]['token'] === $token;
  $scheduleBot = false;

  if ($room['status'] === 'playing' && ($room['game_state']['winner'] ?? null) === null) {
    $currentId = (int) $room['game_state']['current_turn'];
    $currentPlayer = $room['players'][$currentId] ?? null;
    $timedOut = time() > (int) $room['game_state']['turn_deadline'];

    if ($timedOut && $currentPlayer !== null) {
      if (isBotPlayer($currentPlayer)) {
        $room =
          updateRoom($storage, $roomId, function (array $liveRoom) {
            $liveRoom['game_state']['bot_ready_at'] = time();
            return $liveRoom;
          }) ?? $room;
      } else {
        $room =
          updateRoom($storage, $roomId, function (array $liveRoom) use ($currentId) {
            advanceTurn($liveRoom, $currentId);
            return $liveRoom;
          }) ?? $room;
      }
    }

    if (roomNeedsBotProcessing($room)) {
      $scheduleBot = true;
    }
  }

  echo json_encode(filterRoomForPlayer($room, $playerId, $isPlayerInRoom));

  if ($scheduleBot) {
    scheduleBotProcessing($storage, $roomId);
  }
}
