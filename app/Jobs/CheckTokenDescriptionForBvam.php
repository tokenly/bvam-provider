<?php

namespace App\Jobs;

use App\Handlers\Bvam\Facade\BvamUtil;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckTokenDescriptionForBvam implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($asset_name)
    {
        $this->asset_name = $asset_name;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $asset_info_cache = app('App\Handlers\AssetInfo\AssetInfoCache');
        $token_info = $asset_info_cache->getInfo($this->asset_name);

        $confirmations = 1; // to make confirmed
        BvamUtil::processIssuance($token_info['asset'], $token_info['description'], $token_info['tx_hash'], $confirmations);
    }

}
