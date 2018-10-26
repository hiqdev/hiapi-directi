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

use arr;
use err;
use fix;
use check;
use retrieve;

/**
 * Domain operations.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class DomainModule extends AbstractModule
{
    /// domain
    public function domainGetId($row)
    {
        $id = $this->get('domains/orderid',$row,[
            'domain->domain-name'       => 'domain,*',
        ]);

        return err::is($id) ? $id : compact('id');
    }

    public function domainCheck($row)
    {
    }

    public function domainsCheck($jrow)
    {
    }

    public function _domainInfo($row)
    {
        $data = $this->get('domains/details-by-name',[
            'domain-name'           => $row['domain'],
            'options'           => ['OrderDetails', 'NsDetails', 'ContactIds'],
        ]);
        $res = fix::values([
            'orderid->id'           => 'id',
            'domainname->domain'        => 'domain',
            'domsecret->password'       => 'password',
        ],$data);
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
        //return array_merge($data,$res);
    }

    public function domainInfo($row)
    {
        $res = $this->_domainInfo($row);
        if (!err::is($res)) {
            return $res;
        }
        if (!$row['password'] && $row['id']) {
            $row = array_merge($row,$this->base->domainGetPassword($row));
        }

        return $this->base->getTool(3027237)->domainInfo($row);
    }

    public function domainCheckTransfer($row)
    { // check through evo@ahnames
        return $this->base->getTool(3027237)->domainCheckTransfer($row);
    }

    public function domainPrepareContacts($row)
    {
        $contacts = $this->base->domainGetWPContactsInfo($row);
        if (err::is($contacts)) {
            return $contacts;
        }
        $rids = [];
        foreach ($this->base->getContactTypes() as $t) {
            $cid = $contacts[$t]['id'];
            $remoteid = $rids[$cid];
            if (!$remoteid) {
                $r = $this->contactSet($contacts[$t]);
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

    public function domainRegister($row)
    {
        if (!$row['nss']) {
            $row['nss'] = arr::get($this->base->domainGetNSs($row),'nss');
        }
        if (!$row['nss']) {
            $row['nss'] = $this->tool->getDefaultNss();
        }
        $row = $this->domainPrepareContacts($row);
    //d($row);
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

    public function domainTransfer($row)
    {
        $row = $this->domainPrepareContacts($row);
        $res = $this->post('domains/transfer',$row,[
            'domain->domain-name'           => 'domain',
            'password->auth-code'           => 'password',
            'nss->ns'               => 'nss',
            'registrant_remoteid->reg-contact-id'   => 'id',
            'admin_remoteid->admin-contact-id'  => 'id',
            'tech_remoteid->tech-contact-id'    => 'id',
            'billing_remoteid->billing-contact-id'  => 'id',
        ],[
            'entityid->id'              => 'id',
            'description->domain'           => 'domain',
        ],[
            'customer-id'               => $this->tool->getCustomerId(),
            'invoice-option'            => 'NoInvoice',
            'protect-privacy'           => 'false',
        ]);

        return $res;
    }

    public function domainRenew($row)
    {
    }

    public function domainSetNSs($row)
    {
        return $this->post_orderid('domains/modify-ns',$row,[
            'nss->ns'           => 'nss',
        ]);
    }

    public function domainSetContacts($row)
    {
        $res = $this->post_orderid('domains/modify-contact',$row,[
            'registrant_remoteid->reg-contact-id'   => 'id',
            'admin_remoteid->admin-contact-id'  => 'id',
            'tech_remoteid->tech-contact-id'    => 'id',
            'billing_remoteid->billing-contact-id'  => 'id',
        ],[
            'entityid->id'              => 'id',
            'description->domain'           => 'domain',
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
            'password->auth-code'       => 'password',
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
        return $this->base->_simple_domainSaveContacts($row);
    }
}
