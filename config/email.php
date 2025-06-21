<?php
declare(strict_types=1);

return [
    // Mailer to use: 'smtp' or 'wp_mail'
    'mailer'        => getenv('CPP_MAILER')        ?: ( defined('CPP_MAILER')        ? CPP_MAILER        : 'smtp' ),

    // SMTP configuration (only used if mailer === 'smtp')
    'smtp' => [
        'host'     => getenv('SMTP_HOST')     ?: ( defined('SMTP_HOST')     ? SMTP_HOST     : 'localhost' ),
        'port'     => getenv('SMTP_PORT')     ?: ( defined('SMTP_PORT')     ? SMTP_PORT     : 587 ),
        'user'     => getenv('SMTP_USER')     ?: ( defined('SMTP_USER')     ? SMTP_USER     : '' ),
        'pass'     => getenv('SMTP_PASS')     ?: ( defined('SMTP_PASS')     ? SMTP_PASS     : '' ),
        'secure'   => getenv('SMTP_SECURE')   ?: ( defined('SMTP_SECURE')   ? SMTP_SECURE   : 'tls' ),
        'auth'     => filter_var(
            getenv('SMTP_AUTH')      ?: ( defined('SMTP_AUTH')      ? SMTP_AUTH      : true ),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        ) ?? true,
    ],

    // Default From address (used for both mailers)
    'from_address' => getenv('CPP_MAIL_FROM_ADDRESS') ?: ( defined('CPP_MAIL_FROM_ADDRESS') ? CPP_MAIL_FROM_ADDRESS : get_option('admin_email') ),
    'from_name'    => getenv('CPP_MAIL_FROM_NAME')    ?: ( defined('CPP_MAIL_FROM_NAME')    ? CPP_MAIL_FROM_NAME    : get_bloginfo('name') ),

    // Optional reply-to address
    'reply_to'     => getenv('CPP_MAIL_REPLY_TO')     ?: ( defined('CPP_MAIL_REPLY_TO')     ? CPP_MAIL_REPLY_TO     : null ),

    // Whether to enable debug logging
    'debug'        => filter_var(
        getenv('CPP_MAIL_DEBUG') ?: ( defined('CPP_MAIL_DEBUG') ? CPP_MAIL_DEBUG : false ),
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE
    ) ?? false,
];
