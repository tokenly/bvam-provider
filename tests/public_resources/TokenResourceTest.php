<?php

use App\Handlers\Bvam\Facade\BvamUtil;
use \PHPUnit_Framework_Assert as PHPUnit;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class TokenResourceTest extends TestCase
{

    protected $use_database = true;

    public function testGetMissingAssetName() {
        // mock xchain
        app('Tokenly\XChainClient\Mock\MockBuilder')->installXChainMockClient();

        $api = app('APITestHelper');
        $response = $api->callAPIAndReturnJSONContent('GET', 'asset/NOTFOUND', [], 404);
    }

    public function testGetValidatedTokenInfoByAssetName()
    {
        $mock_cache = app('AssetInfoCacheHelper')->mockAssetInfoCacheGetMultiple(['TOKENLY']);

        $bvam_helper = app('BvamHelper');
        $api = app('APITestHelper');

        $bvam1 = $bvam_helper->newBvam(['asset' => 'TOKENLY']);
        $bvam2 = $bvam_helper->newBvam(['asset' => 'MYTOKEN2', 'name'  => 'My Token Two']);


        // confirm BVAM
        PHPUnit::assertNotEmpty(BvamUtil::confirmBvam($bvam1['hash'], 'TOKENLY', '0000000000000000000000000000000000000000000000000000000000000111', 1));

        // get the public token data by name (unvalidated token)
        $response = $api->callAPIAndReturnJSONContent('GET', 'asset/TOKENLY', [], 200);
        PHPUnit::assertEquals('TOKENLY', $response['asset']);
        PHPUnit::assertEquals('T3g97ZF9LSRBFXsv1AGbgBAri126h.json', $response['filename']);
        PHPUnit::assertEquals('T3g97ZF9LSRBFXsv1AGbgBAri126h', $response['hash']);
        PHPUnit::assertEquals('http://bvam-provider.dev/T3g97ZF9LSRBFXsv1AGbgBAri126h.json', $response['uri']);
        PHPUnit::assertEquals('0000000000000000000000000000000000000000000000000000000000000111', $response['txid']);
        PHPUnit::assertEquals('TOKENLY', $response['assetInfo']['asset']);
        PHPUnit::assertEquals('TOKENLY', $response['metadata']['asset']);
        PHPUnit::assertTrue($response['validated']);
        PHPUnit::assertNotEmpty($response['bvamString']);
        // echo "\$response: ".json_encode($response, 192)."\n";

        Mockery::close();
    }

    public function testGetUnvalidatedTokenInfoByAssetName()
    {
        $mock_cache = app('AssetInfoCacheHelper')->mockAssetInfoCacheGetMultiple(['TOKENLY']);

        $bvam_helper = app('BvamHelper');
        $api = app('APITestHelper');


        // get the public token data by name (unvalidated token)
        $response = $api->callAPIAndReturnJSONContent('GET', 'asset/TOKENLY', [], 200);
        PHPUnit::assertEquals('TOKENLY', $response['asset']);
        PHPUnit::assertEquals(null, $response['filename']);
        PHPUnit::assertEquals(null, $response['hash']);
        PHPUnit::assertEquals(null, $response['uri']);
        PHPUnit::assertEquals(null, $response['txid']);
        PHPUnit::assertEquals('TOKENLY', $response['assetInfo']['asset']);
        PHPUnit::assertEquals('TOKENLY', $response['metadata']['asset']);
        PHPUnit::assertFalse($response['validated']);
        PHPUnit::assertEquals(null, $response['bvamString']);
        // echo "\$response: ".json_encode($response, 192)."\n";

    }


    public function testGetEnhancedTokenInfoByAssetName()
    {
        $mock_xchain_client = Mockery::mock('Tokenly\XChainClient\Client');
        $mock_xchain_client->shouldReceive('getAssets')->with(['NEWCOIN'])->once()->andReturn([[
            'asset'       => 'NEWCOIN',
            'divisible'   => true,
            'description' => 'tokenplace.foo/tokendata.json',
            'locked'      => false,
            'owner'       => '12717MBviQxttaBVhFGRP1LxD8X6CaW452',
            'issuer'      => '12717MBviQxttaBVhFGRP1LxD8X6CaW452',
            'supply'      => 10000000000000,
        ]]);
        app()->instance('Tokenly\XChainClient\Client', $mock_xchain_client);

        $mock = new MockHandler([
            new Response(200, [], app('EnhancedAssetInfoHelper')->getEnhancedAssetInfoAsJson()),
            new Response(200, [], app('EnhancedAssetInfoHelper')->samplePNGImageBinary()),
        ]);
        $handler = HandlerStack::create($mock);
        app()->bind('enhancedAssetInfo.guzzle', function($app) use ($handler) {
            return new GuzzleClient(['handler' => $handler]);
        });


        $api = app('APITestHelper');

        // get the public token data by name (unvalidated token)
        $response = $api->callAPIAndReturnJSONContent('GET', 'asset/NEWCOIN', [], 200);
        PHPUnit::assertEquals('NEWCOIN', $response['asset']);
        PHPUnit::assertEquals(null, $response['filename']);
        PHPUnit::assertEquals(null, $response['hash']);
        PHPUnit::assertEquals(null, $response['uri']);
        PHPUnit::assertEquals(null, $response['txid']);
        PHPUnit::assertEquals('NEWCOIN', $response['assetInfo']['asset']);
        PHPUnit::assertFalse($response['validated']);
        PHPUnit::assertEquals(null, $response['bvamString']);

        PHPUnit::assertEquals('NEWCOIN', $response['metadata']['asset']);
        PHPUnit::assertEquals('This is a long description of my token', $response['metadata']['description']);
        PHPUnit::assertEquals('http://tokensite.foo/', $response['metadata']['website']);
        PHPUnit::assertEquals('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAAAGUlEQVQokWNkMf7PQApgIkn1qIZRDUNKAwDmNgFWz6CTPQAAAABJRU5ErkJggg==', $response['metadata']['images'][0]['data']);

        // echo "\$response: ".json_encode($response, 192)."\n";

        Mockery::close();
    }

    public function testGetNumericAsset() {
        $mock_cache = app('AssetInfoCacheHelper')->mockAssetInfoCacheGetMultiple(['A8222555550000000000']);

        $api = app('APITestHelper');

        // get the public token data by name (unvalidated token)
        $response = $api->callAPIAndReturnJSONContent('GET', 'asset/A8222555550000000000', [], 200);
        PHPUnit::assertEquals('A8222555550000000000', $response['asset']);
        PHPUnit::assertEquals('A8222555550000000000', $response['metadata']['asset']);
        PHPUnit::assertEquals('My a8222555550000000000', $response['metadata']['description']);

        Mockery::close();
    }

    public function testBadNumericAsset() {
        // get the public token data by name (invalid asset)
        $api = app('APITestHelper');
        $response = $api->callAPIAndReturnJSONContent('GET', 'asset/A100', [], 422);

    }



}
