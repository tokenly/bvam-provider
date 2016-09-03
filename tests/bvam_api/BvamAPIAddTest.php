<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class BvamAPIAddTest extends TestCase
{

    protected $use_database = true;

    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testAddBvamErrors()
    {
        $api = app('APITestHelper');
        $response = $api->callAPIAndReturnJSONContent('POST', 'bvam', ['foo' => 'bar'], 422);
        PHPUnit::assertContains('bvam field is required', $response['message']);

        $response = $api->callAPIAndReturnJSONContent('POST', 'bvam', ['bvam' => str_repeat('x', 30000)], 422);
        PHPUnit::assertContains('may not be greater than', $response['message']);

        $response = $api->callAPIAndReturnJSONContent('POST', 'bvam', ['bvam' => str_repeat('x', 200)], 400);
        PHPUnit::assertContains('BVAM was invalid.', $response['message']);
    }

    public function testAddBvamParseError()
    {
        $api = app('APITestHelper');

        $bvam_string = json_encode([
            'asset' => 'MYTOKEN',
        ], 192);

        $response = $api->callAPIAndReturnJSONContent('POST', 'bvam', ['bvam' => $bvam_string], 400);
        PHPUnit::assertContains('BVAM was invalid', $response['message']);
        PHPUnit::assertEquals(["The property name is required","The property meta is required",], $response['errors']);
    }

    public function testAddValidBvamAsString()
    {
        $api = app('APITestHelper');

        $bvam_string = app('BvamHelper')->defaultBvamJson();
        $response = $api->callAPIAndReturnJSONContent('POST', 'bvam', ['bvam' => $bvam_string], 200);

        PHPUnit::assertEquals('T2XRkYJQz7xjUt6HeDoFxTLSMywak.json', $response['filename']);
        PHPUnit::assertEquals('http://bvam-provider.dev/T2XRkYJQz7xjUt6HeDoFxTLSMywak.json', $response['uri']);
        PHPUnit::assertEquals('MYTOKEN', $response['asset']);
    }

    public function testAddValidBvamAsData()
    {
        $api = app('APITestHelper');

        $bvam_data = app('BvamHelper')->defaultBvamData();
        $response = $api->callAPIAndReturnJSONContent('POST', 'bvam', ['bvam' => $bvam_data], 200);

        PHPUnit::assertEquals('T2XRkYJQz7xjUt6HeDoFxTLSMywak.json', $response['filename']);
        PHPUnit::assertEquals('http://bvam-provider.dev/T2XRkYJQz7xjUt6HeDoFxTLSMywak.json', $response['uri']);
        PHPUnit::assertEquals('MYTOKEN', $response['asset']);
    }

    public function testAddDuplicateValidBvam()
    {
        $api = app('APITestHelper');

        $bvam_data = app('BvamHelper')->defaultBvamData();
        $response = $api->callAPIAndReturnJSONContent('POST', 'bvam', ['bvam' => $bvam_data], 200);
        PHPUnit::assertEquals('T2XRkYJQz7xjUt6HeDoFxTLSMywak.json', $response['filename']);

        // add the same data again
        $response = $api->callAPIAndReturnJSONContent('POST', 'bvam', ['bvam' => $bvam_data], 200);
        PHPUnit::assertEquals('T2XRkYJQz7xjUt6HeDoFxTLSMywak.json', $response['filename']);
    }

}
