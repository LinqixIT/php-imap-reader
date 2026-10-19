# Changelog

Changes in this fork by [pascal-lpholding](https://github.com/pascal-lpholding)
under [LinqixIT](https://github.com/LinqixIT), based on
[php-imap-reader by Benjamin Hall (benhall14)](https://github.com/benhall14/php-imap-reader).

## 2026-10-19 – PHP 8.1–8.5 compatibility

### Project identity and attribution

- GitHub project: [LinqixIT/php-imap-reader](https://github.com/LinqixIT/php-imap-reader).
- Maintainer: `pascal-lpholding`; organization: `LinqixIT`.
- Separate Composer package identity: `linqixit/php-imap-reader`.
- Retained credit for the original author, Benjamin Hall, and the original project.
- Preserved the MIT license and original copyright notices; added a copyright
  notice for the modifications in this fork.
- Adopted the dedicated PHP namespace `linqixit\phpImapReader` and updated the
  autoload mapping.
- Translated all project documentation into English.

### Namespace and migration compatibility

- The canonical PHP namespace is `linqixit\phpImapReader`.
- Added compatibility aliases for `Reader`, `Email` and `EmailAttachment` in
  the original `benhall14\phpImapReader` namespace. Composer registers these
  aliases automatically when `vendor/autoload.php` is loaded.
- Existing `use` statements, fully qualified class references and type
  declarations using the original names remain supported. New code can use
  `linqixit\phpImapReader` directly.
- Class names reported by `get_class()`, reflection and newly serialized
  objects use the canonical namespace. Code that compares or stores class
  names as exact strings may need adjustment.
- When switching through Composer, change the package dependency from
  `benhall14/php-imap-reader` to `linqixit/php-imap-reader`, update the
  application's lock file and regenerate its autoloader.
- Declared a Composer conflict with the original package because the
  compatibility aliases share its class names.

### Compatibility

- One shared codebase for PHP 8.1 through PHP 8.5.
- Set the Composer requirement to `php: ^8.1` and updated `composer.lock`.
- Declared `ext-imap` and `ext-iconv` as requirements; `ext-mbstring` is optional.
- Added guidance on installing IMAP separately starting with PHP 8.4.
- Preserved class names, public method names, and parameter order.

### Fixes

- `isImapResource()` recognizes the `IMAP\Connection` objects used since PHP 8.1.
- Reused active connections; `get()` no longer opens an additional, unused
  connection.
- `close()` clears the stored handle and can be called repeatedly. Connections
  closed externally are handled explicitly.
- Reconnecting and switching folders close the previous connection without an
  additional expunge. Switching folders then uses the requested mailbox.
- Checked `false` results when fetching mailbox lists, headers, structures, and
  body parts before passing them to subsequent function calls.
- Missing sender fields and `null` values in message bodies, dates, format strings,
  and inline replacements no longer trigger the corresponding warnings or
  deprecation notices.
- Missing attachment IDs and header keys are explicitly treated as empty strings
  to avoid the PHP 8.5 deprecation of `null` array keys.
- Handled raw UTF-8 headers correctly. Unknown character sets no longer cause an
  unhandled `ValueError`; the original data is preserved if conversion is not
  possible.
- Stopped using deprecated mbstring transfer encodings as character sets.
- Preserved MIME 8BIT and BINARY content as raw data; decoded Base64 and
  Quoted-Printable content. Binary attachments are no longer accidentally
  Base64-encoded again.
- Marking a message as read uses its actual UID instead of its sequence number.
- `read()` returns the Reader and supports method chaining like `seen()`.
- Corrected invalid method names in the usage examples.

### Error handling and behavior

- Invalid or empty date filters throw `InvalidArgumentException`.
- Individual messages that cannot be fetched may return `null`; `get()` skips
  these results and returns successfully read `Email` objects.
- Missing raw body data is treated as an empty string if the rest of the message
  can be read.
- A clear exception is thrown if the IMAP extension is missing.
- Preserved the existing expunge behavior of `close()`, `reset()`, and the destructor.

### Verification

The compatibility changes were verified with PHP 8.1.34 and PHP 8.5.0: on each
version, all 27 regression tests with mbstring, all 27 tests using the iconv
fallback, and all 29 checks with the native IMAP extension passed. Syntax
checks, Composer validation, and an installation dry run succeeded on both
versions.

The compatibility aliases passed 12 additional runs on PHP 8.1.34 and PHP
8.5.0, covering normal, optimized and authoritative Composer autoloading with
both original and canonical class names used first. These checks covered all
three aliases, class identity, `instanceof`, parameter and return type
declarations, interactions between classes and repeated loading of
`vendor/autoload.php`. Class loading was also verified without ext-imap and
without connecting to a mail server. Syntax checks, Composer validation and
installation dry runs passed again on both versions.

The native checks used an empty local test mailbox and native MIME functions.
Complete message retrieval from an actual email provider, including TLS and
provider authentication, was not tested.
