<?php

namespace spec\App;

use App\ActiveCampaign;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use RuntimeException;

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
        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/users/me',
            ['headers' => $headers]
        )->willReturn(new Response());

        $this->ping()->shouldReturn(true);
    }

    function it_throws_when_pinging_with_invalid_credentials(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];
        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/users/me',
            ['headers' => $headers]
        )->willThrow(new Exception());

        $this->shouldThrow(new Exception())->during('ping');
    }

    function it_should_throw_early_with_empty_creds(Client $client)
    {
        $this->shouldThrow(RuntimeException::class)->during('ping');
    }

    function it_allows_for_changing_creds(Client $client)
    {
        $expected = new ActiveCampaign($client->getWrappedObject(), '789', '012');
        $this->withCreds('789', '012')->shouldBeLike($expected);
    }

    function it_should_get_deals(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];
        //https://1499693424850.api-us1.com/api/3/deals/789
        $apiResponse = [
            'deal' => [
                'id' => 789,
            ],
        ];
        $response = new Response(200, [], json_encode($apiResponse));
        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/deals/789',
            ['headers' => $headers]
        )->willReturn($response);

        // https://1499693424850.api-us1.com/api/3/deals/789/dealCustomFieldData
        $apiResponse = ['dealCustomFieldData' => []];
        $response = new Response(200, [], json_encode($apiResponse));
        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/deals/789/dealCustomFieldData',
            ['headers' => $headers]
        )->willReturn($response);

        $this->get(789)->shouldReturn(['id' => 789]);
    }

    function it_should_deal_with_bad_responses(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];

        $apiResponse = [];

        $response = new Response(200, [], json_encode($apiResponse));
        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/deals/789',
            ['headers' => $headers]
        )->willReturn($response);
        $this->shouldThrow(RuntimeException::class)->during('get', [789]);
    }

    function it_should_add_custom_fields(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];
        // https://1499693424850.api-us1.com/api/3/deals/789
        $apiResponse = [
            'deal' => [
                'id' => 789,
            ],
        ];
        $response = new Response(200, [], json_encode($apiResponse));
        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/deals/789',
            ['headers' => $headers]
        )->willReturn($response);

        // https://1499693424850.api-us1.com/api/3/deals/789/dealCustomFieldData
        $apiResponse = [
            'dealCustomFieldData' => [
                [
                    'customFieldId' => 1,
                    'fieldValue' => '3',
                ],
                [
                    'customFieldId' => 2,
                    'fieldValue' => '5',
                ],
                [
                    'customFieldId' => 3,
                    'fieldValue' => '7',
                ]
            ]
        ];
        $response = new Response(200, [], json_encode($apiResponse));
        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/deals/789/dealCustomFieldData',
            ['headers' => $headers]
        )->willReturn($response);

        $expected = [
            'id' => 789,
            'custom_field_1' => '3',
            'custom_field_2' => '5',
            'custom_field_3' => '7',
        ];
        $this->get(789)->shouldReturn($expected);
    }
}
