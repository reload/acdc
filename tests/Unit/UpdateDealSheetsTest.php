<?php

namespace Tests\Unit;

use App\ActiveCampaign;
use App\Events\DealUpdated;
use App\Listeners\UpdateDealSheets;
use App\SheetWriter;
use App\Sheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UpdateDealSheetsTest extends TestCase
{
    public function testDateTranslation()
    {
        // Suppress log output.
        Log::spy();

        $ac = $this->prophesize(ActiveCampaign::class);
        $writer = $this->prophesize(SheetWriter::class);

        $updater = new UpdateDealSheets($ac->reveal(), $writer->reveal());

        $deal = [
            'untranslated' => '2019-02-13T03:12:08-06:00',
            'cdate' => '2019-02-13T03:12:08-06:00',
            'mdate' => '2019-02-13T03:12:08-06:00',
        ];

        $expected = [
            'untranslated' => '2019-02-13T03:12:08-06:00',
            'cdate' => '2019-02-13 09:12:08',
            'mdate' => '2019-02-13 09:12:08',
        ];

        $this->assertEquals($expected, $updater->translateFields($deal));
    }

    public function testValueTranslation()
    {
        // Suppress log output.
        Log::spy();

        $ac = $this->prophesize(ActiveCampaign::class);
        $writer = $this->prophesize(SheetWriter::class);

        $updater = new UpdateDealSheets($ac->reveal(), $writer->reveal());

        $deal = [
            'untranslated' => '100000',
            'value' => '100000',
        ];
        $expected = [
            'untranslated' => '100000',
            'value' => '1000',
        ];
        $this->assertEquals($expected, $updater->translateFields($deal));

        $deal = [
            'untranslated' => '34000',
            'value' => '34000',
        ];
        $expected = [
            'untranslated' => '34000',
            'value' => '340',
        ];
        $this->assertEquals($expected, $updater->translateFields($deal));

        $deal = [
            'untranslated' => '123',
            'value' => '123',
        ];
        $expected = [
            'untranslated' => '123',
            'value' => '1',
        ];
        $this->assertEquals($expected, $updater->translateFields($deal));

        // Expect round up.
        $deal = [
            'untranslated' => '777',
            'value' => '777',
        ];
        $expected = [
            'untranslated' => '777',
            'value' => '8',
        ];
        $this->assertEquals($expected, $updater->translateFields($deal));
    }

    public function testLanguageTranslation()
    {
        // Suppress log output.
        Log::spy();

        $ac = $this->prophesize(ActiveCampaign::class);
        $writer = $this->prophesize(SheetWriter::class);

        $updater = new UpdateDealSheets($ac->reveal(), $writer->reveal());

        $deal = [
            'cdate' => '2019-02-13T03:12:08-06:00',
            'some-value' => '3.14',
            'some-array' => ['3.14'],
        ];
        $expected = [
            'cdate' => '2019-02-13 09:12:08',
            'some-value' => '3.14',
            'some-array' => ['3.14'],
        ];
        $this->assertEquals($expected, $updater->translateFields($deal));

        $expected = [
            'cdate' => '2019-02-13 09.12.08',
            'some-value' => '3,14',
            'some-array' => ['3.14'],
        ];
        $this->assertEquals($expected, $updater->translateFields($deal, true));
    }
}
