<?php

namespace Tests\Feature;

use App\User;
use App\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function testSettingsAccess()
    {
        // Guest users should be redirected.
        $response = $this->get('/settings');
        $response->assertStatus(302);
    }

    /**
     * @return void
     */
    public function testSeeingSettings()
    {
        $this->actingAsAuthenticated();
        $settings = $this->app->get(Settings::class);
        $settings->set('activecampaign_account', 'the_account_id');
        $settings->set('activecampaign_token', 'the_token_id');

        $response = $this->get('/settings');
        $response->assertSee('the_account_id');
        $response->assertSee('the_token');
        $response->assertStatus(200);
    }

    public function actingAsAuthenticated()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user);
    }
}
