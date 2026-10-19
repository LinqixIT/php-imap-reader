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

    echo 'UID: ' . $email->id() . PHP_EOL;
    echo 'Sequence: ' . $email->msgno() . PHP_EOL;
    echo 'Subject: ' . $email->subject() . PHP_EOL;
    echo 'From: ' . $email->fromName() . ' <' . $email->fromEmail() . '>' . PHP_EOL;
    echo 'Date: ' . $email->date(DATE_ATOM) . PHP_EOL;
    echo 'Size: ' . $email->size() . PHP_EOL;
    echo 'Recent: ' . ($email->isRecent() ? 'yes' : 'no') . PHP_EOL;
    echo 'Unseen: ' . ($email->isUnseen() ? 'yes' : 'no') . PHP_EOL;
    echo 'Flagged: ' . ($email->isFlagged() ? 'yes' : 'no') . PHP_EOL;
    echo 'Answered: ' . ($email->isAnswered() ? 'yes' : 'no') . PHP_EOL;
    echo 'Deleted: ' . ($email->isDeleted() ? 'yes' : 'no') . PHP_EOL;
    echo 'Draft: ' . ($email->isDraft() ? 'yes' : 'no') . PHP_EOL;

    echo "\nPlain body:\n" . $email->plain() . PHP_EOL;
    echo "\nHTML body length: " . strlen($email->html()) . PHP_EOL;
} finally {
    $reader->close();
}
