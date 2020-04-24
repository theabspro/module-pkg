<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterModulesC extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign('modules_platform_id_foreign');
            $table->foreign('platform_id')->references('id')->on('platforms')->onDelete('SET NULL')->onUpdate('cascade');
        });        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign('modules_platform_id_foreign');
            $table->foreign('platform_id')->references('id')->on('configs')->onDelete('SET NULL')->onUpdate('cascade');
        });
    }
}
