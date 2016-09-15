<?php

use \PHPUnit_Framework_Assert as PHPUnit;

/**
*  AssetInfoCacheHelper
*/
class AssetInfoCacheHelper
{


    function mockAssetInfoCacheGetMultiple($asset_names, $times=1) {
        $should_return_results = [];
        foreach($asset_names as $asset_name) {
            $should_return_results[] = [
                'asset'       => $asset_name,
                'status'      => 'valid',
                'description' => 'My '.strtolower($asset_name),
            ];
        }

        $mock_cache = Mockery::mock('App\Handlers\AssetInfo\AssetInfoCache');
        $mock_cache->shouldReceive('getMultiple')->with($asset_names)->times($times)->andReturn($should_return_results);
        app()->bind('App\Handlers\AssetInfo\AssetInfoCache', function() use ($mock_cache) {
            return $mock_cache;
        });

        return $mock_cache;
    }


}