<?php
class WFSecurity
{
    private $module_config;

    function __construct($module_config)
    {
        $this->module_config = $module_config;
    }

    function get_csrf_token()
    {
        if (!isset($_SESSION['csrf_token']) || strlen($_SESSION['csrf_token']) != 16)
            $_SESSION['csrf_token'] = openssl_random_pseudo_bytes(16);

        $token = $_SESSION['csrf_token'];
        $xor = openssl_random_pseudo_bytes(16);
        for ($i = 0; $i < 16; $i++)
            $token[$i] = chr(ord($xor[$i]) ^ ord($token[$i]));

        return bin2hex($xor).bin2hex($token);
    }

    function validate_csrf_token($token)
    {
        if(!isset($_SESSION['csrf_token']))
            return FALSE;

        $check = $_SESSION['csrf_token'];
        $value = $token;
        if (strlen($value) != 16 * 4 || strlen($check) != 16)
            return;

        $xor = pack("H*" , substr($value, 0, 16 * 2));
        $token = pack("H*", substr($value, 16 * 2, 16 * 2));

        // Slow compare (time-constant)
        $diff = 0;
        for ($i = 0; $i < 16; $i++)
        {
            $token[$i] = chr(ord($xor[$i]) ^ ord($token[$i]));
            $diff |= ord($token[$i]) ^ ord($check[$i]);
        }

        return ($diff === 0);
    }

    function urlencode_and_auth_array($array)
    {
        return urlencode($this->encode_and_auth_array($array));
    }

    function encode_and_auth_array($array)
    {
        $str = json_encode($array);

        // First encrypt it
        //
        $cipher = 'AES-256-CBC';
        $iv_len = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($iv_len);
        $key = hash('sha256', $this->module_config['crypt_key'], true);
        $str = openssl_encrypt($str, $cipher, $key, 0, $iv);

        $str = base64_encode($str);
        $iv = base64_encode($iv);

        $str_hmac = hash_hmac($this->module_config['hash'], $iv.$str,
                              $this->module_config['hmac_key']);

        return $iv.":".$str.":".$str_hmac;
    }

    function urldecode_and_verify_array($str)
    {
        $urldecoded = urldecode($str);

        return $this->decode_and_verify_array($urldecoded);
    }

    function decode_and_verify_array($str)
    {
        $idx = strpos($str, ":");
        if ($idx === false)
            return "";

        $part_iv = substr($str, 0, $idx);
        $iv = base64_decode($part_iv);

        $str = substr($str, $idx + 1);

        $idx = strpos($str, ":");
        if ($idx === FALSE)
            return false;

        $part_msg = substr($str, 0, $idx);
        $part_hmac = substr($str, $idx + 1);

        $str_hmac = hash_hmac($this->module_config['hash'], $part_iv.$part_msg,
                              $this->module_config['hmac_key']);

        if ($str_hmac !== $part_hmac)
        {
            $framework = WF::get_framework();
            $framework->add_blacklist_entry('hmac-mismatch', 4);
            return "";
        }

        $key = hash('sha256', $this->module_config['crypt_key'], true);
        $cipher = 'AES-256-CBC';
        $json_encoded = openssl_decrypt(base64_decode($part_msg), $cipher, $key, 0, $iv);

        if (!strlen($json_encoded))
            return false;

        $array = json_decode($json_encoded, true);
        if (!is_array($array))
            return false;

        return $array;
    }

    function get_auth_config($name)
    {
        $auth_config_file = WF::$site_includes.'/auth/'.$name.'.php';
        if (!file_exists($auth_config_file))
            die("Auth Config {$name} does not exist");

        $auth_config = require($auth_config_file);
        WF::verify(is_array($auth_config) || strlen($auth_config), 'Auth Config '.$name.' invalid');

        return $auth_config;
    }
};
?>
