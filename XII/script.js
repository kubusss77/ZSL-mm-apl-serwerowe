let options = { flagi: [], materialy: [] };
let dataRows = [];

document.addEventListener('DOMContentLoaded', () => {
  fetchOptions().then(() => {
    fetchData();
  });
});

// flagi + stopy do selectow
async function fetchOptions() {
  const res = await fetch('api.php?action=get_options');
  options = await res.json();

  populateSelect(document.getElementById('add_flaga'), options.flagi);
  populateSelect(document.getElementById('add_stop'), options.materialy);
}

function populateSelect(selectElem, dataOptions, selectedId = null) {
  selectElem.innerHTML = '';
  dataOptions.forEach((opt) => {
    const option = document.createElement('option');
    option.value = opt.id;

    option.textContent = opt.nazwa.replace(/\.jpg$/i, '');
    if (selectedId == opt.id) option.selected = true;
    selectElem.appendChild(option);
  });
}

async function fetchData() {
  const res = await fetch('api.php?action=get_data');
  dataRows = await res.json();
  renderTable();
}

function renderTable() {
  const tbody = document.querySelector('#dataTable tbody');
  tbody.innerHTML = '';

  dataRows.forEach((row) => {
    const tr = document.createElement('tr');
    tr.dataset.id = row.id;

    const flagaNazwaDoWyswietlenia = row.flaga_nazwa ? row.flaga_nazwa.replace(/\.jpg$/i, '') : '';
    const flagaHtml = row.flaga_nazwa
      ? `<img src="gfx/${row.flaga_nazwa}" alt="${flagaNazwaDoWyswietlenia}" style="max-width: 40px; vertical-align: middle;">`
      : '';

    tr.innerHTML = `
            <td class="col-flaga" data-val="${row.flaga_id}">${flagaHtml}</td>
            <td class="col-nominal">${row.nominal}</td>
            <td class="col-nr_kat">km#${row.nr_kat}</td>
            <td class="col-stop" data-val="${row.stop_id}">${row.stop_nazwa || ''}</td>
            <td class="col-rok">${row.rok}</td>
            <td class="col-akcja"><img src="gfx/u.gif" class="action-icon" onclick="deleteRecord(event, ${row.id})"></td>
        `;

    // klik na rekord
    const colsToEdit = tr.querySelectorAll('td:not(.col-akcja)');
    colsToEdit.forEach((td) => td.addEventListener('click', () => makeEditable(tr, row)));

    tbody.appendChild(tr);
  });
}

function makeEditable(tr, row) {
  if (tr.classList.contains('edit-mode')) return;
  tr.classList.add('edit-mode');

  const cols = tr.querySelectorAll('td:not(.col-akcja)');
  cols.forEach((td) => {
    const newTd = td.cloneNode(true);
    td.parentNode.replaceChild(newTd, td);
  });

  // Replace flaga
  const tdFlaga = tr.querySelector('.col-flaga');
  const flagaSelect = document.createElement('select');
  populateSelect(flagaSelect, options.flagi, row.flaga_id);
  tdFlaga.innerHTML = '';
  tdFlaga.appendChild(flagaSelect);

  // Replace nominal
  const tdNominal = tr.querySelector('.col-nominal');
  tdNominal.innerHTML = `<input type="text" value="${row.nominal}">`;

  // Replace nrkat
  const tdNrKat = tr.querySelector('.col-nr_kat');
  tdNrKat.innerHTML = `<input type="text" value="${row.nr_kat}">`;

  // Replace stop
  const tdStop = tr.querySelector('.col-stop');
  const stopSelect = document.createElement('select');
  populateSelect(stopSelect, options.materialy, row.stop_id);
  tdStop.innerHTML = '';
  tdStop.appendChild(stopSelect);

  // Replace rok
  const tdRok = tr.querySelector('.col-rok');
  tdRok.innerHTML = `<input type="text" value="${row.rok}">`;

  // Replace action
  const tdAkcja = tr.querySelector('.col-akcja');
  tdAkcja.innerHTML = `
        <img src="gfx/faja.png" class="action-icon" onclick="saveRecord(event, ${row.id}, this)">
    `;
}

async function saveRecord(event, id, btn) {
  event.stopPropagation();

  const tr = btn.closest('tr');

  const flagaSelect = tr.querySelector('.col-flaga select');
  const stopSelect = tr.querySelector('.col-stop select');
  const nominalInput = tr.querySelector('.col-nominal input');
  const nrKatInput = tr.querySelector('.col-nr_kat input');
  const rokInput = tr.querySelector('.col-rok input');

  const updatedData = {
    id: id,
    flaga_id: flagaSelect.value,
    stop_id: stopSelect.value,
    nominal: nominalInput.value,
    nr_kat: nrKatInput.value,
    rok: rokInput.value,
  };

  const res = await fetch('api.php?action=edit', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(updatedData),
  });

  if (res.ok) {
    // aktualizacja danyvh
    const rowIndex = dataRows.findIndex((r) => r.id == id);
    if (rowIndex !== -1) {
      const flagaPelnadNazwa = options.flagi.find((f) => f.id == updatedData.flaga_id)?.nazwa;

      dataRows[rowIndex] = {
        ...dataRows[rowIndex],
        ...updatedData,
        flaga_nazwa: flagaPelnadNazwa,
        stop_nazwa: options.materialy.find((m) => m.id == updatedData.stop_id)?.nazwa,
      };
    }
    renderTable();
  }
}

async function addRecord() {
  const flagaSelect = document.getElementById('add_flaga');
  const stopSelect = document.getElementById('add_stop');
  const nominalInput = document.getElementById('add_nominal');
  const nrKatInput = document.getElementById('add_nr_kat');
  const rokInput = document.getElementById('add_rok');

  if (!nominalInput.value.trim() && !nrKatInput.value.trim() && !rokInput.value.trim()) return;

  const newData = {
    flaga_id: flagaSelect.value,
    stop_id: stopSelect.value,
    nominal: nominalInput.value,
    nr_kat: nrKatInput.value,
    rok: rokInput.value,
  };

  const res = await fetch('api.php?action=add', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(newData),
  });

  const text = await res.text();
  try {
    const result = JSON.parse(text);
    if (result.id) {
      newData.id = result.id;
      const flagaPelnadNazwa = options.flagi.find((f) => f.id == newData.flaga_id)?.nazwa;
      newData.flaga_nazwa = flagaPelnadNazwa;
      newData.stop_nazwa = options.materialy.find((m) => m.id == newData.stop_id)?.nazwa;

      dataRows.push(newData);
      renderTable();

      // Wyczyść inputy
      nominalInput.value = '';
      nrKatInput.value = '';
      rokInput.value = '';
      flagaSelect.selectedIndex = 0;
      stopSelect.selectedIndex = 0;
    }
  } catch (e) {
    console.error('SyntaxError: did not get valid JSON from api.php. Response: ', text);
  }
}

async function deleteRecord(event, id) {
  event.stopPropagation();

  if (confirm('Czy na pewno?')) {
    const res = await fetch('api.php?action=delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id }),
    });

    if (res.ok) {
      dataRows = dataRows.filter((r) => r.id != id);
      renderTable();
    }
  }
}
