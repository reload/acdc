<?php

namespace App\Http\Controllers;

use App\Events\ContactUpdated;
use App\Events\DealUpdated;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{

    public function handle(Request $request)
    {
        if ($request->has('deal.id')) {
            $request->validate([
                'deal.id' => 'required:integer',
            ]);

            event(new DealUpdated($request->input('deal.id')));
        }

        if ($request->has('contact.id')) {
            $request->validate([
                'contact.id' => 'required:integer',
            ]);

            event(new ContactUpdated($request->input('contact.id')));
        }
    }
}
