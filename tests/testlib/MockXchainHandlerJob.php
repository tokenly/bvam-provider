<?php 

use Tokenly\XchainReceiveQueue\Jobs\XchainReceiveJob;

/**
* XChain MockXchainHandlerJob
*/
class MockXchainHandlerJob extends XchainReceiveJob
{

    public static function getCalledEvents() {
        return isset($GLOBALS['MockXchainHandlerJob_calledEvents']) ? $GLOBALS['MockXchainHandlerJob_calledEvents'] : [];
    }

    public function handleAnyEvent($payload) {
        if (!isset($GLOBALS['MockXchainHandlerJob_calledEvents'])) { $GLOBALS['MockXchainHandlerJob_calledEvents'] = []; }
        
        $GLOBALS['MockXchainHandlerJob_calledEvents'][] = $payload;

        return;
    }


}