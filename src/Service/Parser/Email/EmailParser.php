<?php

namespace DataParser\Service\Parser\Email;

use DataParser\Service\CloudFlare\EmailProtection;

/**
 * Will attempt to extract email addresses from string
 */
class EmailParser implements EmailParserInterface
{

    /**
     * Will return array of email addresses or null if none will be extracted
     *
     * @param string|null $parsedString
     *
     * @return string[] | null
     */
    public static function parseEmailsFromString(?string $parsedString): ?array
    {
        if( empty($parsedString) ){
            return null;
        }

        $foundStandardEmails    = self::parseStandardEmails($parsedString);
        $foundNonStandardEmails = self::parseNonStandardEmails($parsedString);

        $allFoundEmails = [
            ...$foundStandardEmails,
            ...$foundNonStandardEmails,
        ];

        if (empty($allFoundEmails)) {
            return null;
        }

        return array_filter(array_unique($allFoundEmails));
    }

    /**
     * Will try to obtain E-Mails that are anyhow encoded / escaped / protected from scrapping etc.
     *
     * @param string $parsedContent
     *
     * @return array
     */
    private static function parseNonStandardEmails(string $parsedContent): array
    {
        $emailsEscapedWithRoundBrackets = self::findAtEscapedWithRoundBracketsEmails($parsedContent);
        $cloudflareProtectedEmails      = self::parseCloudFlareEmails($parsedContent);

        $foundEmails = [
            ...$emailsEscapedWithRoundBrackets,
            ...$cloudflareProtectedEmails
        ];

        return $foundEmails;
    }

    /**
     * Will try to obtain emails that are escaped with `(at)` instead of `@`
     *
     * @param string $parsedString
     *
     * @return array
     */
    private static function findAtEscapedWithRoundBracketsEmails(string $parsedString): array
    {
        $foundEmails = [];
        preg_match_all('#' . EmailParserInterface::REGEX_MATCH_AT_ESCAPED_WITH_ROUND_BRACKETS_EMAIL . '#', $parsedString, $matches);

        $escapedEmailsFromRegex = $matches['ESCAPED_EMAIL'] ?? [];
        foreach ($escapedEmailsFromRegex as $escapedEmailFromRegex) {
            $normalizedEmail = str_replace("(at)", "@", $escapedEmailFromRegex);

            if (filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
                $foundEmails[] = $normalizedEmail;
            }
        }

        return $foundEmails;
    }

    /**
     * Will try to obtain normal E-Mails, that have proper email syntax
     *
     * @param string $parsedString
     *
     * @return array
     */
    private static function parseStandardEmails(string $parsedString): array
    {
        $allFoundEmails = [];
        preg_match_all('#' . EmailParserInterface::REGEX_MATCH_STANDARD_EMAIL . '#', $parsedString, $matches);

        $emailsFromRegex = $matches['EMAIL'] ?? [];
        foreach ($emailsFromRegex as $emailFromRegex) {

            if (filter_var($emailFromRegex, FILTER_VALIDATE_EMAIL)) {
                $allFoundEmails[] = $emailFromRegex;
            }
        }

        return $allFoundEmails;
    }

    /**
     * Will try to obtain the cloudflare protected emails
     *
     * @param string $parsedString
     *
     * @return array
     */
    private static function parseCloudFlareEmails(string $parsedString): array
    {
        $allEmails = [];
        foreach (EmailParserInterface::REGEX_MATCH_ALL_CLOUDFLARE_EMAIL_PROTECTION as $regexp) {
            preg_match_all('#' . $regexp . '#', $parsedString, $matches);

            $encodedEmails = $matches['ENCODED_EMAIL'] ?? [];
            foreach ($encodedEmails as $encodedEmail) {

                $decodedEmail = EmailProtection::decodeProtectedEmail($encodedEmail);
                if (filter_var($decodedEmail, FILTER_VALIDATE_EMAIL)) {
                    $allEmails[] = $decodedEmail;
                }
            }
        }

        return $allEmails;
    }
}