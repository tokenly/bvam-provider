<?php

use Illuminate\Support\Facades\Artisan;
use \PHPUnit_Framework_Assert as PHPUnit;

class ReparseTokenDescriptionCommandTest extends TestCase
{

    protected $use_database = true;

    public function testCheckTokenDescriptionForBvamCommand()
    {

        $mock = Mockery::mock('Illuminate\Bus\Dispatcher');
        $firedEvents = [];
        $mock->shouldReceive('dispatchNow')->andReturnUsing(function ($called) use (&$firedEvents) {
            $firedEvents[] = $called;
        });
        app()->instance('Illuminate\Bus\Dispatcher', $mock);

        // call the console command
         // {--clear-cache} {asset}
        $exitCode = Artisan::call('bvam:reparse-token', [
            'asset' => 'MYTOKEN',
        ]);

        PHPUnit::assertCount(1, $firedEvents);
    }

    public function testCheckTokenDescriptionWithClearCacheForBvamCommand()
    {

        $mock = Mockery::mock('Illuminate\Bus\Dispatcher');
        $firedEvents = [];
        $mock->shouldReceive('dispatchNow')->andReturnUsing(function ($called) use (&$firedEvents) {
            $firedEvents[] = $called;
        });
        app()->instance('Illuminate\Bus\Dispatcher', $mock);

        $mock_cache = Mockery::mock('App\Handlers\AssetInfo\AssetInfoCache');
        $mock_cache->shouldReceive('forget')->with('MYTOKEN')->once();
        app()->instance('App\Handlers\AssetInfo\AssetInfoCache', $mock_cache);


        // call the console command
         // {--clear-cache} {asset}
        $exitCode = Artisan::call('bvam:reparse-token', [
            'asset'         => 'MYTOKEN',
            '--clear-cache' => true,
        ]);

        PHPUnit::assertCount(1, $firedEvents);
    }

}
