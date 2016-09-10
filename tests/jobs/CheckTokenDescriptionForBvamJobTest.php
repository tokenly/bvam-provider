<?php

use App\Handlers\Bvam\Facade\BvamUtil;
use App\Jobs\CheckTokenDescriptionForBvam;
use App\Models\Bvam;
use App\Models\BvamCategory;
use \PHPUnit_Framework_Assert as PHPUnit;

class CheckTokenDescriptionForBvamJobTest extends TestCase
{

    protected $use_database = true;

    public function testCheckTokenDescriptionForBvamJob()
    {
        // create a bvam
        $bvam1 = app('BvamHelper')->newBvam(['asset' => 'NEWCOIN',], []);
        $actual_hash = $bvam1['hash'];

        $mock_cache = Mockery::mock('App\Handlers\AssetInfo\AssetInfoCache');
        $mock_cache->shouldReceive('getInfo')->with('NEWCOIN')->once()->andReturn([
            'locked'      => false,
            'description' => $actual_hash,
            'divisible'   => true,
            'asset'       => 'NEWCOIN',
            'owner'       => '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j',
            'issuer'      => '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j',
            'supply'      => 10000000000000,
            'status'      => 'valid',
            'tx_hash'     => '1111111111111111111111111111111111111111111111111111111111110001',
            'block_index' => 429000,
        ]);
        app()->instance('App\Handlers\AssetInfo\AssetInfoCache', $mock_cache);

        // run the job
        $job = new CheckTokenDescriptionForBvam('NEWCOIN');
        $job->handle();

        $repository = app('App\Repositories\BvamRepository');
        $bvam_model = $repository->findByHash($actual_hash);
        PHPUnit::assertNotEmpty($bvam_model);
        PHPUnit::assertEquals($bvam1['uuid'], $bvam_model['uuid']);
        PHPUnit::assertEquals(Bvam::STATUS_CONFIRMED, $bvam_model['status']);

        Mockery::close();
    }

}
