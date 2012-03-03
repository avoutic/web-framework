<?php
abstract class PageCore
{
    protected $state = array();
    protected $database;
    protected $memcache;
    protected $config;

    function __construct($global_info)
    {
        $this->database = $global_info['database'];
        $this->memcache = $global_info['memcache'];
        $this->state = $global_info['state'];
        $this->config = $global_info['config'];
    }

    static function get_filter()
    {
        return array();
    }

    static function get_permissions()
    {
        return array();
    }

    static function redirect_login_type()
    {
        return 'redirect';
    }
};

abstract class PageBasic extends PageCore
{
    protected $frame_file;
    protected $page_content = array();

    function __construct($global_info)
    {
        parent::__construct($global_info);
        $this->frame_file = $this->config['page']['default_frame_file'];
    }

    function get_title()
    {
        return "No Title Defined";
    }

    function get_content_title()
    {
        return $this->get_title();
    }

    function get_keywords()
    {
        return "";
    }

    function get_description()
    {
        return "";
    }

    function add_message($type, $message, $extra_message = "")
    {
        $this->state['message']['mtype'] = $type;
        $this->state['message']['message'] = $message;
        $this->state['message']['extra_message'] = $extra_message;
    }

    function do_logic()
    {
    }

    function display_header()
    {
        return "";
    }

    function display_content()
    {
        return "No content.";
    }

    function display_frame()
    {
        global $site_includes;
        unset($this->state['input']);

        if (strlen($this->frame_file))
            require($site_includes.$this->frame_file);
        else
            $this->display_content();
    }

    function html_main()
    {
        $this->do_logic();
        $this->display_frame();
    }
};

abstract class PageService extends PageCore
{
    static function redirect_login_type()
    {
        return '403';
    }

    function output_json($success, $output, $direct = false)
    {
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
