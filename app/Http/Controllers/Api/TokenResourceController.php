<?php

namespace App\Http\Controllers\Api;

use App\Handlers\AssetInfo\AssetInfoCache;
use App\Handlers\Bvam\Facade\BvamUtil;
use App\Repositories\BvamRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
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
            ->map('trim')
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
            if (!$this->isValidAssetName($asset_name)) {
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

    // ------------------------------------------------------------------------
    
    
    protected function isValidAssetName($name) {
        if ($name === 'BTC') { return true; }
        if ($name === 'XCP') { return true; }

        // check free asset names
        if (substr($name, 0, 1) == 'A') { return $this->isValidFreeAssetName($name); }

        if (!preg_match('!^[A-Z]+$!', $name)) { return false; }
        if (strlen($name) < 4) { return false; }

        return true;
    }

    // allow integers between 26^12 + 1 and 256^8 (inclusive), prefixed with 'A'
    protected function isValidFreeAssetName($name) {
        if (substr($name, 0, 1) != 'A') { return false; }

        $number_string = substr($name, 1);
        if (!preg_match('!^\\d+$!', $number_string)) { return false; }
        if (bccomp($number_string, "95428956661682201") < 0) { return false; }
        if (bccomp($number_string, "18446744073709600000") > 0) { return false; }

        return true;
    }

    
}
