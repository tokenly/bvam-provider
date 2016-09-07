<?php

namespace App\Repositories;

use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use Exception;

/*
* XchainEventMonitorRepository
*/
class XchainEventMonitorRepository extends APIRepository
{

    protected $model_type = 'App\Models\XchainEventMonitor';

}
