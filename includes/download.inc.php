<?php
require_once('page_basic.inc.php');

class PageDownload extends PageBasic
{
    static function get_filter()
    {
        return array(
                'name' => FORMAT_FILE_NAME,
                'file' => FORMAT_FILE_LOCATION,
                'do'   => 'yes'
                );
    }

    function get_title()
    {
        return "Download ".$this->page_content['name'];
    }

    function do_logic()
    {
        $this->page_content['name'] = $this->state['input']['name'];
        $this->page_content['file'] = $this->state['input']['file'];

        if ($this->state['input']['do'] == 'yes')
        {
            header('Location: '.$this->state['input']['file']);
        }
    }

    function display_header()
    {
        if (!strlen($this->page_content['file']))
            return;
        ?>
            <meta http-equiv="refresh" content="3; /download?do=yes&file=<?=$this->page_content['file']?>">
            <?
    }

    function display_content()
    {
        if (!strlen($this->page_content['file']))
        {
?>
<div class="source_item">
  <h2>File download</h2>
  <div class="source_content">
    <p>No file selected for download.</p>
  </div>
</div>
<?
        } else {
?>
<div class="source_item">
  <h2>File download</h2>
  <div class="source_content">
    <p>Downloading of <b><?=$this->page_content['name']?></b> should start automatically.</p>
    <p>If the downloading does not start within 10 seconds, you can manually start the download by clicking on the following link: <a href="<?=$this->page_content['file']?>"><?=$this->page_content['name']?></a>.</p>
  </div>
</div>
<?
        }
    }
};
?>
