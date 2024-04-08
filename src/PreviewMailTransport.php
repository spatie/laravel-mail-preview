<?php

namespace Spatie\MailPreview;

use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Spatie\MailPreview\Events\MailStoredEvent;
use Spatie\MailPreview\SentMails\SentMail;
use Spatie\MailPreview\SentMails\SentMails;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class PreviewMailTransport extends AbstractTransport
{
    protected Filesystem $filesystem;

    protected int $maximumLifeTimeInSeconds;

    public array $sentMails = [];

    public function __construct(Filesystem $files, int $maximumLifeTimeInSeconds = 60)
    {
        parent::__construct();

        $this->filesystem = $files;

        $this->maximumLifeTimeInSeconds = $maximumLifeTimeInSeconds;
    }

    public function doSend(SentMessage $message, &$failedRecipients = null): void
    {
        $this->sentMails[] = $message;

        if (! config('mail-preview.enabled')) {
            return;
        }

        $this
            ->ensureEmailPreviewDirectoryExists()
            ->cleanOldPreviews();

        $previewPath = $this->getPreviewFilePath($message);

        session()->put('mail_preview_file_name', basename($previewPath));

        $htmlFullPath = "{$previewPath}.html";
        $emlFullPath = "{$previewPath}.eml";

        $this->filesystem->put($htmlFullPath, $this->getHtmlPreviewContent($message));
        $this->filesystem->put($emlFullPath, $this->getEmlPreviewContent($message));

        $sentMail = new SentMail($message, $htmlFullPath, $emlFullPath);

        app(SentMails::class)->add($sentMail);

        event(new MailStoredEvent($message, $htmlFullPath, $emlFullPath));
    }

    protected function getHtmlPreviewContent(SentMessage $message): string
    {
        $messageInfo = $this->getMessageInfo($message);

        return $messageInfo . MessageConverter::toEmail($message->getOriginalMessage())->getHtmlBody();
    }

    protected function getEmlPreviewContent(SentMessage $message): string
    {
        return $message->toString();
    }

    protected function getPreviewFilePath(SentMessage $message): string
    {
        $to = '';

        /** @var Address $toAddress */
        if ($toAddress = ($message->getOriginalMessage()->getTo()[0] ?? null)) {
            $to = str_replace(['@', '.'], ['_at_', '_'], $toAddress->getAddress()) . '_';
        }

        $subject = $message->getOriginalMessage()->getSubject();
        $date = $message->getOriginalMessage()->getDate() ?? now();

        return $this->storagePath() . '/' . Str::slug($date->format($this->filenameDateFormat()) . '_' . $to . $subject, '_');
    }

    protected function getMessageInfo(SentMessage $message): string
    {
        return sprintf(
            "<!--\nFrom:%s, \nto:%s, \nreply-to:%s, \ncc:%s, \nbcc:%s, \nsubject:%s\n-->\n",
            json_encode($this->extractAddresses($message->getOriginalMessage()->getFrom())),
            json_encode($this->extractAddresses($message->getOriginalMessage()->getTo())),
            json_encode($this->extractAddresses($message->getOriginalMessage()->getReplyTo())),
            json_encode($this->extractAddresses($message->getOriginalMessage()->getCc())),
            json_encode($this->extractAddresses($message->getOriginalMessage()->getBcc())),
            $message->getOriginalMessage()->getSubject(),
        );
    }

    protected function extractAddresses(array $addresses): array
    {
        return array_map(function (Address $address) {
            return $address->getAddress();
        }, $addresses);
    }

    protected function ensureEmailPreviewDirectoryExists(): self
    {
        if ($this->filesystem->exists($this->storagePath())) {
            return $this;
        }

        $this->filesystem->makeDirectory($this->storagePath());

        $this->filesystem->put("{$this->storagePath()}/.gitignore", '*' . PHP_EOL . '!.gitignore');

        return $this;
    }

    protected function cleanOldPreviews(): self
    {
        collect($this->filesystem->files($this->storagePath()))
            ->filter(function (SplFileInfo $path) {
                $fileAgeInSeconds = Carbon::createFromTimestamp($path->getCTime())->diffInSeconds();

                return $fileAgeInSeconds >= $this->maximumLifeTimeInSeconds;
            })
            ->each(fn (SplFileInfo $file) => $this->filesystem->delete($file->getPathname()));

        return $this;
    }

    protected function storagePath(): string
    {
        return config('mail-preview.storage_path');
    }

    protected function filenameDateFormat(): string
    {
        return config('mail-preview.filename_date_format') ?? 'u';
    }

    public function __toString(): string
    {
        return '';
    }
}
