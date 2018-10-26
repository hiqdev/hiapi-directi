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
 * Contact operations.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class ContactModule extends AbstractModule
{
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
            'customer-id'       => $this->tool->getCustomerId(),
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
    public function contactCreate(array $row)
    {
        $id = $this->post('contacts/add',
            $this->contactPrepare($row),
            null,
            null, [
            'type'              => 'Contact',
            'customer-id'       => $this->tool->getCustomerId(),
        ]);

        return compact('id');
    }

    public function contactUpdate($row)
    {
        return $this->post('contacts/modify',$this->contactPrepare($row),null,[
            'entityid->id'          => 'id',
        ],[
            'customer-id'           => $this->tool->getCustomerId(),
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

}
