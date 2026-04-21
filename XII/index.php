<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monetki</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<table id="dataTable">
    <thead>
        <tr>
            <th></th>
            <th>Nominał</th>
            <th>Nr. Kat</th>
            <th>Stop</th>
            <th>Rok</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <!-- wierzse z script.js -->
    </tbody>
    <tfoot>
        <tr id="addRow" class="edit-mode">
            <td><select id="add_flaga"></select></td>
            <td><input type="text" id="add_nominal" placeholder="Nominał" /></td>
            <td><input type="text" id="add_nr_kat" placeholder="Nr. Kat" /></td>
            <td><select id="add_stop"></select></td>
            <td><input type="text" id="add_rok" placeholder="Rok" /></td>
            <td><img src="gfx/faja.png" alt="Zapisz" class="action-icon" title="Zapisz" onclick="addRecord()"></td>
        </tr>
    </tfoot>
</table>

<script src="script.js"></script>
</body>
</html>
