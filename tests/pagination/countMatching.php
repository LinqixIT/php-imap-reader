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
    $pageSize = 20;
    $page = 2;

    $total = $reader->clearFilters()->unseen()->countMatching();
    echo "Total unread: {$total}\n";

    $emails = $reader
        ->clearFilters()
        ->unseen()
        ->limit($pageSize)
        ->page($page)
        ->orderDESC()
        ->get();

    echo "Page {$page}, page size {$pageSize}: " . count($emails) . " messages\n";

    foreach ($emails as $email) {
        echo $email->id() . ' | ' . $email->subject() . PHP_EOL;
    }

    // Use orderASC() when the oldest matching messages should come first.
} finally {
    $reader->close();
}
