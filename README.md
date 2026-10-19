# PHP IMAP Reader – PHP 8.1–8.5

A PHP library for reading and managing IMAP mailboxes. This version uses a
single codebase for **PHP 8.1 through 8.5**.

## Project and credits

**Organization:** [LinqixIT](https://github.com/LinqixIT)  
**Development and maintenance:** [pascal-lpholding](https://github.com/pascal-lpholding)  
**Repository:** [LinqixIT/php-imap-reader](https://github.com/LinqixIT/php-imap-reader)

This independently maintained fork is based on
[php-imap-reader by Benjamin Hall (benhall14)](https://github.com/benhall14/php-imap-reader).
Changes and new features are documented in [CHANGELOG.md](CHANGELOG.md).
The original copyright notices and MIT license are retained.

The Composer package is `linqixit/php-imap-reader`. Its PHP namespace is
`linqixit\phpImapReader`. Compatibility aliases also support the original
`benhall14\phpImapReader` class names.

## Features

- Fluent mailbox searches, including OR groups and custom header filters.
- UID lists, message overviews and attachment metadata without downloading bodies.
- Message-by-message processing with a generator.
- Message IDs, reply references and access to repeated headers.
- International attachment filenames and explicit saving with collision handling.

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

### OR searches and custom headers

Use `anyOf()` when either condition should match:

```php
$emails = $reader
    ->clearFilters()
    ->anyOf(['UNSEEN', 'FLAGGED'])
    ->limit(20)
    ->page(1)
    ->get();
```

Each condition in `anyOf()` is a native IMAP2 search string, for example
`UNSEEN` or `FROM "support@example.com" UNANSWERED`. Existing chained filters
still apply to every branch. Results are combined without duplicates before
sorting and pagination. Calling `anyOf()` again replaces the previous OR group.
Unsupported operators or malformed alternatives cause an
`InvalidArgumentException`; use `searchHeader()` for header conditions.

Filter by a header such as `References`, `Message-ID` or an application-specific
field:

```php
$emails = $reader
    ->clearFilters()
    ->from('support@example.com')
    ->searchHeader('References', '<original@example.com>')
    ->get();
```

Header names are case-insensitive. Header values use a case-insensitive
substring match; an empty search value checks whether the header exists.
Several header filters must all match. Folded and repeated headers are
supported.

Header filtering reads the headers of candidate messages first and downloads
full messages only for matches. Apply sender, date or status filters first
when searching a large mailbox.

`clearFilters()` starts a new query while keeping the connection and pagination
settings. `id($uid)` selects a specific message and takes precedence over query
filters and pagination.

### UID lists and message overviews

| Method | Result |
| --- | --- |
| `searchUIDs()` | Matching UIDs in the selected order and page |
| `searchUIDs(false)` | All matching UIDs, without pagination |
| `countMatching()` | Number of matches before pagination |
| `getOverview()` | Message summaries for the selected order and page |

These methods leave the loaded `emails()` collection available. Overviews
include native IMAP fields such as `uid`, `msgno`, `subject`, `from`, `to`,
`date`, `size`, `seen`, `flagged` and `answered`; some fields may be absent.
Subject and address display text are decoded. These calls do not download
message bodies or attachment contents, and do not mark messages as read.

```php
foreach ($reader->clearFilters()->unseen()->limit(20)->page(1)->getOverview() as $message) {
    echo $message->uid . ': ' . ($message->subject ?? '') . PHP_EOL;
}
```

### Processing a large mailbox

```php
foreach ($reader->clearFilters()->unseen()->limit(0)->page(1)->iterate() as $uid => $email) {
    echo $uid . ': ' . $email->subject() . PHP_EOL;
    // Process this message here.
}
```

`iterate()` yields one `Email` at a time, keyed by UID, and does not build an
`emails()` result collection. It uses the configured filters, order, pagination
and automatic read marking. The UID list is captured before processing, so
marking or moving a processed message does not shift the remaining selection.
The UID list and the current message still require memory; a single message
can contain large attachments. Keep the same account and mailbox throughout
the iteration.

## Message fields and attachments

| Data | Methods on an `Email` object |
| --- | --- |
| Identification | `id()` (UID), `msgno()`, `size()` |
| Content | `subject()`, `date($format)`, `plain()`, `html()` |
| Addresses | `fromName()`, `fromEmail()`, `to()`, `cc()`, `replyTo()`, `isTo($address)` |
| Headers | `headers()`, `getHeader($name)`, `getHeaderValues($name)` |
| Reply references | `messageId()`, `inReplyTo()`, `references()` |
| Status | `isRecent()`, `isUnseen()`, `isFlagged()`, `isAnswered()`, `isDeleted()`, `isDraft()` |
| Attachments | `hasAttachments()`, `attachments()`, `attachment($id)` |
| Raw message | `eml()`, `saveEml($filename)` |

`getHeader()` looks up names without regard to case and returns the latest
value or `null`. `getHeaderValues()` returns every value of a repeated field
in arrival order. Folded values and colons inside values are preserved.

`messageId()` and `inReplyTo()` return the first message ID, including angle
brackets, or `null`. `references()` returns an ordered list of unique message
IDs. These header IDs identify messages across mailboxes; `id()` remains the
mailbox UID.

```php
echo $email->messageId() ?? '';
echo $email->inReplyTo() ?? '';

foreach ($email->references() as $messageId) {
    echo $messageId . PHP_EOL;
}
```

```php
foreach ($email->attachments() as $attachment) {
    echo $attachment->name() . PHP_EOL;

    // For attachments kept in memory:
    $content = $attachment->content();

    // When an attachment directory is configured:
    $path = $attachment->filePath();
}
```

### Attachment names, metadata and saving

| Method on `EmailAttachment` | Result |
| --- | --- |
| `originalName()` | Decoded sender filename, or a generated name if absent |
| `name()` | Existing attachment name, including its ID prefix |
| `mime()` | Declared MIME type |
| `extension()` | Lowercase filename extension |
| `size()` | Decoded content size or saved file size, in bytes |
| `save($directory, $filename = null, $overwrite = false)` | Absolute path of the saved file |

RFC 2231 extended filenames and numbered filename continuations are supported,
including non-ASCII names. `save()` uses the original filename by default and
removes path components. If that filename already exists, a suffix such as
` (1)` is added. Set `$overwrite` to `true` only when replacing an existing file
is intended. The destination directory must already exist and be writable.
The returned path and `filePath()` identify the actual saved file, including
any filename changes. `name()` and `content()` are retained. Automatic saving
through the constructor's attachment directory also handles filename collisions.
Names that exceed common filesystem limits are shortened for saving;
`originalName()` retains the full display name. Invalid filenames or directories
cause an `InvalidArgumentException`. Unreadable attachment content or failed
writes cause a `RuntimeException`; `size()` also throws if no readable content
is available.

```php
foreach ($email->attachments() as $attachment) {
    $path = $attachment->save(__DIR__ . '/downloads');
    echo $attachment->originalName() . ' -> ' . $path . PHP_EOL;
}
```

To inspect attachments before downloading a message, use
`$reader->getAttachmentInfo($uid)`. Each entry contains `part`, `name`, `mime`,
`disposition`, `content_id` and `bytes`. This reads the MIME structure without
fetching attachment contents. `bytes` is the size reported for the encoded
MIME part and can differ from the decoded file size, or be `null` if unavailable.
Attachments without a sender filename receive a name based on their MIME part.

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
