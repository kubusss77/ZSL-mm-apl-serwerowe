<?php
$memcached = new Memcached();
$memcached->addServer('127.0.0.1', 11211);

if ($memcached->flush()) {
    echo json_encode(['status' => 'success', 'message' => 'Pamięć Memcached została całkowicie wyczyszczona.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Nie udało się wyczyścić pamięci.']);
}