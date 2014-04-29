<?php
require_once($includes.'securimage/securimage.php');

class PageCaptcha extends PageBasic
{
    function do_logic()
    {
        $securimage = new Securimage();
        $securimage->show();
        exit(0);
    }
};
?>
