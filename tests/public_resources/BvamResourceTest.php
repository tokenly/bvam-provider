<?php

use App\Handlers\Bvam\Facade\BvamUtil;
use \PHPUnit_Framework_Assert as PHPUnit;

class BvamResourceTest extends TestCase
{

    protected $use_database = true;

    public function testGetBvamResourceByHash()
    {
        $bvam_helper = app('BvamHelper');
        $api = app('APITestHelper');
        $api->url_base = '';

        $bvam1 = $bvam_helper->newBvam();
        $bvam2 = $bvam_helper->newBvam(['asset' => 'MYTOKEN2', 'name'  => 'My Token Two']);
        $bvam3 = $bvam_helper->newBvam(['asset' => 'MYTOKEN3', 'name'  => 'My Token Three']);


        // nothing updated yet
        $response = $api->callAPIAndReturnJSONContent('GET', 'foo', [], 404);


        // confirm BVAM for 2 of the tokens
        PHPUnit::assertNotEmpty(BvamUtil::confirmBvam($bvam1['hash'], 'MYTOKEN', '0000000000000000000000000000000000000000000000000000000000000111', 1));
        PHPUnit::assertNotEmpty(BvamUtil::confirmBvam($bvam2['hash'], 'MYTOKEN2', '0000000000000000000000000000000000000000000000000000000000000222', 1));

        // now get the resources again
        $response = $api->callAPIAndReturnJSONContent('GET', $bvam1['hash'].'.json', [], 200);
        PHPUnit::assertEquals('MYTOKEN', $response['asset']);

        $response = $api->callAPIAndReturnJSONContent('GET', $bvam2['hash'].'.json', [], 200);
        PHPUnit::assertEquals('MYTOKEN2', $response['asset']);

        $response = $api->callAPIAndReturnJSONContent('GET', $bvam3['hash'].'.json', [], 404);
    }

    public function testGetBvamCategoryResourceByHash()
    {
        $bvam_category_helper = app('BvamCategoryHelper');
        $api = app('APITestHelper');
        $api->url_base = '';

        $bvam_cat1 = $bvam_category_helper->newBvamCategory();
        $bvam_cat2 = $bvam_category_helper->newBvamCategory(['category_id'  => 'cat0002', 'title' => 'Category 2']);
        $bvam_cat3 = $bvam_category_helper->newBvamCategory(['category_id'  => 'cat0003', 'title' => 'Category 3']);

        // not confirmed yet
        $response = $api->callAPIAndReturnJSONContent('GET', $bvam_cat1['hash'].'.json', [], 404);


        // confirm BVAM for 2 of the categories
        PHPUnit::assertNotEmpty(BvamUtil::confirmBvamCategory($bvam_cat1['hash'], 'BVAM Test Category One 201609a', '0000000000000000000000000000000000000000000000000000000000000111', 1));
        PHPUnit::assertNotEmpty(BvamUtil::confirmBvamCategory($bvam_cat2['hash'], 'cat0002', '0000000000000000000000000000000000000000000000000000000000000222', 1));

        // now get the resources again
        $response = $api->callAPIAndReturnJSONContent('GET', $bvam_cat1['hash'].'.json', [], 200);
        PHPUnit::assertEquals('BVAM Test Category One 201609a', $response['category_id']);

        $response = $api->callAPIAndReturnJSONContent('GET', $bvam_cat2['hash'].'.json', [], 200);
        PHPUnit::assertEquals('cat0002', $response['category_id']);

        $response = $api->callAPIAndReturnJSONContent('GET', $bvam_cat3['hash'].'.json', [], 404);
    }

}
