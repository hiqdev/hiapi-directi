<?php

namespace hiapi\directi\tests\unit;

use hiapi\directi\DirectiTool;
use hiapi\directi\HttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class DirectiToolTest extends \PHPUnit\Framework\TestCase
{
    protected $testUri = 'http://test.test/test';

    /// TODO later because needs API base mocking
    public function no_testDomainRegister()
    {
        $result = $tool->domainRegister([

        ]);
        $this->assertSame([

        ], $result);
    }

    protected $contact1 = [
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
        'email' => 'contact1.me@whoisprotectservice.net',
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
    ];

    public function testContactCreate()
    {
        $id = 1234213;
        $client = $this->mockGuzzleClient();
        $client->method('request')
            ->with('POST', 'contacts/add.json', [
                'body' => 'name=WhoisProtectService.net&company=PROTECTSERVICE%2C+LTD.&email=contact1.me%40whoisprotectservice.net&address-line-1=Agios+Fylaxeos+66+and+Chr.+Perevou+2%2C+Kalia+Court%2C+off.+601&city=Limassol&zipcode=3025&country=CY&phone-cc=357&phone=95713635&fax-cc=357&fax=95713635&type=Contact&customer-id=98765432&auth-userid=753669&api-key=UiQJ1uQHVlMasbrPTZMQ2pFcKHeHfEPY',
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ])
            ->willReturn(new Response(200, [], $id));
        $tool = $this->createTool($client);

        $result = $tool->contactCreate($this->contact1);
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
            'login' => '753669',
            'password' => 'UiQJ1uQHVlMasbrPTZMQ2pFcKHeHfEPY',
            'customer_id' => '98765432',
        ]);
        $tool->setHttpClient(new HttpClient($guzzleClient));

        return $tool;
    }

    public function testDomenInfo()
    {
        $domainInfoData = [
            'domain' => 'silverfires777.com',
            'password' => NULL,
            'id' => 25844319,
        ];

        $responseBody = '{"billingcontact":{"faxnocc":"357","emailaddr":"silverfires777.com@whoisprotectservice.net","country":"CY","contactstatus":"Active","contacttype":[],"name":"WhoisProtectService.net","parentkey":"999999999_999999998_753669","company":"PROTECTSERVICE, LTD.","city":"Limassol","address1":"Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601","contactid":"80083695","telnocc":"357","zip":"3025","telno":"95713635","faxno":"95713635","customerid":"19371930","type":"Contact"},"description":"silverfires777.com","eaqid":"0","addons":[],"currentstatus":"Active","paused":"false","noOfNameServers":"2","customercost":"0.0","domainstatus":["sixtydaylock"],"raaVerificationStartTime":"1540560893","recurring":"false","autoRenewTermType":"LONG_TERM","entitytypeid":"3","isImmediateReseller":"true","productkey":"domcno","dnssec":[],"classkey":"domcno","orderSuspendedByParent":"false","endtime":"1572096892","entityid":"84311819","jumpConditions":[],"multilingualflag":"f","cns":{},"gdpr":{"enabled":"true","eligible":"true"},"actioncompleted":"0","registrantcontactid":"80083695","domainname":"silverfires777.com","productcategory":"domorder","admincontactid":"80083695","isprivacyprotected":"false","techcontactid":"80083695","orderid":"84311819","admincontact":{"faxnocc":"357","emailaddr":"silverfires777.com@whoisprotectservice.net","country":"CY","contactstatus":"Active","contacttype":[],"name":"WhoisProtectService.net","parentkey":"999999999_999999998_753669","company":"PROTECTSERVICE, LTD.","city":"Limassol","address1":"Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601","contactid":"80083695","telnocc":"357","zip":"3025","telno":"95713635","faxno":"95713635","customerid":"19371930","type":"Contact"},"parentkey":"999999999_999999998_753669","orderstatus":["transferlock"],"creationtime":"1540560892","classname":"com.logicboxes.foundation.sfnb.order.domorder.DomCno","techcontact":{"faxnocc":"357","emailaddr":"silverfires777.com@whoisprotectservice.net","country":"CY","contactstatus":"Active","contacttype":[],"name":"WhoisProtectService.net","parentkey":"999999999_999999998_753669","company":"PROTECTSERVICE, LTD.","city":"Limassol","address1":"Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601","contactid":"80083695","telnocc":"357","zip":"3025","telno":"95713635","faxno":"95713635","customerid":"19371930","type":"Contact"},"customerid":"19371930","ns2":"ns2.topdns.me","ns1":"ns1.topdns.me","resellercost":"0","billingcontactid":"80083695","autoRenewAttemptDuration":"30","privacyprotectedallowed":"true","domsecret":"5npuY-XQnJ","raaVerificationStatus":"Pending","isOrderSuspendedUponExpiry":"false","allowdeletion":"true","bulkwhoisoptout":"t","registrantcontact":{"faxnocc":"357","emailaddr":"silverfires777.com@whoisprotectservice.net","country":"CY","contactstatus":"Active","contacttype":[],"name":"WhoisProtectService.net","parentkey":"999999999_999999998_753669","company":"PROTECTSERVICE, LTD.","city":"Limassol","address1":"Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601","contactid":"80083695","telnocc":"357","zip":"3025","telno":"95713635","faxno":"95713635","customerid":"19371930","type":"Contact"},"moneybackperiod":"4"}';

        $client = $this->mockGuzzleClient();
        $client->method('request')
            ->with('GET', 'domains/details-by-name.json?domain-name=silverfires777.com&options=All&auth-userid=753669&api-key=UiQJ1uQHVlMasbrPTZMQ2pFcKHeHfEPY')
            ->willReturn(new Response(200, [], $responseBody));
        $tool = $this->createTool($client);

        $result = $tool->domainInfo($domainInfoData);

        $this->assertSame([
            'id'                => '84311819',
            'domain'            => 'silverfires777.com',
            'password'          => '5npuY-XQnJ',
            'registrant'        => '80083695',
            'admin'             => '80083695',
            'billing'           => '80083695',
            'tech'              => '80083695',
            'created_date'      => '2018-10-26 13:34:52',
            'expiration_date'   => '2019-10-26 13:34:52',
            'nameservers'       => 'ns1.topdns.me,ns2.topdns.me',
        ], $result);
    }
}
