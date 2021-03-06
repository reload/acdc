<?php

namespace Tests\Feature;

use App\ActiveCampaign;
use App\Events\DealUpdated;
use App\Listeners\UpdateDealAverage;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UpdateDealAverageTest extends TestCase
{
    public function testAverageUpdate()
    {
        Log::shouldReceive('info')
            ->with('Updated deal 42 with average.')
            ->andReturn();

        $deal = [
            'id' => '42',
            'custom_field_1' => '3',
            'custom_field_2' => '3',
            'custom_field_3' => '3',
            'custom_field_4' => '3',
            'custom_field_5' => '3',
        ];

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->getDeal(42)->willReturn($deal);
        $ac->updateDealCustomField(42, 'custom_field_8', 3)->shouldBeCalled();

        $updater = new UpdateDealAverage($ac->reveal());
        $updater->handle(new DealUpdated(42));

        $deal = [
            'id' => '42',
            'custom_field_1' => '2',
            'custom_field_2' => '3',
            'custom_field_3' => '5',
            'custom_field_4' => '3',
            'custom_field_5' => '1',
        ];

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->getDeal(42)->willReturn($deal);
        $ac->updateDealCustomField(42, 'custom_field_8', 2.8)->shouldBeCalled();

        $updater = new UpdateDealAverage($ac->reveal());
        $updater->handle(new DealUpdated(42));
    }

    public function testNoData()
    {
        Log::shouldReceive('info')
            ->with('Updated deal 42 with average.')
            ->andReturn();

        $deal = [
            'id' => '42',
        ];

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->getDeal(42)->willReturn($deal);
        $ac->updateDealCustomField(42, 'custom_field_8', 0)->shouldBeCalled();

        $updater = new UpdateDealAverage($ac->reveal());
        $updater->handle(new DealUpdated(42));
    }
}
