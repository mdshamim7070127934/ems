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
        Schema::create('role_menu_permissions', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement();
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('menu_id');
            $table->boolean('can_access')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['role_id', 'menu_id'], 'uq_role_menu');
            $table->foreign('role_id', 'fk_rmp_role')
                ->references('id')
                ->on('roles')
                ->cascadeOnDelete();
            $table->foreign('menu_id', 'fk_rmp_menu')
                ->references('id')
                ->on('mst_menu')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_menu_permissions');
    }
};
