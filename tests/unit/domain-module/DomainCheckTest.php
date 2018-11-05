<?php

namespace hiapi\directi\tests\unit\domain_module;

use hiapi\directi\tests\unit\TestCase;

class DomainCheckTest extends TestCase
{
    private $command = 'domains/available.json';

    public function testDomainCheck()
    {
        $domainName = 'silverfires.com';

        $requestQuery = 'domain-name=silverfires&tlds=com';
        $responseBody = json_encode([
            $domainName => [
                'classkey'  => 'domcno',
                'status'    => 'available',
            ],
        ]);

        $tool = $this->mockGet($this->command, $requestQuery, $responseBody);
        $result = $tool->domainsCheck([
            'domains' => [
                    0 => $domainName,
                ],
            'uncached' => true,
        ]);

        $this->assertSame([
            $domainName => [
                'classkey'  => 'domcno',
                'status'    => 'available',
            ],
        ], $result);
    }
}
