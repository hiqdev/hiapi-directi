<?php

namespace hiapi\directi\tests\unit\domain_module;

use GuzzleHttp\Psr7\Response;
use hiapi\directi\tests\unit\TestCase;


class DomainCheckTest extends TestCase
{
    private $command = 'domains/available.json';

    public function testDomainCheck()
    {
        $domainCheckData = [
            'domains' => [
                    0 => 'silverfires.com',
                ],
            'uncached' => true,
        ];

        $client = $this->mockGuzzleClient();

        $requestQuery = sprintf('%s?domain-name=silverfires&tlds=com&auth-userid=%s&api-key=%s',
            $this->command,
            $this->authUserId,
            $this->apiKey);
        $responseBody = '{"silverfires.com":{"classkey":"domcno","status":"available"}}';

        $client->method('request')
            ->with('GET', $requestQuery)
            ->willReturn(new Response(200, [], $responseBody));

        $tool = $this->createTool($this->mockBase(), $client);
        $result = $tool->domainsCheck($domainCheckData);

        $this->assertSame([
            'silverfires.com' => [
                    'classkey'  => 'domcno',
                    'status'    => 'available',
                ],
        ], $result);
    }
}
