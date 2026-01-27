<?php
$df_month = new IntlDateFormatter(
  'pl_PL', // wspierane w danym systemie Linux locale można sprawdzić poleceniem "locale -a"
  IntlDateFormatter::LONG,
  IntlDateFormatter::NONE,
  null,
  null,
  'LLLL', // formaty
);
?>
