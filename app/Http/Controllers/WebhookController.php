<?php

namespace App\Http\Controllers;

use App\Events\DealUpdated;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    //
    function handle(Request $request)
    {
        $request->validate([
            'deal.id' => 'required:integer',
        ]);

        event(new DealUpdated($request->input('deal.id')));
    }
}
