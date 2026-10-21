<?php

use App\Enums\Master\CountryStatus;
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
        Schema::create('mst_country', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('sortname', 3);
            $table->string('name', 150);
            $table->string('currency', 100);
            $table->integer('phonecode');
            $table->enum('status', CountryStatus::cases())->default(CountryStatus::Inactive);
            $table->string('time_zone', 1000)->nullable();

            $table->unique('sortname');
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_country');
    }
};
