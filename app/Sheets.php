<?php

namespace App;

use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use RuntimeException;
use Throwable;

class Sheets
{

    /**
     * Google Sheets client.
     * @var \Google_Service_Sheets
     */
    protected $sheets;

    public function __construct(Google_Service_Sheets $sheets)
    {
        $this->sheets = $sheets;
    }

    /**
     * Check for spreadsheet existence.
     */
    public function exists(string $spreadsheetId)
    {
        return (bool) $this->get($spreadsheetId);
    }

    /**
     * Get sheets in spreadsheet.
     */
    public function sheets(string $spreadsheetId)
    {
        $spreadsheet = $this->get($spreadsheetId);
        if (!$spreadsheet || !is_array($spreadsheet->sheets)) {
            return null;
        }
        $names = [];
        foreach ($spreadsheet->sheets as $sheet) {
            if (isset($sheet->properties->title)) {
                $names[] = $sheet->properties->title;
            }
        }

        return $names;
    }

    /**
     * Get header from sheet in spreadsheet.
     *
     * Header is the first row.
     */
    public function header(string $spreadsheetId, string $sheetId)
    {
        $spreadsheetValues = $this->getValues($spreadsheetId, $sheetId, true);
        if (!$spreadsheetValues || !is_array($spreadsheetValues->values)) {
            return null;
        }

        if (!isset($spreadsheetValues->values[0])) {
            return [];
        }

        return $spreadsheetValues->values[0];
    }

    /**
     * Get data form sheet.
     */
    public function data(string $spreadsheetId, string $sheetId)
    {
        $spreadsheetValues = $this->getValues($spreadsheetId, $sheetId, false);
        if (!$spreadsheetValues || !is_array($spreadsheetValues->values)) {
            return null;
        }

        if (!isset($spreadsheetValues->values)) {
            return [];
        }

        return $spreadsheetValues->values;
    }

    /**
     * Get sheet.
     *
     * Returns null if not found.
     */
    protected function get(string $spreadsheetId)
    {
        try {
            return $this->sheets->spreadsheets->get($spreadsheetId);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Get sheet content.
     *
     * @param string $spreadsheetId
     *   Id of spreadsheet.
     * @param string $sheetId
     *   Id of sheet.
     * @param bool $headerOnly
     *   Whether to only return header.
     */
    protected function getValues(string $spreadsheetId, string $sheetId, bool $headerOnly = false)
    {
        try {
            return $this->sheets->spreadsheets_values->get(
                $spreadsheetId,
                sprintf("'%s'%s", $sheetId, $headerOnly ? '!1:1' : ''),
                ['majorDimension' => 'ROWS']
            );
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Append row to sheet.
     *
     * @param string $spreadsheetId
     *   Id of spreadsheet.
     * @param string $sheet
     *   Id of sheet.
     * @param array $row
     *   Array of values for the new row.
     */
    public function appendRow(string $spreadsheetId, string $sheetId, array $row)
    {
        // We'd much rather use RAW, but then dates aren't parsed. So until
        // someone rewrites this to be able to switch between RAW and
        // USER_ENTERED depending on content, we'll use USER_ENTERED and
        // translate the decimal separator for numbers.
        $options = ['valueInputOption' => 'USER_ENTERED'];
        $values = new Google_Service_Sheets_ValueRange(['values' => [$row]]);
        $result = $this->sheets->spreadsheets_values->append(
            $spreadsheetId,
            sprintf("'%s'!%s%d:%s%d", $sheetId, 'A', 1,  $this->columnLetter(count($row)), 1),
            $values,
            $options
        );
    }

    /**
     * Update row in sheet.
     *
     * @param string $spreadsheetId
     *   Id of spreadsheet.
     * @param string $sheet
     *   Id of sheet.
     * @param int $rowNum
     *   Row number to update.
     * @param array $row
     *   Array of values for the new row.
     */
    public function updateRow(string $spreadsheetId, string $sheetId, int $rowNum, array $row)
    {
        $options = ['valueInputOption' => 'USER_ENTERED'];
        $values = new Google_Service_Sheets_ValueRange(['values' => [$row]]);
        $result = $this->sheets->spreadsheets_values->update(
            $spreadsheetId,
            sprintf("'%s'!%s%d:%s%d", $sheetId, 'A', $rowNum, $this->columnLetter(count($row)), $rowNum),
            $values,
            $options
        );
    }

    /**
     * Get column letter.
     *
     * @param int $colNum
     *   Column number.
     */
    protected function columnLetter($colNum)
    {
        if ($colNum > 24) {
            throw new RuntimeException('At most 24 columns supported at the moment.');
        }

        return chr($colNum + 64);
    }
}
