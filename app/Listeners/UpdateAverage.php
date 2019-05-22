<?php

namespace App\Listeners;

use App\ActiveCampaign;
use App\Events\DealUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateAverage
{
    protected $activeCampaign;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(ActiveCampaign $activeCampaign)
    {
        $this->activeCampaign = $activeCampaign;
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

        $sum = 0;
        $count = 0;
        // Sum the field values and use count to keep track how many were
        // actually defined so we can calculate the average.
        foreach (range(1, 5) as $num) {
            if (isset($deal['custom_field_' . $num])) {
                $sum += $deal['custom_field_' . $num];
                $count++;
            }
        }

        $average = $count > 0 ? $sum / $count : $sum;

        $this->activeCampaign->updateDealCustomField($deal['id'], 'custom_field_8', $average);
        Log::info(sprintf("Updated deal %d with average.", $deal['id']));
    }
}
