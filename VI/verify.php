<?php
require './tools/db.php';

$message = '';

if (isset($_GET['token'])) {
  $token = $_GET['token'];

  // Find unverified token
  $stmt = $db->prepare("SELECT id, token_hash, userid FROM tokens WHERE type = 'verify_email'");
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
    // Mark as verified
    $db->beginTransaction();
    try {
      $updateUser = $db->prepare('UPDATE users SET is_verified = 1 WHERE id = :userid');
      $updateUser->execute(['userid' => $foundUserId]);

      $updateToken = $db->prepare('DELETE FROM tokens WHERE id = :id');
      $updateToken->execute(['id' => $tokenId]);

      // dodać usuwanie tokena

      $db->commit();
      $message = 'Udało się zweryfikować konto! Możesz teraz się zalogować.';
    } catch (Exception $e) {
      $db->rollBack();
      $message = 'Wystąpił błąd podczas weryfikacji.';
    }
  } else {
    $message = 'Niepoprawny token lub konto już potwierdzone.';
  }
} else {
  $message = 'Brak tokena.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./css/global.css">
  <link rel="stylesheet" href="./css/auth.css">
  <title>Potwierdzenie konta</title>
</head>
<body>
  <main>
    <h1>Weryfikacja</h1>
    <p><?php echo htmlspecialchars($message); ?></p>
    <a href="./login.php">Powrót do logowania</a>
  </main>
</body>
</html>