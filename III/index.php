<?php

$user = 'root'; // user
$passwd = 'ServBay.dev'; // hasło

$db = new PDO('mysql:dbname=kraje;host=localhost', $user, $passwd);
$data = $db->exec('select * from test');
print_r($data);

?>
