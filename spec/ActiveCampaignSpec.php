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
    protected $client;
    protected $account;
    protected $token;

    function let(Client $client)
    {
        $this->beConstructedWith($client);
    }

    /**
     * Set constructor parameters.
     *
     * Overridden so we can grab the arguments for later usage.
     */
    function beConstructedWith($client, $account = null, $token = null)
    {
        parent::beConstructedWith($client, $account, $token);
        $this->client = $client;
        $this->account = $account;
        $this->token = $token;
    }

    /**
     * Expect a request.
     */
    function expectRequest($path, $reply)
    {
        $headers = [
            'Api-Token' => $this->token,
        ];
        $response = $this->response($reply);
        $this->client->request(
            'GET',
            'https://' . $this->account. '.api-us1.com/api/3/' . $path,
            ['headers' => $headers]
        )->willReturn($response)->shouldBeCalled();
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

        //https://1499693424850.api-us1.com/api/3/deals/789
        $this->expectRequest('deals/789', [
            'deal' => [
                'id' => 789,
            ],
        ]);

        // https://1499693424850.api-us1.com/api/3/deals/789/dealCustomFieldData
        $this->expectRequest('deals/789/dealCustomFieldData', ['dealCustomFieldData' => []]);

        $this->getDeal(789)->shouldReturn(['id' => 789]);
    }

    function it_should_deal_with_bad_responses(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        $headers = [
            'Api-Token' => '456',
        ];

        $this->expectRequest('deals/789', []);

        $this->shouldThrow(RuntimeException::class)->during('getDeal', [789]);
    }

    function it_should_add_deal_custom_fields(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');

        // https://1499693424850.api-us1.com/api/3/deals/789
        $this->expectRequest('deals/789', [
            'deal' => [
                'id' => 789,
            ],
        ]);

        // https://1499693424850.api-us1.com/api/3/deals/789/dealCustomFieldData
        $this->expectRequest('deals/789/dealCustomFieldData', [
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

        $this->expectRequest('deals/42/dealCustomFieldData', [
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

        $this->expectRequest('deals/42/dealCustomFieldData', ['dealCustomFieldData' => []]);

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

        // https://1499693424850.api-us1.com/api/3/deals/789
        $this->expectRequest('deals/789', [
            'deal' => [
                'id' => 789,
            ],
        ]);

        // https://1499693424850.api-us1.com/api/3/deals/789/dealCustomFieldData
        $this->expectRequest('deals/789/dealCustomFieldData', [
            'dealCustomFieldData' => [
                [
                    'customFieldId' => 1,
                    'fieldValue' => null,
                ],
            ]
        ]);

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

        $this->expectRequest('deals/42/dealCustomFieldData', [
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

        //https://1499693424850.api-us1.com/api/3/contacts/688
        $this->expectRequest('contacts/688', [
            'contact' => [
                'id' => 688,
            ],
        ]);

        //https://1499693424850.api-us1.com/api/3/contacts/688/contactTags
        $this->expectRequest('contacts/688/contactTags', ['contactTags' => []]);

        $this->getContact(688)->shouldReturn(['id' => 688, 'tags' => '']);
    }

    /**
     * Test that field data gets added to the contact.
     *
     * Field data should end up as "field." . strtolower(perstag).
     */
    function it_should_get_contact_fields(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');

        //https://1499693424850.api-us1.com/api/3/contacts/688
        $this->expectRequest('contacts/688', [
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

        //https://1499693424850.api-us1.com/api/3/contacts/688/contactTags
        $this->expectRequest('contacts/688/contactTags', ['contactTags' => []]);


        //https://1499693424850.api-us1.com/api/3/fields
        $this->expectRequest('fields', [
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

        $expected = [
            'id' => 688,
            'field.thelabel' => 'field-value',
            'field.emptyfield' => '',
            'tags' => '',
        ];

        $this->getContact(688)->shouldReturn($expected);
    }

    function it_should_get_contact_tags(Client $client)
    {
        $this->beConstructedWith($client, '123', '456');
        //https://1499693424850.api-us1.com/api/3/contacts/688
        $this->expectRequest('contacts/688', [
            'contact' => [
                'id' => 688,
            ],
        ]);

        //https://1499693424850.api-us1.com/api/3/contacts/688/contactTags
        $this->expectRequest('contacts/688/contactTags', [
            'contactTags' => [
                ['id' => '4115'],
                ['id' => '5489'],
                ['id' => '6783'],
            ]
        ]);

        //https://1499693424850.api-us1.com/api/3/contactTags/<id>/tag
        $this->expectRequest('contactTags/4115/tag', ['tag' => ['tag' => 'velkomst-flow-skipped']]);
        $this->expectRequest('contactTags/5489/tag', ['tag' => ['tag' => 'newsletter']]);
        $this->expectRequest('contactTags/6783/tag', ['tag' => ['tag' => 'e-bog-syv-skridt-til-succes']]);

        $this->getContact(688)->shouldReturn([
            'id' => 688,
            'tags' => "velkomst-flow-skipped, newsletter, e-bog-syv-skridt-til-succes",
        ]);
    }
}
