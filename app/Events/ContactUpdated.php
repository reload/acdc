<?php

namespace App\Events;

class ContactUpdated
{
    public $contactId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $contactId)
    {
        $this->contactId = $contactId;
    }
}
