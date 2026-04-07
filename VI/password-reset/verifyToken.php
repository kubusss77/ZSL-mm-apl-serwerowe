<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<body>
  <main>
    <h1>Weryfikacja tokena i reset hasła</h1>
    <?php
    require '../tools/db.php';

    $message = '';

    if (isset($_GET['token'])) {
      $token = $_GET['token'];

      $stmt = $db->prepare("SELECT id, token_hash, userid FROM tokens WHERE type = 'reset_password'");
      $stmt->execute();
      $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $foundUserId = null;
      $tokenId = null;

      foreach ($tokens as $t) {
        if (password_verify($token, $t['token_hash'])) {
          $foundUserId = $t['userid'];
          $tokenId = $t['id'];
          break;
        }
      }

      if ($foundUserId) {
        echo '<p>Token jest ważny. Możesz teraz zresetować hasło.</p>'; ?>
          <form method="POST" action="">
            <input type="hidden" name="tokenId" value="<?php echo $tokenId; ?>">
            <input type="hidden" name="userId" value="<?php echo $foundUserId; ?>">
            <input type="password" name="new_password" placeholder="Nowe hasło" required>
            <input type="submit" value="Zmień hasło">
          </form>
          <?php
      } else {
        echo '<p>Token jest nieprawidłowy lub wygasł.</p>';
      }
    }

    if (isset($_POST['new_password']) && isset($_POST['userId']) && isset($_POST['tokenId'])) {
      $newPassword = $_POST['new_password'];
      $passwordErrors = [];
      if (strlen($newPassword) < 8) {
        $passwordErrors[] = 'minimum 8 znaków';
      }
      if (!preg_match('/[A-Z]/', $newPassword)) {
        $passwordErrors[] = 'co najmniej jedną wielką literę';
      }
      if (!preg_match('/[a-z]/', $newPassword)) {
        $passwordErrors[] = 'co najmniej jedną małą literę';
      }
      if (!preg_match('/[0-9]/', $newPassword)) {
        $passwordErrors[] = 'co najmniej jedną cyfrę';
      }

      if (!empty($passwordErrors)) {
        echo '<p>W Twoim nowym haśle brakuje:</p><ul>';
        foreach ($passwordErrors as $err) {
          echo "<li>$err</li>";
        }
        echo '</ul>';
      } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $userId = $_POST['userId'];
        $tokenId = $_POST['tokenId'];

        $db->beginTransaction();
        try {
          $updateUser = $db->prepare('UPDATE users SET password = :password WHERE id = :userid');
          $updateUser->execute(['password' => $hashedPassword, 'userid' => $userId]);

          $updateToken = $db->prepare('DELETE FROM tokens WHERE id = :id');
          $updateToken->execute(['id' => $tokenId]);

          $db->commit();
          echo '<p>Hasło zmienione pomyślnie. <a href="../login.php">Zaloguj się</a>.</p>';
        } catch (Exception $e) {
          $db->rollBack();
          echo '<p>Wystąpił błąd podczas zmiany hasła.</p>';
          echo $e;
        }
      }
    }
    ?>
  </main>
</body>
</html>