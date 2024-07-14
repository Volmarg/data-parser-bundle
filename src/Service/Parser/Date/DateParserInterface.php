<?php

namespace DataParser\Service\Parser\Date;

/**
 * Defines common logic or consist of data to prevent from bloating
 */
interface DateParserInterface
{
    const DAY_S_STRING_IN_DIFFERENT_LANGUAGES_AND_FORMS = [
        // German
        self::DAYS_V1_DE,
        self::DAYS_V2_DE,
        self::DAY_V1_DE,
        self::DAY_V2_DE,

        // English
        self::DAYS_V1_ENG,
        self::DAY_V1_ENG,

        // Polish
        self::DAYS_V1_PL,
        self::DAY_V1_PL,
        self::DAY_V2_PL,
    ];

    // German
    const DAYS_V1_DE = "Tagen";
    const DAYS_V2_DE = "Tages";
    const DAY_V1_DE  = "Tage";
    const DAY_V2_DE  = "Tag";

    // English
    const DAYS_V1_ENG = "days";
    const DAY_V1_ENG  = "day";

    // Polish
    const DAYS_V1_PL = "dni";
    const DAY_V1_PL  = "dzień";
    const DAY_V2_PL  = "dnia";
}