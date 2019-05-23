<?php

namespace Tests\Feature;

use App\ActiveCampaign;
use App\Events\ContactUpdated;
use App\Exceptions\UpdateSheetsException;
use App\Listeners\UpdateContactSheets;
use App\SheetWriter;
use App\Sheets;
use Illuminate\Support\Facades\Log;
use Prophecy\Argument;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class UpdateContactSheetsTest extends TestCase
{
    public function testMissingMapping()
    {
        $this->expectException(UpdateSheetsException::class);

        putenv('CONTACT_SHEETS=');

        $ac = $this->prophesize(ActiveCampaign::class);
        $sheets = $this->prophesize(Sheets::class);

        $sheetWriter = new SheetWriter($sheets->reveal());
        $updater = new UpdateContactSheets($ac->reveal(), $sheetWriter);
        $updater->handle(new ContactUpdated(42));
    }

    public function testErrorLogging()
    {
        $contact = [
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

        putenv('CONTACT_SHEETS=' . YAML::dump($sheets));

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->getContact(42)->willReturn($contact);

        $sheetWriter = $this->prophesize(SheetWriter::class);
        $sheetWriter->updateSheet($sheets[0], $contact, Argument::any())
            ->willThrow(new UpdateSheetsException("ERROR!"));

        $updater = new UpdateContactSheets($ac->reveal(), $sheetWriter->reveal());
        $updater->handle(new ContactUpdated(42));
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

        Log::shouldReceive("error")->with('Error fetching contact 42: bad stuff')->once();

        putenv('CONTACT_SHEETS=' . YAML::dump($sheets));

        $ac = $this->prophesize(ActiveCampaign::class);
        $ac->getContact(42)->willThrow(new RuntimeException('bad stuff'));

        $sheetWriter = $this->prophesize(SheetWriter::class);
        $sheets = $this->prophesize(Sheets::class);

        $updater = new UpdateContactSheets($ac->reveal(), $sheetWriter->reveal());
        $updater->handle(new ContactUpdated(42));
    }

    public function testUpdatingDanish()
    {

        $ac = $this->prophesize(ActiveCampaign::class);
        $sheetWriter = $this->prophesize(SheetWriter::class);

        $updater = new UpdateContactSheets($ac->reveal(), $sheetWriter->reveal());

        $data = [
            'id' => 42,
            'some_value' => '8.241',
        ];

        $expected = [
            'id' => 42,
            'some_value' => '8,241',
        ];

        $this->assertEquals($data, $updater->translateFields($data, false));
        $this->assertEquals($expected, $updater->translateFields($data, true));
    }
}
