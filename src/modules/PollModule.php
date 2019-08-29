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
 * Poll operations.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 */
class PollModule extends AbstractModule
{
    public function pollsGetNew($jrow)
    {
        foreach (['incoming', 'outgoing', 'expired'] as $state) {
            $domains = $this->base->domainsSearch([
                'limit' => 'ALL',
                'state' => $state,
                'access_id' => $this->tool->data['id'],
            ]);

            if (empty($domains)) {
                continue;
            }

            $polls = call_user_func_array([$this, "_pollsGet" . ucfirst($state) . "Message"], [$polls, $domains]);
        }

        return empty($polls) ? true : $polls;
    }

    protected function _pollsGetTransferMessage($polls = [], $domains = []) : array
    {
        foreach ($domains as $id => $domain) {
            $info = $this->base->domainInfo($domain);

            if (err::is($info) && strpos(err::get($info), "Website doesn't exist for {$domain['domain']}") !== false) {
                $polls[] = $this->_pollBuild($domain, [
                    'type' => 'clientRejected',
                    'message' => 'Transfer rejected',
                ]);

                continue;
            }

            if (!empty($info['statuses_arr'][DomainModule::STATE_OK])) {
                $polls[] = $this->_pollBuild($domain, [
                    'type' => 'serverApproved',
                    'message' => 'Transfer completed',
                ]);
            }
        }

        return $polls;
    }

    protected function _pollsGetOutgoingMessage($polls = [], $domains) : array
    {
        foreach ($domains as $id => $domain) {
            $info = $this->base->domainInfo($domain);

            if (err::is($info) && strpos(err::get($info), "Website doesn't exist for {$domain['domain']}") !== false) {
                $polls[] = $this->_pollBuild($domain, [
                    'type' => 'serverApproved',
                    'message' => 'Transfer approved',
                ], true);
            }
        }

        return $polls;
    }

    protected function _pollsGetDeleteMessage($polls = [], $domains) : array
    {
        foreach ($domains as $id => $domain) {
            $info = $this->base->domainInfo($domain);

            if (err::is($info) && strpos(err::get($info), "Website doesn't exist for {$domain['domain']}") !== false) {
                // TODO CHANGE DOMAIN STATE
            }
        }

        return $polls;

    }

    private function _pollBuild($row, $data, $outgoing = false) : array
    {
        return array_merge([
            'class' => 'domain',
            'name' => $row['domain'],
            'request_client' => $this->tool->data['name'],
            'request_date' => date("Y-m-d H:i:s"),
            'action_date' => date("Y-m-d H:i:s"),
            'action_client' => $this->tool->data['name'],
            'outgoing' => $outgoing,
        ], $data);
    }
}
