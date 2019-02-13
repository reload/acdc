<?php

namespace App\Listeners;

use App\ActiveCampaign;
use App\Events\DealUpdated;
use App\Exceptions\MapperException;
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
        $mapping = YAML::parse(strtr(env('SHEETS', ''), ['\n' => "\n"]));
        if (!is_array($mapping)) {
            throw new MapperException('Mapping should be an array of mappings.');
        }
        foreach ($mapping as $map) {
            try {
                $this->validateMapping($map);

                $fieldMapping = $this->sheets->header($map['sheet'], $map['tab']);
                if (!in_array('id', $fieldMapping)) {
                    throw new MapperException('The "id" field must be mapped.');
                }

                // Create new row.
                $values = $this->map($this->translateFields($deal), $fieldMapping);

                // See if the row exists.
                $idCol = array_search('id', $fieldMapping);
                $sheetData = $this->sheets->data($map['sheet'], $map['tab']);
                if (!$sheetData) {
                    throw new MapperException('Error fetching data from Sheets.');
                }
                $ids = array_map(function ($row) use ($idCol) {
                    return isset($row[$idCol]) ? $row[$idCol] : null;
                }, $sheetData);

                // Update or add the row.
                $rowNum = array_search($deal['id'], $ids);
                if ($rowNum !== false) {
                    $this->sheets->updateRow($map['sheet'], $map['tab'], $rowNum + 1, $values);
                    Log::info(sprintf("Updated deal %d in Sheets.", $deal['id']));
                } else {
                    $this->sheets->appendRow($map['sheet'], $map['tab'], $values);
                    Log::info(sprintf("Added deal %d to Sheets.", $deal['id']));
                }
            } catch (MapperException $e) {
                // Use json for logging as it's one line.
                Log::error(sprintf('Error "%s" while mapping %s', $e->getMessage(), json_encode($map)));
            }
        }
    }

    public function map($deal, $map)
    {
        $values = [];
        for ($i = 0; $i <= max(array_keys($map)); $i++) {
            $value = '';
            if (isset($map[$i])) {
                $field = $map[$i];
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
        if (array_keys($map) != ['sheet', 'tab']) {
            throw new MapperException('Each mapping should contain "sheet" and "tab", and nothing else.');
        }
        if (!is_string($map['sheet']) || !is_string($map['tab'])) {
            throw new MapperException('Sheet and tab of each mapping should be a string.');
        }
    }

    public function translateFields($deal)
    {
        foreach (['cdate', 'mdate'] as $field) {
            if (isset($deal[$field])) {
                $time = strtotime($deal[$field]);
                $deal[$field] = date('Y-m-d H:i:s', $time);
            }
        }

        return $deal;
    }
}
