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


    // protected function handleEvent_broadcast($payload) {
    //     $description   = $payload['counterpartyTx']['description'];
    //     $asset         = $payload['counterpartyTx']['asset'];
    //     $confirmations = $payload['confirmations'];

    //     $description_info = BvamUtil::parseBvamIssuanceDescription();
    //     $type = $description_info['type'];

    //     $issuance_log_info = [
    //         'asset'         => $asset,
    //         'category_id'   => $description_info['category_id'],
    //         'version'       => null,
    //         'issuer'        => null,
    //         'hash'          => $description_info['bvam_hash'],
    //         'description'   => $description,
    //         'confirmations' => $confirmations,
    //         'confirmed'     => $payload['confirmed'],
    //         'txid'          => $payload['txid'],
    //         'type'          => $type,
    //     ];

    //     switch ($type) {
    //         case 'bvam':
    //             unset($issuance_log_info['category_id']);
    //             unset($issuance_log_info['version']);
    //             unset($issuance_log_info['issuer']);
    //             break;
    //         case 'category':
    //             unset($issuance_log_info['asset']);
    //             break;
    //     }

    //     switch ($type) {
    //         case 'category':
    //             // $category_id_from_transaction = null;
    //             // $confirmed = BvamUtil::confirmBvamCategory($description_info['bvam_hash'], $category_id, $payload['txid'], $confirmations);
    //             // if ($confirmed) {
    //             //     EventLog::info('issuance.confirmedBvam', $issuance_log_info);
    //             // } else {
    //             //     EventLog::warning('issuance.confirmedBvamFailed', $issuance_log_info);
    //             // }
    //             // break;

    //         default:
    //             EventLog::info('issuance.unenhanced', $issuance_log_info);
    //             return;
    //     }
    // }
}
