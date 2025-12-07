<?php

namespace Lylink\Mail;

use Lylink\Data\EnvStore;
use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    public static function prepareMail(string $targetMail, string $targetUsername, string $subject, string $body, EnvStore $env): PHPMailer
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = $env->SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = $env->SMTP_USERNAME;
        $mail->Password = $env->SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom($env->SMTP_USERNAME, 'LyLink');
        $mail->addAddress($targetMail, $targetUsername); //Add a recipient

                             //Content
        $mail->isHTML(true); //Set email format to HTML
        $mail->Subject = 'LyLink - ' . $subject;
        $mail->Body = $body;

        $plain = strip_tags($body);
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $mail->AltBody = $plain;

        return $mail;
    }
}
