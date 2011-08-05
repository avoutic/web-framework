<?php
class PageBasic
{
    protected $state = array();
    protected $page_content = array();

    function __construct($state)
    {
        $this->state = $state;
    }

    static function get_filter()
    {
        return array();
    }

    function get_title()
    {
        return "No Title Defined";
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

    function display_page()
    {
        $this->do_logic();
        unset($this->state['input']);
        $this->page_content['title'] = $this->get_title();
        $this->page_content['keywords'] = $this->get_keywords();
        $this->page_content['description'] = $this->get_description();
    }
};
?>
