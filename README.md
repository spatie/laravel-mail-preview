
# A mail driver to quickly preview mail

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-mail-preview.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-mail-preview)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/spatie/laravel-mail-preview/run-tests?label=tests)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-mail-preview.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-mail-preview)

This package can display a small overlay whenever a mail is sent. The overlay contains a link to the mail that was just sent.

<img alt="screenshot" src="https://github.com/spatie/laravel-mail-preview/blob/main/docs/images/overlay.png?raw=true" width="400" />

This can be handy when testing out emails in a local environment. 

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-mail-preview.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-mail-preview)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-mail-preview
```

### Configuring the mail transport

This package contains a mail transport called `preview`. We recommend to only use this transport in non-production environments. To use the `preview` transport, change the `mailers.smtp.transport` to `preview` in your `config/mail.php` file:

```php
// in config/mail.php

'mailers' => [
    'smtp' => [
        'transport' => 'preview',
        // ...
    ],
    // ...
],
```

### Registering the preview middleware route

The package can display a link to sent mails whenever they are sent. To use this feature, you must add the `Spatie\MailPreview\Http\Middleware\AddMailPreviewOverlayToResponse` middleware to the `web` middleware group in your kernel.

```php
// in app/Http/Kernel.php

protected $middlewareGroups = [
    'web' => [
        // other middleware
        
        \Spatie\MailPreview\Http\Middleware\AddMailPreviewOverlayToResponse::class,
    ],
    
    // ...
];
```

You must also add the `mailPreview` to your routes file. Typically, the routes file will be located at `routes/web.php`.

```php
// in routes/web.php

Route::mailPreview();
```

This will register a route to display sent mails at `/spatie-mail-preview`. To customize the URL, pass the URL you want to the macro.

```php
Route::mailPreview('custom-url-where-sent-mails-will-be-shown');
```

### Publishing the config file

Optionally, you can publish the config file with:

```bash
php artisan vendor:publish --provider="Spatie\MailPreview\MailPreviewServiceProvider" --tag="mail-preview-config"
```

This is the content of the config file that will be published at `config/mail-preview.php`:

```php
return [
    /*
     * By default, the overlay will only be shown and mail will only be stored
     * when the application is in debug mode.
     */
    'enabled' => env('APP_DEBUG', false),

    /*
     * All mails will be stored in the given directory.
     */
    'storage_path' => storage_path('email-previews'),

    /*
     * This option determines how long generated preview files will be kept.
     */
    'maximum_lifetime_in_seconds' => 60,

    /*
     * When enabled, a link to mail will be added to the response
     * every time a mail is sent.
     */
    'show_link_to_preview' => true,

    /*
     * Determines how long the preview pop up should remain visible.
     *
     * Set this to `false` if the popup should stay visible.
     */
    'popup_timeout_in_seconds' => 8,
    
    /**
     * Determines the date format used for the file names.
     *
     * The default 'u' stands for microseconds.
     */
    'filename_date_format' => 'u',
];

```

### Publishing the views

Optionally, you can publish the views that render the preview overlay and the mail itself.

```bash
php artisan vendor:publish --provider="Spatie\MailPreview\MailPreviewServiceProvider" --tag="mail-preview-views"
```

You can modify the views that will be published at `resources/views/vendor/mail-preview` to your liking.

## Usage

Everytime an email is sent, an `.html` and `.eml` file will be saved in the directory specified in the `storage_path` of the `mail-preview` config file.  The name includes the first recipient and the subject:

```
1457904864_john_at_example_com_invoice_000234.html
1457904864_john_at_example_com_invoice_000234.eml
```

You can open the `.html` file in a web browser.  The `.eml` file in your default email client to have a realistic look
of the final output.

### Preview in a web browser

When you open the `.html` file in a web browser you'll be able to see how your email will look.

At the beginning of the generated file you'll find an HTML comment with all the message info:

```html
<!--
From:{"info@acme.com":"Acme HQ"},
to:{"jack@gmail.com":"Jack Black"},
reply-to:{"info@acme.com"},
cc:[{"finance@acme.com":"Acme Finance"}, {"management@acme.com":"Acme Management"}],
bcc:null,
subject:Invoice #000234
-->
```

### Events

Whenever a mail is stored on disk, the `Spatie\MailPreview\Events\MailStoredEvent` will be fired. It has three public properties:

- `message`: an instance of `Swift_Mime_SimpleMessage`
- `pathToHtmlVersion`: the path to the html version of the sent mail
- `pathToEmlVersion`: the path to the email version of the sent mail

### Making assertions against sent mails

Currently, using Laravel's `Mail::fake` you cannot make any assertions against the content of a mail, as the using the fake will not render the mail. 

The `SentMails` facade provided this package does allow you to make asserts against the content.

This allows you to make assertions on the content of a mail, without having the mailable in scope.

```php
// in a test

Artisan::call(CommandThatSendsMail::class);

Spatie\MailPreview\Facades\SentMails::assertLastContains('something in your mail');
```

Let's explain other available assertions method using this mailable as example.

```php
namespace App\Mail;

use Illuminate\Mail\Mailable;

class MyNewSongMailable extends Mailable
{
    public function build()
    {
        $this
            ->to('ringo@example.com')
            ->cc('paul@examle.com')
            ->bcc('john@examle.com')
            ->subject('Here comes the sun')
            ->html("It's been a long cold lonely winter");
    }
}
```

In your code you can send that mailable with:

```php
Mail::send(new MyNewSongMailable());
```

In your tests you can assert that the mail was sent using the `assertSent` function. You should pass a callable to `assertSent` which will get an instance of `SentMail` to it. Each sent mail will be passed to the callable. If the callable returns `true` the assertions passes.

```php
use Spatie\MailPreview\Facades\SentMails;
use \Spatie\MailPreview\SentMails\SentMail;

SentMails::assertSent(fn (SentMail $mail) => $mail->bodyContains('winter')); // will pass
SentMails::assertSent(fn (SentMail $mail) => $mail->bodyContains('spring')); // will not pass
```

You can use as many assertion methods on the `SentMail` as you like.

```php
SentMails::assertSent(function (SentMail $mail)  {
    return
        $mail->subjectContains('sun') &&
        $mail->hasTo('ringo@example.com')
        $mail->bodyContains('winter');
```

The `Spatie\MailPreview\Facades\SentMails` has the following assertions methods:

- `assertCount(int $expectedCount)`: assert how many mails were sent
- `assertLastContains(string $expectedSubstring)`: assert that the body of the last sent mail contains a given substring
- `assertSent($findMailCallable, int $expectedCount = 1)`: explained above
- `assertTimesSent(int $expectedCount, Closure $findMail)`
- `assertNotSent(Closure $findMail)`

Additionally, the `Spatie\MailPreview\Facades\SentMails` has these methods:

- `all`: returns an array of sent mails. Each item will be an instance of  `sentMail`
- `count()`: returns the amount of mails sent
- `last`: returns an instance of `SentMail` for the last sent mail. If no mail was sent `null` will be returned.
- `lastContains`: returns `true` if the body of the last sent mail contains the given substring
- `timesSent($findMailCallable)`: returns the amount of mails the were sent and that passed the given callable

The `sentMail` class provides these assertions:

- `assertSubjectContains($expectedSubstring)`
- `assertFrom($expectedAddress)`
- `assertTo$expectedAddress)`
- `assertCc($expectedAddress)`
- `assertBcc($expectedAddress)`
- `assertContains($substring)`: will pass if the body of the mail contains the substring

Additionally, `sentMail` contains these methods:

- `subject()`: return the body of a mail
- `to()`: returns all to recipients as an array
- `cc()`: returns all cc recipients as an array
- `bcc()`: returns all bcc recipients as an array
- `body()`: returns the body of a mail
- `subjectContains)`: returns a boolean
- `hasFrom($expectedAddress)`: return a boolean
- `hasTo($expectedAddress)`: return a boolean
- `hasCc($expectedAddress)`: return a boolean
- `hasBcc($expectedAddress)`: return a boolean

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## UPGRADING

Please see [UPGRADING](UPGRADING.md) for what to do to switch over from `themsaid/laravel-mail-preview`, and how to upgrade to newer major versions.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [Mohamed Said](https://github.com/themsaid)
- [All Contributors](../../contributors)

The initial version of this package was created by Mohamed Said, who graciously entrusted this package to us at Spatie.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
