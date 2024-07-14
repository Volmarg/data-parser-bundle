<?php

namespace DataParser\Service\Parser\Text;

use DataParser\Service\Parser\Date\DateParser;

/**
 * Handles parsing the text in terms of finding words etc.
 */
class TextParser
{
    /**
     * Will check if target string contains given word.
     * This is not {@see strstr} but rather check if the real word is there, not just a substring
     *
     * @example "I like chocolate-cookie"
     *          - {@see strstr} returns true when checking `chocolate` as it operates on substring,
     *          - This function will return false, because `chocolate` is not standalone word, rather the word is "chocolate-cookie"
     *
     * @param string $needle
     * @param string $haystack
     * @return bool
     */
    public static function hasWord(string $haystack, string $needle): bool
    {
        $strippedSpecialChars = str_replace(["/", "\\", "-", "_"], " ", $haystack);
        $regexpSafeNeedle = preg_quote($needle);

        // Covers given cases: (bank), [bank], 'bank', "bank"
        $charactersWrapped = "[\(\"\'\[]{1}{$needle}[\)\"\'\]]{1}";

        $regexPattern = "#[ ]{0}{$regexpSafeNeedle} | {$regexpSafeNeedle} | {$regexpSafeNeedle}[ ]{0} |{$charactersWrapped}#i";
        return preg_match($regexPattern, $strippedSpecialChars);
    }

    /**
     * In general look on {@see TextParser::getRangeValues()}.
     * If successfully parsed then will return lower range value, else null.
     *
     * @param string $parsedString
     *
     * @return float|null
     */
    public static function getLowerRangeValue(string $parsedString): ?float
    {
        $parsingResult = self::getRangeValues($parsedString);
        if (empty($parsingResult)) {
            return null;
        }

        return array_pop($parsingResult);
    }

    /**
     * In general look on {@see TextParser::getRangeValues()}.
     * If successfully parsed then will return higher range value, else null.
     *
     * @param string $parsedString
     *
     * @return float|null
     */
    public static function getHigherRangeValue(string $parsedString): ?float
    {
        $parsingResult = self::getRangeValues($parsedString);
        if (empty($parsingResult)) {
            return null;
        }

        $reversedArr = array_reverse($parsingResult);

        return array_pop($reversedArr);
    }

    /**
     * In general look on {@see TextParser::getMinMaxValues()}.
     * If successfully parsed then will return the lowest value, else null.
     *
     * @param string $parsedString
     *
     * @return float|null
     */
    public static function getMinValue(string $parsedString): ?float
    {
        $parsingResult = self::getMinMaxValues($parsedString);
        if (empty($parsingResult)) {
            return null;
        }

        return array_pop($parsingResult);
    }

    /**
     * In general look on {@see TextParser::getMinMaxValues()}.
     * If successfully parsed then will return highest found value, else null.
     *
     * @param string $parsedString
     *
     * @return float|null
     */
    public static function getMaxValue(string $parsedString): ?float
    {
        $parsingResult = self::getMinMaxValues($parsedString);
        if (empty($parsingResult)) {
            return null;
        }

        $reversedArr = array_reverse($parsingResult);

        return array_pop($reversedArr);
    }


    /**
     * Takes the provided string and attempts to extract numbers from it. If it extracts some then returns them,
     * else returns null
     *
     * @param string $string
     *
     * @return int|null
     */
    public static function extractNumbers(string $string): ?int
    {
        $groupName = "NUMBERS";
        $regex = "#(?<{$groupName}>([\d]+([ \.\-,]?){0,}[\d]+))#";

        preg_match($regex, $string, $matches);
        $numbersMatch = $matches[$groupName] ?? null;
        if (empty($numbersMatch)) {
            return null;
        }

        $filteredOutMatch = str_replace([' ', ',', '.', '-'], '', $numbersMatch);
        if(empty($filteredOutMatch)){
            return null;
        }

        $numbers = (int)$filteredOutMatch;

        return $numbers;
    }

    /**
     * Does not only normal trim, but also removes the spacebar represented by html entity, removes new lines etc.
     *
     * @param string $targetString
     *
     * @return string
     */
    public static function deepTrim(string $targetString): string
    {
        $changedString = str_replace(["&nbsp", "\n", ' '], "", $targetString);
        $changedString = preg_replace("#[ ]{1,}#", " ", $changedString);
        $changedString = trim($changedString);

        return $changedString;
    }

    /**
     * Parse string and try getting the date string from it
     *
     * @param string $targetString
     *
     * @return string|null
     */
    public static function obtainDateString(string $targetString): ?string
    {
        $dateTime = DateParser::tryExtractingByKnownDateFormats($targetString);
        if (DateParser::validateDateTime($dateTime)) {
            return $dateTime->format("Y-m-d");
        }

        return null;
    }

    /**
     * Tries to find lowest and highest values in the string. Possible returned values scenarios:
     * - empty array (no numbers were found at all),
     * - one element in array (unknown if it's min or max),
     * - two elements (first is LOW, second is HIGH)
     *
     * This is not the same as {@see self::getRangeValues}.
     * The mentioned method looks for range first then splits it,
     * Current method just looks for any numbers in string, sorts it out,
     * then takes the lowest and highest value and returns both in array
     *
     *
     * @param string $parsedString
     *
     * @return array
     */
    private static function getMinMaxValues(string $parsedString): array
    {
        $regex = '(?<VALUE>([\d+  ,\.])*)';
        preg_match_all("#{$regex}#m", $parsedString, $matches);

        $filtered = [];
        $values   = $matches['VALUE'] ?? [];
        foreach ($values as $value) {
            if (empty(trim($value))) {
                continue;
            }
            $filtered[] = trim(str_replace([" ", " ", ",", "."], "", $value));
        }

        $filtered = array_map(
            fn(string $value) => (float)$value,
            $filtered
        );

        rsort($filtered);
        if (count($filtered) <= 2) {
            return $filtered;
        }

        $reverseFiltered = array_reverse($filtered);

        $first = (float)array_pop($filtered);
        $last  = (float)array_pop($reverseFiltered);

        return [$first, $last];
    }

    /**
     * Attempt to find range in the string, and split it into low / high value.
     * Returns array:
     * - empty if no range was found,
     * - empty if more / less than expected (2) values were found,
     * - 2 elements if extraction went fine where 1st element is lower value, and 2nd is higher value
     *
     * @param string $parsedString
     *
     * @return array
     */
    private static function getRangeValues(string $parsedString): array
    {
        if (empty($parsedString)) {
            return [];
        }

        $rangeSeparationCharacterSets = [
            "–","-", "->", "<-", "<->"
        ];

        $lowerValueGroupName  = "LOWER_VALUE";
        $higherValueGroupName = "HIGHER_VALUE";

        $trimmedString = TextParser::deepTrim($parsedString);
        $foundMatch    = [];
        foreach ($rangeSeparationCharacterSets as $separationCharacter) {
            $numberMatchRegexPart = "([\d]+([ ]?[\d]?)?)+";
            $rangeRegex           = "#(?<{$lowerValueGroupName}>{$numberMatchRegexPart})[ ]?{$separationCharacter}[ ]?(?<{$higherValueGroupName}>{$numberMatchRegexPart})#si";

            preg_match($rangeRegex, $trimmedString, $matches);

            if (
                    empty($matches)
                ||  !array_key_exists($lowerValueGroupName, $matches)
                ||  !array_key_exists($higherValueGroupName, $matches)
            ) {
                continue;
            }

            $callback    = fn(string $str) => str_replace(" ", "", $str);
            $lowerValue  = $callback($matches[$lowerValueGroupName]);
            $higherValue = $callback($matches[$higherValueGroupName]);

            $foundMatch = [$lowerValue, $higherValue];

            break;
        }

        if (empty($foundMatch)) {
            return [];
        }

        return $foundMatch;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public static function br2nl(string $string): string
    {
        return preg_replace('#<[ ]?br[ ]?/>#', "\n", $string);
    }
}