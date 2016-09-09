<?php

namespace App\Handlers\AssetInfo;

use App\Handlers\AssetInfo\EnhancedAssetInfoResolver;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Log;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\XChainClient\Client;
use \Exception;

/*
* AssetInfoCache
*/
class AssetInfoCache
{

    const FOREVER_CACHE_LENGTH_MINUTES  = -1;
    const ENHANCED_CACHE_LENGTH_MINUTES = 1440; // 24 hours
    const ERROR_CACHE_LENGTH_MINUTES    = 60;   //  1 hour

    public function __construct(Repository $laravel_cache, Client $xchain_client, EnhancedAssetInfoResolver $enhanced_asset_info_resolver)
    {
        $this->laravel_cache                = $laravel_cache;
        $this->xchain_client                = $xchain_client;
        $this->enhanced_asset_info_resolver = $enhanced_asset_info_resolver;
    }

    public function getInfo($asset_name) {
        $cached_info = $this->getFromCache($asset_name);
        if ($cached_info === null) {
            $cache_length = self::FOREVER_CACHE_LENGTH_MINUTES;

            try {
                $info = $this->loadFromXChain($asset_name);

                // resolve the description
                $enhanced_info = $this->enhanced_asset_info_resolver->resolveExtendedAssetInfoFromDescription($info['description']);
                if ($enhanced_info['is_enhanced'] AND $enhanced_info['enhanced_data']) {
                    $info['enhanced_data'] = $enhanced_info['enhanced_data'];
                    $was_enhanced = true;
                    $cache_length = self::ENHANCED_CACHE_LENGTH_MINUTES;
                }

                if ($enhanced_info['had_error']) {
                    $cache_length = self::ERROR_CACHE_LENGTH_MINUTES;
                }

            } catch (Exception $e) {
                if ($e->getCode() == 404) {
                    // the asset was not found by xchain
                    //   cache that it does not exist
                    $info = [];
                } else {
                    EventLog::logError('assetInfo.error', $e);
                    $info = null;
                }
            }

            if ($info !== null) {
                if ($cache_length == self::FOREVER_CACHE_LENGTH_MINUTES) {
                    $this->laravel_cache->forever($asset_name, $info);
                } else {
                    $this->laravel_cache->put($asset_name, $info, $cache_length);
                }
            }

        } else {
            $info = $cached_info;
        }

        return $info;
    }

    public function isDivisible($asset_name) {
        $info = $this->get($asset_name);
        if (!isset($info['divisible'])) { return null; }
        return !!$info['divisible'];
    }

    public function getFromCache($asset_name) {
        return $this->laravel_cache->get($asset_name);
    }

    public function set($asset_name, $asset_info) {
        $this->laravel_cache->forever($asset_name, $asset_info);
    }

    public function forget($asset_name) {
        $this->laravel_cache->forget($asset_name);
    }

    protected function loadFromXChain($asset_name) {
        return $this->xchain_client->getAsset($asset_name);
    }



}
