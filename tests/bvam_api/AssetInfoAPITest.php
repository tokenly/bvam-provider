<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class AssetInfoAPITest extends TestCase
{

    protected $use_database = true;

    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testGetBadSingleAsset()
    {
        $api = app('APITestHelper');
        $response = $api->callAPIAndReturnJSONContent('GET', 'asset/ABAD', [], 422);
        PHPUnit::assertContains('invalid', $response['message']);

    }

    public function testGetAssetInfo()
    {
        $mock_cache = app('AssetInfoCacheHelper')->mockAssetInfoCacheGetMultiple(['FOOCOIN']);

        $api = app('APITestHelper');
        $response = $api->callAPIAndReturnJSONContent('GET', 'asset/FOOCOIN', [], 200);
        PHPUnit::assertEquals('FOOCOIN', $response['asset']);
        PHPUnit::assertEquals('My foocoin', $response['assetInfo']['description']);
        PHPUnit::assertEquals('My foocoin', $response['metadata']['description']);

        Mockery::close();
    }

    public function testGetMultipleAssetInfo()
    {
        $mock_cache = app('AssetInfoCacheHelper')->mockAssetInfoCacheGetMultiple(['FOOCOIN','BARCOIN']);

        $api = app('APITestHelper');
        $response = $api->callAPIAndReturnJSONContent('GET', 'assets', ['assets' => 'FOOCOIN,BARCOIN'], 200);
        PHPUnit::assertCount(2, $response);
        PHPUnit::assertEquals('FOOCOIN', $response[0]['asset']);
        PHPUnit::assertEquals('My foocoin', $response[0]['assetInfo']['description']);
        PHPUnit::assertEquals('My foocoin', $response[0]['metadata']['description']);
        PHPUnit::assertEquals('BARCOIN', $response[1]['asset']);
        PHPUnit::assertEquals('My barcoin', $response[1]['assetInfo']['description']);
        PHPUnit::assertEquals('My barcoin', $response[1]['metadata']['description']);

        Mockery::close();
    }


}
