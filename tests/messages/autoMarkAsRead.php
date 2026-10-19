<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use linqixit\phpImapReader\Reader;

// The fifth constructor argument controls automatic read marking.
$reader = new Reader(
    getenv('IMAP_HOST') ?: '{mail.example.com:993/imap/ssl}',
    getenv('IMAP_USER') ?: 'name@example.com',
    getenv('IMAP_PASS') ?: 'password',
    false,
    true // Automatically mark fully fetched messages as read.
);

try {
    $emails = $reader->clearFilters()->unseen()->limit(3)->get();

    foreach ($emails as $email) {
        echo 'Fetched and marked as read: UID ' . $email->id() . ' | ' . $email->subject() . PHP_EOL;
    }

    // For read-only inspection, construct Reader with false instead.
} finally {
    $reader->close();
}
