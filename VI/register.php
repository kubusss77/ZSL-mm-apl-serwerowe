<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./css/loginRegister.css">
  <title>Document</title>
  <?php
    require './tools/db.php'
  ?>
</head>
<body>
  <main class="flex">
    <h1>Zarejestruj</h1>
    <form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
      <input type="text" name="mail" placeholder="E-mail" required>
      <input type="text" name="pass" placeholder="Hasło" required>
      <input type="submit" value="Zarejestruj">
    </form>

    <?php
      if(isset($_POST['mail']) && isset($_POST['pass'])) {

        $email = $_POST['mail'];
        $password = $_POST['pass'];

        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $userCount = $stmt->fetchColumn();

        if($userCount) {
          echo '<p>This email is already registered!</p>';
          exit();
        }

      }
    ?>
  </main>
</body>
</html>