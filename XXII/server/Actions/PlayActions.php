<?php

require_once __DIR__ . '/../bootstrap.php';

/** Obsługuje zagranie karty przez aktualnego gracza */
function actionPlayCard($storage): void {
  $roomId = (int) ($_POST['room_id'] ?? 0);
  $playerId = (int) ($_POST['player_id'] ?? 0);
  $token = $_POST['token'] ?? '';
  $cardId = $_POST['card_id'] ?? '';
  $chosenColor = $_POST['chosen_color'] ?? '';

  $moveExecuted = false;
  $errorMessage = 'Nieznany błąd';
  $scheduleBot = false;

  $updatedRoom = updateRoom($storage, $roomId, function (array $room) use (
    $playerId,
    $token,
    $cardId,
    $chosenColor,
    &$moveExecuted,
    &$errorMessage,
    &$scheduleBot,
  ) {
    if (!isset($room['players'][$playerId]) || $room['players'][$playerId]['token'] !== $token) {
      $errorMessage = 'Błąd autoryzacji tokenu';
      return $room;
    }

    $result = executePlayForPlayer($room, $playerId, $cardId, $chosenColor);
    $moveExecuted = $result['success'];
    if (!$moveExecuted) {
      $errorMessage = $result['message'] ?? $errorMessage;
      return $room;
    }

    $scheduleBot = roomNeedsBotProcessing($room);
    return $room;
  });

  if ($updatedRoom === null && !$moveExecuted) {
    echo json_encode(['status' => 'error', 'message' => 'Brak pokoju']);
    return;
  }

  echo json_encode(
    $moveExecuted
      ? ['status' => 'success', 'bot_scheduled' => $scheduleBot]
      : ['status' => 'error', 'message' => $errorMessage],
  );

  if ($moveExecuted && $scheduleBot) {
    scheduleBotProcessing($storage, $roomId);
  }
}
