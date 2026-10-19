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
    // iterate() yields one complete Email at a time instead of keeping every
    // Email object in Reader::emails(). This is preferable for larger mailboxes.
    foreach ($reader->clearFilters()->unseen()->limit(0)->iterate() as $uid => $email) {
        echo "Processing UID {$uid}: {$email->subject()}\n";

        // Do your work here, e.g. parse the plain body, inspect headers,
        // save attachments, write data to a database, etc.
        $plainText = $email->plain();
        echo 'Body length: ' . strlen($plainText) . PHP_EOL;
    }
} finally {
    $reader->close();
}
