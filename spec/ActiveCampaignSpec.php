<?php

namespace spec\App;

use App\ActiveCampaign;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Response;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ActiveCampaignSpec extends ObjectBehavior
{
    function let(Client $client)
    {
        $this->beConstructedWith($client);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ActiveCampaign::class);
    }

    function it_returns_true_when_pinging_with_valid_credentials(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];
        $client->request('GET', 'https://123.api-us1.com/api/3/users/me', ['headers' => $headers])->willReturn(new Response());

        $this->ping()->shouldReturn(true);
    }

    function it_throws_when_pinging_with_invalid_credentials(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];
        $client->request('GET', 'https://123.api-us1.com/api/3/users/me', ['headers' => $headers])->willThrow(new Exception());

        $this->shouldThrow(new Exception())->during('ping');
    }

    function it_should_throw_early_with_empty_creds(Client $client)
    {
        $this->shouldThrow(TransferException::class)->during('ping');
    }

    function it_allows_for_changing_creds(Client $client)
    {
        $expected = new ActiveCampaign($client->getWrappedObject(), '789', '012');
        $this->withCreds('789', '012')->shouldBeLike($expected);
    }
}
