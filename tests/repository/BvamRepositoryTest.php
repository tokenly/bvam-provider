<?php

use App\Models\Bvam;
use \PHPUnit_Framework_Assert as PHPUnit;

/*
* BvamRepositoryTest
*/
class BvamRepositoryTest extends TestCase {

    protected $use_database = true;

    public function testBvamRepository()
    {
        $helper = $this->createRepositoryTestHelper();

        $helper->cleanup()->testLoad();
    }

    public function testFindActiveBvamByAssetName()
    {
        $bvam1 = app('BvamHelper')->newBvam();
        $bvam2 = app('BvamHelper')->newBvam(['asset' => 'MYTOKEN2',],['status' => Bvam::STATUS_CONFIRMED]);
        $repository = app('App\Repositories\BvamRepository');

        $bvam = $repository->findByActiveBvamByAsset('MYTOKEN2');
        PHPUnit::assertEquals($bvam2['uuid'], $bvam['uuid']);
    }

    public function testFindBvamByHash()
    {
        $bvam1 = app('BvamHelper')->newBvam();
        $bvam2 = app('BvamHelper')->newBvam(['asset' => 'MYTOKEN2',],['status' => Bvam::STATUS_CONFIRMED]);
        $repository = app('App\Repositories\BvamRepository');

        $bvam = $repository->findByHash($bvam2['hash']);
        PHPUnit::assertEquals($bvam2['uuid'], $bvam['uuid']);
    }

    public function testMarkAsReplacedByAsset()
    {
        $bvam1 = app('BvamHelper')->newBvam();
        $bvam2 = app('BvamHelper')->newBvam(['name' => 'Update 2'], ['status' => Bvam::STATUS_CONFIRMED]);
        $bvam3 = app('BvamHelper')->newBvam(['name' => 'Update 3'], ['status' => Bvam::STATUS_UNCONFIRMED]);
        $bvam4 = app('BvamHelper')->newBvam(['asset' => 'MYTOKEN2'], ['status' => Bvam::STATUS_CONFIRMED]);
        $repository = app('App\Repositories\BvamRepository');

        $repository->markActiveBvamsForAssetAsReplaced('MYTOKEN');

        // reload
        $bvam1 = $repository->findById($bvam1['id']);
        $bvam2 = $repository->findById($bvam2['id']);
        $bvam3 = $repository->findById($bvam3['id']);
        $bvam4 = $repository->findById($bvam4['id']);

        // check statuses
        PHPUnit::assertEquals(Bvam::STATUS_DRAFT, $bvam1['status']);
        PHPUnit::assertEquals(Bvam::STATUS_REPLACED, $bvam2['status']);
        PHPUnit::assertEquals(Bvam::STATUS_REPLACED, $bvam3['status']);
        PHPUnit::assertEquals(Bvam::STATUS_CONFIRMED, $bvam4['status']);
    }

    protected function createRepositoryTestHelper() {
        $create_model_fn = function() {
            return app('BvamHelper')->newBvam();
        };

        $helper = new RepositoryTestHelper($create_model_fn, app('App\Repositories\BvamRepository'));
        return $helper;
    }

}
