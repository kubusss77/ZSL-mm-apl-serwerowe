<?php

const BOT_CHAT_COOLDOWN_SEC = 3;
const BOT_CHAT_CHANCE = 0.68;
const BOT_CHAT_BEFORE_MOVE_CHANCE = 50;
const BOT_CHAT_RANDOM_FALLBACK_CHANCE = 32;
const BOT_CHAT_GAME_START_CHANCE = 82;

/** Dopisuje tekst od bota do czatu pokoju */
function appendBotChatMessage(Storage $storage, int $roomId, string $nickname, string $text): void {
  require_once __DIR__ . '/../Support/ChatStore.php';
  appendChatMessage($storage, $roomId, $nickname, $text);
}

/** Sprawdza, czy cooldown czatu bota minął */
function canBotSendChat(Storage $storage, int $roomId): bool {
  $last = (int) ($storage->get('bot_chat_cooldown_' . $roomId) ?? 0);
  return time() - $last >= BOT_CHAT_COOLDOWN_SEC;
}

/** Zapisuje czas ostatniej wiadomości bota */
function markBotChatSent(Storage $storage, int $roomId): void {
  $storage->set('bot_chat_cooldown_' . $roomId, time(), 300);
}

/** Losuje krótką kwestię bota pasującą do sytuacji */
function pickBotChatLine(string $situation): string {
  static $lines = null;
  if ($lines === null) {
    $lines = [
      'game_start' => ['zaczynamy?? 🐢', 'szybki wyścig do sałaty, lets goooo', 'żółwie nie śpią dzisiaj no cap'],
      'thinking' => ['mysli 🧠', 'hmm... trudna decyzja ngl'],
      'good_move' => ['lecimy dalej', 'thank you, next'],
      'sabotage' => ['nie dziś, kolego', ' wracaj'],
      'near_win' => ['nie zatrzymujcie mnie teraz', 'to jest mój moment fr'],
      'blocked_threat' => ['za późno, kochani', 'nie przechodzi ta sztuczka'],
      'win' => ['jestem mistrzem żółwi, no cap', 'wygrana w kieszeni'],
      'lose' => ['następnym razem moja', 'rewanż kiedyś?'],
      'opponent_close' => ['za blisko sałaty, stres', 'nie pozwolę na to'],
      'random' => ['chat jest toxic dzisiaj?', 'pls nie ragequit'],
    ];
  }

  $pool = $lines[$situation] ?? $lines['random'];
  return $pool[array_rand($pool)];
}

/** Wysyła wiadomość bota z uwzględnieniem szansy i cooldownu. */
function maybeBotChat(
  Storage $storage,
  int $roomId,
  array $room,
  int $botPlayerId,
  string $situation,
  ?int $chancePercent = null,
): void {
  if (!canBotSendChat($storage, $roomId)) {
    return;
  }

  $chance = $chancePercent ?? (int) (BOT_CHAT_CHANCE * 100);
  if (mt_rand(1, 100) > $chance) {
    return;
  }

  $nickname = $room['players'][$botPlayerId]['nickname'] ?? 'Żółw-Mistrz';
  appendBotChatMessage($storage, $roomId, $nickname, pickBotChatLine($situation));
  markBotChatSent($storage, $roomId);
}

/** Rozpoznaje sytuację na planszy po ruchu bota. */
function detectBotChatSituation(array $room, int $botPlayerId, array $before, array $after): ?string {
  $gsAfter = $after['game_state'];
  $myColor = normalizeTurtleColor($room['players'][$botPlayerId]['color'] ?? '');
  $boardBefore = $before['game_state']['board'];
  $boardAfter = $gsAfter['board'];

  if (($gsAfter['winner'] ?? null) !== null) {
    $winColor = normalizeTurtleColor($gsAfter['winner']['color']);
    return $myColor === $winColor ? 'win' : 'lose';
  }

  $myPosBefore = getTurtlePosition($boardBefore, $myColor);
  $myPosAfter = getTurtlePosition($boardAfter, $myColor);
  $myStackBefore = getStackIndex($boardBefore, $myColor);
  $myStackAfter = getStackIndex($boardAfter, $myColor);
  $lastCard = $gsAfter['last_played_card'] ?? null;
  $moveStr = trim((string) ($lastCard['move'] ?? ''));

  if (str_contains($moveStr, 'LF')) {
    return 'lf_move';
  }

  if (str_contains($moveStr, '-')) {
    foreach ($room['players'] as $oid => $p) {
      if ((int) $oid === $botPlayerId) {
        continue;
      }
      $opColor = normalizeTurtleColor($p['color']);
      $opBefore = getTurtlePosition($boardBefore, $opColor);
      $opAfter = getTurtlePosition($boardAfter, $opColor);
      if ($opAfter < $opBefore) {
        return 'sabotage';
      }
    }
  }

  if ($myPosBefore <= 4 && $myPosAfter >= 6) {
    return 'comeback';
  }

  if ($myPosAfter > $myPosBefore && $myStackAfter > 0 && $myStackAfter < count($boardAfter[$myPosAfter]) - 1) {
    if (mt_rand(1, 100) <= 45) {
      return 'stack_ride';
    }
  }

  if ($myPosAfter >= 7 && $myPosAfter > $myPosBefore) {
    return 'near_win';
  }

  if ($myPosAfter > $myPosBefore) {
    if (mt_rand(1, 100) <= 25) {
      return 'taunt';
    }
    return 'good_move';
  }

  foreach ($room['players'] as $oid => $p) {
    if ((int) $oid === $botPlayerId) {
      continue;
    }
    $opColor = normalizeTurtleColor($p['color']);
    if (getTurtlePosition($boardAfter, $opColor) >= 7) {
      return 'opponent_close';
    }
  }

  return null;
}

/** Opcjonalnie dodaje komentarz bota przed ruchem */
function maybeBotChatBeforeMove(Storage $storage, int $roomId, array $room, int $botPlayerId): void {
  maybeBotChat($storage, $roomId, $room, $botPlayerId, 'thinking', BOT_CHAT_BEFORE_MOVE_CHANCE);
}

/** Opcjonalnie dodaje komentarz bota po wykonanym ruchu */
function maybeBotChatAfterMove(Storage $storage, int $roomId, array $room, int $botPlayerId, array $roomBefore): void {
  $situation = detectBotChatSituation($room, $botPlayerId, $roomBefore, $room);
  if ($situation === null) {
    maybeBotChat($storage, $roomId, $room, $botPlayerId, 'random', BOT_CHAT_RANDOM_FALLBACK_CHANCE);
    return;
  }

  maybeBotChat($storage, $roomId, $room, $botPlayerId, $situation, 72);
}

/** Opcjonalnie dodaje pierwszą wiadomość bota po starcie gry */
function maybeBotChatGameStart(Storage $storage, int $roomId, array $room): void {
  foreach ($room['players'] as $id => $player) {
    if (!isBotPlayer($player)) {
      continue;
    }
    maybeBotChat($storage, $roomId, $room, (int) $id, 'game_start', BOT_CHAT_GAME_START_CHANCE);
    if (mt_rand(1, 100) <= 35) {
      maybeBotChat($storage, $roomId, $room, (int) $id, 'taunt', 55);
    }
    break;
  }
}
