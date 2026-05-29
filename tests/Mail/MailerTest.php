<?php

use Lylink\Data\EnvStore;
use Lylink\Mail\Mailer;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    public function testPrepareMail(): void
    {
        $env = new EnvStore(stmp_host: 'stmp.test.test', stmp_username: 'test@test.test', stmp_password: 'test', client_id: 'test', client_secret: 'test', base_domain: 'test');

        $targetMail = 'test@test.test';
        $targetUsername = 'test';
        $subject = 'test';
        $body = '<p>test &amp; <strong>more</strong></p>';

        $mail = Mailer::prepareMail($targetMail, $targetUsername, $subject, $body, $env);

        $this::assertInstanceOf(PHPMailer::class, $mail);
        $this::assertSame([[$targetMail, $targetUsername]], $mail->getToAddresses());
        $this::assertSame("LyLink - " . $subject, $mail->Subject);
        $this::assertSame($body, $mail->Body);
        $this::assertSame('test & more', $mail->AltBody);
        $this::assertSame($env->SMTP_HOST, $mail->Host);

        $this::assertTrue($mail->SMTPAuth);
        $this::assertSame(PHPMailer::ENCRYPTION_SMTPS, $mail->SMTPSecure);
        $this::assertSame(465, $mail->Port);
        $this::assertSame('LyLink', $mail->FromName);
        $this::assertSame($env->SMTP_USERNAME, $mail->From);
        $this::assertSame($env->SMTP_PASSWORD, $mail->Password);
        $this::assertSame($env->SMTP_USERNAME, $mail->Username);
    }
}
