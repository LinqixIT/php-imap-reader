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
    echo "Available mailboxes:\n";
    foreach ($reader->listMailboxes() as $mailbox) {
        echo '  ' . $mailbox . PHP_EOL;
    }

    $name = 'Archive';
    echo "Archive exists: " . ($reader->doesMailboxExist($name) ? 'yes' : 'no') . PHP_EOL;

    // The following aliases are intentionally supported as well:
    echo "Archive exists (folder alias): " . ($reader->doesFolderExist($name) ? 'yes' : 'no') . PHP_EOL;
    echo "Archive exists (legacy spelling): " . ($reader->doesMailboxExists($name) ? 'yes' : 'no') . PHP_EOL;

    // Folder creation changes the server. Enable intentionally when needed:
    // if (!$reader->doesMailboxExist('Processed')) {
    //     $reader->createMailbox('Processed');
    // }

    // folder()/mailbox() selects a mailbox for subsequent reads.
    $reader->folder('INBOX');
    echo 'INBOX message count: ' . $reader->count() . PHP_EOL;
} finally {
    $reader->close();
}
