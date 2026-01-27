<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <?php
  $base_url = '/mendela-apl-serwerowe/spr/spr2%202026.01.21%204ib1/';

  if ($_POST['name'] == '' || $_POST['email'] == '' || $_POST['date'] == '') {
    header('Location:' . $base_url . 'index.php?error=empty');
  }
  ?>
</head>
<body>
  <?php
  $error;

  echo '<p> Imię: ' . $_POST['name'] . '</p>';
  if (!preg_match('/[A-z]{2,}/', $_POST['name'])) {
    echo '<b>Błędne imię!</b>';
    $error = 1;
  }

  echo '<p> E-mail: ' . $_POST['email'] . '</p>';
  if (!preg_match('/^[A-z1-9.]+@{1}[A-z1-9.]{2,}\.{1}[A-z]{2,}$/', $_POST['email'])) {
    echo '<b>Błędny e-mail!</b>';
    $error = 1;
  }

  echo '<p> Data: ' . $_POST['date'] . '</p>';
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['date'])) {
    echo '<b>Błędna data!</b>';
    $error = 1;
  }

  if (isset($error)) {
    exit();
  }

  $xml = simplexml_load_file('info.xml');
  echo '<p> Wydarzenie: ' . $xml->nazwa . '</p>';
  echo '<p> Miejsce: ' . $xml->miejsce . '</p>';
  echo '<p> Limit: ' . $xml['limit'] . '</p>';
  ?>
</body>
</html>


