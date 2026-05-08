<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';
include 'secrets.php';
include 'lib.php';
include 'grade_helpers.php';
include 'pdf_helpers.php';

$orientacja = isset($_GET['o']) && strtoupper($_GET['o']) == 'L' ? 'L' : 'P';

// klasa pomocnicza do generowania pdf.
class MYPDF extends TCPDF {
  public function Header() {
    $this->SetFont('dejavusans', 'B', 12);
    $this->Cell(0, 10, 'Oceny bieżące', 0, 0, 'L');
    $this->SetFont('dejavusans', '', 10);
    $this->Cell(0, 10, 'PDF wygenerowano: ' . date('d-m-Y H:i:s'), 0, 1, 'R');
  }
}

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
  die('Nie moglem sie zalogowac, sprawdz hasla w pliku.');
}

$dom = new DOMDocument();
@$dom->loadHTML('<?xml encoding="UTF-8">' . $pageHtmlSource);
$xpath = new DOMXPath($dom);

$tabela1 = null;
$tabela2 = null;

// przeszukjiwanie glownych tabelek
foreach ($xpath->query("//table[contains(@class,'decorated') and contains(@class,'stretch')]") as $tabela) {
  $tekst_tabeli = mb_strtolower($tabela->nodeValue, 'UTF-8');
  if ($tabela1 == null && strpos($tekst_tabeli, 'śr.i') !== false) {
    $tabela1 = $tabela;
  } elseif ($tabela1 != null && $tabela2 == null) {
    $tabela2 = $tabela;
  }
}

if ($tabela1 == null) {
  die('Nie widac tabelki. Moze stona zmienila struktore?');
}

$kolumny_glowne = extractTableHeadersMap($xpath, $tabela1, false);
$kolumny_punktowe = $tabela2 != null ? extractTableHeadersMap($xpath, $tabela2, true) : null;

$siatka = parseHtmlTableToGrid($xpath, $tabela1);
$zakres1 = locateColumnRange($siatka, 'okres 1');
$zakres2 = locateColumnRange($siatka, 'okres 2');
$zakres_rok = locateColumnRange($siatka, 'koniec roku');

$kol_i_par = $zakres1 != null ? findColExact($siatka, $zakres1, ['(i)']) : null;
$kol_i = $zakres1 != null ? findColExact($siatka, $zakres1, ['i']) : null;
$kol_ii_par = $zakres2 != null ? findColExact($siatka, $zakres2, ['(ii)']) : null;
$kol_ii = $zakres2 != null ? findColExact($siatka, $zakres2, ['ii']) : null;
$kol_zach_ii = $zakres2 != null ? findCol($siatka, $zakres2, 'zachowanie') : null;
$kol_r = $zakres_rok != null ? findColExact($siatka, $zakres_rok, ['r', 'ocena roczna']) : null;

$pointRows = [];
if ($tabela2 != null && $kolumny_punktowe != null) {
  foreach ($xpath->query(".//tr[contains(@class,'line')]", $tabela2) as $pointRow) {
    $subject = fetchSubjectNameFixed($xpath, $pointRow);
    if ($subject != '') {
      $pointRows[$subject] = $pointRow;
    }
  }
}

$rows = [];

foreach ($xpath->query(".//tr[contains(@class,'line')]", $tabela1) as $gradeRow) {
  $cells = $xpath->query('./td', $gradeRow);
  $subjectName = fetchSubjectNameFixed($xpath, $gradeRow);
  if ($subjectName == '' || $kolumny_glowne == null) {
    continue;
  }

  $oceny1 = gatherPdfInfoList($xpath, getCell($cells, $kolumny_glowne['ok1']));
  $oceny2 = gatherPdfInfoList($xpath, getCell($cells, $kolumny_glowne['ok2']));

  if (isset($pointRows[$subjectName]) && $kolumny_punktowe != null) {
    $pointCells = $xpath->query('./td', $pointRows[$subjectName]);
    $oceny1 = combineAraysToPdf($oceny1, gatherPdfInfoList($xpath, getCell($pointCells, $kolumny_punktowe['ok1'])));
    $oceny2 = combineAraysToPdf($oceny2, gatherPdfInfoList($xpath, getCell($pointCells, $kolumny_punktowe['ok2'])));
  }

  $avg1 = calculateFinalAverage($oceny1['calc']);
  $avg2 = calculateFinalAverage($oceny2['calc']);
  $srednia_rok = calculateFinalAverage(combineAraysToPdf($oceny1, $oceny2)['calc']);

  $ocena_i_par = $kol_i_par != null ? gradeFromCell(getCell($cells, $kol_i_par)) : '-';
  $ocena_i = $kol_i != null ? gradeFromCell(getCell($cells, $kol_i)) : '-';
  $ocena_ii_par = $kol_ii_par != null ? gradeFromCell(getCell($cells, $kol_ii_par)) : '-';
  $ocena_ii = $kol_ii != null ? gradeFromCell(getCell($cells, $kol_ii)) : '-';
  $zachowanie_ii = $kol_zach_ii != null ? cellTextValue(getCell($cells, $kol_zach_ii)) : '-';
  $ocena_r = $kol_r != null ? gradeFromCell(getCell($cells, $kol_r)) : '-';

  $ma_dane =
    !empty($oceny1['disp']) ||
    !empty($oceny2['disp']) ||
    $avg1 != '-' ||
    $avg2 != '-' ||
    $srednia_rok != '-' ||
    $ocena_i_par != '-' ||
    $ocena_i != '-' ||
    $ocena_ii_par != '-' ||
    $ocena_ii != '-' ||
    $zachowanie_ii != '-' ||
    $ocena_r != '-';
  if (!$ma_dane) {
    continue;
  }

  $rows[] = [
    'nazwa' => $subjectName,
    'o1' => buildLine($oceny1['disp']),
    'sr1' => $avg1,
    'i_par' => $ocena_i_par,
    'i' => $ocena_i,
    'o2' => buildLine($oceny2['disp']),
    'sr2' => $avg2,
    'ii_par' => $ocena_ii_par,
    'ii' => $ocena_ii,
    'zach_ii' => $zachowanie_ii,
    'sr_r' => $srednia_rok,
    'r' => $ocena_r,
  ];
}

$pdf = new MYPDF($orientacja, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetMargins(10, 20, 10);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();
$pdf->SetFillColor(242, 242, 242);
$pdf->SetCellPaddings(1.5, 1.5, 1.5, 1.5);

$szer_strony = $pdf->getPageWidth() - 20;
$szer_mala = $orientacja == 'L' ? 12 : 10;
$szer_przedmiot = $orientacja == 'L' ? $szer_strony * 0.22 : $szer_strony * 0.24;
$szer_oceny = ($szer_strony - $szer_przedmiot - $szer_mala * 9) / 2;

$naglowek1 = ['Przedmiot', 'Okres 1', 'Okres 2', 'Koniec roku'];
$szer_n1 = [$szer_przedmiot, $szer_oceny + $szer_mala * 3, $szer_oceny + $szer_mala * 4, $szer_mala * 2];
$naglowek2 = [
  '',
  'Oceny bieżące',
  'Śr.I',
  '(I)',
  'I',
  'Oceny bieżące',
  'Śr.II',
  '(II)',
  'II',
  'Zachowanie',
  'Śr.R',
  'R',
];
$szer_n2 = [
  $szer_przedmiot,
  $szer_oceny,
  $szer_mala,
  $szer_mala,
  $szer_mala,
  $szer_oceny,
  $szer_mala,
  $szer_mala,
  $szer_mala,
  $szer_mala,
  $szer_mala,
  $szer_mala,
];
$wyrownanie = ['L', 'L', 'C', 'C', 'C', 'L', 'C', 'C', 'C', 'C', 'C', 'C'];

drawHeader($pdf, $naglowek1, $szer_n1, ['C', 'C', 'C', 'C'], $naglowek2, $szer_n2, $wyrownanie);

foreach ($rows as $row) {
  $cells = [
    mb_convert_case($row['nazwa'], MB_CASE_TITLE, 'UTF-8'),
    $row['o1'],
    $row['sr1'],
    $row['i_par'],
    $row['i'],
    $row['o2'],
    $row['sr2'],
    $row['ii_par'],
    $row['ii'],
    $row['zach_ii'],
    $row['sr_r'],
    $row['r'],
  ];
  $rowHeight = getRowH($pdf, $cells, $szer_n2);
  if ($pdf->GetY() + $rowHeight > $pdf->getPageHeight() - 15) {
    $pdf->AddPage();
    drawHeader($pdf, $naglowek1, $szer_n1, ['C', 'C', 'C', 'C'], $naglowek2, $szer_n2, $wyrownanie);
  }
  drawRow($pdf, $cells, $szer_n2, $wyrownanie, false);
}

$pdf->Output('oceny_librus.pdf', 'I');
