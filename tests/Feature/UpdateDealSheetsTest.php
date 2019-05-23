<?php

namespace Tests\Feature;

use App\ActiveCampaign;
use App\Events\DealUpdated;
use App\Exceptions\UpdateSheetsException;
use App\Listeners\UpdateDealSheets;
use App\Sheets;
use Illuminate\Support\Facades\Log;
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

        $updater = new UpdateDealSheets($ac->reveal(), $sheets->reveal());
        $updater->handle(new DealUpdated(42));
    }

    public function testMissingId()
    {
        $deal = [
            'id' => '500',
        ];

        $sheets = [
            [
                'sheet' => 'the-sheet',
                'tab' => 'the-tab',
            ],
        ];

        Log::shouldReceive("error")->with('Error "The "id" field must be mapped." while mapping {"sheet":"the-sheet","tab":"the-tab"}')->once();

        putenv('DEAL_SHEETS=' . YAML::dump($sheets));

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->getDeal(42)->willReturn($deal);

        $sheets = $this->prophesize(Sheets::class);
        $sheets->header('the-sheet', 'the-tab')->willReturn(['banana']);

        $updater = new UpdateDealSheets($ac->reveal(), $sheets->reveal());
        $updater->handle(new DealUpdated(42));
    }

    public function testMissingField()
    {
        Log::shouldReceive("warning")->with('Unknown field "banana".')->once();
        Log::shouldReceive("info");

        $deal = [
            'id' => '500',
        ];

        $sheets = [
            [
                'sheet' => 'the-sheet',
                'tab' => 'the-tab',
            ],
        ];

        putenv('DEAL_SHEETS=' . YAML::dump($sheets));

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->getDeal(42)->willReturn($deal);

        $sheets = $this->prophesize(Sheets::class);
        $sheets->header('the-sheet', 'the-tab')->willReturn(['id', 'banana']);
        $sheets->data('the-sheet', 'the-tab')->willReturn([[]]);

        $sheets->appendRow('the-sheet', 'the-tab', [500, ''])->shouldBeCalled();

        $updater = new UpdateDealSheets($ac->reveal(), $sheets->reveal());
        $updater->handle(new DealUpdated(42));
    }

    public function testAppending()
    {
        Log::spy();
        Log::shouldNotReceive('error');
        $deal = [
            'id' => '500',
            'cdate' => '2019-02-13T03:12:08-06:00',
        ];

        $sheets = [
            [
                'sheet' => 'the-sheet',
                'tab' => 'the-tab',
            ],
        ];

        putenv('DEAL_SHEETS=' . YAML::dump($sheets));

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->getDeal(42)->willReturn($deal);

        $sheets = $this->prophesize(Sheets::class);
        $sheets->header('the-sheet', 'the-tab')->willReturn(['id', 'cdate']);
        $sheets->data('the-sheet', 'the-tab')->willReturn([[]]);

        $sheets->appendRow('the-sheet', 'the-tab', [500, '2019-02-13 09:12:08'])->shouldBeCalled();

        $updater = new UpdateDealSheets($ac->reveal(), $sheets->reveal());
        $updater->handle(new DealUpdated(42));
    }

    public function testUpdating()
    {
        Log::spy();
        Log::shouldNotReceive('error');
        $deal = [
            'id' => '500',
            'name' => 'new name',
            'some-value' => '93.14'
        ];

        $sheet = [
            ['one', 499],
            ['two', 500],
            ['three', 501],
        ];
        $sheets = [
            [
                'sheet' => 'the-sheet',
                'tab' => 'the-tab',
            ],
        ];

        putenv('DEAL_SHEETS=' . YAML::dump($sheets));

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->getDeal(42)->willReturn($deal);

        $sheets = $this->prophesize(Sheets::class);
        $sheets->header('the-sheet', 'the-tab')->willReturn(['name', 'id', 'some-value']);
        $sheets->data('the-sheet', 'the-tab')->willReturn($sheet);

        $sheets->updateRow('the-sheet', 'the-tab', 2, ['new name', 500, '93.14'])->shouldBeCalled();

        $updater = new UpdateDealSheets($ac->reveal(), $sheets->reveal());
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

        $updater = new UpdateDealSheets($ac->reveal(), $sheets->reveal());
        $updater->handle(new DealUpdated(42));
    }

    public function testUpdatingDanish()
    {
        Log::spy();
        Log::shouldNotReceive('error');

        $deal = [
            'id' => '42',
            'name' => 'new name',
            'some-value' => '8.241',
            'cdate' => '2019-02-13T03:12:08-06:00',
        ];

        $sheets = [
            [
                'sheet' => 'the-sheet',
                'tab' => 'the-tab',
                'localeTranslate' => true,
            ],
        ];

        putenv('DEAL_SHEETS=' . YAML::dump($sheets));

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->getDeal(42)->willReturn($deal);

        $sheets = $this->prophesize(Sheets::class);
        $sheets->header('the-sheet', 'the-tab')->willReturn(['name', 'id', 'some-value', 'cdate']);
        $sheets->data('the-sheet', 'the-tab')->willReturn([[]]);

        $expected = ['new name', 42, '8,241', '2019-02-13 09.12.08'];
        $sheets->appendRow('the-sheet', 'the-tab', $expected)->shouldBeCalled();

        $updater = new UpdateDealSheets($ac->reveal(), $sheets->reveal());
        $updater->handle(new DealUpdated(42));
    }
}
