<?php
function get_page_filter()
{
	return array(
		'name' => FORMAT_FILE_NAME,
		'file' => FORMAT_FILE_LOCATION,
        'do'   => 'yes'
	);
}

function get_page_permissions()
{
	return array();
}

function get_page_title()
{
	return "Download";
}

function do_page_logic()
{
    global $state;

    if ($state['input']['do'] == 'yes')
    {
        header('Location: '.$state['input']['file']);
    }
}

function display_header()
{
    global $state;

    if (!strlen($state['input']['file']))
        return;
?>
  <meta http-equiv="refresh" content="3; ?page=download&do=yes&file=<?=$state['input']['file']?>">
<?
}

function display_page()
{
	global $state;

    if (!strlen($state['input']['file']))
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
    <p>Downloading of <b><?=$state['input']['name']?></b> should start automatically.</p>
    <p>If the downloading does not start within 10 seconds, you can manually start the download by clicking on the following link: <a href="<?=$state['input']['file']?>"><?=$state['input']['name']?></a>.</p>
  </div>
</div>
<?
    }
}
?>
