<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateXchainEventMonitorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('xchain_event_monitors', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36)->unique();

            $table->char('monitor_uuid', 36)->unique();
            $table->string('webhook_endpoint');
            $table->string('monitor_type')->unique();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('xchain_event_monitors');
    }
}

