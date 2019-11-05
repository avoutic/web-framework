<?php
interface iPageModule
{
    public function callback($input);
};

class UrlConfig implements iPageModule
{
    protected $config;

    function __construct($global_info)
    {
        $this->config = $global_info['config'];
    }

    function callback($input)
    {
        $input = preg_replace_callback(
            '/<var:url:([\w\._-]+)(\\/)?>/i',
            array($this, 'url_match'), $input);

        return $input;
    }

    function url_match($matches)
    {
        return $this->translate($matches[1]);
    }

    function translate($tag, $replaces = array())
    {
        if (!isset($this->config['mods']['urls'][$tag]))
            return $tag;

        return $this->config['mods']['urls'][$tag];
    }
};

class Translator implements iPageModule
{
    protected $database;
    protected $state;
    protected $match_func;

    function __construct($global_info, $match_func = 'translate_match')
    {
        $this->database = $global_info['database'];
        $this->state = $global_info['state'];
        $this->match_func = $match_func;
    }

    function callback($input)
    {
        $input = preg_replace_callback(
            '/<var:translate:([\w\._-]+)(\\/)?>/i',
            array($this, $this->match_func), $input);

        return $input;
    }

    function translate_match($matches)
    {
        return $this->translate($matches[1]);
    }

    function translate_match_encode($matches)
    {
        $input = $this->translate($matches[1]);

        $str = htmlentities((string)$input, ENT_QUOTES, 'UTF-8');
        if (!strlen($str))
            $str = htmlentities((string)$input, ENT_QUOTES, 'ISO-8859-1');

        return $str;
    }

    function translate($tag, $replaces = array())
    {
        // Get message
        $result = $this->database->Query('SELECT value FROM translations WHERE `key` = ? AND lang = ?',
                array($tag, $this->state['lang']));

        verify($result !== FALSE, 'Failed to get translation');

        $msg = $tag;

        if ($result->RecordCount() == 1 && strlen($result->fields['value']))
            $msg = $result->fields['value'];

        foreach($replaces as $k => $v)
            $msg = str_replace('$' . $k, $v, $msg);

        return str_replace('\n', "\n", $msg);
    }
};
?> 
