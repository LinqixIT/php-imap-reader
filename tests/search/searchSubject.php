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
    // Literal values are supported, including quotes and backslashes.
    $subjectMatches = $reader
        ->clearFilters()
        ->searchSubject('Invoice "April"')
        ->get();

    echo 'Subject matches: ' . count($subjectMatches) . PHP_EOL;

    $bodyMatches = $reader
        ->clearFilters()
        ->searchBody('customer number 12345')
        ->get();

    echo 'Body matches: ' . count($bodyMatches) . PHP_EOL;

    $anyTextMatches = $reader
        ->clearFilters()
        ->searchText('urgent')
        ->get();

    echo 'Whole-message text matches: ' . count($anyTextMatches) . PHP_EOL;
} finally {
    $reader->close();
}
