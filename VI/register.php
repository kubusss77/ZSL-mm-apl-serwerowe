<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./css/global.css">
  <link rel="stylesheet" href="./css/auth.css">
  <title>Document</title>
  <?php
    require './tools/db.php'
  ?>
</head>
<body>
  <main>
    <div style="display: flex; justify-content: space-between;">
      <h1>Zarejestruj</h1>
      <a href="./login.php"><p>Masz konto? Zaloguj się!</p></a>
    </div>
    <form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
      <input type="text" name="mail" placeholder="E-mail" required style="width: 90%;">
      <input type="text" name="pass" placeholder="Hasło" required style="width: 90%;">
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
          echo '<p>Ten adres e-mail jest już zarejestrowany!</p>';
          exit();
        }

        if(!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{8,}$/m', $password)) {
          echo '<p>Hasło musi mieć:</p>';
          echo '<ul><li>minimum 8 znaków</li><li>co najmniej jedną wielką literę</li><li>co najmniej jedną małą literę</li><li>co najmniej jedną cyfrę</li></ul>';
          exit();
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (email, password) VALUES (:email, :password)");
        $stmt->execute(['email' => $email, 'password' => $hashedPassword]);

        echo '<p>Pomyślnie zarejestrowano!</p>';


      }
    ?>
  </main>
</body>
</html>