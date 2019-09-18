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

/**
 * Host operations.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class HostModule extends AbstractModule
{
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

    public function hostSet($row)
    {
        return $this->hostCreate($row);
    }

    public function hostCreate($row)
    {
        return $this->post_orderid('domains/add-cns',$row,[
            'host->cns'         => 'ns',
            'ips->ip'           => 'ips',
        ]);
    }
}
