<?php

namespace WebFramework\Core;

class UploadHandler
{
    protected string $varName;
    protected string $tmpFilename = '';
    protected string $origFilename = '';
    protected string $mimeType = '';

    public function __construct(string $varName = 'file')
    {
        $this->varName = $varName;
    }

    /**
     * @param array<string>|true $whitelistMimeTypes
     */
    public function checkUpload(int $maxSize, true|array $whitelistMimeTypes = true): string|true
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

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getOrigFilename(): string
    {
        return $this->origFilename;
    }

    public function getTmpFilename(): string
    {
        return $this->tmpFilename;
    }

    public function move(string $newLocation): string|true
    {
        $result = move_uploaded_file($this->tmpFilename, $newLocation);

        return ($result === true) ? true : 'save_failed';
    }
}
