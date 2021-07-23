<?php

namespace spec\App;

use App\Sheets;
use Exception;
use Google\Service\Sheets as GoogleSheets;
use Google\Service\Sheets\Resource\Spreadsheets;
use Google\Service\Sheets\Resource\SpreadsheetsValues;
use Google\Service\Sheets\ValueRange;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SheetsSpec extends ObjectBehavior
{
    function let(GoogleSheets $sheets)
    {
        $this->beConstructedWith($sheets);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Sheets::class);
    }

    function it_should_check_for_sheet_existence(
        GoogleSheets $sheets,
        Spreadsheets $spreadsheets
    ) {
        $sheet = (object) [];
        $spreadsheets->get('spreadsheet-id')->willReturn($sheet);
        $spreadsheets->get('spreadsheet-id2')->willThrow(new Exception());
        $sheets->spreadsheets = $spreadsheets;

        $this->exists('spreadsheet-id')->shouldReturn(true);
        $this->exists('spreadsheet-id2')->shouldReturn(false);
    }

    function it_should_list_pages_in_sheet(
        GoogleSheets $sheets,
        Spreadsheets $spreadsheets
    ) {
        // fake $sheets->spreadsheets->get(id)->sheets[X]->properties->title.
        $sheet = (object) ['sheets' => [
            (object) ['properties' => (object) ['title' => 'sheet-1']],
            (object) ['properties' => (object) ['title' => 'sheet-2']],
        ]];
        $spreadsheets->get('spreadsheet-id')->willReturn($sheet);
        $sheets->spreadsheets = $spreadsheets;

        $this->sheets('spreadsheet-id')->shouldReturn(['sheet-1', 'sheet-2']);
    }

    function it_should_return_sheet_header(
        GoogleSheets $sheets,
        SpreadsheetsValues $values
    ) {
        $valuesObj = (object) [
            'values' => [
                ['header 1', 'header 2', 'header 3'],
                ['value 1', 'value 2', 'value 3'],
                ['value a', 'value b', 'value c'],
            ],
        ];
        $values->get('spreadsheet-id', "'le sheet'!1:1", ['majorDimension' => 'ROWS'])->willReturn($valuesObj);
        $sheets->spreadsheets_values = $values;

        $this->header('spreadsheet-id', 'le sheet')->shouldReturn(['header 1', 'header 2', 'header 3']);
    }

    function it_should_return_sheet_data(
        GoogleSheets $sheets,
        SpreadsheetsValues $values
    ) {
        $rows = [
            ['header 1', 'header 2', 'header 3'],
            ['value 1', 'value 2', 'value 3'],
            ['value a', 'value b', 'value c'],
        ];
        $valuesObj = (object) [
            'values' => $rows,
        ];

        $values->get('spreadsheet-id', "'le sheet'", ['majorDimension' => 'ROWS'])->willReturn($valuesObj);
        $sheets->spreadsheets_values = $values;

        $this->data('spreadsheet-id', 'le sheet')->shouldReturn($rows);
    }

    function it_should_append_row(
        GoogleSheets $sheets,
        SpreadsheetsValues $values
    ) {
        $valueRange = new ValueRange(['values' => [['one', 'two']]]);

        $values->append(
            'spreadsheet-id',
            "'le sheet'!A1:B1",
            $valueRange,
            ['valueInputOption' => 'USER_ENTERED']
        )->shouldBeCalled();
        $sheets->spreadsheets_values = $values;
        $this->appendRow('spreadsheet-id', 'le sheet', ['one' , 'two']);
    }

    function it_should_update_row(
        GoogleSheets $sheets,
        SpreadsheetsValues $values
    ) {
        $valueRange = new ValueRange(['values' => [['one', 'two']]]);

        $values->update(
            'spreadsheet-id',
            "'le sheet'!A3:B3",
            $valueRange,
            ['valueInputOption' => 'USER_ENTERED']
        )->shouldBeCalled();
        $sheets->spreadsheets_values = $values;
        $this->updateRow('spreadsheet-id', 'le sheet', 3, ['one' , 'two']);
    }
}
