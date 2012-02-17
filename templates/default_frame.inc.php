<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">

<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <meta http-equiv="expires" content="3600" />
  <meta name="robots" content="index,follow" />
  <meta name="copyright" content="" />
  <meta name="author" content="" />
  <meta name="distribution" content="global" />
  <link rel="icon" type="image/x-icon" href="/favicon.ico" />
  <title><?=$this->get_title()?></title>
<?
$this->display_header();
?>
</head>
<body>
<?
$this->display_content();
?>
<?
if (user_has_permissions(array('debug'))) {
    print('<hr/>');
    print('<pre>');
    print_r($this->state);
    print('</pre>');
}
?>
</body>
</html>
