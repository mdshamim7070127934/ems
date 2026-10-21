<?php

use App\Enums\Master\Status;
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
        Schema::create('mst_city', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('country_id');
            $table->integer('state_id');
            $table->string('name', 100);
            $table->enum('status', Status::cases())->default(Status::Active);
            $table->dateTime('created_on')->useCurrent();
            $table->dateTime('updated_on')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['state_id', 'name']);
            $table->index('country_id');
            $table->foreign('country_id')
                ->references('id')
                ->on('mst_country')
                ->restrictOnDelete();
            $table->foreign('state_id')
                ->references('id')
                ->on('mst_state')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_city');
    }
};
