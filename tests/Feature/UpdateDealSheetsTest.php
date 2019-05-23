<?php

namespace Tests\Feature;

use App\ActiveCampaign;
use App\Events\DealUpdated;
use App\Exceptions\UpdateSheetsException;
use App\Listeners\UpdateDealSheets;
use App\SheetWriter;
use App\Sheets;
use Illuminate\Support\Facades\Log;
use Prophecy\Argument;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class UpdateDealSheetsTest extends TestCase
{
    public function testMissingMapping()
    {
        $this->expectException(UpdateSheetsException::class);

        putenv('DEAL_SHEETS=');

        $ac = $this->prophesize(ActiveCampaign::class);
        $sheets = $this->prophesize(Sheets::class);

        $sheetWriter = new SheetWriter($sheets->reveal());
        $updater = new UpdateDealSheets($ac->reveal(), $sheetWriter);
        $updater->handle(new DealUpdated(42));
    }

    public function testErrorLogging()
    {
        $deal = [
            'id' => '500',
            'firstName' => 'Athur',
        ];

        $sheets = [
            [
                'sheet' => 'the-sheet',
                'tab' => 'the-tab',
            ],
        ];

        Log::shouldReceive("error")->with('Error "ERROR!" while mapping {"sheet":"the-sheet","tab":"the-tab"}')->once();

        putenv('DEAL_SHEETS=' . YAML::dump($sheets));

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->getDeal(42)->willReturn($deal);

        $sheetWriter = $this->prophesize(SheetWriter::class);
        $sheetWriter->updateSheet($sheets[0], $deal, Argument::any())
            ->willThrow(new UpdateSheetsException("ERROR!"));

        $updater = new UpdateDealSheets($ac->reveal(), $sheetWriter->reveal());
        $updater->handle(new DealUpdated(42));
    }

    public function testCatchingErrorsInAC()
    {
        $sheets = [
            [
                'sheet' => 'the-sheet',
                'tab' => 'the-tab',
                'map' => [
                    'id' => 1,
                ]
            ],
        ];

        Log::shouldReceive("error")->with('Error fetching deal 42: bad stuff')->once();

        putenv('DEAL_SHEETS=' . YAML::dump($sheets));

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->getDeal(42)->willThrow(new RuntimeException('bad stuff'));

        $sheets = $this->prophesize(Sheets::class);

        $sheetWriter = new SheetWriter($sheets->reveal());
        $updater = new UpdateDealSheets($ac->reveal(), $sheetWriter);
        $updater->handle(new DealUpdated(42));
    }

    public function testUpdatingDanish()
    {
        $ac = $this->prophesize(ActiveCampaign::class);
        $sheetWriter = $this->prophesize(SheetWriter::class);
        $updater = new UpdateDealSheets($ac->reveal(), $sheetWriter->reveal());

        $data = [
            'id' => '42',
            'name' => 'new name',
            'some-value' => '8.241',
            'cdate' => '2019-02-13T03:12:08-06:00',
        ];

        $expected = [
            'id' => '42',
            'name' => 'new name',
            'some-value' => '8.241',
            'cdate' =>'2019-02-13 09:12:08',
        ];

        $expectedLocal = [
            'id' => 42,
            'name' => 'new name',
            'some-value' => '8,241',
            'cdate' =>'2019-02-13 09.12.08',
        ];

        $this->assertEquals($expected, $updater->translateFields($data, false));
        $this->assertEquals($expectedLocal, $updater->translateFields($data, true));
    }
}
