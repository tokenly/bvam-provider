<?php 

use Tokenly\XChainClient\WebHookReceiver;

/**
* XChain WebhookReceiverMock
*/
class WebhookReceiverMock extends WebHookReceiver
{
    
    public function validateWebhookNotification($json_data) {
        return true;
    }


}