<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/calendar.css">
    <title>Planer</title>
    <?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
      header('Location: login.php');
      exit();
    }
    require 'tools/dateFormatter.php';
    // $df_month->format()

    $now = new DateTime();

    $month;
    if (isset($_POST['sel-month'])) {
      $month = $_POST['sel-month'];
    } else {
      $month = date('m');
    }

    $year;
    $curYear = date('Y');

    if (isset($_POST['sel-year'])) {
      $year = $_POST['sel-year'];
    } else {
      $year = $curYear;
    }

    require 'tools/db.php';
    $userId = $_SESSION['user_id'];

    // Fetch today's plan
    $todayDate = date('Y-m-d');
    $stmtToday = $db->prepare('SELECT content FROM entries WHERE userid = :userid AND entry_date = :entry_date');
    $stmtToday->execute(['userid' => $userId, 'entry_date' => $todayDate]);
    $todayPlan = $stmtToday->fetchColumn();

    // Fetch days with plans for current calendar view
    $stmtDays = $db->prepare(
      'SELECT DAY(entry_date) as e_day FROM entries WHERE userid = :userid AND YEAR(entry_date) = :year AND MONTH(entry_date) = :month AND content IS NOT NULL AND content != \'\'',
    );
    $stmtDays->execute(['userid' => $userId, 'year' => $year, 'month' => $month]);
    $daysWithPlans = $stmtDays->fetchAll(PDO::FETCH_COLUMN);
    $daysWithPlans = array_map('intval', $daysWithPlans);
    ?>
    <style>
      .has-plan a {
        background-color: #ffeb3b; 
        color: #000;
        border-radius: 50%;
        display: inline-block;
        width: 30px;
        line-height: 30px;
      }
    </style>
  </head>
  <body>
    <header>
      <div style="text-align: right; margin-bottom: 10px;">
        <a href="logout.php">Wyloguj</a>
      </div>
      <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST">
      <select name="sel-month">
      <?php for ($i = 1; $i <= 12; $i++) {
        $now->setDate(2022, $i, 12);
        if ($i == $month) {
          echo '<option value="' . $i . '"' . ' selected="selected"' . '>' . $df_month->format($now) . '</option>';
        } else {
          echo '<option value="' . $i . '">' . $df_month->format($now) . '</option>';
        }
      } ?>
      </select>
      <select name="sel-year">
        <?php for ($i = -2; $i < 8; $i++) {
          if ($i == 0) {
            echo '<option' . ' selected="selected" ' . '>' . $year + $i . '</option>';
          } else {
            echo '<option>' . $year + $i . '</option>';
          }
        } ?>
      </select>
      <input class=button type="submit" value="Ok">
      </form>
    </header>
    <main>
      <table class="calendar">
        <?php
        $firstOfMonth = date('N', mktime(0, 0, 0, $month, 1, $year));
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $today = date('j');
        $cur_month = date('n');

        if ($month != $cur_month) {
          $today = 0;
        }

        $day = 1;

        function genTd($y, $m, $d) {
          $url = 'day/' . '?' . 'y=' . $y . '&m=' . $m . '&d=' . $d;

          global $today;
          global $curYear;
          global $daysWithPlans;

          $classes = ['day'];
          if (in_array($d, $daysWithPlans)) {
            $classes[] = 'has-plan';
          }
          $classStr = implode(' ', $classes);

          if ($d == $today && $y == $curYear) {
            echo '<td id="today" class="' . $classStr . '"> <a ';
          } else {
            echo '<td class="' . $classStr . '"> <a ';
          }

          echo 'href="' . $url . '">' . $d . '</a>' . '</td>';
        }

        //Pierwszy tydzień
        echo '<tr>';
        for ($i = 0; $i < $firstOfMonth - 1; $i++) {
          echo '<td>' . '</td>';
        }
        for ($i = $firstOfMonth; $i <= 7; $i++) {
          genTd($year, $month, $day);
          $day++;
        }
        echo '</tr>';

        //Kolejne tygodnie
        for ($i = 0; $day < $daysInMonth; $i++) {
          echo '<tr>';
          for ($j = 0; $j < 7 && $day <= $daysInMonth; $j++) {
            genTd($year, $month, $day);
            $day++;
          }
          echo '</tr>';
        }
        ?>
      </table>
      
      <div style="margin-top: 2rem;">
        <h2>Plany na dziś:</h2>
        <?php if ($todayPlan): ?>
          <div style="white-space: pre-wrap; background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 5px solid #007bff; margin-top: 1rem;"><?php echo htmlspecialchars(
            $todayPlan,
          ); ?></div>
        <?php else: ?>
          <p style="color: #666;">Brak planów na dzisiaj.</p>
        <?php endif; ?>
      </div>
      </main>
  </body>
</html>