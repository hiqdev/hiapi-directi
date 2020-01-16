<?php
/**
 * hiAPI Directi plugin
 *
 * @link      https://github.com/hiqdev/hiapi-directi
 * @package   hiapi-directi
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2017, HiQDev (http://hiqdev.com/)
 */

namespace hiapi\directi\modules;

use hiapi\legacy\lib\deps\err;
use hiapi\directi\DirectiTool;

/**
 * General module functions.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class AbstractModule
{
    /** @const state */
    const STATE_OK = 'ok';

    const OBJECT_DOES_NOT_EXIST = 'Object does not exist';
    const REQUIRED_PARAMETR_MISSING = 'Required parametr missing';

    public $tool;
    public $base;

    public function __construct(DirectiTool $tool)
    {
        $this->tool = $tool;
        $this->base = $tool->getBase();
    }

    /**
     * Performs http GET request
     *
     * @param string $command
     * @param array $data
     * @param array|null $inputs
     * @param array|null $returns
     * @param array|null $auxData
     * @return array
     */
    public function get(
        string $command,
        array $data,
        array $inputs=null,
        array $returns=null,
        array $auxData=null
    ) {
        return $this->tool->request('GET', $command, $data, $inputs, $returns, $auxData);
    }

    /**
     * Performs http POST request
     *
     * @param string $command
     * @param array $data
     * @param array|null $inputs
     * @param array|null $returns
     * @param array|null $auxData
     * @return array
     */
    public function post(
        string $command,
        array $data,
        array $inputs=null,
        array $returns=null,
        array $auxData=null
    ) {
        return $this->tool->request('POST', $command, $data, $inputs, $returns, $auxData);
    }

    public function post_orderid($name,$data,$inputs=null,$returns=null)
    {
        $res = $this->domainGetId($data);
        if (err::is($res)) {
            return $res;
        }

        return $this->tool->request(
            'POST',
            $name,
            $data,
            $inputs,
            $returns,
            ['order-id'=>$res['id']]);
    }

    /// domain
    /**
     * @param array $row
     * @return array
     */
    public function domainGetId(array $row): array
    {
        $id = $this->get('domains/orderid', $row, [
            'domain->domain-name'       => 'domain,*',
        ]);

        return err::is($id) ? $id : compact('id');
    }


}
