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
        $r = $this->tool->domainInfo($row);
        return isset($r['cns'][$row['host']]) ? $this->hostUpdate(array_merge($row, $r)) : $this->hostCreate($row);
    }

    public function hostCreate($row)
    {
        return $this->post_orderid('domains/add-cns',$row,[
            'host->cns'         => 'ns',
            'ips->ip'           => 'ips',
        ]);
    }

    public function hostUpdate($row)
    {
        $old = $row['hosts'][$row['host']];
        for ($i = 0; $i < count($old) - 1; $i++) {
            $res[] = $this->post_orderid('domains/delete-cns-ip', [
                'host' => $row['host'],
                'ips' => [ $old[$i] ],
                'id' => $row['id'],
                'domain' => $row['domain'],
            ], [
                'host->cns'         => 'ns',
                'ips->ip'           => 'ips',
            ]);
        }

        $change = $old[count($old) - 1];
        for ($i = 0; $i < count($row['ips']); $i++) {
            $data = [
                'id' => $row['id'],
                'domain' => $row['domain'],
                'ips' => $row['ips'][$i],
                'host' => $row['host'],
            ];

            if ($data['ips'] === $change) {
                continue;
            }

            if ($i > 0) {
                $res[] = $this->hostCreate($data);
                continue;
            }


            $data['old-ip'] = [ $change ];

            $res[] = $this->post_orderid('domains/modify-cns-ip', $data, [
                'host->cns'         => 'ns',
                'ips->new-ip'       => 'ips',
                'old-ip->old-ip'    => 'ips',
            ]);
        }

        return $row;
    }
}
