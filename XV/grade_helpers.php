<?php

// czysci smieci typu brak ocen bo psuje wyswietlanie
function clearEmptyGradeText($cell) {
  $xp = new DOMXPath($cell->ownerDocument);
  foreach ($xp->query('.//text()', $cell) as $node) {
    $node->nodeValue = str_replace('Brak ocen', '', $node->nodeValue);
  }
}

function mergePointsHtml($doc, $target, $src) {
  if ($target == null || $src == null) {
    return;
  }
  $txt = trim($src->nodeValue);
  if ($txt == '' || $txt == 'Brak ocen') {
    return;
  }
  $done = false;
  foreach ($src->getElementsByTagName('span') as $box) {
    if (strpos($box->getAttribute('class'), 'grade-box') !== false) {
      if (!$done) {
        clearEmptyGradeText($target);
      }
      $target->appendChild($doc->createTextNode(' '));
      $target->appendChild($box->cloneNode(true));
      $done = true;
    }
  }
  if ($done) {
    return;
  }
  $txt = str_replace(["\r", "\n", "\t"], ' ', $txt);
  $parts = explode(' ', $txt);
  $found = false;
  foreach ($parts as $p) {
    $p = trim($p);
    if (strpos($p, '/') !== false) {
      if (!$found) {
        clearEmptyGradeText($target);
        $found = true;
      }
      $span = $doc->createElement('span', $p);
      $span->setAttribute('class', 'grade-box');
      $span->setAttribute(
        'style',
        'display:inline-block;padding:1px 4px;margin:1px;border-radius:3px;font-size:.85em;background:#d0e8ff;color:#003;',
      );
      $target->appendChild($doc->createTextNode(' '));
      $target->appendChild($span);
    }
  }
}

// petla po wszystkich divach z ocenami
function gatherGradesList($xp, $cell) {
  $res = ['normal' => [], 'points' => []];
  if ($cell == null) {
    return $res;
  }
  foreach ($xp->query(".//*[contains(@class,'grade-box')]", $cell) as $el) {
    $val = trim($el->nodeValue);
    if (strpos($val, '/') !== false) {
      $pts = explode('/', $val);
      if (count($pts) == 2) {
        $got = (float) str_replace(',', '.', trim($pts[0]));
        $max = (float) str_replace(',', '.', trim($pts[1]));
        if ($max > 0) {
          $res['points'][] = [$got, $max];
        }
      }
    } else {
      $num = parseGradeValueRaw($val);
      if ($num == null) {
        continue;
      }
      $link = $xp->query('.//a', $el)->item(0);
      $w = getWeight($link ?? $el);
      $res['normal'][] = [$num, $w];
    }
  }
  return $res;
}

function gatherPdfInfoList($xp, $kom) {
  $wynik = ['disp' => [], 'calc' => ['normal' => [], 'points' => []]];
  if ($kom == null) {
    return $wynik;
  }
  $pudelka = $xp->query(".//*[contains(@class,'grade-box')]", $kom);
  foreach ($pudelka as $el) {
    $v = trim($el->nodeValue);
    $v = str_replace(["\r", "\n", "\t"], ' ', $v);
    while (strpos($v, '  ') !== false) {
      $v = str_replace('  ', ' ', $v);
    }
    if ($v == '' || mb_strtolower($v, 'UTF-8') == 'brak ocen') {
      continue;
    }
    if (strpos($v, '/') !== false) {
      $p = explode('/', $v);
      if (count($p) == 2) {
        $g = (float) str_replace(',', '.', trim($p[0]));
        $m = (float) str_replace(',', '.', trim($p[1]));
        if ($m > 0) {
          $wynik['calc']['points'][] = [$g, $m];
        }
      }
      $wynik['disp'][] = str_replace(' ', '', $v);
    } else {
      $num = parseGradeValueRaw($v);
      $waga = getWeight($xp->query('.//a', $el)->item(0) ?? $el);
      if ($num != null) {
        $wynik['calc']['normal'][] = [$num, $waga];
      }
      if ($waga != 1.0) {
        $ws = rtrim(rtrim(number_format($waga, 2, '.', ''), '0'), '.');
        $wynik['disp'][] = $v . ' (' . $ws . ')';
      } else {
        $wynik['disp'][] = $v;
      }
    }
  }
  return $wynik;
}

function combineAraysToPdf($a, $b) {
  return [
    'disp' => array_merge($a['disp'], $b['disp']),
    'calc' => [
      'normal' => array_merge($a['calc']['normal'], $b['calc']['normal']),
      'points' => array_merge($a['calc']['points'], $b['calc']['points']),
    ],
  ];
}

function buildLine($items) {
  return empty($items) ? '-' : implode(', ', $items);
}

function gradeFromCell($kom) {
  if ($kom == null) {
    return '-';
  }
  $t = trim($kom->nodeValue);
  if ($t == '' || $t == '-' || mb_strtolower($t, 'UTF-8') == 'brak ocen') {
    return '-';
  }
  for ($i = 0; $i < strlen($t); $i++) {
    $c = $t[$i];
    if (is_numeric($c) && (int) $c >= 1 && (int) $c <= 6) {
      $g = $c;
      if ($i + 1 < strlen($t)) {
        $n = $t[$i + 1];
        if ($n == '+' || $n == '-') {
          $g .= $n;
        }
      }
      return $g;
    }
  }
  return '-';
}

function cellTextValue($kom) {
  if ($kom == null) {
    return '-';
  }
  $t = trim($kom->nodeValue);
  $t = str_replace(["\r", "\n", "\t"], ' ', $t);
  while (strpos($t, '  ') !== false) {
    $t = str_replace('  ', ' ', $t);
  }
  return $t == '' || $t == '-' || mb_strtolower($t, 'UTF-8') == 'brak ocen' ? '-' : $t;
}
