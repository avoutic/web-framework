<?php
namespace WebFramework\Core;

use finfo;

abstract class PageCore extends FrameworkCore
{
    protected WFWebHandler $web_handler;

    /**
     * @var array<string|array<string>>
     */
    protected array $input = array();

    /**
     * @var array<string|array<string>>
     */
    protected array $raw_input = array();

    function __construct()
    {
        parent::__construct();

        $this->web_handler = WF::get_web_handler();
        $this->input = $this->framework->get_input();
        $this->raw_input = $this->framework->get_raw_input();
    }

    /**
     * @return array<string>
     */
    static function get_filter(): array
    {
        return array();
    }

    protected function get_input_var(string $name, bool $content_required = false): string
    {
        $this->verify(isset($this->input[$name]), 'Missing input variable: '.$name);
        $this->verify(is_string($this->input[$name]), 'Not a string');

        if ($content_required)
            $this->verify(strlen($this->input[$name]), 'Missing input variable: '.$name);

        return $this->input[$name];
    }

    /**
     * @return array<string>
     */
    protected function get_input_array(string $name): array
    {
        $this->verify(isset($this->input[$name]), 'Missing input variable: '.$name);
        $this->verify(is_array($this->input[$name]), 'Not an array');

        return $this->input[$name];
    }

    /**
     * @return array<string|array<string>>
     */
    protected function get_input_vars(): array
    {
        $fields = array();

        foreach (array_keys($this->get_filter()) as $key)
            $fields[$key] = $this->input[$key];

        return $fields;
    }

    protected function get_raw_input_var(string $name): string
    {
        $this->verify(isset($this->raw_input[$name]), 'Missing input variable: '.$name);
        $this->verify(is_string($this->input[$name]), 'Not a string');

        return $this->raw_input[$name];
    }

    /**
     * @return array<string>
     */
    protected function get_raw_input_array(string $name): array
    {
        $this->verify(isset($this->raw_input[$name]), 'Missing input variable: '.$name);
        $this->verify(is_array($this->input[$name]), 'Not an array');

        return $this->raw_input[$name];
    }

    protected function get_base_url(): string
    {
        return $this->get_config('page.base_url');
    }

    /**
     * @return never
     */
    protected function exit_send_403(string $type = 'generic'): void
    {
        $this->web_handler->exit_send_403($type);
    }

    /**
     * @return never
     */
    protected function exit_send_404(string $type = 'generic'): void
    {
        $this->web_handler->exit_send_404($type);
    }

    protected function blacklist_404(bool|int $bool, string $reason, string $type = 'generic'): void
    {
        if ($bool)
            return;

        $this->add_blacklist_entry($reason);

        $this->exit_send_404($type);
    }

    /**
     * @return array<string>
     */
    static function get_permissions(): array
    {
        return array();
    }

    static function redirect_login_type(): string
    {
        return 'redirect';
    }

    static function encode(mixed $input, bool $double_encode = true): string
    {
        WF::verify(( is_string($input) || is_bool($input) || is_int($input) || is_float($input) || is_null($input)) && is_bool($double_encode), 'Not valid for encoding');

        $str = htmlentities((string)$input, ENT_QUOTES, 'UTF-8', $double_encode);
        if (!strlen($str))
            $str = htmlentities((string)$input, ENT_QUOTES, 'ISO-8859-1', $double_encode);
        return $str;
    }
};

abstract class PageBasic extends PageCore
{
    protected string $frame_file;

    /**
     * @var array<mixed>
     */
    protected array $page_content = array();

    function __construct()
    {
        parent::__construct();

        $this->frame_file = $this->get_config('page.default_frame_file');
        $this->page_content['base_url'] = $this->get_base_url();
    }

    protected function get_csrf_token(): string
    {
        return $this->web_handler->get_csrf_token();
    }

    protected function get_title(): string
    {
        return "No Title Defined";
    }

    protected function get_content_title(): string
    {
        return $this->get_title();
    }

    protected function get_canonical(): string
    {
        return "";
    }

    protected function get_onload(): string
    {
        return "";
    }

    protected function get_keywords(): string
    {
        return "";
    }

    protected function get_description(): string
    {
        return "";
    }

    protected function get_meta_robots(): string
    {
        // Default behaviour is "index,follow"
        //
        return "index,follow";
    }

    protected function get_frame_file(): string
    {
        return $this->frame_file;
    }

    /**
     * @param array<mixed> $args
     */
    protected function load_template(string $name, array $args = array()): void
    {
        $this->verify(file_exists(WF::$site_templates.$name.'.inc.php'), 'Requested template not present');
        include(WF::$site_templates.$name.'.inc.php');
    }

    protected function is_blocked(string $name): bool
    {
        return $this->input[$name] != $this->raw_input[$name];
    }

    protected function check_sanity(): void
    {
    }

    protected function do_logic(): void
    {
    }

    protected function display_header(): void
    {
    }

    protected function display_footer(): void
    {
    }

    protected function display_content(): void
    {
    }

    protected function display_frame(): void
    {
        // Unset availability of input in display
        // Forces explicit handling in do_logic()
        //
        unset($this->input);
        unset($this->raw_input);

        ob_start();

        if (strlen($this->get_frame_file()))
        {
            $frame_file = WF::$site_frames.$this->get_frame_file();
            $this->verify(file_exists($frame_file), 'Requested frame file not present');
            require($frame_file);
        }
        else
            $this->display_content();

        $content = ob_get_clean();

        print($content);
    }

    public function html_main(): void
    {
        $this->check_sanity();
        $this->do_logic();
        $this->display_frame();
    }
};

function arrayify_datacore(mixed &$item, string $key): void
{
    if (is_object($item) && is_subclass_of($item, 'DataCore'))
    {
        $item = get_object_vars($item);
    }
}

abstract class PageService extends PageCore
{
    static function redirect_login_type(): string
    {
        return '403';
    }

    protected function output_json(bool $success, mixed $output, bool $direct = false): void
    {
        header('Content-type: application/json');

        if (is_array($output))
            array_walk_recursive($output, '\\WebFramework\\Core\\arrayify_datacore');

        if ($direct && $success)
        {
            print(json_encode($output));
            return;
        }

        print(json_encode(
                    array(
                        'success' => $success,
                        'result' => $output
                        )));
    }

    protected function output_file(string $filename, string $hash = ''): void
    {
        if (!file_exists($filename))
            $this->exit_send_404();

        // Check if already cached on client
        //
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']))
        {
            // Calculate hash if missing but presented by client.
            //
            if (!strlen($hash))
                $hash = sha1_file($filename);

            if ($_SERVER['HTTP_IF_NONE_MATCH'] == '"'.$hash.'"')
            {
                header("HTTP/1.1 304 Not modified");
                header('Cache-Control: public, max-age=604800');
                header('ETag: "'.$hash.'"');
                exit();
            }
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $type = $finfo->file($filename);

        header('Cache-Control: public, max-age=604800');
        header('ETag: "'.$hash.'"');
        header('Content-Length: ' . filesize($filename));
        header("Content-Type: " . $type);
        header('Content-Transfer-Encoding: Binary');
        readfile($filename);
        exit();
    }
};
?>
