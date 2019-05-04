<?php

return [
    /**
     * --------------------------------------------------------------------------
     * Generated previews path
     * --------------------------------------------------------------------------
     *
     * This option determines where all the generated email previews will be
     * stored for the application. Typically, this is within the storage
     * directory. However, you may change the location as you desire.
     *
     */

    'path' => storage_path('email-previews'),

    /**
     * --------------------------------------------------------------------------
     * Time in seconds to keep old previews
     * --------------------------------------------------------------------------
     *
     * This option determines how long (in seconds) the mail transformer should
     * keep the generated preview files before deleting them. By default it
     * set to 60 seconds, but you can change this to whatever you desire.
     *
     */

    'maximum_lifetime' => 60,

    /**
     * --------------------------------------------------------------------------
     * Formats to preview in
     * --------------------------------------------------------------------------
     *
     * This option determines which formats to create previews for. Possible
     * options: eml, html.
     *
     */

    'formats' => ['eml', 'html'],

    /**
     * --------------------------------------------------------------------------
     * An option to enable showing a HTML link on mail sent
     * --------------------------------------------------------------------------
     *
     * This option determines if you would like to show a HTML link at the top
     * left corner of your screen every time and email is sent from your
     * system, the link will point the browser to the preview file.
     *
     */

    'show_link_to_preview' => true,

    /**
     * The timeout for the popup
     *
     * This is a time in miliseconds
     * if you use 0 or a negative number it will never be removed.
     */
    'popup_timeout' => 8000,

    /**
     * --------------------------------------------------------------------------
     * Set middleware for the mail preview route
     * --------------------------------------------------------------------------
     *
     * This option allows for setting middlewares for the route that shows a
     * preview to the mail that was just sent.
     */

    'middleware' => [

    ],
];
