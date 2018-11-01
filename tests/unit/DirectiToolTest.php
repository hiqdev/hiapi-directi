<?php

namespace hiapi\directi\tests\unit;

use hiapi\directi\DirectiTool;
use hiapi\directi\HttpClient;
use GuzzleHttp\Client;
use hiapi\directi\modules\DomainModule;

class DirectiToolTest extends \PHPUnit\Framework\TestCase
{
    protected $testUri      = 'http://test.test/test';
    protected $authUserId   = '753669';
    protected $apiKey       = 'UiQJ1uQHVlMasbrPTZMQ2pFcKHeHfEPY';
    protected $customerId   = '19371930';


    protected function mockGuzzleClient()
    {
        return $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(['request'])
            ->getMock();
    }

    protected function mockBase()
    {
        return $this->getMockBuilder(\mrdpBase::class)
            ->disableOriginalConstructor()
            ->setMethods(['domainGetNSs', 'domainGetWPContactsInfo'])
            ->getMock();
    }

    protected function mockDomainModule(array $methods)
    {
        return $this->getMockBuilder(DomainModule::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    protected function createTool($guzzleClient)
    {
        $base = $this->mockBase();

        /** @noinspection PhpUnhandledExceptionInspection */
        $tool = new DirectiTool($base, [
            'url'           => $this->testUri,
            'login'         => '753669',
            'password'      => 'UiQJ1uQHVlMasbrPTZMQ2pFcKHeHfEPY',
            'customer_id'   => '19371930',
        ]);
        $tool->setHttpClient(new HttpClient($guzzleClient));

        return $tool;
    }
}
