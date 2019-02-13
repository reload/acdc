<?php

namespace Tests\Unit;

use App\ActiveCampaign;
use App\Events\DealUpdated;
use App\Listeners\UpdateSheets;
use App\Sheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UpdateSheetsTest extends TestCase
{
    public function testMapFunction()
    {
        // Suppress log output.
        Log::spy();

        $ac = $this->prophesize(ActiveCampaign::class);
        $sheets = $this->prophesize(Sheets::class);

        $updater = new UpdateSheets($ac->reveal(), $sheets->reveal());

        $deal = [
            'id' => 12,
            'name' => 'banana',
            'ignored' => 'none',
        ];
        $map = [
            2 => 'id',
            1 => 'name',
        ];

        $this->assertEquals(['', 'banana', 12], $updater->map($deal, $map));

        $deal = [
            'id' => 12,
            'name' => 'banana',
            'ignored' => 'none',
        ];
        $map = [
            1 => 'id',
            3 => 'name',
        ];

        $this->assertEquals(['', 12, '', 'banana'], $updater->map($deal, $map));

        $deal = [
            'id' => 12,
            'ignored' => 'none',
        ];
        $map = [
            1 => 'id',
            3 => 'name',
        ];

        $this->assertEquals(['', 12, '', ''], $updater->map($deal, $map));
    }

    public function testDateTranslation()
    {
        // Suppress log output.
        Log::spy();

        $ac = $this->prophesize(ActiveCampaign::class);
        $sheets = $this->prophesize(Sheets::class);

        $updater = new UpdateSheets($ac->reveal(), $sheets->reveal());

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
}
