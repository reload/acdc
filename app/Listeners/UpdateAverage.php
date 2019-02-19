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
            $deal = $this->activeCampaign->get($event->dealId);
        } catch (Throwable $e) {
            Log::error(sprintf('Error fetching deal %d: %s', $event->dealId, $e->getMessage()));
            return;
        }

        $sum = 0;
        foreach (range(1, 5) as $num) {
            $sum += $deal['custom_field_' . $num];
        }

        $this->activeCampaign->updateCustomField($deal['id'], 'custom_field_8', $sum / 5);
        Log::info(sprintf("Updated deal %d with average.", $deal['id']));
    }
}
