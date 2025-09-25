<?php

namespace Lylink\Mail;

use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    public static function send(string $targetMail, string $targetUsername, string $subject, string $body):void
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = 'smtp.seznam.cz';
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom($_ENV['SMTP_USERNAME'], 'LyLink');
        $mail->addAddress($targetMail, $targetUsername); //Add a recipient

                             //Content
        $mail->isHTML(true); //Set email format to HTML
        $mail->Subject = 'LyLink - ' . $subject;
        $mail->Body = $body;

        $plain = strip_tags($body);
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $mail->AltBody = $plain;

        $mail->send();
    }
}
