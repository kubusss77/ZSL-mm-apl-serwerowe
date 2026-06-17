<?php

/** Dołącza gracza do istniejącego pokoju lobby albo tworzy nowy pokój */
function actionJoin($storage, string $sessionToken): void {
  $nickname = trim($_GET['nickname'] ?? '');
  if (!$nickname) {
    echo json_encode(['status' => 'error', 'message' => 'Brak nicku']);
    exit();
  }

  $result = [];
  updateRooms($storage, function ($rooms) use ($nickname, $sessionToken, &$result, $storage) {
    $assignedRoomId = null;
    foreach ($rooms as $roomId => $room) {
      if (($room['status'] ?? 'lobby') === 'lobby' && count((array) $room['players']) < 5) {
        $assignedRoomId = $roomId;
        break;
      }
    }

    if ($assignedRoomId === null) {
      $assignedRoomId = $storage->increment('global_room_counter');
      $rooms[$assignedRoomId] = ['status' => 'lobby', 'players' => []];
    }

    $playerId = count((array) $rooms[$assignedRoomId]['players']) + 1;
    $rooms[$assignedRoomId]['players'][$playerId] = [
      'nickname' => $nickname,
      'is_ready' => false,
      'token' => $sessionToken,
    ];

    $result = ['roomId' => (int) $assignedRoomId, 'playerId' => (int) $playerId];
    return $rooms;
  });

  echo json_encode([
    'status' => 'success',
    'roomId' => $result['roomId'],
    'playerId' => $result['playerId'],
    'token' => $sessionToken,
  ]);
}

/** Odtwarza sesję gracza na podstawie identyfikatora pokoju, gracza i tokenu */
function actionReconnect($storage): void {
  $roomId = (int) ($_GET['room_id'] ?? 0);
  $playerId = (int) ($_GET['player_id'] ?? 0);
  $token = $_GET['token'] ?? '';

  $rooms = $storage->get('rooms') ?: [];
  if (isset($rooms[$roomId]['players'][$playerId]) && $rooms[$roomId]['players'][$playerId]['token'] === $token) {
    echo json_encode([
      'status' => 'success',
      'roomId' => $roomId,
      'playerId' => $playerId,
      'gameStatus' => $rooms[$roomId]['status'],
    ]);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Sesja wygasła']);
  }
}
