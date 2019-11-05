<?php
class TranslationFactory
{
    protected $database;

    function __construct($global_info)
    {
        $this->database = $global_info['database'];
    }

    function get_all_translations($lang)
    {
        $result = $this->database->Query('SELECT tt.key, t.value FROM translation_tags AS tt LEFT JOIN translations AS t ON t.key = tt.key AND t.lang = ?',
                array($lang));

        verify($result !== FALSE, 'Failed to get translations');

        $info = array();

        foreach ($result as $row)
            $info[$row['key']] = $row['value'];

        return $info;
    }

    function get_translation($key, $lang)
    {
        $result = $this->database->Query('SELECT value FROM translations WHERE lang = ? AND `key` = ?',
                array($lang, $key));

        verify($result !== FALSE, 'Failed to get translation');

        if ($result->RecordCount() == 0)
            return $key;

        return $result->fields['value'];
    }

    function update_translation($key, $lang, $value)
    {
        $result = $this->database->Query('REPLACE INTO translations SET `key` = ?, lang = ?, value = ?',
                array($key, $lang, $value));

        verify($result !== FALSE, 'Failed to update translation');

        return TRUE;
    }
};
?>
