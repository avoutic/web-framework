<?php
class PageBasic
{
    protected $state = array();
    protected $database;
    protected $frame_file;
    protected $page_content = array();

    function __construct($database, $state, $frame_file)
    {
        $this->database = $database;
        $this->state = $state;
        $this->frame_file = $frame_file;
    }

    static function get_filter()
    {
        return array();
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

    static function get_permissions()
    {
        return array();
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
        return "";
    }

    function display_frame()
    {
        global $site_includes;

        unset($this->state['input']);
        $this->page_content['title'] = $this->get_title();
        $this->page_content['keywords'] = $this->get_keywords();
        $this->page_content['description'] = $this->get_description();

        require($site_includes.$this->frame_file);
    }
};
?>
