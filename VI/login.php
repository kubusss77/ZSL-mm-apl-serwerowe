<?php
session_start();
if (isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit();
}
require './tools/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./css/global.css">
  <link rel="stylesheet" href="./css/auth.css">
  <title>Planer - zaloguj</title>
</head>
<body>
  <main>
    <div style="display: flex; justify-content: space-between;">
      <h1>Zaloguj</h1>
      <div>
        <a href="./register.php"><p>Nie masz konta? Zarejestruj się!</p></a>
        <a href="./password-reset"><p style="padding-top: 0; margin-top: -0.4rem; color: #444; text-align: right;">Zapomniałeś hasła?</p></a>
      </div>
      
    </div>
    <form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
      <input type="text" name="mail" placeholder="E-mail" required style="width: 90%;">
      <input type="password" name="pass" placeholder="Hasło" required style="width: 90%;">
      <input type="submit" value="Zaloguj">
    </form>

    <?php if (isset($_POST['mail']) && isset($_POST['pass'])) {
      $email = $_POST['mail'];
      $password = $_POST['pass'];

      $stmt = $db->prepare('SELECT id, password, is_verified FROM users WHERE email = :email');
      $stmt->execute(['email' => $email]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($user && password_verify($password, $user['password'])) {
        if ($user['is_verified']) {
          $_SESSION['user_id'] = $user['id'];
          header('Location: index.php');
          exit();
        } else {
          echo '<p>Potwierdź swój adres e-mail, aby móc się zalogować!</p>';
        }
      } else {
        echo '<p>Niepoprawny e-mail lub hasło!</p>';
      }
    } ?>
  </main>

</body>
</html>