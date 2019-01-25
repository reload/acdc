<?php

namespace App\Http\Controllers;

use App\Settings;
use App\Http\Requests\StoreActiveCampaignSettings;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function view(Settings $settings)
    {
        return view('settings', [
            'activecampaign_account' => $settings->get('activecampaign_account'),
            'activecampaign_token' => $settings->get('activecampaign_token'),
        ]);
    }

    public function save(StoreActiveCampaignSettings $request, Settings $settings)
    {
        $data = $request->validated();
        $settings->set('activecampaign_account', $data['activecampaign_account']);
        $settings->set('activecampaign_token', $data['activecampaign_token']);

        $request->session()->flash('status', 'Settings saved');
        return redirect('/');
    }
}
