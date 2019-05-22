<?php

namespace App\Console\Commands;

use App\ActiveCampaign;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

class GetContact extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ac:get:contact {contact_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get a contact from ActiveCampaign and dump to stdout';

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
    public function handle(ActiveCampaign $ac)
    {
        print Yaml::dump($ac->getContact($this->argument('contact_id')));
    }
}
