<?php

namespace Spatie\MailPreview\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MailPreview\Facades\SentMails;
use Spatie\MailPreview\Tests\TestCase;
use Spatie\MailPreview\Tests\TestClasses\TestMailable;

class MailPreviewTest extends TestCase
{
    #[Test]
    public function it_will_write_sent_mails_to_disk()
    {
        config()->set('mail.default', 'smtp');

        Mail::to('john@example.com')->send(new TestMailable());

        $sentMail = SentMails::last();

        $this->assertFileExists($sentMail->htmlPath);
        $this->assertFileExists($sentMail->emlPath);
    }
}
