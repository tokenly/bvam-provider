<?php

use Illuminate\Http\Request;
use \PHPUnit_Framework_Assert as PHPUnit;

/**
*  APITestHelper
*/
class APITestHelper
{

    public $url_base = '/api/v1';

    function __construct() {
    }

    public function callAPIAndReturnJSONContent($method, $uri_or_url_extension, $parameters=[], $expected_response_code=200, $cookies = [], $files = [], $server = [], $content = null) {
        if (substr($uri_or_url_extension, 0, 1) == '/' or substr($uri_or_url_extension, 0, 7) == 'http://' ) {
            $uri = $uri_or_url_extension;
        } else {
            $uri = $this->extendURL($this->url_base, $uri_or_url_extension);
        }
        $request = $this->createAPIRequest($method, $uri, $parameters, $cookies, $files, $server, $content);
        $response = $this->runRequest($request);

        PHPUnit::assertEquals($expected_response_code, $response->getStatusCode(), "Response was: ".$response->getContent());

        return json_decode($response->getContent(), true);
    }


    protected function createAPIRequest($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null) {
        // convert a POST to json
        if ($parameters AND $method == 'POST' OR $method == 'PATCH' OR $method == 'PUT') {
            $content = json_encode($parameters, JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT);
            $server['CONTENT_TYPE'] = 'application/json';
            $parameters = [];
        }

        // always want JSON
        $server['HTTP_ACCEPT'] = 'application/json';

        return Request::create($uri, $method, $parameters, $cookies, $files, $server, $content);
    }


    ////////////////////////////////////////////////////////////////////////
    

    protected function extendURL($base_url, $url_extension) {
        return $base_url.(strlen($url_extension) ? '/'.ltrim($url_extension, '/') : '');
    }

    protected function runRequest($request) {
        $kernel = app('Illuminate\Contracts\Http\Kernel');
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        return $response;
    }


}