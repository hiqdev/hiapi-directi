<?php

namespace hiapi\directi\tests\unit;

use hiapi\directi\DirectiTool;
use hiapi\directi\HttpClient;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected $testUrl      = 'http://test.test/test';
    protected $authUserId   = '753669';
    protected $apiKey       = 'UiQJ1uQHVlMasbrPTZMQ2pFcKHeHfEPY';
    protected $customerId   = '19371930';

    /**
     * @var MockObject Client mock
     */
    protected $client;

    /**
     * @var DirectiTool
     */
    protected $tool;

    protected function mockGet(string $command, string $requestQuery, string $responseBody, array $baseMethods=null)
    {
        $client = $this->getGuzzleClient();
        $client->method('request')
            ->with('GET', $command . '?' . $this->prepareQuery($requestQuery))
            ->willReturn(new Response(200, [], $responseBody));

        return $this->createTool($this->mockBase($baseMethods), $client);
    }

    protected function mockPost(string $command, string $requestQuery, string $responseBody, array $baseMethods=null)
    {
        $client = $this->getGuzzleClient();
        $client->method('request')
            ->with('POST', $command, [
                'body'    => $this->prepareQuery($requestQuery),
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ])
            ->willReturn(new Response(200, [], $responseBody));

        return $this->createTool($this->mockBase($baseMethods), $client);
    }

    protected function prepareQuery(string $query): string{
        return "$query&auth-userid={$this->authUserId}&api-key={$this->apiKey}";
    }

    protected function getGuzzleClient(): MockObject
    {
        if ($this->client === null) {
            $this->client = $this->mockGuzzleClient();
        }

        return $this->client;
    }

    protected function mockGuzzleClient(): MockObject
    {
        return $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['request'])
            ->getMock();
    }

    protected function mockBase(array $methods=null): MockObject
    {
        return $this->getMockBuilder(\mrdpBase::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    protected function mockModule(string $moduleClassName, array $methods): MockObject
    {
        return $this->getMockBuilder($moduleClassName)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    protected function createTool(\mrdpBase $base, Client $guzzleClient): DirectiTool
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $tool = new DirectiTool($base, [
            'url'           => $this->testUrl,
            'login'         => $this->authUserId,
            'password'      => $this->apiKey,
            'customer_id'   => $this->customerId,
        ]);
        $tool->setHttpClient(new HttpClient($guzzleClient));

        return $tool;
    }
}
