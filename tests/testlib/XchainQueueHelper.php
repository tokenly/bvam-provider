<?php

use Illuminate\Support\Facades\Log;

/*
* XchainQueueHelper
*/
class XchainQueueHelper
{
    public function __construct() {
    }


    public function mockWebhookReceiver() {
        app()->bind('Tokenly\XChainClient\WebHookReceiver', function() {
            return new WebhookReceiverMock(config('xchain.api_token'), config('xchain.api_key'));
        });

        return $this;
    }

    public function buildReceiveRequest($notification_overrides=[]) {
        return $this->buildRequest(
            $this->buildReceiveNotification($notification_overrides)
        );
    }

    public function buildReceiveNotification($notification_overrides=[]) {
        $template = [
            'asset' => 'WALMART',
            'bitcoinTx' => [
                'txid' => '13277080111fdd1d6969d8dcdafdb8d4b84907d5c89802437927b1f79973b621',
            ],
            'confirmations' => 1,
            'confirmed' => true,
            'counterpartyTx' => [
                'asset' => 'WALMART',
                'destinations' => [
                    0 => '15fx1Gqe4KodZvyzN6VUSkEmhCssrM1yD7',
                ],
                'dustSize' => 5.4299999999999998E-5,
                'dustSizeSat' => 5430,
                'quantity' => 5,
                'quantitySat' => 500000000,
                'sources' => [
                    0 => '1KXJWSkm2CF6xMqbqNRJwxp6XxsUCjouZM',
                ],
                'type' => 'send',
                'validated' => true,
            ],
            'destinations' => [
                0 => '15fx1Gqe4KodZvyzN6VUSkEmhCssrM1yD7',
            ],
            'event' => 'receive',
            'network' => 'counterparty',
            'notifiedAddress' => '15fx1Gqe4KodZvyzN6VUSkEmhCssrM1yD7',
            'notifiedAddressId' => '21aa819e-f222-4b49-9c94-fc7fafab833e',
            'quantity' => 5,
            'quantitySat' => 500000000,
            'sources' => [
                0 => '1KXJWSkm2CF6xMqbqNRJwxp6XxsUCjouZM',
            ],
            'transactionTime' => '2016-08-31T17:41:59+0000',
            'txid' => '13277080111fdd1d6969d8dcdafdb8d4b84907d5c89802437927b1f79973b621',
        ];

        $out = array_merge($template, $notification_overrides);

        return $this->buildNotification($out);
    }

    // ------------------------------------------------------------------------

    public function buildRequest($notification) {
        return $this->createJsonRequest('POST', config('xchainqueue.receivePath'), $notification);
    }

    
    public function buildNotification($payload_data) {
        $notification = [
            'payload' => json_encode($payload_data)
        ];

        return $notification;
    }

    public function createJsonRequest($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null) {
        // convert a POST to json
        if ($parameters AND $method == 'POST' OR $method == 'PATCH' OR $method == 'PUT') {
            $content = json_encode($parameters, JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT);
            $server['CONTENT_TYPE'] = 'application/json';
            $parameters = [];
        }

        // always want JSON
        $server['HTTP_ACCEPT'] = 'application/json';

        return Request::create($uri, $method, $parameters, $cookies, $files, $server, $content);
    }

    public function receiveRequest($request) {
        $kernel = app('Illuminate\Contracts\Http\Kernel');
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        return $response;
    }
}
