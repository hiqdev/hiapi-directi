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
use hiapi\legacy\lib\deps\fix;
use hiapi\legacy\lib\deps\check;
use hiapi\legacy\lib\deps\retrieve;

/**
 * Contact operations.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class ContactModule extends AbstractModule
{

    const ORGANISATION_NOT_APPLICABLE = 'Not Applicable';
    const ORGANISATION_NA = 'N/A';

    public function contactInfo($row)
    {
        $data = $this->get('contacts/details', [
            'contact-id' => $row['id'],
        ]);

        return $this->contactParse($data);

    }

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
            'domain'            => 'domain',
        ],[],[
            'customer-id'       => $this->contactGetCustomerID($row),
            'no-of-records'     => 10,
            'page-no'           => 1,
        ]);

        if (err::is($data) || empty($data['result'][0]['entity.entityid'])) {
            return $data;
        }

        return ['id'=>$data['result'][0]['entity.entityid']];
    }

    /**
     * @param array $row
     * @return array
     */
    public function contactCreate(array $row): array
    {
        $id = $this->post('contacts/add',
            $this->contactPrepare($row),
            null,
            null, array_filter([
                'type'          => 'Contact',
                'customer-id'   => $this->contactGetCustomerID($row),
                'product-key'   => $row['product-key'] ?? null,
                'attr-name1'    => $row['product-key'] ? 'purpose' : null,
                'attr-value1'   => $row['product-key'] ? 'P1' : null,
                'attr-name2'    => $row['product-key'] ? 'category' : null,
                'attr-value2'   => $row['product-key'] ? 'C32' : null,
        ]));

        return compact('id');
    }

    public function contactUpdate($row)
    {
        if ($this->_checkIsSame($row)) {
            return $row;
        }

        return $this->contactCreate($row);
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
            $row['organization'] = self::ORGANISATION_NOT_APPLICABLE;
        }

        return check::values([
            'id->contact-id'            => 'id',
            'name'                      => 'label',
            'organization->company'     => 'label',
            'email'                     => 'email',
            'street1->address-line-1'   => 'label',
            'street2->address-line-2'   => 'label',
            'street3->address-line-3'   => 'label',
            'city'                      => 'label',
            'postal_code->zipcode'      => 'label',
            'province->state'           => 'label',
            'country'                   => 'ref,uc',
            'phone-cc'                  => 'digits',
            'phone'                     => 'digits',
            'fax-cc'                    => 'digits',
            'fax'                       => 'digits',
        ], $row);
    }

    public function contactParse($row)
    {
        $data = check::values([
            'entityid->id'          => 'id',
            'name'                  => 'label',
            'company->organization' => 'label',
            'emailaddr->email'      => 'email',
            'address1->street1'     => 'label',
            'address2->street2'     => 'label',
            'address3->street3'     => 'label',
            'city'                  => 'label',
            'zip->postal_code'      => 'label',
            'state->province'       => 'label',
            'country'               => 'lc,ref',
            'telnocc->phone-cc'     => 'digits',
            'telno->phone'          => 'digits',
            'faxnocc->fax-cc'       => 'digits',
            'faxno->fax'            => 'digits',
        ], $row);
        if ($this->isEmptyCompany($data['organization'])) {
            $data['organization'] = '';
        }

        $data['voice_phone'] = fix::phone($data['phone-cc'] . $data['phone']);
        $data['fax_phone'] = fix::phone($data['fax-cc'] . $data['fax']);
        [$data['first_name'], $data['last_name']] = explode(" ", $data['name'], 2);
        unset($data['phone-cc'], $data['phone'], $data['fax-cc'], $data['fax']);

        return $data;
    }

    public function contactGetCustomerID($row)
    {
        if (empty($row['domain'])) {
            return $this->tool->getCustomerId();
        }

        $info = $this->tool->domainInfo([
            'domain' => $row['domain'],
        ]);

        return $info['customer'] ?? $this->tool->getCustomerId();
    }

    protected function _checkIsSame(array $row) : bool
    {
        $new = $this->contactPrepare($row);
        $res = $this->contactPrepare($this->contactInfo($row));

        foreach ($new as $key => $value) {
            if (in_array($key, ['fax-cc', 'fax'], true)) {
                continue;
            }

            if ($value == $res[$key]) {
                continue;
            }

            if ($key === 'company') {
                if ($this->isEmptyCompany($value) && $this->isEmptyCompany($res[$key])) {
                    continue;
                }
            }

            return false;
        }

        return true;
    }

    protected function isEmptyCompany($org)
    {
        return in_array($org, [self::ORGANISATION_NOT_APPLICABLE, self::ORGANISATION_NA], true);
    }
}
