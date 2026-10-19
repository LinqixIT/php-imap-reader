<?php

namespace linqixit\phpImapReader;

use Exception;

/**
 * IMAP Reader Class.
 *
 * This class is used to fetch emails based on the parameter options set. You can set useful filtering flags such as ALL, ANSWERED, DELETED, FLAGGED, NEW, OLD, RECENT, SEEN, UNANSWERED, UNDELETED, UNGLAGGED, UNKEYWORD, UNSEEN. You can match emails by BCC, BEFORE DATE, CC, FROM, KEYWORD, ON DATE, SINCE DATE, TO. You can also search for text strings in email TEXT, SUBJECT and BODY.
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
class Reader
{
    /**
     * The IMAP host name.
     * 
     * @var string
     */
    public $hostname;

    /**
     * The IMAP user name.
     * 
     * @var string
     */
    public $user_name;

    /**
     * The IMAP password.
     * 
     * @var string
     */
    public $password;

    /**
     * The IMAP Encoding
     *
     * @var string
     */
    public $encoding;

    /**
     * Explicit search charset override. Null omits the CHARSET parameter.
     *
     * @var string|null
     */
    protected $search_charset = null;

    /**
     * Follow the existing public output encoding until an override is set.
     *
     * @var boolean
     */
    protected $search_charset_set = false;

    /**
     * Save attachment status - should we save attachments.
     * 
     * @var boolean
     */
    public $save_attachments;

    /**
     * Retry status
     * 
     * @var boolean
     */
    public $retry;

    /**
     * The IMAP handler.
     * 
     * @var \IMAP\Connection|resource|null|false
     */
    public $imap = null;

    /**
     * The index of email ids.
     * 
     * @var array
     */
    public $email_index = array();

    /**
     * The array of previously fetched emails
     * 
     * @var array
     */
    public $emails = array();

    /**
     * Modes - such as NEW or UNSEEN.
     * 
     * @var array
     */
    public $modes = array();

    /**
     * Header filters applied to the native search results.
     *
     * @var array
     */
    protected $header_filters = array();

    /**
     * Alternative native search criteria, combined as one OR group.
     *
     * @var array
     */
    protected $any_of_criteria = array();

    /**
     * Track folder changes so an iterator cannot fetch UIDs from another folder.
     *
     * @var int
     */
    protected $mailbox_generation = 0;

    /**
     * The id of an specific id.
     * 
     * @var int
     */
    public $id = 0;

    /**
     * Limit the number of emails fetch. Can be used with page for pagination.
     * 
     * @var int
     */
    public $limit = 0;

    /**
     * Page number. Can be used with limit for pagination.
     * 
     * @var int
     */
    public $page = 0;

    /**
     * The email offset.
     * 
     * @var int
     */
    public $offset = 0;

    /**
     * The sorting order direction.
     * 
     * @var string
     */
    public $order = 'DESC';

    /**
     * The mailbox name.
     * 
     * @var string
     */
    public $mailbox = 'INBOX';

    /**
     * Defines whether the email should be auto-marked as read.
     *
     * @var boolean
     */
    public $mark_as_read = false;

    /**
     * Defines the attachment directory on disk.
     *
     * @var string
     */
    public $attachment_dir = null;

    /**
     * Sets the IMAP Reader
     * 
     * @param string $hostname       The IMAP host name.
     * @param string $user_name      The IMAP user name.
     * @param string $password       The IMAP password.
     * @param mixed  $attachment_dir The directory path to store attachments or false to turn off saving attachments.
     * @param bool   $mark_as_read   Whether we should mark as read.
     * 
     */
    public function __construct($hostname, $user_name, $password, $attachment_dir = false, $mark_as_read = true, $encoding = 'UTF-8')
    {
        $this->hostname = $hostname;

        $this->user_name = $user_name;

        $this->password = $password;

        $this->encoding = $encoding ?? 'UTF-8';

        $this->retry = 0;

        $this->mark_as_read = $mark_as_read;

        $this->save_attachments = false;

        if ($attachment_dir) {
            if (!is_dir($attachment_dir)) {
                throw new Exception(
                    'ERROR: Directory "' . $attachment_dir . '" could not be found.'
                );
            }

            if (!is_writable($attachment_dir)) {
                throw new Exception(
                    'ERROR: Directory "' . $attachment_dir . '" is not writable.'
                );
            }

            $this->save_attachments = true;
            $this->attachment_dir = $attachment_dir;
        }
    }

    /**
     * Recognise an IMAP connection, keeping the existing public method name.
     *
     * @param mixed $imap
     *
     * @return boolean
     */
    public function isImapResource($imap)
    {
        return $imap instanceof \IMAP\Connection
            || (is_resource($imap) && 'imap' === \get_resource_type($imap));
    }

    /**
     * Fetch the active IMAP stream. If no stream is active, try a connection.
     * 
     * @param boolean $reconnect Whether to reconnect connection.
     * 
     * @return \IMAP\Connection|resource
     */
    public function stream($reconnect = false)
    {
        if ($reconnect) {
            $this->closeConnection();
        }

        if ($this->isImapResource($this->imap)) {
            try {
                if (imap_ping($this->imap)) {
                    return $this->imap;
                }
            } catch (\ValueError $exception) {
                // The public connection may have been closed outside Reader.
                if ($exception->getMessage() !== 'IMAP\\Connection is already closed') {
                    throw $exception;
                }
            }

            $this->close();
        }

        $this->imap = $this->connect();

        return $this->imap;
    }

    /**
     * Connect to an IMAP stream.
     * 
     * @return \IMAP\Connection|resource
     */
    public function connect()
    {
        if (!extension_loaded('imap')) {
            throw new Exception(
                'ERROR: The PHP IMAP extension (ext-imap) is required. '
                . 'On PHP 8.4 and later, install a compatible IMAP extension separately (PECL or your hosting provider).'
            );
        }

        $stream = imap_open(
            $this->hostname . $this->mailbox,
            $this->user_name,
            $this->password,
            0,
            $this->retry
        );

        if (!$stream) {
            $last_error = imap_last_error();

            imap_errors();

            throw new Exception('ERROR: Could Not Connect (' . $last_error . ')');
        }

        return $stream;
    }

    /**
     * Close the current IMAP stream.
     * 
     * @return Reader
     */
    public function close()
    {
        return $this->closeConnection(true);
    }

    /**
     * Release a connection. Reconnects and folder switches do not expunge.
     *
     * @param bool $expunge Whether to permanently remove messages marked deleted.
     * @return Reader
     */
    protected function closeConnection($expunge = false)
    {
        $stream = $this->imap;
        $this->imap = null;

        if ($this->isImapResource($stream)) {
            try {
                imap_close($stream, $expunge ? CL_EXPUNGE : 0);
            } catch (\ValueError $exception) {
                // Closing an already closed IMAP\\Connection must be harmless.
                if ($exception->getMessage() !== 'IMAP\\Connection is already closed') {
                    throw $exception;
                }
            }
        }

        return $this;
    }

    /**
     * Resets the reader to be able to connect to another folder.
     *
     * @return Reader
     */
    public function reset()
    {
        $this->close();

        $this->emails = [];
        $this->email_index = [];

        return $this;
    }

    /**
     * Close connection on destruct.
     * 
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * The number of emails found.
     * 
     * @return int
     */
    public function count()
    {
        return imap_num_msg($this->stream());
    }

    /**
     * Get the last error.
     * 
     * @return string The error message.
     */
    public function getError()
    {
        return imap_last_error();
    }

    /**
     * Alias for doesMailboxExist - Returns true/false based on if the specified folder/mailbox exists on the IMAP stream.
     *
     * @param string $folder_name
     *
     * @return boolean.
     */
    public function doesFolderExist($folder_name = null)
    {
        return $this->doesMailboxExist($folder_name);
    }

    /**
     * Returns true/false based on if the specified folder/mailbox exists on the IMAP stream.
     *
     * @param string $mailbox
     *
     * @return boolean
     */
    public function doesMailboxExist($mailbox)
    {
        if (!$mailbox) {
            return false;
        }

        $mailboxes = imap_list($this->stream(), $this->hostname, "*");

        return is_array($mailboxes)
            && in_array($this->hostname . $mailbox, $mailboxes, true);
    }

    /**
     * Alias retained for the spelling used in the original documentation.
     *
     * @param string $mailbox
     * @return boolean
     */
    public function doesMailboxExists($mailbox)
    {
        return $this->doesMailboxExist($mailbox);
    }

    /**
     * Alias retained for the spelling used in the original documentation.
     *
     * @param string $folder
     * @return boolean
     */
    public function doesFolderExists($folder)
    {
        return $this->doesFolderExist($folder);
    }

    /**
     * Returns an array of mailboxes.
     *
     * @return array
     */
    public function listMailboxes()
    {
        $mailboxes = imap_list($this->stream(), $this->hostname, "*");

        return $mailboxes ?: [];
    }

    /**
     * Alias for listMailboxes. Returns an array of mailboxes.
     *
     * @return array
     */
    public function listFolders()
    {
        return $this->listMailboxes();
    }

    /**
     * Create a new folder/mailbox on the IMAP stream.
     *
     * @param string $folder_name
     *
     * @return boolean
     */
    public function makeFolder($folder_name = null)
    {
        if (!$folder_name) {
            return false;
        }

        if ($this->doesFolderExist($folder_name)) {
            return false;
        }

        return imap_createmailbox($this->stream(), imap_utf7_encode($this->hostname . $folder_name));
    }

    /**
     * Alias for makeFolder. Creates a new folder/mailbox on the IMAP stream.
     *
     * @param string $folder_name
     *
     * @return boolean
     */
    public function createFolder($folder_name)
    {
        return $this->makeFolder($folder_name);
    }

    /**
     * Alias for makeFolder. Create a new folder/mailbox on the IMAP stream.
     *
     * @param string $mailbox
     *
     * @return boolean
     */
    public function createMailbox($mailbox = null)
    {
        return $this->makeFolder($mailbox);
    }

    /**
     * Alias for makeFolder. Create a new folder/mailbox on the IMAP stream.
     *
     * @param string $mailbox
     *
     * @return boolean
     */
    public function makeMailbox($mailbox = null)
    {
        return $this->makeFolder($mailbox);
    }

    /**
     * Delete an email by given email id.
     * 
     * @param int $email_id The id of the email to delete.
     * 
     * @return boolean
     */
    public function deleteEmail($email_id)
    {
        return imap_delete($this->stream(), (string) $email_id, FT_UID);
    }

    /**
     * Expunge all emails that are marked for deletion on the connected inbox.
     *
     * @return boolean
     */
    public function expunge()
    {
        return imap_expunge($this->stream());
    }

    /**
     * Mark an email as read by given email id.
     * 
     * @param int $email_id The id of the email to mark as read.
     * 
     * @return boolean
     */
    public function markAsRead($email_id)
    {
        return imap_setflag_full($this->stream(), (string) $email_id, '\\Seen', ST_UID);
    }

    /**
     * Move mail to specific folder
     * @param  int $email_id The id of the email to move.
     * @param  string $folder Destination folder
     * @return boolean       The result of the action.
     */
    public function moveEmailToFolder($email_id, $folder)
    {
        if ($this->mailbox == $folder) {
            return false;
        }

        return imap_mail_move($this->stream(), (string) $email_id, $folder, CP_UID);
    }

    /**
     * Get the list of emails from the last get call.
     * 
     * @return array An array of returned emails.
     */
    public function emails()
    {
        return $this->emails;
    }

    /**
     * Get the first email from the list of returned emails. This is a shortcut for $this->emails[0];
     * 
     * @return Email The email.
     */
    public function email()
    {
        return $this->emails && isset($this->emails[0])
            ? $this->emails[0] : null;
    }

    /**
     * Get the email based on the given id.
     * 
     * @param int $id The Email Id.
     * 
     * @return Reader
     */
    public function id($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * Add 'all' to the mode selection. 
     * This allows for matching 'all' emails.
     * 
     * @return Reader
     */
    public function all()
    {
        $this->modes[] = 'ALL';

        return $this;
    }

    /**
     * Add 'flagged' to the mode selection. 
     * This allows for matching 'flagged' emails.
     * 
     * @return Reader
     */
    public function flagged()
    {
        $this->modes[] = 'FLAGGED';

        return $this;
    }

    /**
     * Add 'unanswered' to the mode selection. 
     * This allows for matching 'unanswered' emails.
     * 
     * @return Reader
     */
    public function unanswered()
    {
        $this->modes[] = 'UNANSWERED';

        return $this;
    }

    /**
     * Add 'deleted' to the mode selection. 
     * This allows for matching 'deleted' emails.
     * 
     * @return Reader
     */
    public function deleted()
    {
        $this->modes[] = 'DELETED';

        return $this;
    }

    /**
     * Add 'unseen' to the mode selection. 
     * This allows for matching 'unseen' emails.
     * 
     * @return Reader
     */
    public function unseen()
    {
        $this->modes[] = 'UNSEEN';

        return $this;
    }

    /**
     * An alias of unseen. See unseen().
     *
     * @return void
     */
    public function unread()
    {
        return $this->unseen();
    }

    /**
     * Add 'from' to the mode selection. 
     * This allows for matching emails 'from' the given email address.
     * 
     * @param string $from The sender email address.
     * 
     * @return Reader
     */
    public function from($from)
    {
        $this->modes[] = 'FROM ' . $this->formatSearchValue($from);

        return $this;
    }

    /**
     * Add the body search to the mode selection. 
     * This allows for searching for a string within a body
     * 
     * @param string $string The search keywords.
     * 
     * @return Reader
     */
    public function searchBody($string)
    {
        $string = (string) ($string ?? '');
        if ($string !== '') {
            $this->modes[] = 'BODY ' . $this->formatSearchValue($string);
        }

        return $this;
    }

    /**
     * Add the subject search to the mode selection. 
     * This allows for searching for a string within a subject line.
     * 
     * @param string $string The string to search for.
     * 
     * @return Reader
     */
    public function searchSubject($string)
    {
        $string = (string) ($string ?? '');
        if ($string !== '') {
            $this->modes[] = 'SUBJECT ' . $this->formatSearchValue($string);
        }

        return $this;
    }

    /**
     * Match an unfolded header value using a case-insensitive substring.
     * Repeated calls are combined with AND; an empty value checks for presence.
     *
     * @param string $name  The header field name.
     * @param string $value The substring to find.
     *
     * @return Reader
     */
    public function searchHeader($name, $value)
    {
        if (!is_string($name)
            || preg_match('/\A[\x21-\x39\x3b-\x7e]+\z/', $name) !== 1) {
            throw new \InvalidArgumentException('Invalid email header field name.');
        }

        if (!is_string($value) || strpbrk($value, "\r\n\0") !== false) {
            throw new \InvalidArgumentException('A header search value must be a string without CR, LF or NUL characters.');
        }

        $this->header_filters[] = array(strtolower($name), $value);

        return $this;
    }

    /**
     * Set one OR group of native IMAP search criteria.
     * Existing filters are applied to every alternative; another call replaces
     * the previous group. OR and HEADER operators are not supported by c-client.
     * Escaped quotes and backslashes in quoted values are normalized before use.
     *
     * @param array $criteria Nonempty native IMAP2 search strings.
     *
     * @return Reader
     */
    public function anyOf(array $criteria)
    {
        if (!$criteria) {
            throw new \InvalidArgumentException('At least one search alternative is required.');
        }

        $alternatives = array();
        foreach ($criteria as $criterion) {
            $alternatives[] = $this->validateSearchAlternative($criterion);
        }

        $this->any_of_criteria = $alternatives;

        return $this;
    }

    /**
     * Validate the IMAP2 search syntax accepted by the native extension.
     *
     * @param string $criterion
     *
     * @return string
     */
    protected function validateSearchAlternative($criterion)
    {
        if (!is_string($criterion)
            || trim($criterion) === ''
            || strpbrk($criterion, "\r\n\0") !== false) {
            throw new \InvalidArgumentException('Each search alternative must be a nonempty string without CR, LF or NUL characters.');
        }

        $criterion = trim($criterion);
        preg_match_all('/"(?:[^"\\\\]|\\\\.)*"|[^\s"]+/', $criterion, $matches, PREG_OFFSET_CAPTURE);

        $tokens = array();
        $end = 0;
        foreach ($matches[0] as $match) {
            $gap = substr($criterion, $end, $match[1] - $end);
            if (trim($gap) !== '' || ($tokens && $gap === '')) {
                throw new \InvalidArgumentException('Invalid quoted string in IMAP search alternative.');
            }
            $tokens[] = $match[0];
            $end = $match[1] + strlen($match[0]);
        }

        if (!$tokens || trim(substr($criterion, $end)) !== '') {
            throw new \InvalidArgumentException('Invalid quoted string in IMAP search alternative.');
        }

        $flags = array('ALL', 'ANSWERED', 'DELETED', 'FLAGGED', 'NEW', 'OLD', 'RECENT', 'SEEN', 'UNANSWERED', 'UNDELETED', 'UNFLAGGED', 'UNSEEN');
        $with_value = array('BCC', 'BEFORE', 'BODY', 'CC', 'FROM', 'KEYWORD', 'ON', 'SINCE', 'SUBJECT', 'TEXT', 'TO', 'UNKEYWORD');

        for ($index = 0; $index < count($tokens); $index++) {
            $operator = strtoupper($tokens[$index]);
            if (in_array($operator, $flags, true)) {
                continue;
            }
            if (in_array($operator, $with_value, true) && isset($tokens[$index + 1])) {
                $index++;
                $value = $tokens[$index];
                if ($value[0] === '"') {
                    $value = substr($value, 1, -1);
                    $value = preg_replace_callback('/\\\\(["\\\\])/', static function ($match) {
                        return $match[1];
                    }, $value);
                    $tokens[$index] = $this->formatSearchValue($value);
                } elseif ($value[0] === '{' || strpos($value, '\\') !== false) {
                    $tokens[$index] = $this->formatSearchValue($value);
                }
                continue;
            }

            throw new \InvalidArgumentException(
                'Unsupported IMAP search alternative. Use native IMAP2 criteria, anyOf() for OR, and searchHeader() for header fields.'
            );
        }

        return implode(' ', $tokens);
    }

    /**
     * Encode a search value for c-client's IMAP2 criteria parser.
     * Its quoted strings do not process backslash escapes, so values containing
     * quotes or backslashes use a byte-counted literal instead.
     *
     * @param string $value
     * @return string
     */
    protected function formatSearchValue($value)
    {
        $value = (string) ($value ?? '');
        if (strpbrk($value, "\r\n\0") !== false) {
            throw new \InvalidArgumentException('A search value must not contain CR, LF or NUL characters.');
        }

        if (strpbrk($value, "\"\\") !== false) {
            return '{' . strlen($value) . "}\r\n" . $value;
        }

        return '"' . $value . '"';
    }

    /**
     * Select the charset sent with searches without changing output encoding.
     * Null omits CHARSET. The server determines which named charsets it accepts.
     *
     * @param string|null $charset
     * @return Reader
     */
    public function searchCharset($charset)
    {
        if ($charset !== null
            && (!is_string($charset)
                || preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]*\z/', $charset) !== 1)) {
            throw new \InvalidArgumentException('A search charset must be a charset name or null.');
        }

        $this->search_charset = $charset;
        $this->search_charset_set = true;

        return $this;
    }

    /**
     * Run a native search with the configured charset, or omit it entirely.
     *
     * @param \IMAP\Connection|resource $stream
     * @param string $criteria
     * @param int $flags
     * @return array|false
     */
    protected function searchNative($stream, $criteria, $flags)
    {
        $charset = $this->search_charset_set ? $this->search_charset : $this->encoding;
        if ($charset === null) {
            return imap_search($stream, $criteria, $flags);
        }

        return imap_search($stream, $criteria, $flags, $charset);
    }

    /**
     * Clear search filters while keeping connection settings and loaded emails.
     *
     * @return Reader
     */
    public function clearFilters()
    {
        $this->id = 0;
        $this->modes = array();
        $this->header_filters = array();
        $this->any_of_criteria = array();

        return $this;
    }

    /**
     * Add the recent flag to the mode selection. 
     * This allows for matching recent emails.
     * 
     * @return Reader
     */
    public function recent()
    {
        $this->modes[] = 'RECENT';

        return $this;
    }

    /**
     * Add the unflagged flag to the mode selection. 
     * This allows for matching unflagged emails.
     * 
     * @return Reader
     */
    public function unflagged()
    {
        $this->modes[] = 'UNFLAGGED';

        return $this;
    }

    /**
     * Add the seen flag to the mode selection. 
     * This allows for matching seen emails.
     * 
     * @return Reader
     */
    public function seen()
    {
        $this->modes[] = 'SEEN';

        return $this;
    }

    /**
     * Alias of seen(). See Seen().
     *
     * @return Reader
     */
    public function read()
    {
        return $this->seen();
    }

    /**
     * Add the new flag to the mode selection. This allows for matching new emails.
     * 
     * @return Reader
     */
    public function newMessages()
    {
        $this->modes[] = 'NEW';

        return $this;
    }

    /**
     * Add the old flag to the mode selection. 
     * This allows for matching old emails.
     * 
     * @return Reader
     */
    public function oldMessages()
    {
        $this->modes[] = 'OLD';

        return $this;
    }

    /**
     * Add the keyword flag to the mode selection. 
     * This allows for matching emails with the given keyword.
     * 
     * @param string $keyword The keyword to search for.
     * 
     * @return Reader
     */
    public function keyword($keyword)
    {
        $this->modes[] = 'KEYWORD ' . $this->formatSearchValue($keyword);

        return $this;
    }

    /**
     * Add the unkeyword flag to the mode selection. 
     * This allows for matching emails without the given keyword.
     * 
     * @param string $keyword The keyword to avoid.
     * 
     * @return Reader
     */
    public function unkeyword($keyword)
    {
        $this->modes[] = 'UNKEYWORD ' . $this->formatSearchValue($keyword);

        return $this;
    }

    /**
     * Add the before date flag to the mode selection. 
     * This allows for matching emails received before the given date.
     * 
     * @param string $date The date to match.
     * 
     * @return Reader
     */
    public function beforeDate($date)
    {
        $date = $this->formatSearchDate($date);

        $this->modes[] = 'BEFORE "' . $date . '"';

        return $this;
    }

    /**
     * Add the since date flag to the mode selection. 
     * This allows for matching emails received since the given date.
     * 
     * @param string $date The date to match.
     * 
     * @return Reader
     */
    public function sinceDate($date)
    {
        $date = $this->formatSearchDate($date);

        $this->modes[] = 'SINCE "' . $date . '"';

        return $this;
    }

    /**
     * Add the sent to flag to the mode selection. 
     * This allows for matching emails sent to the given email address string.
     * 
     * @param string $to The email address to match.
     * 
     * @return Reader
     */
    public function sentTo($to)
    {
        $this->modes[] = 'TO ' . $this->formatSearchValue($to);

        return $this;
    }

    /**
     * Add the BCC flag to the mode selection. 
     * This allows for matching emails with the string present in the BCC field.
     * 
     * @param string $to The email address to match.
     * 
     * @return Reader
     */
    public function searchBCC($to)
    {
        $this->modes[] = 'BCC ' . $this->formatSearchValue($to);

        return $this;
    }

    /**
     * Add the CC flag to the mode selection. 
     * This allows for matching emails with the string present in the CC field.
     * 
     * @param string $to The email address to match.
     * 
     * @return Reader
     */
    public function searchCC($to)
    {
        $this->modes[] = 'CC ' . $this->formatSearchValue($to);

        return $this;
    }

    /**
     * Add the on date flag to the mode selection. 
     * This allows for matching emails received on the given date.
     * 
     * @param string $date The date to match.
     * 
     * @return Reader
     */
    public function onDate($date)
    {
        $date = $this->formatSearchDate($date);

        $this->modes[] = 'ON "' . $date . '"';

        return $this;
    }

    /**
     * Format a valid search date without silently using the Unix epoch.
     *
     * @param string $date
     * @return string
     */
    protected function formatSearchDate($date)
    {
        $timestamp = strtotime((string) ($date ?? ''));

        if ($timestamp === false) {
            throw new \InvalidArgumentException('Invalid IMAP search date.');
        }

        return date('d-M-Y', $timestamp);
    }

    /**
     * Add the text flag to the mode selection. 
     * This allows for matching emails with the given text string.
     * 
     * @param string $string The string to search for.
     * 
     * @return Reader
     */
    public function searchText($string)
    {
        $this->modes[] = 'TEXT ' . $this->formatSearchValue($string);

        return $this;
    }

    /**
     * Set the limit. 
     * This can be used with page() for pagination of emails.
     * 
     * @param int $limit The total number of emails to return.
     * 
     * @return Reader
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;

        return $this;
    }

    /**
     * Set the page number. 
     * This is used with limit() for pagination of emails.
     * 
     * @param int $page The page number.
     * 
     * @return Reader
     */
    public function page($page)
    {
        $this->page = (int) $page;

        $this->offset = ($this->page - 1) * $this->limit;

        return $this;
    }

    /**
     * Set the email fetching order to ASCending.
     * 
     * @return Reader
     */
    public function orderASC()
    {
        $this->order = 'ASC';

        return $this;
    }

    /**
     * Set the email fetching order to DESCending.
     * 
     * @return Reader
     */
    public function orderDESC()
    {
        $this->order = 'DESC';

        return $this;
    }

    /**
     * Sets the folder to retrieve emails from. Alias for mailbox().
     * 
     * @param string $folder The name of the folder. IE. INBOX.
     * 
     * @return Reader
     */
    public function folder($folder)
    {
        return $this->mailbox($folder);
    }

    /**
     * Sets the mailbox to retrieve emails from.
     * 
     * @param string $mailbox The name of the mailbox, IE. INBOX.
     * 
     * @return Reader
     */
    public function mailbox($mailbox)
    {
        if ($this->mailbox !== $mailbox) {
            $this->closeConnection();
            $this->mailbox_generation++;
        }

        $this->mailbox = $mailbox;

        return $this;
    }

    /**
     * Get a formatted list of selected modes for imap_search.
     * 
     * @return string
     */
    public function modes()
    {
        if (!$this->modes) {
            $this->modes[] = 'ALL';
        }

        return implode(' ', $this->modes);
    }

    /**
     * Return matching message UIDs without downloading message bodies.
     * Header filters fetch only headers. An explicit id() takes precedence over
     * other filters and pagination, as it does in get().
     *
     * @param bool $applyPagination Whether to apply the current page and limit.
     *
     * @return int[]
     */
    public function searchUIDs($applyPagination = true)
    {
        if ($this->id) {
            $uid = (int) $this->id;

            return $uid > 0 && imap_msgno($this->stream(), $uid) > 0
                ? array($uid) : array();
        }

        $stream = $this->stream();
        $common = $this->modes ? implode(' ', $this->modes) : 'ALL';
        $alternatives = $this->any_of_criteria ?: array('');
        $selected = array();

        foreach ($alternatives as $alternative) {
            // c-client does not accept an OR search operator. Search each
            // branch separately, then merge UIDs before filtering or paging.
            // Do not trim: the last bytes can belong to a search literal.
            $criteria = $common . ($alternative !== '' ? ' ' . $alternative : '');
            $matches = $this->searchNative($stream, $criteria, SE_UID);

            foreach ($matches ?: array() as $uid) {
                $uid = (int) $uid;
                if ($uid > 0) {
                    $selected[$uid] = $uid;
                }
            }
        }

        if ($this->header_filters) {
            foreach ($selected as $uid) {
                $headers = imap_fetchheader($stream, $uid, FT_UID);
                if ($headers === false) {
                    unset($selected[$uid]);
                    continue;
                }

                $email = (new Email())->setHeaders($headers);
                foreach ($this->header_filters as $filter) {
                    $matched = false;
                    foreach ($email->getHeaderValues($filter[0]) as $value) {
                        if ($filter[1] === '' || stripos($value, $filter[1]) !== false) {
                            $matched = true;
                            break;
                        }
                    }
                    if (!$matched) {
                        unset($selected[$uid]);
                        break;
                    }
                }
            }
        }

        $uids = array_values($selected);
        if ($this->order == 'DESC') {
            rsort($uids, SORT_NUMERIC);
        } else {
            sort($uids, SORT_NUMERIC);
        }

        $limit = (int) $this->limit;
        if ($applyPagination && $limit > 0) {
            $offset = $this->page > 0
                ? ((int) $this->page - 1) * $limit
                : (int) $this->offset;
            $uids = array_slice($uids, max(0, $offset), $limit);
        }

        return $uids;
    }

    /**
     * Count matching messages before pagination without fetching their bodies.
     *
     * @return int
     */
    public function countMatching()
    {
        return count($this->searchUIDs(false));
    }

    /**
     * Fetch native message summaries in the selected UID order.
     * This does not fetch message bodies or mark messages as read.
     *
     * @return \stdClass[]
     */
    public function getOverview()
    {
        $uids = $this->searchUIDs();
        if (!$uids) {
            return array();
        }

        $stream = $this->stream();
        $selected = array_fill_keys($uids, true);
        $summaries = array();

        foreach (array_chunk($uids, 100) as $chunk) {
            $overview = imap_fetch_overview($stream, implode(',', $chunk), FT_UID);
            foreach ($overview ?: array() as $item) {
                if (!is_object($item) || !isset($item->uid)) {
                    continue;
                }

                $uid = (int) $item->uid;
                if (!isset($selected[$uid])) {
                    continue;
                }

                $summary = (object) get_object_vars($item);
                $summary->uid = $uid;
                foreach (array('subject', 'from', 'to') as $field) {
                    if (isset($summary->{$field})) {
                        $summary->{$field} = $this->decodeMimeHeader($summary->{$field});
                    }
                }
                $summaries[$uid] = $summary;
            }
        }

        $result = array();
        foreach ($uids as $uid) {
            if (isset($summaries[$uid])) {
                $result[] = $summaries[$uid];
            }
        }

        return $result;
    }

    /**
     * Yield one email at a time, keyed by UID, without filling emails().
     * UIDs are selected once so marking or deleting messages cannot shift the
     * remaining selection. Do not change this Reader's mailbox while iterating.
     *
     * @return \Generator<int, Email>
     */
    public function iterate()
    {
        $mailbox = array($this->hostname, $this->user_name, $this->mailbox, $this->mailbox_generation);
        $uids = $this->searchUIDs();

        foreach ($uids as $uid) {
            $this->assertIteratorMailbox($mailbox);

            $email = $this->getEmail($uid);
            if ($email === null) {
                continue;
            }

            if ($this->mark_as_read) {
                $this->markAsRead($email->id());
            }

            yield $uid => $email;

            $this->assertIteratorMailbox($mailbox);
        }
    }

    /**
     * Prevent a running iterator from applying selected UIDs to another mailbox.
     *
     * @param array $mailbox
     *
     * @return void
     */
    protected function assertIteratorMailbox(array $mailbox)
    {
        if ($mailbox !== array($this->hostname, $this->user_name, $this->mailbox, $this->mailbox_generation)) {
            throw new \LogicException('The Reader mailbox must not change while iterating messages.');
        }
    }

    /**
     * Fetch emails based on the previously set parameters.
     * 
     * @return array
     */
    public function get()
    {
        $this->stream();

        if ($this->id) {
            $this->emails = array();

            $email = $this->getEmail($this->id);
            if ($email !== null) {
                $this->emails[] = $email;
            }

            return $this->emails;
        }

        if ($this->header_filters || $this->any_of_criteria) {
            $uids = $this->searchUIDs();
            $this->emails = array();
            $this->email_index = array();

            foreach ($uids as $uid) {
                $email = $this->getEmail($uid);
                if ($email === null) {
                    continue;
                }

                $this->emails[] = $email;
                $this->email_index[] = $email->msgno();

                if ($this->mark_as_read) {
                    $this->markAsRead($email->id());
                }
            }

            return $this->emails;
        }

        $this->emails = array();

        $this->email_index = $this->searchNative(
            $this->stream(),
            $this->modes(),
            0
        );

        if (!$this->limit) {
            $this->limit = isset($this->email_index) && is_array($this->email_index) ? count($this->email_index) : 0;
        }

        if ($this->email_index) {
            if ($this->order == 'DESC') {
                rsort($this->email_index);
            } else {
                sort($this->email_index);
            }

            if ($this->limit || ($this->limit && $this->offset)) {
                $this->email_index = array_slice(
                    $this->email_index,
                    $this->offset,
                    $this->limit
                );
            }

            $this->emails = array();

            foreach ($this->email_index as $id) {
                $email = $this->getEmailByMessageSequence($id);

                if ($email === null) {
                    continue;
                }

                $this->emails[] = $email;

                if ($this->mark_as_read) {
                    $this->markAsRead($email->id());
                }
            }
        }

        return $this->emails;
    }

    /**
     * Fetches an email by its UID.
     *
     * @param integer $uid UID Number
     *
     * @return Email|null
     */
    public function getEmailByUID($uid)
    {
        return $this->getEmail($uid);
    }

    /**
     * Fetches an email by its message sequence id
     *
     * @param integer $id ID
     *
     * @return Email|null
     */
    public function getEmailByMessageSequence($id)
    {
        $uid = imap_uid($this->stream(), $id);

        return $uid ? $this->getEmail($uid) : null;
    }

    /**
     * Fetch an email by id.
     * 
     * @param integer $uid The message UID.
     * 
     * @return Email|null
     */
    public function getEmail($uid)
    {
        $uid = (int) $uid;
        if ($uid <= 0) {
            return null;
        }

        $email = new Email();

        // imap_headerinfo doesn't work with the uid, so we use imap_fetchbody instead.
        //$header = imap_headerinfo($this->stream(), $uid);

        $options = ($this->mark_as_read) ? FT_UID : FT_UID | FT_PEEK;

        $header_from_body = imap_fetchbody($this->stream(), $uid, '0', $options);

        if ($header_from_body === false) {
            return null;
        }

        $header = imap_rfc822_parse_headers($header_from_body);

        if (!$header) {
            return null;
        }

        $email->setId($uid);

        $header->subject = isset($header->subject)
            ? $this->decodeMimeHeader($header->subject)
            : false;

        $email->setSubject($header->subject);

        $email->setDate(isset($header->date) ? $header->date : null);

        if (isset($header->to)) {
            foreach ($header->to as $to) {
                if (!isset($to->mailbox, $to->host)) {
                    continue;
                }

                $to_name = isset($to->personal)
                    ? $this->decodeMimeHeader($to->personal)
                    : false;
                $email->addTo($to->mailbox, $to->host, $to_name);
            }
        }

        if (isset($header->from[0]->mailbox, $header->from[0]->host)) {
            $from_name = isset($header->from[0]->personal)
                ? $this->decodeMimeHeader($header->from[0]->personal)
                : false;
            $email->setFrom(
                $header->from[0]->mailbox,
                $header->from[0]->host,
                $from_name
            );
        }

        if (isset($header->reply_to)) {
            foreach ($header->reply_to as $reply_to) {
                if (!isset($reply_to->mailbox, $reply_to->host)) {
                    continue;
                }

                $reply_to_name = isset($reply_to->personal)
                    ? $this->decodeMimeHeader($reply_to->personal)
                    : false;
                $email->addReplyTo(
                    $reply_to->mailbox,
                    $reply_to->host,
                    $reply_to_name
                );
            }
        }

        if (isset($header->cc)) {
            foreach ($header->cc as $cc) {
                if (!isset($cc->mailbox, $cc->host)) {
                    continue;
                }

                $cc_name = isset($cc->personal)
                    ? $this->decodeMimeHeader($cc->personal)
                    : false;
                $email->addCC($cc->mailbox, $cc->host, $cc_name);
            }
        }

        $raw_body = imap_fetchbody($this->stream(), $uid, '', $options);
        $email->setRawBody($raw_body === false ? '' : $raw_body);

        $body = imap_fetchstructure($this->stream(), $uid, FT_UID);

        if ($body === false) {
            return null;
        }

        if (isset($body->parts) && count($body->parts)) {
            foreach ($body->parts as $part_number => $part) {
                $this->decodePart($email, $part, $part_number + 1);
            }
        } else {
            $this->decodePart($email, $body);
        }

        $msgno = imap_msgno($this->stream(), $uid);

        if ($msgno <= 0) {
            return null;
        }

        $email->setMsgno($msgno);

        $header = imap_headerinfo($this->imap, $msgno, 20, 20);

        $email->setSize(isset($header->Size) ? $header->Size : 0);

        $email->setUdate(isset($header->udate) ? $header->udate : null);

        $recent = isset($header->Recent)
            && ($header->Recent == 'R' || $header->Recent == 'N')
            ? true
            : false;
        $email->setRecent($recent);

        $unseen = isset($header->Unseen) && $header->Unseen == 'U'
            ? true : false;
        $email->setUnseen($unseen);

        $flagged = isset($header->Flagged) && $header->Flagged == 'F'
            ? true : false;
        $email->setFlagged($flagged);

        $answered = isset($header->Answered) && $header->Answered == 'A'
            ? true : false;
        $email->setAnswered($answered);

        $deleted = isset($header->Deleted) && $header->Deleted == 'D'
            ? true : false;
        $email->setDeleted($deleted);

        $draft = isset($header->Draft) && $header->Draft == 'X'
            ? true : false;
        $email->setDraft($draft);

        $headers = imap_fetchheader($this->stream(), $email->msgno());
        $email->setHeaders($headers);

        return $email;
    }

    /**
     * List attachment metadata without downloading any message content.
     * The bytes field is the encoded MIME part size reported by the server.
     *
     * @param int $uid The message UID in the selected mailbox.
     * @return array[] Part, name, MIME type, disposition, Content-ID and bytes.
     */
    public function getAttachmentInfo($uid)
    {
        $uid = (int) $uid;
        if ($uid <= 0) {
            return array();
        }

        $structure = imap_fetchstructure($this->stream(), $uid, FT_UID);
        if (!is_object($structure)) {
            return array();
        }

        return $this->collectAttachmentInfo($structure);
    }

    /**
     * Walk the MIME structure without fetching body parts.
     */
    protected function collectAttachmentInfo($part, $part_number = '')
    {
        if (!is_object($part)) {
            return array();
        }

        $info = $this->describeAttachmentPart($part, $part_number);
        if ($info !== null) {
            return array($info);
        }

        $attachments = array();
        if (!empty($part->parts)) {
            foreach ($part->parts as $number => $child) {
                $child_number = $part_number === ''
                    ? (string) ($number + 1) : $part_number . '.' . ($number + 1);
                if (($part->type ?? null) == 2 && strtoupper($part->subtype ?? '') === 'RFC822') {
                    $child_number = $part_number;
                }
                foreach ($this->collectAttachmentInfo($child, $child_number) as $attachment) {
                    $attachments[] = $attachment;
                }
            }
        }

        return $attachments;
    }

    /**
     * Read Content-Type and Content-Disposition parameters.
     */
    protected function getPartParameters($part)
    {
        $params = array();
        foreach (array('parameters', 'dparameters') as $property) {
            if (empty($part->$property)) {
                continue;
            }
            foreach ($part->$property as $param) {
                if (isset($param->attribute, $param->value)) {
                    $params[strtolower((string) $param->attribute)] = (string) $param->value;
                }
            }
        }

        return $params;
    }

    /**
     * Decode filename/name parameters, including RFC 2231 continuations.
     */
    protected function decodeAttachmentName($params)
    {
        foreach (array('filename', 'name') as $attribute) {
            $segments = array();
            foreach ($params as $key => $value) {
                if (preg_match('/^' . $attribute . '\\*(0|[1-9][0-9]*)(\\*)?$/D', $key, $match)) {
                    $segments[(int) $match[1]] = array($value, !empty($match[2]));
                }
            }

            if (isset($segments[0])) {
                ksort($segments, SORT_NUMERIC);
                $expected = 0;
                $continuous = true;
                foreach ($segments as $index => $segment) {
                    if ($index !== $expected++) {
                        $continuous = false;
                        break;
                    }
                }
                if ($continuous) {
                    $name = $this->decodeAttachmentNameSegments($segments);
                    if ($name !== null && $name !== '') {
                        return $name;
                    }
                }
            }

            if (isset($params[$attribute . '*'])) {
                $name = $this->decodeAttachmentNameSegments(array(array($params[$attribute . '*'], true)));
                if ($name !== null && $name !== '') {
                    return $name;
                }
            }

            if (isset($params[$attribute]) && $params[$attribute] !== '') {
                $name = $this->decodeMimeHeader($params[$attribute]);
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * Join decoded octets before converting the complete filename's charset.
     */
    protected function decodeAttachmentNameSegments($segments)
    {
        $charset = '';
        $decoded = '';
        $encoded = '';

        foreach ($segments as $index => $segment) {
            list($value, $is_encoded) = $segment;
            if ($index === 0 && $is_encoded) {
                if (!preg_match("/^([^']*)'[^']*'(.*)$/s", $value, $match)) {
                    return null;
                }
                $charset = $match[1];
                $value = $match[2];
            }

            if ($is_encoded) {
                $encoded .= $value;
            } else {
                if (preg_match('/%(?![0-9a-f]{2})/i', $encoded)) {
                    return null;
                }
                $decoded .= rawurldecode($encoded) . $value;
                $encoded = '';
            }
        }

        if (preg_match('/%(?![0-9a-f]{2})/i', $encoded)) {
            return null;
        }
        $decoded .= rawurldecode($encoded);

        return $charset !== ''
            ? $this->convertEncoding($decoded, $charset)
            : $this->decodeMimeHeader($decoded);
    }

    /**
     * Use the same attachment classification and filenames for both APIs.
     */
    protected function describeAttachmentPart($part, $part_number, $params = null)
    {
        $params = $params ?? $this->getPartParameters($part);
        $name = $this->decodeAttachmentName($params);
        $disposition = strtolower((string) ($part->disposition ?? ''));
        $type = (int) ($part->type ?? 0);
        $subtype = strtolower((string) ($part->subtype ?? ''));
        $content_id = isset($part->id) ? trim((string) $part->id, " <>") : null;
        $has_content_id = $content_id !== null && $content_id !== '';
        $is_text_body = $type === 0 && in_array($subtype, array('plain', 'html'), true);

        if ($name === null && $disposition !== 'attachment'
            && !($disposition === 'inline' && !$is_text_body)
            && !($has_content_id && $type !== 0 && $type !== 1)) {
            return null;
        }

        $types = array('text', 'multipart', 'message', 'application', 'audio', 'image', 'video', 'other');
        $mime = isset($types[$type]) && $type !== 7 && $subtype !== ''
            ? $types[$type] . '/' . $subtype : 'application/octet-stream';
        $part_number = $part_number !== '' && $part_number !== false ? (string) $part_number : '1';
        if ($name === null) {
            $name = 'attachment-' . $part_number;
            if ($mime === 'message/rfc822') {
                $name .= '.eml';
            } elseif (in_array($subtype, array('png', 'gif', 'jpeg', 'jpg', 'pdf', 'txt'), true)) {
                $name .= '.' . $subtype;
            }
        }

        return array(
            'part' => $part_number,
            'name' => $name,
            'mime' => $mime,
            'disposition' => $disposition === 'inline' || ($has_content_id && $disposition !== 'attachment')
                ? 'inline' : 'attachment',
            'content_id' => $content_id !== '' ? $content_id : null,
            'bytes' => isset($part->bytes) ? (int) $part->bytes : null,
        );
    }

    /**
     * Decode an email part.
     * 
     * @param Email   $email       The email object to update.
     * @param object  $part        The part data to decode.
     * @param string|int|false $part_number The part number.
     * 
     * @return string
     */
    public function decodePart(Email $email, $part, $part_number = false)
    {
        if (!is_object($part)) {
            return '';
        }

        $options = ($this->mark_as_read) ? FT_UID : FT_UID | FT_PEEK;

        if ($part_number) {
            $data = imap_fetchbody(
                $this->stream(),
                $email->id(),
                (string) $part_number,
                $options
            );
        } else {
            $data = imap_body($this->stream(), $email->id(), $options);
        }

        if ($data === false) {
            return '';
        }

        // 7BIT, 8BIT and BINARY are already raw bytes. Only transfer
        // encodings BASE64 and QUOTED-PRINTABLE need decoding here.
        switch ($part->encoding ?? 0) {
            case 3:
                $data = imap_base64($data);
                break;

            case 4:
                $data = quoted_printable_decode($data);
                break;
        }

        if ($data === false) {
            return '';
        }

        $params = $this->getPartParameters($part);
        $attachment_info = $this->describeAttachmentPart($part, $part_number, $params);

        // is this part an attachment
        $attachment_id = false;
        $is_attachment = $attachment_info !== null;

        if ($is_attachment) {
            $attachment_type = $attachment_info['disposition'];

            if ($attachment_type == 'inline') {
                $is_inline_attachment = true;
                $attachment_id = $attachment_info['content_id'] ?? rand();
            } else {
                $is_inline_attachment = false;
                $attachment_id = rand();
            }
        }

        // if there is an attachment
        if ($is_attachment) {
            $file_name = $attachment_info['name'];

            if ($file_name !== '') {
                $attachment = new EmailAttachment();
                $attachment->setId($attachment_id);
                $attachment->setOriginalName($file_name);
                $attachment->setName($attachment_id . '-' . $file_name);
                $attachment->setMime($attachment_info['mime']);

                if ($is_inline_attachment) {
                    $attachment->setType('inline');
                } else {
                    $attachment->setType('attachment');
                }

                if ($this->save_attachments) {
                    $attachment->setAttachmentData($data);
                    $attachment->save($this->attachment_dir, $attachment->name());
                    $attachment->setAttachmentData(null);
                } else {
                    $attachment->setAttachmentData($data);
                }

                $email->addAttachment($attachment);
            }
        } else {
            // if the charset is set, convert to our encoding UTF-8
            if (!empty($params['charset'])) {
                $data = $this->convertEncoding($data, $params['charset']);
            }

            // part->type = 0 is TEXT or TYPETEXT
            if (isset($part->type)) {
                if ($part->type == 0) {
                    // subpart is either plain text or html version
                    if (strtoupper($part->subtype ?? '') == 'PLAIN') {
                        $email->setPlain($data);
                    } else {
                        $email->setHTML($data);
                    }

                    // part->type = 2 is MESSAGE
                } elseif ($part->type == 2) {
                    $email->setPlain($data);
                }
            }
        }

        // rerun for additional parts
        if (!$is_attachment && !empty($part->parts)) {
            foreach ($part->parts as $subpart_number => $subpart) {
                if (($part->type ?? null) == 2 && ($part->subtype ?? '') == 'RFC822') {
                    $this->decodePart($email, $subpart, $part_number);
                } else {
                    $this->decodePart($email, $subpart, $part_number . '.' . ($subpart_number + 1));
                }
            }
        }

        return trim($data);
    }

    /**
     * Decode Mime Header.
     * 
     * @param string $encoded_header The encoded header string.
     * 
     * @return string
     */
    public function decodeMimeHeader($encoded_header)
    {
        $encoded_header = (string) ($encoded_header ?? '');
        $decoded_header = '';

        $elements = imap_mime_header_decode($encoded_header);

        if (!is_array($elements)) {
            return $encoded_header;
        }

        foreach ($elements as $element) {
            $text = (string) ($element->text ?? '');
            $charset = $element->charset ?? 'default';

            if (strtolower($charset) === 'default') {
                // Unencoded headers may already contain UTF-8. Retain the
                // legacy Latin-1 fallback only for bytes that are not UTF-8.
                $charset = preg_match('//u', $text) === 1 ? 'UTF-8' : 'ISO-8859-1';
            }

            $decoded_header .= $this->convertEncoding($text, $charset);
        }

        return $decoded_header;
    }

    /**
     * Convert a string encoding to the encoding set.
     * 
     * @param string $string                The string to re-encode.
     * @param string $current_encoding_type The encoding type of the original string.
     * 
     * @return string
     */
    public function convertEncoding($string, $current_encoding_type)
    {
        if (!$string) {
            return $string;
        }

        $current_encoding_type = trim((string) ($current_encoding_type ?? ''));
        $target_encoding = (string) ($this->encoding ?? 'UTF-8');

        if ($current_encoding_type === '' || strcasecmp($current_encoding_type, $target_encoding) === 0) {
            return $string;
        }

        // These are transfer encodings, not character sets. mbstring has
        // deprecated accepting them as encodings since PHP 8.2.
        $transfer_encodings = array('BASE64', 'UUENCODE', 'HTML-ENTITIES', 'HTML', 'QUOTED-PRINTABLE', 'QPRINT');
        if (in_array(strtoupper($current_encoding_type), $transfer_encodings, true)
            || in_array(strtoupper($target_encoding), $transfer_encodings, true)) {
            return $string;
        }

        $converted_string = false;
        if (extension_loaded('mbstring')) {
            try {
                $converted_string = mb_convert_encoding($string, $target_encoding, $current_encoding_type);
            } catch (\ValueError $exception) {
                // Unsupported charset labels must not abort reading a mail.
            }
        }

        if ($converted_string === false && function_exists('iconv')) {
            try {
                $converted_string = @iconv($current_encoding_type, $target_encoding . '//IGNORE', $string);
            } catch (\ValueError $exception) {
                // Preserve the original data if the charset is unsupported.
            }
        }

        return $converted_string === false ? $string : $converted_string;
    }
}

// Keep the original namespace available for existing applications.
if (!\class_exists('benhall14\\phpImapReader\\Reader', false)) {
    \class_alias(Reader::class, 'benhall14\\phpImapReader\\Reader', false);
}
