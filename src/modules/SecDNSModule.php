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

use hiapi\directi\DirectiTool;
use hiapi\legacy\lib\deps\arr;
use hiapi\legacy\lib\deps\err;
use hiapi\legacy\lib\deps\fix;
use hiapi\legacy\lib\deps\format;
use hiapi\directi\exceptions\DirectiException;

/**
 * Domain operations.
 *
 * @author Yurii Myronchuk <bladeroot@gmail.com>
 */
class SecDNSModule extends AbstractModule
{
    protected $apiDirectiKeys = [
        'key_tag' => 'keytag',
        'digest_alg' => 'algorithm',
        'digest_type' => 'digesttype',
        'digest' => 'digest',
    ];
    /**
     * Stub function for compability
     *
     * @param array $row
     * @reurn array
     */
    public function secdnsChange(array $row) : array
    {
        return $row;
    }

   /**
     * Create SecDNS record
     *
     * @param array $row
     * @return array
     */
    public function secdnsCreate(array $row) : array
    {
        return $this->executeAction('domains/add-dnssec', $row);
    }

   /**
     * Remove SecDNS record
     *
     * @param array $row
     * @return array
     */
    public function secdnsDelete(array $row) : array
    {
        return $this->executeAction('domains/del-dnssec', $row);
    }

    protected function executeAction(string $action, array $row) : array
    {
        $domain = $this->domainGetId($row);
        if (err::is($domain)) {
            return err::set($domain, 'Failed to get domain: ' . err::get($domain));
        }

        $row['order-id'] = $domain['id'];

        $res = $this->post($action, $row, [
            'order-id'       => 'id',
        ], null, $this->prepareDSData($row));

        return $row;
    }

    protected function prepareDSData(array $row) : array
    {
        $i = 1;
        $data = [];
        foreach ($this->apiDirectiKeys as $api => $key) {
            $data["attr-name{$i}"] = $key;
            $data["attr-value{$i}"] = $row[$api];
            $i++;
        }

        return $data;
    }
}
