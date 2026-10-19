<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use linqixit\phpImapReader\Reader;

$reader = new Reader(
    getenv('IMAP_HOST') ?: '{mail.example.com:993/imap/ssl}',
    getenv('IMAP_USER') ?: 'name@example.com',
    getenv('IMAP_PASS') ?: 'password',
    false,
    false
);

try {
    $email = $reader->clearFilters()->all()->limit(1)->get()[0] ?? null;
    if ($email === null) {
        exit("Mailbox is empty.\n");
    }

    // eml() returns the complete fetched message with its original headers.
    $rawMessage = $email->eml();
    echo 'EML bytes in memory: ' . strlen($rawMessage) . PHP_EOL;

    $target = __DIR__ . '/message-' . $email->id() . '.eml';
    $email->saveEml($target);
    echo 'Saved: ' . $target . PHP_EOL;
} finally {
    $reader->close();
}
