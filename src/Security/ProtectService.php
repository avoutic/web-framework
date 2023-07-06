<?php

namespace WebFramework\Security;

class ProtectService
{
    /**
     * @param array<string> $moduleConfig
     */
    public function __construct(
        private array $moduleConfig,
    ) {
    }

    protected function getRandomBytes(int $ivLen): string
    {
        return openssl_random_pseudo_bytes($ivLen);
    }

    protected function internalPackString(string $str): string
    {
        // First encrypt it
        //
        $cipher = 'AES-256-CBC';
        $ivLen = openssl_cipher_iv_length($cipher);
        if ($ivLen === false)
        {
            return '';
        }

        $iv = $this->getRandomBytes($ivLen);
        $key = hash('sha256', $this->moduleConfig['crypt_key'], true);
        $str = openssl_encrypt($str, $cipher, $key, 0, $iv);
        if ($str === false)
        {
            return '';
        }

        $str = strtr(base64_encode($str), '+/=', '._~');
        $iv = strtr(base64_encode($iv), '+/=', '._~');

        $strHmac = hash_hmac(
            $this->moduleConfig['hash'],
            $iv.$str,
            $this->moduleConfig['hmac_key']
        );

        $fullStr = $iv.'-'.$str.'-'.$strHmac;

        // Add double hyphens every 16 characters for 'line-breaking in e-mail clients'
        //
        $chunks = str_split($fullStr, 16);

        return implode('--', $chunks);
    }

    public function packString(string $value): string
    {
        return $this->internalPackString($value);
    }

    /**
     * @param array<mixed> $array
     */
    public function packArray(array $array): string
    {
        $str = json_encode($array);
        if ($str === false)
        {
            return '';
        }

        return $this->internalPackString($str);
    }

    protected function internalUnpackString(string $str): string|false
    {
        // Remove the double hyphens first
        //
        $str = str_replace('--', '', $str);

        $idx = strpos($str, '-');
        if ($idx === false)
        {
            return false;
        }

        $partIv = substr($str, 0, $idx);
        $iv = base64_decode(strtr($partIv, '._~', '+/='));

        $str = substr($str, $idx + 1);

        $idx = strpos($str, '-');
        if ($idx === false)
        {
            return false;
        }

        $partMsg = substr($str, 0, $idx);
        $partHmac = substr($str, $idx + 1);

        $strHmac = hash_hmac(
            $this->moduleConfig['hash'],
            $partIv.$partMsg,
            $this->moduleConfig['hmac_key']
        );

        if ($strHmac !== $partHmac)
        {
            return false;
        }

        $key = hash('sha256', $this->moduleConfig['crypt_key'], true);
        $cipher = 'AES-256-CBC';
        $msg = base64_decode(strtr($partMsg, '._~', '+/='));
        $original = openssl_decrypt($msg, $cipher, $key, 0, $iv);

        if ($original === false || !strlen($original))
        {
            return false;
        }

        return $original;
    }

    public function unpackString(string $str): string|false
    {
        return $this->internalUnpackString($str);
    }

    /**
     * @return array<mixed>|false
     */
    public function unpackArray(string $str): array|false
    {
        $jsonEncoded = $this->internalUnpackString($str);

        if ($jsonEncoded === false)
        {
            return false;
        }

        $array = json_decode($jsonEncoded, true);
        if (!is_array($array))
        {
            return false;
        }

        return $array;
    }
}
