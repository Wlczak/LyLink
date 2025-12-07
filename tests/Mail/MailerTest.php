<?php

use Lylink\Data\EnvStore;
use Lylink\Mail\Mailer;
use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    public function testPrepareMail(): void
    {
        $env = new EnvStore(stmp_host: 'test@test.test', stmp_username: 'test', stmp_password: 'test');

        $mail = Mailer::prepareMail('test@test.test', 'test', 'test', 'test', $env);
    }
}
