<?php

namespace App\Http\Controllers\Api;

use App\Handlers\AssetInfo\AssetInfoCache;
use App\Handlers\Bvam\Facade\BvamUtil;
use App\Repositories\BvamRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tokenly\AssetNameUtils\Validator as AssetValidator;
use Tokenly\LaravelApiProvider\Helpers\APIControllerHelper;

class TokenResourceController extends ApiController
{

    public function getTokenInfo(Request $request, AssetInfoCache $asset_info_cache, BvamRepository $bvam_repository, APIControllerHelper $helper, $asset_name) {
        $result = $this->processMultipleTokens([$asset_name], $request, $asset_info_cache, $bvam_repository, $helper);
        if ($result instanceof SymfonyResponse) { return $result; }

        return $helper->transformValueForOutput($result[0]);
    }

    public function getMultipleTokenInfo(Request $request, AssetInfoCache $asset_info_cache, BvamRepository $bvam_repository, APIControllerHelper $helper) {
        $asset_names = collect(explode(',', $request->input('assets')))
            ->map(function($asset) { return trim($asset); })
            ->reject(function($asset) { return empty($asset); })
            ->toArray();

        $result = $this->processMultipleTokens($asset_names, $request, $asset_info_cache, $bvam_repository, $helper);
        if ($result instanceof SymfonyResponse) { return $result; }

        return $helper->buildJSONResponse($result);
    }

    // ------------------------------------------------------------------------

    protected function processMultipleTokens($asset_names, Request $request, AssetInfoCache $asset_info_cache, BvamRepository $bvam_repository, APIControllerHelper $helper) {
        $errors = [];
        foreach($asset_names as $asset_name) {
            // check the asset name
            if (!AssetValidator::isValidAssetName($asset_name)) {
                $errors[] = 'The asset '.$asset_name.' was invalid';
            }
        }
        if ($errors) { return $helper->newJsonResponseWithErrors($errors, 422); }

        // load the asset info
        $asset_infos = $asset_info_cache->getMultiple($asset_names);

        if (!$asset_infos) {
            return $helper->newJsonResponseWithErrors('This asset was not found', 404);
        }

        $enhanced_asset_infos = [];
        foreach($asset_names as $offset => $asset_name) {
            $asset_info = $asset_infos[$offset];

            // don't build null results for non-existent assets
            if (!$asset_info) { continue; }

            // find the bvam data by token name
            $bvam = $bvam_repository->findByActiveBvamByAsset($asset_name);

            $enhanced_asset_info = [];
            if (isset($asset_info['enhanced_data'])) {
                $enhanced_asset_info = $asset_info['enhanced_data'];
                unset($asset_info['enhanced_data']);
            }

            // assemble the data
            $enhanced_asset_infos[] = [
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

        }

        return $enhanced_asset_infos;
    }

    
}
