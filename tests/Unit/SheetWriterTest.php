<?php

namespace Tests\Unit;

use App\Exceptions\UpdateSheetsException;
use App\FieldTranslator;
use App\SheetWriter;
use App\Sheets;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SheetWriterTest extends TestCase
{
    public function testMapFunction()
    {
        // Suppress log output.
        Log::spy();

        $sheets = $this->prophesize(Sheets::class);

        $writer = new SheetWriter($sheets->reveal());

        $data = [
            'id' => 12,
            'name' => 'banana',
            'ignored' => 'none',
        ];
        $map = [
            2 => 'id',
            1 => 'name',
        ];

        $this->assertEquals(['', 'banana', 12], $writer->map($data, $map));

        $data = [
            'id' => 12,
            'name' => 'banana',
            'ignored' => 'none',
        ];
        $map = [
            1 => 'id',
            3 => 'name',
        ];

        $this->assertEquals(['', 12, '', 'banana'], $writer->map($data, $map));

        $data = [
            'id' => 12,
            'ignored' => 'none',
        ];
        $map = [
            1 => 'id',
            3 => 'name',
        ];

        $this->assertEquals(['', 12, '', ''], $writer->map($data, $map));
    }

    public function testLanguageTranslation()
    {
        // Suppress log output.
        Log::spy();

        $data = [
            'id' => '2',
            'name' => 'Arthur',
            'value' => ['3'],
        ];

        $expected = [
            'id' => '2',
            'name' => 'Arthur',
            'value' => ['3'],
        ];

        $translator = $this->prophesize(FieldTranslator::class);
        $sheets = $this->prophesize(Sheets::class);
        $sheets->header('sheet', 'tab')->willReturn(['id', 'name', 'value']);
        $translator->translateFields($data, false)->willReturn($expected)->shouldBeCalled();
        $sheets->data('sheet', 'tab')->willReturn([[2, 'Ford', '4']]);
        $sheets->updateRow('sheet', 'tab', 1, array_values($expected))->shouldBeCalled();

        $writer = new SheetWriter($sheets->reveal());

        $this->assertEquals(
            SheetWriter::UPDATED,
            $writer->updateSheet(['sheet' => 'sheet', 'tab' => 'tab'], $data, $translator->reveal())
        );
    }

    public function testMissingId()
    {
        $translator = $this->prophesize(FieldTranslator::class);
        $sheets = $this->prophesize(Sheets::class);
        // No id header.
        $sheets->header('sheet', 'tab')->willReturn(['name', 'value']);
        $writer = new SheetWriter($sheets->reveal());

        $this->expectException(UpdateSheetsException::class);
        $writer->updateSheet(['sheet' => 'sheet', 'tab' => 'tab'], [], $translator->reveal());
    }

    public function testMissingField()
    {
        Log::shouldReceive("warning")->with('Unknown field "banana".')->once();

        $data = ['id' => 42];
        $translator = $this->prophesize(FieldTranslator::class);
        $translator->translateFields($data, false)->willReturn($data);
        $sheets = $this->prophesize(Sheets::class);
        // Header field not in data.
        $sheets->header('sheet', 'tab')->willReturn(['id', 'banana'])->shouldBeCalled();
        $sheets->data('sheet', 'tab')->willReturn([['42', 'lala']])->shouldBeCalled();
        $sheets->updateRow('sheet', 'tab', 1, [42, ""])->shouldBeCalled();
        $writer = new SheetWriter($sheets->reveal());

        $writer->updateSheet(['sheet' => 'sheet', 'tab' => 'tab'], $data, $translator->reveal());
    }

    public function testAppending()
    {
        $data = [
            'id' => 500,
            'name' => 'Arthur',
        ];
        $translator = $this->prophesize(FieldTranslator::class);
        $translator->translateFields($data, false)->willReturn($data);
        $sheets = $this->prophesize(Sheets::class);
        // Header field not in data.
        $sheets->header('sheet', 'tab')->willReturn(['id', 'name'])->shouldBeCalled();
        $sheets->data('sheet', 'tab')->willReturn([['499', 'Ford']])->shouldBeCalled();

        $sheets->appendRow('sheet', 'tab', [500, "Arthur"])->shouldBeCalled();
        $writer = new SheetWriter($sheets->reveal());

        $writer->updateSheet(['sheet' => 'sheet', 'tab' => 'tab'], $data, $translator->reveal());
    }

    public function testUpdating()
    {
        $data = [
            'id' => 500,
            'name' => 'Arthur',
        ];
        $translator = $this->prophesize(FieldTranslator::class);
        $translator->translateFields($data, false)->willReturn($data);
        $sheets = $this->prophesize(Sheets::class);
        // Header field not in data.
        $sheets->header('sheet', 'tab')->willReturn(['id', 'name'])->shouldBeCalled();
        $sheets->data('sheet', 'tab')->willReturn([['499', 'Ford'], ['500', '']])->shouldBeCalled();

        $sheets->updateRow('sheet', 'tab', 2, [500, "Arthur"])->shouldBeCalled();
        $writer = new SheetWriter($sheets->reveal());

        $writer->updateSheet(['sheet' => 'sheet', 'tab' => 'tab'], $data, $translator->reveal());
    }
}
