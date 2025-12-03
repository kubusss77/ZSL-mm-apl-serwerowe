<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .border td {
            border-top: 1px solid #bbb;
        }

        td, th {
            padding: 5px;
        }
    </style>
    <?php
        $db = new PDO("mysql:dbname=film_rentals;host=localhost", "root", "ServBay.dev");
    ?>
</head>
<body>
    <form action="<?php echo $_SERVER['REQUEST_URI'] ?>" method="POST">
        Gatunek:
        <?php
            $selectValue = 'all';
            if(isset($_POST['filtr-gatunek'])) {
                $selectValue = $_POST['filtr-gatunek'];

                $nazwaGatunkuQuery = $db->query('select nazwa from gatunki where id = "' . $_POST['filtr-gatunek'] . '"');
                $nazwaGatunku = $nazwaGatunkuQuery->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_FIRST);
            }
        ?>
        <select name="filtr-gatunek" value="<?php echo $nazwaGatunku[0] ?>">
            <option value="all">wszystkie</option>
            <?php
                $rows = $db->query('select * from gatunki');
                foreach($rows as $row) {
                    echo '<option value="' . $row['id'] . '"> ' . $row['nazwa'] . '</option>';
                }
            ?>
        </select>
        <input type='submit' value='filtruj'>
    <table>
        <tr>
            <th>Tytuł</th>
            <th>Gatunek</th>
            <th>
                <?php 
                    $dataValue = 'desc';
                    if(isset($_POST['filtr-data'])) {
                        if($_POST['filtr-data'] == 'desc') {
                            $dataValue = 'asc';
                        }
                    }
                ?>
                <input type="hidden" name="filtr-data" value="<?php echo $dataValue ?>">
                <input type="submit" value="Data dodania">
            </form>
            </th>
            <th>Status</th>
            <th>Ilość ocen</th>
            <th>Średnia ocen</th>
        </tr>

        <?php
            $sort = 'desc';
            if(isset($_POST['filtr-data'])) {
                $sort = $_POST['filtr-data'];
            }

            $rows = $db->query('select * from filmy order by data_dodania ' . $sort);
            
            if(isset($_POST['filtr-gatunek'])) {
                if($_POST['filtr-gatunek'] != 'all') {
                    $rows = $db->query('select * from filmy where id_gatunku = "' . $_POST['filtr-gatunek'] . '" order by data_dodania ' . $sort);
                }
            }
            
            foreach($rows as $row) {
                echo '<tr class="border">';
                echo '<td>' . $row['tytul'] . '</td>';
                echo '<td>' . $row['id_gatunku'] . '</td>';
                echo '<td>' . $row['data_dodania'] . '</td>';
                echo '<td>' . $row['status'] . '</td>';
                echo '<td>' . $row['ilosc_ocen'] . '</td>';
                echo '<td>' . $row['srednia_ocen'] . '</td>';
                echo '</tr>';
            }
        ?>
    </table>
</body>
</html>

