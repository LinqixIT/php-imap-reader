<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use linqixit\phpImapReader\Reader;

$downloadDir = __DIR__ . '/downloads';
if (!is_dir($downloadDir)) {
    mkdir($downloadDir, 0775, true);
}

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

    foreach ($email->attachments() as $attachment) {
        // save() uses originalName() by default, strips path components and
        // automatically adds " (1)", " (2)", ... if the file already exists.
        $savedPath = $attachment->save($downloadDir);
        echo $attachment->originalName() . ' -> ' . $savedPath . PHP_EOL;
    }

    // You can also let Reader save attachments automatically while decoding:
    // $reader = new Reader($host, $user, $pass, $downloadDir, false);
} finally {
    $reader->close();
}
