<?php

namespace hiapi\directi\tests\unit;

use hiapi\directi\DirectiTool;
use hiapi\directi\HttpClient;
use GuzzleHttp\Client;

class DirectiToolTest extends \PHPUnit\Framework\TestCase
{
    protected $testUri = 'http://test.test/test';
    protected $authUserId = '753669';
    protected $apiKey = 'UiQJ1uQHVlMasbrPTZMQ2pFcKHeHfEPY';

    /// TODO later because needs API base mocking
    public function no_testDomainRegister()
    {
        $result = $tool->domainRegister([

        ]);
        $this->assertSame([

        ], $result);
    }

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
