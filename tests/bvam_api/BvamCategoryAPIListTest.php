<?php

use App\Handlers\Bvam\Facade\BvamUtil;
use App\Models\BvamCategory;
use \PHPUnit_Framework_Assert as PHPUnit;

class BvamCategoryAPIListTest extends TestCase
{

    protected $use_database = true;

    public function testGetAllCategoryBvam()
    {
        $bvam_category_helper = app('BvamCategoryHelper');
        $api = app('APITestHelper');

        $bvam_cat1 = $bvam_category_helper->newBvamCategory();
        $bvam_cat2 = $bvam_category_helper->newBvamCategory(['category_id'  => 'cat0002', 'title' => 'Category 2']);
        $bvam_cat3 = $bvam_category_helper->newBvamCategory(['category_id'  => 'cat0003', 'title' => 'Category 3']);


        // nothing updated yet
        $response = $api->callAPIAndReturnJSONContent('GET', 'category/all', [], 200);
        PHPUnit::assertEquals(0, $response['count']);
        PHPUnit::assertEquals([], $response['items']);

        $response = $api->callAPIAndReturnJSONContent('GET', 'category/all', ['status' => 1], 200);
        PHPUnit::assertEquals(0, $response['count']);

        // confirm 2 of the BVAM Categories
        $txid1 = '0000000000000000000000000000000000000000000000000000000000000111';
        $txid2 = '0000000000000000000000000000000000000000000000000000000000000111';
        BvamUtil::confirmBvamCategory($bvam_cat1['hash'], 'BVAM Test Category One 201609a', $txid1, 1);
        BvamUtil::confirmBvamCategory($bvam_cat2['hash'], 'cat0002', $txid2, 1);


        // now get the list again with the 2 items
        $response = $api->callAPIAndReturnJSONContent('GET', 'category/all', [], 200);
        PHPUnit::assertEquals(2, $response['count']);
        PHPUnit::assertCount(2, $response['items']);
        PHPUnit::assertEquals('BVAM Test Category One 201609a', $response['items'][0]['categoryId']);
        PHPUnit::assertEquals('Test Category One', $response['items'][0]['title']);
        PHPUnit::assertEquals($txid1, $response['items'][0]['txid']);
        PHPUnit::assertEquals('cat0002', $response['items'][1]['categoryId']);
        PHPUnit::assertEquals('Category 2', $response['items'][1]['title']);
        PHPUnit::assertEquals($txid2, $response['items'][1]['txid']);
    }

    public function testWithStatusVarGetAllCategoryBvam()
    {
        $bvam_category_helper = app('BvamCategoryHelper');
        $api = app('APITestHelper');

        $bvam_cat1 = $bvam_category_helper->newBvamCategory();
        $bvam_cat2 = $bvam_category_helper->newBvamCategory(['category_id'  => 'cat0002', 'title' => 'Category 2']);
        $bvam_cat3 = $bvam_category_helper->newBvamCategory(['category_id'  => 'cat0003', 'title' => 'Category 3']);

        $response = $api->callAPIAndReturnJSONContent('GET', 'category/all', ['status' => BvamCategory::STATUS_DRAFT], 200);
        PHPUnit::assertEquals(0, $response['count']);
        $response = $api->callAPIAndReturnJSONContent('GET', 'category/all', ['hash' => $bvam_cat1['hash']], 200);
        PHPUnit::assertEquals(0, $response['count']);
    }

}
