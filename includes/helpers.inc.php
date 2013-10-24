<?php
class Helpers
{
    /**
     * Valid email adress?
     *
     * @param string
     * @return boolean
     */
    public static function valid_email($string)
    {
        // filter_var has the weird tendency to accept emails like yourname or test@test
        return
            filter_var($string, FILTER_VALIDATE_EMAIL) !== false
            &&
            preg_match("/^[(\w)\.!#$%&*+-=?^_~]+@[(\w)\.-]+\.[a-z]{2,6}$/Dis", $string) > 0;
    }

	/**
	 * Replace any accents in the string with ASCII characters
	 * 
	 * @param string
	 * @return string
	 */
	public static function remove_accents($str)
	{
		$lowercase = array(
			'à' => 'a',  'ô' => 'o',  'ď' => 'd',  'ḟ' => 'f',  'ë' => 'e',  'š' => 's',  'ơ' => 'o',
			'ß' => 'ss', 'ă' => 'a',  'ř' => 'r',  'ț' => 't',  'ň' => 'n',  'ā' => 'a',  'ķ' => 'k',
			'ŝ' => 's',  'ỳ' => 'y',  'ņ' => 'n',  'ĺ' => 'l',  'ħ' => 'h',  'ṗ' => 'p',  'ó' => 'o',
			'ú' => 'u',  'ě' => 'e',  'é' => 'e',  'ç' => 'c',  'ẁ' => 'w',  'ċ' => 'c',  'õ' => 'o',
			'ṡ' => 's',  'ø' => 'o',  'ģ' => 'g',  'ŧ' => 't',  'ș' => 's',  'ė' => 'e',  'ĉ' => 'c',
			'ś' => 's',  'î' => 'i',  'ű' => 'u',  'ć' => 'c',  'ę' => 'e',  'ŵ' => 'w',  'ṫ' => 't',
			'ū' => 'u',  'č' => 'c',  'ö' => 'o',  'è' => 'e',  'ŷ' => 'y',  'ą' => 'a',  'ł' => 'l',
			'ų' => 'u',  'ů' => 'u',  'ş' => 's',  'ğ' => 'g',  'ļ' => 'l',  'ƒ' => 'f',  'ž' => 'z',
			'ẃ' => 'w',  'ḃ' => 'b',  'å' => 'a',  'ì' => 'i',  'ï' => 'i',  'ḋ' => 'd',  'ť' => 't',
			'ŗ' => 'r',  'ä' => 'a',  'í' => 'i',  'ŕ' => 'r',  'ê' => 'e',  'ü' => 'u',  'ò' => 'o',
			'ē' => 'e',  'ñ' => 'n',  'ń' => 'n',  'ĥ' => 'h',  'ĝ' => 'g',  'đ' => 'd',  'ĵ' => 'j',
			'ÿ' => 'y',  'ũ' => 'u',  'ŭ' => 'u',  'ư' => 'u',  'ţ' => 't',  'ý' => 'y',  'ő' => 'o',
			'â' => 'a',  'ľ' => 'l',  'ẅ' => 'w',  'ż' => 'z',  'ī' => 'i',  'ã' => 'a',  'ġ' => 'g',
			'ṁ' => 'm',  'ō' => 'o',  'ĩ' => 'i',  'ù' => 'u',  'į' => 'i',  'ź' => 'z',  'á' => 'a',
			'û' => 'u',  'þ' => 'th', 'ð' => 'dh', 'æ' => 'ae', 'µ' => 'u',  'ĕ' => 'e',
		);

		$str = str_replace(
			array_keys($lowercase),
			array_values($lowercase),
			$str
		);

		$uppercase = array(
			'À' => 'A',  'Ô' => 'O',  'Ď' => 'D',  'Ḟ' => 'F',  'Ë' => 'E',  'Š' => 'S',  'Ơ' => 'O',
			'Ă' => 'A',  'Ř' => 'R',  'Ț' => 'T',  'Ň' => 'N',  'Ā' => 'A',  'Ķ' => 'K',  'Ĕ' => 'E',
			'Ŝ' => 'S',  'Ỳ' => 'Y',  'Ņ' => 'N',  'Ĺ' => 'L',  'Ħ' => 'H',  'Ṗ' => 'P',  'Ó' => 'O',
			'Ú' => 'U',  'Ě' => 'E',  'É' => 'E',  'Ç' => 'C',  'Ẁ' => 'W',  'Ċ' => 'C',  'Õ' => 'O',
			'Ṡ' => 'S',  'Ø' => 'O',  'Ģ' => 'G',  'Ŧ' => 'T',  'Ș' => 'S',  'Ė' => 'E',  'Ĉ' => 'C',
			'Ś' => 'S',  'Î' => 'I',  'Ű' => 'U',  'Ć' => 'C',  'Ę' => 'E',  'Ŵ' => 'W',  'Ṫ' => 'T',
			'Ū' => 'U',  'Č' => 'C',  'Ö' => 'O',  'È' => 'E',  'Ŷ' => 'Y',  'Ą' => 'A',  'Ł' => 'L',
			'Ų' => 'U',  'Ů' => 'U',  'Ş' => 'S',  'Ğ' => 'G',  'Ļ' => 'L',  'Ƒ' => 'F',  'Ž' => 'Z',
			'Ẃ' => 'W',  'Ḃ' => 'B',  'Å' => 'A',  'Ì' => 'I',  'Ï' => 'I',  'Ḋ' => 'D',  'Ť' => 'T',
			'Ŗ' => 'R',  'Ä' => 'A',  'Í' => 'I',  'Ŕ' => 'R',  'Ê' => 'E',  'Ü' => 'U',  'Ò' => 'O',
			'Ē' => 'E',  'Ñ' => 'N',  'Ń' => 'N',  'Ĥ' => 'H',  'Ĝ' => 'G',  'Đ' => 'D',  'Ĵ' => 'J',
			'Ÿ' => 'Y',  'Ũ' => 'U',  'Ŭ' => 'U',  'Ư' => 'U',  'Ţ' => 'T',  'Ý' => 'Y',  'Ő' => 'O',
			'Â' => 'A',  'Ľ' => 'L',  'Ẅ' => 'W',  'Ż' => 'Z',  'Ī' => 'I',  'Ã' => 'A',  'Ġ' => 'G',
			'Ṁ' => 'M',  'Ō' => 'O',  'Ĩ' => 'I',  'Ù' => 'U',  'Į' => 'I',  'Ź' => 'Z',  'Á' => 'A',
			'Û' => 'U',  'Þ' => 'Th', 'Ð' => 'Dh', 'Æ' => 'Ae',
		);

		$str = str_replace(
			array_keys($uppercase),
			array_values($uppercase),
			$str
		);
		
		return $str;
	}

    /**
     * Converts a title to human readable string which can be used in the URL
     *
     * @param string $str
     * @return string
     */
    public static function title( $str )
    {
        // Convert HTML entities to normal characters
        $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');

        // Convert accents (Montréal -> Montreal)
        $str = strtolower(Helpers::remove_Accents($str));

        // Some characters should be replaced by words
        $str = str_replace("\$", " dollar ", $str);
        $str = str_replace("+", " plus ", $str);
        $str = str_replace("%", " percent ", $str);
        $str = str_replace("&", " and ", $str);

        // Some characters need to be removed
        $str = str_replace(array("'", "\"", "?", "!", "*", "^", "#", "@", "~"), "", $str);

        // Some characters need to be replaced
        $str = str_replace(array("/", ".", ","), "-", $str);

        // Remove other non alphanumeric characters and convert spaces to dashes
        return preg_replace("/\s+/s", "-", trim(preg_replace("/\W/s", " ", $str)));
    }

    public static function mysql_datetime_to_timestamp($time_str)
    {
        $ftime = strptime($time_str, '%F %T');
        return mktime(
                $ftime['tm_hour'],
                $ftime['tm_min'],
                $ftime['tm_sec'],
                1 ,
                $ftime['tm_yday'] + 1,
                $ftime['tm_year'] + 1900);
    }

    public static function mysql_date_to_timestamp($time_str)
    {
        if ($time_str === null)
            return "";

        $ftime = strptime($time_str, '%F');
        return mktime(
                $ftime['tm_hour'],
                $ftime['tm_min'],
                $ftime['tm_sec'],
                1 ,
                $ftime['tm_yday'] + 1,
                $ftime['tm_year'] + 1900);
    }
};
