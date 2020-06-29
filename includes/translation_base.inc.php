<?php
class TranslationFactory extends FrameworkCore
{
    function get_all_translations($lang)
    {
        $result = $this->query('SELECT tt.key, t.value FROM translation_tags AS tt LEFT JOIN translations AS t ON t.key = tt.key AND t.lang = ?',
                array($lang));

        $this->verify($result !== FALSE, 'Failed to get translations');

        $info = array();

        foreach ($result as $row)
            $info[$row['key']] = $row['value'];

        return $info;
    }

    function get_translation($key, $lang)
    {
        $result = $this->query('SELECT value FROM translations WHERE lang = ? AND `key` = ?',
                array($lang, $key));

        $this->verify($result !== FALSE, 'Failed to get translation');

        if ($result->RecordCount() == 0)
            return $key;

        return $result->fields['value'];
    }

    function update_translation($key, $lang, $value)
    {
        $result = $this->query('REPLACE INTO translations SET `key` = ?, lang = ?, value = ?',
                array($key, $lang, $value));

        $this->verify($result !== FALSE, 'Failed to update translation');

        return TRUE;
    }
};
?>
