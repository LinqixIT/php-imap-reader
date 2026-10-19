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
    // Search a standard or application-specific header. Header names are
    // matched case-insensitively. Several calls are combined with AND.
    $emails = $reader
        ->clearFilters()
        ->from('support@example.com')
        ->searchHeader('References', '<original@example.com>')
        ->searchHeader('X-Ticket-ID', 'ABC-123')
        ->get();

    foreach ($emails as $email) {
        echo $email->subject() . PHP_EOL;
        echo 'Message-ID: ' . ($email->messageId() ?? '(none)') . PHP_EOL;
    }

    // An empty value checks only whether the header exists:
    $withListId = $reader
        ->clearFilters()
        ->searchHeader('List-Id', '')
        ->get();

    echo 'Messages containing List-Id: ' . count($withListId) . PHP_EOL;
} finally {
    $reader->close();
}
