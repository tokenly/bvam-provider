<?php

namespace App\Jobs;

use App\Handlers\Bvam\Facade\BvamUtil;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\XchainReceiveQueue\Jobs\XchainReceiveJob;

class XchainHandler extends XchainReceiveJob
{

    protected function handleEvent_issuance($payload) {
        $description   = $payload['counterpartyTx']['description'];
        $asset         = $payload['counterpartyTx']['asset'];
        $txid          = $payload['txid'];
        $confirmations = $payload['confirmations'];

        // parse the description and confirm the BVAM
        BvamUtil::processIssuance($asset, $description, $txid, $confirmations);

        // refresh the local asset info cache
        if ($confirmations > 0) {
            $asset_info_cache = app('App\Handlers\AssetInfo\AssetInfoCache');

            // clear the cache
            $asset_info_cache->forget($asset);

            // refresh the cached asset info
            $asset_info_cache->getInfo($asset);
        }
    }


    protected function handleEvent_broadcast($payload) {
        $message       = $payload['counterpartyTx']['message'];
        $txid          = $payload['txid'];
        $confirmations = $payload['confirmations'];
        BvamUtil::processBroadcast($message, $txid, $confirmations);
    }
}
