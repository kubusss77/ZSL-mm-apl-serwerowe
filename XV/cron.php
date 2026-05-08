<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';
include 'secrets.php';
include 'lib.php';

$cookieConfigPath = __DIR__ . '/cookie.txt';
$c_session = curl_init();
curl_setopt($c_session, CURLOPT_COOKIEFILE, $cookieConfigPath);
curl_setopt($c_session, CURLOPT_COOKIEJAR, $cookieConfigPath);
curl_setopt($c_session, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($c_session, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($c_session, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($c_session, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

authSynergiaAcc($c_session);

// zbieramy źródło
$pageHtmlSource = executeGetReq($c_session, 'https://synergia.librus.pl/uczen/index');
$doc = new DOMDocument();
@$doc->loadHTML('<?xml encoding="UTF-8">' . $pageHtmlSource);
$xp = new DOMXPath($doc);

$elementNumerku = $xp->query("//span[@class='luckyNumber']")->item(0);
$lucky_num = $elementNumerku ? trim($elementNumerku->nodeValue) : 'Nieznany';

// inicjacja mailera
$mail = new PHPMailer(true);
try {
  $mail->isSMTP();
  $mail->Host = $mail_host;
  $mail->SMTPAuth = true;
  $mail->Username = $mail_user;
  $mail->Password = $mail_pass;
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = $mail_port;
  $mail->CharSet = 'UTF-8';

  $mail->setFrom($mail_from, 'Szczęśliwy numerek');
  $mail->addAddress($mail_to);

  $mail->isHTML(true);
  $mail->Subject = "Dzisiejszy szczęśliwy Numerek: $lucky_num";
  $mail->Body = "Siemanko!<br><br>Dzisiejszy szczęśliwy numerek to: <b>$lucky_num</b>";

  $mail->send();
  echo "Sukces: E-mail wysłany (Numerek: $lucky_num)\n";
} catch (Exception $e) {
  echo "Błąd: Nie udało się wysłać maila. PHPMailer Error: {$mail->ErrorInfo}\n";
}
