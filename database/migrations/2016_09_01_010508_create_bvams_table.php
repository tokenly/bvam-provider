<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBvamsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bvams', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36)->unique();

            $table->text('bvam_json');
            $table->string('hash')->unique();
            $table->string('asset')->default('');
            $table->string('txid')->nullable();
            $table->tinyInteger('status')->unsigned()->default(1);

            $table->timestamps();

            $table->timestamp('first_validated_at')->nullable()->index();
            $table->timestamp('last_validated_at')->nullable();
            $table->integer('confirmations')->nullable()->unsigned();

            $table->index('asset');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bvams');
    }
}
