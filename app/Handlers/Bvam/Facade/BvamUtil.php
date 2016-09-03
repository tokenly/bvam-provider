<?php

namespace App\Handlers\Bvam\Facade;

use Illuminate\Support\Facades\Facade;


class BvamUtil extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'App\Handlers\Bvam\BvamUtil';
    }

}
