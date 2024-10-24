<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Security;

/**
 * Class ProtectService.
 *
 * Provides encryption and decryption services for sensitive data.
 */
class ProtectService
{
    /**
     * ProtectService constructor.
     *
     * @param RandomProvider $randomProvider The random provider service
     * @param array<string>  $moduleConfig   The module configuration
     */
    public function __construct(
        private RandomProvider $randomProvider,
        private array $moduleConfig,
    ) {}

    /**
     * Encrypt and encode a string.
     *
     * @param string $str The string to encrypt and encode
     *
     * @return string The encrypted and encoded string
     */
    public function packString(string $str): string
    {
        // First encrypt it
        //
        $cipher = 'AES-256-CBC';
        $ivLen = openssl_cipher_iv_length($cipher);
        if ($ivLen === false)
        {
            return '';
        }

        $iv = $this->randomProvider->getRandom($ivLen);
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

    /**
     * Encrypt and encode an array.
     *
     * @param array<mixed> $array The array to encrypt and encode
     *
     * @return string The encrypted and encoded string
     */
    public function packArray(array $array): string
    {
        $str = json_encode($array);
        if ($str === false)
        {
            return '';
        }

        return $this->packString($str);
    }

    /**
     * Decrypt and decode a string.
     *
     * @param string $str The string to decrypt and decode
     *
     * @return false|string The decrypted and decoded string, or false on failure
     */
    public function unpackString(string $str): false|string
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

    /**
     * Decrypt and decode an array.
     *
     * @param string $str The string to decrypt and decode
     *
     * @return array<mixed>|false The decrypted and decoded array, or false on failure
     */
    public function unpackArray(string $str): array|false
    {
        $jsonEncoded = $this->unpackString($str);

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
