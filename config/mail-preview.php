<?php

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
