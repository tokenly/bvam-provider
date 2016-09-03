<?php

use App\Handlers\Bvam\Facade\BvamUtil;
use App\Models\Bvam as BvamModel;
use App\Models\BvamCategory;
use \PHPUnit_Framework_Assert as PHPUnit;

class BvamHandlerTest extends TestCase
{

    protected $use_database = true;

    public function testConfirmBvam()
    {
        $bvam_helper = app('BvamHelper');
        $api = app('APITestHelper');

        $bvam1 = $bvam_helper->newBvam();
        $bvam2 = $bvam_helper->newBvam(['asset' => 'MYTOKEN2', 'name'  => 'My Token Two']);
        $bvam3 = $bvam_helper->newBvam(['asset' => 'MYTOKEN3', 'name'  => 'My Token Three']);

        // confirm BVAM for 2 of the tokens
        $txid1 = '0000000000000000000000000000000000000000000000000000000000000111';
        $txid2 = '0000000000000000000000000000000000000000000000000000000000000222';
        $unconfirmed_bvam = BvamUtil::markBvamAsUnconfirmed($bvam1['hash'], 'MYTOKEN', $txid1);

        // now get the list again with the 2 items
        PHPUnit::assertEquals('MYTOKEN', $unconfirmed_bvam['asset']);
        PHPUnit::assertEquals(BvamModel::STATUS_UNCONFIRMED, $unconfirmed_bvam['status']);
        PHPUnit::assertEquals($txid1, $unconfirmed_bvam['txid']);

        // try a mismatched token
        $confirmed_bvam = BvamUtil::confirmBvam($bvam2['hash'], 'BADTOKENNAME', $txid1, 1);
        PHPUnit::assertEquals(false, $confirmed_bvam);

        // confirm both fully
        $confirmed_bvam1 = BvamUtil::confirmBvam($bvam1['hash'], 'MYTOKEN', $txid1, 1);
        $confirmed_bvam2 = BvamUtil::confirmBvam($bvam2['hash'], 'MYTOKEN2', $txid2, 1);

        // now get the list again with the 2 items
        PHPUnit::assertEquals('MYTOKEN', $confirmed_bvam1['asset']);
        PHPUnit::assertEquals(BvamModel::STATUS_CONFIRMED, $confirmed_bvam1['status']);
        PHPUnit::assertEquals($txid1, $confirmed_bvam1['txid']);
        PHPUnit::assertEquals('MYTOKEN2', $confirmed_bvam2['asset']);
        PHPUnit::assertEquals(BvamModel::STATUS_CONFIRMED, $confirmed_bvam2['status']);
        PHPUnit::assertEquals($txid2, $confirmed_bvam2['txid']);
    }

    public function testConfirmBvamInvalidatesOlderBvamsForAsset() {
        $bvam_helper = app('BvamHelper');
        $api = app('APITestHelper');
        $txid1 = '0000000000000000000000000000000000000000000000000000000000000111';
        $txid2 = '0000000000000000000000000000000000000000000000000000000000000222';

        $bvam1 = $bvam_helper->newBvam();

        // confirm the bvam
        $bvam1 = BvamUtil::confirmBvam($bvam1['hash'], 'MYTOKEN', $txid1, 1);

        // now confirm a new hash for the same token
        $bvam2 = $bvam_helper->newBvam(['name' => 'Updated Token Name']);
        $bvam2 = BvamUtil::confirmBvam($bvam2['hash'], 'MYTOKEN', $txid2, 1);

        // get the list of active bvams - only bvam2 should be active
        $repository = app('App\Repositories\BvamRepository');
        $all_bvams = $repository->findAll();
        PHPUnit::assertCount(2, $all_bvams);
        PHPUnit::assertEquals($bvam1['uuid'], $all_bvams[0]['uuid']);
        PHPUnit::assertEquals(BvamModel::STATUS_REPLACED, $all_bvams[0]['status']);
        PHPUnit::assertEquals($bvam2['uuid'], $all_bvams[1]['uuid']);
        PHPUnit::assertEquals(BvamModel::STATUS_CONFIRMED, $all_bvams[1]['status']);


    }


    public function testConfirmBvamCategory()
    {
        $bvam_category_helper = app('BvamCategoryHelper');
        $api = app('APITestHelper');

        $bvam_category1 = $bvam_category_helper->newBvamCategory();
        $bvam_category2 = $bvam_category_helper->newBvamCategory(['category_id' => 'cat0002', 'title'  => 'My Token Two']);
        $bvam_category3 = $bvam_category_helper->newBvamCategory(['category_id' => 'cat0003', 'title'  => 'My Token Three']);

        // confirm BVAM for 2 of the tokens
        $txid1 = '0000000000000000000000000000000000000000000000000000000000000111';
        $txid2 = '0000000000000000000000000000000000000000000000000000000000000222';
        $unconfirmed_bvam = BvamUtil::markBvamCategoryAsUnconfirmed($bvam_category1['hash'], 'BVAM Test Category One 201609a', $txid1);

        // now get the list again with the 2 items
        PHPUnit::assertEquals('BVAM Test Category One 201609a', $unconfirmed_bvam['category_id']);
        PHPUnit::assertEquals(BvamCategory::STATUS_UNCONFIRMED, $unconfirmed_bvam['status']);
        PHPUnit::assertEquals($txid1, $unconfirmed_bvam['txid']);

        // try a mismatched category
        $confirmed_bvam = BvamUtil::confirmBvamCategory($bvam_category2['hash'], 'BADCATEGORYID', $txid1, 1);
        PHPUnit::assertEquals(false, $confirmed_bvam);

        // confirm both fully
        $confirmed_bvam1 = BvamUtil::confirmBvamCategory($bvam_category1['hash'], 'BVAM Test Category One 201609a', $txid1, 1);
        $confirmed_bvam2 = BvamUtil::confirmBvamCategory($bvam_category2['hash'], 'cat0002', $txid2, 1);

        // now get the list again with the 2 items
        PHPUnit::assertEquals('BVAM Test Category One 201609a', $confirmed_bvam1['category_id']);
        PHPUnit::assertEquals(BvamCategory::STATUS_CONFIRMED, $confirmed_bvam1['status']);
        PHPUnit::assertEquals($txid1, $confirmed_bvam1['txid']);
        PHPUnit::assertEquals('cat0002', $confirmed_bvam2['category_id']);
        PHPUnit::assertEquals(BvamCategory::STATUS_CONFIRMED, $confirmed_bvam2['status']);
        PHPUnit::assertEquals($txid2, $confirmed_bvam2['txid']);
    }

    public function testConfirmBvamCategoryInvalidatesOlderBvamCategories() {
        $bvam_category_helper = app('BvamCategoryHelper');
        $api = app('APITestHelper');
        $txid1 = '0000000000000000000000000000000000000000000000000000000000000111';
        $txid2 = '0000000000000000000000000000000000000000000000000000000000000222';

        $bvam_category1 = $bvam_category_helper->newBvamCategory();

        // confirm the bvam
        $bvam_category1 = BvamUtil::confirmBvamCategory($bvam_category1['hash'], 'BVAM Test Category One 201609a', $txid1, 1);

        // now confirm a new version and hash for the same category ID
        $bvam_category2 = $bvam_category_helper->newBvamCategory(['title' => 'Updated Category Name', 'version' => '1.0.1']);
        $bvam_category2 = BvamUtil::confirmBvamCategory($bvam_category2['hash'], 'BVAM Test Category One 201609a', $txid2, 1);

        // get the list of active bvams - only bvam2 should be active
        $repository = app('App\Repositories\BvamCategoryRepository');
        $all_bvams = $repository->findAll();
        PHPUnit::assertCount(2, $all_bvams);
        PHPUnit::assertEquals($bvam_category1['uuid'], $all_bvams[0]['uuid']);
        PHPUnit::assertEquals(BvamCategory::STATUS_REPLACED, $all_bvams[0]['status']);
        PHPUnit::assertEquals($bvam_category2['uuid'], $all_bvams[1]['uuid']);
        PHPUnit::assertEquals(BvamCategory::STATUS_CONFIRMED, $all_bvams[1]['status']);


    }

}
