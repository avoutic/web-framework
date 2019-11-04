<?php
class PageLogoff extends PageBasic
{
    static function get_filter()
    {
        return array(
                'return_page' => FORMAT_RETURN_PAGE,
                );
    }

    function get_title()
    {
        return "Logoff";
    }

    function do_logic()
    {
        $this->auth->deauthenticate();

        $return_page = $this->state['input']['return_page'];
        if (!strlen($return_page) || substr($return_page, 0, 2) == '//')
            $return_page = '/';

        header("Location: ".$return_page);
        exit();
    }

    function display_content()
    {
        echo <<<HTML
<div>
  Logging off.
</div>
HTML;
    }
};
?>
