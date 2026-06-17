<?php

/** Przygotowuje początkową talię, ręce graczy, planszę i pierwszą turę */
function initializeGame(&$room) {
  $colors = ['red', 'green', 'blue', 'yellow', 'purple'];
  shuffle($colors);

  $playerIndex = 0;
  foreach ($room['players'] as $id => $player) {
    $room['players'][$id]['color'] = $colors[$playerIndex++];
    $room['players'][$id]['cards'] = [];
  }

  $deck = [];
  foreach ($colors as $col) {
    for ($i = 0; $i < 5; $i++) {
      $deck[] = ['id' => bin2hex(random_bytes(4)), 'color' => $col, 'move' => '+1'];
    }
    $deck[] = ['id' => bin2hex(random_bytes(4)), 'color' => $col, 'move' => '+2'];
    for ($i = 0; $i < 2; $i++) {
      $deck[] = ['id' => bin2hex(random_bytes(4)), 'color' => $col, 'move' => '-1'];
    }
  }

  for ($i = 0; $i < 2; $i++) {
    $deck[] = ['id' => bin2hex(random_bytes(4)), 'color' => 'any', 'move' => '+2LF'];
  }
  for ($i = 0; $i < 3; $i++) {
    $deck[] = ['id' => bin2hex(random_bytes(4)), 'color' => 'any', 'move' => '+1LF'];
  }
  for ($i = 0; $i < 5; $i++) {
    $deck[] = ['id' => bin2hex(random_bytes(4)), 'color' => 'any', 'move' => '+1'];
  }
  for ($i = 0; $i < 2; $i++) {
    $deck[] = ['id' => bin2hex(random_bytes(4)), 'color' => 'any', 'move' => '-1'];
  }

  shuffle($deck);

  foreach ($room['players'] as $id => $player) {
    for ($i = 0; $i < 5; $i++) {
      $room['players'][$id]['cards'][] = array_pop($deck);
    }
  }

  $board = array_fill(0, 10, []);
  $board[0] = ['red', 'green', 'blue', 'yellow', 'purple'];
  shuffle($board[0]);

  $firstPlayerId = (int) array_key_first($room['players']);
  $room['game_state'] = [
    'deck' => $deck,
    'board' => $board,
    'current_turn' => $firstPlayerId,
    'winner' => null,
    'cards_played_count' => 0,
    'last_played_card' => null,
    'turn_deadline' => time() + 30,
  ];
  setTurnTiming($room, $firstPlayerId);
}

/** Ustawia limit czasu tury i znacznik gotowości bota */
function setTurnTiming(array &$room, int $playerId): void {
  $room['game_state']['turn_deadline'] = time() + 30;

  if (!empty($room['players'][$playerId]['is_bot'])) {
    $room['game_state']['bot_ready_at'] = time();
  } else {
    unset($room['game_state']['bot_ready_at']);
  }
}

/** Tłumaczy kartę i wybór koloru na konkretny ruch żółwia */
function resolveMove(array $card, array &$board, string $chosenColor, &$errorMessage): ?array {
  $moveStr = trim((string) $card['move']);
  $targetColor = '';
  $currentPos = null;
  $steps = 1;

  if (str_contains($moveStr, 'LF')) {
    $lowestOccupiedPos = null;
    foreach ($board as $pos => $stack) {
      if (!empty($stack) && $pos < 9) {
        $lowestOccupiedPos = $pos;
        break;
      }
    }

    if ($lowestOccupiedPos === null) {
      $errorMessage = 'Błąd planszy: brak żółwi w grze';
      return null;
    }

    $currentPos = $lowestOccupiedPos;
    $targetColor = $board[$currentPos][0];
    $steps = str_contains($moveStr, '2') ? 2 : 1;
  } else {
    $targetColor = $card['color'] === 'any' ? $chosenColor : $card['color'];
    if (!in_array($targetColor, ['red', 'green', 'blue', 'yellow', 'purple'])) {
      $errorMessage = 'Nieprawidłowy wybór koloru żółwia';
      return null;
    }

    foreach ($board as $pos => $stack) {
      if (in_array($targetColor, $stack)) {
        $currentPos = $pos;
        break;
      }
    }

    if ($currentPos === null) {
      $errorMessage = 'Nie znaleziono wybranego żółwia na planszy';
      return null;
    }

    $steps = match (true) {
      $moveStr === '+2' || $moveStr === '2' => 2,
      $moveStr === '-1' || str_contains($moveStr, '-') => -1,
      default => 1,
    };
  }

  return ['targetColor' => $targetColor, 'currentPos' => $currentPos, 'steps' => $steps];
}

/** Przenosi żółwia wraz ze stosem na nowe pole planszy */
function applyMove(array &$board, string $targetColor, int $currentPos, int $steps, &$errorMessage): ?int {
  if ($currentPos === 0 && $steps < 0) {
    $errorMessage = 'Żółw na polu startowym nie może się cofać!';
    return null;
  }

  $stack = $board[$currentPos];
  $turtleIdx = array_search($targetColor, $stack, true);
  if ($turtleIdx === false) {
    $errorMessage = 'Nie znaleziono wybranego żółwia w stosie';
    return null;
  }

  $movingTurtles = array_slice($stack, $turtleIdx);
  $newPos = max(0, min(9, $currentPos + $steps));

  $board[$currentPos] = array_slice($stack, 0, $turtleIdx);
  $board[$newPos] = array_merge($board[$newPos], $movingTurtles);

  return $newPos;
}

/** Sprawdza kolor na mecie i zapisuje zwycięzcę pokoju */
function checkWinner(array &$room): void {
  $board = &$room['game_state']['board'];
  if (empty($board[9])) {
    return;
  }

  $winningColor = $board[9][0];
  $winnerName = 'Nikt';
  $normalize = fn($c) => $c === 'violet' ? 'purple' : $c;

  foreach ($room['players'] as $playerData) {
    if ($normalize($playerData['color']) === $normalize($winningColor)) {
      $winnerName = $playerData['nickname'];
      break;
    }
  }

  $room['game_state']['winner'] = ['color' => $winningColor, 'winner_name' => $winnerName];
}

/** Przekazuje turę następnemu graczowi w kolejności pokoju */
function advanceTurn(array &$room, int $currentPlayerId): void {
  $playerIds = array_keys($room['players']);
  $currentIdx = array_search($currentPlayerId, $playerIds);
  $nextId = (int) $playerIds[($currentIdx + 1) % count($playerIds)];
  $room['game_state']['current_turn'] = $nextId;
  setTurnTiming($room, $nextId);
}
