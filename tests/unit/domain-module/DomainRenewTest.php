<?php /** @noinspection PhpParamsInspection */

namespace hiapi\directi\tests\unit\domain_module;

use GuzzleHttp\Psr7\Response;
use hiapi\directi\modules\DomainModule;
use hiapi\directi\tests\unit\DirectiToolTestBase;

class DomainRenewTest extends DirectiToolTestBase
{
    private $command = 'domains/renew.json';

    public function testDomainRenew()
    {
        $domainName     = 'silverfires.com';
        $domainRemoteId = '84372632';

        $domainRenewData = [
            'domain'        => $domainName,
            'amount'        => '1',
            'period'        => '1',
            'expires'       => NULL,
            'coupon'        => NULL,
            'id'            => 25844196,
            'type'          => 'drenewal',
            'object'        => 'domain',
            'client_id'     => '2024202',
            'seller_id'     => '1004697',
            'expires_time'  => '2019-11-01 09:47:02',
        ];

        $domainModule   = $this->mockModule(DomainModule::class, ['domainGetId']);
        $domainModule->expects($this->once())
            ->method('domainGetId')
            ->with($domainRenewData)
            ->willReturn([
                'id' => $domainRemoteId,
            ]);

        $client = $this->mockGuzzleClient();
        $requestQuery = sprintf('order-id=%s&years=1&exp-date=1572601622' .
            '&invoice-option=KeepInvoice&auth-userid=%s&api-key=%s',
            $domainRemoteId,
            $this->authUserId,
            $this->apiKey
        );
        $responseBody = '{"actiontypedesc":"Renewal of silverfires.com for 1 year",' .
            '"unutilisedsellingamount":"-14.990","sellingamount":"-14.990","entityid":"84372632",' .
            '"actionstatus":"Success","status":"Success","eaqid":"514448554","customerid":"19371930",' .
            '"description":"silverfires.com","actiontype":"RenewDomain","invoiceid":"86704006",' .
            '"sellingcurrencysymbol":"USD","actionstatusdesc":"Domain renewed successfully"}';
        $client->method('request')
            ->with('POST', $this->command, [
                'body'    => $requestQuery,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ])
            ->willReturn(new Response(200, [], $responseBody));

        $tool = $this->createTool($this->mockBase(), $client);
        $domainModule->tool = $tool;
        $tool->setModule('domain', $domainModule);
        $result = $tool->domainRenew($domainRenewData);

        $this->assertSame([
            'actiontypedesc'            => 'Renewal of silverfires.com for 1 year',
            'unutilisedsellingamount'   => '-14.990',
            'sellingamount'             => '-14.990',
            'entityid'                  => '84372632',
            'actionstatus'              => 'Success',
            'status'                    => 'Success',
            'eaqid'                     => '514448554',
            'customerid'                => '19371930',
            'description'               => 'silverfires.com',
            'actiontype'                => 'RenewDomain',
            'invoiceid'                 => '86704006',
            'sellingcurrencysymbol'     => 'USD',
            'actionstatusdesc'          => 'Domain renewed successfully',
        ], $result);
    }
}
