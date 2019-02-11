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
        $deal = $this->activeCampaign->get($event->dealId);

        $mapping = YAML::parse(env('MAPPING', ''));
        if (!is_array($mapping)) {
            throw new MapperException('Mapping should be an array of mappings');
        }
        foreach ($mapping as $map) {
            try {
                $this->validateMapping($map);

                // Create new row.
                $values = $this->map($deal, $map['map']);

                // See if the row exists.
                $idCol = $map['map']['id'] - 1;
                $sheetData = $this->sheets->data($map['sheet'], $map['tab']);
                $ids = array_map(function ($row) use ($idCol) {
                    return isset($row[$idCol]) ? $row[$idCol] : null;
                }, $sheetData);

                // Update or add the row.
                $rowNum = array_search($deal['id'], $ids);
                if ($rowNum !== false) {
                    $this->sheets->updateRow($map['sheet'], $map['tab'], $rowNum + 1, $values);
                } else {
                    $this->sheets->appendRow($map['sheet'], $map['tab'], $values);
                }
            } catch (MapperException $e) {
                // Use json for logging as it's one line.
                Log::error(sprintf('Error "%s" while mapping %s', $e->getMessage(), json_encode($map)));
            }
        }
    }

    public function map($deal, $map)
    {
        $colMapping = array_flip($map);
        $values = [];
        for ($i = 0; $i < max(array_keys($colMapping)); $i++) {
            $value = '';
            if (isset($colMapping[$i+1])) {
                $field = $colMapping[$i+1];
                if (isset($deal[$field])) {
                    $value = $deal[$field];
                } else {
                    Log::error(sprintf('Unknown field "%s".', $field));
                }
            }
            $values[$i] = $value;
        }
        return $values;
    }

    protected function validateMapping($map)
    {
        if (array_keys($map) != ['sheet', 'tab', 'map']) {
            throw new MapperException('Each mapping should contain "sheet", "tab" and "map", and nothing else.');
        }
        if (!is_string($map['sheet']) || !is_string($map['tab'])) {
            throw new MapperException('Sheet and tab of each mapping should be a string.');
        }
        foreach ($map['map'] as $key => $val) {
            if (!is_string($key) || !is_int($val)) {
                throw new MapperException('Map should consist of string => int pairs.');
            }
        }
        if (!isset($map['map']['id'])) {
            throw new MapperException('The "id" field must be mapped.');
        }
    }
}
