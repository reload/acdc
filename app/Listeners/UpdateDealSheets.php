<?php

namespace App\Listeners;

use App\ActiveCampaign;
use App\Events\DealUpdated;
use App\Exceptions\UpdateSheetsException;
use App\FieldTranslator;
use App\SheetWriter;
use App\Sheets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;
use Throwable;

class UpdateDealSheets implements FieldTranslator
{

    protected $activeCampaign;
    protected $sheetWriter;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(ActiveCampaign $activeCampaign, SheetWriter $sheetWriter)
    {
        $this->activeCampaign = $activeCampaign;
        $this->sheetWriter = $sheetWriter;
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
            $deal = $this->activeCampaign->getDeal($event->dealId);
        } catch (Throwable $e) {
            Log::error(sprintf('Error fetching deal %d: %s', $event->dealId, $e->getMessage()));
            return;
        }
        $sheets = YAML::parse(strtr(env('DEAL_SHEETS', ''), ['\n' => "\n"]));
        if (!is_array($sheets)) {
            throw new UpdateSheetsException('DEAL_SHEETS should be an array of sheet specs.');
        }
        foreach ($sheets as $sheet) {
            try {
                if ($this->sheetWriter->updateSheet($sheet, $deal, $this) == SheetWriter::UPDATED) {
                    Log::info(sprintf("Updated deal %d in Sheets.", $deal['id']));
                } else {
                    Log::info(sprintf("Added deal %d to Sheets.", $deal['id']));
                }
            } catch (UpdateSheetsException $e) {
                // Use json for logging as it's one line.
                Log::error(sprintf('Error "%s" while mapping %s', $e->getMessage(), json_encode($sheet)));
            }
        }
    }

    /**
     * "Translate" fields.
     *
     * Sheets has strong opinions on what it'll consider dates and decimal
     * numbers depending on the locale of the sheet. Sadly there's no
     * canonical representation, so we have to covert differently depending on
     * whether it's an English sheet or danish one.
     *
     * @param array $deal
     *   Deal to translate.
     * @param bool $localeTranslation
     *   Whether it's a danish sheet.
     *
     * @return array
     *   The translated deal.
     */
    public function translateFields($deal, $localeTranslation = false)
    {
        // Render dates so sheets will see them as such.
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

        // AC uses lowest denominator for currencies, translate to everyday
        // use.
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
