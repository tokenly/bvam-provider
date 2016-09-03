<?php

use App\Handlers\Bvam\Facade\BvamUtil;
use App\Models\Bvam as BvamModel;
use App\Models\BvamCategory;
use Illuminate\Support\Facades\Config;
use \PHPUnit_Framework_Assert as PHPUnit;

class XchainQueueTest extends TestCase
{

    protected $use_database = false;

    public function testReceiveNotification()
    {
        // mock the job handler
        Config::set('xchainqueue.jobClass', 'MockXchainHandlerJob');

        $xchain_queue_helper = app('XchainQueueHelper')->mockWebhookReceiver();
        $request = $xchain_queue_helper->buildReceiveRequest();

        // receive the request
        $response = $xchain_queue_helper->receiveRequest($request);

        // check the events
        $called_events = MockXchainHandlerJob::getCalledEvents();
        PHPUnit::assertCount(1, $called_events);
    }



}
