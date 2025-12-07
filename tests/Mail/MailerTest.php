<?php

use Lylink\Data\EnvStore;
use Lylink\Mail\Mailer;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    public function testPrepareMail(): void
    {
        $env = new EnvStore(stmp_host: 'stmp.test.test', stmp_username: 'test@test.test', stmp_password: 'test');

        $targetMail = 'test@test.test';
        $targetUsername = 'test';
        $subject = 'test';
        $body = 'test';

        $mail = Mailer::prepareMail($targetMail, $targetUsername, $subject, $body, $env);

        $this->assertInstanceOf(PHPMailer::class, $mail);
        $this->assertSame([[$targetMail, $targetUsername]], $mail->getToAddresses());
        $this->assertSame("LyLink - " . $subject, $mail->Subject);
        $this->assertSame($body, $mail->Body);
        $this->assertSame('stmp.test.test', $mail->Host);
    }
}
