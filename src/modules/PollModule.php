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
        $polls = [];
        foreach (['incoming', 'outgoing', 'expired', 'deleting'] as $state) {
            $domains = $this->base->domainsSearchForPolls([
                'status' => $state === 'deleting' ? 'checked4deleting' : $state,
                'access_id' => $this->tool->data['id'],
            ]);

            if (empty($domains)) {
                continue;
            }

            $polls = call_user_func_array([$this, "_pollsGet" . ucfirst($state) . "Message"], [$polls, $domains]);
        }

        return empty($polls) ? true : $polls;
    }

    protected function _pollsGetIncomingMessage($polls = [], $domains = []) : array
    {
        if (empty($domains)) {
            return $polls;
        }

        foreach ($domains as $id => $domain) {
            $info = $this->base->domainInfo($domain);

            if (err::is($info) && strpos(err::get($info), DomainModule::OBJECT_DOES_NOT_EXIST) !== false) {
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
        if (empty($domains)) {
            return $polls;
        }

        foreach ($domains as $id => $domain) {
            $info = $this->base->domainInfo($domain);

            if (err::is($info) && strpos(err::get($info), DomainModule::OBJECT_DOES_NOT_EXIST) !== false) {
                $polls[] = $this->_pollBuild($domain, [
                    'type' => 'serverApproved',
                    'message' => 'Transfer approved',
                ], true);
            }
        }

        return $polls;
    }

    public function _pollsGetExpiredMessage($polls = [], $domains = []) : array
    {
        if (empty($domains)) {
            return $polls;
        }

        foreach ($domains as $domain) {
            $info = $this->base->domainInfo($domain);
            $error = err::is($info) && strpos(err::get($info), DomainModule::OBJECT_DOES_NOT_EXIST) !== false;
            if ($error || strpos($info['statuses'], 'pendingDelete') !== false) {
                $this->_setState($domain, DomainModule::STATE_DELETING);
            }

            if ($error) {
                $polls[] = $this->_pollSetDeletedMessage($domain);
            }
        }

        return $polls;
    }

    protected function _pollsGetDeletingMessage($polls = [], $domains) : array
    {
        if (empty($domains)) {
            return $polls;
        }

        foreach ($domains as $id => $domain) {
            $info = $this->base->domainInfo($domain);
            if (err::is($info) && strpos(err::get($info), DomainModule::OBJECT_DOES_NOT_EXIST) !== false) {
                $polls[] = $this->_pollSetDeletedMessage($domain);
            }
        }

        return $polls;
    }

    private function _setState(array $domain, string $state)
    {
        $this->base->domainSetStateInDb(array_merge($domain, ['state' => $state]));
    }

    private function _pollSetDeletedMessage(array $domain) : array
    {
        return $this->_pollBuild($domain, [
            'type' => 'pendingDelete',
            'message' => 'domain deleted',
        ], false);
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
