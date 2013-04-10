<?php
class PageMain extends PageBasic
{
    function display_content()
    {
        $this->load_template('main.tpl', $this->page_content);
    }
};
?>
