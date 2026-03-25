<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<body>
  <main>
    <h1>Weryfikacja tokena</h1>
    <?php
      require '../tools/db.php';

      if(isset($_GET['token'])) {
        $token = $_GET['token'];

        echo '<p>Otrzymany token: ' . $token . '</p>';

        $tokenHash = password_hash($token, PASSWORD_DEFAULT);

        echo '<p>Hasz tokena: ' . $tokenHash . '</p>';
        $stmt = $db->prepare("SELECT user_id FROM tokens WHERE type = 'reset_password'");
        $stmt->execute();
        $userId = $stmt->fetchColumn();

        echo '<p>Odnaleziony user_id: ' . $userId . '</p>';

        if($userId) {
          echo '<p>Token jest ważny. Możesz teraz zresetować hasło.</p>';
        } else {
          echo '<p>Token jest nieprawidłowy lub wygasł.</p>';
        }
      }
    ?>
  </main>
</body>
</html>