<?php

namespace WebFramework\Core;

function arrayify_datacore(mixed &$item, string $key): void
{
    if (is_object($item) && is_subclass_of($item, 'DataCore'))
    {
        $item = get_object_vars($item);
    }
}

abstract class ApiAction extends ActionCore
{
    public static function redirect_login_type(): string
    {
        return '403';
    }

    protected function output_json(bool $success, mixed $output, bool $direct = false): void
    {
        header('Content-type: application/json');

        if (is_array($output))
        {
            array_walk_recursive($output, '\\WebFramework\\Core\\arrayify_datacore');
        }

        if ($direct && $success)
        {
            echo(json_encode($output));

            return;
        }

        echo(json_encode(
            [
                'success' => $success,
                'result' => $output,
            ]
        ));
    }

    protected function output_file(string $filename, string $hash = ''): void
    {
        if (!file_exists($filename))
        {
            $this->exit_send_404();
        }

        // Check if already cached on client
        //
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']))
        {
            // Calculate hash if missing but presented by client.
            //
            if (!strlen($hash))
            {
                $hash = sha1_file($filename);
            }

            if ($_SERVER['HTTP_IF_NONE_MATCH'] == '"'.$hash.'"')
            {
                header('HTTP/1.1 304 Not modified');
                header('Cache-Control: public, max-age=604800');
                header('ETag: "'.$hash.'"');

                exit();
            }
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $type = $finfo->file($filename);

        header('Cache-Control: public, max-age=604800');
        header('ETag: "'.$hash.'"');
        header('Content-Length: '.filesize($filename));
        header('Content-Type: '.$type);
        header('Content-Transfer-Encoding: Binary');
        readfile($filename);

        exit();
    }
}
