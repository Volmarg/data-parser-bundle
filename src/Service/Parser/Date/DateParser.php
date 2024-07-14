<?php

namespace DataParser\Service\Parser\Date;

use DateTimeImmutable;
use DateTime;
use Exception;
use TypeError;

/**
 * The purpose of this class is to attempt to extract date from any kind of strings
 */
class DateParser implements DateParserInterface
{

    private const EXTRACTED_DATE_TIME_DAYS_MARGIN = 100;

    /**
     * Will return {@see DateTime} in case of success, null otherwise
     *
     * @param string|null $parsedString
     *
     * @return DateTime|null
     * @throws Exception
     */
    public static function parseDateFromString(?string $parsedString): ?DateTime
    {
        if( empty($parsedString) ){
            return null;
        }

        $dateTime = self::tryUsingDateTimeConstructorToParse($parsedString);
        if( self::validateDateTime($dateTime) ){
            return $dateTime;
        }

        $dateTime = self::tryCheckingJoinedDaysOldString($parsedString);
        if( self::validateDateTime($dateTime) ){
            return $dateTime;
        }

        $dateTime = self::tryUsingTimeStamp($parsedString);
        if( self::validateDateTime($dateTime) ){
            return $dateTime;
        }

        $dateTime = self::tryExtractingByKnownDateFormats($parsedString);
        if (self::validateDateTime($dateTime)) {
            return $dateTime;
        }

        return null;
    }

    /**
     * Will just try to use {@see DateTime} constructor to get the {@see DateTime}
     * @param string $parsedString
     * @return DateTime|null
     * @throws Exception
     */
    private static function tryUsingDateTimeConstructorToParse(string $parsedString): ?DateTime
    {
        try{
            return new DateTime($parsedString);
        }catch(Exception | TypeError){
            return null;
        }
    }

    /**
     * Will try to extract days old by directly taking the number of days from joined strings such as:
     *  - "vor 4 Tagen"
     *
     * @param string $parsedString
     * @return DateTime|null
     */
    private static function tryCheckingJoinedDaysOldString(string $parsedString): ?DateTime
    {
        $days = null;
        foreach(self::DAY_S_STRING_IN_DIFFERENT_LANGUAGES_AND_FORMS as $daysStringInLanguage){
            $regexMatchDaysOldWithJoinedDaysOldString = "#(?<DAYS_COUNT>[\d]+)([\+]?)[ ]{1,}{$daysStringInLanguage}#";
            if( preg_match($regexMatchDaysOldWithJoinedDaysOldString, $parsedString, $allMatches) ){
                $days = $allMatches["DAYS_COUNT"];
                break;
            }
        }

        $dateTime= null;
        if( !is_null($days) ){
            $dateTime = (new DateTime())->modify("-{$days} DAYS");
        }

        return $dateTime;
    }

    /**
     * Will try to obtain {@see DateTime} by using provided value as timestamp
     *
     * @param string|int $timestamp
     * @return DateTime|null
     */
    private static function tryUsingTimeStamp(string | int $timestamp): ?DateTime
    {
        if( !is_numeric($timestamp) ){
            return null;
        }

        $timestamp       = (int)$timestamp;
        $timestampDigits = strlen($timestamp);
        $usedTimestamp   = match ($timestampDigits)
        {
            13      => $timestamp / 1000,
            default => $timestamp,
        };

        try{
            $dateTime = new DateTime();
            $dateTime->setTimestamp($usedTimestamp);
        }catch(Exception){
            return null;
        }

        return $dateTime;
    }

    /**
     * Will try extracting the date by finding certain known date patterns in the provided string
     *
     * @param string $parsedString
     *
     * @return DateTime|null
     */
    public static function tryExtractingByKnownDateFormats(string $parsedString): ?DateTime
    {
        $dayPattern   = '[0-9]{1,}';
        $monthPattern = '[0-1]?[0-9]';
        $yearPattern  = '[0-9]{4}';

        $regexps = [
            "{$dayPattern}.{$monthPattern}.{$yearPattern}", // covers: 11.04.2023
            "{$yearPattern}-{$monthPattern}-{$dayPattern}", // covers: 2023-06-02
        ];

        foreach ($regexps as $regexp) {
            preg_match("#{$regexp}#", $parsedString, $matches);
            if (empty($matches)) {
                continue;
            }

            try {
                $matchingString = $matches[0];
                $dateTime       = new DateTime($matchingString);
                return $dateTime;
            } catch (Exception|TypeError) {
                continue;
            }
        }

        return null;
    }

    /**
     * Will validate if extracted date time makes sense, this is necessary because date obtained by using timestamp
     * in {@see DateTime} constructor CAN result in date being returned but not necessarily correct one
     *
     * @param DateTime|null $dateTime
     * @return bool
     */
    public static function validateDateTime(?DateTime $dateTime): bool
    {
        if( empty($dateTime) ){
            return false;
        }

        $now          = new DateTimeImmutable();
        $minTimeStamp = $now->modify('-' . self::EXTRACTED_DATE_TIME_DAYS_MARGIN . " days")->getTimestamp();
        $maxTimeStamp = $now->modify('+' . self::EXTRACTED_DATE_TIME_DAYS_MARGIN . " days")->getTimestamp();

        return !(
                $dateTime->getTimestamp() < $minTimeStamp
            ||  $dateTime->getTimestamp() > $maxTimeStamp
        );
    }
}