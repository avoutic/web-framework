<?php
abstract class PageCore
{
    protected $global_info;
    protected $state;
    protected $database;
    protected $cache;
    protected $config;

    function __construct($global_info)
    {
        $this->global_info = $global_info;
        $this->database = $global_info['database'];
        $this->cache = $global_info['cache'];
        $this->state = $global_info['state'];
        $this->config = $global_info['config'];
        $this->auth = $global_info['auth'];
    }

    static function get_filter()
    {
        return array();
    }

    function get_input_var($name)
    {
        verify(isset($this->state['input'][$name]), 'Missing input variable: '.$name);

        return $this->state['input'][$name];
    }

    function get_input_vars()
    {
        $fields = array();

        foreach (array_keys($this->get_filter()) as $key)
            $fields[$key] = $this->state['input'][$key];

        return $fields;
    }

    function get_raw_input_var($name)
    {
        verify(isset($this->state['raw_input'][$name]), 'Missing input variable: '.$name);

        return $this->state['raw_input'][$name];
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
        verify(( is_string($input) || is_bool($input) || is_int($input) || is_float($input) || is_null($input)) && is_bool($double_encode), 'Not valid for encoding');

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
    
    function __construct($global_info)
    {
        parent::__construct($global_info);
        $this->frame_file = $this->config['page']['default_frame_file'];

        if (isset($this->config['page']['mods']))
        {
            foreach ($this->config['page']['mods'] as $mod_info)
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

                    $mod_obj = new $class($global_info, $mod_info);

                    if ($this->cache != null && $class::is_cacheable)
                        $this->cache->set($tag, $mod_obj);
                }

                array_push($this->mods, $mod_obj);
            }
        }
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

    function get_frame_file()
    {
        return $this->frame_file;
    }

    function add_message($type, $message, $extra_message = "")
    {
        array_push($this->state['messages'], array(
                    'mtype' => $type,
                    'message' => $message,
                    'extra_message' => $extra_message));
    }

    function load_template($name, $args = array())
    {
        global $site_templates;

        include($site_templates.$name.'.inc.php');
    }

    function load_file($name)
    {
        include($this->config['document_root'].'/'.$name);
    }

    function check_required(&$var, $name)
    {

        if (!isset($this->state['input'][$name]) ||
            !strlen($this->state['input'][$name]))
        {
            die('Missing input variable \''.$name.'\'.');
        }

        $var = $this->state['input'][$name];
        return TRUE;
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
        global $site_frames;
        unset($this->state['input']);

        ob_start();

        if (strlen($this->get_frame_file()))
            require($site_frames.$this->get_frame_file());
        else
            $this->display_content();

        $content = ob_get_clean();

        foreach ($this->mods as $mod)
        {
            $content = $mod->callback($content);
        }

        print($content);
    }

    function html_main()
    {
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

    function check_required(&$var, $name)
    {

        if (!isset($this->state['input'][$name]) ||
            !strlen($this->state['input'][$name]))
        {
            $this->output_json(false, 'Missing input variable \''.$name.'\'.');
            return FALSE;
        }

        $var = $this->state['input'][$name];
        return TRUE;
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
