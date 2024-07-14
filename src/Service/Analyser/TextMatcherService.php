<?php

namespace DataParser\Service\Analyser;

/**
 * Basically a bunch of logic for checking if "text A" contains "text B" etc.
 */
class TextMatcherService
{
    /**
     * Check if the analysed string contains any variation of "remote" word in any of provided variants:
     * - be it direct string,
     * - kebab case string variant etc.
     *
     * @param array  $remoteSpellingVariants
     * @param string $analysedString
     *
     * @return bool
     */
    public static function matchesRemoteWords(array $remoteSpellingVariants, string $analysedString): bool
    {
        foreach ($remoteSpellingVariants as $remoteSpelling) {
            $gluedString = preg_replace("#\s#", "", $remoteSpelling);
            $kebabString = str_replace(" ", "-", $remoteSpelling);

            $stringCombinations = [
                $remoteSpelling,
                $gluedString,
                $kebabString,
            ];

            foreach ($stringCombinations as $stringCombination) {
                if (stristr($analysedString, $stringCombination)) {
                    return true;
                }
            }
        }

        return false;
    }

}