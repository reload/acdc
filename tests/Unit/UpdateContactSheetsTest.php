<?php

namespace Tests\Unit;

use App\ActiveCampaign;
use App\Events\ContactUpdated;
use App\Listeners\UpdateContactSheets;
use App\SheetWriter;
use App\Sheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UpdateContactSheetsTest extends TestCase
{
    public function setUp() : void
    {
        parent::setUp();
        // Suppress log output.
        Log::spy();

        $this->ac = $this->prophesize(ActiveCampaign::class);
        $this->writer = $this->prophesize(SheetWriter::class);

        $this->updater = new UpdateContactSheets($this->ac->reveal(), $this->writer->reveal());
    }

    public function testDateTranslation()
    {
        $contact = [
            'untranslated' => '2019-02-13T03:12:08-06:00',
            'cdate' => '2019-02-13T03:12:08-06:00',
            'adate' => '2019-02-13T03:12:08-06:00',
            'edate' => '2019-02-13T03:12:08-06:00',
            'udate' => '2019-02-13T03:12:08-06:00',
        ];

        $expected = [
            'untranslated' => '2019-02-13T03:12:08-06:00',
            'cdate' => '2019-02-13 09:12:08',
            'adate' => '2019-02-13 09:12:08',
            'edate' => '2019-02-13 09:12:08',
            'udate' => '2019-02-13 09:12:08',
        ];

        $this->assertEquals($expected, $this->updater->translateFields($contact));
    }

    public function testLanguageTranslation()
    {
        $contact = [
            'cdate' => '2019-02-13T03:12:08-06:00',
            'some-value' => '3.14',
            'some-array' => ['3.14'],
        ];
        $expected = [
            'cdate' => '2019-02-13 09:12:08',
            'some-value' => '3.14',
            'some-array' => ['3.14'],
        ];
        $this->assertEquals($expected, $this->updater->translateFields($contact));

        $expected = [
            'cdate' => '2019-02-13 09.12.08',
            'some-value' => '3,14',
            'some-array' => ['3.14'],
        ];
        $this->assertEquals($expected, $this->updater->translateFields($contact, true));
    }
}
