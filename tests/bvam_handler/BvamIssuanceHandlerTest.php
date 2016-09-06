<?php

use App\Jobs\XchainHandler;
use \PHPUnit_Framework_Assert as PHPUnit;

class BvamIssuanceHandlerTest extends TestCase
{

    protected $use_database = true;

    public function testHandleIssuanceWithBvam()
    {
        $mock_bvam_util = Mockery::mock('App\Handlers\Bvam\BvamUtil')
            ->makePartial()
            // $description_info['bvam_hash'], $asset, $payload['txid'], $confirmations
            ->shouldReceive('confirmBvam')->with('T1111111111111111111111111111', 'CATEGORYID', '1111111122222222111111112222222211111111222222221111111122222222', 0)
            ->once()
            ->andReturn(true)
            ->getMock();
        app()->instance('App\Handlers\Bvam\BvamUtil', $mock_bvam_util);

        $xchain_helper = app('XchainQueueHelper');
        $payload = json_decode($xchain_helper->buildIssuanceNotification([], 'T1111111111111111111111111111')['payload'], true);
        (new XchainHandler($payload))->handle();

        Mockery::close();
    }

    // check that it calls BvamUtil::scrapeBvam
    public function testHandleExternalProviderIssuanceWithBvam()
    {
        $mock_bvam_util = Mockery::mock('App\Handlers\Bvam\BvamUtil')
            ->makePartial()
            ->shouldReceive('scrapeBvam')->with('http://external-provider.com/T1111111111111111111111111111.json')
            ->once()
            ->getMock();
        app()->instance('App\Handlers\Bvam\BvamUtil', $mock_bvam_util);

        $xchain_helper = app('XchainQueueHelper');
        $override_vars=[];
        array_set($override_vars, 'counterpartyTx.description', 'http://external-provider.com/T1111111111111111111111111111.json');
        $payload = json_decode($xchain_helper->buildIssuanceNotification($override_vars)['payload'], true);
        (new XchainHandler($payload))->handle();

        Mockery::close();
    }

}
