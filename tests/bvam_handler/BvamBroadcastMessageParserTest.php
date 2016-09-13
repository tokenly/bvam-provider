<?php

use App\Handlers\Bvam\Facade\BvamUtil;
use \PHPUnit_Framework_Assert as PHPUnit;

class BvamBroadcastMessageParserTest extends TestCase
{

    protected $use_database = false;

    public function testBvamBroadcastMessageParser()
    {
        $info = BvamUtil::parseBvamBroadcastMessage('');
        PHPUnit::assertEquals([
            'is_valid'    => false,
            'category_id' => null,
            'version'     => null,
            'bvam_info'   => null,
            'error'       => 'Not a category schema broadcast message',
        ], $info);

        $info = BvamUtil::parseBvamBroadcastMessage('Just a text description');
        PHPUnit::assertEquals([
            'is_valid'    => false,
            'category_id' => null,
            'version'     => null,
            'bvam_info'   => null,
            'error'       => 'Not a category schema broadcast message',
        ], $info);

        $info = BvamUtil::parseBvamBroadcastMessage('a;b;c');
        PHPUnit::assertEquals([
            'is_valid'    => false,
            'category_id' => null,
            'version'     => null,
            'bvam_info'   => null,
            'error'       => 'Not a category schema broadcast message',
        ], $info);

        $info = BvamUtil::parseBvamBroadcastMessage('a;b;c;d');
        PHPUnit::assertEquals([
            'is_valid'    => false,
            'category_id' => null,
            'version'     => null,
            'bvam_info'   => null,
            'error'       => 'Invalid marker',
        ], $info);

        // BVAMCS;ACME Car Title;1.0.0;https://bvam-provider.com/bvamcs/S4RGTgM6BJtuu2EUsvsG3GkTpGr2T.json
        $info = BvamUtil::parseBvamBroadcastMessage('BVAMCS;;c;d');
        PHPUnit::assertEquals([
            'is_valid'    => false,
            'category_id' => null,
            'version'     => null,
            'bvam_info'   => null,
            'error'       => 'Invalid category ID',
        ], $info);
        $info = BvamUtil::parseBvamBroadcastMessage('BVAMCS;xx##;c;d');
        PHPUnit::assertEquals([
            'is_valid'    => false,
            'category_id' => null,
            'version'     => null,
            'bvam_info'   => null,
            'error'       => 'Invalid category ID',
        ], $info);

        $info = BvamUtil::parseBvamBroadcastMessage('BVAMCS;A Category;c;d');
        PHPUnit::assertEquals([
            'is_valid'    => false,
            'category_id' => null,
            'version'     => null,
            'bvam_info'   => null,
            'error'       => 'Invalid version',
        ], $info);

        $info = BvamUtil::parseBvamBroadcastMessage('BVAMCS;A Category;0.0.1;d');
        PHPUnit::assertEquals([
            'is_valid'    => false,
            'category_id' => null,
            'version'     => null,
            'bvam_info'   => null,
            'error'       => 'Invalid BVAM url',
        ], $info);

        $info = BvamUtil::parseBvamBroadcastMessage('BVAMCS;A Category;0.0.1;http://foo.com/some/enhanced.json');
        PHPUnit::assertEquals([
            'is_valid'    => false,
            'category_id' => null,
            'version'     => null,
            'bvam_info'   => null,
            'error'       => 'Invalid BVAM url',
        ], $info);

        $info = BvamUtil::parseBvamBroadcastMessage('BVAMCS;A Category;0.0.1;http://foo.com/some/S1111111111111111111111111111.json');
        PHPUnit::assertEquals([
            'is_valid'    => true,
            'category_id' => 'A Category',
            'version'     => '0.0.1',
            'bvam_info'   => [
                'type'      => 'category',
                'bvam_hash' => 'S1111111111111111111111111111',
                'host'      => 'foo.com',
                'uri'       => 'http://foo.com/some/S1111111111111111111111111111.json',
            ],
            'error'       => null,
        ], $info);

        $info = BvamUtil::parseBvamBroadcastMessage('BVAMCS;A Category;0.0.1;secure.com/S1111111111111111111111111111.json');
        PHPUnit::assertEquals([
            'is_valid'    => true,
            'category_id' => 'A Category',
            'version'     => '0.0.1',
            'bvam_info'   => [
                'type'      => 'category',
                'bvam_hash' => 'S1111111111111111111111111111',
                'host'      => 'secure.com',
                'uri'       => 'https://secure.com/S1111111111111111111111111111.json',
            ],
            'error'       => null,
        ], $info);

        $info = BvamUtil::parseBvamBroadcastMessage('BVAMCS;A Category;0.0.1;S1111111111111111111111111111.json');
        PHPUnit::assertEquals([
            'is_valid'    => true,
            'category_id' => 'A Category',
            'version'     => '0.0.1',
            'bvam_info'   => [
                'type'      => 'category',
                'bvam_hash' => 'S1111111111111111111111111111',
                'host'      => 'bvam-provider.dev',
                'uri'       => 'https://bvam-provider.dev/S1111111111111111111111111111.json',
            ],
            'error'       => null,
        ], $info);

    }

}
