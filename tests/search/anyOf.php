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
    // Match messages that are EITHER unseen OR flagged.
    $emails = $reader
        ->clearFilters()
        ->anyOf(['UNSEEN', 'FLAGGED'])
        ->limit(50)
        ->get();

    foreach ($emails as $email) {
        printf(
            "UID %d | unseen=%s | flagged=%s | %s\n",
            $email->id(),
            $email->isUnseen() ? 'yes' : 'no',
            $email->isFlagged() ? 'yes' : 'no',
            $email->subject()
        );
    }

    // Chained filters still apply to every OR branch:
    $fromSupport = $reader
        ->clearFilters()
        ->from('support@example.com')
        ->anyOf(['UNSEEN', 'FLAGGED'])
        ->get();

    echo 'Support messages matched: ' . count($fromSupport) . PHP_EOL;
} finally {
    $reader->close();
}
