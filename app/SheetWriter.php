<?php

namespace App;

use App\Exceptions\UpdateSheetsException;
use App\Sheets;
use Illuminate\Support\Facades\Log;

class SheetWriter
{
    const UPDATED = 1;
    const INSERTED = 2;

    protected $sheets;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Sheets $sheets)
    {
        $this->sheets = $sheets;
    }

    /**
     * Update sheet.
     *
     * Adds or updates a row in the sheet.
     *
     * @param array $sheet
     *   Sheet spoc containing "sheet", "tab" and optionally "localeTranslate".
     * @param array $data
     *   Row data to update/insert.
     */
    public function updateSheet($sheet, $data, FieldTranslator $translator)
    {
        $this->validateMapping($sheet);

        $fieldMapping = $this->sheets->header($sheet['sheet'], $sheet['tab']);
        if (!in_array('id', $fieldMapping)) {
            throw new UpdateSheetsException('The "id" field must be mapped.');
        }

        $localeTranslation = isset($sheet['localeTranslate']);
        // Create new row.
        $values = $this->map($translator->translateFields($data, $localeTranslation), $fieldMapping);

        // See if the row exists.
        $idCol = array_search('id', $fieldMapping);
        $sheetData = $this->sheets->data($sheet['sheet'], $sheet['tab']);
        if (!$sheetData) {
            throw new UpdateSheetsException('Error fetching data from Sheets.');
        }
        $ids = array_map(function ($row) use ($idCol) {
            return isset($row[$idCol]) ? $row[$idCol] : null;
        }, $sheetData);

        // Update or add the row.
        $rowNum = array_search($data['id'], $ids);
        if ($rowNum !== false) {
            $this->sheets->updateRow($sheet['sheet'], $sheet['tab'], $rowNum + 1, $values);
            return self::UPDATED;
        } else {
            $this->sheets->appendRow($sheet['sheet'], $sheet['tab'], $values);
            return self::INSERTED;
        }
    }

    /**
     * Validate mapping configuration.
     */
    protected function validateMapping($map)
    {
        if (array_keys($map) != ['sheet', 'tab'] && array_keys($map) != ['sheet', 'tab', 'localeTranslate']) {
            throw new UpdateSheetsException('Each sheet spec should contain "sheet" and "tab", and optionally localeTranslate.');
        }
        if (!is_string($map['sheet']) || !is_string($map['tab'])) {
            throw new UpdateSheetsException('Sheet and tab of each sheet spec should be a string.');
        }
    }

    /**
     * Map fields using given mapping.
     *
     * Creates a new array where the values are collected from the array in the
     * order defined by the mapping. For instance, given that the array is ['id' =>
     * 12, 'name' => 'banana'], and a mapping of ['name', 'id'] it will return
     * ['banana', 12].
     *
     * @param array $data
     *   The data to map.
     * @param array $mapping
     *   The order of keys in $data to return.
     *
     * @return array
     *   The mapped data.
     */
    public function map($data, $mapping)
    {
        $values = [];
        for ($i = 0; $i <= max(array_keys($mapping)); $i++) {
            $value = '';
            if (isset($mapping[$i])) {
                $field = $mapping[$i];
                if (isset($data[$field])) {
                    $value = $data[$field];
                } else {
                    Log::warning(sprintf('Unknown field "%s".', $field));
                }
            }
            $values[$i] = $value;
        }
        return $values;
    }
}
