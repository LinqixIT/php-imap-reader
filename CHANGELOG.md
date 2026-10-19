# Changelog

Maintained by [pascal-lpholding](https://github.com/pascal-lpholding) under
[LinqixIT](https://github.com/LinqixIT), based on
[benhall14/php-imap-reader](https://github.com/benhall14/php-imap-reader).

## 2026-10-20 – PHP 8.1–8.5 compatibility, features and fixes

### Added

- Added 23 detailed standalone examples under `tests/<category>/<function>.php`, covering connection handling, filtering, searches, pagination, UIDs, overviews, iteration, headers, attachments, EML export, mailbox actions, search charsets and legacy namespace compatibility.
- `searchHeader()` for filtering by custom headers before downloading complete messages, with support for folded and repeated fields.
- `anyOf()` for OR searches. Existing filters apply to each branch, and matching UIDs are deduplicated before sorting and pagination.
- `searchUIDs()`, `countMatching()` and `getOverview()` for working with message lists without downloading bodies or attachments.
- `iterate()` for processing messages one at a time from a stable UID selection.
- `clearFilters()` to start a new query on the same connection.
- `messageId()`, `inReplyTo()`, `references()` and `getHeaderValues()` for reply tracking and repeated header fields.
- `getAttachmentInfo()` to inspect attachment metadata without downloading its contents.
- `originalName()`, `mime()`, `extension()`, `size()` and `save()` on attachments. Saving uses a filename suffix for collisions unless overwriting is requested.
- RFC 2231 extended filenames and numbered continuations for attachment names.
- `searchCharset()` to configure the IMAP search charset independently of the message output encoding. Passing `null` omits the search charset.
- `htmlWithInlineImages()` to embed available inline images from memory or disk as data URLs in the returned HTML.
- `getHeaders()`, `doesMailboxExists()` and `doesFolderExists()` aliases for method names used in examples from the original project.

### Changed

- Composer package: `linqixit/php-imap-reader`, maintained by LinqixIT and pascal-lpholding.
- Canonical namespace: `linqixit\phpImapReader`. Composer automatically loads compatibility aliases for the original `benhall14\phpImapReader` classes. Existing imports and type declarations remain supported.
- The original package and this fork cannot be installed together because they share the legacy class names. Class-name strings reported by PHP use the canonical LinqixIT namespace.
- Requires PHP `^8.1`, `ext-imap` and `ext-iconv`; `ext-mbstring` is optional.
- IMAP needs a separate installation from PHP 8.4 onward.
- Documentation is available in English.
- Benjamin Hall's original attribution and MIT license are retained, with an additional notice for fork modifications.

### Fixed

- Recognize `IMAP\Connection` objects and reuse active connections.
- Handle repeated closes and connections closed outside the Reader.
- Reconnect and switch folders without an additional expunge. `close()`, `reset()` and the destructor retain their existing expunge behavior.
- Check failed mailbox, header, structure and body fetches before processing. Unavailable individual messages return `null` and are skipped by `get()`.
- Handle missing fields and `null` values without the related PHP warnings or deprecation notices, including `null` array keys on PHP 8.5.
- Decode raw UTF-8 headers and preserve data when a character-set conversion is unavailable. Avoid deprecated mbstring transfer encodings.
- Preserve MIME 8BIT and BINARY data and decode Base64 and Quoted-Printable content correctly.
- Mark messages as read by UID and return the Reader from `read()` for chaining.
- Reject invalid or empty date filters with `InvalidArgumentException`.
- Report a clear error when the IMAP extension is missing.
- Header parsing now preserves folded values, repeated fields and embedded colons. Header lookups are case-insensitive.
- Attachment filenames are confined to the selected directory when saved.
- Automatic attachment saving also adds a suffix on filename collisions instead of silently reusing an existing file.
- Recognize named text attachments and inline images with a Content-ID.
- Attached messages are kept as one attachment instead of adding their enclosed parts to the outer message.
- Search values containing quotation marks or backslashes are passed as literal text. Subject and body searches also accept `0` as a search term.
- Inline-image replacement matches complete Content-IDs and supports URL-encoded references, avoiding collisions between IDs with the same prefix.
- EML exports report failed file creation or writes and avoid overwriting an existing regular file.
