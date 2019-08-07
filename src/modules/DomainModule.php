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

use hiapi\legacy\lib\deps\arr;
use hiapi\legacy\lib\deps\err;
use hiapi\legacy\lib\deps\fix;
use hiapi\legacy\lib\deps\format;
use hiapi\directi\exceptions\DirectiException;

/**
 * Domain operations.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class DomainModule extends AbstractModule
{
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

    /**
     * @param array $row
     * @return array
     */
    private function prepareDomainsData(array $row): array
    {
        $domainNames = [];
        $tlds = [];

        foreach ($row['domains'] as $domain) {
            list($name, $tld) = explode('.', $domain, 2);
            if (!in_array($name, $domainNames)) {
                $domainNames[] = $name;
            }
            if (!in_array($tld, $tlds)) {
                $tlds[] = $tld;
            }
        }
        $row['domain-name'] = $domainNames;
        $row['tlds'] = $tlds;

        return $row;
    }

    /**
     * !!! НЕ ПРИХОДИТ БОЛЬШЕ ОДНОГО ДОМЕНА ДЛЯ ПРОВЕРКИ !!!
     *
     * @param array $row
     * @return array
     */
    public function domainsCheck(array $row): array
    {
        $row = $this->prepareDomainsData($row);
        $res = $this->get('domains/available', [
            'domain-name' => $row['domain-name'],
            'tlds'        => $row['tlds']
        ]);
        foreach ($res as $domain => $check) {
            $res[$domain] = $check['status'] === 'available' ? 1 : 0;
        }

        return $res;
    }

    /**
     * @param array $row
     * @return array
     */
    public function _domainInfo(array $row): array
    {
        $data = $this->get('domains/details-by-name',[
            'domain-name'   => $row['domain'],
            'options'       => ['All'],
        ]);
        $res = fix::values([
            'orderid->id'                       => 'id',
            'domainname->domain'                => 'domain',
            'domsecret->password'               => 'password',
            'registrantcontactid->registrant'   => 'id',
            'admincontactid->admin'             => 'id',
            'billingcontactid->billing'         => 'id',
            'techcontactid->tech'               => 'id',

        ], $data);
        $res['created_date'] = format::datetime($data['creationtime'],'iso');
        $res['expiration_date'] = format::datetime($data['endtime'],'iso');
        if (err::is($data)) {
            return $data;
        }
        for ($i=1; $i <= 13; ++$i) {
            if ($data["ns$i"]) {
                $nss[] = $data["ns$i"];
            }
        }
        if ($nss) {
            $res['nameservers'] = arr::cjoin($nss);
        }
        return $res;
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainInfo(array $row): array
    {
        $res = $this->_domainInfo($row);
        if (!err::is($res)) {
            return $res;
        }
        if (!$row['password'] && $row['id']) {
            $row = array_merge($row, $this->base->domainGetPassword($row));
        }

        return $this->base->getTool(3027237)->domainInfo($row);
    }

    public function domainCheckTransfer($row)
    {
        return $row;
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainPrepareContacts(array $row): array
    {
        $contacts = $this->base->domainGetContactsInfo($row);
        if (err::is($contacts)) {
            return $contacts;
        }
        $rids = [];
        foreach ($this->base->getContactTypes() as $t) {
            $cid = $contacts[$t]['id'];
            $remoteid = $rids[$cid];
            if (!$remoteid) {
                $r = $this->tool->contactSet($contacts[$t]);
                if (err::is($r)) {
                    return $r;
                }
                $remoteid = $r['id'];
                $rids[$cid] = $remoteid;
            }
            $row[$t . '_remoteid'] = $remoteid;
        }

        return $row;
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainRegister(array $row): array
    {
        if (!$row['nss']) {
            $row['nss'] = arr::get($this->base->domainGetNSs($row),'nss');
        }
        if (!$row['nss']) {
            $row['nss'] = $this->tool->getDefaultNss();
        }
        $row = $this->domainPrepareContacts($row);
        if (err::is($row)) {
            return $row;
        }

        $res = $this->post('domains/register', $row , [
            'domain->domain-name'                   => 'domain',
            'period->years'                         => 'period',
            'nss->ns'                               => 'nss',
            'registrant_remoteid->reg-contact-id'   => 'id',
            'admin_remoteid->admin-contact-id'      => 'id',
            'tech_remoteid->tech-contact-id'        => 'id',
            'billing_remoteid->billing-contact-id'  => 'id',

        ],[
            'entityid->id'          => 'id',
            'description->domain'   => 'domain',
        ],[
            'customer-id'       => $this->tool->getCustomerId(),
            'invoice-option'    => 'KeepInvoice',
            'protect-privacy'   => 'false',
        ]);

        return $res;
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainTransfer(array $row): array
    {
        $row = $this->domainPrepareContacts($row);
        $res = $this->post('domains/transfer',$row,[
            'domain->domain-name'                   => 'domain',
            'password->auth-code'                   => 'password',
            'nss->ns'                               => 'nss',
            'registrant_remoteid->reg-contact-id'   => 'id',
            'admin_remoteid->admin-contact-id'      => 'id',
            'tech_remoteid->tech-contact-id'        => 'id',
            'billing_remoteid->billing-contact-id'  => 'id',
        ],[
            'entityid->id'          => 'id',
            'description->domain'   => 'domain',
        ],[
            'customer-id'       => $this->tool->getCustomerId(),
            'invoice-option'    => 'NoInvoice',
            'protect-privacy'   => 'false',
        ]);

        return $res;
    }

    /**
     * @param $row
     * @return array
     */
    public function domainRenew($row): array
    {
        $domain = $this->domainGetId($row);
        if (err::is($domain)) {
            return err::set($domain, 'Failed to get domain: ' . err::get($domain));
        }

        $row['order-id'] = $domain['id'];
        $row['exp-date'] = strtotime($row['expires_time']);

        $res = $this->post('domains/renew', $row, [
            'order-id'       => 'id',
            'period->years'  => 'period',
            'exp-date'       => 'id',

        ], null, [
            'invoice-option'    => 'NoInvoice',
        ]);

        return $res;
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainSetNSs(array $row): array
    {
        return $this->post_orderid('domains/modify-ns', $row, [
            'nss->ns'   => 'nss',
        ]);
    }

    /**
     * @param array $row
     * @return array
     */
    public function domainSetContacts(array $row): array
    {
        $res = $this->domainSetWhoisProtect($row, $row['whois_protected']);
        $res = $this->post_orderid('domains/modify-contact', $row, [
            'registrant_remoteid->reg-contact-id'   => 'id',
            'admin_remoteid->admin-contact-id'      => 'id',
            'tech_remoteid->tech-contact-id'        => 'id',
            'billing_remoteid->billing-contact-id'  => 'id',
        ],[
            'entityid->id'          => 'id',
            'description->domain'   => 'domain',
        ]);
        if (err::is($res) && $res['message'] === 'The Contacts selected are the same as the existing contacts') {
            return arr::mget($row,'id,domain');
        }

        return $res;
    }

    public function domainEnableLock($row)
    {
        return $this->post_orderid('domains/enable-theft-protection',$row);
    }

    public function domainsEnableLock($rows)
    {
        foreach ($rows as $k=>$row) {
            $res[$k] = $this->domainEnableLock($row);
        }

        return err::reduce($res);
    }

    public function domainDisableLock($row)
    {
        return $this->post_orderid('domains/disable-theft-protection',$row);
    }

    public function domainsDisableLock($rows)
    {
        foreach ($rows as $k=>$row) {
            $res[$k] = $this->domainDisableLock($row);
        }

        return err::reduce($res);
    }

    public function domainSetPassword($row)
    {
        return $this->post_orderid('domains/modify-auth-code',$row,[
            'password->auth-code'   => 'password',
        ]);
    }

    public function domainsLoadInfo($rows)
    {
        return true;
    }

    public function domainsGetInfo($rows)
    {
        foreach ($rows as $id=>$row) {
            $res[$id] = $this->domainInfo($row);
        }

        return err::reduce($res);
    }

    public function domainSaveContacts($row)
    {
        return $this->base->_simple_domainSaveContacts($row, false);
    }

    public function domainEnableWhoisProtect($row)
    {
        $this->domainSetWhoisProtect($row, true);
    }

    public function domainDisableWhoisProtect($row)
    {
        $this->domainSetWhoisProtect($row, false);
    }

    public function domainsEnableWhoisProtect($row)
    {
        $this->domainsSetWhoisProtect($row, true);
    }

    public function domainsDisableWhoisProtect($row)
    {
        $this->domainsSetWhoisProtect($row, false);
    }

    public function domainSetWhoisProtect($row, $enable = null)
    {
        try {
            $res = $this->_inner_domainSetWhoisProtect($row, $enable);
        } catch (DirectiException $e) {
            if ($e->getMessage() === 'Privacy Protection not Purchased') {
                $this->domainPurchaseWhoisProtect($row);
                $res = $this->_inner_domainSetWhoisProtect($row, $enable);
            }
        }

        return $res;
    }

    public function domainPurchaseWhoisProtect($row)
    {
        $row['invoice-option'] = 'NoInvoice';

        return $this->post_orderid('domains/purchase-privacy', $row);
    }

    private function _inner_domainSetWhoisProtect($row, $enable)
    {
        $enable = $enable ?? $row['enable'] ?? false;
        $row['protect-privacy'] = $enable ? 'true' : 'false';
        $row['reason'] = $row['reason'] ?? 'on a client request';

        return $this->post_orderid('domains/modify-privacy-protection', $row);
    }

    public function domainsSetWhoisProtect($rows, $enable = null)
    {
        $res = [];
        foreach ($rows as $k=>$row) {
            $res[$k] = $this->domainSetWhoisProtect($row, $enable);
        }

        return err::reduce($res);
    }
}
