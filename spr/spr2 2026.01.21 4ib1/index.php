<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <?php $base_url = '/mendela-apl-serwerowe/spr/spr2%202026.01.21%204ib1/'; ?>
</head>
<body>
  <form method="POST" action=<?php echo $base_url . 'add.php'; ?>>
    Imię: <input type=text name="name">
    <br>
    E-mail: <input type="text" name="email">
    <br>
    Data wydarzenia: <input type="text" name="date">
    <input type="submit">
  </form>

  <p>
  <?php if (isset($_GET['error'])) {
    switch ($_GET['error']) {
      case 'empty':
        echo 'Wszystkie pola są wymagane!';
        break;
      default:
        echo 'Niezanany błąd';
    }
  } ?>
  </p>
</body>
</html>