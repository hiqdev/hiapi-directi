<?php

namespace hiapi\directi;

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

    public function getPlain ($url,$data=null,$method='',$options = []) {
        $url .= '?';
        foreach ($data as $k => $v) $url .= static::_prepareVar($k,$v);
        return parent::getPlain($url,null,$method);
    }

    static public function _prepareVar ($k,$v) {
        if (is_array($v)) {
            foreach ($v as $w) $res .= static::_prepareVar($k,$w);
            return $res;
        } else return $k.'='.urlencode($v).'&';
    }

    public function call ($name,$data,$method,$inputs=null,$returns=null,$add_data=array()) {
        if (err::is($data)) return $data;
        $add_data['auth-userid']    = $this->login;
        $add_data['api-key']        = $this->password;
        $res = $this->checkedRequest($name.'.json',$data,$method,$inputs,null,$add_data);
        if ($res['status']=='ERROR') return error('directi error',$res);
        return $returns ? fix::values($returns,$res) : $res;
    }

    public function call_orderid ($name,$data,$method,$inputs=null,$returns=null) {
        $res = $this->domainGetId($data);
        if (err::is($res)) return $res;
        return $this->call($name,$data,$method,$inputs,$returns,array('order-id'=>$res['id']));
    }

/// domain maintenance
    public function domainSaveContacts ($row) {
        return $this->base->_simple_domainSaveContacts($row);
    }

/// domain
    public function domainGetId ($row) {
        $id = $this->call('domains/orderid',$row,'GET',array(
            'domain->domain-name'       => 'domain,*'
        ));
        return err::is($id) ? $id : compact('id');
    }

    public function domainCheck ($row) {
    }

    public function domainsCheck ($jrow) {
    }

    public function _domainInfo ($row) {
        $data = $this->call('domains/details-by-name',array(
            'domain-name'           => $row['domain'],
            'options'           => array('OrderDetails','NsDetails','ContactIds'),
        ),'GET');
        $res = fix::values(array(
            'orderid->id'           => 'id',
            'domainname->domain'        => 'domain',
            'domsecret->password'       => 'password',
        ),$data);
        $res['created_date'] = format::datetime($data['creationtime'],'iso');
        $res['expiration_date'] = format::datetime($data['endtime'],'iso');
        if (err::is($data)) return $data;
        for ($i=1;$i<=13;$i++) if ($data["ns$i"]) $nss[] = $data["ns$i"];
        if ($nss) $res['nameservers'] = arr::cjoin($nss);
        return $res;
        #return array_merge($data,$res);
    }
    public function domainInfo ($row) {
        $res = $this->_domainInfo($row);
        if (!err::is($res)) return $res;
        if (!$row['password'] && $row['id']) $row = array_merge($row,$this->base->domainGetPassword($row));
        return $this->base->getTool(3027237)->domainInfo($row);
    }

    public function domainCheckTransfer ($row) { // check through evo@ahnames
        return $this->base->getTool(3027237)->domainCheckTransfer($row);
    }

    public function domainPrepareContacts ($row) {
        $contacts = $this->base->domainGetWPContactsInfo($row);
        if (err::is($contacts)) return $contacts;
        foreach ($this->base->getContactTypes() as $t) {
            $cid = $contacts[$t]['id'];
            $remoteid = $rids[$cid];
            if (!$remoteid) {
                $r = $this->contactSet($contacts[$t]);
                if (err::is($r)) return $r;
                $remoteid = $r['id'];
                $rids[$cid] = $remoteid;
            };
            $row[$t.'_remoteid'] = $remoteid;
        };
        return $row;
    }

    public function domainRegister ($row) {
        if (!$row['nss']) $row['nss'] = arr::get($this->base->domainGetNSs($row),'nss');
        if (!$row['nss']) $row['nss'] = array('ns1.topdns.me','ns2.topdns.me');
        $row = $this->domainPrepareContacts($row);
        if (err::is($row)) return $row;
        $res = $this->call('domains/register',$row,'POST',array(
            'domain->domain-name'           => 'domain',
            'period->years'             => 'period',
            'nss->ns'               => 'nss',
            'registrant_remoteid->reg-contact-id'   => 'id',
            'admin_remoteid->admin-contact-id'  => 'id',
            'tech_remoteid->tech-contact-id'    => 'id',
            'billing_remoteid->billing-contact-id'  => 'id',
        ),array(
            'entityid->id'              => 'id',
            'description->domain'           => 'domain',
        ),array(
            'customer-id'               => $this->customer_id,
            'invoice-option'            => 'NoInvoice',
            'protect-privacy'           => 'false',
        ));
        return $res;
    }

    public function domainTransfer ($row) {
        $row = $this->domainPrepareContacts($row);
        $res = $this->call('domains/transfer',$row,'POST',array(
            'domain->domain-name'           => 'domain',
            'password->auth-code'           => 'password',
            'nss->ns'               => 'nss',
            'registrant_remoteid->reg-contact-id'   => 'id',
            'admin_remoteid->admin-contact-id'  => 'id',
            'tech_remoteid->tech-contact-id'    => 'id',
            'billing_remoteid->billing-contact-id'  => 'id',
        ),array(
            'entityid->id'              => 'id',
            'description->domain'           => 'domain',
        ),array(
            'customer-id'               => $this->customer_id,
            'invoice-option'            => 'NoInvoice',
            'protect-privacy'           => 'false',
        ));
        return $res;
    }

    public function domainRenew ($row) {
    }

    public function domainSetNSs ($row) {
        return $this->call_orderid('domains/modify-ns',$row,'POST',array(
            'nss->ns'           => 'nss',
        ));
    }

    public function domainSetContacts ($row) {
        $res = $this->call_orderid('domains/modify-contact',$row,'POST',array(
            'registrant_remoteid->reg-contact-id'   => 'id',
            'admin_remoteid->admin-contact-id'  => 'id',
            'tech_remoteid->tech-contact-id'    => 'id',
            'billing_remoteid->billing-contact-id'  => 'id',
        ),array(
            'entityid->id'              => 'id',
            'description->domain'           => 'domain',
        ));
        if (err::is($res) && $res['message']=='The Contacts selected are the same as the existing contacts')
            return arr::mget($row,'id,domain');
        return $res;
    }

    public function domainEnableLock ($row) {
        return $this->call_orderid('domains/enable-theft-protection',$row,'POST');
    }

    public function domainsEnableLock ($row) {
        foreach ($rows as $k=>$row) $res[$k] = $this->domainEnableLock($row);
        return err::reduce($res);
    }

    public function domainDisableLock ($row) {
        return $this->call_orderid('domains/disable-theft-protection',$row,'POST');
    }

    public function domainsDisableLock ($rows) {
        foreach ($rows as $k=>$row) $res[$k] = $this->domainDisableLock($row);
        return err::reduce($res);
    }

    public function domainSetPassword ($row) {
        return $this->call_orderid('domains/modify-auth-code',$row,'POST',array(
            'password->auth-code'       => 'password',
        ));
    }

    public function domainsLoadInfo ($rows) { return true; }

    public function domainsGetInfo ($rows) {
        foreach ($rows as $id=>$row) $res[$id] = $this->domainInfo($row);
        return err::reduce($res);
    }

/// contact
    public function contactSet ($row) {
        $info = $this->contactGetId($row);
        $row['id'] = $info['id'];
        return (err::is($info) || !$info['id']) ? $this->contactCreate($row) : $this->contactUpdate($row);
    }

    public function contactGetId ($row) {
        $data = $this->call('contacts/search',$row,'GET',array(
            'name'              => 'label',
            'email'             => 'email',
        ),array(),array(
            'customer-id'           => $this->customer_id,
            'no-of-records'         => 10,
            'page-no'           => 1,
        ));
        return err::is($data) ? $data : array('id'=>$data['result'][0]['entity.entityid']);
    }

    public function contactCreate ($row) {
        $id = $this->call('contacts/add',$this->contactPrepare($row),'POST',null,null,array(
            'type'              => 'Contact',
            'customer-id'           => $this->customer_id,
        ));
        return compact('id');
    }

    public function contactUpdate ($row) {
        return $this->call('contacts/modify',$this->contactPrepare($row),'POST',null,array(
            'entityid->id'          => 'id',
        ),array(
            'customer-id'           => $this->customer_id,
            'type'              => 'Contact',
        ));
    }

    public function contactPrepare ($row) {
        $phone = fix::digits($row['voice_phone']);
        $cc = retrieve::phoneCC($phone);
        $row['phone-cc'] = $cc;
        $row['phone'] = substr($phone,strlen($cc));
        $fax = fix::digits($row['fax_phone']);
        if ($fax) {
            $cc = retrieve::phoneCC($fax);
            $row['fax-cc'] = $cc;
            $row['fax'] = substr($fax,strlen($cc));
        };
        if (!$row['organization']) $row['organization'] = 'Not Applicable';
        return check::values(array(
            'id->contact-id'        => 'id',
            'name'              => 'label',
            'organization->company'     => 'label',
            'email'             => 'email',
            'street1->address-line-1'   => 'label',
            'street2->address-line-2'   => 'label',
            'street3->address-line-3'   => 'label',
            'city'              => 'label',
            'postal_code->zipcode'      => 'label',
            'province->state'       => 'label',
            'country'           => 'ref,uc',
            'phone-cc'          => 'digits',
            'phone'             => 'digits',
            'fax-cc'            => 'digits',
            'fax'               => 'digits',
        ),$row);
    }

/// host
    public function hostSet ($row) {
        return $this->hostCreate($row);
    }

    public function hostCreate ($row) {
        return $this->call_orderid('domains/add-cns',$row,'POST',array(
            'host->cns'         => 'ns',
            'ips->ip'           => 'ips',
        ));
    }

/// poll
    public function pollsGetNew ($jrow) {
    }

}
