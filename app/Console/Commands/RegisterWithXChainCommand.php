<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Tokenly\LaravelEventLog\Facade\EventLog;

class RegisterWithXChainCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xchain:register {--delete} {type=all}';

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
        $type = $this->argument('type');
        $delete = $this->option('delete');

        $types = ['block','issuance','broadcast'];
        if ($type AND $type != 'all') {
            $types = [$type];
        }

        // get the existing types
        $repository = app('App\Repositories\XchainEventMonitorRepository');
        $all_monitors = $repository->findAll()->keyBy(function($event_monitor) {
            return $event_monitor['monitor_type'];
        });
        // echo "\$all_monitors: ".json_encode($all_monitors, 192)."\n";

        $xchain_client = app('Tokenly\XChainClient\Client');
        $webhook_endpoint = env('SITE_HOST').'/'.ltrim(env('XCHAIN_CALLBACK_URL'), '/');
        foreach($types as $type) {
            if (isset($all_monitors[$type])) {
                // check existing
                $existing_monitor = $all_monitors[$type];

                if ($delete) {
                    $this->comment("Deleting XChain $type event monitor.");
                    $api_response = $xchain_client->destroyEventMonitor($existing_monitor['monitor_uuid']);

                    // remove from repository
                    $api_response = $repository->delete($existing_monitor);

                    $this->comment("XChain $type event monitor deleted.");

                } else {
                    $up_to_date = true;
                    if (
                        $existing_monitor['webhook_endpoint'] != $webhook_endpoint
                    ) {
                        $up_to_date = false;
                    }
                    if ($up_to_date) {
                        $this->comment("Existing XChain $type event monitor was up to date for $webhook_endpoint ({$existing_monitor['uuid']}).");
                    } else {
                        $this->comment("Updating XChain $type event monitor updated ({$existing_monitor['uuid']}).");
                        $api_response = $xchain_client->updateEventMonitor($existing_monitor['monitor_uuid'], $webhook_endpoint, $type);
                        // echo "\$api_response: ".json_encode($api_response, 192)."\n";

                        $repository->update($existing_monitor, [
                            'webhook_endpoint' => $webhook_endpoint,
                            'monitor_type'     => $type,
                        ]);

                        $this->comment("XChain $type event monitor updated ({$existing_monitor['uuid']}).");

                    }
                }

            } else if (!$delete) {
                $this->comment("Creating new XChain $type event monitor.");

                // add new
                $api_response = $xchain_client->newEventMonitor($webhook_endpoint, $type);

                // create a new entry in the database
                $new_monitor = $repository->create([
                    'monitor_uuid'     => $api_response['id'],
                    'webhook_endpoint' => $webhook_endpoint,
                    'monitor_type'     => $type,
                ]);
                $this->comment("New XChain $type event monitor created ({$new_monitor['uuid']}).");
            }
        }

        $this->info("done.");
    }
}
