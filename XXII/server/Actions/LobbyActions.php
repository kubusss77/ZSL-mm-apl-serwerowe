<?php

require_once __DIR__ . '/../bootstrap.php';

/** Ustawia gotowość gracza i startuje grę, gdy lobby spełnia warunki. */
function actionReady($storage): void
{
    $roomId = (int)($_GET['room_id'] ?? 0);
    $playerId = (int)($_GET['player_id'] ?? 0);
    $token = $_GET['token'] ?? '';
    $scheduleBot = false;

    $authorized = false;

    updateRooms($storage, function ($rooms) use ($roomId, $playerId, $token, $storage, &$scheduleBot, &$authorized) {
        if (!isset($rooms[$roomId]['players'][$playerId])) {
            return $rooms;
        }

        if (($rooms[$roomId]['players'][$playerId]['token'] ?? '') !== $token) {
            return $rooms;
        }

        $authorized = true;
        $rooms[$roomId]['players'][$playerId]['is_ready'] = ($_GET['ready'] ?? 'true') === 'true';
        $players = (array)$rooms[$roomId]['players'];

        $allReady = count($players) >= 2 && !array_filter(
            $players,
            fn($p) => empty($p['is_bot']) && empty($p['is_ready'])
        );
        if ($allReady) {
            $rooms[$roomId]['status'] = 'playing';
            initializeGame($rooms[$roomId]);
            maybeBotChatGameStart($storage, $roomId, $rooms[$roomId]);
            $scheduleBot = roomNeedsBotProcessing($rooms[$roomId]);
        }

        return $rooms;
    });

    if (!$authorized) {
        echo json_encode(['status' => 'error', 'message' => 'Błąd autoryzacji']);
        return;
    }

    echo json_encode(['status' => 'success']);

    if ($scheduleBot) {
        scheduleBotProcessing($storage, $roomId);
    }
}
