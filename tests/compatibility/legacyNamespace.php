<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

// Existing applications can keep the original namespace. Composer loads
// compatibility aliases that point these class names to the LinqixIT classes.
use benhall14\phpImapReader\Reader;

$reader = new Reader(
    getenv('IMAP_HOST') ?: '{mail.example.com:993/imap/ssl}',
    getenv('IMAP_USER') ?: 'name@example.com',
    getenv('IMAP_PASS') ?: 'password',
    false,
    false
);

try {
    $emails = $reader->clearFilters()->unseen()->limit(5)->get();

    foreach ($emails as $email) {
        echo $email->subject() . PHP_EOL;
    }

    echo 'Runtime class: ' . get_class($reader) . PHP_EOL;
    // Reflection/get_class() can expose the canonical linqixit class name even
    // though the legacy namespace remains valid for imports and type checks.
} finally {
    $reader->close();
}
