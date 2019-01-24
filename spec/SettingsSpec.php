<?php

namespace spec\App;

use App\Settings;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use PhpSpec\Laravel\LaravelObjectBehavior;

class SettingsSpec extends LaravelObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Settings::class);
    }

    function it_sets_a_setting()
    {
        $this->set('setting name', 'setting value')->shouldReturn(null);
    }

    function it_returns_settings_from_db()
    {
        // Note that because the db is shared between tests, we should use
        // unique settings in each test.
        $this->set('setting name2', 'setting value')->shouldReturn(null);
        $this->get('setting name2')->shouldReturn('setting value');
    }
}
