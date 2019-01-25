<?php

namespace Tests\Feature;

use App\User;
use App\Settings;
use App\ActiveCampaign;
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
        $settings->set('activecampaign_account', '123');
        $settings->set('activecampaign_token', '456');

        $response = $this->get('/settings');
        $response->assertSee('123');
        $response->assertSee('456');
        $response->assertStatus(200);
    }

    public function testSavingSettings()
    {
        $this->app->singleton(ActiveCampaign::class, function () {
            $ac = $this->prophesize(ActiveCampaign::class);
            $ac2 = $this->prophesize(ActiveCampaign::class);
            $ac->withCreds('321', '654')->willReturn($ac2);
            $ac2->ping()->willReturn(true);
            return $ac->reveal();
        });

        $this->actingAsAuthenticated();

        $response = $this->post('/settings/save', [
            'activecampaign_account' => '321',
            'activecampaign_token' => '654',
        ]);

        $response->assertStatus(302);

        $settings = $this->app->get(Settings::class);
        $this->assertEquals('321', $settings->get('activecampaign_account'));
        $this->assertEquals('654', $settings->get('activecampaign_token'));
    }

    public function actingAsAuthenticated()
    {
        $user = factory(User::class)->create();

        $this->actingAs($user);
    }
}
