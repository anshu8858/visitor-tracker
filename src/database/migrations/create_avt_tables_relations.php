<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvtTablesRelations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('avt_query_arguments', function (Blueprint $table) {
            $table->foreign('query_id')
                ->references('id')
                ->on('avt_queries')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_route_paths', function (Blueprint $table) {
            $table->foreign('route_id')
                ->references('id')
                ->on('avt_routes')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_route_path_parameters', function (Blueprint $table) {
            $table->foreign('route_path_id')
                ->references('id')
                ->on('avt_route_paths')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_referers', function (Blueprint $table) {
            $table->foreign('domain_id')
                ->references('id')
                ->on('avt_domains')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_sessions', function (Blueprint $table) {
            $table->foreign('device_id')
                ->references('id')
                ->on('avt_devices')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_sessions', function (Blueprint $table) {
            $table->foreign('agent_id')
                ->references('id')
                ->on('avt_agents')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_sessions', function (Blueprint $table) {
            $table->foreign('referer_id')
                ->references('id')
                ->on('avt_referers')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_sessions', function (Blueprint $table) {
            $table->foreign('cookie_id')
                ->references('id')
                ->on('avt_cookies')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_sessions', function (Blueprint $table) {
            $table->foreign('geoip_id')
                ->references('id')
                ->on('avt_geoip')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_sessions', function (Blueprint $table) {
            $table->foreign('language_id')
                    ->references('id')
                    ->on('avt_languages')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
        });

        Schema::table('avt_log', function (Blueprint $table) {
            $table->foreign('session_id')
                ->references('id')
                ->on('avt_sessions')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_log', function (Blueprint $table) {
            $table->foreign('path_id')
                ->references('id')
                ->on('avt_paths')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_log', function (Blueprint $table) {
            $table->foreign('query_id')
                ->references('id')
                ->on('avt_queries')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_log', function (Blueprint $table) {
            $table->foreign('route_path_id')
                ->references('id')
                ->on('avt_route_paths')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_log', function (Blueprint $table) {
            $table->foreign('error_id')
                ->references('id')
                ->on('avt_errors')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_events_log', function (Blueprint $table) {
            $table->foreign('event_id')
                ->references('id')
                ->on('avt_events')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_events_log', function (Blueprint $table) {
            $table->foreign('class_id')
                ->references('id')
                ->on('avt_system_classes')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_events_log', function (Blueprint $table) {
            $table->foreign('log_id')
                ->references('id')
                ->on('avt_log')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_sql_query_bindings_parameters', function (Blueprint $table) {
            $table->foreign('sql_query_bindings_id', 'avt_sqlqb_parameters')
                ->references('id')
                ->on('avt_sql_query_bindings')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_sql_queries_log', function (Blueprint $table) {
            $table->foreign('log_id')
                ->references('id')
                ->on('avt_log')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_sql_queries_log', function (Blueprint $table) {
            $table->foreign('sql_query_id')
                ->references('id')
                ->on('avt_sql_queries')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('avt_referers_search_terms', function (Blueprint $table) {
            $table->foreign('referer_id', 'avt_referers_referer_id_fk')
                ->references('id')
                ->on('avt_referers')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Tables will be dropped in the correct order... :)
    }
}
