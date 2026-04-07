<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/global.css">
  <link rel="stylesheet" href="../css/auth.css">
  <title>Document</title>
  <?php
  require '../tools/db.php';
  require '../tools/mailer.php';
  ?>
</head>
<body>
  <main>
    <div style="display: flex; justify-content: space-between;">
      <h1>Resetuj hasło</h1>
      <a href="./login.php"><p>Powrót do logowania</p></a>
    </div>
    <form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
      <input type="text" name="mail" placeholder="E-mail" style="width: 90%;" required>
      <input type="submit" value="Wyślij link resetujący">
    </form>

    <?php if (isset($_POST['mail'])) {
      $email = $_POST['mail'];

      $stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
      $stmt->execute(['email' => $email]);
      $userId = $stmt->fetchColumn();

      if (!$userId) {
        echo '<p>Nie znaleziono użytkownika o podanym adresie e-mail!</p>';
        exit();
      }

      $token = bin2hex(random_bytes(16));
      $hashedToken = password_hash($token, PASSWORD_DEFAULT);
      $stmt = $db->prepare(
        "INSERT INTO tokens (token_hash, type, userid) VALUES (:token_hash, 'reset_password', :userid)",
      );
      $stmt->execute(['token_hash' => $hashedToken, 'userid' => $userId]);

      $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
      $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
      $resetLink = $baseUrl . "/password-reset/verifyToken.php?token=$token";

      $mail = null;
      try {
        $mail = getMailer();
        $mail->addAddress($email);
        $mail->Subject = 'Resetowanie hasla';
        $mail->Body = "Kliknij w link, aby zresetowac haslo: $resetLink";
        $mail->send();
        echo '<p>Link resetujący hasło został wysłany na podany adres e-mail!</p>';
      } catch (Exception $e) {
        $errorMsg = $mail ? $mail->ErrorInfo : $e->getMessage();
        echo "<p>Nie udało się wysłać e-maila: {$errorMsg}</p>";
      }
    } ?>
  </main>
</body>
</html>