<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use linqixit\phpImapReader\Reader;

$reader = new Reader(
    getenv('IMAP_HOST') ?: '{mail.example.com:993/imap/ssl}',
    getenv('IMAP_USER') ?: 'name@example.com',
    getenv('IMAP_PASS') ?: 'password',
    false,
    false,
    'UTF-8'
);

try {
    // Default behavior: the search charset follows $reader->encoding.
    $reader->encoding = 'UTF-8';
    $matches = $reader->clearFilters()->searchSubject('Rechnung')->get();
    echo 'UTF-8 search matches: ' . count($matches) . PHP_EOL;

    // Some IMAP servers reject CHARSET UTF-8. For ASCII search terms you can
    // explicitly use US-ASCII or omit the search charset entirely.
    $reader->searchCharset('US-ASCII');
    $asciiMatches = $reader->clearFilters()->searchSubject('Invoice')->get();
    echo 'US-ASCII search matches: ' . count($asciiMatches) . PHP_EOL;

    $reader->searchCharset(null);
    $withoutCharset = $reader->clearFilters()->searchSubject('Invoice')->get();
    echo 'Searches without CHARSET: ' . count($withoutCharset) . PHP_EOL;
} finally {
    $reader->close();
}
