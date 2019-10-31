<?php
class PageMain extends PageBasic
{
    function get_title()
    {
        return 'Hello World';
    }

    function display_content()
    {
        $this->load_template('main.tpl', $this->page_content);
    }
};
?>
