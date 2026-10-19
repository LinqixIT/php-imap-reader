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
    // stream() opens the connection when needed and reuses it while valid.
    $first = $reader->stream();
    $second = $reader->stream();
    echo 'Connection reused: ' . ($first === $second ? 'yes' : 'no') . PHP_EOL;

    // A forced reconnect closes the current connection without expunging and
    // creates a new one.
    $third = $reader->stream(true);
    echo 'Forced reconnect completed: ' . ($third !== null ? 'yes' : 'no') . PHP_EOL;

    // reset() closes the connection and clears cached email collections.
    $reader->reset();
    echo 'Reader reset. Next operation will reconnect automatically.' . PHP_EOL;
} finally {
    $reader->close();
}
