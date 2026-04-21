<?php
require 'db.php';

$action = $_GET['action'] ?? '';

switch ($action) {
  case 'get_options':
    $stmt_flagi = $db->query('SELECT id, nazwa FROM flagi');
    $flagi = $stmt_flagi->fetchAll(PDO::FETCH_ASSOC);

    $stmt_materialy = $db->query('SELECT id, nazwa FROM materialy');
    $materialy = $stmt_materialy->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['flagi' => $flagi, 'materialy' => $materialy]);
    break;

  case 'get_data':
    $stmt = $db->query("
            SELECT d.id, d.nominal, d.nr_kat, d.rok, d.flaga_id, d.stop_id, f.nazwa as flaga_nazwa, m.nazwa as stop_nazwa 
            FROM dane d
            LEFT JOIN flagi f ON d.flaga_id = f.id
            LEFT JOIN materialy m ON d.stop_id = m.id
        ");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    break;

  case 'add':
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data) {
      $stmt = $db->prepare('INSERT INTO dane (flaga_id, nominal, nr_kat, stop_id, rok) VALUES (?, ?, ?, ?, ?)');
      $stmt->execute([$data['flaga_id'], $data['nominal'], $data['nr_kat'], $data['stop_id'], $data['rok']]);
      echo json_encode(['id' => $db->lastInsertId()]);
    }
    break;

  case 'edit':
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data) {
      $stmt = $db->prepare('UPDATE dane SET flaga_id = ?, nominal = ?, nr_kat = ?, stop_id = ?, rok = ? WHERE id = ?');
      $stmt->execute([
        $data['flaga_id'],
        $data['nominal'],
        $data['nr_kat'],
        $data['stop_id'],
        $data['rok'],
        $data['id'],
      ]);
      echo json_encode(['success' => true]);
    }
    break;

  case 'delete':
    $data = json_decode(file_get_contents('php://input'), true);
    if ($data && isset($data['id'])) {
      $stmt = $db->prepare('DELETE FROM dane WHERE id = ?');
      $stmt->execute([$data['id']]);
      echo json_encode(['success' => true]);
    }
    break;

  default:
    echo json_encode(['error' => 'Invalid action']);
}
