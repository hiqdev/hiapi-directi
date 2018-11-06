<?php /** @noinspection PhpParamsInspection */

namespace hiapi\directi\tests\unit\domain_module;

use GuzzleHttp\Psr7\Response;
use hiapi\directi\modules\DomainModule;
use hiapi\directi\tests\unit\TestCase;

class DomainSetContactsTest extends TestCase
{
    private $command = 'domains/modify-contact.json';

    public function testDomainSetContacts()
    {
        $domainName     = 'silverfires.com';
        $domainRemoteId = 84383857;

        $domainSetContactsData = [
            'id'                    => $domainLocalId,
            'domain'                => $domainName,
            'client_email'          => 'sol@solex.me',
            'expires'               => NULL,
            'state'                 => 'ok',
            'whois_protected'       => NULL,
            'abuse_email'           => 'abuse@ahnames.com',
            'registered_through'    => 'AHnames.com  https://www.AHnames.com/',
            'client'                => 'solex',
            'client_id'             => '2024202',
            'registrant'            => 'MR_2024202N',
            'registrant_eppid'      => 'MR_2024202N',
            'admin'                 => 'MI_3988176N',
            'admin_eppid'           => 'MI_3988176N',
            'tech'                  => 'MR_2024202N',
            'tech_eppid'            => 'MR_2024202N',
            'billing'               => 'MR_2024202N',
            'billing_eppid'         => 'MR_2024202N',
            'registrant_remoteid'   => '80032054',
            'admin_remoteid'        => '80187184',
            'tech_remoteid'         => '80032054',
            'billing_remoteid'      => '80032054',
        ];

        $domainModule   = $this->mockModule(DomainModule::class, ['domainGetId']);
        $domainModule->expects($this->once())
            ->method('domainGetId')
            ->with($domainSetContactsData)
            ->willReturn([
                'id' => $domainRemoteId,
            ]);

        $requestQuery = "reg-contact-id=80032054&admin-contact-id=80187184&tech-contact-id=80032054&" .
                        "billing-contact-id=80032054&order-id=${domainRemoteId}";

        $responseBody = json_encode([
            'actionstatusdesc'  => 'Modification of Contact Details of ' . $domainName,
            'entityid'          => $domainRemoteId,
            'actionstatus'      => 'PENDING_REGISTRANT_AUTHORIZATION',
            'status'            => 'Success',
            'eaqid'             => '514705743',
            'currentaction'     => '514705743',
            'description'       => $domainName,
            'actiontype'        => 'ModContact',
            'actionstatusdesc'  => 'Pending Registrant Approval'
        ]);

        $tool = $this->mockPost($this->command, $requestQuery, $responseBody);
        $tool->setModule('domain', $domainModule);
        $domainModule->tool = $tool;

        $result = $tool->domainSetContacts($domainSetContactsData);
        $this->assertSame([
            'id'     => (string)$domainRemoteId,
            'domain' => $domainName,
        ], $result);
    }
}
