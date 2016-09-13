<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBvamCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bvam_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->char('uuid', 36)->unique();

            $table->text('category_json');
            $table->string('hash')->index();
            $table->string('category_id')->index();
            $table->string('title')->index();
            $table->string('version');
            $table->string('txid')->nullable();
            $table->string('owner')->nullable();
            $table->tinyInteger('status')->unsigned()->default(1);

            $table->timestamps();

            $table->timestamp('first_validated_at')->nullable()->index();
            $table->timestamp('last_validated_at')->nullable();
            $table->integer('confirmations')->nullable()->unsigned();

            $table->unique(['hash', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('bvam_categories');
    }
}
