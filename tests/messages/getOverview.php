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
    // getOverview() is useful for inbox listings because it does not download
    // full message bodies or attachment contents.
    $overview = $reader
        ->clearFilters()
        ->all()
        ->limit(30)
        ->orderDESC()
        ->getOverview();

    foreach ($overview as $message) {
        printf(
            "UID=%s | seen=%s | from=%s | subject=%s | size=%s\n",
            $message->uid ?? '-',
            !empty($message->seen) ? 'yes' : 'no',
            $message->from ?? '',
            $message->subject ?? '',
            $message->size ?? 0
        );
    }
} finally {
    $reader->close();
}
