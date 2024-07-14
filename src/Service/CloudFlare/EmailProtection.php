<?php

namespace DataParser\Service\CloudFlare;

/**
 * Handles the logic related to the cloudflare email protection
 */
class EmailProtection
{
    /**
     * Taken from:
     * - {@link https://usamaejaz.com/cloudflare-email-decoding/}
     *
     * That's for cases where E-Mail is displayed in DOM as:
     * - email-protection#d6a5b3a4a0bfb5b396bfbbbbb9b4bfbabfb3b8a5b5b9a3a2e4e2f8b2b3
     * - data-cfemail="543931142127353935313e352e7a373b39"
     *
     * @param string $encodedString
     *
     * @return string
     */
    public static function decodeProtectedEmail(string $encodedString): string
    {
        $k = hexdec(substr($encodedString, 0, 2));
        for ($i = 2, $email = ''; $i < strlen($encodedString) - 1; $i += 2) {
            $email .= chr(hexdec(substr($encodedString, $i, 2)) ^ $k);
        }

        return $email;
    }
}