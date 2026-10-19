<?php

namespace linqixit\phpImapReader;

/**
 * IMAP Email Attachment Class.
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
class EmailAttachment
{
    /**
     * The attachment id
     * 
     * @var int
     */
    public $id;

    /**
     * The attachment name.
     * 
     * @var string
     */
    public $name;

    /**
     * The decoded filename supplied by the sender, without the attachment id.
     *
     * @var string|null
     */
    public $original_name;

    /**
     * The attachment file path.
     * 
     * @var string
     */
    public $file_path;

    /**
     * The attachment type.
     * 
     * @var string
     */
    public $type;

    /**
     * The attachment mime type.
     * 
     * @var string
     */
    public $mime;

    /**
     * The attachment data.
     *
     * @var string
     */
    public $attachment_data;

    /**
     * Sets the attachments id.
     * 
     * @param int $id The attachment id.
     * 
     * @return EmailAttachment
     */
    public function setID($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Sets the attachments name.
     * 
     * @param string $name The attachment name.
     * 
     * @return EmailAttachment
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the decoded filename supplied by the sender.
     *
     * @return EmailAttachment
     */
    public function setOriginalName($name)
    {
        $this->original_name = $name;

        return $this;
    }

    /**
     * Sets the attachment type.
     * 
     * @param string $type The attachment type.
     * 
     * @return EmailAttachment
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Sets the attachment mime type.
     * 
     * @param string $mime_type The attachment mime type.
     * 
     * @return EmailAttachment
     */
    public function setMime($mime_type)
    {
        $this->mime = $mime_type;

        return $this;
    }

    /**
     * Sets the attachments file path.
     * 
     * @param string $file_path The attachment file path.
     * 
     * @return EmailAttachment
     */
    public function setFilePath($file_path)
    {
        $this->file_path = $file_path;

        return $this;
    }

    /**
     * Sets the attachments data
     * 
     * @param string $data The attachment data.
     * 
     * @return EmailAttachment
     */
    public function setAttachmentData($data)
    {
        $this->attachment_data = $data;

        return $this;
    }

    /**
     * Get the attachments id.
     * 
     * @return integer
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Get the attachments name.
     * 
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Get the sender's filename, falling back to name() for older objects.
     *
     * @return string|null
     */
    public function originalName()
    {
        return $this->original_name !== null && $this->original_name !== ''
            ? $this->original_name : $this->name;
    }

    /**
     * Get the declared MIME type. This does not inspect the file content.
     *
     * @return string|null
     */
    public function mime()
    {
        return $this->mime;
    }

    /**
     * Get the lowercase extension of the sender's filename.
     *
     * @return string
     */
    public function extension()
    {
        $name = str_replace('\\', '/', (string) ($this->originalName() ?? ''));

        return strtolower(pathinfo($name, PATHINFO_EXTENSION));
    }

    /**
     * Get the decoded content size in bytes, from memory or the saved file.
     *
     * @return int
     * @throws \RuntimeException When no readable attachment content is available.
     */
    public function size()
    {
        if ($this->attachment_data !== null) {
            if (!is_string($this->attachment_data)) {
                throw new \RuntimeException('Attachment content must be a string.');
            }

            return strlen($this->attachment_data);
        }

        $path = $this->readableSourcePath();
        clearstatcache(true, $path);
        $size = @filesize($path);

        if ($size === false) {
            throw new \RuntimeException('Unable to determine the attachment size.');
        }

        return $size;
    }

    /**
     * Save a copy in an existing directory and return its absolute path.
     * Existing names receive " (1)", " (2)", etc., unless overwrite is enabled.
     * The original name and in-memory content are retained.
     *
     * @param string $directory
     * @param string|null $filename Defaults to originalName().
     * @param bool $overwrite Replace the chosen filename explicitly.
     * @return string
     * @throws \InvalidArgumentException For an invalid filename or directory.
     * @throws \RuntimeException When reading or writing fails.
     */
    public function save($directory, $filename = null, $overwrite = false)
    {
        $directory = realpath((string) $directory);
        if ($directory === false || !is_dir($directory) || !is_writable($directory)) {
            throw new \InvalidArgumentException('The attachment directory must exist and be writable.');
        }

        $filename = $this->safeFileName($filename ?? $this->originalName() ?? 'attachment');
        $prefix = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR;
        $source = null;
        $output = null;
        $created_path = null;
        $target = $prefix . $filename;

        // Open and validate the source before creating or replacing any file.
        if ($this->attachment_data === null) {
            $source = @fopen($this->readableSourcePath(), 'rb');
            if ($source === false) {
                throw new \RuntimeException('Unable to open the saved attachment.');
            }
        } elseif (!is_string($this->attachment_data)) {
            throw new \RuntimeException('Attachment content must be a string.');
        }

        try {
            if ($overwrite) {
                if (is_dir($target) || is_link($target)) {
                    throw new \RuntimeException('The attachment destination is a directory or symbolic link.');
                }

                // Stage a replacement in the same directory; never follow an
                // existing destination link or truncate the source while copying.
                $staging_path = $prefix . '.imap-' . bin2hex(random_bytes(16));
                $output = @fopen($staging_path, 'x+b');
                if ($output !== false) {
                    $created_path = $staging_path;
                }
            } else {
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                $extension = $extension !== '' ? '.' . $extension : '';
                $stem = $extension !== '' ? substr($filename, 0, -strlen($extension)) : $filename;

                for ($index = 0; $index < 10000; $index++) {
                    $target = $prefix . ($index ? $stem . ' (' . $index . ')' . $extension : $filename);
                    $output = @fopen($target, 'x+b');

                    if ($output !== false) {
                        $created_path = $target;
                        break;
                    }

                    clearstatcache(true, $target);
                    if (!file_exists($target) && !is_link($target)) {
                        throw new \RuntimeException('Unable to create the attachment file.');
                    }
                }
            }

            if ($output === false || $output === null) {
                throw new \RuntimeException('Unable to create the attachment file.');
            }

            if ($source !== null) {
                $stat = fstat($source);
                $written = @stream_copy_to_stream($source, $output);
                if ($written === false || ($stat !== false && $written !== $stat['size'])) {
                    throw new \RuntimeException('Unable to read the complete attachment file.');
                }
            } else {
                $length = strlen($this->attachment_data);
                for ($offset = 0; $offset < $length; $offset += $written) {
                    $written = @fwrite($output, substr($this->attachment_data, $offset, 8192));
                    if ($written === false || $written === 0) {
                        throw new \RuntimeException('Unable to write the attachment content.');
                    }
                }
            }

            if (!fflush($output)) {
                throw new \RuntimeException('Unable to flush the attachment file.');
            }
            fclose($output);
            $output = null;
            if ($source !== null) {
                fclose($source);
                $source = null;
            }

            if ($overwrite && !@rename($created_path, $target)) {
                throw new \RuntimeException('Unable to replace the attachment file.');
            }

            $created_path = null;
            $this->setFilePath($target);

            return $target;
        } finally {
            if (is_resource($output)) {
                fclose($output);
            }
            if (is_resource($source)) {
                fclose($source);
            }
            if ($created_path !== null) {
                @unlink($created_path);
            }
        }
    }

    /**
     * Resolve file-backed content without treating a missing file as empty data.
     */
    protected function readableSourcePath()
    {
        $path = $this->file_path;
        if (!is_string($path) || $path === '' || !is_file($path) || !is_readable($path)) {
            throw new \RuntimeException('The attachment has no readable content or saved file.');
        }

        return $path;
    }

    /**
     * Keep one portable filename while preserving the sender's display name.
     */
    protected function safeFileName($filename)
    {
        $filename = (string) $filename;
        if (strpos($filename, "\0") !== false) {
            throw new \InvalidArgumentException('Attachment filenames cannot contain null bytes.');
        }

        $filename = str_replace('\\', '/', $filename);
        $separator = strrpos($filename, '/');
        if ($separator !== false) {
            $filename = substr($filename, $separator + 1);
        }
        $filename = preg_replace('/[\x01-\x1f\x7f<>:"|?*]/', '_', $filename);
        $filename = rtrim($filename, " .");

        if ($filename === '' || $filename === '.' || $filename === '..') {
            throw new \InvalidArgumentException('An attachment filename must contain a file name.');
        }
        if (preg_match('/^(?:CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.|$)/i', $filename)) {
            $filename = '_' . $filename;
        }

        // Leave room for collision suffixes on filesystems with 255-byte names.
        if (strlen($filename) > 240) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $extension = $extension !== '' && strlen($extension) <= 32 ? '.' . $extension : '';
            $stem = $extension !== '' ? substr($filename, 0, -strlen($extension)) : $filename;
            $shortened = substr($stem, 0, 240 - strlen($extension));
            if (preg_match('//u', $stem) === 1) {
                while (preg_match('//u', $shortened) !== 1) {
                    $shortened = substr($shortened, 0, -1);
                }
            }
            $filename = $shortened . $extension;
        }

        return $filename;
    }

    /**
     * Get the attachments file path.
     * 
     * @return string
     */
    public function filePath()
    {
        return $this->file_path;
    }

    /**
     * Get the attachments content.
     * 
     * @return string
     */
    public function content()
    {
        return $this->attachment_data;
    }

    /**
     * Get the attachments type.
     * 
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Gets the inline status of the attachment.
     * 
     * @return boolean
     */
    public function isInline()
    {
        return $this->type() == 'inline' ? true : false;
    }
}

// Keep the original namespace available for existing applications.
if (!\class_exists('benhall14\\phpImapReader\\EmailAttachment', false)) {
    \class_alias(EmailAttachment::class, 'benhall14\\phpImapReader\\EmailAttachment', false);
}
