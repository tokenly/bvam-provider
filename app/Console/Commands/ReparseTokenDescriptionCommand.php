<?php

namespace App\Console\Commands;

use App\Jobs\CheckTokenDescriptionForBvam;
use Illuminate\Bus\dispatch;
use Illuminate\Console\Command;
use Tokenly\LaravelEventLog\Facade\EventLog;

class ReparseTokenDescriptionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bvam:reparse-token {--clear-cache} {asset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register callbacks with XChain';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $asset = $this->argument('asset');
        $clear_cache = $this->option('clear-cache');

        if ($clear_cache) {
            app('App\Handlers\AssetInfo\AssetInfoCache')->forget($asset);
        }

        $job = new CheckTokenDescriptionForBvam($asset);
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatchNow($job);

        $this->info("done.");
    }
}
