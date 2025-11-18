<?php
  include('db.php');
  $flaga = $_POST['flaga'];
  $nominal = $_POST['nominal'];
  $nrkat = $_POST['nrkat'];
  $stop = $_POST['stop'];
  $rok = (int)$_POST['rok'];

  $db->exec('insert into dane (flaga, nominal, nrkat, stop, rok) values ("' . $flaga . '", "' . $nominal . '", "' . $nrkat . '", "' . $stop . '", "' . $rok . '")');

  header('Location: http://' . $_SERVER['HTTP_HOST'] . $_POST['redirect']);
  die();
?>