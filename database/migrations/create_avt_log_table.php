<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvtLogTable extends Migration
{
    /**
     * Table related to this migration.
     *
     * @var string
     */
    private $table = 'avt_log';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            $this->table,
            function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->bigInteger('session_id')->unsigned()->index();
                $table->bigInteger('path_id')->unsigned()->nullable()->index();
                $table->bigInteger('query_id')->unsigned()->nullable()->index();
                $table->string('method', 10)->index();
                $table->bigInteger('route_path_id')->unsigned()->nullable()->index();
                $table->boolean('is_ajax');
                $table->boolean('is_secure');
                $table->boolean('is_json');
                $table->boolean('wants_json');
                $table->bigInteger('error_id')->unsigned()->nullable()->index();
                $table->integer('referer_id')->unsigned()->nullable()->index();

                $table->timestamps();
                $table->index('created_at');
                $table->index('updated_at');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $this->drop($this->table);
    }
}
