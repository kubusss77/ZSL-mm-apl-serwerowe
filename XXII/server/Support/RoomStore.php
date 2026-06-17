<?php

const ROOMS_KEY = 'rooms';
const ROOMS_LOCK_KEY = 'rooms_mutex';
const ROOMS_TTL = 86400;
const ROOMS_LOCK_TTL = 5;

function storageErrorAndExit(string $message): void {
  echo json_encode(['status' => 'error', 'message' => $message]);
  exit();
}

function getRooms($storage): array {
  return $storage->get(ROOMS_KEY) ?: [];
}

function getRoom($storage, int $roomId): ?array {
  $rooms = getRooms($storage);
  return $rooms[$roomId] ?? null;
}

function withRoomsLock($storage, callable $callback) {
  for ($attempts = 0; $attempts < 25; $attempts++) {
    if (!$storage->add(ROOMS_LOCK_KEY, 1, ROOMS_LOCK_TTL)) {
      usleep(3000 + $attempts * 1500);
      continue;
    }

    try {
      return $callback();
    } finally {
      $storage->delete(ROOMS_LOCK_KEY);
    }
  }

  storageErrorAndExit('Serwer zajęty (lock timeout)');
}

function updateRooms($storage, callable $callback) {
  return withRoomsLock($storage, function () use ($storage, $callback) {
    $rooms = $storage->get(ROOMS_KEY) ?: [];
    $rooms = $callback($rooms);
    $storage->set(ROOMS_KEY, $rooms, ROOMS_TTL);
    return $rooms;
  });
}

function updateRoom($storage, int $roomId, callable $callback): ?array {
  return withRoomsLock($storage, function () use ($storage, $roomId, $callback) {
    $rooms = $storage->get(ROOMS_KEY) ?: [];
    if (!isset($rooms[$roomId])) {
      return null;
    }

    $updatedRoom = $callback($rooms[$roomId]);
    if ($updatedRoom === null) {
      return null;
    }

    $rooms[$roomId] = $updatedRoom;
    $storage->set(ROOMS_KEY, $rooms, ROOMS_TTL);
    return $updatedRoom;
  });
}

function filterRoomForPlayer(array $room, int $playerId, bool $isPlayerInRoom): array {
  if ($room['status'] === 'playing') {
    unset($room['game_state']['deck']);
    foreach ($room['players'] as $id => $p) {
      unset($room['players'][$id]['token']);
      if ((int) $id !== $playerId || !$isPlayerInRoom) {
        unset($room['players'][$id]['color'], $room['players'][$id]['cards']);
      }
    }
  }
  return $room;
}
