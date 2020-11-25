<?php
abstract class PageCore extends FrameworkCore
{
    protected $web_handler = null;
    private $input = array();
    private $raw_input = array();

    function __construct()
    {
        parent::__construct();

        $this->web_handler = WF::get_web_handler();
        $this->input = $this->framework->get_input();
        $this->raw_input = $this->framework->get_raw_input();
    }

    static function get_filter()
    {
        return array();
    }

    function get_input_var($name, $content_required = false)
    {
        $this->verify(isset($this->input[$name]), 'Missing input variable: '.$name);
        if ($content_required)
            $this->verify(strlen($this->input[$name]), 'Missing input variable: '.$name);

        return $this->input[$name];
    }

    function get_input_vars()
    {
        $fields = array();

        foreach (array_keys($this->get_filter()) as $key)
            $fields[$key] = $this->input[$key];

        return $fields;
    }

    function get_raw_input_var($name)
    {
        $this->verify(isset($this->raw_input[$name]), 'Missing input variable: '.$name);

        return $this->raw_input[$name];
    }

    function exit_send_404($type = 'generic')
    {
        $this->web_handler->exit_send_404($type);
    }

    static function get_permissions()
    {
        return array();
    }

    static function redirect_login_type()
    {
        return 'redirect';
    }

    static function encode($input, $double_encode = true)
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
    protected $frame_file;
    protected $page_content = array();
    private $mods = array();

    function __construct()
    {
        parent::__construct();

        $this->frame_file = $this->get_config('page.default_frame_file');
        $this->page_content['base_url'] = $this->get_base_url();

        $page_mods = $this->get_config('page.mods');
        foreach ($page_mods as $mod_info)
        {
            $mod_obj = FALSE;

            $class = $mod_info['class'];
            $include_file = $mod_info['include_file'];

            $tag = sha1($mod_info);

            if ($this->cache != null && $class::is_cacheable)
                $mod_obj = $this->cache->get($tag);

            if ($mod_obj === FALSE)
            {
                require_once($include_file);

                $mod_obj = new $class($mod_info);

                if ($this->cache != null && $class::is_cacheable)
                    $this->cache->set($tag, $mod_obj);
            }

            array_push($this->mods, $mod_obj);
        }
    }

    protected function get_csrf_token()
    {
        return $this->web_handler->get_csrf_token();
    }

    protected function get_base_url()
    {
        return $this->get_config('page.base_url');
    }

    function add_page_mod($tag, iPageModule $mod)
    {
        $this->mods[$tag] = $mod;
    }

    function get_page_mod($tag)
    {
        return $this->mods[$tag];
    }

    function get_title()
    {
        return "No Title Defined";
    }

    function get_content_title()
    {
        return $this->get_title();
    }

    function get_canonical()
    {
        return "";
    }

    function get_onload()
    {
        return "";
    }

    function get_keywords()
    {
        return "";
    }

    function get_description()
    {
        return "";
    }

    function get_meta_robots()
    {
        // Default behaviour is "index,follow"
        //
        return "index,follow";
    }

    function get_frame_file()
    {
        return $this->frame_file;
    }

    function load_template($name, $args = array())
    {
        $this->verify(file_exists(WF::$site_templates.$name.'.inc.php'), 'Requested template not present');
        include(WF::$site_templates.$name.'.inc.php');
    }

    function load_file($name)
    {
        include($this->get_config('document_root').'/'.$name);
    }

    function is_blocked($name)
    {
        return $this->input[$name] != $this->raw_input[$name];
    }

    function check_sanity()
    {
    }

    function do_logic()
    {
    }

    function display_header()
    {
        return "";
    }

    function display_footer()
    {
        return "";
    }

    function display_content()
    {
        return "No content.";
    }

    function display_frame()
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

        foreach ($this->mods as $mod)
            $content = $mod->callback($content);

        print($content);
    }

    function html_main()
    {
        $this->check_sanity();
        $this->do_logic();
        $this->display_frame();
    }
};

function arrayify_datacore(&$item, $key)
{
    if (is_object($item) && is_subclass_of($item, 'DataCore'))
    {
        $item = get_object_vars($item);
    }
}

abstract class PageService extends PageCore
{
    static function redirect_login_type()
    {
        return '403';
    }

    function output_json($success, $output, $direct = false)
    {
        header('Content-type: application/json');

        if (is_array($output))
            array_walk_recursive($output, 'arrayify_datacore');

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
};
?>
