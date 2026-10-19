# PHP IMAP Reader – PHP 8.1–8.5

A PHP library for reading and managing IMAP mailboxes. This version uses a
single codebase for **PHP 8.1 through 8.5**.

## Project and credits

**Organization:** [LinqixIT](https://github.com/LinqixIT)  
**Development and maintenance:** [pascal-lpholding](https://github.com/pascal-lpholding)  
**Repository:** [LinqixIT/php-imap-reader](https://github.com/LinqixIT/php-imap-reader)

This independently maintained fork is based on
[php-imap-reader by Benjamin Hall (benhall14)](https://github.com/benhall14/php-imap-reader).
The changes for PHP 8.1–8.5 are documented in [CHANGELOG.md](CHANGELOG.md).
The original copyright notices and MIT license are retained.

The Composer package is `linqixit/php-imap-reader`. Its PHP namespace is
`linqixit\phpImapReader`. Compatibility aliases also support the original
`benhall14\phpImapReader` class names.

## Requirements

- PHP `^8.1` and Composer.
- **ext-imap**, compatible with your PHP version.
- **ext-iconv** for character encoding conversion.
- **ext-mbstring** is optional and used when available.

Starting with PHP 8.4, IMAP must be installed separately, for example through
your hosting provider or a compatible PECL package. The extension must be
available both to your application and to the PHP runtime used by Composer.

Official guidance: [Installing PHP IMAP](https://www.php.net/manual/en/imap.installation.php)
and [PECL IMAP](https://pecl.php.net/package/imap).

This version was tested with PHP 8.1.34 and PHP 8.5.0. The Composer requirement
allows later PHP 8.x versions; those need further verification and a compatible
IMAP extension.

## Installation

Extract the ZIP archive and run the following command in the directory
containing `composer.json`:

```sh
composer install
```

Then include `vendor/autoload.php` in your application.

To use this library as a Composer dependency, use the package name
`linqixit/php-imap-reader`. Installation through Packagist requires the package
to be registered there. Alternatively, configure the GitHub repository as a
Composer VCS repository, or the extracted directory as a path repository.
Your application's Composer installation manages its own lock file.

## Migrating from the original library

Change your Composer dependency from `benhall14/php-imap-reader` to
`linqixit/php-imap-reader`. Update your application's Composer lock file and
regenerate its autoloader as part of that switch.

**Existing imports can stay as they are.** After loading `vendor/autoload.php`,
the original class names automatically refer to their LinqixIT equivalents:

| Original class name | Canonical class name |
| --- | --- |
| `benhall14\phpImapReader\Reader` | `linqixit\phpImapReader\Reader` |
| `benhall14\phpImapReader\Email` | `linqixit\phpImapReader\Email` |
| `benhall14\phpImapReader\EmailAttachment` | `linqixit\phpImapReader\EmailAttachment` |

For example, this existing import remains supported:

```php
use benhall14\phpImapReader\Reader;
```

New code can use the LinqixIT namespace directly:

```php
use linqixit\phpImapReader\Reader;
use linqixit\phpImapReader\Email;
use linqixit\phpImapReader\EmailAttachment;
```

The aliases also support fully qualified class references and type declarations
using the original names. PHP reports the canonical `linqixit` class names in
`get_class()`, reflection and newly serialized objects, so code that compares
or stores those names as exact strings may need adjustment.

Install only one of the two packages. The compatibility aliases occupy the
original class names, so the fork declares a Composer conflict with
`benhall14/php-imap-reader`.

Public method names, parameter order and the interfaces for reading message
data are preserved. The bug fixes and changes to error handling are listed in
[CHANGELOG.md](CHANGELOG.md).

## Quick start

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use linqixit\phpImapReader\Reader;

$reader = new Reader(
    '{mail.example.com:993/imap/ssl}',
    'name@example.com',
    'password',
    false,      // Keep attachments in memory
    false,      // Do not automatically mark messages as read
    'UTF-8'
);

$reader->folder('INBOX')->unread()->limit(10)->get();

foreach ($reader->emails() as $email) {
    echo $email->fromEmail() . PHP_EOL;
    echo $email->subject() . PHP_EOL;
    echo $email->date('Y-m-d H:i:s') . PHP_EOL;
    echo $email->plain() . PHP_EOL;
}

$reader->close();
```

The server address and credentials in this example are placeholders.

## Constructor

```php
new Reader($hostname, $username, $password, $attachmentDir = false, $markAsRead = true, $encoding = 'UTF-8');
```

An attachment directory must already exist and be writable. Set the fourth
parameter to `false` to keep attachments in memory. The fifth parameter
controls whether messages are automatically marked as read; it defaults to
`true`.

## Selecting messages

Filters can be chained. `get()` fetches messages and returns an array of `Email`
objects. The same results are available through `emails()`; `email()` returns
the first message or `null`.

| Purpose | Methods |
| --- | --- |
| Folder | `folder($name)`, `mailbox($name)` |
| Selection | `all()`, `unread()` / `unseen()`, `read()` / `seen()`, `recent()`, `flagged()`, `unflagged()`, `unanswered()`, `deleted()`, `newMessages()`, `oldMessages()` |
| Addresses | `from($address)`, `sentTo($address)`, `searchCC($text)`, `searchBCC($text)` |
| Content | `searchSubject($text)`, `searchBody($text)`, `searchText($text)` |
| Keywords | `keyword($keyword)`, `unkeyword($keyword)` |
| Date | `beforeDate($date)`, `sinceDate($date)`, `onDate($date)` |
| Pagination and order | `limit($number)`, `page($number)`, `orderASC()`, `orderDESC()` |
| Single message by UID | `id($uid)->get()`, `getEmailByUID($uid)` |
| Single message by sequence number | `getEmailByMessageSequence($number)` |

Date filters accept values that `strtotime()` can parse, such as `2026-10-19`.
Invalid values cause an `InvalidArgumentException`.

## Message fields and attachments

| Data | Methods on an `Email` object |
| --- | --- |
| Identification | `id()` (UID), `msgno()`, `size()` |
| Content | `subject()`, `date($format)`, `plain()`, `html()` |
| Addresses | `fromName()`, `fromEmail()`, `to()`, `cc()`, `replyTo()`, `isTo($address)` |
| Headers | `headers()`, `getHeader($name)` |
| Status | `isRecent()`, `isUnseen()`, `isFlagged()`, `isAnswered()`, `isDeleted()`, `isDraft()` |
| Attachments | `hasAttachments()`, `attachments()`, `attachment($id)` |
| Raw message | `eml()`, `saveEml($filename)` |

```php
foreach ($email->attachments() as $attachment) {
    echo $attachment->name() . PHP_EOL;

    // For attachments kept in memory:
    $content = $attachment->content();

    // When an attachment directory is configured:
    $path = $attachment->filePath();
}
```

## Managing mailboxes and connections

- `listMailboxes()` / `listFolders()` return the list of folders.
- `doesMailboxExist($name)` / `doesFolderExist($name)` check whether a folder exists.
- `createMailbox($name)` / `createFolder($name)` create a folder.
- `count()` returns the number of messages in the connected mailbox.
- `markAsRead($uid)`, `deleteEmail($uid)` and `moveEmailToFolder($uid, $folder)`
  operate on a message's UID.
- `expunge()` permanently removes messages marked for deletion.
- `stream(true)` reconnects. `close()` closes the connection.
- `reset()` closes the connection and clears the results; existing filters and
  settings are retained.

As in the original library, `close()`, `reset()` and the destructor expunge
messages when closing the connection. A forced reconnect or folder change does
not trigger an additional expunge itself.

## Changes and license

See [CHANGELOG.md](CHANGELOG.md) for the changes in this version.

Licensed under the MIT license; see [LICENSE](LICENSE).

- Original author: [Benjamin Hall / benhall14](https://github.com/benhall14/php-imap-reader).
- Development and maintenance: [pascal-lpholding](https://github.com/pascal-lpholding) under [LinqixIT](https://github.com/LinqixIT).
- The original copyright notices are retained. Copyright for modifications is
  additionally attributed to pascal-lpholding.
