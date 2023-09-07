<?php

namespace WebFramework\Validation;

use Psr\Http\Message\UploadedFileInterface;
use WebFramework\Exception\ValidationException;

class UploadValidationService
{
    public function __construct(
    ) {
    }

    /**
     * @param ?array<string> $whitelistMimeTypes
     */
    public function validate(UploadedFileInterface $uploadedFile, ?int $maxSize = null, ?array $whitelistMimeTypes = null): string
    {
        $error = $uploadedFile->getError();
        if ($error !== UPLOAD_ERR_OK)
        {
            if ($error == UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE)
            {
                throw new ValidationException('upload', 'upload.file_too_large');
            }
            if ($error == UPLOAD_ERR_PARTIAL)
            {
                throw new ValidationException('upload', 'upload.partial_upload');
            }
            if ($error == UPLOAD_ERR_NO_FILE)
            {
                throw new ValidationException('upload', 'upload.no_file');
            }
            if ($error == UPLOAD_ERR_NO_TMP_DIR)
            {
                throw new \RuntimeException('No tmp dir present');
            }
            if ($error == UPLOAD_ERR_CANT_WRITE)
            {
                throw new \RuntimeException('Cannot write file');
            }
            if ($error == UPLOAD_ERR_EXTENSION)
            {
                throw new \RuntimeException('Extension blocking upload');
            }

            throw new \RuntimeException('Unknown error: '.$error);
        }

        if ($maxSize !== null && $uploadedFile->getSize() > $maxSize)
        {
            throw new ValidationException('upload', 'upload.file_too_large_max_size', ['max_size' => (string) $maxSize]);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($uploadedFile->getStream());

        if (!is_string($mimeType))
        {
            $mimeType = 'application/octet-stream';
        }

        if ($whitelistMimeTypes !== null && !in_array($mimeType, $whitelistMimeTypes))
        {
            throw new ValidationException('upload', 'upload.mime_type_not_allowed');
        }

        return $mimeType;
    }
}
