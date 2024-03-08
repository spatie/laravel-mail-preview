<?php

namespace Spatie\MailPreview\Tests;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\MailPreview\MailPreviewServiceProvider;
use Symfony\Component\Finder\SplFileInfo;

abstract class TestCase extends Orchestra
{
    public function setUp(): void
    {
        ray()->newScreen("Test {$this->name()}");

        parent::setUp();

        config()->set('mail-preview.enabled', true);

        config()->set('mail.mailers.smtp.transport', 'preview');

        $this->clearSentMails();
    }

    protected function getPackageProviders($app)
    {
        return [
            MailPreviewServiceProvider::class,
        ];
    }

    protected function assertLatestMailContains(string $substring): void
    {
        $this->assertDirectoryExists(config('mail-preview.storage_path'));

        $storedMails = File::allFiles(config('mail-preview.storage_path'));

        $latestMailPath = collect($storedMails)
            ->sortByDesc(fn (SplFileInfo $file) => $file->getMTime())
            ->first()
            ->getPathName();

        $latestMailContent = file_get_contents($latestMailPath);

        $latestMailContent = str_replace("=\r\n", '', $latestMailContent);

        $this->assertStringContainsString($substring, $latestMailContent);
    }

    protected function clearSentMails(): void
    {
        File::cleanDirectory(config('mail-preview.path'));
    }
}
