<?php

namespace Tests\Feature;

use App\ActiveCampaign;
use App\Events\DealUpdated;
use App\Exceptions\MapperException;
use App\Listeners\UpdateSheets;
use App\Sheets;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class UpdateSheetsTest extends TestCase
{
    public function testMissingMapping()
    {
        $this->expectException(MapperException::class);

        $ac = $this->prophesize(ActiveCampaign::class);
        $sheets = $this->prophesize(Sheets::class);

        $updater = new UpdateSheets($ac->reveal(), $sheets->reveal());
        $updater->handle(new DealUpdated(42));
    }

    public function testMissingId()
    {
        $deal = [
            'id' => '500',
        ];

        $mapping = [
            [
                'sheet' => 'the-sheet',
                'tab' => 'the-tab',
                'map' => [
                    'banana' => 1,
                ]
            ],
        ];

        Log::shouldReceive("error")->with('Error "The "id" field must be mapped." while mapping {"sheet":"the-sheet","tab":"the-tab","map":{"banana":1}}')->once();

        putenv('MAPPING=' . YAML::dump($mapping));

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->get(42)->willReturn($deal);

        $sheets = $this->prophesize(Sheets::class);

        $updater = new UpdateSheets($ac->reveal(), $sheets->reveal());
        $updater->handle(new DealUpdated(42));
    }

    public function testMissingField()
    {
        Log::shouldReceive("error")->with('Unknown field "banana".')->once();

        $deal = [
            'id' => '500',
        ];

        $mapping = [
            [
                'sheet' => 'the-sheet',
                'tab' => 'the-tab',
                'map' => [
                    'id' => 1,
                    'banana' => 2,
                ]
            ],
        ];

        putenv('MAPPING=' . YAML::dump($mapping));

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->get(42)->willReturn($deal);

        $sheets = $this->prophesize(Sheets::class);
        $sheets->data('the-sheet', 'the-tab')->willReturn([]);

        $sheets->appendRow('the-sheet', 'the-tab', [500, ''])->shouldBeCalled();

        $updater = new UpdateSheets($ac->reveal(), $sheets->reveal());
        $updater->handle(new DealUpdated(42));
    }

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
            'id' => 2,
            'name' => 1,
        ];

        $this->assertEquals(['banana', 12], $updater->map($deal, $map));

        $deal = [
            'id' => 12,
            'name' => 'banana',
            'ignored' => 'none',
        ];
        $map = [
            'id' => 2,
            'name' => 4,
        ];

        $this->assertEquals(['', 12, '', 'banana'], $updater->map($deal, $map));

        $deal = [
            'id' => 12,
            'ignored' => 'none',
        ];
        $map = [
            'id' => 2,
            'name' => 4,
        ];

        $this->assertEquals(['', 12, '', ''], $updater->map($deal, $map));
    }

    public function testAppending()
    {
        Log::spy();
        Log::shouldNotReceive('error');
        $deal = [
            'id' => '500',
        ];

        $mapping = [
            [
                'sheet' => 'the-sheet',
                'tab' => 'the-tab',
                'map' => [
                    'id' => 1,
                ]
            ],
        ];

        putenv('MAPPING=' . YAML::dump($mapping));

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->get(42)->willReturn($deal);

        $sheets = $this->prophesize(Sheets::class);
        $sheets->data('the-sheet', 'the-tab')->willReturn([]);

        $sheets->appendRow('the-sheet', 'the-tab', [500])->shouldBeCalled();

        $updater = new UpdateSheets($ac->reveal(), $sheets->reveal());
        $updater->handle(new DealUpdated(42));
    }

    public function testUpdating()
    {
        Log::spy();
        Log::shouldNotReceive('error');
        $deal = [
            'id' => '500',
            'name' => 'new name',
        ];

        $sheet = [
            ['one', 499],
            ['two', 500],
            ['three', 501],
        ];
        $mapping = [
            [
                'sheet' => 'the-sheet',
                'tab' => 'the-tab',
                'map' => [
                    'id' => 2,
                    'name' => 1,
                ]
            ],
        ];

        putenv('MAPPING=' . YAML::dump($mapping));

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->get(42)->willReturn($deal);

        $sheets = $this->prophesize(Sheets::class);
        $sheets->data('the-sheet', 'the-tab')->willReturn($sheet);

        $sheets->updateRow('the-sheet', 'the-tab', 2, ['new name', 500])->shouldBeCalled();

        $updater = new UpdateSheets($ac->reveal(), $sheets->reveal());
        $updater->handle(new DealUpdated(42));
    }
}
