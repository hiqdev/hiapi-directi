<?php

namespace hiapi\directi\tests\unit;

use hiapi\directi\DirectiTool;
use hiapi\directi\HttpClient;
use GuzzleHttp\Client;

class DirectiToolTest extends \PHPUnit\Framework\TestCase
{
    protected $testUri = 'http://test.test/test';

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

    protected function createTool($guzzleClient)
    {
        $base = new class {};

        /** @noinspection PhpUnhandledExceptionInspection */
        $tool = new DirectiTool($base, [
            'url'           => $this->testUri,
            'login'         => '753669',
            'password'      => 'UiQJ1uQHVlMasbrPTZMQ2pFcKHeHfEPY',
            'customer_id'   => '98765432',
        ]);
        $tool->setHttpClient(new HttpClient($guzzleClient));

        return $tool;
    }
}
