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
    // Search only for UIDs. No message bodies or attachment contents are loaded.
    $uids = $reader
        ->clearFilters()
        ->unseen()
        ->limit(100)
        ->page(1)
        ->searchUIDs();

    echo 'Paged UIDs: ' . implode(', ', $uids) . PHP_EOL;

    // Ignore pagination and retrieve every matching UID.
    $allMatchingUids = $reader
        ->clearFilters()
        ->flagged()
        ->searchUIDs(false);

    echo 'All flagged UIDs: ' . implode(', ', $allMatchingUids) . PHP_EOL;
} finally {
    $reader->close();
}
