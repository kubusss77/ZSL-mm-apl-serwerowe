<?php
require_once __DIR__ . '/../server/bootstrap.php';

$room = [
    'status' => 'playing',
    'players' => [
        1 => [
            'nickname' => 'Human',
            'is_ready' => true,
            'token' => 'human',
            'color' => 'red',
            'cards' => [
                ['id' => 'c1', 'color' => 'red', 'move' => '+1'],
                ['id' => 'c2', 'color' => 'any', 'move' => '+1'],
                ['id' => 'c3', 'color' => 'blue', 'move' => '-1'],
                ['id' => 'c4', 'color' => 'green', 'move' => '+2'],
                ['id' => 'c5', 'color' => 'any', 'move' => '+1LF'],
            ],
        ],
        2 => [
            'nickname' => 'Żółw-Mistrz 1',
            'is_ready' => true,
            'is_bot' => true,
            'token' => 'bot_1',
            'color' => 'blue',
            'cards' => [
                ['id' => 'b1', 'color' => 'blue', 'move' => '+2'],
                ['id' => 'b2', 'color' => 'yellow', 'move' => '+1'],
                ['id' => 'b3', 'color' => 'any', 'move' => '+2'],
                ['id' => 'b4', 'color' => 'purple', 'move' => '-1'],
                ['id' => 'b5', 'color' => 'any', 'move' => '+1LF'],
            ],
        ],
    ],
    'game_state' => [
        'deck' => [],
        'board' => [
            0 => [],
            1 => ['yellow', 'purple'],
            2 => ['green'],
            3 => ['red'],
            4 => [],
            5 => [],
            6 => ['blue'],
            7 => [],
            8 => [],
            9 => [],
        ],
        'current_turn' => 2,
        'winner' => null,
        'cards_played_count' => 10,
        'last_played_card' => null,
        'turn_deadline' => time() + 30,
    ],
];

$start = microtime(true);
$move = chooseBotMove($room, 2);
$elapsed = round((microtime(true) - $start) * 1000);

echo "Chosen move: " . json_encode($move, JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo "Time: {$elapsed}ms" . PHP_EOL;
echo "Legal moves: " . count(enumerateLegalMoves($room, 2)) . PHP_EOL;

if ($move === null) {
    fwrite(STDERR, "FAIL: no move chosen\n");
    exit(1);
}

$sim = cloneRoomState($room);
executePlayForPlayer($sim, 2, $move['card_id'], $move['chosen_color']);
echo "Score after move: " . evaluatePosition($sim, 2) . PHP_EOL;
echo "OK\n";
