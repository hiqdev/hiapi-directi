<?php
/**
 * hiAPI Directi plugin
 *
 * @link      https://github.com/hiqdev/hiapi-directi
 * @package   hiapi-directi
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\directi;

use hiapi\directi\modules\AbstractModule;
use hiapi\directi\modules\ContactModule;
use hiapi\directi\modules\DomainModule;
use hiapi\directi\modules\SecDNSModule;
use hiapi\directi\modules\HostModule;
use hiapi\directi\modules\PollModule;
use hiapi\legacy\lib\deps\err;
use hiapi\legacy\lib\deps\fix;
use yii\base\InvalidCallException;

/**
 * GoGetSSL certificate tool.
 *
 * http://manage.resellerclub.com/kb/servlet/KBServlet/cat106.html
 * Requires different HTTP request types for different operations.
 * XXX looks obsolete :(
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class DirectiTool extends \hiapi\components\AbstractTool
{
    public $data = [];

    protected $url = 'https://test.httpapi.com/api/';
    protected $login;
    protected $password;
    protected $customer_id;
    protected $default_nss = ['ns1.topdns.me', 'ns2.topdns.me'];

    protected $httpClient = null;

    public function __construct($base, $data)
    {
        parent::__construct($base, $data);
        foreach (['login','password'] as $key) {
            if (empty($data[$key])) {
                throw new \Exception("`$key` must be given for DirectiTool");
            }
            $this->{$key} = $data[$key];
        }
        foreach (['url', 'customer_id', 'default_nss'] as $key) {
            if (!empty($data[$key])) {
                $this->{$key} = $data[$key];
            }
        }
    }

    public function getCustomerId()
    {
        return $this->customer_id;
    }

    public function getDefaultNss()
    {
        return $this->default_nss;
    }

    public function __call($command, $args)
    {
        $parts = preg_split('/(?=[A-Z])/', $command);
        $entity = reset($parts);
        $module = $this->getModule($entity);

        return call_user_func_array([$module, $command], $args);
    }

    protected $modules = [
        'domain'    => DomainModule::class,
        'domains'   => DomainModule::class,
        'secdns'    => SecDNSModule::class,
        'secdnss'   => SecDNSModule::class,
        'contact'   => ContactModule::class,
        'contacts'  => ContactModule::class,
        'host'      => HostModule::class,
        'hosts'     => HostModule::class,
        'poll'      => PollModule::class,
        'polls'     => PollModule::class,
    ];

    public function getModule($name)
    {
        if (empty($this->modules[$name])) {
            throw new InvalidCallException("module `$name` not found");
        }
        $module = $this->modules[$name];
        if (!is_object($module)) {
            $this->modules[$name] = $this->createModule($module);
        }

        return $this->modules[$name];
    }

    /**
     * This method is for testing purpose only
     *
     * @param string $name
     * @param AbstractModule $module
     */
    public function setModule(string $name, AbstractModule $module): void
    {
        if (!key_exists($name, $this->modules)) {
            throw new InvalidCallException("module `$name` not found");
        }
        $this->modules[$name] = $module;
    }

    public function createModule($class)
    {
        return new $class($this);
    }

    /**
     * Performs http request with specified method
     * Direct usage is deprecated
     *
     * @param string $httpMethod
     * @param string $command
     * @param array $data
     * @param array|null $inputs
     * @param array|null $returns
     * @param array|null $auxData
     * @return array
     */
    public function request(
        string $httpMethod,
        string $command,
        array $data,
        array $inputs=null,
        array $returns=null,
        array $auxData=null
    ) {
        if (err::is($data)) {
            return $data;
        }
        $auxData['auth-userid'] = $this->login;
        $auxData['api-key']     = $this->password;

        $res = $this->getHttpClient()->performRequest(
            $httpMethod,
            $command . '.json',
            $data ,
            $inputs,
            null,
            $auxData
        );

        return $returns ? fix::values($returns,$res) : $res;
    }

    /**
     * @return HttpClient
     */
    public function getHttpClient(): HttpClient
    {
        if ($this->httpClient === null) {
            $guzzle = new \GuzzleHttp\Client(['base_uri' => $this->url]);
            $this->httpClient = new HttpClient($guzzle);
        }
        return $this->httpClient;
    }

    public function setHttpClient($httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }
}
