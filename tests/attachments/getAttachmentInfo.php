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
    $uid = (int) (getenv('IMAP_UID') ?: 1);

    // Inspect the MIME structure without downloading attachment contents.
    $attachments = $reader->getAttachmentInfo($uid);

    foreach ($attachments as $info) {
        printf(
            "Part=%s | name=%s | mime=%s | disposition=%s | cid=%s | encoded bytes=%s\n",
            $info['part'] ?? '-',
            $info['name'] ?? '-',
            $info['mime'] ?? '-',
            $info['disposition'] ?? '-',
            $info['content_id'] ?? '-',
            isset($info['bytes']) ? (string) $info['bytes'] : '-'
        );
    }
} finally {
    $reader->close();
}
