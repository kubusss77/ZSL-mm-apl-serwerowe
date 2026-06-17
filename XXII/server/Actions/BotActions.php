<?php

require_once __DIR__ . '/../bootstrap.php';

/** Dodaje gotowego bota do lobby wskazanego przez gracza */
function actionAddBot($storage): void {
  $roomId = (int) ($_GET['room_id'] ?? 0);
  $playerId = (int) ($_GET['player_id'] ?? 0);
  $token = $_GET['token'] ?? '';

  $botId = null;
  $errorMessage = 'Nie udało się dodać bota';

  updateRooms($storage, function ($rooms) use ($roomId, $playerId, $token, &$botId, &$errorMessage) {
    if (!isset($rooms[$roomId])) {
      $errorMessage = 'Brak pokoju';
      return $rooms;
    }

    $room = &$rooms[$roomId];
    if (($room['status'] ?? 'lobby') !== 'lobby') {
      $errorMessage = 'Boty można dodawać tylko w poczekalni';
      return $rooms;
    }

    if (!isset($room['players'][$playerId]) || $room['players'][$playerId]['token'] !== $token) {
      $errorMessage = 'Błąd autoryzacji.';
      return $rooms;
    }

    if (count((array) $room['players']) >= 5) {
      $errorMessage = 'Pokój jest pełny';
      return $rooms;
    }

    $botNumber = 1;
    foreach ($room['players'] as $p) {
      if (!empty($p['is_bot'])) {
        $botNumber++;
      }
    }

    $newId = count((array) $room['players']) + 1;
    $room['players'][$newId] = [
      'nickname' => 'ŻółwikBot ' . $botNumber,
      'is_ready' => true,
      'is_bot' => true,
      'token' => 'bot_' . bin2hex(random_bytes(12)),
    ];

    $botId = $newId;
    return $rooms;
  });

  echo json_encode(
    $botId !== null ? ['status' => 'success', 'botId' => $botId] : ['status' => 'error', 'message' => $errorMessage],
  );
}
