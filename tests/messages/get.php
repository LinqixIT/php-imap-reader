<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use linqixit\phpImapReader\Reader;

/**
 * Basic mailbox read.
 *
 * Required environment variables:
 *   IMAP_HOST={mail.example.com:993/imap/ssl}
 *   IMAP_USER=name@example.com
 *   IMAP_PASS=secret
 */

$reader = new Reader(
    getenv('IMAP_HOST') ?: '{mail.example.com:993/imap/ssl}',
    getenv('IMAP_USER') ?: 'name@example.com',
    getenv('IMAP_PASS') ?: 'password',
    false, // Keep attachments in memory.
    false, // Do not mark messages as read automatically.
    'UTF-8'
);

try {
    $emails = $reader
        ->folder('INBOX')
        ->clearFilters()
        ->all()
        ->limit(10)
        ->page(1)
        ->orderDESC()
        ->get();

    echo 'Loaded: ' . count($emails) . PHP_EOL;

    foreach ($emails as $email) {
        echo str_repeat('-', 70) . PHP_EOL;
        echo 'UID:     ' . $email->id() . PHP_EOL;
        echo 'From:    ' . $email->fromName() . ' <' . $email->fromEmail() . '>' . PHP_EOL;
        echo 'Subject: ' . $email->subject() . PHP_EOL;
        echo 'Date:    ' . $email->date('Y-m-d H:i:s') . PHP_EOL;
        echo 'Size:    ' . $email->size() . ' bytes' . PHP_EOL;
        echo PHP_EOL . $email->plain() . PHP_EOL;
    }
} finally {
    $reader->close();
}
