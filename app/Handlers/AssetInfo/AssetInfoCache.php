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

    const FOREVER_CACHE_LENGTH_MINUTES   = 31536000; // one year
    const ENHANCED_CACHE_LENGTH_MINUTES  = 1440;     // 24 hours
    const ERROR_CACHE_LENGTH_MINUTES     = 60;       //  1 hour
    const NOT_FOUND_CACHE_LENGTH_MINUTES = 1;        //  1 minute

    public function __construct(Repository $laravel_cache, Client $xchain_client, EnhancedAssetInfoResolver $enhanced_asset_info_resolver)
    {
        $this->laravel_cache                = $laravel_cache;
        $this->xchain_client                = $xchain_client;
        $this->enhanced_asset_info_resolver = $enhanced_asset_info_resolver;
    }

    public function getInfo($asset_name) {
        $results = $this->fetchMultipleAssetInformation([$asset_name]);
        return $results ? $results[0] : $results;
    }

    public function getMultiple($asset_names) {
        return $this->fetchMultipleAssetInformation($asset_names);
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

    // ------------------------------------------------------------------------
    
    protected function fetchMultipleAssetInformation($asset_names) {
        // Log::debug("\$asset_names=".json_encode($asset_names, 192));
        $asset_names_to_load = [];
        $responses_by_asset_name = [];
        foreach($asset_names as $asset_name) {
            $cached_info = $this->getFromCache($asset_name);
            // Log::debug("\$asset_name=".json_encode($asset_name)." \$cached_info=".json_encode($cached_info, 192));
            if ($cached_info) {
                $responses_by_asset_name[$asset_name] = $cached_info;
            } else {
                $responses_by_asset_name[$asset_name] = null;
                $asset_names_to_load[] = $asset_name;
            }
        }


        // Log::debug("\$asset_names_to_load=".json_encode($asset_names_to_load, 192));
        if ($asset_names_to_load) {
            $cache_length = self::FOREVER_CACHE_LENGTH_MINUTES;

            try {
                $infos = $this->_loadFromXChain($asset_names_to_load);

                // resolve each description
                foreach($infos as $info) {
                    if ($info) {
                        $enhanced_info = $this->enhanced_asset_info_resolver->resolveExtendedAssetInfoFromDescription($info['description']);
                        if ($enhanced_info['is_enhanced'] AND $enhanced_info['enhanced_data']) {
                            $info['enhanced_data'] = $enhanced_info['enhanced_data'];
                            $was_enhanced = true;
                            $cache_length = min($cache_length, self::ENHANCED_CACHE_LENGTH_MINUTES);
                        }

                        if ($enhanced_info['had_error']) {
                            $cache_length = min($cache_length, self::ERROR_CACHE_LENGTH_MINUTES);
                        }

                        $responses_by_asset_name[$info['asset']] = $info;
                    } else {
                        // asset didn't exist in counterparty yet
                        $info = [];
                        $cache_length = min($cache_length, self::NOT_FOUND_CACHE_LENGTH_MINUTES);
                    }

                }

            } catch (Exception $e) {
                if ($e->getCode() == 404) {
                    EventLog::logError('assetInfo.error', $e);

                    // at least one asset was not found by xchain
                    $responses_by_asset_name = [];
                } else {
                    EventLog::logError('assetInfo.error', $e);
                    $responses_by_asset_name = [];
                }
            }

            // Log::debug("\$responses_by_asset_name=".json_encode($responses_by_asset_name, 192));
            foreach($responses_by_asset_name as $asset_name => $info) {
                if ($info !== null) {
                    if ($cache_length == self::FOREVER_CACHE_LENGTH_MINUTES) {
                        $this->laravel_cache->forever($asset_name, $info);
                    } else {
                        $this->laravel_cache->put($asset_name, $info, $cache_length);
                    }
                }
            }

        }

        return array_values($responses_by_asset_name);
    }

    // public for mocking
    public function _loadFromXChain($asset_names) {
        return $this->xchain_client->getAssets($asset_names);
    }



}
