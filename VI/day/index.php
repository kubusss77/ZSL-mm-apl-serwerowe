<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/global.css">
  <?php
  session_start();
  if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
  }

  require '../tools/dateFormatter.php';
  // $df_month->format()

  require '../tools/db.php';

  $year;
  $month;
  $day;

  if (isset($_GET['y']) && isset($_GET['m']) && isset($_GET['d'])) {
    $year = $_GET['y'];
    $month = $_GET['m'];
    $day = $_GET['d'];
  } else {
    http_response_code(400);
    echo 'Bad request';
    exit();
  }

  // Powinien pobierać wpis z bazy dla zalogowanego użytkownika (schemat bazy jest w readme)

  $date = new DateTime();
  $date->setDate($year, $month, $day);
  $formattedDate = $date->format('Y-m-d');
  $userId = $_SESSION['user_id'];

  $message = '';
  if (isset($_POST['content']) || isset($_POST['action'])) {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
      $del = $db->prepare('DELETE FROM entries WHERE userid = :userid AND entry_date = :entry_date');
      $del->execute(['userid' => $userId, 'entry_date' => $formattedDate]);
      $message = 'Skasowano wszystkie wpisy z tego dnia.';
    } else {
      $stmt = $db->prepare('SELECT id FROM entries WHERE userid = :userid AND entry_date = :entry_date');
      $stmt->execute(['userid' => $userId, 'entry_date' => $formattedDate]);
      $entryId = $stmt->fetchColumn();

      if ($entryId) {
        $update = $db->prepare('UPDATE entries SET content = :content WHERE id = :id');
        $update->execute(['content' => $_POST['content'], 'id' => $entryId]);
      } else {
        $insert = $db->prepare(
          'INSERT INTO entries (userid, content, entry_date) VALUES (:userid, :content, :entry_date)',
        );
        $insert->execute(['userid' => $userId, 'content' => $_POST['content'], 'entry_date' => $formattedDate]);
      }
      $message = 'Zapisano zmiany.';
    }
  }

  $stmt = $db->prepare('SELECT content FROM entries WHERE userid = :userid AND entry_date = :entry_date');
  $stmt->execute(['userid' => $userId, 'entry_date' => $formattedDate]);
  $currentContent = $stmt->fetchColumn();
  if (!$currentContent) {
    $currentContent = '';
  }
  ?>
  <title>Wpis <?php echo $day . '-' . $month . '-' . $year; ?></title>
  <style>
    textarea {
      margin-top: 1rem;
    }
  </style>
</head>
<body>
  <a href="../index.php">Wróć do kalendarza</a>
  <h1><?php echo $day . ' ' . $df_month->format($date) . ' ' . $year; ?></h1>
  <?php if ($message): ?>
    <p style="color: green;"><?php echo htmlspecialchars($message); ?></p>
  <?php endif; ?>
  <form method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
    <div style="display: flex; gap: 16px; align-items: center;">
      <h3>Edytuj wpis</h3>
      <button type="submit" name="action" value="save" class="button">Zapisz</button>
      <button type="submit" name="action" value="delete" class="button" style="background-color: #ff4444;" onclick="return confirm('Na pewno chcesz skasować wpisy z tego dnia?');">Skasuj</button>
    </div>
    
    <textarea rows="20" cols="100" name="content"><?php echo htmlspecialchars($currentContent); ?></textarea>
  </form>
  
</body>
</html>