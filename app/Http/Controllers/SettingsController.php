<?php

namespace App\Http\Controllers;

use App\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function view(Request $request, Settings $settings)
    {
        return view('settings', [
            'activecampaign_account' => $settings->get('activecampaign_account'),
            'activecampaign_token' => $settings->get('activecampaign_token'),
        ]);
    }
}
