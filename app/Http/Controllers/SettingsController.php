<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function view(Request $request)
    {
        return view('settings', ['ac_key' => '']);
    }
}
