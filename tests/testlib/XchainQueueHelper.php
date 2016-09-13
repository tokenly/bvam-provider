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

        $out = array_replace_recursive($template, $notification_overrides);

        return $this->buildNotification($out);
    }

    public function buildConfirmedIssuanceNotification($notification_overrides=[], $bvam_hash=null) {
        $notification_overrides['confirmed']     = true;
        $notification_overrides['confirmations'] = 2;
        return $this->buildIssuanceNotification($notification_overrides, $bvam_hash);
    }

    public function buildIssuanceNotification($notification_overrides=[], $bvam_hash=null) {
        $template = [
            'asset' => 'CATEGORYID',
            'bitcoinTx' => [
                'txid' => '1111111122222222111111112222222211111111222222221111111122222222',
                'fees' => 0.00014575999999999999,
                'feesSat' => 14576,
            ],
            'confirmationTime' => '',
            'confirmations' => 0,
            'confirmed' => false,
            'counterpartyTx' => [
                'asset' => 'CATEGORYID',
                'call_date' => 0,
                'call_price' => 0,
                'callable' => false,
                'description' => 'http://bvam-provider.dev/{TEMPLATE}.json',
                'destinations' => [
                    0 => '1AuTJDwH6xNqxRLEjPB7m86dgmerYVQ5G1',
                ],
                'divisible' => false,
                'dustSize' => 0,
                'dustSizeSat' => 0,
                'quantity' => 1,
                'quantitySat' => 100000000,
                'sources' => [
                    0 => '1AuTJDwH6xNqxRLEjPB7m86dgmerYVQ5G1',
                ],
                'type' => 'issuance',
            ],
            'destinations' => [
                0 => '1AuTJDwH6xNqxRLEjPB7m86dgmerYVQ5G1',
            ],
            'event' => 'issuance',
            'network' => 'counterparty',
            'notifiedAddress' => '1AuTJDwH6xNqxRLEjPB7m86dgmerYVQ5G1',
            'notifiedAddressId' => '41850433-6290-4392-98d1-ef427974d521',
            'quantity' => 1,
            'quantitySat' => 100000000,
            'sources' => [
                0 => '1AuTJDwH6xNqxRLEjPB7m86dgmerYVQ5G1',
            ],
            'transactionFingerprint' => '8ccf0f98efd58839fc0318c6e1bd72e5db7e7a02725cfc9e7f2b7deabd25da62',
            'transactionTime' => '2016-09-03T13:08:19+0000',
            'txid' => '1111111122222222111111112222222211111111222222221111111122222222',
        ];

        if ($bvam_hash === null) { $bvam_hash = 'template'; }
        array_set($template, 'counterpartyTx.description', str_replace('{TEMPLATE}', $bvam_hash, array_get($template, 'counterpartyTx.description')));

        // set asset
        if (isset($notification_overrides['asset']) AND (!isset($notification_overrides['counterpartyTx']) OR !isset($notification_overrides['counterpartyTx']['asset']))) {
            if (isset($notification_overrides['counterpartyTx'])) { $notification_overrides['counterpartyTx'] = []; }
            $notification_overrides['counterpartyTx']['asset'] = $notification_overrides['asset'];
        }

        $out = array_replace_recursive($template, $notification_overrides);

        return $this->buildNotification($out);
    }

    // ------------------------------------------------------------------------
    
    public function buildConfirmedBroadcastNotification($notification_overrides=[], $broadcast_message=null) {
        $notification_overrides['confirmed']     = true;
        $notification_overrides['confirmations'] = 2;
        return $this->buildBroadcastNotification($notification_overrides, $broadcast_message);
    }

    public function buildBroadcastNotification($notification_overrides=[], $broadcast_message=null) {
        $template = [
            'event' => 'broadcast',
            'network' => 'counterparty',
            'asset' => NULL,
            'quantity' => 0,
            'quantitySat' => 0,
            'sources' => [
                0 => '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j',
            ],
            'destinations' => [],
            'notificationId' => 'd2dbb81c-a178-4b32-97f6-4def24dfdfd7',
            'txid' => '0000000000000000000000000000000000000000000000000000000000003333',
            'transactionTime' => '2016-09-07T15:55:05+0000',
            'confirmed' => false,
            'confirmations' => 0,
            'confirmationTime' => '',
            'notifiedMonitorId' => '8de89a79-bc2f-40e9-a991-b9a511e16485',
            'bitcoinTx' => [
                'hex' => '0100000001b78a8302b19b96946eadd07639eb7cba5912b0289e06651b43397eafaa5c2263000000006a47304402202bcb838d5cde360a441a3a2078c850da7d5beab07b59d0fe9c65a76cc2773ee90220243dd885f5da5d498e42f9ab9168f4ab4ee7059f2570ff98df116a13c55978fc012102bf6dd08324d2ba2dde233bd2e0679e2b9566569832a5df95e66067205cc37cfdffffffff020000000000000000476a453278869e7fd274e341da1146748d71feec55a24cac50559905938bd422261e6141e6511a4dfc44ed7def29b4ca07116be1a0bde2c0222ccb08e246ceba787d06d44f58d58d8d610100000000001976a914fa6bfacfa3fc9a6a1998ca6a88d26d168aff71f888ac00000000',
                'txid' => '0000000000000000000000000000000000000000000000000000000000003333',
                'confirmations' => 0,
                'fees' => 0.00013490999999999999,
                'feesSat' => 13491,
            ],
            'transactionFingerprint' => 'b30b789a222a809d8626157184557e9a72a84d175935983cd6bb9b28a0a657bd',
            'counterpartyTx' => [
                'type' => 'broadcast',
                'sources' => [
                    0 => '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j',
                ],
                'source' => '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j',
                'timestamp' => 1473158100,
                'value' => -1,
                'fee_fraction' => 0,
                'message' => 'Test Broadcast Message',
                'destinations' => [],
                'quantity' => 0,
                'quantitySat' => 0,
                'dustSize' => 0,
                'dustSizeSat' => 0,
            ],
        ];


        if ($broadcast_message === null) { $broadcast_message = 'template'; }
        array_set($template, 'counterpartyTx.message', $broadcast_message);

        $out = array_replace_recursive($template, $notification_overrides);

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
