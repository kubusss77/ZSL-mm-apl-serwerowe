<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';
require 'secrets.php';

function getMailer() {
  global $mailUser, $mailPass;
  $mail = new PHPMailer(true);

  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = $mailUser; // gmail user
  $mail->Password = $mailPass; // gmail password
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port = 587;
  $mail->CharSet = 'UTF-8';
  $mail->setFrom($mailUser, 'Planer Bot'); // gmail user
  return $mail;
}
