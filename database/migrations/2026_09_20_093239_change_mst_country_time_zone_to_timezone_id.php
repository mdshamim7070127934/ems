<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mst_country', function (Blueprint $table) {
            $table->dropColumn('time_zone');
            $table->integer('timezone_id')->nullable()->after('phonecode');
            $table->foreign('timezone_id')
                ->references('id')
                ->on('mst_timezone')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mst_country', function (Blueprint $table) {
            $table->dropForeign(['timezone_id']);
            $table->dropColumn('timezone_id');
            $table->string('time_zone', 1000)->nullable();
        });
    }
};
