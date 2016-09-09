<?php

namespace App\Http\Controllers\Api;

use App\Handlers\AssetInfo\AssetInfoCache;
use App\Handlers\Bvam\Facade\BvamUtil;
use App\Repositories\BvamRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class TokenResourceController extends ApiController
{

    public function getTokenInfo(Request $request, AssetInfoCache $asset_info_cache, BvamRepository $bvam_repository, APIControllerHelper $helper, $asset_name) {
        // find the bvam data by token name
        $bvam = $bvam_repository->findByActiveBvamByAsset($asset_name);

        // load the asset info
        $asset_info = $asset_info_cache->getInfo($asset_name);

        if (!$asset_info) {
            return $helper->newJsonResponseWithErrors('This asset was not found', 404);
        }

        $enhanced_asset_info = [];
        if (isset($asset_info['enhanced_data'])) {
            $enhanced_asset_info = $asset_info['enhanced_data'];
            unset($asset_info['enhanced_data']);
        }

        // assemble the data
        $response_data = [
            'asset'       => $asset_info['asset'],
            'filename'    => $bvam ? $bvam['hash'].'.json' : null,
            'hash'        => $bvam['hash'],
            'uri'         => $bvam ? $bvam['uri'] : null,
            'txid'        => $bvam ? $bvam['txid'] : null,
            'lastUpdated' => $bvam ? $bvam['last_updated']->toIso8601String() : null,
            'assetInfo'   => $asset_info,
            'metadata'    => $bvam ? json_decode($bvam['bvam_json'], true) : BvamUtil::makeBvamFromAssetInfo($asset_info, $enhanced_asset_info),
            'bvamString'  => $bvam ? $bvam['bvam_json'] : null,
            'validated'   => !!$bvam,
        ];

        return $helper->transformValueForOutput($response_data);
    }

}
