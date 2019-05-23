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

    /**
     * Helper for creating responses.
     */
    function response($data = null, $code = 200, $headers = [])
    {
        return new Response($code, $headers, $data ? json_encode($data) : null);
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
        )->willReturn($this->response());

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
        $response = $this->response([
            'deal' => [
                'id' => 789,
            ],
        ]);
        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/deals/789',
            ['headers' => $headers]
        )->willReturn($response);

        // https://1499693424850.api-us1.com/api/3/deals/789/dealCustomFieldData
        $response = $this->response(['dealCustomFieldData' => []]);
        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/deals/789/dealCustomFieldData',
            ['headers' => $headers]
        )->willReturn($response);

        $this->getDeal(789)->shouldReturn(['id' => 789]);
    }

    function it_should_deal_with_bad_responses(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];

        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/deals/789',
            ['headers' => $headers]
        )->willReturn($this->response([]));

        $this->shouldThrow(RuntimeException::class)->during('getDeal', [789]);
    }

    function it_should_add_deal_custom_fields(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];
        // https://1499693424850.api-us1.com/api/3/deals/789
        $response = $this->response([
            'deal' => [
                'id' => 789,
            ],
        ]);

        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/deals/789',
            ['headers' => $headers]
        )->willReturn($response);

        // https://1499693424850.api-us1.com/api/3/deals/789/dealCustomFieldData
        $response = $this->response([
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
        ]);

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
        $this->getDeal(789)->shouldReturn($expected);
    }

    function it_should_throw_an_bad_deal_custom_field_names()
    {
        $this->shouldThrow()->duringUpdateDealCustomField(1, 'bad_id', 'value');
        $this->shouldThrow()->duringUpdateDealCustomField(1, 'cåstom_field_1', 'value');
        $this->shouldThrow()->duringUpdateDealCustomField(1, 'custom_field_2_stuff', 'value');
        $this->shouldThrow()->duringUpdateDealCustomField(1, 'custom_field_', 'value');
        $this->shouldThrow()->duringUpdateDealCustomField(1, '1', 'value');
    }

    function it_should_update_existing_deal_custom_field(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];

        $response = $this->response([
            'dealCustomFieldData' => [
                [
                    'id' => 11,
                    'customFieldId' => 1,
                ],
                [
                    'id' => 12,
                    'customFieldId' => 2,
                ],
                [
                    'id' => 13,
                    'customFieldId' => 3,
                ]
            ]
        ]);

        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/deals/42/dealCustomFieldData',
            ['headers' => $headers]
        )->willReturn($response);

        $data = [
            'dealCustomFieldDatum' => [
                'fieldValue' => 3,
            ],
        ];

        $client->request(
            'PUT',
            'https://123.api-us1.com/api/3/dealCustomFieldData/12',
            ['json' => $data, 'headers' => $headers]
        )->willReturn($this->response())->shouldBeCalled();

        $this->updateDealCustomField(42, 'custom_field_2', 3);
    }

    function it_should_create_unset_deal_custom_field(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];

        $response = $this->response(['dealCustomFieldData' => []]);

        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/deals/42/dealCustomFieldData',
            ['headers' => $headers]
        )->willReturn($response);

        $data = [
            'dealCustomFieldDatum' => [
                'dealId' => 42,
                'customFieldId' => 2,
                'fieldValue' => 3,
            ],
        ];

        $client->request(
            'POST',
            'https://123.api-us1.com/api/3/dealCustomFieldData',
            ['json' => $data, 'headers' => $headers]
        )->willReturn($this->response())->shouldBeCalled();

        $this->updateDealCustomField(42, 'custom_field_2', 3);
    }

    function it_should_handle_empty_deal_custom_fields(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];
        // https://1499693424850.api-us1.com/api/3/deals/789
        $response = $this->response([
            'deal' => [
                'id' => 789,
            ],
        ]);

        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/deals/789',
            ['headers' => $headers]
        )->willReturn($response);

        // https://1499693424850.api-us1.com/api/3/deals/789/dealCustomFieldData
        $response = $this->response([
            'dealCustomFieldData' => [
                [
                    'customFieldId' => 1,
                    'fieldValue' => null,
                ],
            ]
        ]);

        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/deals/789/dealCustomFieldData',
            ['headers' => $headers]
        )->willReturn($response);

        $expected = [
            'id' => 789,
            'custom_field_1' => null,
        ];
        $this->getDeal(789)->shouldReturn($expected);
    }

    function it_should_not_update_existing_deal_custom_field_if_value_is_the_same(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];

        $response = $this->response([
            'dealCustomFieldData' => [
                [
                    'id' => 11,
                    'customFieldId' => 1,
                ],
                [
                    'id' => 12,
                    'customFieldId' => 2,
                    'fieldValue' => 3,
                ],
                [
                    'id' => 13,
                    'customFieldId' => 3,
                ]
            ]
        ]);

        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/deals/42/dealCustomFieldData',
            ['headers' => $headers]
        )->willReturn($response);

        $data = [
            'dealCustomFieldDatum' => [
                'fieldValue' => 3,
            ],
        ];

        $client->request(
            'PUT',
            'https://123.api-us1.com/api/3/dealCustomFieldData/12',
            Argument::any()
        )->shouldNotBeCalled();

        $this->updateDealCustomField(42, 'custom_field_2', 3);
    }

    function it_should_get_contacts(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];
        //https://1499693424850.api-us1.com/api/3/contacts/688
        $response = $this->response([
            'contact' => [
                'id' => 688,
            ],
        ]);
        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/contacts/688',
            ['headers' => $headers]
        )->willReturn($response);


        $this->getContact(688)->shouldReturn(['id' => 688]);
    }

    /**
     * Test that field data gets added to the contact.
     *
     * Field data should end up as "field." . strtolower(perstag).
     */
    function it_should_get_contact_fields(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];
        //https://1499693424850.api-us1.com/api/3/contacts/688
        $response = $this->response([
            'contact' => [
                'id' => 688,
            ],
            'fieldValues' => [
                [
                    'field' => 3,
                    'value' => 'field-value',
                ],
                // "Undefined field" shouldn't trip it up.
                [
                    'field' => 4,
                    'value' => 'should not get through',
                ],
            ]
        ]);

        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/contacts/688',
            ['headers' => $headers]
        )->willReturn($response);


        //https://1499693424850.api-us1.com/api/3/fields
        $response = $this->response([
            'fields' => [
                [
                    'id' => 3,
                    'perstag' => 'THELABEL',
                ],
                // Field without a value.
                [
                    'id' => 2,
                    'perstag' => 'EMPTYFIELD',
                ],
            ]
        ]);
        $client->request(
            'GET',
            'https://123.api-us1.com/api/3/fields',
            ['headers' => $headers]
        )->willReturn($response);

        $expected = [
            'id' => 688,
            'field.thelabel' => 'field-value',
            'field.emptyfield' => '',
        ];

        $this->getContact(688)->shouldReturn($expected);
    }
}
