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
    $uid = (int) (getenv('IMAP_UID') ?: 0);
    if ($uid <= 0) {
        exit("Set IMAP_UID to a real test-message UID before running this example.\n");
    }

    // These calls MODIFY mailbox state. Use a disposable test message.

    $reader->markAsRead($uid);
    echo "Marked UID {$uid} as read.\n";

    // Move to another existing folder:
    // $reader->moveEmailToFolder($uid, 'Processed');

    // Mark for deletion. The message is only permanently removed after expunge().
    // $reader->deleteEmail($uid);
    // $reader->expunge();
} finally {
    $reader->close();
}
