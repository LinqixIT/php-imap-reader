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
    $uid = (int) (getenv('IMAP_UID') ?: 1);

    // Direct UID lookup:
    $email = $reader->getEmailByUID($uid);

    if ($email === null) {
        echo "UID {$uid} not found.\n";
    } else {
        echo $email->subject() . PHP_EOL;
    }

    // Equivalent fluent selection:
    $selected = $reader->clearFilters()->id($uid)->get();
    echo 'Fluent result count: ' . count($selected) . PHP_EOL;

    // A message sequence number is different from a UID. Use this only when
    // the sequence number is intentionally known:
    // $emailBySequence = $reader->getEmailByMessageSequence(1);
} finally {
    $reader->close();
}
