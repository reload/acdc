<?php

namespace App;

interface FieldTranslator
{
    /**
     * "Translate" fields.
     *
     * Sheets has strong opinions on what it'll consider dates and decimal
     * numbers depending on the locale of the sheet. Sadly there's no
     * canonical representation, so we have to covert differently depending on
     * whether it's an English sheet or danish one.
     *
     * @param array $data
     *   Data to translate.
     * @param bool $localeTranslation
     *   Whether it's a danish sheet.
     *
     * @return array
     *   The translated data.
     */
    public function translateFields($data, $localeTranslation = false);
}
