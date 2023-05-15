<?php

namespace WebFramework\Core\Security;

class ProtectService
{
    /**
     * @param array<string> $module_config
     */
    public function __construct(
        private array $module_config,
    ) {
    }

    protected function get_random_bytes(int $iv_len): string
    {
        return openssl_random_pseudo_bytes($iv_len);
    }

    protected function internal_encode_and_auth(string $str): string
    {
        // First encrypt it
        //
        $cipher = 'AES-256-CBC';
        $iv_len = openssl_cipher_iv_length($cipher);
        if ($iv_len === false)
        {
            return '';
        }

        $iv = $this->get_random_bytes($iv_len);
        $key = hash('sha256', $this->module_config['crypt_key'], true);
        $str = openssl_encrypt($str, $cipher, $key, 0, $iv);
        if ($str === false)
        {
            return '';
        }

        $str = strtr(base64_encode($str), '+/=', '._~');
        $iv = strtr(base64_encode($iv), '+/=', '._~');

        $str_hmac = hash_hmac(
            $this->module_config['hash'],
            $iv.$str,
            $this->module_config['hmac_key']
        );

        $full_str = $iv.'-'.$str.'-'.$str_hmac;

        // Add double hyphens every 16 characters for 'line-breaking in e-mail clients'
        //
        $chunks = str_split($full_str, 16);

        return implode('--', $chunks);
    }

    public function encode_and_auth_string(string $value): string
    {
        return $this->internal_encode_and_auth($value);
    }

    /**
     * @param array<mixed> $array
     */
    public function encode_and_auth_array(array $array): string
    {
        $str = json_encode($array);
        if ($str === false)
        {
            return '';
        }

        return $this->internal_encode_and_auth($str);
    }

    protected function internal_decode_and_verify_string(string $str): string|false
    {
        // Remove the double hyphens first
        //
        $str = str_replace('--', '', $str);

        $idx = strpos($str, '-');
        if ($idx === false)
        {
            return false;
        }

        $part_iv = substr($str, 0, $idx);
        $iv = base64_decode(strtr($part_iv, '._~', '+/='));

        $str = substr($str, $idx + 1);

        $idx = strpos($str, '-');
        if ($idx === false)
        {
            return false;
        }

        $part_msg = substr($str, 0, $idx);
        $part_hmac = substr($str, $idx + 1);

        $str_hmac = hash_hmac(
            $this->module_config['hash'],
            $part_iv.$part_msg,
            $this->module_config['hmac_key']
        );

        if ($str_hmac !== $part_hmac)
        {
            return false;
        }

        $key = hash('sha256', $this->module_config['crypt_key'], true);
        $cipher = 'AES-256-CBC';
        $msg = base64_decode(strtr($part_msg, '._~', '+/='));
        $original = openssl_decrypt($msg, $cipher, $key, 0, $iv);

        if ($original === false || !strlen($original))
        {
            return false;
        }

        return $original;
    }

    public function decode_and_verify_string(string $str): string|false
    {
        return $this->internal_decode_and_verify_string($str);
    }

    /**
     * @return array<mixed>|false
     */
    public function decode_and_verify_array(string $str): array|false
    {
        $json_encoded = $this->internal_decode_and_verify_string($str);

        if ($json_encoded === false)
        {
            return false;
        }

        $array = json_decode($json_encoded, true);
        if (!is_array($array))
        {
            return false;
        }

        return $array;
    }
}
