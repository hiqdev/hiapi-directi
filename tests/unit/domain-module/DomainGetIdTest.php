<?php

namespace hiapi\directi\tests\unit\domain_module;

use hiapi\directi\tests\unit\TestCase;

class DomainGetIdTest extends TestCase
{
    private $command = 'domains/orderid.json';

    public function testDomainGetId()
    {
        $domainName = 'silverfires.com';
        $domainId = 84372632;

        $requestQuery = "domain-name=$domainName";
        $tool = $this->mockGet($this->command, $requestQuery, $domainId);

        $result = $tool->domainGetId([
            'domain' => $domainName,
        ]);

        $this->assertSame([
            'id' => $domainId,
        ], $result);
    }
}
