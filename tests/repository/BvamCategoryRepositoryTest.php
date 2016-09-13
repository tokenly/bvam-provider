<?php

use App\Models\BvamCategory;
use \PHPUnit_Framework_Assert as PHPUnit;

/*
* BvamCategoryRepositoryTest
*/
class BvamCategoryRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testBvamCategoryRepository()
    {
        $helper = $this->createRepositoryTestHelper();

        $helper->cleanup()->testLoad();
    }


    public function testFindBvamCategoryByHash()
    {
        $bvam_category1 = app('BvamCategoryHelper')->newBvamCategory();
        $bvam_category2 = app('BvamCategoryHelper')->newBvamCategory(['category_id' => 'cat0002',],['status' => BvamCategory::STATUS_CONFIRMED]);
        $repository = app('App\Repositories\BvamCategoryRepository');

        $bvam_category = $repository->findByHash($bvam_category2['hash']);
        PHPUnit::assertEquals($bvam_category2['uuid'], $bvam_category['uuid']);
    }

    

    public function testMarkActiveForCategoryIdAsReplaced()
    {
        $bvam_category1 = app('BvamCategoryHelper')->newBvamCategory();
        $bvam_category2 = app('BvamCategoryHelper')->newBvamCategory(['version' => '1.0.1'], ['status' => BvamCategory::STATUS_CONFIRMED]);
        $bvam_category3 = app('BvamCategoryHelper')->newBvamCategory(['version' => '1.0.2'], ['status' => BvamCategory::STATUS_UNCONFIRMED]);
        $bvam_category4 = app('BvamCategoryHelper')->newBvamCategory(['category_id' => 'cat002'], ['status' => BvamCategory::STATUS_CONFIRMED]);

        $repository = app('App\Repositories\BvamCategoryRepository');
        $repository->markOtherActiveBvamCategoriesForCategoryIdAsReplaced('BVAM Test Category One 201609a', $bvam_category1);

        // reload
        $bvam_category1 = $repository->findById($bvam_category1['id']);
        $bvam_category2 = $repository->findById($bvam_category2['id']);
        $bvam_category3 = $repository->findById($bvam_category3['id']);
        $bvam_category4 = $repository->findById($bvam_category4['id']);

        // check statuses
        PHPUnit::assertEquals(BvamCategory::STATUS_DRAFT, $bvam_category1['status']);
        PHPUnit::assertEquals(BvamCategory::STATUS_REPLACED, $bvam_category2['status']);
        PHPUnit::assertEquals(BvamCategory::STATUS_REPLACED, $bvam_category3['status']);
        PHPUnit::assertEquals(BvamCategory::STATUS_CONFIRMED, $bvam_category4['status']);
    }

    public function testMarkOtherActiveCategoriesForCategoryIdAsReplaced()
    {
        $bvam_category1 = app('BvamCategoryHelper')->newBvamCategory();
        $bvam_category2 = app('BvamCategoryHelper')->newBvamCategory(['version' => '1.0.1'], ['status' => BvamCategory::STATUS_CONFIRMED]);
        $bvam_category3 = app('BvamCategoryHelper')->newBvamCategory(['version' => '1.0.2'], ['status' => BvamCategory::STATUS_UNCONFIRMED]);
        $bvam_category4 = app('BvamCategoryHelper')->newBvamCategory(['category_id' => 'cat002'], ['status' => BvamCategory::STATUS_CONFIRMED]);

        $repository = app('App\Repositories\BvamCategoryRepository');
        $repository->markOtherActiveBvamCategoriesForCategoryIdAsReplaced('BVAM Test Category One 201609a', $bvam_category2);

        // reload
        $bvam_category1 = $repository->findById($bvam_category1['id']);
        $bvam_category2 = $repository->findById($bvam_category2['id']);
        $bvam_category3 = $repository->findById($bvam_category3['id']);
        $bvam_category4 = $repository->findById($bvam_category4['id']);

        // check statuses
        PHPUnit::assertEquals(BvamCategory::STATUS_DRAFT, $bvam_category1['status']);
        PHPUnit::assertEquals(BvamCategory::STATUS_CONFIRMED, $bvam_category2['status']);
        PHPUnit::assertEquals(BvamCategory::STATUS_REPLACED, $bvam_category3['status']);
        PHPUnit::assertEquals(BvamCategory::STATUS_CONFIRMED, $bvam_category4['status']);
    }

    public function testFindConfirmedByCategoryId()
    {
        $bvam_category1 = app('BvamCategoryHelper')->newBvamCategory();
        $bvam_category2 = app('BvamCategoryHelper')->newBvamCategory(['version' => '1.0.1'], ['status' => BvamCategory::STATUS_CONFIRMED]);
        $bvam_category3 = app('BvamCategoryHelper')->newBvamCategory(['version' => '1.0.2'], ['status' => BvamCategory::STATUS_UNCONFIRMED]);
        $bvam_category4 = app('BvamCategoryHelper')->newBvamCategory(['category_id' => 'cat002'], ['status' => BvamCategory::STATUS_CONFIRMED]);

        $repository = app('App\Repositories\BvamCategoryRepository');
        $found_bvam_category = $repository->findConfirmedByCategoryId('BVAM Test Category One 201609a');
        PHPUnit::assertEquals($bvam_category2['id'], $found_bvam_category['id']);
    }


    protected function createRepositoryTestHelper() {
        $create_model_fn = function() {
            return app('BvamCategoryHelper')->newBvamCategory();
        };

        $helper = new RepositoryTestHelper($create_model_fn, $this->app->make('App\Repositories\BvamCategoryRepository'));
        return $helper;
    }

}
