<?php

namespace App\Listeners;

use App\ActiveCampaign;
use App\Events\ContactUpdated;
use App\Exceptions\UpdateSheetsException;
use App\SheetWriter;
use App\Sheets;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;
use Throwable;

class UpdateContactSheets extends SheetWriter
{

    /**
     * Handle the event.
     *
     * @param  ContactUpdated  $event
     * @return void
     */
    public function handle(ContactUpdated $event)
    {
        try {
            $contact = $this->activeCampaign->getContact($event->contactId);
        } catch (Throwable $e) {
            Log::error(sprintf('Error fetching contact %d: %s', $event->contactId, $e->getMessage()));
            return;
        }
        $sheets = YAML::parse(strtr(env('CONTACT_SHEETS', ''), ['\n' => "\n"]));
        if (!is_array($sheets)) {
            throw new UpdateSheetsException('CONTACT_SHEETS should be an array of sheet specs.');
        }
        foreach ($sheets as $sheet) {
            try {
                if ($this->updateSheet($sheet, $contact) == SheetWriter::UPDATED) {
                    Log::info(sprintf("Updated contact %d in Sheets.", $contact['id']));
                } else {
                    Log::info(sprintf("Added contact %d to Sheets.", $contact['id']));
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
     * @param array $contact
     *   Contact to translate.
     * @param bool $localeTranslation
     *   Whether it's a danish sheet.
     *
     * @return array
     *   The translated contact.
     */
    public function translateFields($contact, $localeTranslation = false)
    {
        // Replace decimal separator.
        if ($localeTranslation) {
            foreach ($contact as $key => $value) {
                if (is_string($value) && preg_match('/^\d+\.\d+$/', $value)) {
                    $contact[$key] = strtr($value, ['.' => ',']);
                }
            }
        }

        return $contact;
    }
}
