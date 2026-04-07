<?php
session_start();
if (isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit();
}
require './tools/db.php';
require './tools/mailer.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./css/global.css">
  <link rel="stylesheet" href="./css/auth.css">
  <title>Planer - zarejestruj</title>
</head>
<body>
  <main>
    <div style="display: flex; justify-content: space-between;">
      <h1>Zarejestruj</h1>
      <a href="./login.php"><p>Masz konto? Zaloguj się!</p></a>
    </div>
    <form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
      <input type="text" name="mail" placeholder="E-mail" required style="width: 90%;">
      <input type="password" name="pass" placeholder="Hasło" required style="width: 90%;">
      <input type="submit" value="Zarejestruj">
    </form>

    <?php if (isset($_POST['mail']) && isset($_POST['pass'])) {
      $email = $_POST['mail'];
      $password = $_POST['pass'];

      $stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
      $stmt->execute(['email' => $email]);
      $userCount = $stmt->fetchColumn();

      if ($userCount) {
        echo '<p>Ten adres e-mail jest już zarejestrowany!</p>';
        exit();
      }

      $passwordErrors = [];
      if (strlen($password) < 8) {
        $passwordErrors[] = 'minimum 8 znaków';
      }
      if (!preg_match('/[A-Z]/', $password)) {
        $passwordErrors[] = 'co najmniej jedną wielką literę';
      }
      if (!preg_match('/[a-z]/', $password)) {
        $passwordErrors[] = 'co najmniej jedną małą literę';
      }
      if (!preg_match('/[0-9]/', $password)) {
        $passwordErrors[] = 'co najmniej jedną cyfrę';
      }

      if (!empty($passwordErrors)) {
        echo '<p>W Twoim haśle brakuje:</p><ul>';
        foreach ($passwordErrors as $err) {
          echo "<li>$err</li>";
        }
        echo '</ul>';
        exit();
      }

      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

      $stmt = $db->prepare('INSERT INTO users (email, password) VALUES (:email, :password)');
      if ($stmt->execute(['email' => $email, 'password' => $hashedPassword])) {
        $userId = $db->lastInsertId();

        // Create verification token
        $token = bin2hex(random_bytes(16));
        $hashedToken = password_hash($token, PASSWORD_DEFAULT);

        $stmt = $db->prepare(
          "INSERT INTO tokens (token_hash, type, userid) VALUES (:token_hash, 'verify_email', :user_id)",
        );
        $stmt->execute(['token_hash' => $hashedToken, 'user_id' => $userId]);

        try {
          $mail = getMailer();
          $mail->addAddress($email);
          $mail->Subject = 'Potwierdzenie rejestracji Planer';

          $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
          $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
          $verifyLink = $baseUrl . '/verify.php?token=' . $token;

          $mail->Body = "Kliknij w link, aby potwierdzić konto: $verifyLink";

          $mail->send();
          echo '<p>Pomyślnie zarejestrowano! Sprawdź e-mail, aby aktywować konto.</p>';
        } catch (Exception $e) {
          $errorMsg = $mail ? $mail->ErrorInfo : $e->getMessage();
          echo "<p>Rejestracja pomyślna, ale nie udało się wysłać e-maila: {$errorMsg}</p>";
        }
      } else {
        echo '<p>Błąd podczas rejestracji!</p>';
      }
    } ?>
  </main>
</body>
</html>