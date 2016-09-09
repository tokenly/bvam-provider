<?php

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use \PHPUnit_Framework_Assert as PHPUnit;

class EnhancedAssetInfoResolverTest extends TestCase
{

    protected $use_database = true;

    public function testLoadEnhancedAssetInfo()
    {
        $mock = new MockHandler([
            new Response(200, [], app('EnhancedAssetInfoHelper')->getEnhancedAssetInfoAsJson(['image' => null])),
        ]);
        $handler = HandlerStack::create($mock);
        app()->bind('enhancedAssetInfo.guzzle', function($app) use ($handler) {
            return new GuzzleClient(['handler' => $handler]);
        });


        $enhanced_asset_resolver = app('App\Handlers\AssetInfo\EnhancedAssetInfoResolver');

        $data = $enhanced_asset_resolver->resolveExtendedAssetInfoFromDescription('foo');
        PHPUnit::assertEquals([
            'is_enhanced'   => false,
            'is_bvam'       => false,
            'enhanced_data' => [],
            'had_error'     => false,
        ], $data);

        $data = $enhanced_asset_resolver->resolveExtendedAssetInfoFromDescription('http://mysite.foo/token-data.json');
        PHPUnit::assertEquals([
            'is_enhanced'   => true,
            'is_bvam'       => false,
            'enhanced_data' => [
                'asset'       => 'MYTOKEN',
                'description' => 'This is a long description of my token',
                'website'     => 'http://tokensite.foo/',
            ],
            'had_error'     => false,
        ], $data);

    }

    public function testLoadImageAndEnhancedAssetInfo()
    {
        $mock = new MockHandler([
            new Response(200, [], app('EnhancedAssetInfoHelper')->getEnhancedAssetInfoAsJson()),
            new Response(200, [], app('EnhancedAssetInfoHelper')->samplePNGImageBinary()),
        ]);
        $handler = HandlerStack::create($mock);
        app()->bind('enhancedAssetInfo.guzzle', function($app) use ($handler) {
            return new GuzzleClient(['handler' => $handler]);
        });

        $enhanced_asset_resolver = app('App\Handlers\AssetInfo\EnhancedAssetInfoResolver');
        $data = $enhanced_asset_resolver->resolveExtendedAssetInfoFromDescription('http://mysite.foo/token-data.json');
        PHPUnit::assertEquals([
            'is_enhanced'   => true,
            'is_bvam'       => false,
            'enhanced_data' => [
                'asset'        => 'MYTOKEN',
                'description'  => 'This is a long description of my token',
                'image'        => 'http://imagesite.foo/tokenimage.png',
                'image_base64' => app('EnhancedAssetInfoHelper')->samplePNGImageBase64(),
                'website'      => 'http://tokensite.foo/',
            ],
            'had_error'     => false,
        ], $data);

    }


}
