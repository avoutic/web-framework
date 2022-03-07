<?php
class UploadHandler
{
    protected $var_name;
    protected $tmp_filename;
    protected $orig_filename;
    protected $mime_type;

    function __construct($var_name = 'file')
    {
        $this->var_name = $var_name;
    }

    function check_upload($max_size, $whitelist_mime_types = true)
    {
        $var_name = $this->var_name;

        if (!isset($_FILES[$var_name]) ||
            !isset($_FILES[$var_name]['size']) ||
            $_FILES[$var_name]['size'] == 0)
        {
            return 'no_file_present';
        }

        if (!isset($_FILES[$var_name]['error']) ||
            is_array($_FILES[$var_name]['error']) ||
            $_FILES[$var_name]['error'] != 0)
        {
            return 'upload_error';
        }

        if ($_FILES[$var_name]['size'] > $max_size)
            return 'file_too_large';

        $this->tmp_filename = $_FILES[$var_name]['tmp_name'];
        $this->orig_filename = $_FILES[$var_name]['name'];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $this->mime_type = $finfo->file($this->tmp_filename);

        if ($whitelist_mime_types !== true)
        {
            $this->verify(is_array($whitelist_mime_types), 'whitelist_mime_types not an array');

            if (!in_array($this->mime_type, $whitelist_mime_types))
                return 'mime_type_not_allowed';
        }

        return true;
    }

    function get_extension()
    {
        $ext = array_search($this->mime_type, array(
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                    'pdf' => 'application/pdf',
                    ), true);

        if ($ext === false)
            return false;

        return $ext;
    }

    function get_mime_type()
    {
        return $this->mime_type;
    }

    function get_orig_filename()
    {
        return $this->orig_filename;
    }

    function get_tmp_filename()
    {
        return $this->tmp_filename;
    }

    function move($new_location)
    {
        return move_uploaded_file($this->tmp_filename, $new_location);
    }
};
?>
