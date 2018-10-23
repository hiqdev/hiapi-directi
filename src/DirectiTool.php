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

use arr;
use err;
use fix;
use check;
use retrieve;
use apiWebTool;
use GuzzleHttp\Client;

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
    protected $url;
    protected $login;
    protected $password;
    protected $customer_id;
    protected $default_nss = ['ns1.topdns.me', 'ns2.topdns.me'];

    protected $httpClient;

    public function __construct($base, $data)
    {
        parent::__construct($base, $data);
        foreach (['url','login','password','customer_id'] as $key) {
            if (empty($data[$key])) {
                throw new \Exception("`$key` must be given for DirectiTool");
            }
            $this->{$key} = $data[$key];
        }
    }

    public function getPlain($url,$data=null,$method='',$options = [])
    {
        $url .= '?';
        foreach ($data as $k => $v) {
            $url .= static::_prepareVar($k,$v);
        }

        return parent::getPlain($url,null,$method);
    }

    public static function _prepareVar($k,$v)
    {
        if (is_array($v)) {
            foreach ($v as $w) {
                $res .= static::_prepareVar($k,$w);
            }

            return $res;
        } else {
            return $k . '=' . urlencode($v) . '&';
        }
    }

    public function get($name, $data, $inputs = null, $returns = null, $add_data = [])
    {
        return $this->call($name, $data, 'GET', $inputs, $returns, $add_data);
    }

    public function post($name, $data, $inputs = null, $returns = null, $add_data = [])
    {
        return $this->call($name, $data, 'POST', $inputs, $returns, $add_data);
    }

    /// XXX: DEPRECATED
    public function call($method,$name,$data,$inputs=null,$returns=null,$add_data=[])
    {
        if (err::is($data)) {
            return $data;
        }
        $add_data['auth-userid']    = $this->login;
        $add_data['api-key']        = $this->password;

        $res = $this->getHttpClient()->checkedRequest($method, $name . '.json',$data,$inputs,null,$add_data);
        if ($res['status'] === 'ERROR') {
            return error('directi error',$res);
        }

        return $returns ? fix::values($returns,$res) : $res;
    }

    protected function getHttpClient()
    {
        if ($this->httpClient === null) {
            $guzzle = new Client();
            $this->httpClient = new HttpClient(rtrim($this->url, '/') . '/api/');
        }

        return $this->httpClient;
    }

    public function post_orderid($method,$name,$data,$inputs=null,$returns=null)
    {
        $res = $this->domainGetId($data);
        if (err::is($res)) {
            return $res;
        }

        return $this->call($method,$name,$data,$inputs,$returns,['order-id'=>$res['id']]);
    }

    /// domain maintenance
    public function domainSaveContacts($row)
    {
        return $this->base->_simple_domainSaveContacts($row);
    }

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
            $row['nss'] = $this->default_nss;
        }
        $row = $this->domainPrepareContacts($row);
    d($row);
        if (err::is($row)) {
            return $row;
        }
        $res = $this->post('domains/register',$row,[
            'domain->domain-name'           => 'domain',
            'period->years'             => 'period',
            'nss->ns'               => 'nss',
            'registrant_remoteid->reg-contact-id'   => 'id',
            'admin_remoteid->admin-contact-id'  => 'id',
            'tech_remoteid->tech-contact-id'    => 'id',
            'billing_remoteid->billing-contact-id'  => 'id',
        ],[
            'entityid->id'              => 'id',
            'description->domain'           => 'domain',
        ],[
            'customer-id'               => $this->customer_id,
            'invoice-option'            => 'NoInvoice',
            'protect-privacy'           => 'false',
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
            'customer-id'               => $this->customer_id,
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

    public function domainsEnableLock($row)
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

    /// contact
    public function contactSet($row)
    {
        $info = $this->contactGetId($row);
        $row['id'] = $info['id'] ?? null;

        return (err::is($info) || empty($info['id'])) ? $this->contactCreate($row) : $this->contactUpdate($row);
    }

    public function contactGetId($row)
    {
        $data = $this->get('contacts/search',$row,[
            'name'              => 'label',
            'email'             => 'email',
        ],[],[
            'customer-id'       => $this->customer_id,
            'no-of-records'     => 10,
            'page-no'           => 1,
        ]);

        if (err::is($data) || empty($data['result'][0]['entity.entityid'])) {
            return $data;
        }

        return ['id'=>$data['result'][0]['entity.entityid']];
    }

    public function contactCreate($row)
    {
        $id = $this->post('contacts/add', $this->contactPrepare($row), null, null, [
            'type'              => 'Contact',
            'customer-id'       => $this->customer_id,
        ]);

        return compact('id');
    }

    public function contactUpdate($row)
    {
        return $this->post('contacts/modify',$this->contactPrepare($row),null,[
            'entityid->id'          => 'id',
        ],[
            'customer-id'           => $this->customer_id,
            'type'              => 'Contact',
        ]);
    }

    public function contactPrepare($row)
    {
        $phone = fix::digits($row['voice_phone']);
        $cc = retrieve::phoneCC($phone);
        $row['phone-cc'] = $cc;
        $row['phone'] = substr($phone,strlen($cc));
        $fax = fix::digits($row['fax_phone']);
        if ($fax) {
            $cc = retrieve::phoneCC($fax);
            $row['fax-cc'] = $cc;
            $row['fax'] = substr($fax,strlen($cc));
        }
        if (!$row['organization']) {
            $row['organization'] = 'Not Applicable';
        }

        return check::values([
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
        ],$row);
    }

    /// host
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

    /// poll
    public function pollsGetNew($jrow)
    {
    }
}
