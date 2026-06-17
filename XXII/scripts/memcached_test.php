<?php
$m = new Memcached();
$m->addServer('127.0.0.1', 11211);
if ($m->set('test_key', 'działa')) {
    echo "Memcached OK: " . $m->get('test_key');
} else {
    echo "Memcached BŁĄD: " . $m->getResultMessage();
}