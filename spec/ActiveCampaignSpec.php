<?php

namespace spec\App;

use App\ActiveCampaign;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ActiveCampaignSpec extends ObjectBehavior
{
    function let(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ActiveCampaign::class);
    }

    function it_returns_true_when_pinging_with_valid_credentials(Client $client)
    {
        $headers = [
            'Api-Token' => '456',
        ];
        $client->get('https://123.api-us1.com/api/3/', ['headers' => $headers])->willReturn(new Response());

        $this->ping()->shouldReturn(true);
    }

    function it_returns_false_when_pinging_with_invalid_credentials(Client $client)
    {
        $headers = [
            'Api-Token' => '456',
        ];
        $client->get('https://123.api-us1.com/api/3/', ['headers' => $headers])->willThrow(new Exception());

        $this->ping()->shouldReturn(false);
    }
}
