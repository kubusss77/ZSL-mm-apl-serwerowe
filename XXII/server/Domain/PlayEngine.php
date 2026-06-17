<?php

const TURTLE_COLORS = ['red', 'green', 'blue', 'yellow', 'purple'];

/** Normalizuje alternatywną nazwę koloru do formatu serwera. */
function normalizeTurtleColor(string $color): string {
  return $color === 'violet' ? 'purple' : $color;
}

/** Buduje indeks pozycji i wysokości stosu dla każdego żółwika */
function buildTurtleIndex(array $board): array {
  $index = [];
  foreach ($board as $pos => $stack) {
    foreach ($stack as $stackIdx => $color) {
      $index[normalizeTurtleColor($color)] = [
        'pos' => (int) $pos,
        'stack' => (int) $stackIdx,
      ];
    }
  }
  return $index;
}

/** Zwraca pozycję żółwia z gotowego indeksu planszy */
function getTurtleFromIndex(array $index, string $color): array {
  return $index[normalizeTurtleColor($color)] ?? ['pos' => 0, 'stack' => 0];
}

/** Znajduje numer pola, na którym stoi wybrany żółw */
function getTurtlePosition(array $board, string $color): int {
  $color = normalizeTurtleColor($color);
  foreach ($board as $pos => $stack) {
    if (in_array($color, $stack, true)) {
      return (int) $pos;
    }
  }
  return 0;
}

/** Zwraca indeks żółwia w stosie na jego aktualnym polu */
function getStackIndex(array $board, string $color): int {
  $color = normalizeTurtleColor($color);
  $pos = getTurtlePosition($board, $color);
  $idx = array_search($color, $board[$pos], true);
  return $idx === false ? 0 : (int) $idx;
}

/** Znajduje najniższe zajęte pole przed metą */
function getLowestOccupiedField(array $board): ?int {
  foreach ($board as $pos => $stack) {
    if (!empty($stack) && $pos < 9) {
      return (int) $pos;
    }
  }
  return null;
}

/** Zwraca ostatniego żółwia na torze: dół stosu na najniższym zajętym polu. */
function getLastTurtleColor(array $board): ?string {
  $pos = getLowestOccupiedField($board);
  if ($pos === null) {
    return null;
  }
  return $board[$pos][0] ?? null;
}

/** Sprawdza, czy karta może zostać zagrana przy aktualnej planszy */
function isMoveLegal(array $card, array $board, string $chosenColor, ?string &$errorMessage = null): bool {
  $errorMessage = '';
  $move = resolveMove($card, $board, $chosenColor, $errorMessage);
  if ($move === null) {
    return false;
  }
  if ($move['currentPos'] === null) {
    return false;
  }
  if ($move['currentPos'] === 0 && $move['steps'] < 0) {
    return false;
  }
  return true;
}

/** Wylicza wszystkie legalne zagrania dostępne dla gracza */
function enumerateLegalMoves(array $room, int $playerId): array {
  if (!isset($room['players'][$playerId]['cards'])) {
    return [];
  }

  $board = $room['game_state']['board'];
  $moves = [];
  $seen = [];

  foreach ($room['players'][$playerId]['cards'] as $card) {
    $moveStr = trim((string) $card['move']);

    if (str_contains($moveStr, 'LF')) {
      $key = $card['id'] . '|lf';
      if (!isset($seen[$key]) && isMoveLegal($card, $board, '')) {
        $moves[] = ['card_id' => $card['id'], 'chosen_color' => ''];
        $seen[$key] = true;
      }
      continue;
    }

    if ($card['color'] === 'any') {
      foreach (TURTLE_COLORS as $color) {
        $key = $card['id'] . '|' . $color;
        if (isset($seen[$key])) {
          continue;
        }
        if (isMoveLegal($card, $board, $color)) {
          $moves[] = ['card_id' => $card['id'], 'chosen_color' => $color];
          $seen[$key] = true;
        }
      }
      continue;
    }

    $key = $card['id'] . '|' . $card['color'];
    if (!isset($seen[$key]) && isMoveLegal($card, $board, $card['color'])) {
      $moves[] = ['card_id' => $card['id'], 'chosen_color' => $card['color']];
      $seen[$key] = true;
    }
  }

  return $moves;
}

/** Wykonuje zagranie gracza, dobiera kartę i przesuwa turę */
function executePlayForPlayer(array &$room, int $playerId, string $cardId, string $chosenColor): array {
  if ((int) $room['game_state']['current_turn'] !== $playerId) {
    return ['success' => false, 'message' => 'To nie jest Twoja tura'];
  }
  if ($room['game_state']['winner'] !== null) {
    return ['success' => false, 'message' => 'Gra została już zakończona'];
  }

  $cardKey = null;
  $card = null;
  foreach ($room['players'][$playerId]['cards'] as $idx => $handCard) {
    if (($handCard['id'] ?? '') === $cardId) {
      $cardKey = $idx;
      $card = $handCard;
      break;
    }
  }
  if ($card === null) {
    return ['success' => false, 'message' => 'Nie posiadasz takiej karty'];
  }
  $errorMessage = 'Nieznany błąd';
  $board = &$room['game_state']['board'];

  $move = resolveMove($card, $board, $chosenColor, $errorMessage);
  if ($move === null) {
    return ['success' => false, 'message' => $errorMessage];
  }

  ['targetColor' => $targetColor, 'currentPos' => $currentPos, 'steps' => $steps] = $move;

  if ($currentPos !== null) {
    $newPos = applyMove($board, $targetColor, $currentPos, $steps, $errorMessage);
    if ($newPos === null) {
      return ['success' => false, 'message' => $errorMessage];
    }

    if ($newPos === 9 && empty($room['game_state']['winner'])) {
      checkWinner($room);
    }
  }

  $room['game_state']['last_played_card'] = $card;
  $room['game_state']['cards_played_count']++;

  unset($room['players'][$playerId]['cards'][$cardKey]);
  $room['players'][$playerId]['cards'] = array_values($room['players'][$playerId]['cards']);

  if (!empty($room['game_state']['deck'])) {
    $room['players'][$playerId]['cards'][] = array_pop($room['game_state']['deck']);
  }

  if ($room['game_state']['winner'] === null) {
    advanceTurn($room, $playerId);
  }

  return ['success' => true, 'message' => null];
}

/** Symuluje zagranie bez modyfikowania oryginalnego pokoju */
function simulatePlay(array $room, int $playerId, array $move): ?array {
  $sim = cloneRoomState($room);
  if (!executePlayForPlayer($sim, $playerId, $move['card_id'], $move['chosen_color'])['success']) {
    return null;
  }
  return $sim;
}

/** Tworzy kopię stanu pokoju bez współdzielonych referencji tablic */
function cloneRoomState(array $room): array {
  $players = [];
  foreach ($room['players'] as $id => $player) {
    $players[$id] = $player;
    $players[$id]['cards'] = isset($player['cards']) ? array_values($player['cards']) : [];
  }

  $gs = $room['game_state'];

  return [
    'status' => $room['status'] ?? 'playing',
    'players' => $players,
    'game_state' => [
      'deck' => array_values($gs['deck'] ?? []),
      'board' => array_map(static fn(array $stack): array => array_values($stack), $gs['board']),
      'current_turn' => $gs['current_turn'],
      'winner' => $gs['winner'] ?? null,
      'cards_played_count' => $gs['cards_played_count'] ?? 0,
      'last_played_card' => $gs['last_played_card'] ?? null,
      'turn_deadline' => $gs['turn_deadline'] ?? 0,
      'bot_ready_at' => $gs['bot_ready_at'] ?? null,
    ],
  ];
}
