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
    // Filters are chainable. This example searches unread messages from one
    // sender that arrived since 2026-01-01 and were sent to a target address.
    $emails = $reader
        ->folder('INBOX')
        ->clearFilters()
        ->unread()                 // Alias for unseen().
        ->from('billing@example.com')
        ->sentTo('accounts@example.com')
        ->sinceDate('2026-01-01')
        ->limit(25)
        ->orderDESC()
        ->get();

    foreach ($emails as $email) {
        printf("%s | %s | %s\n", $email->id(), $email->date('Y-m-d'), $email->subject());
    }

    // Other status filters include:
    // all(), seen()/read(), recent(), flagged(), unflagged(), unanswered(),
    // deleted(), newMessages(), oldMessages(), keyword(), unkeyword().
} finally {
    $reader->close();
}
