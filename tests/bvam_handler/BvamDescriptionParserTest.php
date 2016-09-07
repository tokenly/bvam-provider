<?php

use App\Handlers\Bvam\Facade\BvamUtil;
use \PHPUnit_Framework_Assert as PHPUnit;

class BvamDescriptionParserTest extends TestCase
{

    protected $use_database = true;

    public function testBvamDescriptionParser()
    {
        $info = BvamUtil::parseBvamIssuanceDescription('Just a text description');
        PHPUnit::assertEquals([
            'type'      => null,
            'bvam_hash' => null,
            'host'      => null,
            'uri'       => null,
        ], $info);

        $info = BvamUtil::parseBvamIssuanceDescription('http://foo');
        PHPUnit::assertEquals([
            'type'      => null,
            'bvam_hash' => null,
            'host'      => null,
            'uri'       => null,
        ], $info);

        $info = BvamUtil::parseBvamIssuanceDescription('http://foo.com/some/enhanced.json');
        PHPUnit::assertEquals([
            'type'      => 'enhanced',
            'bvam_hash' => null,
            'host'      => 'foo.com',
            'uri'       => 'http://foo.com/some/enhanced.json',
        ], $info);

        $info = BvamUtil::parseBvamIssuanceDescription('http://foo.com/S1234.json');
        PHPUnit::assertEquals([
            'type'      => 'enhanced',
            'bvam_hash' => null,
            'host'      => 'foo.com',
            'uri'       => 'http://foo.com/S1234.json',
        ], $info);

        $info = BvamUtil::parseBvamIssuanceDescription('http://foo.com/T1111111111111111111111111111.json');
        PHPUnit::assertEquals([
            'type'      => 'bvam',
            'bvam_hash' => 'T1111111111111111111111111111',
            'host'      => 'foo.com',
            'uri'       => 'http://foo.com/T1111111111111111111111111111.json',
        ], $info);

        $info = BvamUtil::parseBvamIssuanceDescription('foo.com/some/enhanced.json');
        PHPUnit::assertEquals([
            'type'      => 'enhanced',
            'bvam_hash' => null,
            'host'      => 'foo.com',
            'uri'       => 'http://foo.com/some/enhanced.json',
        ], $info);

        $info = BvamUtil::parseBvamIssuanceDescription('bad/data');
        PHPUnit::assertEquals([
            'type'      => null,
            'bvam_hash' => null,
            'host'      => null,
            'uri'       => null,
        ], $info);

        $info = BvamUtil::parseBvamIssuanceDescription('bad/data.json');
        PHPUnit::assertEquals([
            'type'      => null,
            'bvam_hash' => null,
            'host'      => null,
            'uri'       => null,
        ], $info);

        $info = BvamUtil::parseBvamIssuanceDescription('good.com/data.json');
        PHPUnit::assertEquals([
            'type'      => 'enhanced',
            'bvam_hash' => null,
            'host'      => 'good.com',
            'uri'       => 'http://good.com/data.json',
        ], $info);

        $info = BvamUtil::parseBvamIssuanceDescription('secure.com/T1111111111111111111111111111.json');
        PHPUnit::assertEquals([
            'type'      => 'bvam',
            'bvam_hash' => 'T1111111111111111111111111111',
            'host'      => 'secure.com',
            'uri'       => 'https://secure.com/T1111111111111111111111111111.json',
        ], $info);


    }

}
