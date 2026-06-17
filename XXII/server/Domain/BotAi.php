<?php

require_once __DIR__ . '/PlayEngine.php';
require_once __DIR__ . '/BotChat.php';

const BOT_SEARCH_DEPTH_MAX = 14;
const BOT_SEARCH_DEPTH_MIN = 6;
const BOT_TOP_MOVES_PER_NODE = 12;
const BOT_TIME_BUDGET_SEC = 15.0;

/** Rozpoznaje gracza sterowanego przez serwer */
function isBotPlayer(array $player): bool {
  return !empty($player['is_bot']);
}

/** Wybiera najlepszy ruch bota w ramach limitu czasu */
function chooseBotMove(array $room, int $botPlayerId): ?array {
  $deadline = microtime(true) + BOT_TIME_BUDGET_SEC;
  $moves = enumerateLegalMoves($room, $botPlayerId);
  if (empty($moves)) {
    return null;
  }

  if (count($moves) === 1) {
    return $moves[0];
  }

  $myColor = normalizeTurtleColor($room['players'][$botPlayerId]['color']);

  $winMove = findWinningMove($room, $botPlayerId, $myColor, $moves);
  if ($winMove !== null) {
    return $winMove;
  }

  if (opponentHasWinningMove($room, $botPlayerId)) {
    $blockMove = findBlockOpponentWinMove($room, $botPlayerId, $moves);
    if ($blockMove !== null) {
      return $blockMove;
    }
  }

  $moves = orderMovesByHeuristic($room, $botPlayerId, $moves);
  $bestMove = $moves[0];

  for ($depth = BOT_SEARCH_DEPTH_MIN; $depth <= BOT_SEARCH_DEPTH_MAX; $depth++) {
    if (microtime(true) >= $deadline - 0.08) {
      break;
    }

    $depthBestMove = null;
    $depthBestScore = -INF;

    foreach ($moves as $move) {
      if (microtime(true) >= $deadline) {
        break 2;
      }

      $sim = simulatePlay($room, $botPlayerId, $move);
      if ($sim === null) {
        continue;
      }

      $scores = maxNScores($sim, $depth - 1, $deadline);
      $score = $scores[$botPlayerId] ?? evaluatePosition($sim, $botPlayerId);

      if (opponentHasWinningMove($sim, $botPlayerId)) {
        $score -= 14000;
      }

      if ($score > $depthBestScore) {
        $depthBestScore = $score;
        $depthBestMove = $move;
      }
    }

    if ($depthBestMove !== null) {
      $bestMove = $depthBestMove;
    }
  }

  return $bestMove;
}

/** Szuka ruchu, który natychmiast daje zwycięstwo */
function findWinningMove(array $room, int $playerId, string $myColor, array $moves): ?array {
  foreach ($moves as $move) {
    $sim = simulatePlay($room, $playerId, $move);
    if ($sim === null) {
      continue;
    }
    $winner = $sim['game_state']['winner'] ?? null;
    if ($winner !== null && normalizeTurtleColor($winner['color']) === $myColor) {
      return $move;
    }
  }
  return null;
}

/** Sprawdza, czy któryś przeciwnik ma natychmiastowe zwycięstwo */
function opponentHasWinningMove(array $room, int $excludePlayerId): bool {
  foreach ($room['players'] as $opId => $_player) {
    $opId = (int) $opId;
    if ($opId === $excludePlayerId) {
      continue;
    }
    foreach (enumerateLegalMoves($room, $opId) as $opMove) {
      $sim = simulatePlay($room, $opId, $opMove);
      if ($sim !== null && ($sim['game_state']['winner'] ?? null) !== null) {
        return true;
      }
    }
  }
  return false;
}

/** Szuka ruchu blokującego najbliższe zwycięstwo przeciwnika */
function findBlockOpponentWinMove(array $room, int $botPlayerId, array $moves): ?array {
  $ordered = orderMovesByHeuristic($room, $botPlayerId, $moves);
  $bestMove = null;
  $bestScore = -INF;

  foreach ($ordered as $move) {
    $sim = simulatePlay($room, $botPlayerId, $move);
    if ($sim === null || opponentHasWinningMove($sim, $botPlayerId)) {
      continue;
    }

    $score = evaluatePosition($sim, $botPlayerId);
    if ($score > $bestScore) {
      $bestScore = $score;
      $bestMove = $move;
    }
  }

  return $bestMove;
}

/** Rekurencyjnie ocenia grę wieloosobową metodą max-n */
function maxNScores(array $room, int $depth, float $deadline): array {
  $playerIds = array_map('intval', array_keys($room['players']));

  if ($depth <= 0 || microtime(true) >= $deadline || $room['game_state']['winner'] !== null) {
    return scoreAllPlayers($room, $playerIds);
  }

  $currentId = (int) $room['game_state']['current_turn'];
  $moves = enumerateLegalMoves($room, $currentId);
  if (empty($moves)) {
    return scoreAllPlayers($room, $playerIds);
  }

  $moves = array_slice(orderMovesByHeuristic($room, $currentId, $moves), 0, BOT_TOP_MOVES_PER_NODE);

  $bestScores = null;
  $bestForCurrent = -INF;

  foreach ($moves as $move) {
    if (microtime(true) >= $deadline) {
      break;
    }

    $sim = simulatePlay($room, $currentId, $move);
    if ($sim === null) {
      continue;
    }

    $childScores = maxNScores($sim, $depth - 1, $deadline);
    $currentScore = $childScores[$currentId] ?? evaluatePosition($sim, $currentId);

    if ($currentScore > $bestForCurrent) {
      $bestForCurrent = $currentScore;
      $bestScores = $childScores;
    }
  }

  return $bestScores ?? scoreAllPlayers($room, $playerIds);
}

/** Liczy punktację pozycji dla wszystkich graczy */
function scoreAllPlayers(array $room, array $playerIds): array {
  $board = $room['game_state']['board'];
  $index = buildTurtleIndex($board);
  $scores = [];
  foreach ($playerIds as $pid) {
    $scores[$pid] = evaluatePosition($room, $pid, $index);
  }
  return $scores;
}

/** Sortuje ruchy od najbardziej obiecujących według heurystyki */
function orderMovesByHeuristic(array $room, int $playerId, array $moves): array {
  $scored = [];
  foreach ($moves as $move) {
    $sim = simulatePlay($room, $playerId, $move);
    $scored[] = [
      'move' => $move,
      'score' => $sim ? evaluatePosition($sim, $playerId) : -INF,
    ];
  }

  usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

  return array_column($scored, 'move');
}

/** Ocenia jakość pozycji z perspektywy jednego gracza */
function evaluatePosition(array $room, int $playerId, ?array $turtleIndex = null): float {
  if (!isset($room['players'][$playerId]['color'])) {
    return 0;
  }

  $gs = $room['game_state'];
  $myColor = normalizeTurtleColor($room['players'][$playerId]['color']);
  $board = $gs['board'];
  $index = $turtleIndex ?? buildTurtleIndex($board);

  if ($gs['winner'] !== null) {
    $winColor = normalizeTurtleColor($gs['winner']['color']);
    if ($myColor === $winColor) {
      return 100000;
    }
    $mine = getTurtleFromIndex($index, $myColor);
    return -80000 + $mine['pos'] * 500 + (10 - $mine['stack']) * 30;
  }

  $myPos = getTurtleFromIndex($index, $myColor)['pos'];
  $myStack = getTurtleFromIndex($index, $myColor)['stack'];
  $score = $myPos * 1500.0;

  if ($myPos >= 7) {
    $stackSize = count($board[$myPos]);
    $score += (10 - $myStack) * 620;
    $score += ($stackSize - $myStack) * 70;
    if ($myPos === 8) {
      $score += 500;
    }
    if ($myPos === 9) {
      $score += 2000;
    }
  } elseif ($myPos >= 5) {
    $score += $myStack * 35;
    if ($myStack > 0 && $myStack < count($board[$myPos]) - 1) {
      $score += 80;
    }
  } elseif ($myPos >= 3) {
    $score += $myStack * 28;
  } else {
    $score += $myStack * 10;
  }

  $leaderPos = $myPos;
  foreach ($room['players'] as $oid => $player) {
    if ((int) $oid === $playerId) {
      continue;
    }
    $op = getTurtleFromIndex($index, normalizeTurtleColor($player['color']));
    $opPos = $op['pos'];
    $opStack = $op['stack'];
    $leaderPos = max($leaderPos, $opPos);

    if ($opPos > $myPos) {
      $score -= ($opPos - $myPos) * 1400;
      if ($opPos >= 7) {
        $score -= (10 - $opStack) * 250;
      }
      if ($opPos >= 8) {
        $score -= 900;
      }
      if ($opPos === 9) {
        $score -= 5000;
      }
    } elseif ($opPos === $myPos && $opStack < $myStack) {
      $score -= ($myStack - $opStack) * 130;
    }

    if ($opPos >= 6 && $opStack === 0) {
      $score -= 350;
    }
  }

  if ($myPos === $leaderPos && $myPos >= 6) {
    $score += 200;
  }

  $score += evaluateHandPotential($room, $playerId, $myColor, $myPos, $myStack, $index);
  $score += evaluateStackTactics($board, $myColor, $myPos, $myStack);

  return $score;
}

/** Dodaje premię za korzystne ułożenie żółwia w stosie */
function evaluateStackTactics(array $board, string $myColor, int $myPos, int $myStack): float {
  if ($myPos <= 0 || $myPos >= 9) {
    return 0;
  }

  $stack = $board[$myPos];
  $bonus = 0.0;

  if ($myStack > 0 && $myStack < count($stack) - 1) {
    $bonus += 90;
  }

  if ($myStack === 0 && count($stack) > 1 && $myPos >= 6) {
    $bonus += 180;
  }

  return $bonus;
}

/** Ocenia potencjał kart trzymanych przez gracza */
function evaluateHandPotential(
  array $room,
  int $playerId,
  string $myColor,
  int $myPos,
  int $myStack,
  array $turtleIndex,
): float {
  $bonus = 0.0;

  foreach ($room['players'][$playerId]['cards'] as $card) {
    $moveStr = trim((string) $card['move']);

    if (str_contains($moveStr, 'LF')) {
      $lastColor = getLastTurtleColor($room['game_state']['board']);
      if ($lastColor !== null && normalizeTurtleColor($lastColor) === $myColor) {
        $bonus += str_contains($moveStr, '2') ? 80 : 45;
      }
      continue;
    }

    $targets = $card['color'] === 'any' ? TURTLE_COLORS : [$card['color']];
    foreach ($targets as $target) {
      if ($target !== $myColor) {
        continue;
      }
      $steps = match (true) {
        $moveStr === '+2' || $moveStr === '2' => 2,
        $moveStr === '-1' || str_contains($moveStr, '-') => -1,
        default => 1,
      };
      if ($steps > 0) {
        $bonus += $steps * 45 + max(0, 9 - $myPos) * 8;
        if ($myPos >= 6 && $myStack > 0) {
          $bonus += 35;
        }
        if ($myPos >= 7 && $myStack === 0) {
          $bonus += 60;
        }
      } elseif ($myPos > 0) {
        $bonus -= 10;
      }
    }

    foreach ($targets as $target) {
      if ($target === $myColor) {
        continue;
      }
      $opPos = getTurtleFromIndex($turtleIndex, $target)['pos'];
      if ($moveStr === '-1' || str_contains($moveStr, '-')) {
        if ($opPos >= 5 && $opPos > $myPos) {
          $bonus += 40 + ($opPos - $myPos) * 15;
        }
        if ($opPos >= 7) {
          $bonus += 35;
        }
      } elseif ($opPos >= $myPos && $opPos >= 5 && ($moveStr === '+2' || $moveStr === '2')) {
        $bonus -= 20;
      } elseif ($opPos >= $myPos && $opPos >= 6) {
        $bonus += 20;
      }
    }
  }

  return $bonus;
}

/** Sprawdza, czy bieżąca tura należy do gotowego bota */
function roomNeedsBotProcessing(array $room): bool {
  if (($room['status'] ?? '') !== 'playing') {
    return false;
  }
  if (($room['game_state']['winner'] ?? null) !== null) {
    return false;
  }

  $currentId = (int) ($room['game_state']['current_turn'] ?? 0);
  $player = $room['players'][$currentId] ?? null;

  if ($player === null || !isBotPlayer($player)) {
    return false;
  }

  $readyAt = (int) ($room['game_state']['bot_ready_at'] ?? 0);
  return $readyAt === 0 || time() >= $readyAt;
}

/** Uruchamia obsługę tur bota dla wskazanego pokoju */
function processBotTurns(array &$room, int $roomId, Storage $storage): void {
  runBotTurnForRoom($storage, $roomId);
}

/** Wybiera i zapisuje ruch bota poza blokadą magazynu pokojów */
function runBotTurnForRoom(Storage $storage, int $roomId): void {
  $room = getRoom($storage, $roomId);
  if ($room === null || !roomNeedsBotProcessing($room)) {
    return;
  }

  $currentId = (int) $room['game_state']['current_turn'];
  $player = $room['players'][$currentId] ?? null;
  if ($player === null || !isBotPlayer($player)) {
    return;
  }

  maybeBotChatBeforeMove($storage, $roomId, $room, $currentId);

  $roomBefore = cloneRoomState($room);
  $move = chooseBotMove($room, $currentId);

  $hadThreat = opponentHasWinningMove($roomBefore, $currentId);

  $applied = updateRoom($storage, $roomId, function (array $liveRoom) use ($currentId, $move) {
    if (!roomNeedsBotProcessing($liveRoom)) {
      return $liveRoom;
    }
    if ((int) $liveRoom['game_state']['current_turn'] !== $currentId) {
      return $liveRoom;
    }

    if ($move === null) {
      advanceTurn($liveRoom, $currentId);
      return $liveRoom;
    }

    $result = executePlayForPlayer($liveRoom, $currentId, $move['card_id'], $move['chosen_color']);
    if (!$result['success']) {
      advanceTurn($liveRoom, $currentId);
    }
    return $liveRoom;
  });

  if ($applied === null) {
    return;
  }

  if ($hadThreat && !opponentHasWinningMove($applied, $currentId)) {
    maybeBotChat($storage, $roomId, $applied, $currentId, 'blocked_threat', 75);
  } else {
    maybeBotChatAfterMove($storage, $roomId, $applied, $currentId, $roomBefore);
  }
}
