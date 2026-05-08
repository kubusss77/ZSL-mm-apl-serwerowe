<?php

// string z geta
function executeGetReq($c_session, $url) {
  curl_setopt($c_session, CURLOPT_HTTPGET, 1);
  curl_setopt($c_session, CURLOPT_URL, $url);
  return curl_exec($c_session);
}

// post
function executePostReq($c_session, $url, $data) {
  curl_setopt($c_session, CURLOPT_POST, 1);
  curl_setopt($c_session, CURLOPT_POSTFIELDS, http_build_query($data));
  curl_setopt($c_session, CURLOPT_URL, $url);
  return curl_exec($c_session);
}

// logowanie do librusa (czasami wywala jak jest za duzo prob!)
function authSynergiaAcc($c_session) {
  global $librus_user, $librus_passwd;
  $cookieConfigPath = __DIR__ . '/cookie.txt';
  curl_setopt($c_session, CURLOPT_COOKIEFILE, $cookieConfigPath);
  curl_setopt($c_session, CURLOPT_COOKIEJAR, $cookieConfigPath);
  curl_setopt($c_session, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($c_session, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($c_session, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($c_session, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

  executeGetReq($c_session, 'https://synergia.librus.pl/loguj/portalRodzina?v=1772633308');
  executePostReq($c_session, 'https://api.librus.pl/OAuth/Authorization?client_id=46', [
    'action' => 'login',
    'login' => $librus_user,
    'pass' => $librus_passwd,
  ]);
  executePostReq($c_session, 'https://api.librus.pl/OAuth/Authorization/2FA?client_id=46', [
    'command' => 'open_synergia_window',
    'commandPayload' => ['url' => 'https://synergia.librus.pl/uczen/index'],
  ]);
}

function fetchSubjectNameFixed($xp, $row) {
  $cell = $xp->query(".//td[contains(@class,'left')]", $row)->item(0) ?? $xp->query('./td[2]', $row)->item(0);
  if ($cell == null) {
    return '';
  }
  $text = trim($cell->nodeValue);
  $text = str_replace(["\r", "\n", "\t"], ' ', $text);
  while (strpos($text, '  ') !== false) {
    $text = str_replace('  ', ' ', $text);
  }
  return mb_strtolower($text, 'UTF-8');
}

function parseHtmlTableToGrid($xp, $table) {
  $rows = $xp->query('.//thead/tr', $table);
  if ($rows->length == 0) {
    $rows = $xp->query('.//tr[th]', $table);
  }
  $grid = [];
  $spans = [];
  $row_i = 0;
  foreach ($rows as $row) {
    $new_spans = [];
    foreach ($spans as $col => $left) {
      if ($left - 1 > 0) {
        $new_spans[$col] = $left - 1;
      }
    }
    $spans = $new_spans;
    $col_i = 0;
    foreach ($xp->query('./th|./td', $row) as $cell) {
      while (isset($spans[$col_i])) {
        $col_i++;
      }
      $cs = max(1, (int) $cell->getAttribute('colspan'));
      $rs = max(1, (int) $cell->getAttribute('rowspan'));
      $val = mb_strtolower(trim($cell->nodeValue), 'UTF-8');
      for ($c = 0; $c < $cs; $c++) {
        $grid[$row_i][$col_i + $c] = $val;
        if ($rs > 1) {
          $spans[$col_i + $c] = $rs;
        }
      }
      $col_i += $cs;
    }
    $row_i++;
  }
  return $grid;
}

function locateColumnRange($grid, $label) {
  foreach ($grid as $row) {
    $start = null;
    $end = null;
    foreach ($row as $col => $txt) {
      if ($txt == $label) {
        if ($start == null) {
          $start = $col;
        }
        $end = $col;
      }
    }
    if ($start != null) {
      return [$start, $end];
    }
  }
  return null;
}

function findCol($grid, $range, $word) {
  foreach ($grid as $row) {
    for ($i = $range[0]; $i <= $range[1]; $i++) {
      if (isset($row[$i]) && strpos($row[$i], $word) !== false) {
        return $i;
      }
    }
  }
  return null;
}

function findColExact($grid, $range, $labels) {
  foreach ($grid as $w) {
    for ($i = $range[0]; $i <= $range[1]; $i++) {
      if (!isset($w[$i])) {
        continue;
      }
      foreach ($labels as $l) {
        if ($w[$i] == $l) {
          return $i;
        }
      }
    }
  }
  return null;
}

function extractTableHeadersMap($xp, $table, $pts_only = false) {
  $grid = parseHtmlTableToGrid($xp, $table);
  if (empty($grid)) {
    return null;
  }
  $range1 = locateColumnRange($grid, 'okres 1');
  $range2 = locateColumnRange($grid, 'okres 2');
  $range_yr = locateColumnRange($grid, 'koniec roku');
  if ($range1 == null || $range2 == null) {
    return null;
  }
  $cols = [];
  $cols['ok1'] = findCol($grid, $range1, 'oceny');
  $cols['ok2'] = findCol($grid, $range2, 'oceny');
  if (!$pts_only) {
    $cols['sr1'] = findCol($grid, $range1, 'śr.');
    $cols['sr2'] = findCol($grid, $range2, 'śr.');
    if ($range_yr != null) {
      $cols['sr_yr'] = findCol($grid, $range_yr, 'śr.');
    }
  }
  if ($cols['ok1'] == null || $cols['ok2'] == null) {
    return null;
  }
  return $cols;
}

function getCell($cells, $n) {
  $i = 0;
  foreach ($cells as $c) {
    if ($i == $n) {
      return $c;
    }
    $i++;
  }
  return null;
}

function setText($doc, $cell, $txt) {
  if ($cell == null) {
    return;
  }
  while ($cell->firstChild) {
    $cell->removeChild($cell->firstChild);
  }
  $cell->appendChild($doc->createTextNode($txt));
}

function parseGradeValueRaw($txt) {
  $txt = trim($txt);
  if (strlen($txt) == 0) {
    return null;
  }
  $base = substr($txt, 0, 1);
  if (!is_numeric($base)) {
    return null;
  }
  $n = (float) $base;
  if ($n < 1 || $n > 6) {
    return null;
  }
  if (strlen($txt) > 1) {
    $mod = substr($txt, 1, 1);
    if ($mod == '+') {
      $n += 0.5;
    } elseif ($mod == '-') {
      $n -= 0.25;
    }
  }
  return $n;
}

function getWeight($el) {
  if ($el == null) {
    return 1.0;
  }
  foreach (['data-weight', 'data-waga', 'title', 'data-tooltip'] as $attr) {
    $v = $el->getAttribute($attr);
    if ($v != '' && strpos(strtolower($v), 'waga') !== false) {
      $p = explode(':', $v);
      if (count($p) > 1) {
        $ws = str_replace([' ', ','], ['', '.'], $p[1]);
        $clean = '';
        for ($i = 0; $i < strlen($ws); $i++) {
          $c = substr($ws, $i, 1);
          if (is_numeric($c) || $c == '.') {
            $clean .= $c;
          } else {
            break;
          }
        }
        if ($clean != '') {
          return (float) $clean;
        }
      }
    }
  }
  return 1.0;
}

function mapPercentageToGradeValue($r) {
  if ($r >= 1.0) {
    return 6.0;
  }
  if ($r < 0.0) {
    return 1.0;
  }
  if ($r < 0.4) {
    return 1.0 + $r / 0.4;
  }
  if ($r < 0.5) {
    return 2.0 + ($r - 0.4) / 0.1;
  }
  if ($r < 0.7) {
    return 3.0 + ($r - 0.5) / 0.2;
  }
  if ($r < 0.9) {
    return 4.0 + ($r - 0.7) / 0.2;
  }
  return 5.0 + ($r - 0.9) / 0.1;
}

function calculateFinalAverage($g) {
  $s_pts = null;
  $s_norm = null;
  if (isset($g['points']) && count($g['points']) > 0) {
    $sum_got = 0;
    $sum_max = 0;
    foreach ($g['points'] as $item) {
      $sum_got += $item[0];
      $sum_max += $item[1];
    }
    if ($sum_max > 0) {
      $s_pts = mapPercentageToGradeValue($sum_got / $sum_max);
    }
  }
  if (isset($g['normal']) && count($g['normal']) > 0) {
    $sum = 0;
    $w_sum = 0;
    foreach ($g['normal'] as $item) {
      $sum += $item[0] * $item[1];
      $w_sum += $item[1];
    }
    if ($w_sum > 0) {
      $s_norm = $sum / $w_sum;
    }
  }
  if ($s_pts !== null && $s_norm !== null) {
    return number_format(($s_pts + $s_norm) / 2, 2);
  }
  if ($s_pts !== null) {
    return number_format($s_pts, 2);
  }
  if ($s_norm !== null) {
    return number_format($s_norm, 2);
  }
  return '-';
}
