<?php
session_start();
include 'secrets.php';
include 'lib.php';
include 'grade_helpers.php';

$cookieConfigPath = __DIR__ . '/cookie.txt';
$c_session = curl_init();
if (!isset($_SESSION['logged']) || !file_exists($cookieConfigPath) || filesize($cookieConfigPath) == 0) {
  authSynergiaAcc($c_session);
  $_SESSION['logged'] = true;
} else {
  curl_setopt($c_session, CURLOPT_COOKIEFILE, $cookieConfigPath);
  curl_setopt($c_session, CURLOPT_COOKIEJAR, $cookieConfigPath);
  curl_setopt($c_session, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($c_session, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($c_session, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($c_session, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
}

// zbieramy zródło
$pageHtmlSource = executeGetReq($c_session, 'https://synergia.librus.pl/przegladaj_oceny/uczen');
if (strpos(curl_getinfo($c_session, CURLINFO_EFFECTIVE_URL), 'loguj') !== false) {
  die('Nie moglem sie zlaogowac... sprwadz hasla w pliku.');
}

$pageHtmlSource = str_replace('href="/', 'href="https://synergia.librus.pl/', $pageHtmlSource);
$pageHtmlSource = str_replace('src="/', 'src="https://synergia.librus.pl/', $pageHtmlSource);
$pageHtmlSource = str_replace('src="images/', 'src="https://synergia.librus.pl/images/', $pageHtmlSource);

$dom = new DOMDocument();
@$dom->loadHTML('<?xml encoding="UTF-8">' . $pageHtmlSource);
$xpath = new DOMXPath($dom);

foreach ($xpath->query('//th//a') as $header_link) {
  $header_link->removeAttribute('href');
  $header_link->setAttribute('style', 'cursor:default;text-decoration:none;color:inherit;');
}

$tabelaGlowaOcen = null;
$tabelaPomocOcen = null;

// przeszukwienie glownych tabelek
foreach ($xpath->query("//table[contains(@class,'decorated') and contains(@class,'stretch')]") as $wezel_tabelki) {
  $tekst_tabelki = mb_strtolower($wezel_tabelki->nodeValue, 'UTF-8');
  if ($tabelaGlowaOcen == null && strpos($tekst_tabelki, 'śr.i') !== false) {
    $tabelaGlowaOcen = $wezel_tabelki;
  } elseif ($tabelaGlowaOcen != null && $tabelaPomocOcen == null) {
    $tabelaPomocOcen = $wezel_tabelki;
  }
}
if ($tabelaGlowaOcen == null) {
  die('Ups, cos posozlo nie tak z pobeirniem html-a. Usun ciatka');
}

$main_cols = extractTableHeadersMap($xpath, $tabelaGlowaOcen, false);
$point_cols = $tabelaPomocOcen != null ? extractTableHeadersMap($xpath, $tabelaPomocOcen, true) : null;
$point_data = [];
if ($tabelaPomocOcen != null && $point_cols != null) {
  foreach ($xpath->query(".//tr[contains(@class,'line')]", $tabelaPomocOcen) as $point_row) {
    $subject = fetchSubjectNameFixed($xpath, $point_row);
    if ($subject != '') {
      $point_data[$subject] = $point_row;
    }
  }
}

foreach ($xpath->query(".//tr[contains(@class,'line')]", $tabelaGlowaOcen) as $grade_row) {
  $cells = $xpath->query('./td', $grade_row);
  $subject_name = fetchSubjectNameFixed($xpath, $grade_row);
  if ($main_cols == null) {
    continue;
  }

  $cell_p1 = getCell($cells, $main_cols['ok1']);
  $cell_p2 = getCell($cells, $main_cols['ok2']);

  if ($subject_name != '' && isset($point_data[$subject_name])) {
    $p_cells = $xpath->query('./td', $point_data[$subject_name]);
    mergePointsHtml($dom, $cell_p1, getCell($p_cells, $point_cols['ok1']));
    mergePointsHtml($dom, $cell_p2, getCell($p_cells, $point_cols['ok2']));
  }

  $oceny1 = gatherGradesList($xpath, $cell_p1);
  $oceny2 = gatherGradesList($xpath, $cell_p2);
  $avg1 = calculateFinalAverage($oceny1);
  $avg2 = calculateFinalAverage($oceny2);

  $yearly_grades = ['normal' => [], 'points' => []];
  foreach ($oceny1['normal'] as $v) {
    $yearly_grades['normal'][] = $v;
  }
  foreach ($oceny2['normal'] as $v) {
    $yearly_grades['normal'][] = $v;
  }
  foreach ($oceny1['points'] as $v) {
    $yearly_grades['points'][] = $v;
  }
  foreach ($oceny2['points'] as $v) {
    $yearly_grades['points'][] = $v;
  }
  $avg_year = calculateFinalAverage($yearly_grades);

  setText($dom, getCell($cells, $main_cols['sr1']), $avg1);
  setText($dom, getCell($cells, $main_cols['sr2']), $avg2);
  if (isset($main_cols['sr_yr'])) {
    setText($dom, getCell($cells, $main_cols['sr_yr']), $avg_year);
  }
}

foreach ($xpath->query('//h3') as $header_h3) {
  if (strpos(mb_strtolower($header_h3->nodeValue, 'UTF-8'), 'punktowe') !== false) {
    $header_h3->parentNode->removeChild($header_h3);
    break;
  }
}
if ($tabelaPomocOcen != null && $tabelaPomocOcen->parentNode != null) {
  $tabelaPomocOcen->parentNode->removeChild($tabelaPomocOcen);
}

$body = $dom->getElementsByTagName('body')->item(0);
$scripts = [];
foreach ($xpath->query('.//script', $body) as $s) {
  $scripts[] = $s->cloneNode(true);
}
while ($body->firstChild) {
  $body->removeChild($body->firstChild);
}

$studentViewHtml = executeGetReq($c_session, 'https://synergia.librus.pl/uczen/index');
$studentDom = new DOMDocument();
@$studentDom->loadHTML('<?xml encoding="UTF-8">' . $studentViewHtml);
$studentXp = new DOMXPath($studentDom);
$elementNumerku = $studentXp->query("//span[@class='luckyNumber']")->item(0);
$lucky_num = $elementNumerku ? trim($elementNumerku->nodeValue) : 'brak';

$gornyPasek = $dom->createElement('div');
$gornyPasek->setAttribute(
  'style',
  'margin:20px 10px;padding:15px;background:#fafafa;border:1px solid #ccc;border-radius:4px;display:flex;align-items:center;font-family:Arial,Tahoma;',
);

$btn1 = $dom->createElement('button', "Wyślij szczęśliwy numerek: $lucky_num");
$btn1->setAttribute('onclick', "fetch('cron.php').then(()=>alert('Wysłano!'));");
$btn1->setAttribute('type', 'button');
$btn1->setAttribute('class', 'btn');
$btn1->setAttribute(
  'style',
  'padding:0 5px;background:linear-gradient(to bottom, #ffffff 0%, #e6e6e6 100%);border:1px solid #ccc;color:#333;border-radius:3px;font-weight:bold;cursor:pointer;font-family:Arial,Tahoma;text-decoration:none;',
);

$formularzPdf = $dom->createElement('form');
$formularzPdf->setAttribute('action', 'pdf.php');
$formularzPdf->setAttribute('method', 'GET');
$formularzPdf->setAttribute('target', '_blank');
$formularzPdf->setAttribute('style', 'margin:0 0 0 20px;display:flex;align-items:center;');

$dropdownOrientacji = $dom->createElement('select');
$dropdownOrientacji->setAttribute('name', 'o');
$dropdownOrientacji->setAttribute(
  'style',
  'padding:4px;border-radius:3px;border:1px solid #ccc;margin-right:5px;font-family:Arial,Tahoma;',
);
$opt1 = $dom->createElement('option', 'Pion');
$opt1->setAttribute('value', 'P');
$opt2 = $dom->createElement('option', 'Poziom');
$opt2->setAttribute('value', 'L');
$dropdownOrientacji->appendChild($opt1);
$dropdownOrientacji->appendChild($opt2);

$guzikPdfowy = $dom->createElement('button', 'Generuj PDF');
$guzikPdfowy->setAttribute('type', 'submit');
$guzikPdfowy->setAttribute('class', 'btn');
$guzikPdfowy->setAttribute(
  'style',
  'padding:0 15px;background:linear-gradient(to bottom, #ffffff 0%, #e6e6e6 100%);border:1px solid #ccc;color:#333;border-radius:3px;font-weight:bold;cursor:pointer;font-family:Arial,Tahoma;text-decoration:none;',
);

$formularzPdf->appendChild($dropdownOrientacji);
$formularzPdf->appendChild($guzikPdfowy);

$gornyPasek->appendChild($btn1);
$gornyPasek->appendChild($formularzPdf);
$body->appendChild($gornyPasek);
$container = $dom->createElement('div');
$container->setAttribute('style', 'padding:10px;');
$container->appendChild($tabelaGlowaOcen);
$body->appendChild($container);

$fix_script = $dom->createElement(
  'script',
  '
const originalSet = Object.getOwnPropertyDescriptor(HTMLImageElement.prototype, "src").set;
Object.defineProperty(HTMLImageElement.prototype, "src", {
  set: function(value) {
    if (typeof value === "string" && value.startsWith("/images/tree_")) {
      value = "https://synergia.librus.pl" + value;
    }
    originalSet.call(this, value);
  }
});
',
);
$body->appendChild($fix_script);

foreach ($scripts as $s) {
  $body->appendChild($s);
}

echo $dom->saveHTML();
