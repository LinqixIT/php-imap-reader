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

    // htmlWithInlineImages() replaces matching cid: URLs with Base64 data URLs.
    // The result is convenient for self-contained previews, but remember that
    // email HTML is untrusted content and should be sanitized before rendering.
    $html = $email->htmlWithInlineImages();

    $outputFile = __DIR__ . '/preview.html';
    file_put_contents($outputFile, $html);

    echo 'Wrote: ' . $outputFile . PHP_EOL;
    echo 'HTML size: ' . strlen($html) . ' bytes' . PHP_EOL;
} finally {
    $reader->close();
}
