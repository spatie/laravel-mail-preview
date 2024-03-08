<?php

namespace Spatie\MailPreview\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MailPreview\Facades\SentMails;
use Spatie\MailPreview\SentMails\SentMail;
use Spatie\MailPreview\Tests\TestCase;
use Spatie\MailPreview\Tests\TestClasses\TestMailable;

class SentMailsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config()->set('mail.default', 'smtp');

        Mail::to('john@example.com')
            ->cc('paul@example.com')
            ->bcc('george@example.com')
            ->send(new TestMailable());
    }

    #[Test]
    public function it_can_get_all_mails()
    {
        $this->assertEquals(1, SentMails::count());
        SentMails::assertCount(1);
    }

    #[Test]
    public function it_can_assert_that_nothing_was_sent()
    {
        SentMails::reset()->assertNothingSent();
    }

    #[Test]
    public function it_can_make_an_assertion_on_the_latest_mail()
    {
        $this->assertTrue(SentMails::lastContains('the html'));
        $this->assertFalse(SentMails::lastContains('will not find'));

        SentMails::assertLastContains('the html');
    }

    #[Test]
    public function it_can_assert_to_persons_of_a_mail_sent()
    {
        $this->assertTrue(SentMails::last()->hasTo('john@example.com'));
        $this->assertFalse(SentMails::last()->hasTo('paul@example.com'));

        $this->assertEquals(['john@example.com'], SentMails::last()->to());

        SentMails::last()->assertTo('john@example.com');
    }

    #[Test]
    public function it_can_assert_cc_persons_of_a_mail_sent()
    {
        $this->assertTrue(SentMails::last()->hasCc('paul@example.com'));
        $this->assertFalse(SentMails::last()->hasCc('john@example.com'));

        $this->assertEquals(['paul@example.com'], SentMails::last()->cc());

        SentMails::last()->assertCc('paul@example.com');
    }

    #[Test]
    public function it_can_assert_bcc_persons_of_a_mail_sent()
    {
        $this->assertTrue(SentMails::last()->hasBcc('george@example.com'));
        $this->assertFalse(SentMails::last()->hasBcc('john@example.com'));

        $this->assertEquals(['george@example.com'], SentMails::last()->bcc());

        SentMails::last()->assertBcc('george@example.com');
    }

    #[Test]
    public function it_can_assert_the_from_persons_of_a_mail_sent()
    {
        $this->assertTrue(SentMails::last()->hasFrom('ringo@example.com'));
        $this->assertFalse(SentMails::last()->hasFrom('john@example.com'));

        $this->assertEquals(['ringo@example.com'], SentMails::last()->from());

        SentMails::last()->assertFrom('ringo@example.com');
    }

    #[Test]
    public function it_can_assert_the_subject_of_a_mail()
    {
        $this->assertEquals('this is the subject', SentMails::last()->subject());
        SentMails::last()->assertSubjectContains('the subject');
    }

    #[Test]
    public function it_can_get_the_times_a_mail_was_sent()
    {
        $actualCount = SentMails::timesSent(function (SentMail $mail) {
            return $mail->hasTo('john@example.com');
        });

        $this->assertEquals(1, $actualCount);
    }

    #[Test]
    public function it_can_assert_the_times_a_mail_was_sent()
    {
        SentMails::assertSent(function (SentMail $mail) {
            return $mail->hasTo('john@example.com');
        });

        SentMails::assertTimesSent(1, function (SentMail $mail) {
            return $mail->hasTo('john@example.com');
        });
    }

    #[Test]
    public function it_can_assert_the_body_content_for_a_sent_mail()
    {
        SentMails::assertSent(function (SentMail $mail) {
            return $mail->bodyContains('this is the html');
        });
    }
}
