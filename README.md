# php-imap-reader

A maintained continuation of [`benhall14/php-imap-reader`](https://github.com/benhall14/php-imap-reader) for PHP 8.1–8.5.

This fork keeps the original lightweight API while updating the library for modern PHP IMAP behavior, improving message and attachment handling, and adding commonly requested features.

Maintained by [pascal-lpholding](https://github.com/pascal-lpholding) under [LinqixIT](https://github.com/LinqixIT).

---

## Why this fork exists

The original `benhall14/php-imap-reader` project provided a small and convenient wrapper around PHP's native IMAP extension.

Since then, PHP and the IMAP extension have changed significantly.

Starting with PHP 8.1, IMAP connections are represented by `IMAP\Connection` objects instead of the resource-based behavior older code commonly relied on.

Starting with PHP 8.4, the IMAP extension is **deprecated and unbundled from PHP**. It is still available, but it must be installed separately.

This fork exists to keep the familiar API usable on current PHP versions while fixing compatibility issues and extending the library without turning it into a large framework.

The main goals are:

- Support PHP 8.1 through PHP 8.5.
- Preserve the original API wherever practical.
- Keep existing `benhall14\phpImapReader` imports working.
- Use `linqixit\phpImapReader` as the canonical namespace.
- Support modern `IMAP\Connection` objects.
- Improve message, MIME, encoding and attachment handling.
- Add useful search and mailbox helpers.
- Provide practical examples for common operations.
- Keep the library small and easy to understand.

More information about PHP's IMAP extension:

https://www.php.net/manual/en/book.imap.php

---

## Requirements

- PHP `^8.1`
- `ext-imap`
- `ext-iconv`
- `ext-mbstring` is optional

> On PHP 8.4 and newer, the IMAP extension is no longer bundled with PHP and must be installed separately.

---

## Installation

Install with Composer:

```bash
composer require linqixit/php-imap-reader
```

Or add it manually to your `composer.json`:

```json
{
    "require": {
        "linqixit/php-imap-reader": "^1.0"
    }
}
```

Then run:

```bash
composer install
```

---

## Namespace

The canonical namespace is:

```php
use linqixit\phpImapReader\Reader;
use linqixit\phpImapReader\Email;
use linqixit\phpImapReader\EmailAttachment;
```

For backwards compatibility, the original namespace is also supported:

```php
use benhall14\phpImapReader\Reader;
use benhall14\phpImapReader\Email;
use benhall14\phpImapReader\EmailAttachment;
```

Both namespace styles refer to the same classes.

This makes it possible to migrate existing projects without immediately changing all imports.

---

## Basic usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use linqixit\phpImapReader\Reader;

$reader = new Reader(
    '{imap.example.com:993/imap/ssl}INBOX',
    'user@example.com',
    'password'
);

$emails = $reader->get();

foreach ($emails as $email) {
    echo $email->subject() . PHP_EOL;
}
```

---

## Reading messages

```php
$emails = $reader
    ->clearFilters()
    ->limit(20)
    ->page(1)
    ->get();

foreach ($emails as $email) {
    echo 'Subject: ' . $email->subject() . PHP_EOL;
    echo 'From: ' . $email->from() . PHP_EOL;
    echo 'Date: ' . $email->date() . PHP_EOL;
}
```

---

## Search by subject

```php
$emails = $reader
    ->clearFilters()
    ->searchSubject('Invoice')
    ->get();
```

Search values containing quotation marks and backslashes are handled as literal search text.

```php
$emails = $reader
    ->clearFilters()
    ->searchSubject('Invoice "April"')
    ->get();
```

---

## Search by body

```php
$emails = $reader
    ->clearFilters()
    ->searchBody('payment received')
    ->get();
```

---

## Search custom headers

Use `searchHeader()` to search fields such as `Message-ID`, `References`, custom application headers or other message headers.

```php
$emails = $reader
    ->clearFilters()
    ->searchHeader('X-Customer-ID', '12345')
    ->get();
```

Header names are matched case-insensitively.

Folded and repeated header fields are supported.

---

## OR searches

Use `anyOf()` when multiple IMAP search conditions should be combined using OR logic.

```php
$emails = $reader
    ->clearFilters()
    ->anyOf([
        'UNSEEN',
        'FLAGGED',
    ])
    ->get();
```

Duplicate UIDs are removed automatically.

---

## Search charset

The IMAP search charset can be configured independently from the message output encoding.

```php
$reader->searchCharset('UTF-8');
```

Some servers may require another charset:

```php
$reader->searchCharset('US-ASCII');
```

To omit the charset argument entirely:

```php
$reader->searchCharset(null);
```

---

## Work with UIDs only

For large mailboxes, you can retrieve only matching UIDs first:

```php
$uids = $reader
    ->clearFilters()
    ->searchSubject('Invoice')
    ->searchUIDs();

foreach ($uids as $uid) {
    echo $uid . PHP_EOL;
}
```

---

## Count matching messages

```php
$count = $reader
    ->clearFilters()
    ->searchSubject('Invoice')
    ->countMatching();

echo $count;
```

---

## Message overview

Use `getOverview()` when you only need lightweight message metadata instead of full message bodies and attachments.

```php
$messages = $reader
    ->clearFilters()
    ->limit(50)
    ->getOverview();

foreach ($messages as $message) {
    print_r($message);
}
```

---

## Iterate through large mailboxes

Use `iterate()` when messages should be processed one at a time.

```php
foreach ($reader->clearFilters()->iterate() as $email) {
    echo $email->subject() . PHP_EOL;
}
```

This is useful when processing larger mailboxes without keeping every `Email` object in memory at once.

---

## Message IDs and reply tracking

The library exposes common threading headers:

```php
$messageId = $email->messageId();
$inReplyTo = $email->inReplyTo();
$references = $email->references();
```

You can also access repeated headers:

```php
$values = $email->getHeaderValues('References');
```

This can be used to associate replies with earlier messages.

---

## Headers

Retrieve all parsed headers:

```php
$headers = $email->getHeaders();

print_r($headers);
```

Header parsing preserves:

- repeated fields
- folded values
- embedded colons
- case-insensitive lookup

---

## Attachments

```php
foreach ($email->attachments() as $attachment) {
    echo $attachment->originalName() . PHP_EOL;
    echo $attachment->mime() . PHP_EOL;
    echo $attachment->extension() . PHP_EOL;
    echo $attachment->size() . PHP_EOL;
}
```

Supported attachment improvements include:

- RFC 2231 filenames
- numbered filename continuations
- international filenames
- named text attachments
- inline images with `Content-ID`
- attached messages
- safe filename handling

---

## Inspect attachment metadata first

Use `getAttachmentInfo()` when you want attachment information before loading the complete attachment content.

```php
$info = $reader->getAttachmentInfo($uid);

print_r($info);
```

---

## Save attachments

```php
foreach ($email->attachments() as $attachment) {
    $attachment->save(__DIR__ . '/downloads');
}
```

If a file with the same name already exists, a suffix is added automatically unless overwrite behavior is explicitly requested.

For example:

```text
invoice.pdf
invoice (1).pdf
invoice (2).pdf
```

---

## Inline images

HTML messages containing `cid:` image references can be rendered with inline images embedded as data URLs:

```php
$html = $email->htmlWithInlineImages();

echo $html;
```

Complete Content-IDs are matched, including URL-encoded references.

---

## Save a message as EML

```php
$email->saveEml(__DIR__ . '/message.eml');
```

Failed file creation or write operations are reported instead of being silently ignored.

Existing regular files are not overwritten automatically.

---

## Mailboxes and folders

Check whether a mailbox exists:

```php
if ($reader->doesMailboxExists('INBOX')) {
    echo 'Mailbox exists';
}
```

The alternative spelling used by some older examples is also supported:

```php
if ($reader->doesFolderExists('INBOX')) {
    echo 'Folder exists';
}
```

---

## Mark messages as read

```php
$reader->read($uid);
```

`read()` returns the reader instance, so chaining remains possible:

```php
$reader
    ->read($uid)
    ->clearFilters();
```

---

## Reusing the connection

The reader supports modern `IMAP\Connection` objects and reuses active connections where possible.

Reconnects and folder changes are handled without unnecessary extra expunge operations.

---

## Examples

The repository includes practical example scripts under:

```text
tests/<category>/<function>.php
```

Examples include:

```text
tests/connection/connect.php
tests/connection/reconnect.php

tests/search/searchSubject.php
tests/search/searchBody.php
tests/search/searchHeader.php
tests/search/anyOf.php
tests/search/searchCharset.php
tests/search/searchUIDs.php

tests/messages/get.php
tests/messages/getOverview.php
tests/messages/countMatching.php
tests/messages/iterate.php
tests/messages/getEmailByUID.php
tests/messages/markAsRead.php

tests/email/getHeaders.php
tests/email/messageId.php
tests/email/inReplyTo.php
tests/email/references.php
tests/email/htmlWithInlineImages.php
tests/email/saveEml.php

tests/attachments/getAttachmentInfo.php
tests/attachments/save.php

tests/mailbox/doesMailboxExists.php
tests/mailbox/doesFolderExists.php
```

Most examples can use environment variables for connection details:

```bash
export IMAP_HOST='{mail.example.com:993/imap/ssl}INBOX'
export IMAP_USER='user@example.com'
export IMAP_PASS='secret'
```

Then run an example:

```bash
php tests/search/searchHeader.php
```

---

## Backwards compatibility

Existing applications using the original namespace can continue to use imports such as:

```php
use benhall14\phpImapReader\Reader;
```

The compatibility layer maps the original class names to the LinqixIT classes.

The original package and this fork should not be installed together because both expose the legacy class names.

---

## PHP support

This fork targets:

```text
PHP 8.1
PHP 8.2
PHP 8.3
PHP 8.4
PHP 8.5
```

The project intentionally keeps its PHP requirements compatible with PHP 8.1 instead of using syntax that would require a newer PHP version.

---

## Original project

This project is based on:

[`benhall14/php-imap-reader`](https://github.com/benhall14/php-imap-reader)

Original author:

**Benjamin Hall / benhall14**

This fork is maintained by:

**LinqixIT / pascal-lpholding**

The original attribution is retained.

---

## License

This project is licensed under the MIT License.

The original copyright and license notice from `benhall14/php-imap-reader`
are retained in accordance with the MIT License.

Additional changes and continued maintenance are provided by LinqixIT.

See [`LICENSE`](LICENSE) for details.
