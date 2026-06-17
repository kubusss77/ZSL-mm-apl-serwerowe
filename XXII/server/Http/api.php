<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$sessionToken = session_id();

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../Actions/JoinActions.php';
require_once __DIR__ . '/../Actions/LobbyActions.php';
require_once __DIR__ . '/../Actions/StateActions.php';
require_once __DIR__ . '/../Actions/PlayActions.php';
require_once __DIR__ . '/../Actions/BotActions.php';
require_once __DIR__ . '/../Actions/ProcessBotActions.php';

$storage = new Storage();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'join':
        actionJoin($storage, $sessionToken);
        break;
    case 'reconnect':
        actionReconnect($storage);
        break;
    case 'ready':
        actionReady($storage);
        break;
    case 'getState':
        actionGetState($storage);
        break;
    case 'playCard':
        actionPlayCard($storage);
        break;
    case 'addBot':
        actionAddBot($storage);
        break;
    case 'processBots':
        actionProcessBots($storage);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Nieznana akcja: ' . $action]);
        exit;
}
