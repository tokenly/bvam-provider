<?php

use App\Jobs\XchainHandler;
use \PHPUnit_Framework_Assert as PHPUnit;

class AssetInfoCacheTest extends TestCase
{

    protected $use_database = true;

    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testAssetInfoCacheClears()
    {
        $mock_cache = Mockery::mock('App\Handlers\AssetInfo\AssetInfoCache');
        $mock_cache->shouldReceive('forget')->with('NEWCOIN')->once();
        $mock_cache->shouldReceive('getInfo')->with('NEWCOIN')->once();
        app()->bind('App\Handlers\AssetInfo\AssetInfoCache', function() use ($mock_cache) {
            return $mock_cache;
        });

        // trigger a fake issuance transaction for NEWCOIN
        $xchain_helper = app('XchainQueueHelper');
        $payload = json_decode($xchain_helper->buildConfirmedIssuanceNotification(['asset' => 'NEWCOIN'], 'T1111111111111111111111111111')['payload'], true);
        (new XchainHandler($payload))->handle();


        // verifies that forget was called once
        Mockery::close();
    }

    public function testAssetInfoCacheSingle() {
        $mock_cache = Mockery::mock(
                '\App\Handlers\AssetInfo\AssetInfoCache', [app('Illuminate\Cache\Repository'), app('Tokenly\XChainClient\Client'), app('App\Handlers\AssetInfo\EnhancedAssetInfoResolver')]
            )->makePartial();
        $mock_cache->shouldReceive('_loadFromXChain')->with(['NEWCOIN'])->once()->andReturn([
            ['asset' => 'NEWCOIN', 'status' => 'valid', 'description' => 'My newcoin'],
        ]);

        // echo "\$mock_cache is ".get_class($mock_cache)."\n";
        $response = $mock_cache->getInfo('NEWCOIN');
        // echo "\$response: ".json_encode($response, 192)."\n";
        PHPUnit::assertEquals([
            'asset'       => 'NEWCOIN',
            'status'      => 'valid',
            'description' => 'My newcoin',
        ], $response);

        // verifies that forget was called once
        Mockery::close();
    }

    public function testAssetInfoCacheMultiple() {
        $mock_cache = Mockery::mock(
                '\App\Handlers\AssetInfo\AssetInfoCache', [app('Illuminate\Cache\Repository'), app('Tokenly\XChainClient\Client'), app('App\Handlers\AssetInfo\EnhancedAssetInfoResolver')]
            )->makePartial();
        $mock_cache->shouldReceive('_loadFromXChain')->with(['FOOCOIN','BARCOIN'])->once()->andReturn([
            ['asset' => 'FOOCOIN', 'status' => 'valid', 'description' => 'My foocoin'],
            ['asset' => 'BARCOIN', 'status' => 'valid', 'description' => 'My barcoin'],
        ]);

        $mock_cache->shouldReceive('_loadFromXChain')->with(['BAZCOIN'])->once()->andReturn([
            ['asset' => 'BAZCOIN', 'status' => 'valid', 'description' => 'My bazcoin'],
        ]);

        $response = $mock_cache->getMultiple(['FOOCOIN','BARCOIN']);
        PHPUnit::assertEquals([
            [
                'asset'       => 'FOOCOIN',
                'status'      => 'valid',
                'description' => 'My foocoin',
            ], 
            [
                'asset'       => 'BARCOIN',
                'status'      => 'valid',
                'description' => 'My barcoin',
            ], 
        ], $response);

        $response = $mock_cache->getMultiple(['FOOCOIN','BARCOIN','BAZCOIN']);
        PHPUnit::assertEquals([
            [
                'asset'       => 'FOOCOIN',
                'status'      => 'valid',
                'description' => 'My foocoin',
            ], 
            [
                'asset'       => 'BARCOIN',
                'status'      => 'valid',
                'description' => 'My barcoin',
            ], 
            [
                'asset'       => 'BAZCOIN',
                'status'      => 'valid',
                'description' => 'My bazcoin',
            ], 
        ], $response);

        // verifies that forget was called once
        Mockery::close();
    }


}
