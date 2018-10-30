<?php

namespace hiapi\directi\tests\unit\domain_module;

use GuzzleHttp\Psr7\Response;
use hiapi\directi\tests\unit\DirectiToolTest;

class DomainRegisterTest
{
    public function testDomainRegister()
    {
        $inputData = array (
            'domain'    => 'silverfires.com',
            'password'  => 'adf-AA01',
            'period' => 1,
            'registrant' => '2024202',
            'admin' => '2024202',
            'tech' => '2024202',
            'billing' => '2024202',
            'coupon' => NULL,
            'product' => NULL,
            'product_id' => NULL,
            'client_id' => 2024202,
            'object' => 'domain',
            'license' => NULL,
            'nss' => ['ns1.topdns.me', 'ns2.topdns.me'],
            'client' => 'solex',
            'zone_id' => 1000211,
            'type' => 'register',
            'wait' => 100,
            'id' => 25844311,
            '_uuid' => '5bd6d0e5b89cb',
        );


        $client = $this->mockGuzzleClient();

        $requestQuery = 'domain-name=silverfires.com&years=1&ns=ns1.topdns.me&ns=ns2.topdns.me&reg-contact-id=80079260&admin-contact-id=80079260&tech-contact-id=80079260&billing-contact-id=80079260&customer-id=19371930&invoice-option=KeepInvoice&protect-privacy=false&auth-userid=753669&api-key=UiQJ1uQHVlMasbrPTZMQ2pFcKHeHfEPY';
        $responseBody = '{"actiontypedesc":"Registration of silverfires.com for 1 year","unutilisedsellingamount":"-14.990","sellingamount":"-14.990","entityid":"84334750","actionstatus":"Success","status":"Success","eaqid":"514034683","customerid":"19371930","description":"silverfires.com","actiontype":"AddNewDomain","invoiceid":"86624444","sellingcurrencysymbol":"USD","actionstatusdesc":"Domain registration completed Successfully"}';

        $client->method('request')
            ->with('POST', 'domains/register.json', [
                'body' => $requestQuery,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ])
            ->willReturn(new Response(200, [], $responseBody));

        $requestQuery = 'contacts/search.json?name=WhoisProtectService.net&email=silverfires.com%40whoisprotectservice.net&customer-id=19371930&no-of-records=10&page-no=1&auth-userid=753669&api-key=UiQJ1uQHVlMasbrPTZMQ2pFcKHeHfEPY';
        $responseBody = '{"recsonpage":"1","result":[{"contact.company":"PROTECTSERVICE, LTD.","entity.currentstatus":"Active","contact.telno":"95713635","whoisValidity":{"valid":"true","invalidData":[]},"entity.entityid":"80079260","contact.creationdt":"1540541263","designated-agent":"true","entity.description":"DomainContact","contact.contactid":"80079260","contact.zip":"3025","contact.faxno":"95713635","contact.timestamp":"2018-10-26 08:07:43.320236+00","contact.faxnocc":"357","contact.city":"Limassol","contact.country":"CY","contact.address1":"Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601","contact.telnocc":"357","contact.name":"WhoisProtectService.net","entity.customerid":"19371930","contact.emailaddr":"silverfires.com@whoisprotectservice.net","contact.type":"Contact"}],"recsindb":"1"}';
        $client->method('request')
            ->with('GET', $requestQuery)
            ->willReturn(new Response(200, [], $responseBody));

        $tool = $this->createTool($client);


        $tool->base->method('domainGetWPContactsInfo')
            ->with($inputData)
            ->willReturn([
                    'id' => '25844175',
                    'domain' => 'silverfires.com',
                    'client_email' => 'sol@solex.me',
                    'expires' => NULL,
                    'state' => 'new',
                    'whois_protected' => '1',
                    'abuse_email' => 'abuse@ahnames.com',
                    'registered_through' => 'AHnames.com  https://www.AHnames.com/',
                    'client' => 'solex',
                    'client_id' => '2024202',
                    'registrant' =>
                        array (
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
                        ),
                    'registrant_eppid' => 'MR_25844175WP',
                    'admin' =>
                        array (
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
                        ),
                    'admin_eppid' => 'MR_25844175WP',
                    'tech' =>
                        array (
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
                        ),
                    'tech_eppid' => 'MR_25844175WP',
                    'billing' =>
                        array (
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
                        ),
                    'billing_eppid' => 'MR_25844175WP',
                ]
            );


        $result = $tool->domainRegister($inputData);
        $this->assertSame([
                'id'     => '84334750',
                'domain' => 'silverfires.com',
        ], $result);
    }
}
