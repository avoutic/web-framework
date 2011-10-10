<?php
class PageCore
{
    protected $state = array();
    protected $database;

    function __construct($database, $state)
    {
        $this->database = $database;
        $this->state = $state;
    }

    static function get_filter()
    {
        return array();
    }

    static function get_permissions()
    {
        return array();
    }
};

class PageBasic extends PageCore
{
    protected $frame_file;
    protected $page_content = array();

    function __construct($database, $state, $frame_file)
    {
        parent::__construct($database, $state);
        $this->frame_file = $frame_file;
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
    }
};
?>
