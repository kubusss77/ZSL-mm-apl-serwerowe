<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./css/loginRegister.css">
  <title>Document</title>
  <?php
    require 'secrets.php';
    // W secrets.php muszą być:
    // $db_user, $db_pass

    $db = new PDO("mysql:dbname=film_rentals;host=localhost", $db_user, $db_pass);
  ?>
</head>
<body>
  <main class="flex">
    <h1>Zaloguj</h1>
    <form class="flex" method="POST">
      <input type="text" name="mail">
      <input type="text" name="pass">
      <input type="submit" value="Zaloguj">
    </form>
  </main class="flex">

</body>
</html>