<?php

namespace App\Handlers\AssetInfo;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\ServiceProvider;

/*
* EnhancedAssetInfoProvider
*/
class EnhancedAssetInfoProvider extends ServiceProvider
{

    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('enhancedAssetInfo.guzzle', function($app) {
            return new GuzzleClient();
        });
    }

}
