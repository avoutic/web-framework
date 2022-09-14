<?php
namespace WebFramework\Actions;

use WebFramework\Core\PageBasic;

class Logoff extends PageBasic
{
    /**
     * @return array<string, string>
     */
    static function get_filter(): array
    {
        return array(
                'return_page' => FORMAT_RETURN_PAGE,
                );
    }

    protected function get_title(): string
    {
        return "Logoff";
    }

    protected function do_logic(): void
    {
        $this->deauthenticate();

        $return_page = $this->get_input_var('return_page');

        if (!strlen($return_page) || substr($return_page, 0, 2) == '//')
            $return_page = '/';

        if (substr($return_page, 0, 1) != '/')
            $return_page = '/'.$return_page;

        header("Location: ".$this->get_base_url().$return_page);
        exit();
    }

    protected function display_content(): void
    {
        echo <<<HTML
<div>
  Logging off.
</div>
HTML;
    }
};
?>
