<?php

namespace hiapi\directi\tests\unit\domain_module;

use GuzzleHttp\Psr7\Response;
use hiapi\directi\tests\unit\TestCase;

class DomainInfoTest extends TestCase
{
    private $command = 'details-by-name.json';

    public function testDomainInfo()
    {
        $domainName = 'silverfires.com';

        $domainInfoData = [
            'domain'    => $domainName,
            'password'  => NULL,
            'id'        => 25844319,
        ];

        $client = $this->mockGuzzleClient();

        $requestQuery = sprintf('domains/%s?domain-name=%s&options=All&auth-userid=%s&api-key=%s',
            $this->command,
            $domainName,
            $this->authUserId,
            $this->apiKey);

        $responseBody = '{"billingcontact":{"faxnocc":"357","emailaddr":"silverfires.com@whoisprotectservice.net",' .
            '"country":"CY","contactstatus":"Active","contacttype":[],"name":"WhoisProtectService.net",' .'
            "parentkey":"999999999_999999998_753669","company":"PROTECTSERVICE, LTD.","city":"Limassol",' .
            '"address1":"Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601","contactid":"80083695",' .
            '"telnocc":"357","zip":"3025","telno":"95713635","faxno":"95713635","customerid":"19371930",' .
            '"type":"Contact"},"description":"silverfires.com","eaqid":"0","addons":[],"currentstatus":"Active",' .
            '"paused":"false","noOfNameServers":"2","customercost":"0.0","domainstatus":["sixtydaylock"],' .
            '"raaVerificationStartTime":"1540560893","recurring":"false","autoRenewTermType":"LONG_TERM",' .
            '"entitytypeid":"3","isImmediateReseller":"true","productkey":"domcno","dnssec":[],"classkey":"domcno",' .
            '"orderSuspendedByParent":"false","endtime":"1572096892","entityid":"84311819","jumpConditions":[],' .
            '"multilingualflag":"f","cns":{},"gdpr":{"enabled":"true","eligible":"true"},"actioncompleted":"0",' .
            '"registrantcontactid":"80083695","domainname":"silverfires.com","productcategory":"domorder",' .
            '"admincontactid":"80083695","isprivacyprotected":"false","techcontactid":"80083695",' .
            '"orderid":"84311819","admincontact":{"faxnocc":"357",' .
            '"emailaddr":"silverfires.com@whoisprotectservice.net","country":"CY",' .
            '"contactstatus":"Active","contacttype":[],"name":"WhoisProtectService.net",' .
            '"parentkey":"999999999_999999998_753669","company":"PROTECTSERVICE, LTD.","city":"Limassol",' .
            '"address1":"Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601","contactid":"80083695",' .
            '"telnocc":"357","zip":"3025","telno":"95713635","faxno":"95713635","customerid":"19371930",' . '
            "type":"Contact"},"parentkey":"999999999_999999998_753669","orderstatus":["transferlock"],' .
            '"creationtime":"1540560892","classname":"com.logicboxes.foundation.sfnb.order.domorder.DomCno",' .
            '"techcontact":{"faxnocc":"357","emailaddr":"silverfires.com@whoisprotectservice.net","country":"CY",' .
            '"contactstatus":"Active","contacttype":[],"name":"WhoisProtectService.net",' .
            '"parentkey":"999999999_999999998_753669","company":"PROTECTSERVICE, LTD.","city":"Limassol",' .
            '"address1":"Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601","contactid":"80083695",' .
            '"telnocc":"357","zip":"3025","telno":"95713635","faxno":"95713635","customerid":"19371930",' .
            '"type":"Contact"},"customerid":"19371930","ns2":"ns2.topdns.me","ns1":"ns1.topdns.me",' .
            '"resellercost":"0","billingcontactid":"80083695","autoRenewAttemptDuration":"30",' .
            '"privacyprotectedallowed":"true","domsecret":"5npuY-XQnJ","raaVerificationStatus":"Pending",' .
            '"isOrderSuspendedUponExpiry":"false","allowdeletion":"true","bulkwhoisoptout":"t",' .
            '"registrantcontact":{"faxnocc":"357","emailaddr":"silverfires.com@whoisprotectservice.net",' .
            '"country":"CY","contactstatus":"Active","contacttype":[],"name":"WhoisProtectService.net",' .
            '"parentkey":"999999999_999999998_753669","company":"PROTECTSERVICE, LTD.","city":"Limassol",' .
            '"address1":"Agios Fylaxeos 66 and Chr. Perevou 2, Kalia Court, off. 601","contactid":"80083695",' .
            '"telnocc":"357","zip":"3025","telno":"95713635","faxno":"95713635","customerid":"19371930",' .
            '"type":"Contact"},"moneybackperiod":"4"}';

        $client->method('request')
            ->with('GET', $requestQuery)
            ->willReturn(new Response(200, [], $responseBody));

        $tool = $this->createTool($this->mockBase(), $client);
        $result = $tool->domainInfo($domainInfoData);

        $this->assertSame([
            'id'                => '84311819',
            'domain'            => $domainName,
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
