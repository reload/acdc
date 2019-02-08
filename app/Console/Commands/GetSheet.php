<?php

namespace App\Console\Commands;

use App\Sheets;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

class GetSheet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ac:getsheet {spreadsheet_id} {sheet_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get data from Sheets and dump to stdout';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Sheets $sheets)
    {
        print Yaml::dump($sheets->data($this->argument('spreadsheet_id'), $this->argument('sheet_id')));
    }
}
