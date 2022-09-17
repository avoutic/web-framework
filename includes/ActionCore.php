<?php
namespace WebFramework\Core;

abstract class ActionCore extends FrameworkCore
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
        return $this->get_config('base_url');
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
?>
