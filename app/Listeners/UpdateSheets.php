<?php

namespace App\Listeners;

use App\ActiveCampaign;
use App\Events\DealUpdated;
use App\Exceptions\UpdateSheetsException;
use App\Sheets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;
use Throwable;

class UpdateSheets
{
    protected $activeCampaign;
    protected $sheets;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(ActiveCampaign $activeCampaign, Sheets $sheets)
    {
        $this->activeCampaign = $activeCampaign;
        $this->sheets = $sheets;
    }

    /**
     * Handle the event.
     *
     * @param  DealUpdated  $event
     * @return void
     */
    public function handle(DealUpdated $event)
    {
        try {
            $deal = $this->activeCampaign->get($event->dealId);
        } catch (Throwable $e) {
            Log::error(sprintf('Error fetching deal %d: %s', $event->dealId, $e->getMessage()));
            return;
        }
        $sheets = YAML::parse(strtr(env('SHEETS', ''), ['\n' => "\n"]));
        if (!is_array($sheets)) {
            throw new UpdateSheetsException('SHEETS should be an array of sheet specs.');
        }
        foreach ($sheets as $sheet) {
            try {
                $this->validateMapping($sheet);

                $fieldMapping = $this->sheets->header($sheet['sheet'], $sheet['tab']);
                if (!in_array('id', $fieldMapping)) {
                    throw new UpdateSheetsException('The "id" field must be mapped.');
                }

                $localeTranslation = isset($sheet['localeTranslate']);
                // Create new row.
                $values = $this->map($this->translateFields($deal, $localeTranslation), $fieldMapping);

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
                $rowNum = array_search($deal['id'], $ids);
                if ($rowNum !== false) {
                    $this->sheets->updateRow($sheet['sheet'], $sheet['tab'], $rowNum + 1, $values);
                    Log::info(sprintf("Updated deal %d in Sheets.", $deal['id']));
                } else {
                    $this->sheets->appendRow($sheet['sheet'], $sheet['tab'], $values);
                    Log::info(sprintf("Added deal %d to Sheets.", $deal['id']));
                }
            } catch (UpdateSheetsException $e) {
                // Use json for logging as it's one line.
                Log::error(sprintf('Error "%s" while mapping %s', $e->getMessage(), json_encode($sheet)));
            }
        }
    }

    public function map($deal, $mapping)
    {
        $values = [];
        for ($i = 0; $i <= max(array_keys($mapping)); $i++) {
            $value = '';
            if (isset($mapping[$i])) {
                $field = $mapping[$i];
                if (isset($deal[$field])) {
                    $value = $deal[$field];
                } else {
                    Log::warning(sprintf('Unknown field "%s".', $field));
                }
            }
            $values[$i] = $value;
        }
        return $values;
    }

    protected function validateMapping($map)
    {
        if (array_keys($map) != ['sheet', 'tab'] && array_keys($map) != ['sheet', 'tab', 'localeTranslate']) {
            throw new UpdateSheetsException('Each sheet spec should contain "sheet" and "tab", and optionally localeTranslate.');
        }
        if (!is_string($map['sheet']) || !is_string($map['tab'])) {
            throw new UpdateSheetsException('Sheet and tab of each sheet spec should be a string.');
        }
    }

    public function translateFields($deal, $localeTranslation = false)
    {
        foreach (['cdate', 'mdate'] as $field) {
            if (isset($deal[$field])) {
                $time = strtotime($deal[$field]);
                if ($localeTranslation) {
                    $deal[$field] = date('Y-m-d H.i.s', $time);
                } else {
                    $deal[$field] = date('Y-m-d H:i:s', $time);
                }
            }
        }

        if (isset($deal['value'])) {
            $deal['value'] = round($deal['value'] / 100);
        }

        // Replace decimal separator.
        if ($localeTranslation) {
            foreach ($deal as $key => $value) {
                if (is_string($value) && preg_match('/^\d+\.\d+$/', $value)) {
                    $deal[$key] = strtr($value, ['.' => ',']);
                }
            }
        }

        return $deal;
    }
}