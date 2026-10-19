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

    echo 'Message-ID: ' . ($email->messageId() ?? '(none)') . PHP_EOL;
    echo 'In-Reply-To: ' . ($email->inReplyTo() ?? '(none)') . PHP_EOL;
    echo 'References:' . PHP_EOL;
    foreach ($email->references() as $reference) {
        echo '  - ' . $reference . PHP_EOL;
    }

    echo 'X-Ticket-ID: ' . ($email->getHeader('X-Ticket-ID') ?? '(none)') . PHP_EOL;

    // getHeaderValues() is useful for repeated headers such as Received.
    foreach ($email->getHeaderValues('Received') as $received) {
        echo 'Received: ' . $received . PHP_EOL;
    }

    // getHeaders() is an alias for headers() kept for compatibility with
    // examples that used that spelling.
    echo "\nRaw headers:\n" . $email->getHeaders() . PHP_EOL;
} finally {
    $reader->close();
}
