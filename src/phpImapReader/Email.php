<?php

namespace linqixit\phpImapReader;

use DateTime;
use stdClass;

/**
 * IMAP Email Object Class.
 *
 * @category  Protocols
 * @package   Protocols
 * @author    Benjamin Hall <ben@conobe.co.uk> (original author)
 * @author    pascal-lpholding (LinqixIT fork maintainer)
 * @copyright 2022 Copyright (c) Benjamin Hall
 * @copyright 2026 pascal-lpholding (modifications)
 * @license   MIT https://github.com/benhall14/php-imap-reader
 * @link      https://conobe.co.uk/projects/php-imap-reader/
 * @see       https://github.com/LinqixIT/php-imap-reader
 */
class Email
{
    /**
     * The ID of this email.
     * 
     * @var int
     */
    public $id;

    /**
     * The msgno of this email.
     * 
     * @var int
     */
    public $msgno;

    /**
     * The date this email was received.
     * 
     * @var DateTime
     */
    public $date;

    /**
     * The UNIX time stamp when this email was received.
     * 
     * @var int
     */
    public $udate;

    /**
     * An array containing the custom headers of the email.
     * 
     * @var array
     */
    public $custom_headers = array();

    /**
     * All values for each header, indexed by its lowercase field name.
     *
     * @var array
     */
    protected $header_values = array();

    /**
     * The subject line of this email.
     * 
     * @var string
     */
    public $subject;

    /**
     * The recipient of this email.
     * 
     * @var array
     */
    public $to = array();

    /**
     * The sender of this email.
     * 
     * @var string
     */
    public $from;

    /**
     * An array of Reply To email addresses.
     * 
     * @var array
     */
    public $reply_to = array();

    /**
     * An array of Carbon Copy recipients.
     * 
     * @var array
     */
    public $cc = array();

    /**
     * The 'recent' flag.
     * 
     * @var boolean
     */
    public $recent;

    /**
     * The 'unseen' flag.
     * 
     * @var boolean
     */
    public $unseen;

    /**
     * The 'flagged' flag.
     * 
     * @var boolean
     */
    public $flagged;

    /**
     * The 'answered' flag.
     * 
     * @var boolean
     */
    public $answered;

    /**
     * The 'deleted' flag.
     * 
     * @var boolean
     */
    public $deleted;

    /**
     * The 'draft' flag.
     * 
     * @var boolean
     */
    public $draft;

    /**
     * The integer size of this email.
     * 
     * @var int
     */
    public $size;

    /**
     * The plain text body of this email.
     * 
     * @var string
     */
    public $text_plain;

    /**
     * The HTML body of this email.
     * 
     * @var string
     */
    public $text_html;

    /**
     * An array of attachments, including inline.
     * 
     * @var array
     */
    public $attachments = array();

    /**
     * Raw Body
     *
     * @var string
     */
    public $raw_body;

    /**
     * Checks if the email recipient matches the given email address.
     * 
     * @param string $email The email address to match against the recipient.
     * 
     * @return boolean
     */
    public function isTo($email)
    {
        foreach ($this->to as $to) {
            if ($email == $to->email) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the 'Reply To' email addresses for this email.
     * 
     * @return array
     */
    public function replyTo()
    {
        return $this->reply_to;
    }

    /**
     * Get the 'Carbon Copied' email addresses for this email.
     * 
     * @return array An array of email addresses in the cc field.
     */
    public function cc()
    {
        return $this->cc;
    }

    /**
     * Get the recipient of this email.
     * 
     * @return string
     */
    public function to()
    {
        return $this->to;
    }

    /**
     * Get the ID of this email.
     * 
     * @return integer The ID of the email.
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Get the msgno of this email.
     * 
     * @return integer The msgno of the email.
     */
    public function msgno()
    {
        return $this->msgno;
    }

    /**
     * Get the custom headers of this email.
     * 
     * @return array The custom headers of the email.
     */
    public function customHeaders()
    {
        return $this->custom_headers;
    }

    /**
     * Get the headers of this email.
     * 
     * @return array The custom headers of the email.
     */
    public function headers()
    {
        return $this->customHeaders();
    }

    /**
     * An alias for headers().
     *
     * @return array The custom headers of the email.
     */
    public function getHeaders()
    {
        return $this->headers();
    }

    /**
     * Get the size in bytes of this email.
     * 
     * @return integer
     */
    public function size()
    {
        return (int) $this->size;
    }

    /**
     * Get the date that this email was received.
     * 
     * @param string $format The format in which to return the date.
     * 
     * @return string
     */
    public function date($format = 'Y-m-d H:i:s')
    {
        return $this->date->format($format ?? '');
    }

    /**
     * Get the subject line of this email.
     * 
     * @return string
     */
    public function subject()
    {
        return $this->subject;
    }

    /**
     * Get the sender name of this email.
     * 
     * @return string
     */
    public function fromName()
    {
        return !empty($this->from->name) ? $this->from->name : null;
    }

    /**
     * Get the sender email address of this email.
     * 
     * @return string
     */
    public function fromEmail()
    {
        return !empty($this->from->email) ? $this->from->email : null;
    }

    /**
     * Get the plain text body of this email.
     * 
     * @return string
     */
    public function plain()
    {
        return $this->text_plain;
    }

    /**
     * Get the HTML body of this email.
     * 
     * @return string
     */
    public function html()
    {
        return $this->text_html ? $this->injectInline($this->text_html) : false;
    }

    /**
     * Get HTML with available inline images embedded as base64 data URIs.
     * Missing or empty image content leaves the original CID reference intact.
     * This does not sanitize the HTML or inspect the declared image content.
     *
     * @return string|false False when the email has no HTML body.
     * @throws \RuntimeException For invalid content or a failed or incomplete read.
     */
    public function htmlWithInlineImages()
    {
        if (!$this->text_html) {
            return false;
        }

        $images = array();

        foreach ($this->attachments as $attachment) {
            $id = $attachment->id();
            $mime = $attachment->mime();

            // A bare image type with a URI-safe subtype cannot introduce
            // parameters, fragments, quotes or controls into the data URI.
            if ($attachment->isInline() && $id !== null && (string) $id !== ''
                && is_string($mime)
                && preg_match('~\Aimage/[a-z0-9][a-z0-9.+_-]{0,126}\z~i', $mime)) {
                $images[(string) $id] = array($attachment, strtolower($mime));
            }
        }

        $resolved = array();

        return $this->replaceInlineReferences($this->text_html, function ($id) use ($images, &$resolved) {
            if (!isset($images[$id])) {
                return null;
            }

            if (!array_key_exists($id, $resolved)) {
                $bytes = $this->inlineImageBytes($images[$id][0]);
                $resolved[$id] = $bytes === null || $bytes === ''
                    ? null : 'data:' . $images[$id][1] . ';base64,' . base64_encode($bytes);
            }

            return $resolved[$id];
        });
    }

    /**
     * Return a boolean based on whether this email has attachments.
     * 
     * @return boolean
     */
    public function hasAttachments()
    {
        return (count($this->attachments)) ? true : false;
    }

    /**
     * Return an array of the attachments for this email.
     * 
     * @return array
     */
    public function attachments()
    {
        return $this->attachments;
    }

    /**
     * Return a specific attachment based on the attachment id.
     * 
     * @param integer $attachment_id The attachment to return.
     * 
     * @return EmailAttachment
     */
    public function attachment($attachment_id)
    {
        $attachment_id = $attachment_id ?? '';

        return isset($this->attachments[$attachment_id]) ? $this->attachments[$attachment_id] : false;
    }

    /**
     * Return the status of the recent flag.
     * 
     * @return boolean
     */
    public function isRecent()
    {
        return $this->recent;
    }

    /**
     * Return the status of the unseen flag.
     * 
     * @return boolean
     */
    public function isUnseen()
    {
        return $this->unseen;
    }

    /**
     * Return the status of the flagged flag.
     * 
     * @return boolean
     */
    public function isFlagged()
    {
        return $this->flagged;
    }

    /**
     * Return the status of the answered flag.
     * 
     * @return boolean
     */
    public function isAnswered()
    {
        return $this->answered;
    }

    /**
     * Return the status of the deleted flag.
     * 
     * @return boolean
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Return the status of the draft flag.
     * 
     * @return boolean
     */
    public function isDraft()
    {
        return $this->draft;
    }

    /**
     * Set the subject line for this email.
     * 
     * @param string $subject The subject line string.
     * 
     * @return Email
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set the unique id for this email.
     * 
     * @param integer $id The id.
     * 
     * @return Email
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set the msgno for this email.
     * 
     * @param integer $msgno The msgno.
     * 
     * @return Email
     */
    public function setMsgno($msgno)
    {
        $this->msgno = $msgno;

        return $this;
    }

    /**
     * Sets the raw body.
     *
     * @param string $body
     *
     * @return Email
     */
    public function setRawBody($body)
    {
        $this->raw_body = $body;

        return $this;
    }

    /**
     * Set the date for this email.
     * 
     * @param string $date The Date string.
     * 
     * @return Email
     */
    public function setDate($date)
    {
        $cleanedDate = preg_replace("/\s\(.*\)$/", "", $date ?? '');

        $this->date = new DateTime(trim($cleanedDate));

        return $this;
    }

    /**
     * Set the UNIX time stamp for this email.
     * 
     * @param integer $date A UNIX time stamp.
     * 
     * @return Email
     */
    public function setUdate($date)
    {
        $this->udate = $date;

        return $this;
    }

    /**
     * Set the size of this email.
     * 
     * @param integer $size Size in bytes.
     * 
     * @return Email
     */
    public function setSize($size)
    {
        $this->size = (int) $size;

        return $this;
    }

    /**
     * Sets the unseen flag based on the given boolean state.
     * 
     * @param boolean $boolean The flag status.
     * 
     * @return Email
     */
    public function setUnseen($boolean)
    {
        $this->unseen = (bool) $boolean;

        return $this;
    }

    /**
     * Sets the answered flag based on the given boolean state.
     * 
     * @param boolean $boolean The flag status.
     * 
     * @return Email
     */
    public function setAnswered($boolean)
    {
        $this->answered = (bool) $boolean;

        return $this;
    }

    /**
     * Sets the draft flag based on the given boolean state.
     * 
     * @param boolean $boolean The flag status.
     * 
     * @return Email
     */
    public function setDraft($boolean)
    {
        $this->draft = (bool) $boolean;

        return $this;
    }

    /**
     * Sets the recent flag based on the given boolean state.
     * 
     * @param boolean $boolean The flag status.
     * 
     * @return Email
     */
    public function setRecent($boolean)
    {
        $this->recent = (bool) $boolean;

        return $this;
    }

    /**
     * Sets the flagged flag based on the given boolean state.
     * 
     * @param boolean $boolean The flag status.
     * 
     * @return Email
     */
    public function setFlagged($boolean)
    {
        $this->flagged = (bool) $boolean;

        return $this;
    }

    /**
     * Sets the deleted flag based on the given boolean state.
     * 
     * @param boolean $boolean The flag status.
     * 
     * @return Email
     */
    public function setDeleted($boolean)
    {
        $this->deleted = (bool) $boolean;

        return $this;
    }

    /**
     * Adds a recipient to the 'To' array.
     * 
     * @param string $mailbox The mailbox.
     * @param string $host    The host name.
     * @param string $name    (optional) The recipient name.
     * 
     * @return Email
     */
    public function addTo($mailbox, $host, $name = false)
    {
        if (!$mailbox || !$host) {
            return false;
        }

        $to = new stdClass();

        $to->name = ($name) ? $name : false;

        $to->mailbox = $mailbox;

        $to->host = $host;

        $to->email = $to->mailbox . '@' . $to->host;

        $this->to[] = $to;

        return $this;
    }

    /**
     * Adds a 'Reply To' to 'Reply To' array.
     * 
     * @param string $mailbox The mailbox.
     * @param string $host    The host name.
     * @param string $name    (optional) The reply to name.
     * 
     * @return Email
     */
    public function addReplyTo($mailbox, $host, $name = false)
    {
        if (!$mailbox || !$host) {
            return false;
        }

        $reply_to = new stdClass();

        $reply_to->name = ($name) ? $name : false;

        $reply_to->mailbox = $mailbox;

        $reply_to->host = $host;

        $reply_to->email = $reply_to->mailbox . '@' . $reply_to->host;

        $this->reply_to[] = $reply_to;

        return $this;
    }

    /**
     * Replace the headers with an RFC header block, unfolding continued lines.
     * Parsing stops at the first empty line, before any message body.
     *
     * @param string $raw_headers The raw headers, with CRLF or LF line endings.
     *
     * @return Email
     */
    public function setHeaders($raw_headers)
    {
        $this->custom_headers = array();
        $this->header_values = array();
        $current_header = null;

        foreach (preg_split('/\r\n|\n/', (string) ($raw_headers ?? '')) as $line) {
            if ($line === '') {
                break;
            }

            if ($line[0] === ' ' || $line[0] === "\t") {
                if ($current_header !== null) {
                    // Unfold by removing the line break and keeping its whitespace.
                    $current_header .= $line;
                }
                continue;
            }

            if ($current_header !== null) {
                $this->addCustomHeader($current_header);
            }

            $current_header = $line;
        }

        if ($current_header !== null) {
            $this->addCustomHeader($current_header);
        }

        return $this;
    }

    /**
     * Add one header, preserving repeated fields and colons in its value.
     * The associative headers() view keeps the last value for each field.
     *
     * @param string $custom_header The header to add, optionally folded.
     *
     * @return Email|false
     */
    public function addCustomHeader($custom_header)
    {
        if (!$custom_header) {
            return false;
        }

        $custom_header = preg_replace('/\r?\n(?=[ \t])/', '', (string) $custom_header);
        $custom_header = rtrim($custom_header, "\r\n");

        // A single field must not contain another header or a message body.
        if (strpbrk($custom_header, "\r\n") !== false) {
            return $this;
        }

        $header_info = explode(':', $custom_header, 2);
        $name = trim($header_info[0]);

        if (!isset($header_info[1]) || !preg_match('/^[\x21-\x39\x3b-\x7e]+$/D', $name)) {
            return $this;
        }

        $value = trim($header_info[1]);
        $key = strtolower($name);
        $values = $this->getHeaderValues($name);
        $values[] = $value;
        $this->header_values[$key] = $values;

        // Keep the first spelling of the field name for existing array callers.
        $first_name = null;
        foreach ((array) $this->custom_headers as $existing_name => $existing_value) {
            if (strcasecmp((string) $existing_name, $name) === 0) {
                if ($first_name === null) {
                    $first_name = $existing_name;
                } else {
                    unset($this->custom_headers[$existing_name]);
                }
            }
        }

        $name = $first_name ?? $name;
        $this->custom_headers[$name] = $value;

        return $this;
    }

    /**
     * An alias for addCustomHeader
     *
     * @param string $custom_header
     *
     * @return Email
     */
    public function addHeader($custom_header)
    {
        return $this->addCustomHeader($custom_header);
    }

    /**
     * Return the last value of a header, using a case-insensitive field name.
     *
     * @param string $header_name
     *
     * @return string|null
     */
    public function getCustomHeader($header_name)
    {
        $header_name = (string) ($header_name ?? '');
        $value = null;

        foreach ((array) $this->custom_headers as $name => $header_value) {
            if (strcasecmp((string) $name, $header_name) === 0) {
                $value = $header_value;
            }
        }

        return $value;
    }

    /**
     * An alias for getCustomHeader
     *
     * @param string $header_name
     *
     * @return string|null
     */
    public function getHeader($header_name)
    {
        return $this->getCustomHeader($header_name);
    }

    /**
     * Return all values of a header in their original order.
     *
     * @param string $header_name A case-insensitive field name.
     *
     * @return array
     */
    public function getHeaderValues($header_name)
    {
        $header_name = (string) ($header_name ?? '');
        $current_values = array();

        foreach ((array) $this->custom_headers as $name => $value) {
            if (strcasecmp((string) $name, $header_name) === 0) {
                $current_values[] = $value;
            }
        }

        $key = strtolower($header_name);

        if (count($current_values) === 1 && isset($this->header_values[$key])) {
            $values = $this->header_values[$key];

            if ($values[count($values) - 1] === $current_values[0]) {
                return $values;
            }
        }

        // Honor callers that assign values to the public custom_headers array.
        return $current_values;
    }

    /**
     * Return the first Message-ID, including angle brackets, or null.
     * This is distinct from id(), which returns the mailbox UID.
     *
     * @return string|null
     */
    public function messageId()
    {
        $ids = $this->messageIdsFromHeader('Message-ID');

        return isset($ids[0]) ? $ids[0] : null;
    }

    /**
     * Return the first Message-ID in In-Reply-To, or null.
     *
     * @return string|null
     */
    public function inReplyTo()
    {
        $ids = $this->messageIdsFromHeader('In-Reply-To');

        return isset($ids[0]) ? $ids[0] : null;
    }

    /**
     * Return the References Message-IDs in order, without exact duplicates.
     * Each ID includes its angle brackets.
     *
     * @return array
     */
    public function references()
    {
        return $this->messageIdsFromHeader('References');
    }

    /**
     * Extract angle-bracketed Message-IDs from all occurrences of a header.
     *
     * @param string $header_name The field to read.
     *
     * @return array
     */
    protected function messageIdsFromHeader($header_name)
    {
        $ids = array();

        foreach ($this->getHeaderValues($header_name) as $value) {
            preg_match_all('/<[^<>\s]+@[^<>\s]+>/', (string) ($value ?? ''), $matches);

            foreach ($matches[0] as $id) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * Return the complete .eml message, optionally saving it to a new file.
     * An existing regular file is left unchanged.
     *
     * @param mixed $filename
     *
     * @return string
     * @throws \InvalidArgumentException For an invalid filename.
     * @throws \RuntimeException When creating or writing the file fails.
     */
    public function eml($filename = null)
    {
        if ($filename === null || $filename === false) {
            return $this->raw_body;
        }

        if (!is_string($filename) || $filename === '' || strpos($filename, "\0") !== false
            || preg_match('~\A[a-z][a-z0-9+.-]*://~i', $filename)) {
            throw new \InvalidArgumentException('The EML filename must be a local filesystem path.');
        }

        clearstatcache(true, $filename);

        if (is_link($filename)) {
            throw new \RuntimeException('The EML destination is a symbolic link.');
        }

        if (is_file($filename)) {
            return $this->raw_body;
        }

        $output = @fopen($filename, 'xb');

        if ($output === false) {
            // Another writer may have created the file since the first check.
            clearstatcache(true, $filename);
            if (!is_link($filename) && is_file($filename)) {
                return $this->raw_body;
            }

            throw new \RuntimeException('Unable to create the EML file.');
        }

        try {
            $data = (string) ($this->raw_body ?? '');
            $length = strlen($data);
            $offset = 0;

            while ($offset < $length) {
                $written = @fwrite($output, substr($data, $offset, 8192));
                if ($written === false || $written === 0) {
                    throw new \RuntimeException('Unable to write the complete EML file.');
                }
                $offset += $written;
            }

            if (!@fflush($output)) {
                throw new \RuntimeException('Unable to flush the EML file.');
            }

            $closed = @fclose($output);
            $output = null;
            if (!$closed) {
                throw new \RuntimeException('Unable to close the EML file.');
            }
        } catch (\Throwable $error) {
            if (is_resource($output)) {
                @fclose($output);
            }
            @unlink($filename);
            throw $error;
        }

        return $this->raw_body;
    }

    /**
     * Save the Email as an .eml file.
     *
     * @param string $filename
     *
     * @return string
     * @throws \InvalidArgumentException For an invalid filename.
     * @throws \RuntimeException When creating or writing the file fails.
     */
    public function saveEml($filename)
    {
        if (!is_string($filename) || $filename === '') {
            throw new \InvalidArgumentException('The EML filename must be a non-empty string.');
        }

        return $this->eml($filename);
    }

    /**
     * Adds a carbon copy entry to this email.
     * 
     * @param string $mailbox The mailbox.
     * @param string $host    The host name.
     * @param string $name    (optional) The name of the CC.
     * 
     * @return Email
     */
    public function addCC($mailbox, $host, $name = false)
    {
        if (!$mailbox || !$host) {
            return false;
        }

        $cc = new stdClass();

        $cc->name = $name ?: false;

        $cc->mailbox = $mailbox;

        $cc->host = $host;

        $cc->email = $cc->mailbox . '@' . $cc->host;

        $this->cc[] = $cc;

        return $this;
    }

    /**
     * Set the 'from' email address for this email.
     * 
     * @param string $mailbox The mailbox.
     * @param string $host    The host name.
     * @param string $name    (optional) The senders name.
     * 
     * @return Email
     */
    public function setFrom($mailbox, $host, $name = false)
    {
        $this->from = new stdClass();

        $this->from->name = $name ?: false;

        $this->from->mailbox = $mailbox;

        $this->from->host = $host;

        $this->from->email = $this->from->mailbox . '@' . $this->from->host;

        return $this;
    }

    /**
     * Updates the HTML text body by concatenating the given 
     * string to the current HTML body.
     * 
     * @param string $html The HTML string to be added to the HTML text body.
     * 
     * @return Email
     */
    public function setHTML($html)
    {
        $this->text_html .= trim($html ?? '');

        return $this;
    }

    /**
     * Updates the plain text body by concatenating the given 
     * string to the current plain text body.
     * 
     * @param string $plain The text string to be added to the plain text body.
     * 
     * @return Email
     */
    public function setPlain($plain)
    {
        $this->text_plain .= trim($plain ?? '');

        return $this;
    }

    /**
     * Adds an attachment to this email.
     * 
     * @param EmailAttachment $attachment An attachment object.
     * 
     * @return Email
     */
    public function addAttachment(EmailAttachment $attachment)
    {
        // Preserve the historical empty-string key without PHP 8.5's null-offset deprecation.
        $attachment_id = $attachment->id() ?? '';

        $this->attachments[$attachment_id] = $attachment;

        return $this;
    }

    /**
     * Inject in-line attachments by replacing the attachment ids
     * with the attachment file path.
     * 
     * @param  string $body The email body to have attachments injected.
     * 
     * @return string
     */
    public function injectInline($body)
    {
        $paths = array();

        foreach ($this->attachments as $attachment) {
            if ($attachment->isInline() && $attachment->id() !== null
                && (string) $attachment->id() !== '' && $attachment->filePath()) {
                $paths[(string) $attachment->id()] = $attachment->filePath();
            }
        }

        return $this->replaceInlineReferences($body, static function ($id) use ($paths) {
            return $paths[$id] ?? null;
        });
    }

    /**
     * Resolve complete CID URL tokens once, preserving unknown references.
     * Percent-encoded IDs are decoded once; a literal plus stays a plus.
     *
     * @param string $body
     * @param callable $resolve Returns the replacement, or null to keep the CID.
     * @return string
     */
    protected function replaceInlineReferences($body, callable $resolve)
    {
        if ($body === null || $body === '') {
            return $body;
        }

        return preg_replace_callback(
            '~(?<![a-z0-9+.-])cid:([^\x00-\x20\x7f"\'<>`()\\\\]+)~i',
            static function ($matches) use ($resolve) {
                $replacement = $resolve(rawurldecode($matches[1]));

                return $replacement === null ? $matches[0] : $replacement;
            },
            $body
        );
    }

    /**
     * Read an inline image from memory or a readable saved file.
     * An unavailable source returns null; a read that starts must complete.
     *
     * @return string|null
     * @throws \RuntimeException For invalid content or a failed or incomplete read.
     */
    protected function inlineImageBytes(EmailAttachment $attachment)
    {
        $bytes = $attachment->content();

        if ($bytes !== null) {
            if (!is_string($bytes)) {
                throw new \RuntimeException('Inline image content must be a string.');
            }

            return $bytes;
        }

        $path = $attachment->filePath();

        if (!is_string($path) || $path === '' || strpos($path, "\0") !== false
            || !is_file($path) || !is_readable($path)) {
            return null;
        }

        $source = @fopen($path, 'rb');
        if ($source === false) {
            return null;
        }

        try {
            $info = @fstat($source);
            $bytes = @stream_get_contents($source);

            if ($bytes === false || $info === false || !isset($info['size'])
                || strlen($bytes) !== $info['size']) {
                throw new \RuntimeException('Unable to read the complete inline image.');
            }

            return $bytes;
        } finally {
            @fclose($source);
        }
    }
}

// Keep the original namespace available for existing applications.
if (!\class_exists('benhall14\\phpImapReader\\Email', false)) {
    \class_alias(Email::class, 'benhall14\\phpImapReader\\Email', false);
}
