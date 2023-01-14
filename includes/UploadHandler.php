<?php

namespace WebFramework\Core;

class UploadHandler extends FrameworkCore
{
    protected string $var_name;
    protected string $tmp_filename = '';
    protected string $orig_filename = '';
    protected string $mime_type = '';

    public function __construct(string $var_name = 'file')
    {
        parent::__construct();

        $this->var_name = $var_name;
    }

    /**
     * @param array<string>|true $whitelist_mime_types
     */
    public function check_upload(int $max_size, bool|array $whitelist_mime_types = true): string|true
    {
        $var_name = $this->var_name;

        if (!isset($_FILES[$var_name])
            || !isset($_FILES[$var_name]['size'])
            || $_FILES[$var_name]['size'] == 0)
        {
            return 'no_file_present';
        }

        if (!isset($_FILES[$var_name]['error'])
            || is_array($_FILES[$var_name]['error'])
            || $_FILES[$var_name]['error'] != 0)
        {
            return 'upload_error';
        }

        if ($_FILES[$var_name]['size'] > $max_size)
        {
            return 'file_too_large';
        }

        $this->tmp_filename = $_FILES[$var_name]['tmp_name'];
        $this->orig_filename = $_FILES[$var_name]['name'];

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($this->tmp_filename);
        if (is_string($mime_type))
        {
            $this->mime_type = $mime_type;
        }

        if ($whitelist_mime_types !== true)
        {
            $this->verify(is_array($whitelist_mime_types), 'whitelist_mime_types not an array');

            if (!in_array($this->mime_type, $whitelist_mime_types))
            {
                return 'mime_type_not_allowed';
            }
        }

        return true;
    }

    public function get_extension(): false|string
    {
        $ext = array_search($this->mime_type, [
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

    public function get_mime_type(): string
    {
        return $this->mime_type;
    }

    public function get_orig_filename(): string
    {
        return $this->orig_filename;
    }

    public function get_tmp_filename(): string
    {
        return $this->tmp_filename;
    }

    public function move(string $new_location): string|true
    {
        $result = move_uploaded_file($this->tmp_filename, $new_location);

        return ($result === true) ? true : 'save_failed';
    }
}
