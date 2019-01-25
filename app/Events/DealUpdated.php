<?php

namespace App\Events;

class DealUpdated
{
    public $dealId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $dealId)
    {
        $this->dealId = $dealId;
    }
}
