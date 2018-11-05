<?php /** @noinspection PhpParamsInspection */

namespace hiapi\directi\tests\unit\domain_module;

use GuzzleHttp\Psr7\Response;
use hiapi\directi\modules\DomainModule;
use hiapi\directi\tests\unit\DirectiToolTestBase;

class DomainSetNssTest extends DirectiToolTestBase
{
    private $command = 'domains/modify-ns.json';

    public function testDomainSetNss()
    {
        $domainName     = 'silverfires.com';
        $domainRemoteId = '84383857';
        $nameServer     = 'nsx.domain.me';
        $currentAction  = '514554489';

        $domainSetNssData = [
            'domain' => $domainName,
            'nss' => [
                $nameServer => $nameServer,
            ],
            'id' => $domainLocalId,
        ];

        $domainModule = $this->mockModule(DomainModule::class, ['domainGetId']);
        $domainModule->expects($this->once())
            ->method('domainGetId')
            ->with($domainSetNssData)
            ->willReturn([
                'id' => $domainRemoteId,
            ]);

        $client = $this->mockGuzzleClient();
        $requestQuery = sprintf('ns=%s&order-id=%s&auth-userid=%s&api-key=%s',
            $nameServer,
            $domainRemoteId,
            $this->authUserId,
            $this->apiKey);
        $responseBody = json_encode([
            'actiontypedesc'    => "Modification of Nameservers of $domainName to [$nameServer]",
            'entityid'          => $domainRemoteId,
            'actionstatus'      => 'Success',
            "status"            => "Success",
            "eaqid"             => $currentAction,
            'currentaction'     => $currentAction,
            "description"       => $domainName,
            "actiontype"        => "ModNS",
            'actionstatusdesc'  => "Modification Completed Successfully.",
        ]);
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
        $result = $tool->domainSetNss($domainSetNssData);

        $this->assertSame([
            'actiontypedesc'    => "Modification of Nameservers of silverfires.com to [${nameServer}]",
            'entityid'          => $domainRemoteId,
            'actionstatus'      => 'Success',
            'status'            => 'Success',
            'eaqid'             => $currentAction,
            'currentaction'     => $currentAction,
            'description'       => $domainName,
            'actiontype'        => 'ModNS',
            'actionstatusdesc'  => 'Modification Completed Successfully.',
        ], $result);
    }
}
