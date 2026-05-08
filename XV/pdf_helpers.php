<?php

function drawHeader($pdf, $h1, $h1_w, $h1_a, $h2, $h2_w, $h2_a) {
  $pdf->SetFont('dejavusans', 'B', 9);
  drawRow($pdf, $h1, $h1_w, $h1_a, true);
  drawRow($pdf, $h2, $h2_w, $h2_a, true);
  $pdf->SetFont('dejavusans', '', 9);
}

function getRowH($pdf, $komorki, $szerokosci) {
  $max = 0;
  foreach ($komorki as $i => $k) {
    $h = $pdf->getStringHeight($szerokosci[$i], $k);
    if ($h > $max) {
      $max = $h;
    }
  }
  return $max;
}

function drawRow($pdf, $komorki, $szerokosci, $wyrownania, $wypelnienie) {
  $startX = $pdf->GetX();
  $startY = $pdf->GetY();
  $rowHeight = getRowH($pdf, $komorki, $szerokosci);
  foreach ($komorki as $i => $k) {
    $pdf->MultiCell(
      $szerokosci[$i],
      $rowHeight,
      $k,
      1,
      $wyrownania[$i],
      $wypelnienie,
      0,
      $pdf->GetX(),
      $startY,
      true,
      0,
      false,
      true,
      $rowHeight,
      'M',
    );
  }
  $pdf->SetXY($startX, $startY + $rowHeight);
}
