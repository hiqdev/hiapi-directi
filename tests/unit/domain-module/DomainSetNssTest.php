<?php /** @noinspection PhpComposerExtensionStubsInspection */

/** @noinspection PhpParamsInspection */

namespace hiapi\directi\tests\unit\domain_module;

use hiapi\directi\modules\DomainModule;
use hiapi\directi\tests\unit\TestCase;

class DomainSetNssTest extends TestCase
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

        $requestQuery = "ns=${nameServer}&order-id=${domainRemoteId}";
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

        $tool = $this->mockPost($this->command, $requestQuery, $responseBody);
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
