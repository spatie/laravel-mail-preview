<?php

namespace Themsaid\MailPreview;

use Illuminate\Support\Facades\Session;
use Swift_Mime_SimpleMessage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Mail\Transport\Transport;

class PreviewTransport extends Transport
{
    /**
     * The Filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Get the preview path.
     *
     * @var string
     */
    protected $previewPath;

    /**
     * Time in seconds to keep old previews.
     *
     * @var int
     */
    private $lifeTime;

    /**
     * Which formats to create previews for.
     *
     * @var string[]
     */
    protected $formats;

    /**
     * Create a new preview transport instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem $files
     * @param  string $previewPath
     * @param  int $lifeTime
     *
     * @return void
     */
    public function __construct(Filesystem $files, $previewPath, $lifeTime = 60, array $formats = [])
    {
        $this->files = $files;
        $this->previewPath = $previewPath;
        $this->lifeTime = $lifeTime;
        $this->formats = $formats;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $this->createEmailPreviewDirectory();

        $this->cleanOldPreviews();

        if ($this->shouldCreateFormat('html')) {
            Session::put('mail_preview_path', basename($previewPath = $this->getPreviewFilePath($message)));

            $this->files->put(
                $previewPath.'.html',
                $this->getHTMLPreviewContent($message)
            );
        }

        if ($this->shouldCreateFormat('eml')) {
            $this->files->put(
                $previewPath.'.eml',
                $this->getEMLPreviewContent($message)
            );
        }
    }

    /**
     * Whether to create a preview in this format.
     *
     * @param  string $format
     *
     * @return bool
     */
    protected function shouldCreateFormat($format)
    {
        return !count($this->formats) || in_array($format, $this->formats);
    }

    /**
     * Get the path to the email preview file.
     *
     * @param  \Swift_Mime_SimpleMessage $message
     *
     * @return string
     */
    protected function getPreviewFilePath(Swift_Mime_SimpleMessage $message)
    {
        $to = str_replace(['@', '.'], ['_at_', '_'], array_keys($message->getTo())[0]);

        $subject = $message->getSubject();

        return $this->previewPath.'/'.str_slug($message->getDate()->getTimestamp().'_'.$to.'_'.$subject, '_');
    }

    /**
     * Get the HTML content for the preview file.
     *
     * @param  \Swift_Mime_SimpleMessage $message
     *
     * @return string
     */
    protected function getHTMLPreviewContent(Swift_Mime_SimpleMessage $message)
    {
        $messageInfo = $this->getMessageInfo($message);

        return $messageInfo.$message->getBody();
    }

    /**
     * Get the EML content for the preview file.
     *
     * @param  \Swift_Mime_SimpleMessage $message
     *
     * @return string
     */
    protected function getEMLPreviewContent(Swift_Mime_SimpleMessage $message)
    {
        return $message->toString();
    }

    /**
     * Generate a human readable HTML comment with message info.
     *
     * @param \Swift_Mime_SimpleMessage $message
     *
     * @return string
     */
    protected function getMessageInfo(Swift_Mime_SimpleMessage $message)
    {
        return sprintf(
            "<!--\nFrom:%s, \nto:%s, \nreply-to:%s, \ncc:%s, \nbcc:%s, \nsubject:%s\n-->\n",
            json_encode($message->getFrom()),
            json_encode($message->getTo()),
            json_encode($message->getReplyTo()),
            json_encode($message->getCc()),
            json_encode($message->getBcc()),
            $message->getSubject()
        );
    }

    /**
     * Create the preview directory if necessary.
     *
     * @return void
     */
    protected function createEmailPreviewDirectory()
    {
        if (! $this->files->exists($this->previewPath)) {
            $this->files->makeDirectory($this->previewPath);

            $this->files->put($this->previewPath.'/.gitignore', "*\n!.gitignore");
        }
    }

    /**
     * Delete previews older than the given life time configuration.
     *
     * @return void
     */
    private function cleanOldPreviews()
    {
        $oldPreviews = array_filter($this->files->files($this->previewPath), function ($file) {
            return time() - $this->files->lastModified($file) > $this->lifeTime;
        });

        if ($oldPreviews) {
            $this->files->delete($oldPreviews);
        }
    }
}
