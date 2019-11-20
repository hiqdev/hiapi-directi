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

use hiapi\directi\exceptions\DirectiException;
use hiapi\legacy\lib\deps\err;

/**
 * Host operations.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class HostModule extends AbstractModule
{
    public function hostInfo(array $row)
    {
        $data = $this->base->hostGetInfo($row);
        return err::is($data)
            ? $this->_hostInfo($row)
            : $this->_hostInfo(array_merge($data, $row));
    }

    public function hostSet(array $row) : array
    {
        $data = $this->hostInfo($row);
        return $data['exists'] === 1 ? $this->hostUpdate($data) : $this->hostCreate($data);
    }

    public function hostCreate(array $row) : array
    {
        return $this->post_orderid('domains/add-cns',$row, [
            'host->cns'         => 'ns',
            'ips->ip'           => 'ips',
        ]);
    }

    public function hostUpdate(array $row) : array
    {
        $old = $row['cur-ips'];

        $oldIP = array_shift($old);
        $newIP = array_shift($row['ips']);

        if (!empty($old)) {
            $this->_hostDelete($row, $old);
        }

        if ($oldIP !== $newIP) {
            $this->post_orderid('domains/modify-cns-ip', $data, [
                'host->cns'         => 'ns',
                'ips->new-ip'       => 'ips',
                'old-ip->old-ip'    => 'ips',
            ]);
        }

        if (!empty($row['ips'])) {
            $this->hostCreate($row);
        }

        return $row;
    }

    public function hostDelete(array $row) : array
    {
        $data = $this->hostInfo($row);
        $old = $data['ips'];
        $res = $this->_hostDelete($row, $old);

        return $row;
    }

    public function hostsDelete(array $rows) : array
    {
        foreach ($rows as $id => $row) {
            try {
                $res[$id] = $this->hostDelete($row);
            } catch (DirectiException $e) {
                $res[$id] = err::set($row, $e->getMessage());
            }
        }

        return err::reduce($res);
    }

    protected function _hostInfo(array $row)
    {
        if (empty($row['domain'])) {
            throw new DirectiException(self::REQUIRED_PARAMETR_MISSING);
        }

        $r = $this->tool->domainInfo($row);
        $exists = isset($r['hosts'][$row['host']]);

        if ($exists === false) {
            return array_merge($row, [
                'exists' => 0,
            ]);
        }

        return array_merge($row, [
            'cur-ips' => $r['hosts'][$row['host']],
            'exists' => 1,
        ]);
    }

    protected function _hostDelete(array $row, $ips) : array
    {
        return $this->post_orderid(
            'domains/delete-cns-ip',
            $this->_prepareHostData($row, $ips),
            [
                'host->cns'         => 'ns',
                'ips->ip'           => 'ips',
            ]
        );
    }

    protected function _prepareHostData(array $row, $ips) : array
    {
        return [
            'host' => $row['host'],
            'ips' => $ips,
            'id' => $row['id'],
            'domain' => $row['domain'],
        ];
    }
}
