<?php
class LocaleFactory extends FrameworkCore
{
    protected $default_lang;
    protected $default_locale;

    function __construct($default_lang = 'en',
                         $default_locale = "['en-US','eng','en']")
    {
        $this->default_lang = $default_lang;
        $this->default_locale = $default_locale;
    }

    function get_all_languages()
    {
        $result = $this->query('SELECT language, name FROM languages', array());

        $this->verify($result !== FALSE, 'Failed to select languages');

        $info = array();

        foreach ($result as $row)
            $info[$row['language']] = $row['name'];

        return $info;
    }

    function get_available_languages()
    {
        if (!isset($_SESSION['languages']))
            $this->update_available_languages();

        return $_SESSION['languages'];
    }

    function update_available_languages()
    {
        $result = $this->query('SELECT language, name FROM languages', array());
        $this->verify($result !== FALSE, 'Failed to retrieve languages');

        $info = array();
        foreach ($result as $row)
            $info[$row['language']] = $row['name'];

        $_SESSION['languages'] = $info;
    }

    function set_current_language($lang, $update_locale = true)
    {
        $result = $this->query('SELECT id FROM languages WHERE language = ?',
                array($lang));

        $this->verify($result !== FALSE, 'Failed to check language');

        if ($result->RecordCount() == 0)
            return FALSE;

        $_SESSION['lang'] = $lang;

        if ($update_locale)
            $this->set_current_locale($lang);
    }

    function set_current_locale($lang)
    {
        $_SESSION['locale_string'] = $this->get_locale_by_lang($lang);
        setlocale(LC_ALL, json_decode($_SESSION['locale_string'], true));
    }

    function get_current_language()
    {
        if (!isset($_SESSION['lang']))
            return $this->default_lang;

        return $_SESSION['lang'];
    }

    function get_current_locale_string()
    {
        if (!isset($_SESSION['locale_string']))
            return $this->default_locale;

        return $_SESSION['locale_string'];
    }

    function get_locale_by_lang($code)
    {
        $result = $this->query('SELECT locale_string FROM languages WHERE language = ?',
                array($code));

        $this->verify($result !== FALSE, 'Failed to select locale');

        if ($result->RecordCount())
            return $result->fields['locale_string'];

        return $this->default_locale;
    }

};

class CountryLocaleFactory extends LocaleFactory
{
    protected $default_country = 'nl';

    function set_current_country($country_code, $update_current_lang = true)
    {
        $_SESSION['current_country'] = $country_code;

        $this->update_available_languages();

        if ($update_current_lang)
        {
            if (!array_key_exists($this->get_current_language(),
                                  $_SESSION['languages']))
                $this->set_current_language($this->get_country_default_language($country_code));
        }
    }

    function get_current_country()
    {
        if (!isset($_SESSION['current_country']))
            return $this->default_country;
        
        return $_SESSION['current_country'];
    }

    function update_available_languages()
    {
        $current_country = $this->get_current_country();

        $result = $this->query('SELECT l.language, l.name FROM languages AS l, country_languages AS cl, countries AS c WHERE c.code = ? AND cl.country_id = c.id AND l.language = cl.language',
                array($current_country));

        $this->verify($result !== FALSE, 'Failed to retrieve country languages');

        $info = array();
        foreach ($result as $row)
            $info[$row['language']] = $row['name'];

        $_SESSION['languages'] = $info;
    }

    function get_current_language()
    {
        if(!isset($_SESSION['lang']))
        {
            $country = $this->get_current_country();
            $lang = $this->get_country_default_language($country);

            $this->set_current_language($lang);

            return $lang;
        }

        return $_SESSION['lang'];
    }

    function get_country_default_language($country_code)
    {
        $result = $this->query('SELECT cl.language FROM country_languages AS cl, countries AS c WHERE c.code = ? AND cl.country_id = c.id AND country_default = 1 LIMIT 1',
                array($country_code));

        $this->verify($result !== FALSE, 'Failed to select default language for country');
        if ($result->RecordCount())
            return $result->fields['language'];

        return $this->default_lang;
    }

    function language_supported_for_country($lang, $country_code)
    {
        $result = $this->query('SELECT cl.id FROM country_languages AS cl, countries AS c WHERE c.code = ? AND cl.country_id = c.id AND cl.language = ? LIMIT 1',
                array($country_code, $lang));

        $this->verify($result !== FALSE, 'Failed to check if language supported');

        if ($result->RecordCount())
            return TRUE;

        return FALSE;
    }
};
?>
