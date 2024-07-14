<?php

namespace DataParser\Service\Parser\Email;

/**
 * Defines common logic or consist of data to prevent from bloating
 */
interface EmailParserInterface
{
    public const REGEX_MATCH_STANDARD_EMAIL                       = '(?<EMAIL>[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,}))';
    public const REGEX_MATCH_AT_ESCAPED_WITH_ROUND_BRACKETS_EMAIL = '(?<ESCAPED_EMAIL>[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*\(at\)[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,}))';
    public const REGEX_MATCH_CLOUDFLARE_EMAIL_PROTECTION_1        = 'email-protection\#(?<ENCODED_EMAIL>.*?)["\']{1}';
    public const REGEX_MATCH_CLOUDFLARE_EMAIL_PROTECTION_2        = 'data-cfemail=["\']{1}(?<ENCODED_EMAIL>.*?)["\']{1}';

    public const REGEX_MATCH_ALL_CLOUDFLARE_EMAIL_PROTECTION = [
        EmailParserInterface::REGEX_MATCH_CLOUDFLARE_EMAIL_PROTECTION_1,
        EmailParserInterface::REGEX_MATCH_CLOUDFLARE_EMAIL_PROTECTION_2,
    ];

}