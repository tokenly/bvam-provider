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


        // $tx_helper = app('SampleTransactionsHelper');
        // $parsed_tx = $tx_helper->loadSampleTransaction('sample_xcp_parsed_issuance_01.json');
        // $block = app('SampleBlockHelper')->createSampleBlock('default_parsed_block_01.json');
        // $block_event_context = app('App\Handlers\XChain\Network\Bitcoin\Block\BlockEventContextFactory')->newBlockEventContext();
        // Event::fire('xchain.tx.confirmed', [$parsed_tx, 1, 101, $block, $block_event_context]);

        // verifies that forget was called once
        Mockery::close();
    }


}
