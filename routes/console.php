<?php

use Illuminate\Foundation\Inspiring;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');


Artisan::command('bvam-devel:queuejob', function () {
    $payload = json_decode(app('XchainQueueHelper')->buildReceiveNotification()['payload'], true);
    $job_class = config('xchainqueue.jobClass');
    $job = new $job_class($payload);
    $this->info('dispatching new '.(new \ReflectionClass($job))->getShortName());
    dispatch($job);

    $this->comment('done');
})->describe('Queue a sample job');;
