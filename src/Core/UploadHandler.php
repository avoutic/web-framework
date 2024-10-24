<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

/**
 * Class UploadHandler.
 *
 * Handles file uploads and performs various checks and operations on uploaded files.
 */
class UploadHandler
{
    private string $varName;
    private string $tmpFilename = '';
    private string $origFilename = '';
    private string $mimeType = '';

    /**
     * UploadHandler constructor.
     *
     * @param string $varName The name of the file input field
     */
    public function __construct(string $varName = 'file')
    {
        $this->varName = $varName;
    }

    /**
     * Check the uploaded file against various criteria.
     *
     * @param int                $maxSize            The maximum allowed file size in bytes
     * @param array<string>|true $whitelistMimeTypes Array of allowed MIME types or true to allow all
     *
     * @return string|true True if the file passes all checks, or an error string
     */
    public function checkUpload(int $maxSize, array|true $whitelistMimeTypes = true): string|true
    {
        $varName = $this->varName;

        if (!isset($_FILES[$varName])
            || !isset($_FILES[$varName]['size'])
            || $_FILES[$varName]['size'] == 0)
        {
            return 'no_file_present';
        }

        if (!isset($_FILES[$varName]['error'])
            || is_array($_FILES[$varName]['error'])
            || $_FILES[$varName]['error'] != 0)
        {
            return 'upload_error';
        }

        if ($_FILES[$varName]['size'] > $maxSize)
        {
            return 'file_too_large';
        }

        $this->tmpFilename = $_FILES[$varName]['tmp_name'];
        $this->origFilename = $_FILES[$varName]['name'];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($this->tmpFilename);
        if (is_string($mimeType))
        {
            $this->mimeType = $mimeType;
        }

        if ($whitelistMimeTypes !== true)
        {
            if (!in_array($this->mimeType, $whitelistMimeTypes))
            {
                return 'mime_type_not_allowed';
            }
        }

        return true;
    }

    /**
     * Get the file extension based on the MIME type.
     *
     * @return false|string The file extension or false if not recognized
     */
    public function getExtension(): false|string
    {
        $ext = array_search($this->mimeType, [
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
        ], true);

        if ($ext === false)
        {
            return false;
        }

        return $ext;
    }

    /**
     * Get the MIME type of the uploaded file.
     *
     * @return string The MIME type
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get the original filename of the uploaded file.
     *
     * @return string The original filename
     */
    public function getOrigFilename(): string
    {
        return $this->origFilename;
    }

    /**
     * Get the temporary filename of the uploaded file.
     *
     * @return string The temporary filename
     */
    public function getTmpFilename(): string
    {
        return $this->tmpFilename;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * @param string $newLocation The new location for the file
     *
     * @return string|true True if the move was successful, or 'save_failed' on failure
     */
    public function move(string $newLocation): string|true
    {
        $result = move_uploaded_file($this->tmpFilename, $newLocation);

        return ($result === true) ? true : 'save_failed';
    }
}
