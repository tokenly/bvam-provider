<?php

namespace App\Handlers\AssetInfo;

use Illuminate\Contracts\Cache\Repository;
use Tokenly\XChainClient\Client;
use \Exception;

/*
* AssetInfoCache
*/
class AssetInfoCache
{
    public function __construct(Repository $laravel_cache, Client $xchain_client)
    {
        $this->laravel_cache = $laravel_cache;
        $this->xchain_client = $xchain_client;
    }

    public function getInfo($asset_name) {
        $cached_info = $this->getFromCache($asset_name);
        if ($cached_info === null) {
            $info = $this->loadFromXChain($asset_name);
            if ($info) {
                $this->laravel_cache->forever($asset_name, $info);
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
