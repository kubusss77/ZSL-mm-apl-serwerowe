<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
      #selects {
        text-align: center;
      }
      
      .calendar {
        display: flex;
        justify-self: center;
        padding: 20px;
      }

      .calendar td {
        padding: 5px;
        font-size: 20px;
        text-align: center;
      }

      .today {
        background: cornflowerblue;
      }
    </style>
    <?php
    $df = new IntlDateFormatter(
      'pl_PL', // wspierane w danym systemie Linux locale można sprawdzić poleceniem "locale -a"
      IntlDateFormatter::LONG,
      IntlDateFormatter::NONE,
      null,
      null,
      'LLLL', // formaty
    );

    $now = new DateTime();
    ?>
  </head>
  <body>
    <section id="selects">
      <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST">
      <select name="sel-month">
      <?php
      $month;
      if (isset($_POST['sel-month'])) {
        $month = $_POST['sel-month'];
      } else {
        $month = date('m');
      }

      for ($i = 1; $i <= 12; $i++) {
        $now->setDate(2022, $i, 12);
        if ($i == $month) {
          echo '<option value="' . $i . '"' . ' selected="selected"' . '>' . $df->format($now) . '</option>';
        } else {
          echo '<option value="' . $i . '">' . $df->format($now) . '</option>';
        }
      }
      ?>
      </select>
      <select name="sel-year">
        <?php
        $year;
        if (isset($_POST['sel-year'])) {
          $year = $_POST['sel-year'];
        } else {
          $year = date('Y');
        }

        for ($i = -2; $i < 8; $i++) {
          if ($year + $i == $year) {
            echo '<option' . ' selected="selected" ' . '>' . $year + $i . '</option>';
          } else {
            echo '<option>' . $year + $i . '</option>';
          }
        }
        ?>
      </select>
      <input type="submit">
      </form>
    </section>
    <section>
      <table class="calendar">
        <?php
        $firstOfMonth = date('N', mktime(0, 0, 0, $month, 1, $year));
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $today = date('j');
        $cur_month = date('n');

        if($month != $cur_month) {
          $today = 0;
        }

        $day = 1;
        //Pierwszy tydzień
        echo '<tr>';
        for($i = 0; $i < $firstOfMonth - 1; $i++) {
          echo '<td>' . '</td>';
        }
        for($i = $firstOfMonth; $i <= 7; $i++) {
          if($day == $today) {
              echo '<td class="today">' . $day . '</td>';
            } else {
              echo '<td>' . $day . '</td>';
            }
            $day++;
        }
        echo '</tr>';

        //Kolejne tygodnie
        for ($i = 0; $day < $daysInMonth; $i++) {
          echo '<tr>';
          for($j = 0; $j < 7 && $day <= $daysInMonth; $j++) {
            if($day == $today) {
              echo '<td class="today">' . $day . '</td>';
            } else {
              echo '<td>' . $day . '</td>';
            }
            $day++;
          }
          echo '</tr>';
        }
        ?>
      </table>
    </section>
  </body>
</html>