<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/global.css">
    <?php
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
    ?>
    <title>Document</title>
    <style>
      header {
        margin-bottom: 1rem;
        text-align: center;
      }

      .calendar {
        margin: auto;
      }

      .calendar td {
        padding: 5px;
        font-size: 20px;
        text-align: center;
        aspect-ratio: 1;
      }

      .today {
        background: darkblue;
        
      }

      td:hover {
        border-radius: 100%;
        background: #eee;
      }

      a {
        text-decoration: none;
        color: black;
      }
    </style>
  </head>
  <body>
    <header>
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
          $url = $_SERVER['REQUEST_URI'] . '/day' . '?' . 'y=' . $y . '&m=' . $m . '&d=' . $d;

          global $today;
          global $curYear;

          if ($d == $today && $y == $curYear) {
            echo '<td class="button"> <a style="color: white;" ';
          } else {
            echo '<td> <a ';
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
      </main>
  </body>
</html>