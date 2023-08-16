<?php

namespace WebFramework\Core;

function arrayifyDatacore(mixed &$item, string $key): void
{
    if (is_object($item) && is_subclass_of($item, 'DataCore'))
    {
        $item = get_object_vars($item);
    }
}

abstract class ApiAction extends ActionCore
{
    public static function redirectLoginType(): string
    {
        return '403';
    }

    protected function outputJson(bool $success, mixed $output, bool $direct = false): void
    {
        header('Content-type: application/json');

        if (is_array($output))
        {
            array_walk_recursive($output, '\\WebFramework\\Core\\arrayifyDatacore');
        }

        if ($direct && $success)
        {
            echo(json_encode($output));

            $this->exit();
        }

        echo(json_encode(
            [
                'success' => $success,
                'result' => $output,
            ]
        ));

        $this->exit();
    }

    protected function outputFile(string $filename, string $hash = '', bool $asDownload = false): void
    {
        if (!file_exists($filename))
        {
            $this->exitSend404();
        }

        // Calculate hash if missing
        //
        if (!strlen($hash))
        {
            $hash = sha1_file($filename);
        }

        // Check if already cached on client
        //
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']))
        {
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

        if ($asDownload)
        {
            header('Content-Disposition: attachment; filename="'.basename($filename).'"');
        }
        else
        {
            header('Content-Disposition: inline; filename="'.basename($filename).'"');
        }

        readfile($filename);

        exit();
    }
}
