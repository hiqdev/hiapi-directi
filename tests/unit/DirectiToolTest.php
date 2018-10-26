<?php

namespace hiapi\directi\tests\unit;

use hiapi\directi\DirectiTool;
use hiapi\directi\HttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;


class DirectiToolTest extends \PHPUnit\Framework\TestCase
{
    protected $testUri = 'http://test.test/test';

    public function testContactSet()
    {
        $base = new class {};

        /** @noinspection PhpUnhandledExceptionInspection */
        $tool = new DirectiTool($base, [
            'url' => '1',
            'login' => '753669',
            'password' => 'UiQJ1uQHVlMasbrPTZMQ2pFcKHeHfEPY',
            'customer_id' => '19371930'
        ]);

        $httpClientMock = $this->getMockBuilder(HttpClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['performRequest'])
            ->getMock();

        $httpClientMock->method('performRequest')
            ->with('POST', 'contacts/add.json', [
                'contact-id' => NULL,
                'name' => 'WhoisProtectService.net',
                'company' => 'PROTECTSERVICE, LTD.',
                'email' => 'silverfires9.me@whoisprotectservice.net',
                'address-line-1' => 'Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601',
                'address-line-2' => NULL,
                'address-line-3' => NULL,
                'city' => 'Limassol',
                'zipcode' => '3025',
                'state' => NULL,
                'country' => 'CY',
                'phone-cc' => '357',
                'phone' => '95713635',
                'fax-cc' => '357',
                'fax' => '95713635',
            ],  null, null, [
                'type' => 'Contact',
                'customer-id' => '19371930',
                'auth-userid' => '753669',
                'api-key' => 'UiQJ1uQHVlMasbrPTZMQ2pFcKHeHfEPY',
            ])
            ->willReturn(80069410);

        $reflection = new \ReflectionObject($tool);
        $propertyReflection = $reflection->getProperty('httpClient');
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($tool, $httpClientMock);

        $result = $tool->contactCreate([
            'id' => NULL,
            'type' => 'domain',
            'obj_id' => '25844219',
            'type_id' => '10532410',
            'state_id' => '1000248',
            'roid' => NULL,
            'client_id' => '2024202',
            'seller_id' => '1004697',
            'client' => 'solex',
            'epp_id' => 'MR_25844219',
            'name' => 'WhoisProtectService.net',
            'first_name' => 'WhoisProtectService.net',
            'last_name' => '',
            'birth_date' => NULL,
            'email' => 'silverfires9.me@whoisprotectservice.net',
            'abuse_email' => NULL,
            'passport_no' => NULL,
            'passport_date' => NULL,
            'passport_by' => NULL,
            'organization' => 'PROTECTSERVICE, LTD.',
            'street1' => 'Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601',
            'street2' => NULL,
            'street3' => NULL,
            'city' => 'Limassol',
            'province' => NULL,
            'province_name' => NULL,
            'postal_code' => '3025',
            'country' => 'cy',
            'country_name' => 'Cyprus',
            'voice_phone' => '+357.95713635',
            'fax_phone' => '+357.95713635',
            'password' => '',
            'created_date' => NULL,
            'updated_date' => NULL,
            'seller' => 'ahnames',
            'client_type' => 'client',
            'create_time' => '2018-10-24 15:55:20.195783',
            'update_time' => '2018-10-25 14:30:57.329301',
            'remote' => '',
        ]);

        $this->assertSame($result, ['id' => 80069410]);
    }

    public function testContactCreate()
    {
        $id = 1234213;
        $client = $this->mockGuzzleClient();
        $client->method('request')
            ->with('POST', 'contacts/add.json', [
                'body' => 'name=WhoisProtectService.net&company=PROTECTSERVICE%2C+LTD.&email=silverfires9.me%40whoisprotectservice.net&address-line-1=Agios+Fylaxeos+66+and+Chr.+Perevou+2%2C+Kalia+Court%2C+off.+601&city=Limassol&zipcode=3025&country=CY&phone-cc=357&phone=95713635&fax-cc=357&fax=95713635&type=Contact&customer-id=98765432&auth-userid=654321&api-key=absdefghIJKLMOPQRSTUvwxyz1234567',
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ])
            ->willReturn(new Response(200, [], $id));
        $tool = $this->createTool($client);

        $result = $tool->contactCreate([
            'id' => NULL,
            'type' => 'domain',
            'obj_id' => '25844219',
            'type_id' => '10532410',
            'state_id' => '1000248',
            'roid' => NULL,
            'client_id' => '2024202',
            'seller_id' => '1004697',
            'client' => 'solex',
            'epp_id' => 'MR_25844219',
            'name' => 'WhoisProtectService.net',
            'first_name' => 'WhoisProtectService.net',
            'last_name' => '',
            'birth_date' => NULL,
            'email' => 'silverfires9.me@whoisprotectservice.net',
            'abuse_email' => NULL,
            'passport_no' => NULL,
            'passport_date' => NULL,
            'passport_by' => NULL,
            'organization' => 'PROTECTSERVICE, LTD.',
            'street1' => 'Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601',
            'street2' => NULL,
            'street3' => NULL,
            'city' => 'Limassol',
            'province' => NULL,
            'province_name' => NULL,
            'postal_code' => '3025',
            'country' => 'cy',
            'country_name' => 'Cyprus',
            'voice_phone' => '+357.95713635',
            'fax_phone' => '+357.95713635',
            'password' => '',
            'created_date' => NULL,
            'updated_date' => NULL,
            'seller' => 'ahnames',
            'client_type' => 'client',
            'create_time' => '2018-10-24 15:55:20.195783',
            'update_time' => '2018-10-25 14:30:57.329301',
            'remote' => '',
        ]);
        $this->assertSame([
            'id' => $id,
        ], $result);
    }

    protected function mockGuzzleClient()
    {
        return $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['request'])
            ->getMock();
    }

    protected function createTool($guzzleClient)
    {
        $base = new class {};

        /** @noinspection PhpUnhandledExceptionInspection */
        $tool = new DirectiTool($base, [
            'url' => $this->testUri,
            'login' => '654321',
            'password' => 'absdefghIJKLMOPQRSTUvwxyz1234567',
            'customer_id' => '98765432',
        ]);
        $tool->setHttpClient(new HttpClient($guzzleClient));

        return $tool;
    }
}
