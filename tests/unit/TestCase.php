<?php

namespace hiapi\directi\tests\unit;

use hiapi\directi\DirectiTool;
use hiapi\directi\HttpClient;
use GuzzleHttp\Client;
use PHPUnit\Framework\MockObject\MockObject;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected $testUri      = 'http://test.test/test';
    protected $authUserId   = '753669';
    protected $apiKey       = 'UiQJ1uQHVlMasbrPTZMQ2pFcKHeHfEPY';
    protected $customerId   = '19371930';


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
            'url'           => $this->testUri,
            'login'         => $this->authUserId,
            'password'      => $this->apiKey,
            'customer_id'   => $this->customerId,
        ]);
        $tool->setHttpClient(new HttpClient($guzzleClient));

        return $tool;
    }
}
