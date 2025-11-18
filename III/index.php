<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    .border td {
      padding: 3px;
      border-top: 1px solid #bbb;
    }

    input {
      width: 50px;
    }

    .center {
      text-align: center;
      padding-top: 20px;
    }
  </style>
  <title>Document</title>
  <?php include('db.php'); ?>
</head>
<body>
  <table>
    <tr>
      <th></th>
      <th>Nominał</th>
      <th>Nr kat.</th>
      <th>Stop</th>
      <th>Rok</th>
      <th></th>
    </tr>
    <?php 
      $rows = $db->query('select * from dane');
      foreach($rows as $row) {
        $alloyQuery = $db->query('select stop from stopy where id = "' . $row['stop'] . '"');
        $alloy = $alloyQuery->fetch(PDO::FETCH_ASSOC);

        echo '<tr class="border">';
        echo '<td>' . $row['flaga'] . '</td>';
        echo '<td>' . $row['nominal'] . '</td>';
        echo '<td> km#' . $row['nrkat'] . '</td>';
        echo '<td>' . $alloy['stop'] . '</td>';
        echo '<td>' . $row['rok'] . '</td>';
        echo '</tr>';
      }
    ?>

    <tr>
      <td colspan="6" class="center">Dodawanie rekordu</td>
    </tr>

    <form method="POST" action="<?php echo $_SERVER['REQUEST_URI'] . '/add.php'; ?>">
      <tr>
        <td>
          <select name="flaga">
            <?php
              $flags = $db->query('select * from flagi');
              foreach($flags as $flag) {
                echo '<option ' . 'value="' . $flag['id'] . '">' . $flag['kraj'] . '</option>';
              }
            ?>
          </select>
        </td>
        <td><input name="nominal"></td>
        <td><input name="nrkat"></td>
        <td>
          <select name="stop">
            <?php
              $alloys = $db->query('select * from stopy');
              foreach($alloys as $alloy) {
                echo '<option ' . 'value="' . $alloy['id'] . '">' . $alloy['stop'] . '</option>';
              }
            ?>
          </select>
        </td>
        <td><input name="rok"></td>
        <td><input type="submit" value="✓"></td>
      </tr>
      <input type="hidden" name="redirect" value=<?php echo $_SERVER['REQUEST_URI'] ?>>
    </form>
  </table>
  <br>
  <form>

  </form>
  <?php


  ?>
</body>
</html>


