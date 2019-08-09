<?php

namespace App;

trait TranslatesFields
{
    /**
     * Translate date into format understood by Sheets.
     *
     * Also adjusts for timezones.
     */
    public function translateDate($date, $localeTranslation)
    {
        $dateTime = new \DateTime($date);
        $timezone = $localeTranslation ?
            new \DateTimeZone('Europe/Copenhagen') :
            $timezone = new \DateTimeZone('UTC');
        $format = $localeTranslation ?
            'Y-m-d H.i.s' :
            'Y-m-d H:i:s';
        $dateTime->setTimezone($timezone);

        return $dateTime->format($format);
    }

    /**
     * Fix numeric values to use the proper decimal separator.
     */
    public function translateDecimalSeperator($value, $localeTranslation)
    {
        if ($localeTranslation && is_string($value) && preg_match('/^\d+\.\d+$/', $value)) {
            return strtr($value, ['.' => ',']);
        }

        return $value;
    }

    /**
     * Fix monetary amounts.
     *
     * AC uses lowest denominator for currencies.
     */
    public function translateAmount($value)
    {
        return round($value / 100);
    }
}
