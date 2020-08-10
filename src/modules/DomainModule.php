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
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class DomainModule extends AbstractModule
{
    /** @const state */
    const STATE_DELETING = 'deleting';

    /** const error messages */
    const SAME_CONTACTS_ERROR = 'The Contacts selected are the same as the existing contacts';
    const ACTION_PENDING_ERROR = 'There is already a pending action on this domain';
    const ACTION_IN_ORDER_ERROR = 'You have already added this action on this order.';
    const WHOIS_PROTECT_SERVICE_ERROR = 'Privacy Protection Service not available.';
    const WHOIS_PROTECT_NOT_PURCHASED = 'Privacy Protection not Purchased';
    const REGISTRAR_ERROR = 'You are not allowed to perform this action';
    const SIGN_FOR_PREMIUM_DOMAIN = 'Not Signed up for Premium Domains';

    /**
     * @var array
     */
    protected $statuses = [
        'Active' => 'ok',
        'transferlock' => 'clientTransferProhibited',
        'renewhold' => 'autoRenewPeriod',
        'resellersuspend' => 'clientHold',
        'Pending Delete Restorable' => 'redemptionPeriod,pendingDelete',
        // TODO Add all statuses
    ];

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
            $res[$domain]['avail'] = $check['status'] === 'available' ? 1 : 0;
            if ($check['status'] !== 'available') {
                continue ;
            }
            try {
                $checkPremium = $this->get('domains/premium-check', [
                    'domain-name' => $domain,
                ]);
            } catch (\Throwable $e) {
                if ($e->getMessage() === self::SIGN_FOR_PREMIUM_DOMAIN) {
                    continue ;
                }
            }
            $res[$domain]['premium'] = $checkPremium['premium'];
            if ($checkPremium['premium']) {
                $res[$domain]['fee'] = $checkPremium;
            }
        }

        return $res;
    }

    /**
     * @param array $row
     * @return array
     */
    public function _domainInfo(array $row): array
    {
        try {
            $data = $this->get('domains/details-by-name',[
                'domain-name'   => $row['domain'],
                'options'       => ['All'],
            ]);
        } catch (DirectiException $e) {
            if (strpos($e->getMessage(), "Website doesn't exist for {$row['domain']}") !== false) {
                throw new DirectiException(self::OBJECT_DOES_NOT_EXIST);
            }
        }

        if (err::is($data)) {
            return $data;
        }

        $res = fix::values([
            'orderid->id'                       => 'id',
            'domainname->domain'                => 'domain',
            'domsecret->password'               => 'password',
            'registrantcontactid->registrant'   => 'id',
            'admincontactid->admin'             => 'id',
            'billingcontactid->billing'         => 'id',
            'techcontactid->tech'               => 'id',
            'customerid->customer'              => 'id',

        ], $data);
        $res['created_date'] = format::datetime($data['creationtime'],'iso');
        $res['expiration_date'] = format::datetime($data['endtime'],'iso');
        $res['statuses_arr'] = $this->_fixStatuses($data);
        $res['statuses'] = arr::cjoin($res['statuses_arr']);
        $res['cns'] = $data['cns'];
        if ($res['cns']) {
            $res['hosts'] = array_keys($res['cns']);
            // foreach ($res['cns'] as $host => $ips) {
            //     $res['hosts'][$host] = [
            //         'host' => $host,
            //         'ips' => $ips,
            //     ];
            // }
        }
        foreach (['registrant', 'admin', 'tech', 'billing'] as $cType) {
            $res["{$cType}c"] = $this->tool->contactParse(array_merge($data["{$cType}contact"], [
                'entityid' => $data["{$cType}contactid"],
            ]));
        }

        if ($data['privacyprotectendtime']) {
            $res['wp_purchased'] = (int) $data['privacyprotectendtime'] >= time();
        }

        $res['wp_enabled'] = $data['isprivacyprotected'] === 'true';
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
        $contacts = $this->_domainGetContactsInfo($row);
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
            'invoice-option'    => 'NoInvoice',
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
        try {
            $res = $this->domainSetWhoisProtect($row, $row['whois_protected']);
        } catch (DirectiException $e) {
            if (!in_array($e->getMessage(), [self::ACTION_IN_ORDER_ERROR, self::WHOIS_PROTECT_SERVICE_ERROR], true)) {
                throw new DirectiException($e->getMessage());
            }
        }

        return $this->_domainSetContacts($row, true);
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

    public function domainDelete(array $row)
    {
        $domain = $this->domainGetId($row);
        if (err::is($domain)) {
            return err::set($domain, 'Failed to get domain: ' . err::get($domain));
        }
        $row['order-id'] = $domain['id'];

        $res = $this->post('domains/delete', $row, [
            'order-id'       => 'id',
        ]);

        return $row;
    }

    public function domainRestore(array $row)
    {
        $domain = $this->domainGetId($row);
        if (err::is($domain)) {
            return err::set($domain, 'Failed to get domain: ' . err::get($domain));
        }

        $row['order-id'] = $domain['id'];
        $res = $this->post('domains/restore', $row, [
            'order-id'       => 'id',
        ], null, [
            'invoice-option'    => 'NoInvoice',
        ]);

        return $row;
    }

    public function domainEnableWhoisProtect($row)
    {
        return $this->domainSetWhoisProtect($row, true);
    }

    public function domainDisableWhoisProtect($row)
    {
        return $this->domainSetWhoisProtect($row, false);
    }

    public function domainsEnableWhoisProtect($row)
    {
        return $this->domainsSetWhoisProtect($row, true);
    }

    public function domainsDisableWhoisProtect($row)
    {
        return $this->domainsSetWhoisProtect($row, false);
    }

    public function domainSetWhoisProtect($row, $enable = null)
    {
        $row['enable'] = $row['enable'] ?? $row['whois_protected'] ?? false;
        $enable = $enable ?? $row['enable'] ?? false;
        $info = $this->tool->domainInfo($row);

        if ($this->_isWPNeadPurchase($info, $enable)) {
            $this->domainPurchaseWhoisProtect($row);
        }

        if ($this->_isWPNeadSet($info, $enable)) {
            $this->_inner_domainSetWhoisProtect($row, $enable);
        }

        return $row;
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

    public function domainsMoveDirecti($rows)
    {
        foreach ($rows as $id => $row)
        {
            $res[$id] = $this->domainMoveDirecti($row);
        }

        return $res;
    }

    public function domainVerificationDirecti($row)
    {
        var_dump($this->post_orderid('domains/raa/skip-verification', $row, [

        ]));

    }

    public function domainsSetVerificationDirecti($rows)
    {
        foreach ($rows as $id => $row) {
            try {
                $res = $this->domainSetVerificationDirecti($row);
            } catch (\DirectiException $e) {
                $res[$id] = err::set($row, $e->getMessage());
            }
        }

        return $res;
    }

    public function domainMoveDirecti($row)
    {
        $info = $this->tool->domainInfo([
            'domain' => $row['name'],
        ]);
        $row['existing-customer-id'] = $info['customer'] ?? $this->tool->getCustomerId();
        $row['default-contact'] = 'oldcontact';
        return $this->post('products/move', $row, [
            'domain->domain-name' => 'domain',
            'new_customer_id->new-customer-id' => 'id',
            'existing-customer-id' => 'id',
            'default-contact' => 'ref',
        ]);
    }

    protected function _domainSetContacts($row, $skipIRTP = false)
    {
        try {
            $res = $this->post_orderid('domains/modify-contact', array_merge($row, $skipIRTP ? [
                'attr-name1' => 'skipIRTP',
                'attr-value1' => 'true',
            ] : []), [
                'registrant_remoteid->reg-contact-id'   => 'id',
                'admin_remoteid->admin-contact-id'      => 'id',
                'tech_remoteid->tech-contact-id'        => 'id',
                'billing_remoteid->billing-contact-id'  => 'id',
                'attr-name1'                            => 'label',
                'attr-value1'                           => 'label',
            ],[
                'entityid->id'          => 'id',
                'description->domain'   => 'domain',
            ]);
        } catch (DirectiException $e) {
            if ($e->getMessage() === self::REGISTRAR_ERROR) {
                return $this->_domainSetContacts($row);
            }
            if (in_array($e->getMessage(), [self::SAME_CONTACTS_ERROR, self::ACTION_PENDING_ERROR], true)) {
                return arr::mget($row,'id,domain');
            }

            throw new DirectiException($e->getMessage());
        }

        return $res;
    }

    protected function _fixStatuses($data)
    {
        $statuses = [];
        if (!empty($this->statuses[$data['currentstatus']])) {
            $statuses[$this->statuses[$data['currentstatus']]] = $this->statuses[$data['currentstatus']];
        }
        foreach (['orderstatus', 'domainstatus'] as $type) {
            foreach ($data[$type] as $status) {
                if (empty($this->statuses[$status])) {
                    continue;
                }

                $statuses[$this->statuses[$status]] = $this->statuses[$status];
            }
        }

        return $statuses;
    }

    protected function _isWPNeadPurchase(array $data, bool $enable) : bool
    {
        $neadEnable = !$data['wp_purchased'] && $enable;
        $neadDisable = $data['wp_enabled'] && !($data['wp_purchased'] || $enable);

        return $neadEnable || $neadDisable;
    }

    protected function _isWPNeadSet(array $data, bool $enable) : bool
    {
        return $data['wp_enabled'] != $enable;
    }

    private function _domainGetContactsInfo(array $row): array
    {
        $rows = $this->base->_domainsGetContactsInfo([$row['id'] => $row]);

        return err::is($rows) ? $rows : reset($rows);
    }
}
