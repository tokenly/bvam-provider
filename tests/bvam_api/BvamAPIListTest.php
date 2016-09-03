<?php

use App\Handlers\Bvam\Facade\BvamUtil;
use \PHPUnit_Framework_Assert as PHPUnit;

class BvamAPITest extends TestCase
{

    protected $use_database = true;

    public function testGetAllBvam()
    {
        $bvam_helper = app('BvamHelper');
        $api = app('APITestHelper');

        $bvam1 = $bvam_helper->newBvam();
        $bvam2 = $bvam_helper->newBvam(['asset' => 'MYTOKEN2', 'name'  => 'My Token Two']);
        $bvam3 = $bvam_helper->newBvam(['asset' => 'MYTOKEN3', 'name'  => 'My Token Three']);


        // nothing updated yet
        $response = $api->callAPIAndReturnJSONContent('GET', 'bvam/all', [], 200);
        PHPUnit::assertEquals(0, $response['count']);
        PHPUnit::assertEquals([], $response['items']);


        // confirm BVAM for 2 of the tokens
        $txid1 = '0000000000000000000000000000000000000000000000000000000000000111';
        $txid2 = '0000000000000000000000000000000000000000000000000000000000000111';
        BvamUtil::confirmBvam($bvam1['hash'], 'MYTOKEN', $txid1, 1);
        BvamUtil::confirmBvam($bvam2['hash'], 'MYTOKEN2', $txid2, 1);


        // now get the list again with the 2 items
        $response = $api->callAPIAndReturnJSONContent('GET', 'bvam/all', [], 200);
        PHPUnit::assertEquals(2, $response['count']);
        PHPUnit::assertCount(2, $response['items']);
        PHPUnit::assertEquals('MYTOKEN', $response['items'][0]['asset']);
        PHPUnit::assertEquals($txid1, $response['items'][0]['txid']);
        PHPUnit::assertEquals('MYTOKEN2', $response['items'][1]['asset']);
        PHPUnit::assertEquals($txid2, $response['items'][1]['txid']);
    }

}
