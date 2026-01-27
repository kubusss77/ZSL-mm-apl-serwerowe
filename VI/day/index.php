<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/global.css">
  <?php
  require '../tools/dateFormatter.php';
  // $df_month->format()

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

  $date = new DateTime()->setDate($year, $month, $day);
  ?>
  <title>Wpis <?php echo $day . '-' . $month . '-' . $year; ?></title>
  <style>
    textarea {
      margin-top: 1rem;
    }
  </style>
</head>
<body>
  <h1><?php echo $day . ' ' . $df_month->format($date) . ' ' . $year; ?></h1>
  <div style="display: flex;">
    <h3>Edytuj wpis</h3>
    <div>
      <button class="button">Zapisz</button>
    </div>
  </div>
  
  <textarea rows="20" cols="100"></textarea>
  
</body>
</html>