<?php

namespace WebFramework\Validation;

use Psr\Http\Message\UploadedFileInterface;
use WebFramework\Exception\ValidationException;

/**
 * Class UploadValidationService.
 *
 * This service is responsible for validating uploaded files.
 */
class UploadValidationService
{
    /**
     * UploadValidationService constructor.
     */
    public function __construct() {}

    /**
     * Check the validity of an uploaded file.
     *
     * @param UploadedFileInterface $uploadedFile       The uploaded file to check
     * @param null|int              $maxSize            The maximum allowed file size in bytes (optional)
     * @param null|array<string>    $whitelistMimeTypes The allowed MIME types (optional)
     *
     * @return string|true Returns true if the file is valid, or an error message string if not
     *
     * @throws \RuntimeException If there's an error during the upload process
     */
    public function check(UploadedFileInterface $uploadedFile, ?int $maxSize = null, ?array $whitelistMimeTypes = null): string|true
    {
        $error = $uploadedFile->getError();
        if ($error !== UPLOAD_ERR_OK)
        {
            if ($error == UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE)
            {
                return 'file_too_large';
            }
            if ($error == UPLOAD_ERR_PARTIAL)
            {
                return 'partial_upload';
            }
            if ($error == UPLOAD_ERR_NO_FILE)
            {
                return 'no_file';
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
            return 'file_too_large_max_size';
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($uploadedFile->getStream());

        if (!is_string($mimeType))
        {
            $mimeType = 'application/octet-stream';
        }

        if ($whitelistMimeTypes !== null && !in_array($mimeType, $whitelistMimeTypes))
        {
            return 'mime_type_not_allowed';
        }

        return true;
    }

    /**
     * Validate an uploaded file and throw an exception if it's invalid.
     *
     * @param UploadedFileInterface $uploadedFile       The uploaded file to validate
     * @param null|int              $maxSize            The maximum allowed file size in bytes (optional)
     * @param null|array<string>    $whitelistMimeTypes The allowed MIME types (optional)
     *
     * @return string The MIME type of the validated file
     *
     * @throws ValidationException If the file is invalid
     * @throws \RuntimeException   If there's an error during the upload process
     */
    public function validate(UploadedFileInterface $uploadedFile, ?int $maxSize = null, ?array $whitelistMimeTypes = null): string
    {
        $result = $this->check($uploadedFile, $maxSize, $whitelistMimeTypes);

        if (is_string($result))
        {
            if ($result === 'file_too_large_max_size')
            {
                throw new ValidationException('upload', 'upload.file_too_large_max_size', ['max_size' => (string) $maxSize]);
            }

            throw new ValidationException('upload', 'upload.'.$result);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($uploadedFile->getStream());

        if (!is_string($mimeType))
        {
            $mimeType = 'application/octet-stream';
        }

        return $mimeType;
    }
}
