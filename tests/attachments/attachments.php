<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use linqixit\phpImapReader\Reader;

$reader = new Reader(
    getenv('IMAP_HOST') ?: '{mail.example.com:993/imap/ssl}',
    getenv('IMAP_USER') ?: 'name@example.com',
    getenv('IMAP_PASS') ?: 'password',
    false, // Keep attachment contents in memory.
    false
);

try {
    $email = $reader->clearFilters()->all()->limit(1)->get()[0] ?? null;
    if ($email === null) {
        exit("Mailbox is empty.\n");
    }

    echo 'Has attachments: ' . ($email->hasAttachments() ? 'yes' : 'no') . PHP_EOL;

    foreach ($email->attachments() as $attachment) {
        echo str_repeat('-', 60) . PHP_EOL;
        echo 'ID: ' . $attachment->id() . PHP_EOL;
        echo 'Name: ' . $attachment->name() . PHP_EOL;
        echo 'Original name: ' . $attachment->originalName() . PHP_EOL;
        echo 'MIME: ' . $attachment->mime() . PHP_EOL;
        echo 'Extension: ' . $attachment->extension() . PHP_EOL;
        echo 'Type: ' . $attachment->type() . PHP_EOL;
        echo 'Inline: ' . ($attachment->isInline() ? 'yes' : 'no') . PHP_EOL;
        echo 'Size: ' . $attachment->size() . ' bytes' . PHP_EOL;

        $content = $attachment->content();
        echo 'Content loaded: ' . (is_string($content) ? strlen($content) . ' bytes' : 'no') . PHP_EOL;
    }
} finally {
    $reader->close();
}
