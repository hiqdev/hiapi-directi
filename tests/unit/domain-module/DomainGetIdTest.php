<?php

namespace hiapi\directi\tests\unit\domain_module;

use GuzzleHttp\Psr7\Response;
use hiapi\directi\tests\unit\TestCase;

class DomainGetIdTest extends TestCase
{
    private $command = 'domains/orderid.json';

    public function testDomainGetId()
    {
        $domainName = 'silverfires.com';
        $domainId = 84372632;

        $client = $this->mockGuzzleClient();

        $domainGetIdData = [
            'domain'    => $domainName,
        ];
        $requestQuery = sprintf('%s?domain-name=%s&auth-userid=%s&api-key=%s',
            $this->command,
            $domainName,
            $this->authUserId,
            $this->apiKey
        );
        $responseBody = $domainId;
        $client->method('request')
            ->with('GET', $requestQuery)
            ->willReturn(new Response(200, [], $responseBody));

        $tool = $this->createTool($this->mockBase(), $client);
        $result = $tool->domainGetId($domainGetIdData);

        $this->assertSame([
            'id' => $domainId,
        ], $result);
    }

}
