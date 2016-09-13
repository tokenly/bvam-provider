<?php

use App\Jobs\XchainHandler;
use \PHPUnit_Framework_Assert as PHPUnit;

class BvamBroadcastHandlerTest extends TestCase
{

    protected $use_database = true;

    public function testHandleBroadcastWithBvamCategory()
    {
        $txid = '0000000000000000000000000000000000000000000000000000000000003333';
        $mock_bvam_util = Mockery::mock('App\Handlers\Bvam\BvamUtil')
            ->makePartial()
            // confirmBvamCategory($hash, $category_id_from_transaction, $version, $txid, $confirmations)
            ->shouldReceive('confirmBvamCategory')->with('S1111111111111111111111111111', 'My Category', '0.0.1', $txid, 0)
            ->once()
            ->andReturn(true)
            ->getMock();
        app()->instance('App\Handlers\Bvam\BvamUtil', $mock_bvam_util);

        $xchain_helper = app('XchainQueueHelper');
        $payload = json_decode($xchain_helper->buildBroadcastNotification([], 'BVAMCS;My Category;0.0.1;S1111111111111111111111111111')['payload'], true);
        (new XchainHandler($payload))->handle();

        Mockery::close();
    }

}
