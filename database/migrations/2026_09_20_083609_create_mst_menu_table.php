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
        Schema::create('mst_menu', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('menu_parent_id')->default(0)->comment('( 0 => Main Menu )');
            $table->string('menu_name', 100);
            $table->text('column_description')->nullable();
            $table->string('menu_route', 100);
            $table->integer('menu_order');
            $table->string('screen_name')->nullable();
            $table->string('web_icon')->nullable();
            $table->string('mobile_icon')->nullable();
            $table->enum('status', Status::cases())->default(Status::Active);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['menu_parent_id', 'menu_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_menu');
    }
};
