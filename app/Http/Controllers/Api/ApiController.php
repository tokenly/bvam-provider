<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApiController extends Controller
{

    protected function rawJsonResponse($json_string, $status=200) {
        return new Response($json_string, $status, ['Content-Type' => 'application/json']);
    }

    protected function getValidatedAttributes($request, $rules) {
        $this->validate($request, $rules);
        return $request->only(array_keys($rules));
    }

}
