<?php

use \PHPUnit_Framework_Assert as PHPUnit;

class BvamCategoryAPITest extends TestCase
{

    protected $use_database = true;

    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testAddBvamCategoryErrors()
    {
        $api = app('APITestHelper');
        $response = $api->callAPIAndReturnJSONContent('POST', 'category', ['foo' => 'bar'], 422);
        PHPUnit::assertContains('category field is required', $response['message']);

        $response = $api->callAPIAndReturnJSONContent('POST', 'category', ['category' => str_repeat('x', 30000)], 422);
        PHPUnit::assertContains('may not be greater than', $response['message']);

        $response = $api->callAPIAndReturnJSONContent('POST', 'category', ['category' => str_repeat('x', 200)], 400);
        PHPUnit::assertContains('BVAM Category Schema was invalid.', $response['message']);
    }

    public function testAddBvamCategoryParseError()
    {
        $api = app('APITestHelper');

        $bvam_category_string = json_encode([
            'foo' => 'bar',
        ], 192);


        $response = $api->callAPIAndReturnJSONContent('POST', 'category', ['category' => $bvam_category_string], 400);
        PHPUnit::assertContains('BVAM Category Schema was invalid.', $response['message']);
        PHPUnit::assertEquals([
                "The property category_id is required",
                "The property title is required",
                "The property version is required",
            ], $response['errors']
        );
    }

    public function testAddValidBvamCategoryAsString()
    {
        $api = app('APITestHelper');

        $bvam_category_string = app('BvamCategoryHelper')->defaultBvamCategoryJson();
        $response = $api->callAPIAndReturnJSONContent('POST', 'category', ['category' => $bvam_category_string], 200);

        PHPUnit::assertEquals('S2d6Ds3uDhy2ecrRWcEwy2WfFF19.json', $response['filename']);
        PHPUnit::assertEquals('http://bvam-provider.dev/S2d6Ds3uDhy2ecrRWcEwy2WfFF19.json', $response['uri']);
        PHPUnit::assertEquals('Test Category One', $response['title']);
        PHPUnit::assertEquals('BVAM Test Category One 201609a', $response['categoryId']);
        PHPUnit::assertEquals('1.0.0', $response['version']);
    }

    public function testAddValidBvamCategoryAsData()
    {
        $api = app('APITestHelper');

        $bvam_category_data = app('BvamCategoryHelper')->defaultBvamCategoryData();
        $response = $api->callAPIAndReturnJSONContent('POST', 'category', ['category' => $bvam_category_data], 200);

        PHPUnit::assertEquals('S2d6Ds3uDhy2ecrRWcEwy2WfFF19.json', $response['filename']);
        PHPUnit::assertEquals('http://bvam-provider.dev/S2d6Ds3uDhy2ecrRWcEwy2WfFF19.json', $response['uri']);
        PHPUnit::assertEquals('Test Category One', $response['title']);
        PHPUnit::assertEquals('BVAM Test Category One 201609a', $response['categoryId']);
        PHPUnit::assertEquals('1.0.0', $response['version']);
    }

    public function testAddDuplicateValidBvamCategory()
    {
        $api = app('APITestHelper');

        $bvam_category_data = app('BvamCategoryHelper')->defaultBvamCategoryData();
        $response = $api->callAPIAndReturnJSONContent('POST', 'category', ['category' => $bvam_category_data], 200);
        PHPUnit::assertEquals('S2d6Ds3uDhy2ecrRWcEwy2WfFF19.json', $response['filename']);

        // add the same data again
        $response = $api->callAPIAndReturnJSONContent('POST', 'category', ['category' => $bvam_category_data], 200);
        PHPUnit::assertEquals('S2d6Ds3uDhy2ecrRWcEwy2WfFF19.json', $response['filename']);

        // check that there is only one
        $repository = app('App\Repositories\BvamCategoryRepository');
        $all_categories = $repository->findAll();
        PHPUnit::assertCount(1, $all_categories);
    }

    public function testAddMultipleBvamCategoryVersions()
    {
        $api = app('APITestHelper');

        $bvam_category_data = app('BvamCategoryHelper')->defaultBvamCategoryData();
        $response = $api->callAPIAndReturnJSONContent('POST', 'category', ['category' => $bvam_category_data], 200);
        PHPUnit::assertEquals('S2d6Ds3uDhy2ecrRWcEwy2WfFF19.json', $response['filename']);

        // add the same data again
        $bvam_category_data_v2 = ['version' => '1.0.1'] + $bvam_category_data;
        $response = $api->callAPIAndReturnJSONContent('POST', 'category', ['category' => $bvam_category_data_v2], 200);
        PHPUnit::assertEquals('S3kTE7U7w3NJPmL2UquKMuf9pwFRu.json', $response['filename']);

        // check that both exist
        $repository = app('App\Repositories\BvamCategoryRepository');
        $all_categories = $repository->findAll();
        PHPUnit::assertCount(2, $all_categories);
        PHPUnit::assertEquals('1.0.0', $all_categories[0]['version']);
        PHPUnit::assertEquals('1.0.1', $all_categories[1]['version']);
    }


}
