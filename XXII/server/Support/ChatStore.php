<?php

/** Tworzy identyfikator wiadomości czatu */
function nextChatMessageId(): int {
  static $lastId = 0;
  $id = (int) round(microtime(true) * 1000);
  if ($id <= $lastId) {
    $id = $lastId + 1;
  }
  $lastId = $id;
  return $id;
}

/** Dopisuje wiadomość do krótkiej historii czatu pokoju */
function appendChatMessage(Storage $storage, int $roomId, string $user, string $text): ?array {
  $trimmed = trim($text);
  if ($trimmed === '') {
    return null;
  }

  $chatKey = 'chat_messages_' . $roomId;
  $messages = $storage->get($chatKey) ?? [];

  $lastStoredId = 0;
  foreach ($messages as $existing) {
    $lastStoredId = max($lastStoredId, (int) ($existing['id'] ?? 0));
  }

  $message = [
    'id' => max(nextChatMessageId(), $lastStoredId + 1),
    'user' => $user,
    'text' => function_exists('mb_substr') ? mb_substr($trimmed, 0, 220) : substr($trimmed, 0, 220),
  ];

  $messages[] = $message;

  if (count($messages) > 20) {
    $messages = array_slice($messages, -20);
  }

  $storage->set($chatKey, $messages);
  return $message;
}
