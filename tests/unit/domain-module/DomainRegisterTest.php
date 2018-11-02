<?php

namespace hiapi\directi\tests\unit\domain_module;

use GuzzleHttp\Psr7\Response;
use hiapi\directi\modules\ContactModule;
use hiapi\directi\tests\unit\DirectiToolTestBase;

class DomainRegisterTest extends DirectiToolTestBase
{
    private $command = 'domains/register.json';

    public function testDomainRegister()
    {
        $domainName = 'silverfires.com';
        $domainId   = 25844311;

        $domainRegisterData = [
            'domain'        => $domainName,
            'password'      => 'adf-AA01',
            'period'        => 1,
            'registrant'    => '2024202',
            'admin'         => '2024202',
            'tech'          => '2024202',
            'billing'       => '2024202',
            'coupon'        => NULL,
            'product'       => NULL,
            'product_id'    => NULL,
            'client_id'     => 2024202,
            'object'        => 'domain',
            'license'       => NULL,
            'nss'           => [
                'ns1.topdns.me',
                'ns2.topdns.me'
            ],
            'client'        => 'solex',
            'zone_id'       => 1000211,
            'type'          => 'register',
            'wait'          => 100,
            'id'            => $domainId,
            '_uuid'         => '5bd6d0e5b89cb',
        ];

        $contactData = [
            'id' => '25844175',
            'type' => 'domain',
            'obj_id' => '25844175',
            'type_id' => '10532410',
            'state_id' => '1000248',
            'roid' => NULL,
            'client_id' => '2024202',
            'seller_id' => '1004697',
            'client' => 'solex',
            'epp_id' => 'MR_25844175',
            'name' => 'WhoisProtectService.net',
            'first_name' => 'WhoisProtectService.net',
            'last_name' => '',
            'birth_date' => NULL,
            'email' => 'silverfires.com@whoisprotectservice.net',
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
            'create_time' => '2018-10-29 14:04:06.880312',
            'update_time' => '2018-10-29 16:57:56.930721',
            'remote' => '',
        ];
        $contactRemoteId = [
            'id' => '80079260',
        ];
        $fullContactsData = [
            'id'                    => '25844175',
            'domain'                => 'silverfires.com',
            'client_email'          => 'sol@solex.me',
            'expires'               => NULL,
            'state'                 => 'new',
            'whois_protected'       => '1',
            'abuse_email'           => 'abuse@ahnames.com',
            'registered_through'    => 'AHnames.com  https://www.AHnames.com/',
            'client'                => 'solex',
            'client_id'             => '2024202',
            'registrant'            => $contactData,
            'registrant_eppid'      => 'MR_25844175WP',
            'admin'                 => $contactData,
            'admin_eppid'           => 'MR_25844175WP',
            'tech'                  => $contactData,
            'tech_eppid'            => 'MR_25844175WP',
            'billing'               => $contactData,
            'billing_eppid'         => 'MR_25844175WP',
        ];

        $contactModule = $this->mockModule(ContactModule::class, ['contactSet']);

        $contactModule->expects($this->once())
            ->method('contactSet')
            ->with($contactData)
            ->willReturn($contactRemoteId);

        $client = $this->mockGuzzleClient();
        $requestQuery = sprintf('domain-name=%s&years=1&ns=ns1.topdns.me&ns=ns2.topdns.me&reg-contact-id=80079260' .
            '&admin-contact-id=80079260&tech-contact-id=80079260&billing-contact-id=80079260' .
            '&customer-id=19371930&invoice-option=KeepInvoice&protect-privacy=false&auth-userid=%s&api-key=%s',
            $domainName,
            $this->authUserId,
            $this->apiKey);
        $responseBody = '{"actiontypedesc":"Registration of silverfires.com for 1 year",' .
            '"unutilisedsellingamount":"-14.990","sellingamount":"-14.990","entityid":"84334750",' .
            '"actionstatus":"Success","status":"Success","eaqid":"514034683","customerid":"19371930",' .
            '"description":"silverfires.com","actiontype":"AddNewDomain","invoiceid":"86624444",' .
            '"sellingcurrencysymbol":"USD","actionstatusdesc":"Domain registration completed Successfully"}';

        $client->method('request')
            ->with('POST', $this->command, [
                'body' => $requestQuery,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ])
            ->willReturn(new Response(200, [], $responseBody));

        $tool = $this->createTool($this->mockBase(['domainGetWPContactsInfo']), $client);
        $tool->setModule('contact', $contactModule);

        $tool->base->method('domainGetWPContactsInfo')
            ->with($domainRegisterData)
            ->willReturn($fullContactsData);

        $result = $tool->domainRegister($domainRegisterData);
        $this->assertSame([
                'id'     => '84334750',
                'domain' => 'silverfires.com',
        ], $result);
    }
}
